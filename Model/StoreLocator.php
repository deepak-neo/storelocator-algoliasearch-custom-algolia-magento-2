<?php

/**
 * Copyright © 2021 Neosoft Technologies. All rights reserved.
 */
namespace Neosoft\StoreLocator\Model;

class StoreLocator extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Store Locator Initialization
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Neosoft\StoreLocator\Model\ResourceModel\StoreLocator::class);
    }
}
