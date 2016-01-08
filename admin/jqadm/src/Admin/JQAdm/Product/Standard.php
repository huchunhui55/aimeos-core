<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015
 * @package Client
 * @subpackage JQAdm
 */


namespace Aimeos\Admin\JQAdm\Product;


/**
 * Default implementation of product JQAdm client.
 *
 * @package Client
 * @subpackage JQAdm
 */
class Standard
	extends \Aimeos\Admin\JQAdm\Common\Admin\Factory\Base
	implements \Aimeos\Admin\JQAdm\Common\Admin\Factory\Iface
{
	/** admin/jqadm/product/standard/subparts
	 * List of JQAdm sub-clients rendered within the product section
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
	private $subPartPath = 'admin/jqadm/product/standard/subparts';
	private $subPartNames = array( 'selection', 'bundle', 'image', 'text', 'price', 'stock', 'physical' );


	/**
	 * Copies a resource
	 *
	 * @return string|null admin output to display or null for redirecting to the list
	 */
	public function copy()
	{
		$view = $this->getView();
		$context = $this->getContext();

		try
		{
			$this->setData( $view );

			if( isset( $view->itemData['product.code'] ) && $view->itemData['product.code'] !== '' )
			{
				$data = $view->itemData;
				$data['product.code'] = $data['product.code'] . '_copy';

				$view->item->setCode( $data['product.code'] );
				$view->itemData = $data;
			}

			$view->itemBody = '';

			foreach( $this->getSubClients() as $client ) {
				$view->itemBody .= $client->copy();
			}
		}
		catch( \Aimeos\MShop\Exception $e )
		{
			$error = array( 'product-item' => $context->getI18n()->dt( 'mshop', $e->getMessage() ) );
			$view->errors = $view->get( 'errors', array() ) + $error;
		}
		catch( \Exception $e )
		{
			$error = array( 'product-item' => $e->getMessage() );
			$view->errors = $view->get( 'errors', array() ) + $error;
		}

		$tplconf = 'admin/jqadm/product/template-item';
		$default = 'product/item-default.php';

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
		$context = $this->getContext();

		try
		{
			$this->setData( $view );
			$view->itemBody = '';

			foreach( $this->getSubClients() as $client ) {
				$view->itemBody .= $client->create();
			}
		}
		catch( \Aimeos\MShop\Exception $e )
		{
			$error = array( 'product-item' => $context->getI18n()->dt( 'mshop', $e->getMessage() ) );
			$view->errors = $view->get( 'errors', array() ) + $error;
		}
		catch( \Exception $e )
		{
			$error = array( 'product-item' => $e->getMessage() );
			$view->errors = $view->get( 'errors', array() ) + $error;
		}

		$tplconf = 'admin/jqadm/product/template-item';
		$default = 'product/item-default.php';

		return $view->render( $view->config( $tplconf, $default ) );
	}


	/**
	 * Deletes a resource
	 *
	 * @return string|null admin output to display or null for redirecting to the list
	 */
	public function delete()
	{
		$view = $this->getView();
		$context = $this->getContext();

		$manager = \Aimeos\MShop\Factory::createManager( $context, 'product' );
		$manager->begin();

		try
		{
			foreach( $this->getSubClients() as $client ) {
				$client->delete();
			}

			$manager->deleteItems( (array) $view->param( 'id' ) );
			$manager->commit();

			return;
		}
		catch( \Aimeos\MShop\Exception $e )
		{
			$error = array( 'product-item' => $context->getI18n()->dt( 'mshop', $e->getMessage() ) );
			$view->errors = $view->get( 'errors', array() ) + $error;
			$manager->rollback();
		}
		catch( \Exception $e )
		{
			$error = array( 'product-item' => $e->getMessage() );
			$view->errors = $view->get( 'errors', array() ) + $error;
			$manager->rollback();
		}

		$tplconf = 'admin/jqadm/partial/template-error';
		$default = 'common/partials/error-default.php';

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
		$context = $this->getContext();

		try
		{
			$this->setData( $view );
			$view->itemBody = '';

			foreach( $this->getSubClients() as $client ) {
				$view->itemBody .= $client->get();
			}
		}
		catch( \Aimeos\MShop\Exception $e )
		{
			$error = array( 'product-item' => $context->getI18n()->dt( 'mshop', $e->getMessage() ) );
			$view->errors = $view->get( 'errors', array() ) + $error;
		}
		catch( \Exception $e )
		{
			$error = array( 'product-item' => $e->getMessage() );
			$view->errors = $view->get( 'errors', array() ) + $error;
		}

		$tplconf = 'admin/jqadm/product/template-item';
		$default = 'product/item-default.php';

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

		$manager = \Aimeos\MShop\Factory::createManager( $context, 'product' );
		$manager->begin();

		$item = $manager->createItem();

		try
		{
			$item->fromArray( $view->param( 'item', array() ) );
			$item->setConfig( $this->getItemConfig( $view ) );
			$manager->saveItem( $item );

			$view->item = $item;
			$view->itemBody = '';

			foreach( $this->getSubClients() as $client ) {
				$view->itemBody .= $client->save();
			}

			$manager->commit();
			return;
		}
		catch( \Aimeos\MShop\Exception $e )
		{
			$error = array( 'product-item' => $context->getI18n()->dt( 'mshop', $e->getMessage() ) );
			$view->errors = $view->get( 'errors', array() ) + $error;
			$manager->rollback();
		}
		catch( \Exception $e )
		{
			$error = array( 'product-item' => $e->getMessage() );
			$view->errors = $view->get( 'errors', array() ) + $error;
			$manager->rollback();
		}

		return $this->create();
	}


	/**
	 * Returns a list of resource according to the conditions
	 *
	 * @return string admin output to display
	 */
	public function search()
	{
		$view = $this->getView();
		$context = $this->getContext();

		try
		{
			$total = 0;
			$manager = \Aimeos\MShop\Factory::createManager( $context, 'product' );
			$search = $this->initCriteria( $manager->createSearch(), $view->param() );

			$view->items = $manager->searchItems( $search, array(), $total );
			$view->filterOperators = $search->getOperators();
			$view->total = $total;
			$view->itemBody = '';

			foreach( $this->getSubClients() as $client ) {
				$view->itemBody .= $client->search();
			}
		}
		catch( \Aimeos\MShop\Exception $e )
		{
			$error = array( 'product-item' => $context->getI18n()->dt( 'mshop', $e->getMessage() ) );
			$view->errors = $view->get( 'errors', array() ) + $error;
		}
		catch( \Exception $e )
		{
			$error = array( 'product-item' => $e->getMessage() );
			$view->errors = $view->get( 'errors', array() ) + $error;
		}

		$tplconf = 'admin/jqadm/product/template-list';
		$default = 'product/list-default.php';

		return $view->render( $view->config( $tplconf, $default ) );
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
		/** admin/jqadm/product/decorators/excludes
		 * Excludes decorators added by the "common" option from the product JQAdm client
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to remove a decorator added via
		 * "client/jqadm/common/decorators/default" before they are wrapped
		 * around the JQAdm client.
		 *
		 *  admin/jqadm/product/decorators/excludes = array( 'decorator1' )
		 *
		 * This would remove the decorator named "decorator1" from the list of
		 * common decorators ("\Aimeos\Admin\JQAdm\Common\Decorator\*") added via
		 * "client/jqadm/common/decorators/default" to the JQAdm client.
		 *
		 * @param array List of decorator names
		 * @since 2016.01
		 * @category Developer
		 * @see admin/jqadm/common/decorators/default
		 * @see admin/jqadm/product/decorators/global
		 * @see admin/jqadm/product/decorators/local
		 */

		/** admin/jqadm/product/decorators/global
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
		 *  admin/jqadm/product/decorators/global = array( 'decorator1' )
		 *
		 * This would add the decorator named "decorator1" defined by
		 * "\Aimeos\Admin\JQAdm\Common\Decorator\Decorator1" only to the JQAdm client.
		 *
		 * @param array List of decorator names
		 * @since 2016.01
		 * @category Developer
		 * @see admin/jqadm/common/decorators/default
		 * @see admin/jqadm/product/decorators/excludes
		 * @see admin/jqadm/product/decorators/local
		 */

		/** admin/jqadm/product/decorators/local
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
		 *  admin/jqadm/product/decorators/local = array( 'decorator2' )
		 *
		 * This would add the decorator named "decorator2" defined by
		 * "\Aimeos\Admin\JQAdm\Product\Decorator\Decorator2" only to the JQAdm client.
		 *
		 * @param array List of decorator names
		 * @since 2016.01
		 * @category Developer
		 * @see admin/jqadm/common/decorators/default
		 * @see admin/jqadm/product/decorators/excludes
		 * @see admin/jqadm/product/decorators/global
		 */
		return $this->createSubClient( 'product/' . $type, $name );
	}


	/**
	 * Returns the mapped input parameter or the existing items as expected by the template
	 *
	 * @param \Aimeos\MW\View\Iface $view View object with helpers and assigned parameters
	 * @return array Multi-dimensional associative array
	 */
	protected function setData( \Aimeos\MW\View\Iface $view )
	{
		$context = $this->getContext();
		$manager = \Aimeos\MShop\Factory::createManager( $context, 'product' );

		$view->itemData = (array) $view->param( 'item', array() );
		$view->itemTypes = $this->getTypeItems();
		$view->item = $manager->createItem();

		if( !empty( $view->itemData ) || ( $id = $view->param( 'id' ) ) === null ) {
			return;
		}

		/** admin/jqadm/product/domains
		 * List of domain items that should be fetched along with the product
		 *
		 * @param array List of domain names
		 * @since 2016.01
		 * @category Developer
		 */
		$domains = array( 'attribute', 'media', 'price', 'product', 'text' );
		$domains = $context->getConfig()->get( 'admin/jqadm/product/domains', $domains );
		$item = $manager->getItem( $id, $domains );

		$data = $item->toArray();
		$data['config'] = array( 'key' => array(), 'val' => array() );

		foreach( $item->getConfig() as $key => $value )
		{
			$data['config']['key'][] = $key;
			$data['config']['val'][] = $value;
		}

		$view->itemData = $data;
		$view->item = $item;
	}


	/**
	 * Maps the item configuration from parameters to a list of key/value pairs
	 *
	 * @param \Aimeos\MW\View\Iface $view View object with helpers and assigned parameters
	 * @return array Associative list of key/value pairs
	 */
	protected function getItemConfig( \Aimeos\MW\View\Iface $view )
	{
		$config = array();

		foreach( $view->param( 'item/config/key' ) as $idx => $key )
		{
			if( trim( $key ) !== '' ) {
				$config[$key] = $view->param( 'item/config/val/' . $idx );
			}
		}

		return $config;
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
	 * Returns the available product type items
	 *
	 * @return array List of item implementing \Aimeos\MShop\Common\Type\Iface
	 */
	protected function getTypeItems()
	{
		$typeManager = \Aimeos\MShop\Factory::createManager( $this->getContext(), 'product/type' );

		$search = $typeManager->createSearch();
		$search->setSortations( array( $search->sort( '+', 'product.type.label' ) ) );

		return $typeManager->searchItems( $search );
	}
}