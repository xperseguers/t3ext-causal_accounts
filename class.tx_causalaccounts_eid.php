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
 * eID controller for the 'causal_accounts' extension.
 *
 * @category    Controller
 * @package     TYPO3
 * @subpackage  tx_causalaccounts
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id$
 */
class tx_causalaccounts_eid {

	protected static $extKey = 'causal_accounts';

	/**
	 * @var array
	 */
	protected $config;

	/**
	 * Default action.
	 *
	 * @return array
	 * @throws RuntimeException
	 */
	public function main() {
		$this->init();

		$allowedIps = t3lib_div::trimExplode(',', $this->config['allowedIps'], TRUE);

		if ($this->config['debug']) {
			t3lib_div::sysLog('Connection from ' . t3lib_div::getIndpEnv('REMOTE_ADDR'), self::$extKey);
		}

		if ($this->config['mode'] !== 'M' || (count($allowedIps) && !t3lib_div::inArray($allowedIps, t3lib_div::getIndpEnv('REMOTE_ADDR')))) {
			$this->denyAccess();
		}

		$this->initTSFE();

		if (!empty($this->config['synchronizeDeletedAccounts']) && $this->config['synchronizeDeletedAccounts']) {
			$additionalFields = ', deleted';
			$additionalWhere = '';
		} else {
			$additionalFields = '';
			$additionalWhere = ' AND deleted=0';
		}
		$administrators = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'username, admin, disable, realName, email, TSconfig, starttime, endtime, lang, tx_openid_openid' . $additionalFields,
			'be_users',
			'admin=1 AND tx_openid_openid<>\'\'' . $additionalWhere
		);

		if (count($administrators)) {
			$key = $this->config['preSharedKey'];
			$data = json_encode($administrators);
			$encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $data, MCRYPT_MODE_CBC, md5(md5($key)));
			$encrypted = base64_encode($encrypted);

			return $encrypted;
		} else {
			throw new RuntimeException('No administrators found', 1327586994);
		}
	}

	/**
	 * Deny access to this module by pretending page was not found.
	 *
	 * @return void
	 */
	protected function denyAccess() {
		header('HTTP/1.0 404 Not Found');
		exit;
	}

	/**
	 * Initializes this class.
	 *
	 * @return void
	 * @throws RuntimeException
	 */
	protected function init() {
		$this->config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::$extKey]);
		if (!is_array($this->config)) {
			throw new RuntimeException('Extension "' . self::$extKey . '" is not configured', 1327582564);
		}
	}

	/**
	 * Initializes TSFE and sets $GLOBALS['TSFE'].
	 *
	 * @return void
	 */
	protected function initTSFE() {
		$GLOBALS['TSFE'] = t3lib_div::makeInstance('tslib_fe', $GLOBALS['TYPO3_CONF_VARS'], t3lib_div::_GP('id'), '');
		$GLOBALS['TSFE']->connectToDB();
		$GLOBALS['TSFE']->initFEuser();
		$GLOBALS['TSFE']->checkAlternativeIdMethods();
		$GLOBALS['TSFE']->determineId();
		$GLOBALS['TSFE']->getCompressedTCarray();
		$GLOBALS['TSFE']->initTemplate();
		$GLOBALS['TSFE']->getConfigArray();

		// Get linkVars, absRefPrefix, etc
		TSpagegen::pagegenInit();
	}

}

/** @var $output tx_causalaccounts_eid */
$output = t3lib_div::makeInstance('tx_causalaccounts_eid');

$ret = array(
	'success' => TRUE,
	'data' => array(),
	'errors' => array(),
);
try {
	$ret['data'] = $output->main();
} catch (Exception $e) {
	$ret['success'] = FALSE;
	$ret['errors'][] = 'Error ' . $e->getCode() . ': ' . $e->getMessage();
}

$ajaxData = json_encode($ret);

header('Content-Length: ' . strlen($ajaxData));
header('Content-Type: application/json');

echo $ajaxData;

?>