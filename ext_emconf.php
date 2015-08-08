<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "causal_accounts".
 *
 * Auto generated 13-01-2015 09:03
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
    'dependencies' => 'openid,scheduler',
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
    'version' => '1.7.0-dev',
    'constraints' => array(
        'depends' => array(
            'php' => '5.3.3-5.6.99',
            'typo3' => '4.5.0-7.99.99',
            'openid' => '',
            'scheduler' => '',
        ),
        'conflicts' => array(),
        'suggests' => array(),
    ),
    '_md5_values_when_last_written' => 'a:25:{s:13:"composer.json";s:4:"faa6";s:16:"ext_autoload.php";s:4:"6e9c";s:21:"ext_conf_template.txt";s:4:"cdbd";s:12:"ext_icon.gif";s:4:"57bc";s:17:"ext_localconf.php";s:4:"580b";s:42:"Classes/4x/class.tx_causalaccounts_eid.php";s:4:"cb56";s:58:"Classes/4x/class.tx_causalaccounts_synchronizationtask.php";s:4:"5938";s:37:"Classes/4x/class.ux_tx_openid_sv1.php";s:4:"6a2d";s:36:"Classes/Controller/EidController.php";s:4:"0278";s:36:"Classes/Task/SynchronizationTask.php";s:4:"3b54";s:32:"Classes/Xclass/OpenidService.php";s:4:"0fb1";s:26:"Documentation/Includes.txt";s:4:"df03";s:23:"Documentation/Index.rst";s:4:"723c";s:26:"Documentation/Settings.yml";s:4:"408c";s:25:"Documentation/Targets.rst";s:4:"94c2";s:43:"Documentation/AdministratorManual/Index.rst";s:4:"4749";s:63:"Documentation/AdministratorManual/InstallingExtension/Index.rst";s:4:"e607";s:33:"Documentation/ChangeLog/Index.rst";s:4:"2776";s:46:"Documentation/Images/authentication-openid.png";s:4:"e90c";s:42:"Documentation/Images/extension-manager.png";s:4:"3901";s:31:"Documentation/Images/finger.png";s:4:"f98d";s:33:"Documentation/Images/overview.png";s:4:"28ed";s:36:"Documentation/Introduction/Index.rst";s:4:"9c31";s:37:"Documentation/KnownProblems/Index.rst";s:4:"5745";s:32:"Documentation/ToDoList/Index.rst";s:4:"b948";}',
);
