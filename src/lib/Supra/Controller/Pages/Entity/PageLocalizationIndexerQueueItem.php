<?php

namespace Supra\Controller\Pages\Entity;

use Supra\Search\Entity\Abstraction\IndexerQueueItem;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Controller\Pages\Entity\Abstraction\Localization;
use Supra\Search\IndexerService;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Search\IndexedDocument;
use Supra\Controller\Pages\Request\PageRequestView;
use Supra\Controller\Pages\Entity\TemplateBlock;
use Supra\Controller\Pages\Markup;
use Supra\Controller\Pages\Entity\ReferencedElement\ImageReferencedElement;
use Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement;
use Supra\Controller\Pages\PageController;
use Supra\Search\Exception\IndexerRuntimeException;
use Supra\Controller\Pages\Search\PageLocalizationFindRequest;
use Supra\Search\SearchService;
use Supra\Controller\Pages\Entity\BlockPropertyMetadata;

/**
 * @Entity
 */
class PageLocalizationIndexerQueueItem extends IndexerQueueItem
{
	const DISCRIMITATOR_VALUE = 'pageLocalization';

	/**
	 * @Column(type="supraId20")
	 * @var string
	 */
	protected $pageLocalizationId;

	/**
	 * @Column(type="string") 
	 * @var string
	 */
	protected $revisionId;

	/**
	 * @Column(type="string") 
	 * @var string
	 */
	protected $schemaName;
	static $indexedLocalizationIds = array();

	public function __construct(PageLocalization $pageLocalization)
	{
		parent::__construct();

		$this->pageLocalizationId = $pageLocalization->getId();
		$this->revisionId = $pageLocalization->getRevisionId();
		$this->schemaName = PageController::SCHEMA_DRAFT;
	}

	protected $parentLocalization;
	protected $parentDocument;
	protected $localization;
	protected $previousDocument;
	protected $previousParentId;
	protected $isVisible;
	protected $reindexChildren;

	/**
	 * @param string $schemaName
	 * @param string $pageLocalizationId
	 * @param string $revisionId
	 * @return string
	 */
	static function getUniqueId($schemaName, $pageLocalizationId, $revisionId = null)
	{
		$id = null;

		if ($schemaName == PageController::SCHEMA_PUBLIC) {
			$id = implode('-', array($pageLocalizationId, $schemaName));
		} else {
			$id = implode('-', array($pageLocalizationId, $schemaName, $revisionId));
		}

		return $id;
	}

	/**
	 * Sets schema name to be used for this queue item.
	 * @param string $schemaName 
	 */
	public function setSchemaName($schemaName)
	{
		if ( ! in_array($schemaName, PageController::$knownSchemaNames)) {
			throw new IndexerRuntimeException('Unknown schema name "' . $schemaName . '". Use constants from PageController.');
		}

		$this->schemaName = $schemaName;
	}

	/**
	 * @return array of IndexedDocument
	 */
	public function getIndexedDocuments()
	{
		$result = array();

		if (in_array($this->pageLocalizationId, self::$indexedLocalizationIds)) {

			\Log::debug('LLL hit cache BIGTIME!!! ', $this->pageLocalizationId);
			return array();
		}

		$em = ObjectRepository::getEntityManager($this->schemaName);
		$pr = $em->getRepository(PageLocalization::CN());

		/* @var $pageLocalization PageLocalization */
		$this->localization = $pr->find($this->pageLocalizationId);

		if (empty($this->localization)) {
			return $result;
		}

		$this->previousDocument = $this->findPageLocalizationIndexedDocument($this->pageLocalizationId);

		if ( ! empty($this->previousDocument)) {

			$ancestorIds = $this->previousDocument->ancestorIds;
			$this->previousParentId = array_shift($ancestorIds);
		}

		$l = $this->localization->getParent();

		while ( ! ($l instanceof PageLocalization || empty($l))) {
			$l = $l->getParent();
		}

		$this->parentLocalization = $l;

		if ( ! empty($this->parentLocalization)) {

			$this->parentDocument = $this->findPageLocalizationIndexedDocument($this->parentLocalization->getId());
		}

		if ( ! empty($this->parentLocalization) && ! empty($this->previousDocument)) {

			$this->hasParentHasPrevious();
		} else if ( ! empty($this->parentLocalization) && empty($this->previousDocument)) {

			$this->hasParentNoPrevious();
		} else if (empty($this->parentLocalization) && ! empty($this->previousDocument)) {

			$this->noParentHasPrevious();
		} else if (empty($this->parentLocalization) && empty($this->previousDocument)) {

			$this->noParentNoPrevious();
		}

		$result[] = $this->makeIndexedDocument($this->localization, $this->isVisible);

		if ($this->reindexChildren) {
			$result = $result + $this->reindexChildren($this->localization, $this->isVisible);
		}

		return $result;
	}

