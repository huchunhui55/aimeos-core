<?php

namespace Aimeos\MW\DB;


/**
 * Test class for \Aimeos\MW\DB\Manager\PDO.
 *
 * @copyright Metaways Infosystems GmbH, 2011
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015
 */
class PDOTest extends \PHPUnit_Framework_TestCase
{
	private $object;
	private $config;


	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @access protected
	 */
	protected function setUp()
	{
		$this->config = \TestHelperMw::getConfig();

		if( ( $adapter = $this->config->get( 'resource/db/adapter', false ) ) === false ) {
			$this->markTestSkipped( 'No database configured' );
		}


		$this->object = new \Aimeos\MW\DB\Manager\PDO( $this->config );

		if( $adapter == 'mysql' ) {
			$sql = 'CREATE TABLE "mw_unit_test" ( "id" INT NOT NULL PRIMARY KEY AUTO_INCREMENT, "name" VARCHAR(20) NOT NULL ) ENGINE=InnoDB';
		} else {
			$sql = 'CREATE TABLE "mw_unit_test" ( "id" INT NOT NULL PRIMARY KEY AUTO_INCREMENT, "name" VARCHAR(20) NOT NULL )';
		}

		$conn = $this->object->acquire();
		$conn->create( $sql )->execute()->finish();
		$this->object->release( $conn );
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 *
	 * @access protected
	 */
	protected function tearDown()
	{
		$this->object = new \Aimeos\MW\DB\Manager\PDO( $this->config );
		$sql = 'DROP TABLE "mw_unit_test"';

		$conn = $this->object->acquire();
		$conn->create( $sql )->execute()->finish();
		$this->object->release( $conn );
	}

	public function testTransactionCommit()
	{
		$sqlinsert = 'INSERT INTO "mw_unit_test" ("name") VALUES (1)';
		$sqlselect = 'SELECT "name" FROM "mw_unit_test" WHERE "name" = 1';

		$conn = $this->object->acquire();

		$conn->begin();
		$stmt = $conn->create( $sqlinsert );
		$stmt->execute()->finish();
		$stmt->execute()->finish();
		$conn->commit();

		$result = $conn->create( $sqlselect )->execute();

		$rows = array();
		while( ( $row = $result->fetch() ) !== false ) {
			$rows[] = $row;
		}

		$result->finish();

		$this->object->release( $conn );

		$this->assertEquals( 2, count( $rows ) );
	}

	public function testTransactionCommitMultiple()
	{
		$sqlinsert = 'INSERT INTO "mw_unit_test" ("name") VALUES (1)';
		$sqlselect = 'SELECT "name" FROM "mw_unit_test" WHERE "name" = 1';

		$conn = $this->object->acquire();

		$stmt = $conn->create( $sqlinsert );

		$conn->begin();
		$stmt->execute()->finish();
		$conn->commit();

		$conn->begin();
		$stmt->execute()->finish();
		$conn->commit();

		$result = $conn->create( $sqlselect )->execute();

		$rows = array();
		while( ( $row = $result->fetch() ) !== false ) {
			$rows[] = $row;
		}

		$result->finish();

		$this->object->release( $conn );

		$this->assertEquals( 2, count( $rows ) );
	}

	public function testTransactionRollback()
	{
		$sqlinsert = 'INSERT INTO "mw_unit_test" ("name") VALUES (1)';
		$sqlselect = 'SELECT "name" FROM "mw_unit_test" WHERE "name" = 1';

		$conn = $this->object->acquire();

		$conn->begin();
		$stmt = $conn->create( $sqlinsert );
		$stmt->execute()->finish();
		$stmt->execute()->finish();
		$conn->rollback();

		$result = $conn->create( $sqlselect )->execute();

		$rows = array();
		while( ( $row = $result->fetch() ) !== false ) {
			$rows[] = $row;
		}

		$result->finish();

		$this->object->release( $conn );

		$this->assertEquals( 0, count( $rows ) );
	}

	public function testTransactionStackCommit()
	{
		$sqlinsert = 'INSERT INTO "mw_unit_test" ("name") VALUES (1)';
		$sqlselect = 'SELECT "name" FROM "mw_unit_test" WHERE "name" = 1';

		$conn = $this->object->acquire();

		$conn->begin();
		$conn->begin();

		$stmt = $conn->create( $sqlinsert );
		$stmt->execute()->finish();

		$conn->rollback();
		$conn->commit();

		$result = $conn->create( $sqlselect )->execute();

		$rows = array();
		while( ( $row = $result->fetch() ) !== false ) {
			$rows[] = $row;
		}

		$result->finish();

		$this->object->release( $conn );

		$this->assertEquals( 1, count( $rows ) );
	}

	public function testTransactionStackRollback()
	{
		$sqlinsert = 'INSERT INTO "mw_unit_test" ("name") VALUES (1)';
		$sqlselect = 'SELECT "name" FROM "mw_unit_test" WHERE "name" = 1';

		$conn = $this->object->acquire();

		$conn->begin();
		$conn->begin();

		$stmt = $conn->create( $sqlinsert );
		$stmt->execute()->finish();

		$conn->commit();
		$conn->rollback();

		$result = $conn->create( $sqlselect )->execute();

		$rows = array();
		while( ( $row = $result->fetch() ) !== false ) {
			$rows[] = $row;
		}

		$result->finish();

		$this->object->release( $conn );

		$this->assertEquals( 0, count( $rows ) );
	}

	public function testAffectedRows()
	{
		$sqlinsert = 'INSERT INTO "mw_unit_test" ("name") VALUES (1)';

		$conn = $this->object->acquire();

		$result = $conn->create( $sqlinsert )->execute();
		$rows = $result->affectedRows();
		$result->finish();

		$this->object->release( $conn );

		$this->assertEquals( 1, $rows );
	}

	public function testStmtEscape()
	{
		$sqlinsert = 'INSERT INTO "mw_unit_test" ("name") VALUES (:value)';

		$conn = $this->object->acquire();

		$value = "(\\')";
		$sqlinsert = str_replace( ':value', '\'' . $conn->escape( $value ) . '\'', $sqlinsert );
		$stmt = $conn->create( $sqlinsert );
		$stmt->execute()->finish();

		$this->object->release( $conn );

		$this->assertEquals( 'INSERT INTO "mw_unit_test" ("name") VALUES (\'(\\\\\'\')\')', strval( $stmt ) );
	}

	public function testStmtSimpleBindOne()
	{
		$sqlinsert = 'INSERT INTO "mw_unit_test" ("name") VALUES (?)';

		$conn = $this->object->acquire();

		$stmt = $conn->create( $sqlinsert );
		$stmt->bind( 1, 'test' );
		$stmt->execute()->finish();

		$this->object->release( $conn );

		$this->assertEquals( 'INSERT INTO "mw_unit_test" ("name") VALUES (\'test\')', strval( $stmt ) );
	}

	public function testStmtSimpleBindTwo()
	{
		$sqlinsert2 =  'INSERT INTO "mw_unit_test" ("id", "name") VALUES (?, ?)';

		$conn = $this->object->acquire();

		$stmt2 = $conn->create( $sqlinsert2 );
		$stmt2->bind( 1, null, \Aimeos\MW\DB\Statement\Base::PARAM_NULL);
		$stmt2->bind( 2, 0.12, \Aimeos\MW\DB\Statement\Base::PARAM_FLOAT);
		$stmt2->execute()->finish();

		$this->object->release( $conn );

		$this->assertEquals( 'INSERT INTO "mw_unit_test" ("id", "name") VALUES (NULL, 0.12)', strval( $stmt2 ) );
	}

	public function testStmtSimpleBindThree()
	{
		$sqlinsert3 =  'INSERT INTO "mw_unit_test" ("name", "id") VALUES (\'?te?st?\', ?)';

		$conn = $this->object->acquire();

		$stmt2 = $conn->create( $sqlinsert3 );
		$stmt2->bind( 1, null, \Aimeos\MW\DB\Statement\Base::PARAM_NULL);
		$stmt2->execute()->finish();

		$this->object->release( $conn );

		$this->assertEquals( 'INSERT INTO "mw_unit_test" ("name", "id") VALUES (\'?te?st?\', NULL)', strval( $stmt2 ) );
	}

	public function testStmtSimpleBindParamType()
	{
		$sqlinsert2 =  'INSERT INTO "mw_unit_test" ("id", "name") VALUES (?, ?)';

		$conn = $this->object->acquire();

		$stmt2 = $conn->create( $sqlinsert2 );
		$stmt2->bind( 1, 0, \Aimeos\MW\DB\Statement\Base::PARAM_NULL);
		$stmt2->bind( 2, 0.15, 123);
		$result = $stmt2->execute();
		$rows = $result->affectedRows();
		$result->finish();

		$this->assertEquals( 'INSERT INTO "mw_unit_test" ("id", "name") VALUES (NULL, 0.15)', strval( $stmt2 ) );
		$this->assertEquals( 1, $rows );
	}

	public function testStmtSimpleBindInvalid()
	{
		$sqlinsert = 'INSERT INTO "mw_unit_test" ("name") VALUES (?)';

		$conn = $this->object->acquire();

		try {
			$stmt = $conn->create( $sqlinsert );
			$stmt->execute();
		} catch ( \Aimeos\MW\DB\Exception $de ) {
			$this->object->release( $conn );
			return;
		}

		$this->object->release( $conn );
		$this->fail('An expected exception has not been raised');
	}

	public function testStmtPreparedBindOne()
	{
		$sqlinsert = 'INSERT INTO "mw_unit_test" ("name") VALUES (?)';

		$conn = $this->object->acquire();

		$stmt = $conn->create( $sqlinsert, \Aimeos\MW\DB\Connection\Base::TYPE_PREP );
		$stmt->bind( 1, 'test' );
		$result = $stmt->execute();
		$rows = $result->affectedRows();
		$result->finish();

		$this->object->release( $conn );

		$this->assertEquals( 1, $rows );
	}

	public function testStmtPreparedBindTwo()
	{
		$sqlinsert2 = 'INSERT INTO "mw_unit_test" ("name", "id") VALUES (\'?te?st?\', ?)';

		$conn = $this->object->acquire();

		$stmt = $conn->create( $sqlinsert2, \Aimeos\MW\DB\Connection\Base::TYPE_PREP );
		$stmt->bind( 1, null );
		$result = $stmt->execute();
		$rows = $result->affectedRows();
		$result->finish();

		$this->object->release( $conn );

		$this->assertEquals( 1, $rows );
	}

	public function testResultFetch()
	{
		$sqlinsert = 'INSERT INTO "mw_unit_test" ("id", "name") VALUES (?, ?)';
		$sqlselect = 'SELECT * FROM "mw_unit_test"';

		$conn = $this->object->acquire();

		$stmt = $conn->create( $sqlinsert, \Aimeos\MW\DB\Connection\Base::TYPE_PREP );
		$stmt->bind( 1, 1 );
		$stmt->bind( 2, 'test' );
		$stmt->execute()->finish();

		$stmt = $conn->create( $sqlselect );
		$result = $stmt->execute();
		$row = $result->fetch();
		$result->finish();

		$this->object->release( $conn );

		$this->assertEquals( array( 'id' => 1, 'name' => 'test' ), $row );
	}

	public function testMultipleResults()
	{
		$sqlinsert = 'INSERT INTO "mw_unit_test" ("id", "name") VALUES (?, ?)';
		$sqlselect = 'SELECT * FROM "mw_unit_test"; SELECT * FROM "mw_unit_test"';

		$conn = $this->object->acquire();

		$stmt = $conn->create( $sqlinsert, \Aimeos\MW\DB\Connection\Base::TYPE_PREP );
		$stmt->bind( 1, 1 );
		$stmt->bind( 2, 'test' );
		$stmt->execute()->finish();

		$stmt->bind( 1, null, \Aimeos\MW\DB\Statement\Base::PARAM_NULL );
		$stmt->bind( 2, 1, \Aimeos\MW\DB\Statement\Base::PARAM_BOOL );
		$stmt->execute()->finish();

		$stmt->bind( 1, 123, \Aimeos\MW\DB\Statement\Base::PARAM_LOB );
		$stmt->bind( 2, 0.1, \Aimeos\MW\DB\Statement\Base::PARAM_FLOAT );
		$stmt->execute()->finish();


		$stmt = $conn->create( $sqlselect );
		$result = $stmt->execute();
		$resultSets = array();

		do {
			$resultSets[] = $result->fetch();
		}
		while( $result->nextResult() !== false );

		$result->finish();

		$this->object->release( $conn );


		/** @todo This doesn't work with PHP 5.3.11 and later but up to PHP 5.3.10, 5.4.x and 5.5.x are OK */
		/*
		$expected = array(
			array( 'id' => 1, 'name' => 'test' ),
			array( 'id' => 1, 'name' => 'test' ),
		);

		$this->assertEquals( $expected, $resultSets );
		*/
	}

	public function testWrongFieldType()
	{
		$this->setExpectedException('\\Aimeos\\MW\\DB\\Exception');
		$sqlinsert = 'INSERT INTO "mw_unit_test" ("id", "name") VALUES (?, ?)';

		$conn = $this->object->acquire();

		try
		{
			$stmt = $conn->create( $sqlinsert, \Aimeos\MW\DB\Connection\Base::TYPE_PREP );
			$stmt->bind( 1, 1 );
			$stmt->bind( 2, 'test', 123 );
		}
		catch ( \Aimeos\MW\DB\Exception $e )
		{
			$this->object->release( $conn );
			throw $e;
		}
	}

	public function testNonExisting()
	{
		$sql = 'SELECT * FROM "mw_non_existing"';

		$conn = $this->object->acquire();

		$this->setExpectedException('\\Aimeos\\MW\\DB\\Exception');

		try
		{
			$conn->create( $sql )->execute()->finish();
		}
		catch ( \Aimeos\MW\DB\Exception $e )
		{
			$this->object->release( $conn );
			throw $e;
		}
	}

	public function testSqlError()
	{
		$conn = $this->object->acquire();

		$this->setExpectedException('\\Aimeos\\MW\\DB\\Exception');

		try
		{
			$conn->create( 'SELECT *' )->execute()->finish();
		}
		catch ( \Aimeos\MW\DB\Exception $e )
		{
			$this->object->release( $conn );
			throw $e;
		}
	}

	public function testWrongStmtType()
	{
		$sql = 'SELECT * FROM "mw_unit_test"';

		$conn = $this->object->acquire();

		$this->setExpectedException('\\Aimeos\\MW\\DB\\Exception');

		try
		{
			$conn->create( $sql, 123 );
		}
		catch (\Aimeos\MW\DB\Exception $e)
		{
			$this->object->release( $conn );
			throw $e;
		}
	}

	public function testPDOException()
	{
		$this->setExpectedException('\\Aimeos\\MW\\DB\\Exception');
		$conn = new TestForPDOException();
		$this->object->release($conn);
	}

	public function testDBFactory()
	{
		$this->assertInstanceOf('\\Aimeos\\MW\\DB\\Manager\\Iface', $this->object);
	}

	public function testFactoryFail()
	{
		$this->setExpectedException('\\Aimeos\\MW\\DB\\Exception');
		\Aimeos\MW\DB\Factory::createManager( \TestHelperMw::getConfig(), 'notDefined' );
	}
}



class TestForPDOException implements \Aimeos\MW\DB\Connection\Iface
{
	public function create($sql, $type = \Aimeos\MW\DB\Connection\Base::TYPE_SIMPLE)
	{
	}

	public function escape($data)
	{
	}

	public function begin()
	{
	}

	public function commit()
	{
	}

	public function rollback()
	{
	}
}
