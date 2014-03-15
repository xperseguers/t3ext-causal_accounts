<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "causal_accounts".
 *
 * Auto generated 07-08-2013 09:44
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Central Account Management',
	'description' => 'This extension allows TYPO3 administrator accounts to be managed centrally.',
	'category' => 'services',
	'author' => 'Xavier Perseguers (Causal)',
	'author_company' => 'Causal Sàrl',
	'author_email' => 'xavier@causal.ch',
	'shy' => '',
	'dependencies' => 'openid',
	'conflicts' => '',
	'suggests' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'version' => '1.4.1-dev',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.3-5.5.99',
			'typo3' => '4.5.0-6.2.99',
			'openid' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:12:{s:9:"ChangeLog";s:4:"8ac8";s:16:"ext_autoload.php";s:4:"5bc9";s:21:"ext_conf_template.txt";s:4:"e480";s:12:"ext_icon.gif";s:4:"57bc";s:17:"ext_localconf.php";s:4:"34c8";s:42:"Classes/4x/class.tx_causalaccounts_eid.php";s:4:"0cab";s:58:"Classes/4x/class.tx_causalaccounts_synchronizationtask.php";s:4:"54c9";s:37:"Classes/4x/class.ux_tx_openid_sv1.php";s:4:"3fcf";s:36:"Classes/Controller/EidController.php";s:4:"a5ef";s:36:"Classes/Task/SynchronizationTask.php";s:4:"8c13";s:32:"Classes/Xclass/OpenidService.php";s:4:"93d3";s:14:"doc/manual.sxw";s:4:"9777";}',
);

?>