	protected function hasParentHasPrevious()
	{
		// Page has been moved?
		if ($this->parentLocalization->getId() != $this->previousParentId) {
			// - Yes. 
			// Set "visibility" to that of parent document.
			$this->isVisible = $this->parentDocument->visible;

			// If "visibility" has changed since last indexing, child pages have to be reindexed as well.
			if ($this->previousDocument->visible != $this->isVisible) {
				$this->reindexChildren = true;
			} else {
				$this->reindexChildren = false;
			}
		} else {
			// - No.
			// If "activity" has been turned OFF ...
			if ($this->localization->isActive() == false && $this->previousDocument->active == true) {

				// This localiaztion is not active and not visible, same goes for its children.
				$this->isVisible = false;
				$this->reindexChildren = true;
			}
			// ... or if "activity" has been turned ON ...
			else if ($this->localization->isActive() == true && $this->previousDocument->active == false) {

				// This localization is active and visible, same goes for its children.
				if ( ! empty($this->parentDocument)) {
					$this->isVisible = $this->parentDocument->visible;
				} else {
					$this->isVisible = $this->parentLocalization->isActive();
				}

				$this->reindexChildren = true;
			} else {

				// Nothing of substance has changed since last indexing.
				$this->isVisible = $this->previousDocument->visible;
				$this->reindexChildren = false;
			}
		}
	}

	protected function hasParentNoPrevious()
	{
		if ($this->localization->isActive()) {

			// If localization is "active", "visibility" is inherited from its parent, ...
			if (empty($this->parentDocument)) {
				$this->isVisible = $this->parentLocalization->isActive();
			} else {
				$this->isVisible = $this->parentDocument->visible;
			}
		} else {

			// ... otherwise "visibility" is FALSE.
			$this->isVisible = false;
		}

		// ... and chidren will be reindexed just to be safe.
		$this->reindexChildren = true;
	}

	protected function noParentHasPrevious()
	{
		// This looks like extreme edge case.
		// We are indexing root page localization. Check if it has 
		// not been moved since last indexing ...
		if (empty($this->previousParentId)) {
			// - not moved.

			if ($this->previousDocument->active != $this->localization->isActive()) {

				// "Activity" has been changed and now we have to adjust "visiblilty" of this
				// localization as well as that of all children.
				$this->isVisible = $this->localization->isActive();
				$this->reindexChildren = true;
			} else {

				$this->isVisible = $this->parentDocument->visible;
				$this->reindexChildren = false;
			}
		} else {
			// If this localization has been made root localization, 
			// set "visibility" according to "activity", and reindex children.
			$this->isVisible = $this->localization->isActive();
			$this->reindexChildren = true;
		}
	}

	protected function noParentNoPrevious()
	{
		$this->isVisible = $this->localization->isActive();
		$this->reindexChildren = true;
	}

	protected function reindexChildren(Localization $pageLocalization, $isVisible)
	{
		$result = array();

		$children = $pageLocalization->getChildren();

		foreach ($children as $child) {

			if ( ! $child instanceof GroupLocalization) {

				if ( ! in_array($child->getId(), self::$indexedLocalizationIds)) {
					$result[] = $this->makeIndexedDocument($child, $isVisible);
				} else {
					\Log::debug('LLL hit cache!!! ', $child->getId());
				}
			}

			$result = $result + $this->reindexChildren($child, $isVisible);
		}

		return $result;
	}

	protected function findPageLocalizationIndexedDocument($pageLocalizationId)
	{
		$findRequest = new PageLocalizationFindRequest();

		$findRequest->setSchemaName($this->schemaName);
		$findRequest->setPageLocalizationId($pageLocalizationId);

		$searchService = new SearchService();

		$results = $searchService->processRequest($findRequest);

		foreach ($results as $result) {
			if ($result->pageLocalizationId == $pageLocalizationId) {
				return $result;
			}
		}

		return null;
	}

