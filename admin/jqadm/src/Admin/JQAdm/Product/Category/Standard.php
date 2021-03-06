<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015
 * @package Admin
 * @subpackage JQAdm
 */


namespace Aimeos\Admin\JQAdm\Product\Category;


/**
 * Default implementation of product category JQAdm client.
 *
 * @package Admin
 * @subpackage JQAdm
 */
class Standard
	extends \Aimeos\Admin\JQAdm\Common\Admin\Factory\Base
	implements \Aimeos\Admin\JQAdm\Common\Admin\Factory\Iface
{
	/** admin/jqadm/product/category/standard/subparts
	 * List of JQAdm sub-clients rendered within the product category section
	 *
	 * The output of the frontend is composed of the code generated by the JQAdm
	 * clients. Each JQAdm client can consist of serveral (or none) sub-clients
	 * that are responsible for rendering certain sub-parts of the output. The
	 * sub-clients can contain JQAdm clients themselves and therefore a
	 * hierarchical tree of JQAdm clients is composed. Each JQAdm client creates
	 * the output that is placed inside the container of its parent.
	 *
	 * At first, always the JQAdm code generated by the parent is printed, then
	 * the JQAdm code of its sub-clients. The order of the JQAdm sub-clients
	 * determines the order of the output of these sub-clients inside the parent
	 * container. If the configured list of clients is
	 *
	 *  array( "subclient1", "subclient2" )
	 *
	 * you can easily change the order of the output by reordering the subparts:
	 *
	 *  admin/jqadm/<clients>/subparts = array( "subclient1", "subclient2" )
	 *
	 * You can also remove one or more parts if they shouldn't be rendered:
	 *
	 *  admin/jqadm/<clients>/subparts = array( "subclient1" )
	 *
	 * As the clients only generates structural JQAdm, the layout defined via CSS
	 * should support adding, removing or reordering content by a fluid like
	 * design.
	 *
	 * @param array List of sub-client names
	 * @since 2016.01
	 * @category Developer
	 */
	private $subPartPath = 'admin/jqadm/product/category/standard/subparts';
	private $subPartNames = array();


	/**
	 * Copies a resource
	 *
	 * @return string|null admin output to display or null for redirecting to the list
	 */
	public function copy()
	{
		$view = $this->getView();

		$this->setData( $view );
		$view->categoryBody = '';

		foreach( $this->getSubClients() as $client ) {
			$view->categoryBody .= $client->copy();
		}

		$tplconf = 'admin/jqadm/product/category/template-item';
		$default = 'product/item-category-default.php';

		return $view->render( $view->config( $tplconf, $default ) );
	}


	/**
	 * Creates a new resource
	 *
	 * @return string|null admin output to display or null for redirecting to the list
	 */
	public function create()
	{
		$view = $this->getView();

		$this->setData( $view );
		$view->categoryBody = '';

		foreach( $this->getSubClients() as $client ) {
			$view->categoryBody .= $client->create();
		}

		$tplconf = 'admin/jqadm/product/category/template-item';
		$default = 'product/item-category-default.php';

		return $view->render( $view->config( $tplconf, $default ) );
	}


	/**
	 * Returns a single resource
	 *
	 * @return string|null admin output to display or null for redirecting to the list
	 */
	public function get()
	{
		$view = $this->getView();

		$this->setData( $view );
		$view->categoryBody = '';

		foreach( $this->getSubClients() as $client ) {
			$view->categoryBody .= $client->get();
		}

		$tplconf = 'admin/jqadm/product/category/template-item';
		$default = 'product/item-category-default.php';

		return $view->render( $view->config( $tplconf, $default ) );
	}


	/**
	 * Saves the data
	 *
	 * @return string|null admin output to display or null for redirecting to the list
	 */
	public function save()
	{
		$view = $this->getView();
		$context = $this->getContext();

		$manager = \Aimeos\MShop\Factory::createManager( $context, 'product/lists' );
		$manager->begin();

		try
		{
			$this->updateItems( $view );
			$view->categoryBody = '';

			foreach( $this->getSubClients() as $client ) {
				$view->categoryBody .= $client->save();
			}

			$manager->commit();
			return;
		}
		catch( \Aimeos\MShop\Exception $e )
		{
			$error = array( 'product-item-category' => $context->getI18n()->dt( 'mshop', $e->getMessage() ) );
			$view->errors = $view->get( 'errors', array() ) + $error;
			$manager->rollback();
		}
		catch( \Exception $e )
		{
			$context->getLogger()->log( $e->getMessage() . ' - ' . $e->getTraceAsString() );
			$error = array( 'product-item-category' => $e->getMessage() );
			$view->errors = $view->get( 'errors', array() ) + $error;
			$manager->rollback();
		}

		throw new \Aimeos\Admin\JQAdm\Exception();
	}


	/**
	 * Returns the sub-client given by its name.
	 *
	 * @param string $type Name of the client type
	 * @param string|null $name Name of the sub-client (Default if null)
	 * @return \Aimeos\Admin\JQAdm\Iface Sub-client object
	 */
	public function getSubClient( $type, $name = null )
	{
		/** admin/jqadm/product/category/decorators/excludes
		 * Excludes decorators added by the "common" option from the product JQAdm client
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to remove a decorator added via
		 * "admin/jqadm/common/decorators/default" before they are wrapped
		 * around the JQAdm client.
		 *
		 *  admin/jqadm/product/category/decorators/excludes = array( 'decorator1' )
		 *
		 * This would remove the decorator named "decorator1" from the list of
		 * common decorators ("\Aimeos\Admin\JQAdm\Common\Decorator\*") added via
		 * "admin/jqadm/common/decorators/default" to the JQAdm client.
		 *
		 * @param array List of decorator names
		 * @since 2016.01
		 * @category Developer
		 * @see admin/jqadm/common/decorators/default
		 * @see admin/jqadm/product/category/decorators/global
		 * @see admin/jqadm/product/category/decorators/local
		 */

		/** admin/jqadm/product/category/decorators/global
		 * Adds a list of globally available decorators only to the product JQAdm client
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to wrap global decorators
		 * ("\Aimeos\Admin\JQAdm\Common\Decorator\*") around the JQAdm client.
		 *
		 *  admin/jqadm/product/category/decorators/global = array( 'decorator1' )
		 *
		 * This would add the decorator named "decorator1" defined by
		 * "\Aimeos\Admin\JQAdm\Common\Decorator\Decorator1" only to the JQAdm client.
		 *
		 * @param array List of decorator names
		 * @since 2016.01
		 * @category Developer
		 * @see admin/jqadm/common/decorators/default
		 * @see admin/jqadm/product/category/decorators/excludes
		 * @see admin/jqadm/product/category/decorators/local
		 */

		/** admin/jqadm/product/category/decorators/local
		 * Adds a list of local decorators only to the product JQAdm client
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to wrap local decorators
		 * ("\Aimeos\Admin\JQAdm\Product\Decorator\*") around the JQAdm client.
		 *
		 *  admin/jqadm/product/category/decorators/local = array( 'decorator2' )
		 *
		 * This would add the decorator named "decorator2" defined by
		 * "\Aimeos\Admin\JQAdm\Product\Decorator\Decorator2" only to the JQAdm client.
		 *
		 * @param array List of decorator names
		 * @since 2016.01
		 * @category Developer
		 * @see admin/jqadm/common/decorators/default
		 * @see admin/jqadm/product/category/decorators/excludes
		 * @see admin/jqadm/product/category/decorators/global
		 */
		return $this->createSubClient( 'product/category/' . $type, $name );
	}


	/**
	 * Returns the list of sub-client names configured for the client.
	 *
	 * @return array List of JQAdm client names
	 */
	protected function getSubClientNames()
	{
		return $this->getContext()->getConfig()->get( $this->subPartPath, $this->subPartNames );
	}


	/**
	 * Returns the category items for the given catalog list items
	 *
	 * @param array $listItems List of items implementing \Aimeos\Common\Item\Lists\Iface
	 * @return array Associative list of catalog IDs as keys and items implementing \Aimeos\Catalog\Item\Iface as values
	 */
	protected function getCatalogItems( array $listItems )
	{
		$ids = array();

		foreach( $listItems as $listItem ) {
			$ids[] = $listItem->getParentId();
		}

		$manager = \Aimeos\MShop\Factory::createManager( $this->getContext(), 'catalog' );

		$search = $manager->createSearch();
		$search->setConditions( $search->compare( '==', 'catalog.id', $ids ) );

		return $manager->searchItems( $search );
	}


	/**
	 * Returns the category list items for the given product ID
	 *
	 * @param string $prodid Unique product ID
	 * @return array Associative list of category list IDs as keys and list items as values
	 */
	protected function getListItems( $prodid )
	{
		$manager = \Aimeos\MShop\Factory::createManager( $this->getContext(), 'catalog/lists' );

		$search = $manager->createSearch();
		$expr = array(
			$search->compare( '==', 'catalog.lists.refid', $prodid ),
			$search->compare( '==', 'catalog.lists.domain', 'product' ),
			$search->compare( '==', 'catalog.lists.type.domain', 'product' ),
			$search->compare( '==', 'catalog.lists.type.code', 'default' ),
		);
		$search->setConditions( $search->combine( '&&', $expr ) );

		return $manager->searchItems( $search );
	}


	/**
	 * Returns the mapped input parameter or the existing items as expected by the template
	 *
	 * @param \Aimeos\MW\View\Iface $view View object with helpers and assigned parameters
	 * @return array Multi-dimensional associative array
	 */
	protected function setData( \Aimeos\MW\View\Iface $view )
	{
		$view->categoryData = (array) $view->param( 'category', array() );

		if( !empty( $view->categoryData ) ) {
			return;
		}

		$listItems = $this->getListItems( $view->item->getId() );
		$catItems = $this->getCatalogItems( $listItems );
		$data = array();

		foreach( $listItems as $listItem )
		{
			$catId = $listItem->getParentId();

			if( isset( $catItems[$catId] ) )
			{
				$data['catalog.id'][] = $catId;
				$data['catalog.label'][] = $catItems[$catId]->getLabel();
			}

			foreach( $listItem->toArray() as $key => $value ) {
				$data[$key][] = $value;
			}
		}

		$view->categoryData = $data;
	}


	/**
	 * Updates existing product category references or creates new ones
	 *
	 * @param \Aimeos\MW\View\Iface $view View object with helpers and assigned parameters
	 */
	protected function updateItems( \Aimeos\MW\View\Iface $view )
	{
		$context = $this->getContext();
		$manager = \Aimeos\MShop\Factory::createManager( $context, 'catalog/lists' );
		$typeManager = \Aimeos\MShop\Factory::createManager( $context, 'catalog/lists/type' );

		$id = $view->item->getId();
		$map = $this->getListItems( $id );
		$listIds = (array) $view->param( 'category/catalog.lists.id', array() );


		foreach( $listIds as $pos => $listid )
		{
			if( isset( $map[$listid] ) ) {
				unset( $map[$listid], $listIds[$pos] );
			}
		}

		$manager->deleteItems( array_keys( $map ) );


		$item = $manager->createItem();
		$item->setTypeId( $typeManager->findItem( 'default', array(), 'product' )->getId() );
		$item->setDomain( 'product' );
		$item->setRefId( $id );

		foreach( $listIds as $pos => $listid )
		{
			$item->setId( null );
			$item->setParentId( $view->param( 'category/catalog.id/' . $pos ) );

			$manager->saveItem( $item, false );
		}
	}
}