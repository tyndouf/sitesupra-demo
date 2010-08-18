<?php

namespace Supra\Proxy;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ORM. DO NOT EDIT THIS FILE.
 */
class SupraControllerPagesEntityPageProxy extends \Supra\Controller\Pages\Entity\Page implements \Doctrine\ORM\Proxy\Proxy
{
    private $_entityPersister;
    private $_identifier;
    public $__isInitialized__ = false;
    public function __construct($entityPersister, $identifier)
    {
        $this->_entityPersister = $entityPersister;
        $this->_identifier = $identifier;
    }
    private function _load()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;
            if ($this->_entityPersister->load($this->_identifier, $this) === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            unset($this->_entityPersister, $this->_identifier);
        }
    }

    
    public function setTemplate(\Supra\Controller\Pages\Entity\Template $template)
    {
        $this->_load();
        return parent::setTemplate($template);
    }

    public function getTemplate()
    {
        $this->_load();
        return parent::getTemplate();
    }

    public function setParent(\Supra\Controller\Pages\Entity\Abstraction\Page $page = NULL)
    {
        $this->_load();
        return parent::setParent($page);
    }

    public function getTemplates()
    {
        $this->_load();
        return parent::getTemplates();
    }

    public function getLeftValue()
    {
        $this->_load();
        return parent::getLeftValue();
    }

    public function getRightValue()
    {
        $this->_load();
        return parent::getRightValue();
    }

    public function getLevel()
    {
        $this->_load();
        return parent::getLevel();
    }

    public function setLeftValue($left)
    {
        $this->_load();
        return parent::setLeftValue($left);
    }

    public function setRightValue($right)
    {
        $this->_load();
        return parent::setRightValue($right);
    }

    public function setLevel($level)
    {
        $this->_load();
        return parent::setLevel($level);
    }

    public function moveLeftValue($diff)
    {
        $this->_load();
        return parent::moveLeftValue($diff);
    }

    public function moveRightValue($diff)
    {
        $this->_load();
        return parent::moveRightValue($diff);
    }

    public function moveLevel($diff)
    {
        $this->_load();
        return parent::moveLevel($diff);
    }

    public function createNestedSetNode()
    {
        $this->_load();
        return parent::createNestedSetNode();
    }

    public function __call($method, $arguments)
    {
        $this->_load();
        return parent::__call($method, $arguments);
    }

    public function free()
    {
        $this->_load();
        return parent::free();
    }

    public function getId()
    {
        $this->_load();
        return parent::getId();
    }

    public function getParent()
    {
        $this->_load();
        return parent::getParent();
    }

    public function getChildren()
    {
        $this->_load();
        return parent::getChildren();
    }

    public function getPlaceHolders()
    {
        $this->_load();
        return parent::getPlaceHolders();
    }

    public function getDataCollection()
    {
        $this->_load();
        return parent::getDataCollection();
    }

    public function getData($locale)
    {
        $this->_load();
        return parent::getData($locale);
    }

    public function setData(\Supra\Controller\Pages\Entity\Abstraction\Data $data)
    {
        $this->_load();
        return parent::setData($data);
    }

    public function removeData($locale)
    {
        $this->_load();
        return parent::removeData($locale);
    }

    public function addPlaceHolder(\Supra\Controller\Pages\Entity\Abstraction\PlaceHolder $placeHolder)
    {
        $this->_load();
        return parent::addPlaceHolder($placeHolder);
    }

    public function getRepository()
    {
        $this->_load();
        return parent::getRepository();
    }

    public function getProperty($name)
    {
        $this->_load();
        return parent::getProperty($name);
    }

    public function getDiscriminator()
    {
        $this->_load();
        return parent::getDiscriminator();
    }

    public function matchDiscriminator(\Supra\Controller\Pages\Entity\Abstraction\Entity $object, $strict = true)
    {
        $this->_load();
        return parent::matchDiscriminator($object, $strict);
    }

    public function __toString()
    {
        $this->_load();
        return parent::__toString();
    }


    public function __sleep()
    {
        return array('__isInitialized__', 'id', 'depth', 'left', 'right', 'level', 'data', 'template', 'children', 'parent', 'placeHolders');
    }
}