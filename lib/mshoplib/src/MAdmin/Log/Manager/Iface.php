<?php

/**
 * @copyright Metaways Infosystems GmbH, 2011
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015
 * @package MAdmin
 * @subpackage Log
 */


namespace Aimeos\MAdmin\Log\Manager;


/**
 * Interface for log manager implementations.
 *
 * @package MAdmin
 * @subpackage Log
 */
interface Iface
	extends \Aimeos\MShop\Common\Manager\Factory\Iface, \Aimeos\MW\Logger\Iface
{
}