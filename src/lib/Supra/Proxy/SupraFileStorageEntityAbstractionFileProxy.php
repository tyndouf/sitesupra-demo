<?php

namespace Supra\Proxy;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ORM. DO NOT EDIT THIS FILE.
 */
class SupraFileStorageEntityAbstractionFileProxy extends \Supra\FileStorage\Entity\Abstraction\File implements \Doctrine\ORM\Proxy\Proxy
{
    private $_entityPersister;
    private $_identifier;
    public $__isInitialized__ = false;
    public function __construct($entityPersister, $identifier)
    {
        $this->_entityPersister = $entityPersister;
        $this->_identifier = $identifier;
    }
    /** @private */
    public function __load()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;

            if (method_exists($this, "__wakeup")) {
                // call this after __isInitialized__to avoid infinite recursion
                // but before loading to emulate what ClassMetadata::newInstance()
                // provides.
                $this->__wakeup();
            }

            if ($this->_entityPersister->load($this->_identifier, $this) === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            unset($this->_entityPersister, $this->_identifier);
        }
    }
    
    
    public function getId()
    {
        $this->__load();
        return parent::getId();
    }

    public function getLeftValue()
    {
        $this->__load();
        return parent::getLeftValue();
    }

    public function getRightValue()
    {
        $this->__load();
        return parent::getRightValue();
    }

    public function getLevel()
    {
        $this->__load();
        return parent::getLevel();
    }

    public function getCreatedTime()
    {
        $this->__load();
        return parent::getCreatedTime();
    }

    public function setCreatedTime()
    {
        $this->__load();
        return parent::setCreatedTime();
    }

    public function getModifiedTime()
    {
        $this->__load();
        return parent::getModifiedTime();
    }

    public function setModifiedTime()
    {
        $this->__load();
        return parent::setModifiedTime();
    }

    public function setLeftValue($left)
    {
        $this->__load();
        return parent::setLeftValue($left);
    }

    public function setRightValue($right)
    {
        $this->__load();
        return parent::setRightValue($right);
    }

    public function setLevel($level)
    {
        $this->__load();
        return parent::setLevel($level);
    }

    public function moveLeftValue($diff)
    {
        $this->__load();
        return parent::moveLeftValue($diff);
    }

    public function moveRightValue($diff)
    {
        $this->__load();
        return parent::moveRightValue($diff);
    }

    public function moveLevel($diff)
    {
        $this->__load();
        return parent::moveLevel($diff);
    }

    public function __call($method, $arguments)
    {
        $this->__load();
        return parent::__call($method, $arguments);
    }

    public function removeTrigger()
    {
        $this->__load();
        return parent::removeTrigger();
    }

    public function free()
    {
        $this->__load();
        return parent::free();
    }

    public function getNestedSetRepositoryClassName()
    {
        $this->__load();
        return parent::getNestedSetRepositoryClassName();
    }

    public function setFileName($fileName)
    {
        $this->__load();
        return parent::setFileName($fileName);
    }

    public function getFileName()
    {
        $this->__load();
        return parent::getFileName();
    }

    public function __toString()
    {
        $this->__load();
        return parent::__toString();
    }

    public function isPublic()
    {
        $this->__load();
        return parent::isPublic();
    }

    public function setPublic($public)
    {
        $this->__load();
        return parent::setPublic($public);
    }

    public function setNestedSetNode(\Supra\NestedSet\Node\DoctrineNode $nestedSetNode)
    {
        $this->__load();
        return parent::setNestedSetNode($nestedSetNode);
    }

    public function getInfo($locale)
    {
        $this->__load();
        return parent::getInfo($locale);
    }

    public function authorize(\Supra\User\Entity\Abstraction\User $user, $permissionType)
    {
        $this->__load();
        return parent::authorize($user, $permissionType);
    }

    public function getPermissionTypes()
    {
        $this->__load();
        return parent::getPermissionTypes();
    }

    public function getAuthorizationId()
    {
        $this->__load();
        return parent::getAuthorizationId();
    }

    public function getAuthorizationClass()
    {
        $this->__load();
        return parent::getAuthorizationClass();
    }

    public function getAuthorizationAncestors($includeSelf = true)
    {
        $this->__load();
        return parent::getAuthorizationAncestors($includeSelf);
    }

    public function equals(\Supra\Database\Entity $entity)
    {
        $this->__load();
        return parent::equals($entity);
    }


    public function __sleep()
    {
        return array('__isInitialized__', 'id', 'left', 'right', 'level', 'fileName', 'createdTime', 'modifiedTime', 'public');
    }

    public function __clone()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;
            $class = $this->_entityPersister->getClassMetadata();
            $original = $this->_entityPersister->load($this->_identifier);
            if ($original === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            foreach ($class->reflFields AS $field => $reflProperty) {
                $reflProperty->setValue($this, $reflProperty->getValue($original));
            }
            unset($this->_entityPersister, $this->_identifier);
        }
        
    }
}