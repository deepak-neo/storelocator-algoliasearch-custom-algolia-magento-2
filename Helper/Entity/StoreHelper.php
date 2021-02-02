<?php

/**
 * Copyright © 2021 Neosoft Technologies. All rights reserved.
 */
namespace Neosoft\StoreLocator\Helper\Entity;

use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Neosoft\StoreLocator\Model\StoreLocator;
use Neosoft\StoreLocator\Model\ResourceModel\StoreLocator\CollectionFactory as StoreLocatorCollectionFactory;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\UrlFactory;
use Magento\Store\Model\StoreManagerInterface;

class StoreHelper
{
    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var StoreLocatorCollectionFactory
     */
    private $storeLocatorCollectionFactory;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var FilterProvider
     */
    private $filterProvider;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var UrlFactory
     */
    private $frontendUrlFactory;

    /**
     * StoreHelper constructor.
     *
     * @param ManagerInterface      $eventManager
     * @param StoreLocatorCollectionFactory $storeLocatorCollectionFactory
     * @param ConfigHelper          $configHelper
     * @param FilterProvider        $filterProvider
     * @param StoreManagerInterface $storeManager
     * @param UrlFactory          $frontendUrlFactory
     */
    public function __construct(
        ManagerInterface $eventManager,
        StoreLocatorCollectionFactory $storeLocatorCollectionFactory,
        ConfigHelper $configHelper,
        FilterProvider $filterProvider,
        StoreManagerInterface $storeManager,
        UrlFactory $frontendUrlFactory
    ) {
        $this->eventManager = $eventManager;
        $this->storeLocatorCollectionFactory = $storeLocatorCollectionFactory;
        $this->configHelper = $configHelper;
        $this->filterProvider = $filterProvider;
        $this->storeManager = $storeManager;
        $this->frontendUrlFactory = $frontendUrlFactory;
    }

    public function getIndexNameSuffix()
    {
        return '_store_locators';
    }

    public function getIndexSettings($storeId)
    {
        $indexSettings = [
            'searchableAttributes' => ['unordered(name)', 'unordered(description)', 'unordered(street)', 'unordered(city)', 'unordered(state)', 'unordered(pincode)', 'unordered(country)'],
            'attributesToSnippet'  => ['description:7'],
        ];

        $transport = new DataObject($indexSettings);
        $this->eventManager->dispatch(
            'algolia_store_locators_index_before_set_settings',
            ['store_id' => $storeId, 'index_settings' => $transport]
        );
        $indexSettings = $transport->getData();

        return $indexSettings;
    }

    public function getStoreLocators($storeId, array $storeLocatorIds = null)
    {
        /** @var \Neosoft\StoreLocator\Model\ResourceModel\StoreLocator\Collection $magentoStoreLocators */
        $magentoStoreLocators = $this->storeLocatorCollectionFactory->create()
            ->addFieldToFilter('store_id', $storeId)
            ->addFieldToFilter('is_active', 1);

        if ($storeLocatorIds && count($storeLocatorIds)) {
            $magentoStoreLocators->addFieldToFilter('id', ['in' => $storeLocatorIds]);
        }

//        $excludedStoreLocators = $this->getExcludedStoreLocatorIds();
//        if (count($excludedStoreLocators)) {
//            $magentoStoreLocators->addFieldToFilter('identifier', ['nin' => $excludedStoreLocators]);
//        }

        $storeLocatorIdsToRemove = $storeLocatorIds ? array_flip($storeLocatorIds) : [];

        $storeLocators = [];

//        $frontendUrlBuilder = $this->frontendUrlFactory->create()->setScope($storeId);

        /** @var Store $storeLocator */
        foreach ($magentoStoreLocators as $storeLocator) {
            $storeLocatorObject = [];

            $storeLocatorObject['name'] = $storeLocator->getTitle();

            $storeLocator->setData('store_id', $storeId);

            if (!$storeLocator->getId()) {
                continue;
            }

            $description = $storeLocator->getDescription();
            if ($this->configHelper->getRenderTemplateDirectives()) {
                $description = $this->filterProvider->getPageFilter()->filter($description);
            }

            $storeLocatorObject['objectID'] = $storeLocator->getId();
            $storeLocatorObject['url'] = "storelocator/storelocator" . $storeLocator->getId();
//           $frontendUrlBuilder->getUrl(
//                null,
//                [
//                    '_direct' => $storeLocator->getIdentifier(),
//                    '_secure' => $this->configHelper->useSecureUrlsInFrontend($storeId),
//                ]
//            );
            $storeLocatorObject['description'] = $this->strip($description, ['script', 'style']);
            $storeLocatorObject['store_id'] = $storeLocator->getStoreId();
            $storeLocatorObject['street'] = $storeLocator->getStreet();
            $storeLocatorObject['city'] = $storeLocator->getCity();
            $storeLocatorObject['state'] = $storeLocator->getState();
            $storeLocatorObject['pincode'] = $storeLocator->getPincode();
            $storeLocatorObject['country'] = $storeLocator->getCountry();

            $transport = new DataObject($storeLocatorObject);
            $this->eventManager->dispatch(
                'algolia_after_create_store_locators_object',
                ['store_locator' => $transport, 'storeLocatorObject' => $storeLocator]
            );
            $storeLocatorObject = $transport->getData();

            if (isset($storeLocatorIdsToRemove[$storeLocator->getId()])) {
                unset($storeLocatorIdsToRemove[$storeLocator->getId()]);
            }
            $storeLocators['toIndex'][] = $storeLocatorObject;
        }

        $storeLocators['toRemove'] = array_unique(array_keys($storeLocatorIdsToRemove));
        return $storeLocators;
    }

    public function getExcludedStoreLocatorIds()
    {
        $excludedStoreLocators = array_values($this->configHelper->getExcludedStoreLocators());
        foreach ($excludedStoreLocators as &$excludedStoreLocator) {
            $excludedStoreLocator = $excludedStoreLocator['attribute'];
        }
        return $excludedStoreLocators;
    }

    public function getStores($storeId = null)
    {
        $storeIds = [];

        if ($storeId === null) {
            /** @var \Magento\Store\Model\Store $store */
            foreach ($this->storeManager->getStores() as $store) {
                if ($this->configHelper->isEnabledBackEnd($store->getId()) === false) {
                    continue;
                }

                if ($store->getData('is_active')) {
                    $storeIds[] = $store->getId();
                }
            }
        } else {
            $storeIds = [$storeId];
        }

        return $storeIds;
    }

    private function strip($s, $completeRemoveTags = [])
    {
        if ($completeRemoveTags && $completeRemoveTags !== [] && $s) {
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML(mb_convert_encoding($s, 'HTML-ENTITIES', 'UTF-8'));
            libxml_use_internal_errors(false);

            $toRemove = [];
            foreach ($completeRemoveTags as $tag) {
                $removeTags = $dom->getElementsByTagName($tag);

                foreach ($removeTags as $item) {
                    $toRemove[] = $item;
                }
            }

            foreach ($toRemove as $item) {
                $item->parentNode->removeChild($item);
            }

            $s = $dom->saveHTML();
        }

        $s = html_entity_decode($s, null, 'UTF-8');

        $s = trim(preg_replace('/\s+/', ' ', $s));
        $s = preg_replace('/&nbsp;/', ' ', $s);
        $s = preg_replace('!\s+!', ' ', $s);
        $s = preg_replace('/\{\{[^}]+\}\}/', ' ', $s);
        $s = strip_tags($s);
        $s = trim($s);

        return $s;
    }
}
