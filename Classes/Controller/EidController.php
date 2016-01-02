<?php
namespace Causal\CausalAccounts\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with TYPO3 source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use \TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * eID controller for the 'causal_accounts' extension.
 *
 * @category    Controller
 * @package     TYPO3
 * @subpackage  tx_causalaccounts
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class EidController
{

    /** @var string */
    protected static $extKey = 'causal_accounts';

    /** @var array */
    protected $config;

    /**
     * Default action.
     *
     * @return array
     * @throws \RuntimeException
     */
    public function main()
    {
        $this->init();

        $allowedIps = GeneralUtility::trimExplode(',', $this->config['allowedIps'], true);

        if ($this->config['debug']) {
            GeneralUtility::sysLog('Connection from ' . GeneralUtility::getIndpEnv('REMOTE_ADDR'), self::$extKey);
        }

        if ($this->config['mode'] !== 'M' || (count($allowedIps) && !in_array(GeneralUtility::getIndpEnv('REMOTE_ADDR'), $allowedIps))) {
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
        $administrators = $this->getDatabaseConnection()->exec_SELECTgetRows(
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
            throw new \RuntimeException('No administrators found', 1327586994);
        }
    }

    /**
     * Deny access to this module by pretending page was not found.
     *
     * @return void
     */
    protected function denyAccess()
    {
        header('HTTP/1.0 404 Not Found');
        exit;
    }

    /**
     * Initializes this class.
     *
     * @return void
     * @throws \RuntimeException
     */
    protected function init()
    {
        $this->config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::$extKey]);
        if (!is_array($this->config)) {
            throw new \RuntimeException('Extension "' . self::$extKey . '" is not configured', 1327582564);
        }
    }

    /**
     * Initializes TSFE and sets $GLOBALS['TSFE'].
     *
     * @return void
     */
    protected function initTSFE()
    {
        /** @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $tsfe */
        $tsfe = GeneralUtility::makeInstance(
            'TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController',
            $GLOBALS['TYPO3_CONF_VARS'],
            GeneralUtility::_GP('id'),
            ''
        );
        $GLOBALS['TSFE'] = $tsfe;
        $tsfe->connectToDB();
        $tsfe->initFEuser();
        $tsfe->checkAlternativeIdMethods();
        $tsfe->determineId();
        $tsfe->initTemplate();
        $tsfe->getConfigArray();

        // Get linkVars, absRefPrefix, etc
        \TYPO3\CMS\Frontend\Page\PageGenerator::pagegenInit();
    }

    /**
     * Returns the database connection.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

}

/** @var \Causal\CausalAccounts\Controller\EidController $output */
$output = GeneralUtility::makeInstance('Causal\\CausalAccounts\\Controller\\EidController');

$ret = array(
    'success' => true,
    'data' => array(),
    'errors' => array(),
);
try {
    $ret['data'] = $output->main();
} catch (\Exception $e) {
    $ret['success'] = false;
    $ret['errors'][] = 'Error ' . $e->getCode() . ': ' . $e->getMessage();
}

$ajaxData = json_encode($ret);

header('Content-Length: ' . strlen($ajaxData));
header('Content-Type: application/json');

echo $ajaxData;
