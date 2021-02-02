<?php

/**
 * Copyright © 2021 Neosoft Technologies. All rights reserved.
 */
namespace Neosoft\StoreLocator\Model\ResourceModel;

class StoreLocator extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize Resource Model
     */
    protected function _construct()
    {
        $this->_init('store_locator', 'id');
    }
}
