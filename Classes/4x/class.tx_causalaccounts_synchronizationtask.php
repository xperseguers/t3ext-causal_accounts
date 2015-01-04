<?php
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

/**
 * Synchronization scheduler task for the 'causal_accounts' extension.
 *
 * @category    Scheduler Task
 * @package     TYPO3
 * @subpackage  tx_causalaccounts
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class tx_causalaccounts_synchronizationtask extends tx_scheduler_Task {

	/** @var string */
	protected static $extKey = 'causal_accounts';

	/** @var array */
	protected $config;

	/**
	 * This is the main method that is called when a task is executed
	 * It MUST be implemented by all classes inheriting from this one
	 * Note that there is no error handling, errors and failures are expected
	 * to be handled and logged by the client implementations.
	 * Should return TRUE on successful execution, FALSE on error.
	 *
	 * @return boolean Returns TRUE on successful execution, FALSE on error
	 */
	public function execute() {
		$success = FALSE;
		$this->init();

		$content = t3lib_div::getUrl($this->config['masterUrl']);
		if ($content) {
			$response = json_decode($content, TRUE);
			if ($response['success']) {
				$key = $this->config['preSharedKey'];
				$encrypted = $response['data'];
				$data = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($encrypted), MCRYPT_MODE_CBC, md5(md5($key))), "\0");
				$records = json_decode($data, TRUE);
				if (count($records)) {
					$this->synchronizeUsers($records);
					$success = TRUE;
				} else {
					t3lib_div::sysLog('No users to be synchronized', self::$extKey, 3);
				}
			} else {
				t3lib_div::sysLog($response['errors'][0], self::$extKey, 3);
			}
		}

		return $success;
	}

	/**
	 * Synchronizes backend users.
	 *
	 * @param array $users
	 */
	protected function synchronizeUsers(array $users) {
		/** @var $instance tx_saltedpasswords_salts */
		$instance = NULL;
		if (t3lib_extMgm::isLoaded('saltedpasswords')) {
			$instance = tx_saltedpasswords_salts_factory::getSaltingInstance(NULL, 'BE');
		}

		$authorizedKeys = array_flip(array(
			'username',
			'admin',
			'disable',
			'realName',
			'email',
			'TSconfig',
			'starttime',
			'endtime',
			'lang',
			'tx_openid_openid',
			'deleted',
		));

		foreach ($users as $user) {
			$user = array_intersect_key($user, $authorizedKeys);

			if (empty($this->config['synchronizeDeletedAccounts']) || !$this->config['synchronizeDeletedAccounts']) {
				if (isset($user['deleted']) && $user['deleted']) {
					// We do not authorize deleted user accounts to be synchronized
					// on this website
					continue;
				}
			} else {
				$user['deleted'] = $user['deleted'] ? 1 : 0;
			}

			// Generate a random password
			$password = t3lib_div::generateRandomBytes(16);
			$user['password'] = $instance ? $instance->getHashedPassword($password) : md5($password);

			$localUser = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
				'uid',
				'be_users',
				'username=' . $this->getDatabaseConnection()->fullQuoteStr($user['username'], 'be_users')
			);
			if ($localUser) {
				// Update existing user
				$this->getDatabaseConnection()->exec_UPDATEquery(
					'be_users',
					'uid=' . $localUser['uid'],
					$user
				);
			} else {
				// Create new user
				$this->getDatabaseConnection()->exec_INSERTquery(
					'be_users',
					$user
				);
			}
		}
	}

	/**
	 * Initializes this class.
	 *
	 * @return void
	 */
	protected function init() {
		$this->config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::$extKey]);
	}

	/**
	 * Returns the database connection.
	 *
	 * @return t3lib_DB
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

}
