<?php

/**
 * @copyright Metaways Infosystems GmbH, 2011
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015
 * @package MShop
 * @subpackage Catalog
 */


namespace Aimeos\MShop\Catalog\Manager;


/**
 * Catalog manager with methods for managing categories products, text, media.
 *
 * @package MShop
 * @subpackage Catalog
 */
class Standard
	extends \Aimeos\MShop\Common\Manager\ListRef\Base
	implements \Aimeos\MShop\Catalog\Manager\Iface, \Aimeos\MShop\Common\Manager\Factory\Iface
{
	private $filter = array();
	private $treeManagers = array();

	private $searchConfig = array(
		'id' => array(
			'code'=>'catalog.id',
			'internalcode'=>'mcat."id"',
			'label'=>'Catalog node ID',
			'type'=> 'integer',
			'internaltype'=> \Aimeos\MW\DB\Statement\Base::PARAM_INT,
		),
		'label' => array(
			'code'=>'catalog.label',
			'internalcode'=>'mcat."label"',
			'label'=>'Catalog node label',
			'type'=> 'string',
			'internaltype'=> \Aimeos\MW\DB\Statement\Base::PARAM_STR,
		),
		'config' => array(
			'code' => 'catalog.config',
			'internalcode' => 'mcat."config"',
			'label' => 'Catalog node config',
			'type' => 'string',
			'internaltype' => \Aimeos\MW\DB\Statement\Base::PARAM_STR,
		),
		'code' => array(
			'code'=>'catalog.code',
			'internalcode'=>'mcat."code"',
			'label'=>'Catalog node code',
			'type'=> 'string',
			'internaltype'=> \Aimeos\MW\DB\Statement\Base::PARAM_STR,
		),
		'status' => array(
			'code'=>'catalog.status',
			'internalcode'=>'mcat."status"',
			'label'=>'Catalog node status',
			'type'=> 'integer',
			'internaltype'=> \Aimeos\MW\DB\Statement\Base::PARAM_INT,
		),
		'parentid' => array(
			'code'=>'catalog.parentid',
			'internalcode'=>'mcat."parentid"',
			'label'=>'Catalog node parentid',
			'type'=> 'integer',
			'internaltype'=> \Aimeos\MW\DB\Statement\Base::PARAM_INT,
			'public' => false,
		),
		'level' => array(
			'code'=>'catalog.level',
			'internalcode'=>'mcat."level"',
			'label'=>'Catalog node tree level',
			'type'=> 'integer',
			'internaltype'=> \Aimeos\MW\DB\Statement\Base::PARAM_INT,
			'public' => false,
		),
		'left' => array(
			'code'=>'catalog.left',
			'internalcode'=>'mcat."nleft"',
			'label'=>'Catalog node left value',
			'type'=> 'integer',
			'internaltype'=> \Aimeos\MW\DB\Statement\Base::PARAM_INT,
			'public' => false,
		),
		'right' => array(
			'code'=>'catalog.right',
			'internalcode'=>'mcat."nright"',
			'label'=>'Catalog node right value',
			'type'=> 'integer',
			'internaltype'=> \Aimeos\MW\DB\Statement\Base::PARAM_INT,
			'public' => false,
		),
		'catalog.siteid' => array(
			'code'=>'catalog.siteid',
			'internalcode'=>'mcat."siteid"',
			'label'=>'Catalog node site ID',
			'type'=> 'integer',
			'internaltype'=> \Aimeos\MW\DB\Statement\Base::PARAM_INT,
			'public' => false,
		),
		'catalog.ctime'=> array(
			'label' => 'Catalog creation time',
			'code' => 'catalog.ctime',
			'internalcode' => 'mcat."ctime"',
			'type' => 'datetime',
			'internaltype' => \Aimeos\MW\DB\Statement\Base::PARAM_STR,
		),
		'catalog.mtime'=> array(
			'label' => 'Catalog modification time',
			'code' => 'catalog.mtime',
			'internalcode' => 'mcat."mtime"',
			'type' => 'datetime',
			'internaltype' => \Aimeos\MW\DB\Statement\Base::PARAM_STR,
		),
		'catalog.editor'=> array(
			'code'=>'catalog.editor',
			'internalcode'=>'mcat."editor"',
			'label'=>'Catalog editor',
			'type'=> 'string',
			'internaltype'=> \Aimeos\MW\DB\Statement\Base::PARAM_STR,
		),
	);


	/**
	 * Initializes the object.
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context object
	 */
	public function __construct( \Aimeos\MShop\Context\Item\Iface $context )
	{
		parent::__construct( $context );
		$this->setResourceName( 'db-catalog' );
	}


	/**
	 * Removes old entries from the storage.
	 *
	 * @param integer[] $siteids List of IDs for sites whose entries should be deleted
	 */
	public function cleanup( array $siteids )
	{
		$context = $this->getContext();
		$config = $context->getConfig();
		$search = $this->createSearch();

		$path = 'mshop/catalog/manager/submanagers';
		foreach( $config->get( $path, array( 'lists' ) ) as $domain ) {
			$this->getSubManager( $domain )->cleanup( $siteids );
		}

		$dbm = $context->getDatabaseManager();
		$dbname = $this->getResourceName();
		$conn = $dbm->acquire( $dbname );

		try
		{
			$path = 'mshop/catalog/manager/standard/delete';
			$sql = $this->getSqlConfig( $path );

			$types = array( 'siteid' => \Aimeos\MW\DB\Statement\Base::PARAM_STR );
			$translations = array( 'siteid' => '"siteid"' );

			$search->setConditions( $search->compare( '==', 'siteid', $siteids ) );
			$sql = str_replace( ':siteid', $search->getConditionString( $types, $translations ), $sql );

			$stmt = $conn->create( $sql );
			$stmt->bind( 1, 0, \Aimeos\MW\DB\Statement\Base::PARAM_INT );
			$stmt->bind( 2, 0x7FFFFFFF, \Aimeos\MW\DB\Statement\Base::PARAM_INT );
			$stmt->execute()->finish();

			$dbm->release( $conn, $dbname );
		}
		catch( \Exception $e )
		{
			$dbm->release( $conn, $dbname );
			throw $e;
		}
	}


	/**
	 * Creates new item object.
	 *
	 * @return \Aimeos\MShop\Common\Item\Iface New item object
	 */
	public function createItem()
	{
		$values = array( 'siteid' => $this->getContext()->getLocale()->getSiteId() );

		return $this->createItemBase( $values );
	}


	/**
	 * Creates a search object.
	 *
	 * @param boolean $default Add default criteria
	 * @return \Aimeos\MW\Criteria\Iface Returns the Search object
	 */
	public function createSearch( $default = false )
	{
		if( $default === true ) {
			return $this->createSearchBase( 'catalog' );
		}

		return parent::createSearch();
	}


	/**
	 * Deletes the item specified by its ID.
	 *
	 * @param mixed $id ID of the item object
	 */
	public function deleteItem( $id )
	{
		$siteid = $this->getContext()->getLocale()->getSiteId();
		$this->begin();

		try
		{
			$this->createTreeManager( $siteid )->deleteNode( $id );
			$this->commit();
		}
		catch( \Exception $e )
		{
			$this->rollback();
			throw $e;
		}
	}


	/**
	 * Removes multiple items specified by ids in the array.
	 *
	 * @param array $ids List of IDs
	 */
	public function deleteItems( array $ids )
	{
		foreach( $ids as $id ) {
			$this->deleteItem( $id );
		}
	}


	/**
	 * Returns the item specified by its code and domain/type if necessary
	 *
	 * @param string $code Code of the item
	 * @param string[] $ref List of domains to fetch list items and referenced items for
	 * @param string|null $domain Domain of the item if necessary to identify the item uniquely
	 * @param string|null $type Type code of the item if necessary to identify the item uniquely
	 * @return \Aimeos\MShop\Common\Item\Iface Item object
	 */
	public function findItem( $code, array $ref = array(), $domain = null, $type = null )
	{
		return $this->findItemBase( array( 'catalog.code' => $code ), $ref );
	}


	/**
	 * Returns the item specified by its ID.
	 *
	 * @param integer $id Unique ID of the catalog item
	 * @param array $ref List of domains to fetch list items and referenced items for
	 * @return \Aimeos\MShop\Catalog\Item\Iface Returns the catalog item of the given id
	 * @throws \Aimeos\MShop\Exception If item couldn't be found
	 */
	public function getItem( $id, array $ref = array() )
	{
		return $this->getItemBase( 'catalog.id', $id, $ref );
	}


	/**
	 * Returns the available manager types
	 *
	 * @param boolean $withsub Return also the resource type of sub-managers if true
	 * @return array Type of the manager and submanagers, subtypes are separated by slashes
	 */
	public function getResourceType( $withsub = true )
	{
		$path = 'mshop/catalog/manager/submanagers';

		return $this->getResourceTypeBase( 'catalog', $path, array( 'lists' ), $withsub );
	}


	/**
	 * Returns the attributes that can be used for searching.
	 *
	 * @param boolean $withsub Return also attributes of sub-managers if true
	 * @return array List of attribute items implementing \Aimeos\MW\Criteria\Attribute\Iface
	 */
	public function getSearchAttributes( $withsub = true )
	{
		/** mshop/catalog/manager/submanagers
		 * List of manager names that can be instantiated by the catalog manager
		 *
		 * Managers provide a generic interface to the underlying storage.
		 * Each manager has or can have sub-managers caring about particular
		 * aspects. Each of these sub-managers can be instantiated by its
		 * parent manager using the getSubManager() method.
		 *
		 * The search keys from sub-managers can be normally used in the
		 * manager as well. It allows you to search for items of the manager
		 * using the search keys of the sub-managers to further limit the
		 * retrieved list of items.
		 *
		 * @param array List of sub-manager names
		 * @since 2014.03
		 * @category Developer
		 */
		$path = 'mshop/catalog/manager/submanagers';

		return $this->getSearchAttributesBase( $this->searchConfig, $path, array( 'lists' ), $withsub );
	}


	/**
	 * Adds a new item object.
	 *
	 * @param \Aimeos\MShop\Common\Item\Iface $item Item which should be inserted
	 */
	public function insertItem( \Aimeos\MShop\Catalog\Item\Iface $item, $parentId = null, $refId = null )
	{
		$siteid = $this->getContext()->getLocale()->getSiteId();
		$node = $item->getNode();
		$this->begin();

		try
		{
			$this->createTreeManager( $siteid )->insertNode( $node, $parentId, $refId );
			$this->updateUsage( $node->getId(), $item, true );
			$this->commit();
		}
		catch( \Exception $e )
		{
			$this->rollback();
			throw $e;
		}
	}


	/**
	 * Moves an existing item to the new parent in the storage.
	 *
	 * @param mixed $id ID of the item that should be moved
	 * @param mixed $oldParentId ID of the old parent item which currently contains the item that should be removed
	 * @param mixed $newParentId ID of the new parent item where the item should be moved to
	 * @param mixed $refId ID of the item where the item should be inserted before (null to append)
	 */
	public function moveItem( $id, $oldParentId, $newParentId, $refId = null )
	{
		$siteid = $this->getContext()->getLocale()->getSiteId();
		$item = $this->getItem( $id );

		$this->begin();

		try
		{
			$this->createTreeManager( $siteid )->moveNode( $id, $oldParentId, $newParentId, $refId );
			$this->updateUsage( $id, $item );
			$this->commit();
		}
		catch( \Exception $e )
		{
			$this->rollback();
			throw $e;
		}
	}


	/**
	 * Updates an item object.
	 *
	 * @param \Aimeos\MShop\Common\Item\Iface $item Item object whose data should be saved
	 * @param boolean $fetch True if the new ID should be returned in the item
	 */
	public function saveItem( \Aimeos\MShop\Common\Item\Iface $item, $fetch = true )
	{
		$iface = '\\Aimeos\\MShop\\Catalog\\Item\\Iface';
		if( !( $item instanceof $iface ) ) {
			throw new \Aimeos\MShop\Catalog\Exception( sprintf( 'Object is not of required type "%1$s"', $iface ) );
		}

		$siteid = $this->getContext()->getLocale()->getSiteId();
		$node = $item->getNode();
		$this->begin();

		try
		{
			$this->createTreeManager( $siteid )->saveNode( $node );
			$this->updateUsage( $node->getId(), $item );
			$this->commit();
		}
		catch( \Exception $e )
		{
			$this->rollback();
			throw $e;
		}
	}


	/**
	 * Searches for all items matching the given critera.
	 *
	 * @param \Aimeos\MW\Criteria\Iface $search Criteria object with conditions, sortations, etc.
	 * @param array $ref List of domains to fetch list items and referenced items for
	 * @param integer|null &$total No function. Reference will be set to null in this case.
	 * @param integer $total
	 * @return array List of items implementing \Aimeos\MShop\Common\Item\Iface
	 */
	public function searchItems( \Aimeos\MW\Criteria\Iface $search, array $ref = array(), &$total = null )
	{
		$nodeMap = $siteMap = array();
		$context = $this->getContext();

		$dbname = $this->getResourceName();
		$dbm = $context->getDatabaseManager();
		$conn = $dbm->acquire( $dbname );

		try
		{
			$required = array( 'catalog' );
			$level = \Aimeos\MShop\Locale\Manager\Base::SITE_PATH;

			/** mshop/catalog/manager/standard/search-item
			 * Retrieves the records matched by the given criteria in the database
			 *
			 * Fetches the records matched by the given criteria from the catalog
			 * database. The records must be from one of the sites that are
			 * configured via the context item. If the current site is part of
			 * a tree of sites, the SELECT statement can retrieve all records
			 * from the current site and the complete sub-tree of sites.
			 *
			 * As the records can normally be limited by criteria from sub-managers,
			 * their tables must be joined in the SQL context. This is done by
			 * using the "internaldeps" property from the definition of the ID
			 * column of the sub-managers. These internal dependencies specify
			 * the JOIN between the tables and the used columns for joining. The
			 * ":joins" placeholder is then replaced by the JOIN strings from
			 * the sub-managers.
			 *
			 * To limit the records matched, conditions can be added to the given
			 * criteria object. It can contain comparisons like column names that
			 * must match specific values which can be combined by AND, OR or NOT
			 * operators. The resulting string of SQL conditions replaces the
			 * ":cond" placeholder before the statement is sent to the database
			 * server.
			 *
			 * If the records that are retrieved should be ordered by one or more
			 * columns, the generated string of column / sort direction pairs
			 * replaces the ":order" placeholder. In case no ordering is required,
			 * the complete ORDER BY part including the "\/*-orderby*\/...\/*orderby-*\/"
			 * markers is removed to speed up retrieving the records. Columns of
			 * sub-managers can also be used for ordering the result set but then
			 * no index can be used.
			 *
			 * The number of returned records can be limited and can start at any
			 * number between the begining and the end of the result set. For that
			 * the ":size" and ":start" placeholders are replaced by the
			 * corresponding values from the criteria object. The default values
			 * are 0 for the start and 100 for the size value.
			 *
			 * The SQL statement should conform to the ANSI standard to be
			 * compatible with most relational database systems. This also
			 * includes using double quotes for table and column names.
			 *
			 * @param string SQL statement for searching items
			 * @since 2014.03
			 * @category Developer
			 * @see mshop/catalog/manager/standard/delete
			 * @see mshop/catalog/manager/standard/get
			 * @see mshop/catalog/manager/standard/insert
			 * @see mshop/catalog/manager/standard/update
			 * @see mshop/catalog/manager/standard/newid
			 * @see mshop/catalog/manager/standard/search
			 * @see mshop/catalog/manager/standard/count
			 * @see mshop/catalog/manager/standard/move-left
			 * @see mshop/catalog/manager/standard/move-right
			 * @see mshop/catalog/manager/standard/update-parentid
			 */
			$cfgPathSearch = 'mshop/catalog/manager/standard/search-item';

			/** mshop/catalog/manager/standard/count
			 * Counts the number of records matched by the given criteria in the database
			 *
			 * Counts all records matched by the given criteria from the catalog
			 * database. The records must be from one of the sites that are
			 * configured via the context item. If the current site is part of
			 * a tree of sites, the statement can count all records from the
			 * current site and the complete sub-tree of sites.
			 *
			 * As the records can normally be limited by criteria from sub-managers,
			 * their tables must be joined in the SQL context. This is done by
			 * using the "internaldeps" property from the definition of the ID
			 * column of the sub-managers. These internal dependencies specify
			 * the JOIN between the tables and the used columns for joining. The
			 * ":joins" placeholder is then replaced by the JOIN strings from
			 * the sub-managers.
			 *
			 * To limit the records matched, conditions can be added to the given
			 * criteria object. It can contain comparisons like column names that
			 * must match specific values which can be combined by AND, OR or NOT
			 * operators. The resulting string of SQL conditions replaces the
			 * ":cond" placeholder before the statement is sent to the database
			 * server.
			 *
			 * Both, the strings for ":joins" and for ":cond" are the same as for
			 * the "search" SQL statement.
			 *
			 * Contrary to the "search" statement, it doesn't return any records
			 * but instead the number of records that have been found. As counting
			 * thousands of records can be a long running task, the maximum number
			 * of counted records is limited for performance reasons.
			 *
			 * The SQL statement should conform to the ANSI standard to be
			 * compatible with most relational database systems. This also
			 * includes using double quotes for table and column names.
			 *
			 * @param string SQL statement for counting items
			 * @since 2014.03
			 * @category Developer
			 * @see mshop/catalog/manager/standard/delete
			 * @see mshop/catalog/manager/standard/get
			 * @see mshop/catalog/manager/standard/insert
			 * @see mshop/catalog/manager/standard/update
			 * @see mshop/catalog/manager/standard/newid
			 * @see mshop/catalog/manager/standard/search
			 * @see mshop/catalog/manager/standard/search-item
			 * @see mshop/catalog/manager/standard/move-left
			 * @see mshop/catalog/manager/standard/move-right
			 * @see mshop/catalog/manager/standard/update-parentid
			 */
			$cfgPathCount = 'mshop/catalog/manager/standard/count';

			$results = $this->searchItemsBase( $conn, $search, $cfgPathSearch, $cfgPathCount, $required, $total, $level );

			while( ( $row = $results->fetch() ) !== false ) {
				$siteMap[$row['siteid']][$row['id']] = new \Aimeos\MW\Tree\Node\Standard( $row );
			}

			$sitePath = array_reverse( $this->getContext()->getLocale()->getSitePath() );

			foreach( $sitePath as $siteId )
			{
				if( isset( $siteMap[$siteId] ) && !empty( $siteMap[$siteId] ) )
				{
					$nodeMap = $siteMap[$siteId];
					break;
				}
			}

			$dbm->release( $conn, $dbname );
		}
		catch( \Exception $e )
		{
			$dbm->release( $conn, $dbname );
			throw $e;
		}

		return $this->buildItems( $nodeMap, $ref, 'catalog' );
	}


	/**
	 * Returns a list of items starting with the given category that are in the path to the root node
	 *
	 * @param integer $id ID of item to get the path for
	 * @param array $ref List of domains to fetch list items and referenced items for
	 * @return array Associative list of items implementing \Aimeos\MShop\Catalog\Item\Iface with IDs as keys
	 */
	public function getPath( $id, array $ref = array() )
	{
		$sitePath = array_reverse( $this->getContext()->getLocale()->getSitePath() );

		foreach( $sitePath as $siteId )
		{
			try {
				$path = $this->createTreeManager( $siteId )->getPath( $id );
			} catch( \Exception $e ) {
				continue;
			}

			if( !empty( $path ) )
			{
				$itemMap = array();

				foreach( $path as $node ) {
					$itemMap[$node->getId()] = $node;
				}

				return $this->buildItems( $itemMap, $ref, 'catalog' );
			}
		}

		throw new \Aimeos\MShop\Catalog\Exception( sprintf( 'Catalog path for ID "%1$s" not found', $id ) );
	}


	/**
	 * Returns a node and its descendants depending on the given resource.
	 *
	 * @param integer|null $id Retrieve nodes starting from the given ID
	 * @param array List of domains (e.g. text, media, etc.) whose referenced items should be attached to the objects
	 * @param integer $level One of the level constants from \Aimeos\MW\Tree\Manager\Base
	 * @param \Aimeos\MW\Criteria\Iface|null $criteria Optional criteria object with conditions
	 * @return \Aimeos\MShop\Catalog\Item\Iface Catalog item, maybe with subnodes
	 */
	public function getTree( $id = null, array $ref = array(), $level = \Aimeos\MW\Tree\Manager\Base::LEVEL_TREE, \Aimeos\MW\Criteria\Iface $criteria = null )
	{
		$sitePath = array_reverse( $this->getContext()->getLocale()->getSitePath() );

		foreach( $sitePath as $siteId )
		{
			try {
				$node = $this->createTreeManager( $siteId )->getNode( $id, $level, $criteria );
			} catch( \Exception $e ) {
				continue;
			}

			$listItems = $listItemMap = $refIdMap = array();
			$nodeMap = $this->getNodeMap( $node );

			if( count( $ref ) > 0 ) {
				$listItems = $this->getListItems( array_keys( $nodeMap ), $ref, 'catalog' );
			}

			foreach( $listItems as $listItem )
			{
				$domain = $listItem->getDomain();
				$parentid = $listItem->getParentId();

				$listItemMap[$parentid][$domain][$listItem->getId()] = $listItem;
				$refIdMap[$domain][$listItem->getRefId()][] = $parentid;
			}

			$refItemMap = $this->getRefItems( $refIdMap );
			$nodeid = $node->getId();

			$listItems = array();
			if( array_key_exists( $nodeid, $listItemMap ) ) {
				$listItems = $listItemMap[$nodeid];
			}

			$refItems = array();
			if( array_key_exists( $nodeid, $refItemMap ) ) {
				$refItems = $refItemMap[$nodeid];
			}

			$item = $this->createItemBase( array(), $listItems, $refItems, array(), $node );
			$this->createTree( $node, $item, $listItemMap, $refItemMap );

			return $item;
		}

		throw new \Aimeos\MShop\Catalog\Exception( sprintf( 'Catalog node for ID "%1$s" not available', $id ) );
	}


	/**
	 * Creates a new extension manager in the domain.
	 *
	 * @param string $manager Name of the sub manager type
	 * @param string $name Name of the implementation, will be from configuration (or Default)
	 * @return \Aimeos\MShop\Common\Manager\Iface Manager extending the domain functionality
	 */
	public function getSubManager( $manager, $name = null )
	{
		return $this->getSubManagerBase( 'catalog', $manager, $name );
	}


	/**
	 * Registers a new item filter for the given name
	 *
	 * To prevent catalog items to be added to the tree, you can register a
	 * closure function that checks if the item should be part of the category
	 * tree or not. The function signature must be:
	 *
	 * function( \Aimeos\MShop\Common\Item\ListRef\Iface $item, $index )
	 *
	 * It must accept an item implementing the list reference interface and the
	 * index of the category in the list starting from 0. Its return value must
	 * be a boolean value of "true" if the category item should be added to the
	 * tree and "false" if not.
	 *
	 * @param string $name Filter name
	 * @param \Closure $fcn Callback function
	 */
	public function registerItemFilter( $name, \Closure $fcn )
	{
		$this->filter[$name] = $fcn;
	}


	/**
	 * Creates the catalog item objects.
	 *
	 * @param array $itemMap Associative list of catalog ID / tree node pairs
	 * @param array $domains List of domains (e.g. text, media) whose items should be attached to the catalog items
	 * @param string $prefix Domain prefix
	 * @return array List of items implementing \Aimeos\MShop\Catalog\Item\Iface
	 */
	protected function buildItems( array $itemMap, array $domains, $prefix )
	{
		$items = $listItemMap = $refItemMap = $refIdMap = array();

		if( count( $domains ) > 0 )
		{
			$listItems = $this->getListItems( array_keys( $itemMap ), $domains, $prefix );

			foreach( $listItems as $listItem )
			{
				$domain = $listItem->getDomain();
				$parentid = $listItem->getParentId();

				$listItemMap[$parentid][$domain][$listItem->getId()] = $listItem;
				$refIdMap[$domain][$listItem->getRefId()][] = $parentid;
			}

			$refItemMap = $this->getRefItems( $refIdMap );
		}

		foreach( $itemMap as $id => $node )
		{
			$listItems = array();
			if( isset( $listItemMap[$id] ) ) {
				$listItems = $listItemMap[$id];
			}

			$refItems = array();
			if( isset( $refItemMap[$id] ) ) {
				$refItems = $refItemMap[$id];
			}

			$items[$id] = $this->createItemBase( array(), $listItems, $refItems, array(), $node );
		}

		return $items;
	}


	/**
	 * Creates a new catalog item.
	 *
	 * @param \Aimeos\MW\Tree\Node\Iface $node Nested set tree node
	 * @param array $children List of children of this catalog item
	 * @param array $listItems List of list items that belong to the catalog item
	 * @param array $refItems Associative list of referenced items grouped by domain
	 * @return \Aimeos\MShop\Catalog\Item\Iface New catalog item
	 */
	protected function createItemBase( array $values = array(), array $listItems = array(), array $refItems = array(),
		array $children = array(), \Aimeos\MW\Tree\Node\Iface $node = null )
	{
		if( $node === null )
		{
			if( !isset( $values['siteid'] ) ) {
				throw new \Aimeos\MShop\Catalog\Exception( 'No site ID available for creating a catalog item' );
			}

			$node = $this->createTreeManager( $values['siteid'] )->createNode();
			$node->siteid = $values['siteid'];
		}

		if( isset( $node->config ) && ( $result = json_decode( $node->config, true ) ) !== null ) {
			$node->config = $result;
		}

		return new \Aimeos\MShop\Catalog\Item\Standard( $node, $children, $listItems, $refItems );
	}


	/**
	 * Builds the tree of catalog items.
	 *
	 * @param \Aimeos\MW\Tree\Node\Iface $node Parent tree node
	 * @param \Aimeos\MShop\Catalog\Item\Iface $item Parent tree catalog Item
	 * @param array $listItemMap Associative list of parent-item-ID / list items for the catalog item
	 * @param array $refItemMap Associative list of parent-item-ID/domain/items key/value pairs
	 */
	protected function createTree( \Aimeos\MW\Tree\Node\Iface $node, \Aimeos\MShop\Catalog\Item\Iface $item,
		array $listItemMap, array $refItemMap )
	{
		foreach( $node->getChildren() as $idx => $child )
		{
			$listItems = array();
			if( array_key_exists( $child->getId(), $listItemMap ) ) {
				$listItems = $listItemMap[$child->getId()];
			}

			$refItems = array();
			if( array_key_exists( $child->getId(), $refItemMap ) ) {
				$refItems = $refItemMap[$child->getId()];
			}

			$newItem = $this->createItemBase( array(), $listItems, $refItems, array(), $child );

			$result = true;
			foreach( $this->filter as $fcn ) {
				$result = $result && $fcn( $newItem, $idx );
			}

			if( $result === true )
			{
				$item->addChild( $newItem );
				$this->createTree( $child, $newItem, $listItemMap, $refItemMap );
			}
		}
	}


	/**
	 * Creates an object for managing the nested set.
	 *
	 * @param integer $siteid Site ID for the specific tree
	 * @return \Aimeos\MW\Tree\Manager\Iface Tree manager
	 */
	protected function createTreeManager( $siteid )
	{
		if( !isset( $this->treeManagers[$siteid] ) )
		{
			$context = $this->getContext();
			$config = $context->getConfig();
			$dbm = $context->getDatabaseManager();


			$treeConfig = array(
				'search' => $this->searchConfig,
				'dbname' => $this->getResourceName(),
				'sql' => array(

					/** mshop/catalog/manager/standard/delete
					 * Deletes the items matched by the given IDs from the database
					 *
					 * Removes the records specified by the given IDs from the database.
					 * The records must be from the site that is configured via the
					 * context item.
					 *
					 * The ":cond" placeholder is replaced by the name of the ID column and
					 * the given ID or list of IDs while the site ID is bound to the question
					 * mark.
					 *
					 * The SQL statement should conform to the ANSI standard to be
					 * compatible with most relational database systems. This also
					 * includes using double quotes for table and column names.
					 *
					 * @param string SQL statement for deleting items
					 * @since 2014.03
					 * @category Developer
					 * @see mshop/catalog/manager/standard/get
					 * @see mshop/catalog/manager/standard/insert
					 * @see mshop/catalog/manager/standard/update
					 * @see mshop/catalog/manager/standard/newid
					 * @see mshop/catalog/manager/standard/search
					 * @see mshop/catalog/manager/standard/search-item
					 * @see mshop/catalog/manager/standard/count
					 * @see mshop/catalog/manager/standard/move-left
					 * @see mshop/catalog/manager/standard/move-right
					 * @see mshop/catalog/manager/standard/update-parentid
					 * @see mshop/catalog/manager/standard/insert-usage
					 * @see mshop/catalog/manager/standard/update-usage
					 */
					'delete' => str_replace( ':siteid', $siteid, $this->getSqlConfig( 'mshop/catalog/manager/standard/delete' ) ),

					/** mshop/catalog/manager/standard/get
					 * Returns a node record and its complete subtree optionally limited by the level
					 *
					 * Fetches the records matched by the given criteria from the catalog
					 * database. The records must be from one of the sites that are
					 * configured via the context item. If the current site is part of
					 * a tree of sites, the SELECT statement can retrieve all records
					 * from the current site and the complete sub-tree of sites. This
					 * statement retrieves all records that are part of the subtree for
					 * the found node. The depth can be limited by the "level" number.
					 *
					 * To limit the records matched, conditions can be added to the given
					 * criteria object. It can contain comparisons like column names that
					 * must match specific values which can be combined by AND, OR or NOT
					 * operators. The resulting string of SQL conditions replaces the
					 * ":cond" placeholder before the statement is sent to the database
					 * server.
					 *
					 * The SQL statement should conform to the ANSI standard to be
					 * compatible with most relational database systems. This also
					 * includes using double quotes for table and column names.
					 *
					 * @param string SQL statement for searching items
					 * @since 2014.03
					 * @category Developer
					 * @see mshop/catalog/manager/standard/delete
					 * @see mshop/catalog/manager/standard/insert
					 * @see mshop/catalog/manager/standard/update
					 * @see mshop/catalog/manager/standard/newid
					 * @see mshop/catalog/manager/standard/search
					 * @see mshop/catalog/manager/standard/search-item
					 * @see mshop/catalog/manager/standard/count
					 * @see mshop/catalog/manager/standard/move-left
					 * @see mshop/catalog/manager/standard/move-right
					 * @see mshop/catalog/manager/standard/update-parentid
					 * @see mshop/catalog/manager/standard/insert-usage
					 * @see mshop/catalog/manager/standard/update-usage
					 */
					'get' => str_replace( ':siteid', $siteid, $this->getSqlConfig( 'mshop/catalog/manager/standard/get' ) ),

					/** mshop/catalog/manager/standard/insert
					 * Inserts a new catalog node into the database table
					 *
					 * Items with no ID yet (i.e. the ID is NULL) will be created in
					 * the database and the newly created ID retrieved afterwards
					 * using the "newid" SQL statement.
					 *
					 * The SQL statement must be a string suitable for being used as
					 * prepared statement. It must include question marks for binding
					 * the values from the catalog item to the statement before they are
					 * sent to the database server. The number of question marks must
					 * be the same as the number of columns listed in the INSERT
					 * statement. The order of the columns must correspond to the
					 * order in the insertNode() method, so the correct values are
					 * bound to the columns.
					 *
					 * The SQL statement should conform to the ANSI standard to be
					 * compatible with most relational database systems. This also
					 * includes using double quotes for table and column names.
					 *
					 * @param string SQL statement for inserting records
					 * @since 2014.03
					 * @category Developer
					 * @see mshop/catalog/manager/standard/delete
					 * @see mshop/catalog/manager/standard/get
					 * @see mshop/catalog/manager/standard/update
					 * @see mshop/catalog/manager/standard/newid
					 * @see mshop/catalog/manager/standard/search
					 * @see mshop/catalog/manager/standard/search-item
					 * @see mshop/catalog/manager/standard/count
					 * @see mshop/catalog/manager/standard/move-left
					 * @see mshop/catalog/manager/standard/move-right
					 * @see mshop/catalog/manager/standard/update-parentid
					 * @see mshop/catalog/manager/standard/insert-usage
					 * @see mshop/catalog/manager/standard/update-usage
					 */
					'insert' => str_replace( ':siteid', $siteid, $this->getSqlConfig( 'mshop/catalog/manager/standard/insert' ) ),

					/** mshop/catalog/manager/standard/move-left
					 * Updates the left values of the nodes that are moved within the catalog tree
					 *
					 * When moving nodes or subtrees with the catalog tree, the left
					 * value of each moved node inside the nested set must be updated
					 * to match their new position within the catalog tree.
					 *
					 * The SQL statement must be a string suitable for being used as
					 * prepared statement. It must include question marks for binding
					 * the values from the catalog item to the statement before they are
					 * sent to the database server. The order of the columns must
					 * correspond to the order in the moveNode() method, so the
					 * correct values are bound to the columns.
					 *
					 * The SQL statement should conform to the ANSI standard to be
					 * compatible with most relational database systems. This also
					 * includes using double quotes for table and column names.
					 *
					 * @param string SQL statement for updating records
					 * @since 2014.03
					 * @category Developer
					 * @see mshop/catalog/manager/standard/delete
					 * @see mshop/catalog/manager/standard/get
					 * @see mshop/catalog/manager/standard/insert
					 * @see mshop/catalog/manager/standard/update
					 * @see mshop/catalog/manager/standard/newid
					 * @see mshop/catalog/manager/standard/search
					 * @see mshop/catalog/manager/standard/search-item
					 * @see mshop/catalog/manager/standard/count
					 * @see mshop/catalog/manager/standard/move-right
					 * @see mshop/catalog/manager/standard/update-parentid
					 * @see mshop/catalog/manager/standard/insert-usage
					 * @see mshop/catalog/manager/standard/update-usage
					 */
					'move-left' => str_replace( ':siteid', $siteid, $this->getSqlConfig( 'mshop/catalog/manager/standard/move-left' ) ),

					/** mshop/catalog/manager/standard/move-right
					 * Updates the left values of the nodes that are moved within the catalog tree
					 *
					 * When moving nodes or subtrees with the catalog tree, the right
					 * value of each moved node inside the nested set must be updated
					 * to match their new position within the catalog tree.
					 *
					 * The SQL statement must be a string suitable for being used as
					 * prepared statement. It must include question marks for binding
					 * the values from the catalog item to the statement before they are
					 * sent to the database server. The order of the columns must
					 * correspond to the order in the moveNode() method, so the
					 * correct values are bound to the columns.
					 *
					 * The SQL statement should conform to the ANSI standard to be
					 * compatible with most relational database systems. This also
					 * includes using double quotes for table and column names.
					 *
					 * @param string SQL statement for updating records
					 * @since 2014.03
					 * @category Developer
					 * @see mshop/catalog/manager/standard/delete
					 * @see mshop/catalog/manager/standard/get
					 * @see mshop/catalog/manager/standard/insert
					 * @see mshop/catalog/manager/standard/update
					 * @see mshop/catalog/manager/standard/newid
					 * @see mshop/catalog/manager/standard/search
					 * @see mshop/catalog/manager/standard/search-item
					 * @see mshop/catalog/manager/standard/count
					 * @see mshop/catalog/manager/standard/move-left
					 * @see mshop/catalog/manager/standard/update-parentid
					 * @see mshop/catalog/manager/standard/insert-usage
					 * @see mshop/catalog/manager/standard/update-usage
					 */
					'move-right' => str_replace( ':siteid', $siteid, $this->getSqlConfig( 'mshop/catalog/manager/standard/move-right' ) ),

					/** mshop/catalog/manager/standard/search
					 * Retrieves the records matched by the given criteria in the database
					 *
					 * Fetches the records matched by the given criteria from the catalog
					 * database. The records must be from one of the sites that are
					 * configured via the context item. If the current site is part of
					 * a tree of sites, the SELECT statement can retrieve all records
					 * from the current site and the complete sub-tree of sites.
					 *
					 * To limit the records matched, conditions can be added to the given
					 * criteria object. It can contain comparisons like column names that
					 * must match specific values which can be combined by AND, OR or NOT
					 * operators. The resulting string of SQL conditions replaces the
					 * ":cond" placeholder before the statement is sent to the database
					 * server.
					 *
					 * If the records that are retrieved should be ordered by one or more
					 * columns, the generated string of column / sort direction pairs
					 * replaces the ":order" placeholder.
					 *
					 * The SQL statement should conform to the ANSI standard to be
					 * compatible with most relational database systems. This also
					 * includes using double quotes for table and column names.
					 *
					 * @param string SQL statement for searching items
					 * @since 2014.03
					 * @category Developer
					 * @see mshop/catalog/manager/standard/delete
					 * @see mshop/catalog/manager/standard/get
					 * @see mshop/catalog/manager/standard/insert
					 * @see mshop/catalog/manager/standard/update
					 * @see mshop/catalog/manager/standard/newid
					 * @see mshop/catalog/manager/standard/search-item
					 * @see mshop/catalog/manager/standard/count
					 * @see mshop/catalog/manager/standard/move-left
					 * @see mshop/catalog/manager/standard/move-right
					 * @see mshop/catalog/manager/standard/update-parentid
					 * @see mshop/catalog/manager/standard/insert-usage
					 * @see mshop/catalog/manager/standard/update-usage
					 */
					'search' => str_replace( ':siteid', $siteid, $this->getSqlConfig( 'mshop/catalog/manager/standard/search' ) ),

					/** mshop/catalog/manager/standard/update
					 * Updates an existing catalog node in the database
					 *
					 * Items which already have an ID (i.e. the ID is not NULL) will
					 * be updated in the database.
					 *
					 * The SQL statement must be a string suitable for being used as
					 * prepared statement. It must include question marks for binding
					 * the values from the catalog item to the statement before they are
					 * sent to the database server. The order of the columns must
					 * correspond to the order in the saveNode() method, so the
					 * correct values are bound to the columns.
					 *
					 * The SQL statement should conform to the ANSI standard to be
					 * compatible with most relational database systems. This also
					 * includes using double quotes for table and column names.
					 *
					 * @param string SQL statement for updating records
					 * @since 2014.03
					 * @category Developer
					 * @see mshop/catalog/manager/standard/delete
					 * @see mshop/catalog/manager/standard/get
					 * @see mshop/catalog/manager/standard/insert
					 * @see mshop/catalog/manager/standard/newid
					 * @see mshop/catalog/manager/standard/search
					 * @see mshop/catalog/manager/standard/search-item
					 * @see mshop/catalog/manager/standard/count
					 * @see mshop/catalog/manager/standard/move-left
					 * @see mshop/catalog/manager/standard/move-right
					 * @see mshop/catalog/manager/standard/update-parentid
					 * @see mshop/catalog/manager/standard/insert-usage
					 * @see mshop/catalog/manager/standard/update-usage
					 */
					'update' => str_replace( ':siteid', $siteid, $this->getSqlConfig( 'mshop/catalog/manager/standard/update' ) ),

					/** mshop/catalog/manager/standard/update-parentid
					 * Updates the parent ID after moving a node record
					 *
					 * When moving nodes with the catalog tree, the parent ID
					 * references must be updated to match the new parent.
					 *
					 * The SQL statement must be a string suitable for being used as
					 * prepared statement. It must include question marks for binding
					 * the values from the catalog item to the statement before they are
					 * sent to the database server. The order of the columns must
					 * correspond to the order in the moveNode() method, so the
					 * correct values are bound to the columns.
					 *
					 * The SQL statement should conform to the ANSI standard to be
					 * compatible with most relational database systems. This also
					 * includes using double quotes for table and column names.
					 *
					 * @param string SQL statement for updating records
					 * @since 2014.03
					 * @category Developer
					 * @see mshop/catalog/manager/standard/delete
					 * @see mshop/catalog/manager/standard/get
					 * @see mshop/catalog/manager/standard/insert
					 * @see mshop/catalog/manager/standard/update
					 * @see mshop/catalog/manager/standard/newid
					 * @see mshop/catalog/manager/standard/search
					 * @see mshop/catalog/manager/standard/search-item
					 * @see mshop/catalog/manager/standard/count
					 * @see mshop/catalog/manager/standard/move-left
					 * @see mshop/catalog/manager/standard/move-right
					 * @see mshop/catalog/manager/standard/insert-usage
					 * @see mshop/catalog/manager/standard/update-usage
					 */
					'update-parentid' => str_replace( ':siteid', $siteid, $this->getSqlConfig( 'mshop/catalog/manager/standard/update-parentid' ) ),

					/** mshop/catalog/manager/standard/newid
					 * Retrieves the ID generated by the database when inserting a new record
					 *
					 * As soon as a new record is inserted into the database table,
					 * the database server generates a new and unique identifier for
					 * that record. This ID can be used for retrieving, updating and
					 * deleting that specific record from the table again.
					 *
					 * For MySQL:
					 *  SELECT LAST_INSERT_ID()
					 * For PostgreSQL:
					 *  SELECT currval('seq_mcat_id')
					 * For SQL Server:
					 *  SELECT SCOPE_IDENTITY()
					 * For Oracle:
					 *  SELECT "seq_mcat_id".CURRVAL FROM DUAL
					 *
					 * There's no way to retrive the new ID by a SQL statements that
					 * fits for most database servers as they implement their own
					 * specific way.
					 *
					 * @param string SQL statement for retrieving the last inserted record ID
					 * @since 2014.03
					 * @category Developer
					 * @see mshop/catalog/manager/standard/delete
					 * @see mshop/catalog/manager/standard/get
					 * @see mshop/catalog/manager/standard/insert
					 * @see mshop/catalog/manager/standard/update
					 * @see mshop/catalog/manager/standard/search
					 * @see mshop/catalog/manager/standard/search-item
					 * @see mshop/catalog/manager/standard/count
					 * @see mshop/catalog/manager/standard/move-left
					 * @see mshop/catalog/manager/standard/move-right
					 * @see mshop/catalog/manager/standard/update-parentid
					 * @see mshop/catalog/manager/standard/insert-usage
					 * @see mshop/catalog/manager/standard/update-usage
					 */
					'newid' => $this->getSqlConfig( 'mshop/catalog/manager/standard/newid' ),
				),
			);

			$this->treeManagers[$siteid] = \Aimeos\MW\Tree\Factory::createManager( 'DBNestedSet', $treeConfig, $dbm );
		}

		return $this->treeManagers[$siteid];
	}


	/**
	 * Creates a flat list node items.
	 *
	 * @param \Aimeos\MW\Tree\Node\Iface $node Root node
	 * @return Associated list of ID / node object pairs
	 */
	protected function getNodeMap( \Aimeos\MW\Tree\Node\Iface $node )
	{
		$map = array();

		$map[(string) $node->getId()] = $node;

		foreach( $node->getChildren() as $child ) {
			$map += $this->getNodeMap( $child );
		}

		return $map;
	}


	/**
	 * Updates the usage information of a node.
	 *
	 * @param integer $id Id of the record
	 * @param \Aimeos\MShop\Catalog\Item\Iface $item Catalog item
	 * @param boolean $case True if the record shoud be added or false for an update
	 *
	 */
	private function updateUsage( $id, \Aimeos\MShop\Catalog\Item\Iface $item, $case = false )
	{
		$date = date( 'Y-m-d H:i:s' );
		$context = $this->getContext();

		$dbm = $context->getDatabaseManager();
		$dbname = $this->getResourceName();
		$conn = $dbm->acquire( $dbname );

		try
		{
			$siteid = $context->getLocale()->getSiteId();

			if( $case !== true )
			{
				/** mshop/catalog/manager/standard/update-usage
				 * Updates the config, editor and mtime value of an updated record
				 *
				 * Each record contains some usage information like when it was
				 * created, last modified and by whom. These information are part
				 * of the catalog items and the generic tree manager doesn't care
				 * about this information. Thus, they are updated after the tree
				 * manager saved the basic record information.
				 *
				 * The SQL statement must be a string suitable for being used as
				 * prepared statement. It must include question marks for binding
				 * the values from the catalog item to the statement before they are
				 * sent to the database server. The order of the columns must
				 * correspond to the order in the method using this statement,
				 * so the correct values are bound to the columns.
				 *
				 * The SQL statement should conform to the ANSI standard to be
				 * compatible with most relational database systems. This also
				 * includes using double quotes for table and column names.
				 *
				 * @param string SQL statement for updating records
				 * @since 2014.03
				 * @category Developer
				 * @see mshop/catalog/manager/standard/delete
				 * @see mshop/catalog/manager/standard/get
				 * @see mshop/catalog/manager/standard/insert
				 * @see mshop/catalog/manager/standard/newid
				 * @see mshop/catalog/manager/standard/search
				 * @see mshop/catalog/manager/standard/search-item
				 * @see mshop/catalog/manager/standard/count
				 * @see mshop/catalog/manager/standard/move-left
				 * @see mshop/catalog/manager/standard/move-right
				 * @see mshop/catalog/manager/standard/update-parentid
				 * @see mshop/catalog/manager/standard/insert-usage
				 */
				$path = 'mshop/catalog/manager/standard/update-usage';
			}
			else
			{
				/** mshop/catalog/manager/standard/insert-usage
				 * Updates the config, editor, ctime and mtime value of an inserted record
				 *
				 * Each record contains some usage information like when it was
				 * created, last modified and by whom. These information are part
				 * of the catalog items and the generic tree manager doesn't care
				 * about this information. Thus, they are updated after the tree
				 * manager inserted the basic record information.
				 *
				 * The SQL statement must be a string suitable for being used as
				 * prepared statement. It must include question marks for binding
				 * the values from the catalog item to the statement before they are
				 * sent to the database server. The order of the columns must
				 * correspond to the order in the method using this statement,
				 * so the correct values are bound to the columns.
				 *
				 * The SQL statement should conform to the ANSI standard to be
				 * compatible with most relational database systems. This also
				 * includes using double quotes for table and column names.
				 *
				 * @param string SQL statement for updating records
				 * @since 2014.03
				 * @category Developer
				 * @see mshop/catalog/manager/standard/delete
				 * @see mshop/catalog/manager/standard/get
				 * @see mshop/catalog/manager/standard/insert
				 * @see mshop/catalog/manager/standard/newid
				 * @see mshop/catalog/manager/standard/search
				 * @see mshop/catalog/manager/standard/search-item
				 * @see mshop/catalog/manager/standard/count
				 * @see mshop/catalog/manager/standard/move-left
				 * @see mshop/catalog/manager/standard/move-right
				 * @see mshop/catalog/manager/standard/update-parentid
				 * @see mshop/catalog/manager/standard/update-usage
				 */
				$path = 'mshop/catalog/manager/standard/insert-usage';
			}

			$stmt = $conn->create( $this->getSqlConfig( $path ) );
			$stmt->bind( 1, json_encode( $item->getConfig() ) );
			$stmt->bind( 2, $date ); // mtime
			$stmt->bind( 3, $context->getEditor() );

			if( $case !== true )
			{
				$stmt->bind( 4, $siteid, \Aimeos\MW\DB\Statement\Base::PARAM_INT );
				$stmt->bind( 5, $id, \Aimeos\MW\DB\Statement\Base::PARAM_INT );
			}
			else
			{
				$stmt->bind( 4, $date ); // ctime
				$stmt->bind( 5, $siteid, \Aimeos\MW\DB\Statement\Base::PARAM_INT );
				$stmt->bind( 6, $id, \Aimeos\MW\DB\Statement\Base::PARAM_INT );
			}

			$stmt->execute()->finish();

			$dbm->release( $conn, $dbname );
		}
		catch( \Exception $e )
		{
			$dbm->release( $conn, $dbname );
			throw $e;
		}
	}
}
