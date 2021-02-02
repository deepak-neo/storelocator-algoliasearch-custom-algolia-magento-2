<?php

/**
 * Copyright © 2021 Neosoft Technologies. All rights reserved.
 */
namespace Neosoft\StoreLocator\Model\Indexer;

use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Model\AbstractModel;

class StoreLocatorObserver
{
    private $indexer;

    public function __construct(IndexerRegistry $indexerRegistry)
    {
        $this->indexer = $indexerRegistry->get('algolia_store_locators');
    }

    public function beforeSave(
        \Neosoft\StoreLocator\Model\ResourceModel\StoreLocator $storeLocatorResource,
        AbstractModel $storeLocator
    ) {
        $storeLocatorResource->addCommitCallback(function () use ($storeLocator) {
            if (!$this->indexer->isScheduled()) {
                $this->indexer->reindexRow($storeLocator->getId());
            }
        });
        return [$storeLocator];
    }

    public function beforeDelete(
        \Neosoft\StoreLocator\Model\ResourceModel\StoreLocator $storeLocatorResource,
        AbstractModel $storeLocator
    ) {
        $storeLocatorResource->addCommitCallback(function () use ($storeLocator) {
            if (!$this->indexer->isScheduled()) {
                $this->indexer->reindexRow($storeLocator->getId());
            }
        });
        return [$storeLocator];
    }
}
