<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Xavier Perseguers <xavier@causal.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Service "Short OpenID Authentication" for the "openid" extension.
 *
 * This class basically overrides the class in TYPO3 4.5 and 4.6 with status
 * of it in 4.7 and adds support for short OpenID authentication.
 *
 * @category    Service
 * @package     TYPO3
 * @subpackage  tx_causalaccounts
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class ux_tx_openid_sv1 extends tx_openid_sv1 {

	/** @var string */
	static protected $xclassExtKey = 'causal_accounts';

	/**
	 * @var string OpenID identifier after it has been normalized.
	 */
	protected $openIDIdentifier;

	/**
	 * @var array Contains the configuration values.
	 */
	protected $config;

	/**
	 * Initializes authentication for this service.
	 *
	 * Synchronization is started before authentication.
	 *
	 * @param	string			$subType: Subtype for authentication (either "getUserFE" or "getUserBE")
	 * @param	array			$loginData: Login data submitted by user and preprocessed by t3lib/class.t3lib_userauth.php
	 * @param	array			$authenticationInformation: Additional TYPO3 information for authentication services (unused here)
	 * @param	t3lib_userAuth	$parentObject: Calling object
	 * @return	void
	 */
	public function initAuth($subType, array $loginData, array $authenticationInformation, t3lib_userAuth &$parentObject) {
		if ($this->initConfiguration()) {
			$this->synchronize();
		}

		// Store login and authentication data
		$this->loginData = $loginData;
		$this->authenticationInformation = $authenticationInformation;

		// Implement normalization according to OpenID 2.0 specification
		$this->openIDIdentifier = $this->normalizeOpenID($this->loginData['uname']);

		// If we are here after authentication by the OpenID server, get its response.
		if (t3lib_div::_GP('tx_openid_mode') == 'finish' && $this->openIDResponse == NULL) {
			$this->includePHPOpenIDLibrary();
			$openIDConsumer = $this->getOpenIDConsumer();
			$this->openIDResponse = $openIDConsumer->complete($this->getReturnURL());
		}
		$this->parentObject = $parentObject;
	}

	/**
	 * Synchronize user and set timestamp of last synchronisation.
	 *
	 * @return void
	 */
	protected function synchronize() {
		$synchronisationInterval = (int) $this->config['updateInterval'];
		if ($synchronisationInterval <= 0) {
			return;
		}
		$currentTimestamp = time();
		/** @var t3lib_Registry $registry */
		$registry = t3lib_div::makeInstance('t3lib_Registry');
		$lastSynchronisation = $registry->get(static::$xclassExtKey, 'lastSynchronisation');
		if (($currentTimestamp - $lastSynchronisation) >= $synchronisationInterval) {
			/** @var tx_causalaccounts_synchronizationtask $syncTask */
			$syncTask = t3lib_div::makeInstance('tx_causalaccounts_synchronizationtask');
			if ($syncTask->execute()) {
				$registry->set(static::$xclassExtKey, 'lastSynchronisation', time());
			}
		}
	}

	/**
	 * Inject extension configuration into $this->config
	 *
	 * @return bool TRUE if operation succeeded, otherwise FALSE
	 */
	protected function initConfiguration() {
		$this->config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][static::$xclassExtKey]);
		if (!is_array($this->config) || !isset($this->config['updateInterval'])) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * This function returns the user record back to the t3lib_userAuth. it does not
	 * mean that user is authenticated, it means only that user is found. This
	 * function makes sure that user cannot be authenticated by any other service
	 * if user tries to use OpenID to authenticate.
	 *
	 * @return	mixed		User record (content of fe_users/be_users as appropriate for the current mode)
	 */
	public function getUser() {
		$userRecord = NULL;
		if ($this->loginData['status'] == 'login') {
			if ($this->openIDResponse instanceof Auth_OpenID_ConsumerResponse) {
				$GLOBALS['BACK_PATH'] = $this->getBackPath();
				// We are running inside the OpenID return script
				// Note: we cannot use $this->openIDResponse->getDisplayIdentifier()
				// because it may return a different identifier. For example,
				// LiveJournal server converts all underscore characters in the
				// original identifier to dashes.
				if ($this->openIDResponse->status == Auth_OpenID_SUCCESS) {
					$openIDIdentifier = $this->getFinalOpenIDIdentifier();
					if ($openIDIdentifier) {
						$userRecord = $this->getUserRecord($openIDIdentifier);
						if ($userRecord != NULL) {
							$this->writeLog('User \'%s\' logged in with OpenID \'%s\'',
								$userRecord[$this->parentObject->formfield_uname], $openIDIdentifier);
						} else {
							$this->writeLog('Failed to login user using OpenID \'%s\'',
								$openIDIdentifier);
						}
					}
				}
			} else {
				// Here if user just started authentication
				$userRecord = $this->getUserRecord($this->openIDIdentifier);
			}
			// The above function will return user record from the OpenID. It means that
			// user actually tried to authenticate using his OpenID. In this case
			// we must change the password in the record to a long random string so
			// that this user cannot be authenticated with other service.
			if (is_array($userRecord)) {
				$userRecord[$this->authenticationInformation['db_user']['userident_column']] = uniqid($this->prefixId . LF, TRUE);
			}
		}
		return $userRecord;
	}

	/**
	 * Authenticates user using OpenID.
	 *
	 * @param	array		$userRecord	User record
	 * @return	int		Code that shows if user is really authenticated.
	 * @see	t3lib_userAuth::checkAuthentication()
	 */
	public function authUser(array $userRecord) {
		$result = 100;	// 100 means "we do not know, continue"

		if ($userRecord['tx_openid_openid'] !== '') {
			// Check if user is identified by the OpenID
			if ($this->openIDResponse instanceof Auth_OpenID_ConsumerResponse) {
				// If we have a response, it means OpenID server tried to authenticate
				// the user. Now we just look what is the status and provide
				// corresponding response to the caller
				if ($this->openIDResponse->status == Auth_OpenID_SUCCESS) {
					// Success (code 200)
					$result = 200;
				} else {
					$this->writeLog('OpenID authentication failed with code \'%s\'.',
							$this->openIDResponse->status);
				}
			} else {
				// We may need to send a request to the OpenID server.
				// First, check if the supplied login name equals with the configured OpenID.
				if ($this->openIDIdentifier == $userRecord['tx_openid_openid']) {
					// Next, check if the user identifier looks like an OpenID identifier.
					// Prevent PHP warning in case if identifiers is not an OpenID identifier
					// (not an URL).
					// TODO: Improve testing here. After normalization has been added, now all identifiers will succeed here...
					$urlParts = @parse_url($this->openIDIdentifier);
					if (is_array($urlParts) && $urlParts['scheme'] != '' && $urlParts['host']) {

						// Yes, this looks like a good OpenID. Ask OpenID server (should not return)
						$this->sendOpenIDRequest();

						// If we are here, it means we have a valid OpenID but failed to
						// contact the server. We stop authentication process.
						// Alternatively it may mean that OpenID format is not correct.
						// In both cases we return code 0 (complete failure)
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Gets user record for the user with the OpenID provided by the user
	 *
	 * @param	string		$openIDIdentifier	OpenID identifier to search for
	 * @return	array		Database fields from the table that corresponds to the current login mode (FE/BE)
	 */
	protected function getUserRecord($openIDIdentifier) {
		$record = NULL;
		if ($openIDIdentifier) {
			// $openIDIdentifier always as a trailing slash because it got normalized
			// but tx_openid_openid possibly not so check for both alternatives in database
			$record = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*',
				$this->authenticationInformation['db_user']['table'],
				'tx_openid_openid IN (' .
					$GLOBALS['TYPO3_DB']->fullQuoteStr($openIDIdentifier, $this->authenticationInformation['db_user']['table']) .
					',' . $GLOBALS['TYPO3_DB']->fullQuoteStr(rtrim($openIDIdentifier, '/'), $this->authenticationInformation['db_user']['table']) .
					')' .
					$this->authenticationInformation['db_user']['check_pid_clause'] .
					$this->authenticationInformation['db_user']['enable_clause']);
			if ($record) {
				// Make sure to work only with normalized OpenID during the whole process
				$record['tx_openid_openid'] = $this->normalizeOpenID($record['tx_openid_openid']);
			}
		} else {
			// This should never happen and generally means hack attempt.
			// We just log it and do not return any records.
			$this->writeLog('getUserRecord is called with the empty OpenID');
		}
		return $record;
	}

	/**
	 * Sends request to the OpenID server to authenticate the user with the
	 * given ID. This function is almost identical to the example from the PHP
	 * OpenID library. Due to the OpenID specification we cannot do a slient login.
	 * Sometimes we have to redirect to the OpenID provider web site so that
	 * user can enter his password there. In this case we will redirect and provide
	 * a return adress to the special script inside this directory, which will
	 * handle the result appropriately.
	 *
	 * This function does not return on success. If it returns, it means something
	 * went totally wrong with OpenID.
	 *
	 * @return	void
	 */
	protected function sendOpenIDRequest() {
		$this->includePHPOpenIDLibrary();

		$openIDIdentifier = $this->openIDIdentifier;

		// Initialize OpenID client system, get the consumer
		$openIDConsumer = $this->getOpenIDConsumer();

		// Begin the OpenID authentication process
		$authenticationRequest = $openIDConsumer->begin($openIDIdentifier);
		if (!$authenticationRequest) {
			// Not a valid OpenID. Since it can be some other ID, we just return
			// and let other service handle it.
			$this->writeLog('Could not create authentication request for OpenID identifier \'%s\'', $openIDIdentifier);
			return;
		}

		// Redirect the user to the OpenID server for authentication.
		// Store the token for this authentication so we can verify the
		// response.

		// For OpenID version 1, we *should* send a redirect. For OpenID version 2,
		// we should use a Javascript form to send a POST request to the server.
		$returnURL = $this->getReturnURL();
		$trustedRoot = t3lib_div::getIndpEnv('TYPO3_SITE_URL');

		if ($authenticationRequest->shouldSendRedirect()) {
			$redirectURL = $authenticationRequest->redirectURL($trustedRoot, $returnURL);

			// If the redirect URL can't be built, return. We can only return.
			if (Auth_OpenID::isFailure($redirectURL)) {
				$this->writeLog('Authentication request could not create redirect URL for OpenID identifier \'%s\'', $openIDIdentifier);
				return;
			}

			// Send redirect. We use 303 code because it allows to redirect POST
			// requests without resending the form. This is exactly what we need here.
			// See http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.3.4
			@ob_end_clean();
			t3lib_utility_Http::redirect($redirectURL, t3lib_utility_Http::HTTP_STATUS_303);
		} else {
			$formHtml = $authenticationRequest->htmlMarkup($trustedRoot,
							$returnURL, FALSE, array('id' => 'openid_message'));

			// Display an error if the form markup couldn't be generated;
			// otherwise, render the HTML.
			if (Auth_OpenID::isFailure($formHtml)) {
				// Form markup cannot be generated
				$this->writeLog('Could not create form markup for OpenID identifier \'%s\'', $openIDIdentifier);
				return;
			} else {
				@ob_end_clean();
				echo $formHtml;
			}
		}
		// If we reached this point, we must not return!
		exit;
	}

	/**
	 * Creates return URL for the OpenID server. When a user is authenticated by
	 * the OpenID server, the user will be sent to this URL to complete
	 * authentication process with the current site. We send it to our script.
	 *
	 * @return	string		Return URL
	 */
	protected function getReturnURL() {
		if ($this->authenticationInformation['loginType'] == 'FE') {
			// We will use eID to send user back, create session data and
			// return to the calling page.
			// Notice: 'pid' and 'logintype' parameter names cannot be changed!
			// They are essential for FE user authentication.
			$returnURL = 'index.php?eID=tx_openid&' .
						'pid=' . $this->authenticationInformation['db_user']['checkPidList'] . '&' .
						'logintype=login&';
		} else {
			// In the Backend we will use dedicated script to create session.
			// It is much easier for the Backend to manage users.
			// Notice: 'login_status' parameter name cannot be changed!
			// It is essential for BE user authentication.
			$absoluteSiteURL = substr(t3lib_div::getIndpEnv('TYPO3_SITE_URL'), strlen(t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST')));
			$returnURL = $absoluteSiteURL . TYPO3_mainDir . 'sysext/' . $this->extKey . '/class.tx_openid_return.php?login_status=login&';
		}
		if (t3lib_div::_GP('tx_openid_mode') == 'finish') {
			$requestURL = t3lib_div::_GP('tx_openid_location');
			$claimedIdentifier = t3lib_div::_GP('tx_openid_claimed');
		} else {
			$requestURL = t3lib_div::getIndpEnv('TYPO3_REQUEST_URL');
			$claimedIdentifier = $this->openIDIdentifier;
		}
		$returnURL .= 'tx_openid_location=' . rawurlencode($requestURL) . '&' .
						'tx_openid_mode=finish&' .
						'tx_openid_claimed=' . rawurlencode($claimedIdentifier) . '&' .
						'tx_openid_signature=' . $this->getSignature($claimedIdentifier);
		return t3lib_div::locationHeaderUrl($returnURL);
	}

	/**
	 * Implement normalization according to OpenID 2.0 specification
	 * See http://openid.net/specs/openid-authentication-2_0.html#normalization
	 *
	 * @param string $openIDIdentifier OpenID identifier to normalize
	 * @return string Normalized OpenID identifier
	 */
	protected function normalizeOpenID($openIDIdentifier) {
		// Strip everything with and behind the fragment delimiter character "#"
		if (strpos($openIDIdentifier, '#') !== FALSE) {
			$openIDIdentifier = preg_replace('/#.*$/', '', $openIDIdentifier);
		}

		// A URI with a missing scheme is normalized to a http URI
		if (!preg_match('#^https?://#', $openIDIdentifier)) {
			if (strpos($openIDIdentifier, '.') === FALSE) {
				// Short OpenID Authentication
				$config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][static::$xclassExtKey]);
				if (trim($config['openIdProvider']) !== '') {
					$openIDIdentifier .= '.' . trim($config['openIdProvider']);
				}
			}
			$escapedIdentifier = $GLOBALS['TYPO3_DB']->quoteStr($openIDIdentifier, $this->authenticationInformation['db_user']['table']);
			$condition = 'tx_openid_openid IN (' .
					'\'http://' . $escapedIdentifier . '\',' .
					'\'http://' . $escapedIdentifier . '/\',' .
					'\'https://' . $escapedIdentifier . '\',' .
					'\'https://' . $escapedIdentifier . '/\'' .
				')';

			$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('tx_openid_openid',
				$this->authenticationInformation['db_user']['table'], $condition
			);
			if (is_array($row)) {
				$openIDIdentifier = $row['tx_openid_openid'];
			}
		}

		// An empty path component is normalized to a slash
		// (e.g. "http://domain.org" -> "http://domain.org/")
		if (preg_match('#^https?://[^/]+$#', $openIDIdentifier)) {
			$openIDIdentifier .= '/';
		}

		return $openIDIdentifier;
	}

}
