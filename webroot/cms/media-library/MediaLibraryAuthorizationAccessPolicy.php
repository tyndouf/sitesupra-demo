<?php

namespace Supra\Cms\MediaLibrary;

use Supra\Authorization\AccessPolicy\AuthorizationThreewayWithEntitiesAccessPolicy;
use Supra\FileStorage\Entity as FileEntity;
use Supra\ObjectRepository\ObjectRepository;
use Supra\User\Entity\AbstractUser;
use Supra\Validator\FilteredInput;

class MediaLibraryAuthorizationAccessPolicy extends AuthorizationThreewayWithEntitiesAccessPolicy
{

	function __construct()
	{
		parent::__construct('files', FileEntity\Abstraction\File::CN());
	}

	public function getEntityTree(FilteredInput $input) 
	{
		$em = ObjectRepository::getEntityManager($this);
		
		$fr = $em->getRepository(FileEntity\Abstraction\File::CN());

		$slash = new FileEntity\SlashFolder();
		
		$slashNode = array(
			'id' => $slash->getId(),
			'title' => $slash->getFileName(),
			'icon' => 'folder'
		);
		
		$entityTree = array();
				
		$rootNodes = $fr->getRootNodes();
		
		foreach ($rootNodes as $rootNode) {
			$tree = $this->buildMediaLibraryTreeArray($rootNode);
			if ( ! is_null($tree)) {
				array_push($entityTree, $tree);
			}
		}

		$slashNode['children'] = $entityTree;
		
		return array($slashNode);
	}
	
	private function buildMediaLibraryTreeArray(FileEntity\Abstraction\File $file) 
	{ 
		if( ! ($file instanceof FileEntity\Folder)) {
			return null;
		}
		
		$array = array(
			'id' => $file->getId(),
			'title' => $file->getFileName(),
			'icon' => 'folder'
		);

		$array['children'] = array();

		foreach ($file->getChildren() as $child) {
			
			$childArray = $this->buildMediaLibraryTreeArray($child);

			if ( ! empty($childArray)) {
				$array['children'][] = $childArray;
			}
		}

		if (count($array['children']) == 0) {
			unset($array['children']);
		} 
		
		return $array;
	}
	
	/**
	 * {@inheritdoc}
	 * @param AbstractUser $user
	 * @return array
	 */
	protected function getAllEntityPermissionStatuses(AbstractUser $user) 
	{
		return parent::getAllEntityPermissionStatuses($user);
	}
	
}