	/**
	 * @param PageLocalization $pageLocalization
	 * @return IndexedDocument 
	 */
	protected function makeIndexedDocument(PageLocalization $pageLocalization, $visible)
	{
		$lm = ObjectRepository::getLocaleManager($this);

		$locale = $lm->getLocale($pageLocalization->getLocale());

		$languageCode = $locale->getProperty('language');

		$id = self::getUniqueId($this->schemaName, $pageLocalization->getId(), $pageLocalization->getRevisionId());

		$class = PageLocalization::CN();

		$indexedDocument = new IndexedDocument($class, $id);

		$indexedDocument->schemaName = $this->schemaName;
		$indexedDocument->revisionId = $this->revisionId;

		$indexedDocument->pageId = $pageLocalization->getMaster()->getId();
		$indexedDocument->pageLocalizationId = $pageLocalization->getId();
		$indexedDocument->localeId = $locale->getId();

		$indexedDocument->title_general = $indexedDocument->formatText($pageLocalization->getTitle());
		$indexedDocument->__set('title_' . $languageCode, $indexedDocument->title_general);

		$indexedDocument->active = $pageLocalization->isActive() ? 'true' : 'false';

		$indexedDocument->keywords = $pageLocalization->getMetaKeywords();
		$indexedDocument->description = $pageLocalization->getMetaDescription();

		$indexedDocument->pageWebPath = $pageLocalization->getPath();

		$indexedDocument->visible = $visible ? 'true' : 'false';

		$ancestors = $pageLocalization->getAuthorizationAncestors();
		$ancestorIds = array();
		foreach ($ancestors as $ancestor) {
			/* @var $ancestor Page */
			if ($ancestor instanceof PageLocalization) {
				$ancestorIds[] = $ancestor->getId();
			}
		}

		$indexedDocument->ancestorIds = $ancestorIds;

		$dummyHttpRequest = new \Supra\Request\HttpRequest();

		$pageRequestView = new PageRequestView($dummyHttpRequest);
		$pageRequestView->setLocale($pageLocalization->getLocale());
		$pageRequestView->setPageLocalization($pageLocalization);

		$em = ObjectRepository::getEntityManager($pageLocalization);

		$pageRequestView->setDoctrineEntityManager($em);
		$blockPropertySet = $pageRequestView->getBlockPropertySet($em);

		$pageContents = array();

		foreach ($blockPropertySet as $blockProperty) {
			/* @var $blockProperty BlockProperty */

			if ( ! $blockProperty->getLocalization() instanceof TemplateLocalization) {

				$blockContents = $this->getIndexableContentFromBlockProperty($blockProperty);
				$pageContents[] = $indexedDocument->formatText($blockContents);
			}
		}

		$indexedDocument->text_general = join(' ', $pageContents);
		$indexedDocument->__set('text_' . $languageCode, $indexedDocument->text_general);

		$indexedDocument->active = $pageLocalization->isActive();

		\Log::debug('LLL makeIndexedDocument: ', $indexedDocument->pageLocalizationId, ' visible: ', $indexedDocument->visible);

		self::$indexedLocalizationIds[] = $pageLocalization->getId();

		return $indexedDocument;
	}

	public function getIndexableContentFromBlockProperty(BlockProperty $blockProperty)
	{
		$tokenizer = new Markup\DefaultTokenizer($blockProperty->getValue());

		$tokenizer->tokenize();

		$result = array();
		foreach ($tokenizer->getElements() as $element) {

			if ($element instanceof Markup\HtmlElement) {
				$result[] = $element->getContent();
			} else if ($element instanceof Markup\SupraMarkupImage) {

				$metadata = $blockProperty->getMetadata();

				/* @var $metadataItem BlockPropertyMetadata */
				$metadataItem = $metadata[$element->getId()];

				$image = $metadataItem->getReferencedElement();

				if ($image instanceof ImageReferencedElement) {
					$result[] = $image->getAlternativeText();
				}
			} else if ($element instanceof Markup\SupraMarkupLinkStart) {

				$metadata = $blockProperty->getMetadata();

				/* @var $metadataItem BlockPropertyMetadata */
				$metadataItem = $metadata[$element->getId()];

				if ( ! empty($metadataItem)) {

					$link = $metadataItem->getReferencedElement();

					if ($link instanceof LinkReferencedElement) {
						$result[] = $link->getTitle();
					}
				} else {
					\Log::debug('EMPTY REFERENCED LINK?');
				}
			}
		}

		return implode(' ', $result);
	}

}
