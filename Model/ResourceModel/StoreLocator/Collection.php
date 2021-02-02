<?php

/**
 * Copyright © 2021 Neosoft Technologies. All rights reserved.
 */
namespace Neosoft\StoreLocator\Model\ResourceModel\StoreLocator;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public $_idFieldName = 'id';

    public function _construct()
    {
        $this->_init(
            \Neosoft\StoreLocator\Model\StoreLocator::class,
            \Neosoft\StoreLocator\Model\ResourceModel\StoreLocator::class
        );
    }
}
