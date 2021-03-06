<?php

/**
 * @copyright Metaways Infosystems GmbH, 2014
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015
 */


namespace Aimeos\Client\Html\Common\Decorator;


/**
 * Provides example decorator for html clients.
 */
class Example
	extends \Aimeos\Client\Html\Common\Decorator\Base
	implements \Aimeos\Client\Html\Common\Decorator\Iface
{
	public function additionalMethod()
	{
		return true;
	}


	protected function getSubClientNames()
	{
		return array();
	}
}
