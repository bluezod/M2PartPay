<?php
namespace MR\PartPay\Model\Api;

class PartPayManagement implements \MR\PartPay\Api\PartPayManagementInterface
{
    // http://devdocs.magento.com/guides/v2.0/extension-dev-guide/service-contracts/service-to-web-service.html

    
    
    /**
     * 
     * @var \MR\PartPay\Model\Api\ApiPartPayHelper
     */
    private $_apiHelper;

    /**
     *
     * @var \MR\PartPay\Logger\PartPayLogger
     */
    private $_logger;
    
    /**
     *
     * @var \Magento\Quote\Api\BillingAddressManagementInterface
     */
    private $_billingAddressManagement;


    public function __construct(\Magento\Quote\Api\BillingAddressManagementInterface $billingAddressManagement)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_billingAddressManagement = $billingAddressManagement;
        $this->_apiHelper = $objectManager->get("\MR\PartPay\Model\Api\ApiPartPayHelper");
        $this->_logger = $objectManager->get("\MR\PartPay\Logger\PartPayLogger");
        
        $this->_logger->info(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function set($cartId, \Magento\Quote\Api\Data\PaymentInterface $method, \Magento\Quote\Api\Data\AddressInterface $billingAddress = null)
    {
        $this->_logger->info(__METHOD__. " cartId:{$cartId}");
        
        if ($billingAddress) {
        	$this->_logger->info(__METHOD__. " assigning billing address");
        	$this->_billingAddressManagement->assign($cartId, $billingAddress);
        }
        
        $url = $this->_apiHelper->createUrlForCustomer($cartId, $method);
        $this->_logger->info(__METHOD__. " redirectUrl:{$url}");
        return $url;
    }

}
