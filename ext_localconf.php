<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY]);

if (is_array($config)) {
	if ($config['mode'] === 'M') {
			// Register the http://your-domain.tld/?eID=causal_accounts handler
		$TYPO3_CONF_VARS['FE']['eID_include'][$_EXTKEY] = 'EXT:' . $_EXTKEY . '/class.tx_causalaccounts_eid.php';
	}

	if (trim($config['openIdProvider']) !== '') {
		// Register XCLASS to allow shorter form of OpenID authentication
		$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/openid/sv1/class.tx_openid_sv1.php'] = t3lib_extMgm::extPath($_EXTKEY) . 'class.ux_tx_openid_sv1.php';
	}

	if ($config['mode'] === 'S' || t3lib_div::getIndpEnv('REMOTE_ADDR') === '127.0.0.1') {
			// Register the synchronization scheduler task
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_' . str_replace('_', '', $_EXTKEY) . '_synchronizationtask'] = array(
			'extension' => $_EXTKEY,
			'title' => 'Account synchronization',
			'description' => 'Regularly synchronize administrator accounts from a master website',
		);
	}
}

?>