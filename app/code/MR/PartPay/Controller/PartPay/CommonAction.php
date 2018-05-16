<?php
namespace MR\PartPay\Controller\PartPay;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order\ShipmentFactory;
use Magento\Sales\Model\Order\Invoice;

use Symfony\Component\Filesystem\LockHandler;

abstract class CommonAction extends \Magento\Framework\App\Action\Action
{

    /**
     *
     * @var \Magento\Quote\Model\QuoteManagement
     */
    private $_quoteManagement;

    /**
     *
     * @var \Magento\Quote\Model\GuestCart\GuestCartManagement
     */
    private $_guestCartManagement;

    /**
     *
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;

    /**
     *
     * @var \MR\PartPay\Helper\Communication
     */
    private $_communication;

    /**
     *
     * @var \MR\PartPay\Helper\Configuration
     */
    private $_configuration;

    /**
     *
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $_messageManager;

    /**
     *
     * @var \MR\PartPay\Logger\PartPayLogger
     */
    private $_logger;

    public function __construct(Context $context)
    {
        parent::__construct($context);
        $this->_logger = $this->_objectManager->get("\MR\PartPay\Logger\PartPayLogger");
        $this->_communication = $this->_objectManager->get("\MR\PartPay\Helper\Communication");
        $this->_configuration = $this->_objectManager->get("\MR\PartPay\Helper\Configuration");
        
        $this->_quoteManagement = $this->_objectManager->get("\Magento\Quote\Model\QuoteManagement");
        $this->_guestCartManagement = $this->_objectManager->get("\Magento\Quote\Model\GuestCart\GuestCartManagement");
        $this->_checkoutSession = $this->_objectManager->get("\Magento\Checkout\Model\Session");
        $this->_messageManager = $this->_objectManager->get("\Magento\Framework\Message\ManagerInterface");
        
        $this->_logger->info(__METHOD__);
    }

    public function success()
    {
        $this->_logger->info(__METHOD__);
        $this->_handlePaymentResponse(true);
    }

    public function fail()
    {
        $this->_logger->info(__METHOD__);
        $this->_handlePaymentResponse(false);
        return;
    }

    private function _handlePaymentResponse($success)
    {
        $pxPayUserId = $this->getRequest()->getParam('userid');
        $token = $this->getRequest()->getParam('result');
        $this->_logger->info(__METHOD__ . " userId:{$pxPayUserId} token:{$token} success:{$success}");

        /**
         *
         * @var \Symfony\Component\Filesystem\LockHandler
         */
        $lockHandler = null;
        try {
            $lockHandler = new LockHandler($token, BP . "/var/locks");
            if (!$lockHandler->lock(false)) {
                $action = $this->getRequest()->getActionName();
                $params = $this->getRequest()->getParams();
                $triedTime = 0;
                if (array_key_exists('TriedTime', $params)) {
                    $triedTime = $params['TriedTime'];
                }
                if ($triedTime > 40) { // 40 seconds should be enough
                    $this->_redirectToCartPageWithError("Failed to process the order, please contact support.");
                    $this->_logger->critical(__METHOD__ . " lock timeout. userId:{$pxPayUserId} token:{$token} success:{$success} triedTime:{$triedTime}");
                    return;
                }
                
                $params['TriedTime'] = $triedTime + 1;
                
                $this->_logger->info( __METHOD__ . " redirecting to self, wait for lock release. userId:{$pxPayUserId} token:{$token} success:{$success} triedTime:{$triedTime}");
                sleep(1); // wait for sometime about lock release
                return $this->_forward($action, null, null, $params);
            }
            
            $this->_handlePaymentResponseWithoutLock($success, $pxPayUserId, $token);
            $lockHandler->release();
        } catch (\Exception $e) {
            if (isset($lockHandler)) {
                $lockHandler->release();
            }
            
            $this->_logger->critical(__METHOD__ . "  " . "\n" . $e->getMessage() . $e->getTraceAsString());
            $this->_redirectToCartPageWithError("Failed to processing the order, please contact support.");
        }
    }

