<?php

/**
 * Copyright © 2021 Neosoft Technologies. All rights reserved.
 */
namespace Neosoft\StoreLocator\Controller\Adminhtml\StoreLocator;

class Save extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \MonotaroIndonesia\ShopbyStore\Model\StoreLocatorFactory
     */
    protected $storeLocatorFactory;

    /**
     * Save constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Neosoft\StoreLocator\Model\StoreLocatorFactory $storeLocatorFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Neosoft\StoreLocator\Model\StoreLocatorFactory $storeLocatorFactory
    ) {
        $this->storeLocatorFactory = $storeLocatorFactory;
        parent::__construct($context);
    }

    /**
     * Execute Method
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            try {
                $storeLocator = $this->storeLocatorFactory->create();
                if ($data['id']) {
                    $storeLocator->load($data['id']);
                    $message = __('Store Locator updated successfully.');
                } else {
                    unset($data['id']);
                    $message = __('Store Locator saved successfully.');
                }
                $storeLocator->setData($data);
                $storeLocator->save();
                $this->messageManager->addSuccessMessage($message);
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $storeLocator->getId()]);
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
            return $resultRedirect->setPath('*/*/');
        }
    }
}
