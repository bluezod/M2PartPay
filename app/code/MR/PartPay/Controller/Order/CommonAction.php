<?php
namespace MR\PartPay\Controller\Order;

use Magento\Framework\App\Action\Context;
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

    protected function _validateToken($partpayId, $orderToken){

        $requestTokenManager = $this->_objectManager->create("\MR\PartPay\Model\RequestToken");
        $requestToken = $requestTokenManager->load($partpayId, "partpay_id");
        return $requestToken->getId() && $requestToken->getToken() == $orderToken;
    }

    private function _handlePaymentResponse($success)
    {
        $orderIncrementId = $this->getRequest()->getParam('mage_order_id');
        $orderToken = $this->getRequest()->getParam('token');
        $partpayId = $this->getRequest()->getParam('orderId');

        if(!$this->_validateToken($partpayId, $orderToken)) {
            $error = "The token and payment ID do not match at PartPay side.";
            $this->_logger->info($error);
            $this->_redirectToCartPageWithError($error);
            return;
        }

        $this->_logger->info(__METHOD__ . " order:{$orderIncrementId} token:{$orderToken} success:{$success}");

        /**
         * @var \Symfony\Component\Filesystem\LockHandler
         */
        $lockHandler = null;
        try {
            $lockHandler = new LockHandler($orderToken, BP . "/var/locks");
            if (!$lockHandler->lock(false)) {
                $action = $this->getRequest()->getActionName();
                $params = $this->getRequest()->getParams();
                $triedTime = 0;
                if (array_key_exists('TriedTime', $params)) {
                    $triedTime = $params['TriedTime'];
                }
                if ($triedTime > 40) { // 40 seconds should be enough
                    $this->_redirectToCartPageWithError("Failed to process the order, please contact support.");
                    $this->_logger->critical(__METHOD__ . " lock timeout. order:{$orderIncrementId} token:{$orderToken} success:{$success} triedTime:{$triedTime}");
                    return;
                }
                
                $params['TriedTime'] = $triedTime + 1;
                
                $this->_logger->info( __METHOD__ . " redirecting to self, wait for lock release. order:{$orderIncrementId} token:{$orderToken} success:{$success} triedTime:{$triedTime}");
                sleep(1); // wait for sometime about lock release
                return $this->_forward($action, null, null, $params);
            }
            
            $this->_handlePaymentResponseWithoutLock($success, $orderIncrementId, $partpayId);
            $lockHandler->release();
        } catch (\Exception $e) {
            if (isset($lockHandler)) {
                $lockHandler->release();
            }
            
            $this->_logger->critical(__METHOD__ . "  " . "\n" . $e->getMessage() . $e->getTraceAsString());
            $this->_redirectToCartPageWithError("Failed to process the order, please contact support.");
        }
    }

    private function _handlePaymentResponseWithoutLock($success, $orderIncrementId, $partpayId)
    {
        $this->_logger->info(__METHOD__ . " order:{$orderIncrementId} partpayId:{$partpayId} success:{$success}");

        if (!$orderIncrementId) {
            $error = "The PartPay response does not contain an order ID. Order failed.";
            $this->_logger->info($error);
            $this->_redirectToCartPageWithError($error);
            return;
        }

        $response = $this->_getTransactionStatus($partpayId);
        if (!$response) {
            return;
        }
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

//            $this->_savePaymentResult($orderIncrementId, $orderToken, $quote, $response);
        if (!$success || in_array($response['orderStatus'], ['Declined', 'Abandoned'])) {
            $payment = $quote->getPayment();
            $this->_savePaymentInfoForFailedPayment($payment);

            $error = "Payment failed. Error: ";
            $this->_logger->info($error);
            $this->_redirectToCartPageWithError($error);
            return;
        }

        return $this->_placeOrder($orderIncrementId, $quote, $response);
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

    private function _placeOrder($orderIncrementId, \Magento\Quote\Model\Quote $quote, $response)
    {
        $this->_logger->info(__METHOD__ . " orderIncrementId:{$orderIncrementId}");
        
        $quoteId = $quote->getId();
        $payment = $quote->getPayment();
        if (!isset($response['orderStatus']) || $response['orderStatus'] != 'Approved') {
            throw new \Magento\Framework\Exception\PaymentException(__('Payment failed. Order was not placed.'));
        }
        
        $info = $payment->getAdditionalInformation();
        $this->_logger->info(__METHOD__ . " info:" . var_export($info, true));
        
        $this->_savePaymentInfoForSuccessfulPayment($payment, $response);

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

    private function _savePaymentInfoForSuccessfulPayment($payment, $response)
    {
        $this->_logger->info(__METHOD__);
        $info = $payment->getAdditionalInformation();
        
        $info = $this->_clearPaymentParameters($info);
        $info = array_merge($info, $response);
        
        $payment->unsAdditionalInformation();
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

        $payment->unsAdditionalInformation();
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

    private function _getTransactionStatus($partpayId)
    {
        try{
            if(!$partpayId){
                throw new \Magento\Framework\Exception\NotFoundException(__('Can\'t find the initial PartPay request ID.'));
            }
            $response = $this->_communication->getTransactionStatus($partpayId);
            if (!$response) { // defensive code. should never happen
                throw new \Magento\Framework\Exception\NotFoundException(__('Transaction status checking response format is incorrect.'));
            }
        } catch (\Exception $ex) {
            $this->_logger->critical(__METHOD__ . " partpayId:{$partpayId} response format is incorrect");
            $this->_redirectToCartPageWithError("Failed to connect to PartPay checking transaction status. Please try again later.");
            return false;
        }
        
        return $response;
    }
    
    private function _redirectToCartPageWithError($error)
    {
        $this->_logger->info(__METHOD__ . " error:{$error}");
        
        $this->_messageManager->addErrorMessage($error);
        $this->_redirect("checkout/cart");
    }
}
