<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015
 * @package Controller
 * @subpackage ExtJS
 */


namespace Aimeos\Controller\ExtJS\Supplier\Lists\Type;


/**
 * ExtJS supplier list type controller for admin interfaces.
 *
 * @package Controller
 * @subpackage ExtJS
 */
class Standard
	extends \Aimeos\Controller\ExtJS\Base
	implements \Aimeos\Controller\ExtJS\Common\Iface
{
	private $manager = null;


	/**
	 * Initializes the supplier list type controller.
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context MShop context object
	 */
	public function __construct( \Aimeos\MShop\Context\Item\Iface $context )
	{
		parent::__construct( $context, 'Supplier_Lists_Type' );
	}


	/**
	 * Returns the manager the controller is using.
	 *
	 * @return \Aimeos\MShop\Common\Manager\Iface Manager object
	 */
	protected function getManager()
	{
		if( $this->manager === null ) {
			$this->manager = \Aimeos\MShop\Factory::createManager( $this->getContext(), 'supplier/lists/type' );
		}

		return $this->manager;
	}


	/**
	 * Returns the prefix for searching items
	 *
	 * @return string MShop search key prefix
	 */
	protected function getPrefix()
	{
		return 'supplier.lists.type';
	}
}
