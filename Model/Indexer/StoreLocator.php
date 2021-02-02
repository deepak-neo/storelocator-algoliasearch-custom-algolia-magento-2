<?php

/**
 * Copyright © 2021 Neosoft Technologies. All rights reserved.
 */
namespace Neosoft\StoreLocator\Model\Indexer;

use Algolia\AlgoliaSearch\Helper\AlgoliaHelper;
use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Algolia\AlgoliaSearch\Helper\Data;
use Neosoft\StoreLocator\Helper\Entity\StoreHelper;
use Algolia\AlgoliaSearch\Model\Queue;
use Magento;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class StoreLocator implements Magento\Framework\Indexer\ActionInterface, Magento\Framework\Mview\ActionInterface
{
    private $fullAction;
    private $storeManager;
    private $storeHelper;
    private $algoliaHelper;
    private $queue;
    private $configHelper;
    private $messageManager;
    private $output;

    public function __construct(
        StoreManagerInterface $storeManager,
        StoreHelper $storeHelper,
        Data $helper,
        AlgoliaHelper $algoliaHelper,
        Queue $queue,
        ConfigHelper $configHelper,
        ManagerInterface $messageManager,
        ConsoleOutput $output
    ) {
        $this->fullAction = $helper;
        $this->storeManager = $storeManager;
        $this->storeHelper = $storeHelper;
        $this->algoliaHelper = $algoliaHelper;
        $this->queue = $queue;
        $this->configHelper = $configHelper;
        $this->messageManager = $messageManager;
        $this->output = $output;
    }

    public function execute($ids)
    {
        if (!$this->configHelper->getApplicationID()
            || !$this->configHelper->getAPIKey()
            || !$this->configHelper->getSearchOnlyAPIKey()) {
            $errorMessage = 'Algolia reindexing failed: 
                You need to configure your Algolia credentials in Stores > Configuration > Algolia Search.';

            if (php_sapi_name() === 'cli') {
                $this->output->writeln($errorMessage);

                return;
            }

            $this->messageManager->addErrorMessage($errorMessage);

            return;
        }

        $storeIds = $this->storeHelper->getStores();

        foreach ($storeIds as $storeId) {
            if ($this->fullAction->isIndexingEnabled($storeId) === false) {
                continue;
            }

//            if ($this->isStoreLocatorsInAdditionalSections($storeId)) {
                $data = ['store_id' => $storeId];
                if (is_array($ids) && count($ids) > 0) {
                    $data['store_locator_ids'] = $ids;
                }

                $this->queue->addToQueue(
                    $this->fullAction,
                    'rebuildStoreLocatorIndex',
                    $data,
                    is_array($ids) ? count($ids) : 1
                );
//            }
        }
    }

    public function executeFull()
    {
        $this->execute(null);
    }

    public function executeList(array $ids)
    {
        $this->execute($ids);
    }

    public function executeRow($id)
    {
        $this->execute([$id]);
    }

    private function isStoreLocatorsInAdditionalSections($storeId)
    {
        $sections = $this->configHelper->getAutocompleteSections($storeId);
        foreach ($sections as $section) {
            if ($section['name'] === 'store_locators') {
                return true;
            }
        }

        return false;
    }
}
