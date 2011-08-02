<?php

namespace Supra\Controller\Pages\Set;

use Supra\Controller\Pages\Exception;
use Supra\Controller\Pages\Entity\Template;
use Supra\Controller\Pages\Entity\Abstraction\Page;

/**
 * Set containing 
 */
class PageSet extends AbstractSet
{
	/**
	 * The root template is the first element in the set
	 * @return Template
	 */
	public function getRootTemplate()
	{
		return $this->getFirstElement();
	}
	
	/**
	 * @return Page
	 */
	public function getFinalPage()
	{
		return $this->getLastElement();
	}
}
