<?php

namespace Supra\Tests\Authorization;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Authorization\AuthorizationProvider;
use Supra\Authorization\Permission\PermissionStatus;

use Supra\Cms\ApplicationConfiguration;

use Supra\NestedSet\ArrayRepository;
use Supra\Authorization\AccessPolicy\AuthorizationAllOrNoneAccessPolicy;

require_once SUPRA_COMPONENT_PATH . 'Authentication/AuthenticationSessionNamespace.php';

/**
 * Test class for ObjectRepository.
 * Generated by PHPUnit on 2011-08-16 at 10:50:12.
 */
class BasicAuthorizationTest extends \PHPUnit_Framework_TestCase 
{
	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $em; 
	
	/**
	 * @var AuthorizationProvider
	 */
	private $ap;
	
	
	/**
	 *
	 * @var \Supra\User\UserProvider;
	 */
	private $up;
	
	function setUp() 
	{
		$this->em = ObjectRepository::getEntityManager($this);
		
		// ACL model creation
		try {
			$this->em->getConnection()->getWrappedConnection()->exec(file_get_contents(SUPRA_PATH . '/../database/authorization-mysql.sql'));
		} catch (\Exception $e) {}
		
		$this->em->beginTransaction();
//		$this->em->beginTransaction();
		
		$this->up = ObjectRepository::getUserProvider($this);

		$this->ap = new AuthorizationProvider(
			ObjectRepository::getEntityManager($this),
			array(
				'class_table_name'         => 'acl_classes',
				'entry_table_name'         => 'acl_entries',
				'oid_table_name'           => 'acl_object_identities',
				'oid_ancestors_table_name' => 'acl_object_identity_ancestors',
				'sid_table_name'           => 'acl_security_identities',
			)
		);
		ObjectRepository::setDefaultAuthorizationProvider($this->ap);
		
		$sessionHandler = new \Supra\Session\Handler\PhpSessionHandler();

		$sessionNamespaceManager = new \Supra\Session\SessionNamespaceManager($sessionHandler);
		ObjectRepository::setDefaultSessionNamespaceManager($sessionNamespaceManager);

		$authenticationSessionNamespace = $sessionNamespaceManager
			->getOrCreateSessionNamespace('Tests', 'Supra\Tests\Authentication\AuthenticationSessionNamespace');

		ObjectRepository::setSessionNamespace(__NAMESPACE__, $authenticationSessionNamespace);	
	}
	
	function makeNewUser() 
	{
		$name = 'test_' . rand(0, 999999999);
		
		$em = ObjectRepository::getEntityManager($this);
		/* @var $em Doctrine\ORM\EntityManager */
		
		$user = new \Supra\User\Entity\User();
		$em->persist($user);

		$user->setName($name);
		$user->setEmail($name . '@' . $name . '.com');
		
		ObjectRepository::getUserProvider($this)
						->getAuthAdapter()
						->credentialChange($user, 'Noris');

		$em->flush();		
		
		\Log::debug('Made test user: ' . $name);
		
		return $user;
	}
	
	function tearDown() 
	{
		$this->em->rollback();
//		$this->em->rollback();
	}
	