    private function _handlePaymentResponseWithoutLock($success, $pxPayUserId, $token)
    {
        $this->_logger->info(__METHOD__ . " userId:{$pxPayUserId} token:{$token} success:{$success}");
        
        $cache = $this->_loadTransactionStatusFromCache($pxPayUserId, $token);
        $orderIncrementId = $cache->getOrderIncrementId();
        if (empty($orderIncrementId)) {
                
            $responseXmlElement = $this->_getTransactionStatus($pxPayUserId, $token);
            if (!$responseXmlElement) {
                return;
            }
            
            $orderIncrementId = (string)$responseXmlElement->MerchantReference;
            $dpsTxnRef = (string)$responseXmlElement->DpsTxnRef;
            
            /**
             * @var \Magento\Quote\Model\Quote $quote
             */
            $quote = $this->_loadQuote($orderIncrementId);
            if ($quote == null) {
                $error = "Failed to load quote from order: {$orderIncrementId}";
                $this->_logger->critical($error);
                $this->_redirectToCartPageWithError($error);
                return;
            }
            
            $this->_savePaymentResult($pxPayUserId, $token, $quote, $responseXmlElement);
            if (!$success) {
                $payment = $quote->getPayment();
                $this->_savePaymentInfoForFailedPayment($payment);
                
                $error = "Payment failed. Error: " . $responseXmlElement->ResponseText;
                $this->_logger->info($error);
                $this->_redirectToCartPageWithError($error);
                return;
            }
            
            return $this->_placeOrder($quote, $responseXmlElement);
        }
        
        if (!$success) {
            $responseXmlElement = $cache->getResponseXmlElement();
            
            $error = "Payment failed. Error: " . $responseXmlElement->ResponseText;
            $this->_logger->info($error);
            $this->_redirectToCartPageWithError($error);
            return;
        }
        
        $this->_redirect("pxpay2/pxfusion/waitingQuote", 
            [
                "_secure" => true,
                "triedTimes" => 0,
                "reservedOrderId" => $orderIncrementId
            ]);
        return;
    }

    private function _loadQuote($orderIncrementId)
    {
        $this->_logger->info(__METHOD__ . " reserved_order_id:{$orderIncrementId}");
        
        $quoteManager = $this->_objectManager->create("\Magento\Quote\Model\Quote");
        /**
         * @var \Magento\Quote\Model\Quote $quote
         */
        $quote = $quoteManager->load($orderIncrementId, "reserved_order_id");
        
        if (!$quote->getId()) {
            $error = "Failed to load quote from order:{$orderIncrementId}";
            $this->_logger->critical($error);
            $this->_redirectToCartPageWithError($error);
            return null;
        }
        
        return $quote;
    }

    private function _loadTransactionStatusFromCache($pxPayUserId, $token)
    {
        $this->_logger->info(__METHOD__ . " userId:{$pxPayUserId} token:{$token}");
        
        $paymentResultModel = $this->_objectManager->create("\MR\PartPay\Model\PaymentResult");
        
        $paymentResultModelCollection = $paymentResultModel->getCollection()
            ->addFieldToFilter('token', $token)
            ->addFieldToFilter('user_name', $pxPayUserId);
        
        $paymentResultModelCollection->getSelect();
        
        $isProcessed = false;
        $dataBag = $this->_objectManager->create("\Magento\Framework\DataObject");

        $orderIncrementId = null;
        foreach ($paymentResultModelCollection as $item) {
            $orderIncrementId = $item->getReservedOrderId();
            $quoteId = $item->getQuoteId();
            $dataBag->setQuoteId($quoteId);
            $responseXmlElement = simplexml_load_string($item->getRawXml());
            $dataBag->setResponseXmlElement($responseXmlElement);
            $this->_logger->info(__METHOD__ . " userId:{$pxPayUserId} token:{$token} orderId:{$orderIncrementId} quoteId:{$quoteId}");
            break;
        }
        
        $dataBag->setOrderIncrementId($orderIncrementId);
        
        $this->_logger->info(__METHOD__ . " userId:{$pxPayUserId} token:{$token} orderIncrementId:{$orderIncrementId}");
        return $dataBag;
    }

    private function _placeOrder(\Magento\Quote\Model\Quote $quote, $responseXmlElement)
    {
        $orderIncrementId = (string)$responseXmlElement->MerchantReference;
        $this->_logger->info(__METHOD__ . " orderIncrementId:{$orderIncrementId}");
        
        $quoteId = $quote->getId();
        $payment = $quote->getPayment();
        
        $info = $payment->getAdditionalInformation();
        $this->_logger->info(__METHOD__ . " info:" . var_export($info, true));
        
        $this->_savePaymentInfoForSuccessfulPayment($payment, $responseXmlElement);

        $isRegisteredCustomer = !empty($quote->getCustomerId());
        if ($isRegisteredCustomer) {
            $quote->setPayment($payment); // looks like $payment is copy by reference. ensure the $payment data of order is exactly same with the quote.
            $this->_logger->info(__METHOD__ . " placing order for logged in customer. quoteId:{$quoteId}");
            // create order, and redirect to success page.
            $orderId = $this->_quoteManagement->placeOrder($quoteId);
        } else {
            // Guest:
            $cartId = $info["cartId"];
            $this->_logger->info(__METHOD__ . " placing order for guest. quoteId:{$quoteId} cartId:{$cartId}");
            $orderId = $this->_guestCartManagement->placeOrder($cartId);
        }
        
        $this->_checkoutSession->setLoadInactive(false);
        $this->_checkoutSession->replaceQuote($this->_checkoutSession->getQuote()->save());
        
        $this->_logger->info(__METHOD__ . " placing order done lastSuccessQuoteId:". $this->_checkoutSession->getLastSuccessQuoteId().
            " lastQuoteId:".$this->_checkoutSession->getLastQuoteId(). 
            " lastOrderId:".$this->_checkoutSession->getLastOrderId().
            " lastRealOrderId:" . $this->_checkoutSession->getLastRealOrderId());
        
        $this->_redirect("checkout/onepage/success", [
            "_secure" => true
        ]);
        return;
    }

