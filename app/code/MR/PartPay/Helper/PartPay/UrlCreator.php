<?php
namespace MR\PartPay\Helper\PartPay;

class UrlCreator
{

    /**
     *
     * @var \Magento\Framework\App\ObjectManager
     */
    private $_objectManager;
    
    /**
     *
     * @var \MR\PartPay\Logger\PartPayLogger
     */
    private $_logger;

    /**
     *
     * @var \MR\PartPay\Helper\Configuration
     */
    protected $_configuration;

    /**
     *
     * @var \MR\PartPay\Helper\Communication
     */
    protected $_communication;

    public function __construct()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_objectManager = $objectManager;
        $this->_communication = $objectManager->get("\MR\PartPay\Helper\Communication");
        $this->_logger = $objectManager->get("\MR\PartPay\Logger\PartPayLogger");
        $this->_configuration = $objectManager->get("\MR\PartPay\Helper\Configuration");
        
        $this->_logger->info(__METHOD__);
    }

    public function CreateUrl(\Magento\Quote\Model\Quote $quote)
    {
        $this->_logger->info(__METHOD__);
        $requestData = $this->_buildPartPayRequestData($quote);
        
        $responseXml = $this->_communication->getPartPayPage($requestData);
        
        $responseXmlElement = simplexml_load_string($responseXml);
        if (!$responseXmlElement) {
            $error = "Invalid response from PartPay: " . $responseXml;
            $this->_logger->critical(__METHOD__ . " " . $error);
            return "";
        }
        
        if ($responseXmlElement['valid'] != "1" || !$responseXmlElement->URI) {
            $error = "Failed to get the Payment Url";
            // <Request valid="1"><Reco>W2</Reco><ResponseText>No Account2Account Account Setup For Payment Currency</ResponseText></Request>
            if (isset($responseXmlElement->Reco) || isset($responseXmlElement->ResponseText)) {
                $error = "Error from PaymentExpress: ReCo: " . $responseXmlElement->Reco . " ResponseText:" .
                     $responseXmlElement->ResponseText;
            } elseif (isset($responseXmlElement->URI)) {
                $error = "Error from PaymentExpress: " . $responseXmlElement->URI;
            }

            $this->_logger->critical(__METHOD__ . " " . $error);
            
            return "";
        }

        return (string)$responseXmlElement->URI;
    }

    private function _buildPartPayRequestData(\Magento\Quote\Model\Quote $quote)
    {
        $orderIncrementId = $quote->getReservedOrderId();
        $this->_logger->info(__METHOD__ . " orderIncrementId:{$orderIncrementId}");

        $customerInfo = $this->_loadCustomerInfo($quote);
        //format order
        $param = array();
        $param['amount'] = $quote->getBaseGrandTotal();

        $param['consumer']['phoneNumber'] = $customerInfo->getPhoneNumber();
        $param['consumer']['surname'] = $customerInfo->getSurname();
        $param['consumer']['email'] = $quote->getCustomerEmail();

        $param['billing']['addressLine1'] = $customerInfo->getBillingStreet1();
        $param['billing']['addressLine2'] = $customerInfo->getBillingStreet2();
        $param['billing']['suburb'] = '';
        $param['billing']['city'] = $quote->getBillingAddress()->getCity();
        $param['billing']['postcode'] = $quote->getBillingAddress()->getPostcode();
        $param['billing']['state'] = $quote->getBillingAddress()->getRegion() ? $quote->getBillingAddress()->getRegion() : '';
        $param['billing']['country'] = $quote->getBillingAddress()->getCountry();

        $param['shipping']['addressLine1'] = $customerInfo->getShippingStreet1();
        $param['shipping']['addressLine2'] = $customerInfo->getShippingStreet2();
        $param['shipping']['suburb'] = '';
        $param['shipping']['city'] = $quote->getShippingAddress()->getCity();
        $param['shipping']['postcode'] = $quote->getShippingAddress()->getPostcode();
        $param['shipping']['state'] = $quote->getShippingAddress()->getRegion();
        $param['shipping']['country'] = $quote->getShippingAddress()->getCountry();

        $param['description'] = '';

        $productManager = $this->_objectManager->create("\Magento\Catalog\Model\Product");
        //format all items in cart
        foreach ( $quote->getAllVisibleItems() as $item){
            /**
             * @var \Magento\Catalog\Model\Product $product
             */
            $product = $productManager->load($item->getProductId());
            $param['items'][] = array(
                'description' => $product->getDescription(),
                'name' => $item->getName(),
                'sku' => $item->getSku(),
                'quantity' => $item->getQtyOrdered(),
                'price' => $item->getBaseRowTotalInclTax(),
            );
        }

        $urlFail = $this->_getUrl('pxpay2/pxpay2/fail', ['_secure' => true]);
        $urlSuccess = $this->_getUrl('pxpay2/pxpay2/success', ['_secure' => true]);
        $param['merchant']['redirectConfirmUrl'] = Mage::getUrl('partpay/order/success', array('_nosid' => true, 'order_id' => $orderIncrementId));
        $param['merchant']['redirectCancelUrl'] = Mage::getUrl('partpay/order/fail', array('_nosid' => true, 'order_id' => $orderIncrementId));

        $param['merchantReference'] = $orderIncrementId;
        $param['taxAmount'] = $quote->getTaxAmount();
        $param['shippingAmount'] = $quote->getShippingAmount();

        $this->_logger->info(__METHOD__ . " param:" . var_export($param, true));
        return $param;
    }

    private function _loadCustomerInfo(\Magento\Quote\Model\Quote $quote)
    {
        $customerId = $quote->getCustomerId();
        $this->_logger->info(__METHOD__ . " customerId:{$customerId}");
        $customerInfo = $this->_objectManager->create("\Magento\Framework\DataObject");

        $customerInfo->setId($customerId);

        $customerInfo->setSurname($this->_getCustomerSurname($quote));
        $customerInfo->setEmail($quote->getCustomerEmail());

        try {
            $billingAddress = $quote->getBillingAddress();
            if ($billingAddress) {
                $customerInfo->setPhoneNumber($billingAddress->getTelephone());

                $billingStreetData = $billingAddress->getStreet();
                $streetFull = implode(" ", $billingStreetData) . " " . $billingAddress->getCity() . ", " .
                    $billingAddress->getRegion() . " " . $billingAddress->getPostcode() . " " . $billingAddress->getCountryId();
                if (isset($billingStreetData[0])) {
                    $customerInfo->setBillingStreet1($billingStreetData[0]);
                }
                if (isset($billingStreetData[1])) {
                    $customerInfo->setBillingStreet2($billingStreetData[1]);
                }
                $customerInfo->setFullAddress($streetFull);
            }
            if ($shippingAddress = $quote->getShippingAddress()) {
                $shippingStreetData = $shippingAddress->getStreet();
                if (isset($shippingStreetData[0])) {
                    $customerInfo->setShippingStreet1($shippingStreetData[0]);
                }
                if (isset($shippingStreetData[1])) {
                    $customerInfo->setShippingStreet2($shippingStreetData[1]);
                }
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->_logger->critical($e->_toString());
        }

        return $customerInfo;
    }

    /**
     * Retrieve customer name
     *
     * @return string
     */
    private function _getCustomerSurname(\Magento\Quote\Model\Quote $quote)
    {
        if ($quote->getCustomerLastname()) {
            $customerName = $quote->getCustomerLastname();
        } else {
            $customerName = $quote->getBillingAddress()->getLastname();
        }
        
        $this->_logger->info(__METHOD__ . " customerSurname:{$customerName}");
        return $customerName;
    }
}
