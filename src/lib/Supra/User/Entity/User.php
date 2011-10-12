<?php

namespace Supra\User\Entity;

/**
 * User object
 * @Entity
 * @Table(name="user")
 */
class User extends Abstraction\User
{
	/**
	 * @Column(type="string", name="password", nullable=true)
	 * @var string
	 */
	protected $password;
	
	/**
	 * @Column(type="string", name="login", nullable=false, unique=true)
	 * @var string
	 */
	protected $login;
	
	/**
	 * @Column(type="string", name="email", nullable=false, unique=true)
	 * @var string
	 */
	protected $email;
	
	/**
	* @ManyToOne(targetEntity="Group")
	* @JoinColumn(name="group_id", referencedColumnName="id") 
	 */
	protected $group;
	
	/**
	 * @Column(type="datetime", name="last_login_at", nullable="false")
	 * @var \DateTime
	 */
	protected $lastLoginTime;
	
	/**
	 * @Column(type="boolean", name="active")
	 * @var boolean
	 */
	protected $active = true;
	
	/**
	 * @Column(type="string", name="salt", nullable=false, length="23")
	 * @var string
	 */
	protected $salt;
	
	/**
	 * Generates random salt for new users
	 */
	public function __construct()
	{
		parent::__construct();
		$this->resetSalt();
	}
	
	/**
	 * Returns user password
	 * @return type 
	 */
	public function getPassword()
	{
		return $this->password;
	}
	
	/**
	 * Sets user password
	 * @param type $password 
	 */
	public function setPassword($password)
	{
		$this->password = $password;
	}
	
	/**
	 * @return string
	 */
	public function getLogin()
	{
		return $this->login;
	}

	/**
	 * @param string $login 
	 */
	public function setLogin($login)
	{
		$this->login = $login;
	}

	/**
	 * Returns user email 
	 * @return string 
	 */
	public function getEmail()
	{
		return $this->email;
	}

	/**
	 * Sets user email
	 * @param string $email 
	 */
	public function setEmail($email)
	{
		$this->email = $email;
	}

	/**
	 * Returns user last logged in time 
	 * @return \DateTime 
	 */
	public function getLastLoginTime()
	{
		return $this->lastLoginTime;
	}

	/**
	 * Sets user last logged in time 
	 * @param \DateTime $lastLoginTime
	 */
	public function setLastLoginTime(\DateTime $lastLoginTime)
	{
		$this->lastLoginTime = $lastLoginTime;
	}

	/**
	 * Get if the user is active
	 * @return boolean
	 */
	public function isActive()
	{
		return $this->active;
	}

	/**
	 * Sets user status
	 * @param boolean $active 
	 */
	public function setActive($active)
	{
		$this->active = $active;
	}
	
	/**
	 * Returns salt
	 * @return string 
	 */
	public function getSalt()
	{
		return $this->salt;
	}

	/**
	 * Resets salt and returns
	 * @return string
	 */
	public function resetSalt()
	{
		$this->salt = uniqid('', true);
		
		return $this->salt;
	}
	
	/**
	 * @return Entity\Group
	 */
	public function getGroup()
	{
		return $this->group;
	}

	/**
	 * @param Entity\Group $group
	 */
	public function setGroup($group)
	{
		$this->group = $group;
	}
	
   /**
   * {@inheritDoc}
   */
	public function isSuper() 
	{
		if( ! is_null($this->getGroup())) {
			return $this->getGroup()->isSuper();
		}
		else {
			return false;
		}
	}
}
