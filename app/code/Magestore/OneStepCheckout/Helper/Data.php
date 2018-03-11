<?php

/**
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */

namespace Magestore\OneStepCheckout\Helper;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address\Renderer;

/**
 * Class Data
 * @package Magestore\OneStepCheckout\Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     *
     */
    const XML_PATH_TRANS_EMAIL_GENERAL_EMAIL = 'trans_email/ident_general/email';

    /**
     *
     */
    const XML_PATH_TRANS_EMAIL_GENERAL_NAME = 'trans_email/ident_general/name';

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $_subscriberFactory;
    

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\GiftMessage\Model\MessageFactory
     */
    protected $_giftMessageFactory;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var Renderer
     */
    protected $_addressRenderer;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $_paymentHelperData;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $_priceCurrency;

    /**
     * @var Config
     */
    protected $_configHelper;


    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param Renderer $addressRenderer
     * @param \Magento\Payment\Helper\Data $paymentHelperData
     * @param Config $configHelper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\GiftMessage\Model\MessageFactory $giftMessageFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        Renderer $addressRenderer,
        \Magento\Payment\Helper\Data $paymentHelperData,
        \Magestore\OneStepCheckout\Helper\Config $configHelper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\GiftMessage\Model\MessageFactory $giftMessageFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
    )
    {
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->_subscriberFactory = $subscriberFactory;
        $this->_transportBuilder = $transportBuilder;
        $this->_paymentHelperData = $paymentHelperData;
        $this->_addressRenderer = $addressRenderer;
        $this->_configHelper = $configHelper;
        $this->_objectManager = $objectManager;
        $this->_giftMessageFactory = $giftMessageFactory;
        $this->inlineTranslation = $inlineTranslation;
        $this->_checkoutSession = $checkoutSession;
        $this->_priceCurrency = $priceCurrency;
    }


    /**
     * @param $email
     */
    public function addSubscriber($email)
    {
        if ($email) {
            $subscriberModel = $this->_subscriberFactory->create()->loadByEmail($email);
            if ($subscriberModel->getId() === null) {
                try {
                    $this->_subscriberFactory->create()->subscribe($email);
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->_objectManager->get('Psr\Log\LoggerInterface')->notice($e->getMessage());
                } catch (\Exception $e) {
                    $this->_objectManager->get('Psr\Log\LoggerInterface')->notice($e->getMessage());
                }

            } elseif ($subscriberModel->getData('subscriber_status') != 1) {
                $subscriberModel->setData('subscriber_status', 1);
                try {
                    $subscriberModel->save();
                } catch (\Exception $e) {
                    $this->_objectManager->get('Psr\Log\LoggerInterface')->notice($e->getMessage());
                }
            }
        }
    }

    /**
     * @return mixed
     */
    public function hasGiftwrap() {
        return $this->_checkoutSession->getData('onestepcheckout_giftwrap');
    }

    /**
     * Get payment info block as html
     *
     * @param Order $order
     *
     * @return string
     */
    protected function getPaymentHtml(Order $order, $storeId)
    {
        return $this->_paymentHelperData->getInfoBlockHtml(
            $order->getPayment(),
            $storeId
        );
    }


    /**
     * @param Order $order
     *
     * @return string|null
     */
    protected function getFormattedShippingAddress($order)
    {
        return $order->getIsVirtual()
            ? null
            : $this->_addressRenderer->format($order->getShippingAddress(), 'html');
    }

    /**
     * @param Order $order
     *
     * @return string|null
     */
    protected function getFormattedBillingAddress($order)
    {
        return $this->_addressRenderer->format($order->getBillingAddress(), 'html');
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     */
    public function sendNewOrderEmail(\Magento\Sales\Model\Order $order)
    {
        $storeId = $order->getStore()->getId();
        if ($this->_configHelper->isEnableSendEmailAdmin()) {
            $emailArray = explode(',', $this->_configHelper->notifyToEmail());
            $sendTo = [];
            if (!empty($emailArray)) {
                foreach ($emailArray as $email) {
                    $sendTo[] = ['email' => trim($email), 'name' => ''];
                }
            }
            $this->inlineTranslation->suspend();
            foreach ($sendTo as $recipient) {
                try {
                    $transport = $this->_transportBuilder->setTemplateIdentifier(
                        $this->_configHelper->getEmailTemplate()
                    )->setTemplateOptions(
                        ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeId]
                    )->setTemplateVars(
                        [
                            'order'                    => $order,
                            'billing'                  => $order->getBillingAddress(),
                            'payment_html'             => $this->getPaymentHtml($order, $storeId),
                            'store'                    => $order->getStore(),
                            'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
                            'formattedBillingAddress'  => $this->getFormattedBillingAddress($order),
                        ]
                    )->setFrom(
                        [
                            'email' => $this->scopeConfig->getValue(
                                self::XML_PATH_TRANS_EMAIL_GENERAL_EMAIL,
                                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                                $storeId
                            ),
                            'name'  => $this->scopeConfig->getValue(
                                self::XML_PATH_TRANS_EMAIL_GENERAL_NAME,
                                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                                $storeId
                            ),
                        ]
                    )->addTo(
                        $recipient['email'],
                        $recipient['name']
                    )->getTransport();
                    $transport->sendMessage();
                } catch (\Magento\Framework\Exception\MailException $ex) {
                    $this->_objectManager->get('Psr\Log\LoggerInterface')->notice($ex->getMessage());
                }
            }
            $this->inlineTranslation->resume();
        }
    }

    /**
     * @return bool
     */
    public function isContainDownloadableProduct()
    {
        if ($this->scopeConfig->isSetFlag('catalog/downloadable/disable_guest_checkout')) {
            $quote = $this->getOnepage()->getQuote();
            foreach ($quote->getAllItems() as $item) {
                if (($product = $item->getProduct())
                    && $product->getTypeId() == \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getGiftWrapAmount()
    {
        return $this->_configHelper->getGiftWrapAmount();
    }


    /**
     * @return float|int|mixed
     */
    public function getOrderGiftWrapAmount()
    {
        $amount = $this->getGiftWrapAmount();
        $giftWrapAmount = 0;
        $items = $this->getQuote()->getAllVisibleItems();
        if ($this->getGiftwrapType() == 1) {
            foreach ($items as $item) {
                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }
                $giftWrapAmount += $amount * ($item->getQty());
            }

        } else {
            $giftWrapAmount = $amount;
        }
        $giftWrapAmount = $this->_priceCurrency->convert($giftWrapAmount);

        return $giftWrapAmount;
    }


    /**
     * @return mixed
     */
    public function getGiftWrapType()
    {
        return $this->_configHelper->getGiftWrapType();
    }

    /**
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        if (empty($this->_quote)) {
            $this->_quote = $this->_checkoutSession->getQuote();
        }

        return $this->_quote;
    }
}