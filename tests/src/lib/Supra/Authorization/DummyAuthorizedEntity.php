<?php

namespace Supra\Tests\Authorization;

use Supra\NestedSet\Node\ArrayNode;

use Supra\Authorization\AuthorizedEntityInterface;
use Supra\Authorization\Permission\Permission;

use Supra\User\Entity\Abstraction\User;

use Supra\Authorization\AuthorizationProvider;

class DummyAuthorizedEntity extends ArrayNode implements AuthorizedEntityInterface
{
	const PERMISSION_EDIT_NAME = 'dummy_edit';
	const PERMISSION_EDIT_MASK = 512;
	
	const PERMISSION_PUBLISH_NAME = 'dummy_publish';
	const PERMISSION_PUBLISH_MASK = 1024;
	
	public function __construct($title) 
	{
		$this->setTitle($title);
	}
	
	function getAuthorizationId() 
	{
		return $this->getTitle();
	}
	
	function getAuthorizationClass() 
	{
		return __CLASS__;
	}
	
	static function registerPermissions(AuthorizationProvider $ap)
	{
		$ap->registerGenericEntityPermission(self::PERMISSION_EDIT_NAME, self::PERMISSION_EDIT_MASK, __CLASS__);
		$ap->registerGenericEntityPermission(self::PERMISSION_PUBLISH_NAME, self::PERMISSION_PUBLISH_MASK, __CLASS__);		
	}
	
	function authorize(User $user, $permission, $grant) 
	{
		return true;
	}
	
	function getAuthorizationAncestors() 
	{
		return $this->getAncestors(0, true);
	}
	
	public static function __CLASS()  
	{
		return get_called_class();
	}
}
