<?php

/**
 * Copyright © 2021 Neosoft Technologies. All rights reserved.
 */
namespace Neosoft\StoreLocator\Controller\Adminhtml\StoreLocator;

use Neosoft\StoreLocator\Model\StoreLocatorFactory;

class Delete extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var StoreLocatorFactory
     */
    protected $storeLocatorFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param StoreLocatorFactory $storeLocatorFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        StoreLocatorFactory $storeLocatorFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->storeLocatorFactory = $storeLocatorFactory;
        parent::__construct($context);
    }

    /**
     * Delete action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($this->getRequest()->getParam('id')) {
            $storeLocator = $this->storeLocatorFactory->create()->load($this->getRequest()->getParam('id'));
            try {
                $storeLocator->delete();
                $this->messageManager->addSuccessMessage(__('Store Locator has been deleted successfully'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Error while trying to delete Store Locator'));
            }
        } else {
            $this->messageManager->addErrorMessage(__("We can't find a Store Locator to delete."));
        }
        return $resultRedirect->setPath('*/*/');
    }
}