	function testApplicationGrantAccessPermission() 
	{
		$appConfig = new \Supra\Cms\ApplicationConfiguration();
		$appConfig->id = 'dummy';
		$appConfig->title = 'Dummy';
		$appConfig->path = '/cms/dummy';
		$appConfig->icon = '/cms/lib/supra/img/apps/dummy';
		$appConfig->applicationNamespace = 'Supra\Tests\DummyApplication';
		$appConfig->authorizationAccessPolicyClass = '\Supra\Authorization\AccessPolicy\AuthorizationAllOrNoneAccessPolicy';
		$appConfig->authorizationAccessPolicy = new AuthorizationAllOrNoneAccessPolicy();
		$appConfig->configure();
		
		$user1 = $this->makeNewUser();
		$user2 = $this->makeNewUser();
		$user3 = $this->makeNewUser();

		$this->ap->grantApplicationAllAccessPermission($user1, $appConfig);
		$this->ap->grantApplicationSomeAccessPermission($user3, $appConfig);
		
		//self::assertEquals($this->ap->isApplicationAllAccessGranted($user1, $appConfig), true);
		self::assertEquals($this->ap->isApplicationAllAccessGranted($user2, $appConfig), false);

		self::assertEquals($this->ap->isApplicationAllAccessGranted($user3, $appConfig), false);		
		self::assertEquals($this->ap->isApplicationSomeAccessGranted($user3, $appConfig), true);
		
		$this->ap->revokeApplicationAllAccessPermission($user1, $appConfig);
		$this->ap->grantApplicationSomeAccessPermission($user1, $appConfig);
		
		self::assertEquals($this->ap->isApplicationAllAccessGranted($user1, $appConfig), false);
		self::assertEquals($this->ap->isApplicationAllAccessGranted($user2, $appConfig), false);
		self::assertEquals($this->ap->isApplicationAllAccessGranted($user3, $appConfig), false);
		
		self::assertEquals($this->ap->isApplicationSomeAccessGranted($user1, $appConfig), true);
	}
	
	function __testEntityAccessPermission() 
	{
		$user1 = $this->makeNewUser();
		
		$rep = new ArrayRepository();
		
		$nodeNames = array(
				'meat',
				'fruit',
				'yellow',
				'red',
				'cherry',
				'tomato',
				'banana',
				'pork',
				'beef',
				'fish',
				'shark',
				'tuna'
			);
			
		$nodes = array();
		foreach($nodeNames as $nodeName) {
			
			$nodes[$nodeName] = new DummyAuthorizedEntity($nodeName);
			$rep->add($nodes[$nodeName]);
		}
		
		$this->ap->registerGenericEntityPermission(
				DummyAuthorizedEntity::PERMISSION_EDIT_NAME, 
				DummyAuthorizedEntity::PERMISSION_EDIT_MASK, 
				DummyAuthorizedEntity::__CLASS()
		);
		
		$this->ap->registerGenericEntityPermission(
				DummyAuthorizedEntity::PERMISSION_PUBLISH_NAME, 
				DummyAuthorizedEntity::PERMISSION_PUBLISH_MASK, 
				DummyAuthorizedEntity::__CLASS()
		);
		
		$nodes['meat']->addChild($nodes['pork']);
		$nodes['meat']->addChild($nodes['beef']);
		$nodes['meat']->addChild($nodes['fish']);
		$nodes['fish']->addChild($nodes['shark']);
		$nodes['fish']->addChild($nodes['tuna']);
		
		$nodes['fruit']->addChild($nodes['yellow']);
		$nodes['fruit']->addChild($nodes['red']);
		
		$nodes['yellow']->addChild($nodes['banana']);
		
		$nodes['red']->addChild($nodes['cherry']);
		$nodes['red']->addChild($nodes['tomato']);
		
		$this->ap->setPermsission($user1, $nodes['fruit'], DummyAuthorizedEntity::PERMISSION_EDIT_NAME, PermissionStatus::ALLOW);
		
		self::assertEquals($this->ap->isPermissionGranted($user1, $nodes['fruit'], DummyAuthorizedEntity::PERMISSION_EDIT_NAME), true);
		self::assertEquals($this->ap->isPermissionGranted($user1, $nodes['banana'], DummyAuthorizedEntity::PERMISSION_EDIT_NAME), true);
		
		$this->ap->setPermsission($user1, $nodes['fish'], DummyAuthorizedEntity::PERMISSION_EDIT_NAME, PermissionStatus::ALLOW);

		self::assertEquals($this->ap->isPermissionGranted($user1, $nodes['tuna'], DummyAuthorizedEntity::PERMISSION_EDIT_NAME), true);
		self::assertEquals($this->ap->isPermissionGranted($user1, $nodes['meat'], DummyAuthorizedEntity::PERMISSION_EDIT_NAME), false);
	}
}
