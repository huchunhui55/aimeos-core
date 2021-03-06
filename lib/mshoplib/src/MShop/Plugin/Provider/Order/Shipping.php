<?php

/**
 * @copyright Metaways Infosystems GmbH, 2011
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015
 * @package MShop
 * @subpackage Plugin
 */


namespace Aimeos\MShop\Plugin\Provider\Order;


/**
 * Free shipping implementation if ordered product sum is above a certain value.
 *
 * @package MShop
 * @subpackage Plugin
 * @deprecated Use Reduction service decorator for each delivery option instead
 */
class Shipping
	extends \Aimeos\MShop\Plugin\Provider\Factory\Base
	implements \Aimeos\MShop\Plugin\Provider\Factory\Iface
{
	/**
	 * Subscribes itself to a publisher
	 *
	 * @param \Aimeos\MW\Observer\Publisher\Iface $p Object implementing publisher interface
	 */
	public function register( \Aimeos\MW\Observer\Publisher\Iface $p )
	{
		$p->addListener( $this, 'addProduct.after' );
		$p->addListener( $this, 'deleteProduct.after' );
		$p->addListener( $this, 'setService.after' );
		$p->addListener( $this, 'addCoupon.after' );
		$p->addListener( $this, 'deleteCoupon.after' );
	}


	/**
	 * Receives a notification from a publisher object
	 *
	 * @param \Aimeos\MW\Observer\Publisher\Iface $order Shop basket instance implementing publisher interface
	 * @param string $action Name of the action to listen for
	 * @param mixed $value Object or value changed in publisher
	 */
	public function update( \Aimeos\MW\Observer\Publisher\Iface $order, $action, $value = null )
	{
		$class = '\\Aimeos\\MShop\\Order\\Item\\Base\\Iface';
		if( !( $order instanceof $class ) ) {
			throw new \Aimeos\MShop\Plugin\Exception( sprintf( 'Object is not of required type "%1$s"', $class ) );
		}

		$config = $this->getItemBase()->getConfig();
		if( !isset( $config['threshold'] ) ) { return true; }

		try {
			$delivery = $order->getService( 'delivery' );
		} catch( \Aimeos\MShop\Order\Exception $oe ) {
			// no delivery item available yet
			return true;
		}

		$price = $delivery->getPrice();
		$currency = $price->getCurrencyId();

		if( !isset( $config['threshold'][$currency] ) ) {
			return true;
		}

		$sum = \Aimeos\MShop\Factory::createManager( $this->getContext(), 'price' )->createItem();

		foreach( $order->getProducts() as $product ) {
			$sum->addItem( $product->getPrice(), $product->getQuantity() );
		}

		if( $sum->getValue() + $sum->getRebate() >= $config['threshold'][$currency] && $price->getCosts() > '0.00' )
		{
			$price->setRebate( $price->getCosts() );
			$price->setCosts( '0.00' );
		}
		else if( $sum->getValue() + $sum->getRebate() < $config['threshold'][$currency] && $price->getRebate() > '0.00' )
		{
			$price->setCosts( $price->getRebate() );
			$price->setRebate( '0.00' );
		}

		return true;
	}
}