<?php
defined('TYPO3_MODE') || die();

$config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY]);
if (is_array($config)) {

    if ($config['mode'] === 'M') {
        // Register the http://your-domain.tld/?eID=causal_accounts handler
        $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include'][$_EXTKEY] = 'EXT:' . $_EXTKEY . '/Classes/Controller/EidController.php';
    }

    // Register XCLASS to allow shorter form of OpenID authentication
    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('openid')) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['FoT3\\Openid\\OpenidService'] = [
            'className' => 'Causal\\CausalAccounts\\Xclass\\OpenidService',
        ];
    }

    if ($config['mode'] === 'S' || \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR') === '127.0.0.1') {
        // Register the synchronization scheduler task
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Causal\\CausalAccounts\\Task\\SynchronizationTask'] = [
            'extension' => $_EXTKEY,
            'title' => 'Account synchronization',
            'description' => 'Regularly synchronize administrator accounts from a master website',
        ];
    }
}
