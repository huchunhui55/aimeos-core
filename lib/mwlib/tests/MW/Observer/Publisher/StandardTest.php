<?php

namespace Aimeos\MW\Observer\Publisher;


/**
 * Test class for \Aimeos\MW\Session\CMSLite.
 *
 * @copyright Metaways Infosystems GmbH, 2011
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015
 */
class StandardTest extends \PHPUnit_Framework_TestCase
{
	private $object;


	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @access protected
	 */
	protected function setUp()
	{
		$this->object = new TestPublisher();
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 *
	 * @access protected
	 */
	protected function tearDown()
	{
	}


	public function testaddListener()
	{
		$l = new TestListener();

		$this->object->addListener($l, 'test');
	}

	public function testRemoveListener()
	{
		$l = new TestListener();

		$this->object->addListener($l, 'test');
		$this->object->removeListener($l, 'test');
	}

	public function testclearListeners()
	{
		$this->object->clearListenersPublic();
	}

	public function testnotifyListeners()
	{
		$l = new TestListener();
		$this->object->addListener($l, 'test');
		$this->object->addListener($l, 'testagain');

		$this->object->notifyListenersPublic('test', 'warn');
		$this->object->notifyListenersPublic('testagain', 'warn');
	}
}


class TestPublisher extends \Aimeos\MW\Observer\Publisher\Base
{
	/**
	 * @param string $action
	 * @param string $value
	 */
	public function notifyListenersPublic($action, $value = null)
	{
		$this->notifyListeners($action, $value);
	}

	public function clearListenersPublic()
	{
		$this->clearListeners();
	}
}


class TestListener implements \Aimeos\MW\Observer\Listener\Iface
{
	public function register( \Aimeos\MW\Observer\Publisher\Iface $p )
	{
	}

	public function update( \Aimeos\MW\Observer\Publisher\Iface $p, $action, $value = null )
	{
		if ($action == 'test') {
			return false;
		}
	}
}
