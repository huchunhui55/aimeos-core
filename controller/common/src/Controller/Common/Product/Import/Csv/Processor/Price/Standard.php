<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Product\Import\Csv\Processor\Price;


/**
 * Price processor for CSV imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Common\Product\Import\Csv\Processor\Base
	implements \Aimeos\Controller\Common\Product\Import\Csv\Processor\Iface
{
	private $listTypes;


	/**
	 * Initializes the object
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context object
	 * @param array $mapping Associative list of field position in CSV as key and domain item key as value
	 * @param \Aimeos\Controller\Common\Product\Import\Csv\Processor\Iface $object Decorated processor
	 */
	public function __construct( \Aimeos\MShop\Context\Item\Iface $context, array $mapping,
			\Aimeos\Controller\Common\Product\Import\Csv\Processor\Iface $object = null )
	{
		parent::__construct( $context, $mapping, $object );

		/** controller/common/product/import/csv/processor/price/listtypes
		 * Names of the product list types for prices that are updated or removed
		 *
		 * If you want to associate price items manually via the administration
		 * interface to products and don't want these to be touched during the
		 * import, you can specify the product list types for these prices
		 * that shouldn't be updated or removed.
		 *
		 * @param array|null List of product list type names or null for all
		 * @since 2015.05
		 * @category Developer
		 * @category User
		 * @see controller/common/product/import/csv/domains
		 * @see controller/common/product/import/csv/processor/attribute/listtypes
		 * @see controller/common/product/import/csv/processor/catalog/listtypes
		 * @see controller/common/product/import/csv/processor/media/listtypes
		 * @see controller/common/product/import/csv/processor/product/listtypes
		 * @see controller/common/product/import/csv/processor/text/listtypes
		 */
		$this->listTypes = $context->getConfig()->get( 'controller/common/product/import/csv/processor/price/listtypes' );
	}


	/**
	 * Saves the product related data to the storage
	 *
	 * @param \Aimeos\MShop\Product\Item\Iface $product Product item with associated items
	 * @param array $data List of CSV fields with position as key and data as value
	 * @return array List of data which hasn't been imported
	 */
	public function process( \Aimeos\MShop\Product\Item\Iface $product, array $data )
	{
		$listManager = \Aimeos\MShop\Factory::createManager( $this->getContext(), 'product/lists' );
		$manager = \Aimeos\MShop\Factory::createManager( $this->getContext(), 'price' );
		$manager->begin();

		try
		{
			$listItems = $product->getListItems( 'price' );
			$map = $this->getMappedChunk( $data );

			foreach( $map as $pos => $list )
			{
				if( !isset( $list['price.value'] ) || $list['price.value'] === '' || isset( $list['product.lists.type'] )
					&& $this->listTypes !== null && !in_array( $list['product.lists.type'], (array) $this->listTypes )
				) {
					continue;
				}

				if( ( $listItem = array_shift( $listItems ) ) !== null ) {
					$refItem = $listItem->getRefItem();
				} else {
					$listItem = $listManager->createItem();
					$refItem = $manager->createItem();
				}

				$typecode = ( isset( $list['price.type'] ) ? $list['price.type'] : 'default' );
				$list['price.typeid'] = $this->getTypeId( 'price/type', 'product', $typecode );
				$list['price.domain'] = 'product';

				$refItem->fromArray( $this->addItemDefaults( $list ) );
				$manager->saveItem( $refItem );

				$typecode = ( isset( $list['product.lists.type'] ) ? $list['product.lists.type'] : 'default' );
				$list['product.lists.typeid'] = $this->getTypeId( 'product/lists/type', 'price', $typecode );
				$list['product.lists.parentid'] = $product->getId();
				$list['product.lists.refid'] = $refItem->getId();
				$list['product.lists.domain'] = 'price';

				$listItem->fromArray( $this->addListItemDefaults( $list, $pos ) );
				$listManager->saveItem( $listItem );
			}

			foreach( $listItems as $listItem )
			{
				$manager->deleteItem( $listItem->getRefItem()->getId() );
				$listManager->deleteItem( $listItem->getId() );
			}

			$remaining = $this->getObject()->process( $product, $data );

			$manager->commit();
		}
		catch( \Exception $e )
		{
			$manager->rollback();
			throw $e;
		}

		return $remaining;
	}


	/**
	 * Adds the text item default values and returns the resulting array
	 *
	 * @param array $list Associative list of domain item keys and their values, e.g. "price.status" => 1
	 * @return array Given associative list enriched by default values if they were not already set
	 */
	protected function addItemDefaults( array $list )
	{
		if( !isset( $list['price.currencyid'] ) ) {
			$list['price.currencyid'] = $this->getContext()->getLocale()->getCurrencyId();
		}

		if( !isset( $list['price.label'] ) ) {
			$list['price.label'] = $list['price.currencyid'] . ' ' . $list['price.value'];
		}

		if( !isset( $list['price.status'] ) ) {
			$list['price.status'] = 1;
		}

		return $list;
	}
}
