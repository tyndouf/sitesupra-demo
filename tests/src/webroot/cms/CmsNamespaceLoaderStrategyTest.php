<?php

namespace Supra\Tests\Cms;

use Supra\Cms\CmsNamespaceLoaderStrategy;

require_once dirname(__FILE__) . '/../../../../src/webroot/cms/CmsNamespaceLoaderStrategy.php';

/**
 * Test class for CmsNamespaceLoaderStrategy.
 * Generated by PHPUnit on 2011-08-16 at 13:14:16.
 */
class CmsNamespaceLoaderStrategyTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var CmsNamespaceLoaderStrategy
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp()
	{
		$this->object = new CmsNamespaceLoaderStrategy('Supra\Cms', SUPRA_WEBROOT_PATH . 'cms');
	}

	/**
	 * @todo Implement testFindClass().
	 */
	public function testFindClass()
	{
		self::assertEquals('content-manager/root/RootAction.php', $this->object->convertToFilePath('ContentManager\Root\RootAction'));
		self::assertEquals('CmsController.php', $this->object->convertToFilePath('CmsController'));
	}

}
