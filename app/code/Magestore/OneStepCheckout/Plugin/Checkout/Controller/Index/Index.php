<?php
/**
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */

namespace Magestore\OneStepCheckout\Plugin\Checkout\Controller\Index;

/**
 * Class Index
 * @package Magestore\OneStepCheckout\Plugin\Checkout\Controller\Index
 */
class Index extends \Magento\Checkout\Controller\Index\Index
{

    /**
     * @param \Magento\Checkout\Controller\Index\Index $subject
     * @param \Closure $proceed
     * @return $this|\Magento\Framework\View\Result\Page
     */
    public function aroundExecute(\Magento\Checkout\Controller\Index\Index $subject, \Closure $proceed)
    {
        if ($this->_objectManager->get('Magestore\OneStepCheckout\Helper\Config')->isEnabledOneStep()) {
            /** @var \Magento\Checkout\Helper\Data $checkoutHelper */
            $checkoutHelper = $this->_objectManager->get('Magento\Checkout\Helper\Data');
            if (!$checkoutHelper->canOnepageCheckout()) {
                $this->messageManager->addErrorMessage(__('One-page checkout is turned off.'));
                return $this->resultRedirectFactory->create()->setPath('checkout/cart');
            }

            $quote = $this->getOnepage()->getQuote();
            if (!$quote->hasItems() || $quote->getHasError() || !$quote->validateMinimumAmount()) {
                return $this->resultRedirectFactory->create()->setPath('checkout/cart');
            }

            if (!$this->_customerSession->isLoggedIn() && !$checkoutHelper->isAllowedGuestCheckout($quote)) {
                $this->messageManager->addErrorMessage(__('Guest checkout is disabled.'));
                return $this->resultRedirectFactory->create()->setPath('checkout/cart');
            }

            $this->_customerSession->regenerateId();
            $this->_objectManager->get('Magento\Checkout\Model\Session')->setCartWasUpdated(false);
            $this->getOnepage()->initCheckout();
            $resultPage = $this->resultPageFactory->create();
            $resultPage->getLayout()->getUpdate()->addHandle('onestepcheckout_layout');
            $resultPage->getConfig()->getTitle()->set(__('Checkout'));
            return $resultPage;
        } else {
            $result = $proceed();
            return $result;
        }
    }
}