    private function _savePaymentInfoForSuccessfulPayment($payment, $paymentResponseXmlElement)
    {
        $this->_logger->info(__METHOD__);
        $info = $payment->getAdditionalInformation();
        
        $info = $this->_clearPaymentParameters($info);
        
        $info["DpsTransactionType"] = (string)$paymentResponseXmlElement->TxnType;
        $info["DpsResponseText"] = (string)$paymentResponseXmlElement->ResponseText;
        $info["ReCo"] = (string)$paymentResponseXmlElement->ReCo;
        $info["DpsTransactionId"] = (string)$paymentResponseXmlElement->TxnId;
        $info["DpsTxnRef"] = (string)$paymentResponseXmlElement->DpsTxnRef;
        $info["CardName"] = (string)$paymentResponseXmlElement->CardName;
        
        // TODO: Save currency because I do not know how to get currency in payment::capture. Remove it when found a better way
        $info["Currency"] = (string)$paymentResponseXmlElement->CurrencyInput;
        
        $payment->unsAdditionalInformation(); // ensure DpsBillingId is not saved to database.
        $payment->setAdditionalInformation($info);
        
        $info = $payment->getAdditionalInformation();
        $this->_logger->info(__METHOD__ . " info: ".var_export($info, true));
        $payment->save();
    }
    
    private function _savePaymentInfoForFailedPayment($payment)
    {
        $this->_logger->info(__METHOD__);
        $info = $payment->getAdditionalInformation();
    
        $info = $this->_clearPaymentParameters($info);

        $payment->unsAdditionalInformation(); // ensure DpsBillingId is not saved to database.
        $payment->setAdditionalInformation($info);
        $payment->save();
    }
    
    private function _clearPaymentParameters($info)
    {
        $this->_logger->info(__METHOD__);
        
        unset($info["cartId"]);
        unset($info["guestEmail"]);
        unset($info["method_title"]);

        $this->_logger->info(__METHOD__ . " info: ".var_export($info, true));
        return $info;
    }

    private function _savePaymentResult($pxpayUserId, $token, \Magento\Quote\Model\Quote $quote, 
        $paymentResponseXmlElement)
    {
        $this->_logger->info(__METHOD__ . " username:{$pxpayUserId}, token:{$token}");
        $payment = $quote->getPayment();
        $method = $payment->getMethod();
        
        $paymentResultModel = $this->_objectManager->create("\MR\PartPay\Model\PaymentResult");
        $paymentResultModel->setData(
            array(
                "dps_transaction_type" => (string)$paymentResponseXmlElement->TxnType,
                "dps_txn_ref" => (string)$paymentResponseXmlElement->DpsTxnRef,
                "method" => $method,
                "user_name" => $pxpayUserId,
                "token" => $token,
                "quote_id" => $quote->getId(),
                "reserved_order_id" => (string)$paymentResponseXmlElement->MerchantReference,
                "updated_time" => new \DateTime(),
                "raw_xml" => (string)$paymentResponseXmlElement->asXML()
            ));
        
        $paymentResultModel->save();
        
        $this->_logger->info(__METHOD__ . " done");
    }

    private function _getTransactionStatus($pxPayUserId, $token)
    {
        $responseXml = $this->_communication->getTransactionStatus($pxPayUserId, $token);
        $responseXmlElement = simplexml_load_string($responseXml);
        if (!$responseXmlElement) { // defensive code. should never happen
            $this->_logger->critical(__METHOD__ . " userId:{$pxPayUserId} token:{$token} response format is incorrect");
            $this->_redirectToCartPageWithError("Failed to connect to Payment Express. Please try again later.");
            return false;
        }
        
        return $responseXmlElement;
    }
    
    private function _redirectToCartPageWithError($error)
    {
        $this->_logger->info(__METHOD__ . " error:{$error}");
        
        $this->_messageManager->addErrorMessage($error);
        $this->_redirect("checkout/cart");
    }
}
