<?php

/**
 * @copyright Metaways Infosystems GmbH, 2013
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015
 */


namespace Aimeos\MW\Setup\Task;


/**
 * Adds product variant attribute performance records.
 */
class ProductAddAttributeVariantPerfData extends \Aimeos\MW\Setup\Task\ProductAddBasePerfData
{
	/**
	 * Returns the list of task names which this task depends on.
	 *
	 * @return string[] List of task names
	 */
	public function getPreDependencies()
	{
		return array( 'ProductAddBasePerfData', 'MShopAddTypeDataUnitperf' );
	}


	/**
	 * Returns the list of task names which depends on this task.
	 *
	 * @return string[] List of task names
	 */
	public function getPostDependencies()
	{
		return array( 'CatalogRebuildPerfIndex' );
	}


	/**
	 * Executes the task for MySQL databases.
	 */
	protected function mysql()
	{
		$this->process();
	}


	/**
	 * Insert attribute items and product/attribute relations.
	 */
	protected function process()
	{

		$this->msg( 'Adding product variant attribute performance data', 0 );


		$context = $this->getContext();

		$attrManager = \Aimeos\MShop\Attribute\Manager\Factory::createManager( $context );
		$attrTypeManager = $attrManager->getSubManager( 'type' );

		$search = $attrTypeManager->createSearch();
		$search->setConditions( $search->compare( '==', 'attribute.type.code', 'width' ) );
		$result = $attrTypeManager->searchItems( $search );

		if( ( $attrTypeItem = reset( $result ) ) === false ) {
			throw new \Exception( 'No attribute type "size" found' );
		}


		$this->txBegin();

		$attrItem = $attrManager->createItem();
		$attrItem->setTypeId( $attrTypeItem->getId() );
		$attrItem->setDomain( 'product' );
		$attrItem->setStatus( 1 );

		$pos = 0;
		$attrListWidth = array();

		foreach( array( 'tight', 'normal', 'wide' ) as $size )
		{
			$attrItem->setId( null );
			$attrItem->setCode( $size );
			$attrItem->setLabel( $size );
			$attrItem->setPosition( $pos++ );

			$attrManager->saveItem( $attrItem );

			$attrListWidth[$attrItem->getId()] = clone $attrItem;
		}



		$search = $attrTypeManager->createSearch();
		$search->setConditions( $search->compare( '==', 'attribute.type.code', 'length' ) );
		$result = $attrTypeManager->searchItems( $search );

		if( ( $attrTypeItem = reset( $result ) ) === false ) {
			throw new \Exception( 'No attribute type "size" found' );
		}

		$attrItem = $attrManager->createItem();
		$attrItem->setTypeId( $attrTypeItem->getId() );
		$attrItem->setDomain( 'product' );
		$attrItem->setStatus( 1 );

		$pos = 0;
		$attrListLength = array();

		foreach( array( 'short', 'normal', 'long' ) as $size )
		{
			$attrItem->setId( null );
			$attrItem->setCode( $size );
			$attrItem->setLabel( $size );
			$attrItem->setPosition( $pos++ );

			$attrManager->saveItem( $attrItem );

			$attrListLength[$attrItem->getId()] = clone $attrItem;
		}

		$this->txCommit();


		$productManager = \Aimeos\MShop\Product\Manager\Factory::createManager( $context );
		$productListManager = $productManager->getSubManager( 'lists' );
		$productListTypeManager = $productListManager->getSubManager( 'type' );

		$expr = array();
		$search = $productListTypeManager->createSearch();
		$expr[] = $search->compare( '==', 'product.lists.type.domain', 'attribute' );
		$expr[] = $search->compare( '==', 'product.lists.type.code', 'variant' );
		$search->setConditions( $search->combine( '&&', $expr ) );
		$types = $productListTypeManager->searchItems( $search );

		if( ( $productListTypeItem = reset( $types ) ) === false ) {
			throw new \Exception( 'Product list type item not found' );
		}


		$search = $productManager->createSearch();
		$search->setSortations( array( $search->sort( '+', 'product.id' ) ) );

		$listItem = $productListManager->createItem();
		$listItem->setTypeId( $productListTypeItem->getId() );
		$listItem->setDomain( 'attribute' );


		$this->txBegin();

		$start = 0;

		do
		{
			$result = $productManager->searchItems( $search );

			foreach( $result as $id => $item )
			{
				$listItem->setId( null );
				$listItem->setParentId( $id );
				$listItem->setRefId( key( $attrListLength ) );
				$listItem->setPosition( 0 );

				$productListManager->saveItem( $listItem, false );

				$listItem->setId( null );
				$listItem->setParentId( $id );
				$listItem->setRefId( key( $attrListWidth ) );
				$listItem->setPosition( 1 );

				$productListManager->saveItem( $listItem, false );

				if( next( $attrListLength ) === false )
				{
					reset( $attrListLength );
					next( $attrListWidth );

					if( current( $attrListWidth ) === false ) {
						reset( $attrListWidth );
					}
				}
			}

			$count = count( $result );
			$start += $count;
			$search->setSlice( $start );
		}
		while( $count == $search->getSliceSize() );

		$this->txCommit();


		$this->status( 'done' );
	}
}
