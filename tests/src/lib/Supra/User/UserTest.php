<?php

namespace Supra\Tests\User;

use Supra\Tests\TestCase;
use Supra\User;
use Supra\User\Entity;
use Supra\User\Exception;
use Supra\ObjectRepository\ObjectRepository;

require_once 'PHPUnit/Extensions/OutputTestCase.php';

/**
 * Test class for EmptyController
 */
class UserTest extends \PHPUnit_Extensions_OutputTestCase
{
	const TEST_USER_NAME = 'Chuck123';
	
	private static $testEmailPattern = '%@chucknorris.com';
	
	/**
	 * @var User\UserProvider
	 */
	private $userProvider;
	
	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $em;
	
	/**
	 * @var \Supra\Authentication\AuthenticationPassword 
	 */
	private $password;
	
	protected function setUp()
	{
		$this->userProvider = ObjectRepository::getUserProvider($this);
		$this->em = $this->userProvider->getEntityManager();
		
		self::assertEquals('test', $this->em->_mode);
		
		$plainPassword = 'Norris';
		$this->password = new \Supra\Authentication\AuthenticationPassword($plainPassword);
	}
	
//	private function cleanUp()
//	{
//		$query = $this->em->createQuery("delete from Supra\User\Entity\User");
//		$query->execute();
//		$query = $this->em->createQuery("delete from Supra\User\Entity\Group");
//		$query->execute();
//		$query = $this->em->createQuery("delete from Supra\User\Entity\Abstraction\User");
//
//		$query->execute();
//	}
	
	private function cleanUp()
	{
		// Removes test users
		$query = $this->em->createQuery("delete from Supra\User\Entity\User u where u.login LIKE ?0");
		$query->execute(array(self::$testEmailPattern));
		
		$this->em->clear();
	}

	public function testCreateUser()
	{
		$randomPart = uniqid();
		$randomEmail = "awesomechuck{$randomPart}@chucknorris.com";
		
		$this->cleanUp();

		$user = new Entity\User();

		$this->em->persist($user);

		$user->setName(self::TEST_USER_NAME);
		$user->setEmail($randomEmail);
		
		$this->userProvider->getAuthAdapter()
				->credentialChange($user, $this->password);
		
		$this->em->flush();
	}

	public function testModifyUser()
	{
		$this->cleanUp();
		$this->testCreateUser();
		
		$repo = $this->em->getRepository('Supra\User\Entity\User');

		$user = $repo->findOneByName(self::TEST_USER_NAME);

		if (empty($user)) {
			$this->fail('Cant\'t find user with name: Chuck');
		}

		$randomPart = uniqid();
		$randomEmail = "awesomechuck{$randomPart}@chucknorris.com";
		
		$user->setEmail($randomEmail);
		
		$this->userProvider->getAuthAdapter()
				->credentialChange($user);

		$this->em->flush();

		$result = $repo->findOneByEmail($randomEmail);

		if (empty($user)) {
			$this->fail('Cant\'t find user with name: Chuck');
		}
	}

	public function testModifyUserWithWrongEmail()
	{

		$userProvider = ObjectRepository::getUserProvider($this);
		/* @var $repo Doctrine\ORM\EntityRepository */
		$repo = $this->em->getRepository('Supra\User\Entity\User');

		$user = $repo->findOneByName(self::TEST_USER_NAME);

		if (empty($user)) {
			$this->fail('Cant\'t find user with name: Chuck');
		}

//		$this->em->persist($user);

		$user->setEmail('awesomechuckchucknorris.com');

		$this->userProvider->getAuthAdapter()
				->credentialChange($user);
		
		try {
			$userProvider->validate($user);
		} catch (Exception\RuntimeException $exc) {
			return;
		}
		
		$this->em->flush();

		$this->fail('Succeed to change email');
	}

	public function testDeleteUser()
	{

		/* @var $repo Doctrine\ORM\EntityRepository */
		$repo = $this->em->getRepository('Supra\User\Entity\User');

		$user = $repo->findOneByName(self::TEST_USER_NAME);

		if (empty($user)) {
			$this->fail('Cant\'t find user with name: Chuck');
		}

		$this->em->remove($user);
		$this->em->flush();

		$result = $repo->findOneByName(self::TEST_USER_NAME);

		if ( ! empty($result)) {
			$this->fail('Chuck should not exist in database. Nobody can add records on Chuck');
		}
	}

	public function testAddUserWithExistentEmail()
	{
		$randomPart = uniqid();
		$randomEmail = "awesomechuck{$randomPart}@chucknorris.com";
		
		$this->cleanUp();

		$user = new Entity\User();
		$userProvider = ObjectRepository::getUserProvider($this);

		$this->em->persist($user);

		$user->setName(self::TEST_USER_NAME);
		$user->setEmail($randomEmail);
		
		$this->userProvider->getAuthAdapter()
				->credentialChange($user, $this->password);

		$userProvider->validate($user);

		$this->em->flush();

		$user = new Entity\User();

		$this->em->persist($user);

		$user->setName(self::TEST_USER_NAME);
		$user->setEmail($randomEmail);
		
		$this->userProvider->getAuthAdapter()
				->credentialChange($user, $this->password);

		try {
			$userProvider->validate($user);
		} catch (Exception\RuntimeException $exc) {
			return;
		}

		$this->fail('Test should catch Runtime exception because user with same email already exists.');
	}
	
	public function testSaltReset()
	{
		$user = new Entity\User();
		$salt1 = $user->getSalt();
		
		self::assertNotEmpty($salt1);
		
		$salt2 = $user->resetSalt();
		
		self::assertNotEmpty($salt2);
		
		self::assertNotEquals($salt2, $salt1);
	}

}