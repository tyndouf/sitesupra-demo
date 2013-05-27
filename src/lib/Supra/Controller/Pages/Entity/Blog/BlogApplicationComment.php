<?php

namespace Supra\Controller\Pages\Entity\Blog;

use Supra\Controller\Pages\Blog\BlogApplication;
use Supra\Controller\Pages\Entity\PageLocalization;

/**
 * @Entity
 */
class BlogApplicationComment extends \Supra\Controller\Pages\Entity\Abstraction\Entity
{
	/**
	 * Contains blog application localization id
	 * 
	 * @Column(type="supraId20")
	 * @var string 
	 */
	protected $applicationLocalizationId;
	
	/**
	 * Related post page localization id
	 * 
	 * @Column(type="supraId20")
	 * @var string 
	 */
	protected $pageLocalizationId;
	
	/**
	 * @Column(type="boolean", nullable=false)
	 * @var boolean
	 */
	protected $approved; 
	
	/**
	 * @Column(type="datetime", nullable=false)
	 * @var \DateTime
	 */
	protected $creationTime;
	
	/**
	 * @Column(type="string", nullable=true)
	 * @var string 
	 */
	protected $authorName;
	
	/**
	 * @Column(type="string", nullable=true)
	 * @var string 
	 */
	protected $authorEmail;
	
	/**
	 * @Column(type="string", nullable=true)
	 * @var string 
	 */
	protected $authorWebsite;
	
	/**
	 * @Column(type="text", nullable=false)
	 * @var string 
	 */
	protected $comment;
	
	
	public function __construct(BlogApplication $application) 
	{
		parent::__construct();
		
		$this->applicationLocalizationId = $application->getApplicationLocalization()
				->getId();
		
		$this->creationTime = new \DateTime('now');
	}
	
	/**
	 * @param string $name
	 */
	public function setAuthorName($name)
	{
		$this->authorName = $name;
	}

	/**
	 * @return string
	 */
	public function getAuthorName()
	{
		return $this->authorName;
	}
	
	/**
	 * @param string $email
	 */
	public function setAuthorEmail($email)
	{
		$this->authorEmail = $email;
	}
	
	/**
	 * @return string
	 */
	public function getAuthorEmail()
	{
		return $this->authorEmail;
	}
	
	/**
	 * @param string $website
	 */
	public function setAuthorWebsite($website)
	{
		$this->authorWebsite = $website;
	}
	
	/**
	 * @return string
	 */
	public function getAuthorWebsite()
	{
		return $this->authorWebsite;
	}
	
	/**
	 * @param string $comment
	 */
	public function setComment($comment)
	{
		$this->comment = $comment;
	}
	
	/**
	 * @return string
	 */
	public function getComment()
	{
		return $this->comment;
	}
	
	public function setBlogApplication(BlogApplication $application) 
	{
		$this->applicationLocalizationId = $application->getApplicationLocalization()
				->getId();
	}
	
	public function setPageLocalization(PageLocalization $localization)
	{
		$this->pageLocalizationId = $localization->getId();
	}
	
	/**
	 * @param boolean $approved
	 */
	public function setApproved($approved)
	{
		$this->approved = $approved;
	}
	
	/**
	 * @return boolean
	 */
	public function isApproved()
	{
		return $this->approved === true;
	}
}
