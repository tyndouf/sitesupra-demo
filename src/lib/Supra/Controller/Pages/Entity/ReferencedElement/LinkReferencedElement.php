<?php

namespace Supra\Controller\Pages\Entity\ReferencedElement;

use Supra\Controller\Pages\Entity\Abstraction\Localization;
use Supra\FileStorage\Entity\File;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Uri\Path;
use Supra\Controller\Pages\Entity\GroupPage;

/**
 * @Entity
 */
class LinkReferencedElement extends ReferencedElementAbstract
{
	const TYPE_ID = 'link';
	
	const RESOURCE_PAGE = 'page';
	const RESOURCE_FILE = 'file';
	const RESOURCE_LINK = 'link';
	
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $resource;
	
	/**
	 * @Column(type="string", nullable="true")
	 * @var string
	 */
	protected $href;
	
	/**
	 * @Column(type="string", nullable="true")
	 * @var string
	 */
	protected $target;
	
	/**
	 * @Column(type="string", nullable="true")
	 * @var string
	 */
	protected $title;
	
	/**
	 * Page localization ID to keep link data without existant real page.
	 * SQL naming for CMS usage, should be fixed (FIXME).
	 * @Column(type="sha1", nullable="true")
	 * @var string
	 */
	protected $pageId;
	
	/**
	 * File ID to keep link data without existant real file.
	 * SQL naming for CMS usage, should be fixed (FIXME).
	 * @Column(type="sha1", nullable="true")
	 * @var string
	 */
	protected $fileId;
	
	/**
	 * @return string
	 */
	public function getResource()
	{
		return $this->resource;
	}

	/**
	 * @param string $resource
	 */
	public function setResource($resource)
	{
		$this->resource = $resource;
	}

	/**
	 * @return string
	 */
	public function getHref()
	{
		return $this->href;
	}

	/**
	 * @param string $resource
	 */
	public function setHref($href)
	{
		$this->href = $href;
	}
	
	/**
	 * @return string
	 */
	public function getTarget()
	{
		return $this->target;
	}

	/**
	 * @param string $target
	 */
	public function setTarget($target)
	{
		$this->target = $target;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string $resource
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}
	
	/**
	 * Get element title
	 * @return string
	 */
	public function getElementTitle()
	{
		$title = null;
		
		switch ($this->resource) {
			
			case self::RESOURCE_PAGE:
				$pageData = $this->getPage();

				/* @var $pageData Localization */
				if ( ! is_null($pageData)) {
					$title = $pageData->getTitle();
				}
				break;
			
			case self::RESOURCE_FILE:
				$file = $this->getFile();

				if ($file instanceof File) {
					$localeId = ObjectRepository::getLocaleManager($this)
							->getCurrent()
							->getId();
					
					$title = $file->getTitle($localeId);
				}

				break;
				
			case self::RESOURCE_LINK:
				$title = $this->getHref();
				break;

			default:
				$this->log()->warn("Unrecognized resource for supra html markup link tag, data: $this");
		}
		
		return $title;
	}
	
	/**
	 * @return string
	 */
	public function getPageId()
	{
		return $this->pageId;
	}

	/**
	 * @param string $pageId
	 */
	public function setPageId($pageId)
	{
		$this->pageId = $pageId;
	}

	/**
	 * @return string
	 */
	public function getFileId()
	{
		return $this->fileId;
	}

	/**
	 * @param string $fileId
	 */
	public function setFileId($fileId)
	{
		$this->fileId = $fileId;
	}
	
	/**
	 * {@inheritdoc}
	 * @return array
	 */
	public function toArray()
	{
		$array = array(
			'type' => self::TYPE_ID,
			'resource' => $this->resource,
			'title' => $this->title,
			'target' => $this->target,
			'page_id' => $this->pageId,
			'file_id' => $this->fileId,
			'href' => $this->href,
		);
		
		return $array;
	}
	
	/**
	 * {@inheritdoc}
	 * @param array $array
	 */
	public function fillArray(array $array)
	{
		$this->resource = $array['resource'];
		$this->title = $array['title'];
		$this->target = $array['target'];
		$this->pageId = $array['page_id'];
		$this->fileId = $array['file_id'];
		$this->href = $array['href'];
	}
	
	/**
	 * @return Localization
	 */
	public function getPage()
	{
		if (empty($this->pageId)) {
			return;
		}
		
		$em = ObjectRepository::getEntityManager($this);
		$pageData = $em->find(Localization::CN(), $this->pageId);
		
		if (empty($pageData)) {
			$master = $em->find(GroupPage::CN(), $this->pageId);
			
			if ($master instanceof GroupPage) {
				//FIXME: somehow better?
				$locale = ObjectRepository::getLocaleManager($this)
						->getCurrent()
						->getId();
				$pageData = $master->getLocalization($locale);
			}
		}
		
		return $pageData;
	}
	
	/**
	 * @return File
	 */
	public function getFile()
	{
		if (empty($this->fileId)) {
			return;
		}
		
		$fs = ObjectRepository::getFileStorage($this);
		$em = $fs->getDoctrineEntityManager();
		$file = $em->find(File::CN(), $this->fileId);
		
		return $file;
	}
	
	/**
	 * Get URL of the link
	 * @return string
	 */
	public function getUrl()
	{
		$url = null;
		
		switch ($this->getResource()) {
			
			case self::RESOURCE_PAGE:
				$pageData = $this->getPage();

				/* @var $pageData PageLocalization */
				if ( ! is_null($pageData)) {
					$path = $pageData->getPath();

					if ( ! is_null($path)) {
						$url = $path->getPath(Path::FORMAT_BOTH_DELIMITERS);
					}
				}
				break;
			
			case self::RESOURCE_FILE:
				$file = $this->getFile();

				if ($file instanceof File) {
					$fs = ObjectRepository::getFileStorage($this);
					$url = $fs->getWebPath($file);
				}

				break;
				
			case self::RESOURCE_LINK:
				$url = $this->getHref();
				break;

			default:
				$this->log()->warn("Unrecognized resource for supra html markup link tag, data: $this");
		}
		
		return $url;
	}

}
