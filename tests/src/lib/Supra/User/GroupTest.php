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
class GroupTest extends \PHPUnit_Extensions_OutputTestCase
{
	const SALT = '2j*s@;0?0saASf1%^&1!';

	private function cleanUp($delete = false)
	{
		$userProvider = ObjectRepository::getUserProvider($this);
		$em = $userProvider->getEntityManager();
		$query = $em->createQuery("delete from Supra\User\Entity\User");
		$query->execute();
		$query = $em->createQuery("delete from Supra\User\Entity\Group");
		$query->execute();
		$query = $em->createQuery("delete from Supra\User\Entity\Abstraction\User");

		$query->execute();
	}

	public function testCreateGroup()
	{
		$this->cleanUp();

		$group = new Entity\Group();
		$userProvider = ObjectRepository::getUserProvider($this);
		$em = $userProvider->getEntityManager();

		/* @var $em Doctrine\ORM\EntityManager */
		$em->persist($group);

		$timeNow = new \DateTime('now');

		$group->setName('Super Heroes');
		$group->setCreatedTime($timeNow);
		$group->setModifiedTime($timeNow);

		$em->flush();
	}

	public function testGetGroupUsers()
	{
		$this->cleanUp();

		/* @var $userProvider User\UserProvider */
		$userProvider = ObjectRepository::getUserProvider($this);

		/* @var $em Doctrine\ORM\EntityManager */
		$em = $userProvider->getEntityManager();

		$group = new Entity\Group();
		$em->persist($group);

		$group->setName('group111');
		$group->setCreatedTime($timeNow);
		$group->setModifiedTime($timeNow);
		$em->flush();

		foreach (array('user1', 'user2', 'user444') as $userName) {

			$user = new Entity\User();
			$em->persist($user);

			$user->setName($userName);
			$user->setLogin($userName);
			$user->setEmail($userName);
			$user->setGroup($group);
			$em->flush();
		}

		$group2 = $userProvider->findGroupByName('group111');
		$group2Users = $userProvider->getAllUsersInGroup($group2);

		self::assertEquals(count($group2Users), 3);
	}

}