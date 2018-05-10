<?php
namespace PaymentExpress\PxPay2\Controller\PxFusion;

use Symfony\Component\Filesystem\LockHandler;

class Result extends CommonAction
{
	/**
	 * 
	 * @var \Magento\Store\Model\StoreManagerInterface
	 */
	private $_storeManager;
	

    public function __construct(
    		\Magento\Framework\App\Action\Context $context, 
    		\Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->_logger->info(__METHOD__);
    }

    public function execute()
    {
        $transactionId = $this->getRequest()->getParam('sessionid');
        $this->_logger->info(__METHOD__ . " transactionId:{$transactionId}");
        
        /**
         *
         * @var \Symfony\Component\Filesystem\LockHandler
         */
        $lockHandler = null;
        try {
            // avoid place order twice, as there is fprn in dps
            $lockHandler = new LockHandler($transactionId, BP . "/var/locks");
            if (!$lockHandler->lock(false)){
                $action = $this->getRequest()->getActionName();
                $params = $this->getRequest()->getParams();
                $triedTime = 0;
                if (array_key_exists('TriedTime', $params)){
                    $triedTime = $params['TriedTime'];
                }
                if ($triedTime > 40){ // 40 seconds should be enough
                    $this->_messageManager->addErrorMessage("Failed to process the order, please contact support");
                    $this->_redirect("checkout/cart");
                    $this->_logger->critical(__METHOD__ . " lock timeout. transactionId:{$transactionId} triedTime:{$triedTime}");
                    return;
                }
                $params['TriedTime'] = $triedTime + 1;
                $this->_logger->info(__METHOD__ . " redirecting to self, wait for lock release. transactionId:{$transactionId} triedTime:{$triedTime}");
                sleep(1); // give sometime for the previous response, before redirecting.
                return $this->_forward($action, null, null, $params);
            }

            $this->_processPaymentResult($transactionId);
            $lockHandler->release();
        } catch (\Exception $e) {
            if (isset($lockHandler)) {
                $lockHandler->release();
            }
            
            $this->_logger->critical(__METHOD__ . "  " . "\n" . $e->getMessage() . $e->getTraceAsString());
            $this->_messageManager->addErrorMessage("Failed to process the order, please contact support.");
            $this->_redirect("checkout/cart");
        }
    }

    private function _processPaymentResult($transactionId)
    {
        $userName = $this->_configuration->getUserName();
        $this->_logger->info(__METHOD__ . " userName:{$userName} transactionId:{$transactionId}");
        
        $dataBag = $this->_loadTransactionResultFromCache($userName, $transactionId); 
        $status = self::RESULT_UNKOWN;
        if (empty($dataBag)){
                        
            $transactionResult = $this->_getPaymentResult($transactionId, 0);

            $status = $transactionResult["status"];
            $quoteId = $transactionResult["txnRef"];
            $quote = $this->_quoteRepository->get($quoteId);
            $payment = $quote->getPayment();
            
            $this->_savePaymentResult($userName, $transactionId, $quote, $transactionResult);
            if ($status == self::APPROVED) {
                $data = $payment->getAdditionalInformation();
                $this->_logger->info(__METHOD__ . " data:" . var_export($data, true));
                
                $this->_updatePaymentData($payment, $transactionResult);

                if (!$quote->getCustomerIsGuest()){
                    $this->_logger->info(__METHOD__ . " placing order for logged in customer. quoteId:{$quoteId}");
                    // create order, and redirect to success page.
                    $orderId = $this->_quoteManagement->placeOrder($quoteId);
                    $enableAddBillCard =  filter_var($data["EnableAddBillCard"], FILTER_VALIDATE_BOOLEAN);
                    
                    if ($this->_configuration->getAllowRebill() && $enableAddBillCard) {
                    	$customerId = $quote->getCustomer()->getId();
                    	$cardNumber = $transactionResult["cardNumber"];
                    	$dateExpiry = $transactionResult["dateExpiry"];
                    	$dpsBillingId = $transactionResult["dpsBillingId"];
                    	$this->_saveRebillToken($orderId, $customerId, $cardNumber, $dateExpiry, $dpsBillingId);
                    }
                } else {
                    // Guest:
                    $cartId = $data["cartId"];
                    $this->_logger->info(__METHOD__ . " placing order for guest. quoteId:{$quoteId} cartId:{$cartId}");
                    $orderId = $this->_guestCartManagement->placeOrder($cartId);
                }
                $this->_logger->info(__METHOD__ . " placing order done lastRealOrderId:".$this->_checkoutSession->getLastRealOrderId());

                $this->_redirect("checkout/onepage/success", [
                    "_secure" => true
                    ]);
                return;
            }
        }
        else{
            $transactionResult = $dataBag->getTransactionResult();
            $status = $transactionResult["status"];
        }
        if ($status == self::APPROVED) {
            $this->_redirect("pxpay2/pxfusion/waitingQuote", 
                [
                    "_secure" => true,
                    "triedTimes" => 0,
                    "reservedOrderId" => $dataBag->getReservedOrderId()
                ]);
            return;
        }
        
        // failed case handled here. Success one is redirected to the onepage/success already.
        $error = "Failed to process the order.";
        if ($status == self::NO_TRANSACTION || $status == self::RESULT_UNKOWN) {
            // Not able to found transaction in dps. And even not able to know which quote does the payment belongs to.
            $error = "The order is not found. Please contact support";
        }
        if ($status == self::DECLINED){
            $error = "Payment failed. Error:" . $transactionResult["responseText"]. ", please contact support";
        }
        
        $this->_messageManager->addErrorMessage($error);
        $this->_logger->critical(__METHOD__ . " status:{$status} error:{$error}");
        $this->_redirect("checkout/cart");
        return;
    }
    
    private function _saveRebillToken($orderId, $customerId, $cardNumber, $dateExpiry, $dpsBillingId)
    {
    	$this->_logger->info(__METHOD__." orderId:{$orderId}, customerId:{$customerId}");
    	$storeId = $this->_storeManager->getStore()->getId();
    	$billingModel = $this->_objectManager->create("\PaymentExpress\PxPay2\Model\BillingToken");
    	$billingModel->setData(
    			array(
    					"customer_id" => $customerId,
    					"order_id" => $orderId,
    					"store_id" => $storeId,
    					"masked_card_number" => $cardNumber,
    					"cc_expiry_date" => $dateExpiry,
    					"dps_billing_id" => $dpsBillingId
    			));
    	$billingModel->save();
    }

}