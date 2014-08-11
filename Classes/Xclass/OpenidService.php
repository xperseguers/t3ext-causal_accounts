<?php

namespace Causal\CausalAccounts\Xclass;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013-2014 Xavier Perseguers <xavier@causal.ch>
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
 * Extends \TYPO3\CMS\Openid\OpenidService to support short OpenID authentication.
 *
 * @category    XCLASS
 * @package     TYPO3
 * @subpackage  tx_causalaccounts
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class OpenidService extends \TYPO3\CMS\Openid\OpenidService {

	/** @var string */
	static protected $extKey = 'causal_accounts';

	/**
	 * @var array Contains the configuration values.
	 */
	protected $config;

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
				$config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][static::$extKey]);
				if (trim($config['openIdProvider']) !== '') {
					$openIDIdentifier .= '.' . trim($config['openIdProvider']);
				}
			}
			$escapedIdentifier = $GLOBALS['TYPO3_DB']->quoteStr($openIDIdentifier, $this->authenticationInformation['db_user']['table']);
			$condition = 'tx_openid_openid IN (' . '\'http://' . $escapedIdentifier . '\',' . '\'http://' . $escapedIdentifier . '/\',' . '\'https://' . $escapedIdentifier . '\',' . '\'https://' . $escapedIdentifier . '/\'' . ')';
			$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('tx_openid_openid', $this->authenticationInformation['db_user']['table'], $condition);
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

	/**
	 * Initializes authentication for this service.
	 *
	 * Synchronization is started before authentication.
	 *
	 * @param string $subType: Subtype for authentication (either "getUserFE" or "getUserBE")
	 * @param array $loginData: Login data submitted by user and preprocessed by AbstractUserAuthentication
	 * @param array $authenticationInformation: Additional TYPO3 information for authentication services (unused here)
	 * @param \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication $parentObject Calling object
	 * @return void
	 */
	public function initAuth($subType, array $loginData, array $authenticationInformation, \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication &$parentObject) {
		if ($this->initConfiguration()) {
			$this->synchronize();
		}

		parent::initAuth($subType, $loginData, $authenticationInformation, $parentObject);
	}

	/**
	 * Synchronizes user and set timestamp of last synchronisation.
	 *
	 * @return void
	 */
	protected function synchronize() {
		$synchronisationInterval = (int) $this->config['updateInterval'];
		if ($synchronisationInterval <= 0) {
			return;
		}
		$currentTimestamp = time();
		/** @var \TYPO3\CMS\Core\Registry $registry */
		$registry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
		$lastSynchronisation = $registry->get(static::$extKey, 'lastSynchronisation');
		if (($currentTimestamp - $lastSynchronisation) >= $synchronisationInterval) {
			/** @var \Causal\CausalAccounts\Task\SynchronizationTask $syncTask */
			$syncTask = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Causal\\CausalAccounts\\Task\\SynchronizationTask');
			if ($syncTask->execute()) {
				$registry->set(static::$extKey, 'lastSynchronisation', time());
			}
		}
	}

	/**
	 * Injects extension configuration into $this->config
	 *
	 * @return bool TRUE if operation succeeded, otherwise FALSE
	 */
	protected function initConfiguration() {
		$this->config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][static::$extKey]);
		if (!is_array($this->config) || !isset($this->config['updateInterval'])) {
			return FALSE;
		}
		return TRUE;
	}

}
