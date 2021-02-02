<?php

/**
 * Copyright © 2021 Neosoft Technologies. All rights reserved.
 */
namespace Neosoft\StoreLocator\Helper;

use Magento\Store\Model\ScopeInterface;
use Algolia\AlgoliaSearch\Exception\CategoryReindexingException;
use Algolia\AlgoliaSearch\Exception\ProductReindexingException;
use Algolia\AlgoliaSearch\Helper\Entity\AdditionalSectionHelper;
use Algolia\AlgoliaSearch\Helper\Entity\CategoryHelper;
use Algolia\AlgoliaSearch\Helper\Entity\PageHelper;
use Algolia\AlgoliaSearch\Helper\Entity\ProductHelper;
use Algolia\AlgoliaSearch\Helper\Entity\SuggestionHelper;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeCodeResolver;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface;
use Magento\Search\Model\Query;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Neosoft\StoreLocator\Helper\Entity\StoreHelper;
use Algolia\AlgoliaSearch\Helper\AlgoliaHelper;
use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Algolia\AlgoliaSearch\Helper\Logger;

class Data extends \Algolia\AlgoliaSearch\Helper\Data
{
    const XML_PATH_ENABLED = 'storelocator/general/enable';
    const XML_SECTION_TITLE = 'storelocator/general/section_title';
    const XML_GMAP_API_KEY = 'storelocator/google_map/api_key';
    const XML_GMAP_STANDARD_LATITUDE = 'storelocator/google_map/std_latitude';
    const XML_GMAP_STANDARD_LONGITUDE = 'storelocator/google_map/std_longitude';
    const XML_GMAP_ZOOM = 'storelocator/google_map/map_zoom';
    const XML_FOOTERLINK_ENABLE = 'storelocator/manage_links/footer_link_enable';
    const XML_FOOTERLINK_TEXT = 'storelocator/manage_links/label_footer';

    private $algoliaHelper;
    private $pageHelper;
    private $categoryHelper;
    private $productHelper;
    private $suggestionHelper;
    private $additionalSectionHelper;
    private $stockRegistry;
    private $logger;
    private $configHelper;
    private $emulation;
    private $resource;
    private $eventManager;
    private $scopeCodeResolver;
    private $storeManager;
    private $storeHelper;
    private $emulationRuns = false;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        AlgoliaHelper $algoliaHelper,
        ConfigHelper $configHelper,
        ProductHelper $producthelper,
        CategoryHelper $categoryHelper,
        PageHelper $pageHelper,
        SuggestionHelper $suggestionHelper,
        AdditionalSectionHelper $additionalSectionHelper,
        StockRegistryInterface $stockRegistry,
        Emulation $emulation,
        Logger $logger,
        ResourceConnection $resource,
        ManagerInterface $eventManager,
        ScopeCodeResolver $scopeCodeResolver,
        StoreManagerInterface $storeManager,
        StoreHelper $storeHelper
    ) {
        $this->algoliaHelper = $algoliaHelper;
        $this->pageHelper = $pageHelper;
        $this->categoryHelper = $categoryHelper;
        $this->productHelper = $producthelper;
        $this->suggestionHelper = $suggestionHelper;
        $this->additionalSectionHelper = $additionalSectionHelper;
        $this->stockRegistry = $stockRegistry;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
        $this->emulation = $emulation;
        $this->resource = $resource;
        $this->eventManager = $eventManager;
        $this->scopeCodeResolver = $scopeCodeResolver;
        $this->storeManager = $storeManager;
        $this->storeHelper = $storeHelper;
        $this->scopeConfig = $context->getScopeConfig();
        parent::__construct($algoliaHelper, $configHelper, $producthelper, $categoryHelper, $pageHelper, $suggestionHelper, $additionalSectionHelper, $stockRegistry, $emulation, $logger, $resource, $eventManager, $scopeCodeResolver, $storeManager);
    }
    
    public function isModuleEnable()
    {
        $isEnabled = true;
        $enabled = $this->scopeConfig->getValue(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
        if ($enabled == null || $enabled == '0') {
            $isEnabled = false;
        }
        return $isEnabled;
    }

    public function getSectionTitle()
    {
        return $this->scopeConfig->getValue(self::XML_SECTION_TITLE, ScopeInterface::SCOPE_STORE);
    }
    
    public function getGMapAPIKey()
    {
        return $this->scopeConfig->getValue(self::XML_GMAP_API_KEY, ScopeInterface::SCOPE_STORE);
    }
    
    public function getGMapStandardLatitude()
    {
        return $this->scopeConfig->getValue(self::XML_GMAP_STANDARD_LATITUDE, ScopeInterface::SCOPE_STORE);
    }
    
    public function getGMapStandardLongitude()
    {
        return $this->scopeConfig->getValue(self::XML_GMAP_STANDARD_LONGITUDE, ScopeInterface::SCOPE_STORE);
    }
    
    public function getGMapZoom()
    {
        if (self::XML_GMAP_ZOOM =='') {
            return 8;
        }
        return $this->scopeConfig->getValue(self::XML_GMAP_ZOOM, ScopeInterface::SCOPE_STORE);
    }

    public function isFooterLinkEnable()
    {
        return $this->scopeConfig->getValue(self::XML_FOOTERLINK_ENABLE, ScopeInterface::SCOPE_STORE);
    }
    
    public function getFooterLinkLabel()
    {
        return $this->scopeConfig->getValue(self::XML_FOOTERLINK_TEXT, ScopeInterface::SCOPE_STORE);
    }
    
    public function getFooterLink()
    {
        return "#";
    }
    
    public function rebuildStoreLocatorIndex($storeId, array $storeLocatorIds = null)
    {
        if ($this->isIndexingEnabled($storeId) === false) {
            return;
        }

        $indexName = $this->getIndexName($this->storeHelper->getIndexNameSuffix(), $storeId);

        $this->startEmulation($storeId);

        $storeLocators = $this->storeHelper->getStoreLocators($storeId, $storeLocatorIds);

        $this->stopEmulation();

        // if there are pageIds defined, do not index to _tmp
        $isFullReindex = (!$storeLocatorIds);

        if (isset($storeLocators['toIndex']) && count($storeLocators['toIndex'])) {
            $storeLocatorsToIndex = $storeLocators['toIndex'];
            $toIndexName = $indexName . ($isFullReindex ? '_tmp' : '');

            foreach (array_chunk($storeLocatorsToIndex, 100) as $chunk) {
                try {
                    $this->algoliaHelper->addObjects($chunk, $toIndexName);
                } catch (\Exception $e) {
                    $this->logger->log($e->getMessage());
                    continue;
                }
            }
        }

        if (!$isFullReindex && isset($storeLocators['toRemove']) && count($storeLocators['toRemove'])) {
            $storeLocatorsToRemove = $storeLocators['toRemove'];

            foreach (array_chunk($storeLocatorsToRemove, 100) as $chunk) {
                try {
                    $this->algoliaHelper->deleteObjects($chunk, $indexName);
                } catch (\Exception $e) {
                    $this->logger->log($e->getMessage());
                    continue;
                }
            }
        }

        if ($isFullReindex) {
            $this->algoliaHelper->copyQueryRules($indexName, $indexName . '_tmp');
            $this->algoliaHelper->moveIndex($indexName . '_tmp', $indexName);
        }

        $this->algoliaHelper->setSettings($indexName, $this->storeHelper->getIndexSettings($storeId));
    }
}
