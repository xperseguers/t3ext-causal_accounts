<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "causal_accounts".
 *
 * Auto generated 25-04-2014 14:02
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Central Account Management',
	'description' => 'This extension allows TYPO3 administrator accounts to be managed centrally and automatically synchronized with remote websites using a secure link. No need for complex LDAP / ActiveDirectory infrastructure.',
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
	'version' => '1.4.1',
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
	'_md5_values_when_last_written' => 'a:24:{s:16:"ext_autoload.php";s:4:"5bc9";s:21:"ext_conf_template.txt";s:4:"e480";s:12:"ext_icon.gif";s:4:"57bc";s:17:"ext_localconf.php";s:4:"34c8";s:42:"Classes/4x/class.tx_causalaccounts_eid.php";s:4:"5e7b";s:58:"Classes/4x/class.tx_causalaccounts_synchronizationtask.php";s:4:"25e4";s:37:"Classes/4x/class.ux_tx_openid_sv1.php";s:4:"4226";s:36:"Classes/Controller/EidController.php";s:4:"9ada";s:36:"Classes/Task/SynchronizationTask.php";s:4:"3494";s:32:"Classes/Xclass/OpenidService.php";s:4:"55b4";s:26:"Documentation/Includes.txt";s:4:"df03";s:23:"Documentation/Index.rst";s:4:"e71c";s:26:"Documentation/Settings.yml";s:4:"c70f";s:25:"Documentation/Targets.rst";s:4:"94c2";s:43:"Documentation/AdministratorManual/Index.rst";s:4:"4749";s:63:"Documentation/AdministratorManual/InstallingExtension/Index.rst";s:4:"e607";s:33:"Documentation/ChangeLog/Index.rst";s:4:"2776";s:46:"Documentation/Images/authentication-openid.png";s:4:"e90c";s:42:"Documentation/Images/extension-manager.png";s:4:"3901";s:31:"Documentation/Images/finger.png";s:4:"f98d";s:33:"Documentation/Images/overview.png";s:4:"28ed";s:36:"Documentation/Introduction/Index.rst";s:4:"9c31";s:37:"Documentation/KnownProblems/Index.rst";s:4:"5745";s:32:"Documentation/ToDoList/Index.rst";s:4:"b948";}',
);

?>