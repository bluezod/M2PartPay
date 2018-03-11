<?php

/**
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */

namespace Magestore\OneStepCheckout\Controller\Index;
/**
 * Class SaveCustomCheckoutData
 * @package Magestore\OneStepCheckout\Controller\Index
 */
class SaveCustomCheckoutData extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Model\Sidebar
     */
    protected $_sidebar;


    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $_dataObjectFactory;
    /**
     * @var \Magento\Quote\Api\CartTotalRepositoryInterface
     */
    protected $_cartTotalRepositoryInterface;

    /**
     * @var \Magestore\OneStepCheckout\Helper\Data
     */
    protected $_oscHelper;


    /**
     * SaveCustomCheckoutData constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalRepositoryInterface
     * @param \Magento\Checkout\Model\Sidebar $sidebar
     * @param \Magestore\OneStepCheckout\Helper\Data $oscHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalRepositoryInterface,
        \Magento\Checkout\Model\Sidebar $sidebar,
        \Magestore\OneStepCheckout\Helper\Data $oscHelper
    ) {
        parent::__construct($context);
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_jsonHelper = $jsonHelper;
        $this->_dataObjectFactory = $dataObjectFactory;
        $this->_sidebar = $sidebar;
        $this->_cartTotalRepositoryInterface = $cartTotalRepositoryInterface;
        $this->_oscHelper = $oscHelper;
    }


    /**
     *
     */
    public function execute()
    {
        /** @var \Magento\Framework\DataObject $qtyData */
        $additionalData = $this->_dataObjectFactory->create([
            'data' => $this->_jsonHelper->jsonDecode($this->getRequest()->getContent()),
        ]);
        $this->_objectManager->get('Magento\Checkout\Model\Session')->setData('osc_delivery_date', $additionalData->getData('osc_delivery_date'));
        $this->_objectManager->get('Magento\Checkout\Model\Session')->setData('osc_comment', $additionalData->getData('osc_comment'));
        $this->_objectManager->get('Magento\Checkout\Model\Session')->setData('osc_newsletter', $additionalData->getData('osc_newsletter'));
        $this->_objectManager->get('Magento\Checkout\Model\Session')->setData('osc_security_code', $additionalData->getData('osc_security_code'));
        $this->_objectManager->get('Magento\Checkout\Model\Session')->setData('osc_delivery_time', $additionalData->getData('osc_delivery_time'));
    }
}