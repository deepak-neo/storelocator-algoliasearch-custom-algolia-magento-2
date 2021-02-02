<?php

/**
 * Copyright © 2021 Neosoft Technologies. All rights reserved.
 */
namespace Neosoft\StoreLocator\Model\Source;

class JobMethods extends \Algolia\AlgoliaSearch\Model\Source\JobMethods
{
    private $methods = [
        'saveConfigurationToAlgolia' => 'Save Configuration',
        'moveIndexWithSetSettings' => 'Move Index',
        'deleteObjects' => 'Object deletion',
        'rebuildStoreCategoryIndex' => 'Category Reindex',
        'rebuildStoreProductIndex' => 'Product Reindex',
        'rebuildProductIndex' => 'Product Reindex',
        'rebuildStoreAdditionalSectionsIndex' => 'Additional Section Reindex',
        'rebuildStoreSuggestionIndex' => 'Suggestion Reindex',
        'rebuildStorePageIndex' => 'Page Reindex',
        'rebuildStoreLocatorIndex' => 'Store Locator Reindex',
    ];
    
    /** @return array */
    public function toOptionArray()
    {
        $options = [];

        foreach ($this->methods as $key => $value) {
            $options[] = [
                'value' => $key,
                'label' => __($value),
            ];
        }

        return $options;
    }
}
