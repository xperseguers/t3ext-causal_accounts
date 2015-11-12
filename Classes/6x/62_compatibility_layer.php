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

namespace FoT3\Openid;

/**
 * Compatibility layer.
 * In TYPO3 7.6 LTS the TYPO3 OpenID was outsourced into a dedicated package.
 * Because of that the namespace changed.
 *
 * @category    Service
 * @package     TYPO3
 * @subpackage  tx_causalaccounts
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class OpenidService extends \TYPO3\CMS\Openid\OpenidService
{
}
