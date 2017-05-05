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
    'version' => '1.7.1',
    'constraints' => array(
        'depends' => array(
            'php' => '5.3.3-7.1.99',
            'typo3' => '4.5.0-7.99.99',
            'openid' => '',
            'scheduler' => '',
        ),
        'conflicts' => array(),
        'suggests' => array(),
    ),
    '_md5_values_when_last_written' => '',
);
