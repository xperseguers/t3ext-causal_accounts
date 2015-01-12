<?php
$classMap = array();
if (version_compare(TYPO3_version, '6.0.0', '<')) {
	$extensionPath = t3lib_extMgm::extPath('causal_accounts');
	$classMap = array(
		'tx_causalaccounts_synchronizationtask' => $extensionPath . 'Classes/4x/class.tx_causalaccounts_synchronizationtask.php',
	);
}
return $classMap;
