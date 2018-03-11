<?php
 /**
  * Magestore
  *
  * NOTICE OF LICENSE
  *
  * This source file is subject to the Magestore.com license that is
  * available through the world-wide-web at this URL:
  * http://www.magestore.com/license-agreement.html
  *
  * DISCLAIMER
  *
  * Do not edit or add to this file if you wish to upgrade this extension to newer
  * version in the future.
  *
  * @category    Magestore
  * @package     Magestore_OneStepCheckout
  * @copyright   Copyright (c) 2017 Magestore (http://www.magestore.com/)
  * @license     http://www.magestore.com/license-agreement.html
  *
  */
 
 namespace Magestore\OneStepCheckout\Observer\Payment\Cart;
 
 use Magento\Framework\Event\ObserverInterface;
 
 class AddCustomItem implements ObserverInterface
 {
     /**
      * @var \Magento\Checkout\Model\Session
      */
     protected $_checkoutSession;
 
     public function __construct(
         \Magento\Checkout\Model\Session $checkoutSession
     ) {
         $this->_checkoutSession = $checkoutSession;
     }
 
     /**
      * @param \Magento\Framework\Event\Observer $observer
      * @return $this
      */
     public function execute(\Magento\Framework\Event\Observer $observer)
     {
         $cart = $observer->getEvent()->getCart();
         $quote = $this->_checkoutSession->getQuote();
         $paymentMethod = $quote->getPayment()->getMethod();
         $paypalMehodList = ['payflowpro','payflow_link','payflow_advanced','braintree_paypal','paypal_express_bml','payflow_express_bml','payflow_express','paypal_express'];
 
         if($quote->getOnestepcheckoutGiftwrapAmount() && ($paymentMethod == null || in_array($paymentMethod,$paypalMehodList))) {
             if (method_exists($cart, 'addCustomItem')) {
                 $name = __("Gift Wrap");
                 $cart->addCustomItem($name->render(), 1, $quote->getOnestepcheckoutGiftwrapAmount());
             }
         }
     }
 }