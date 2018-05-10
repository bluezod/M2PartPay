<?php
namespace PaymentExpress\PxPay2\Controller\Customer;

class Delete extends \Magento\Framework\App\Action\Action
{

    /**
     *
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $resultPageFactory;

    /**
     *
     * @var \PaymentExpress\PxPay2\Logger\DpsLogger
     */
    private $_logger;

    /**
     *
     * @var \Magento\Customer\Model\Session
     */
    private $_customerSession;

    /**
     *
     * @var \PaymentExpress\PxPay2\Helper\PaymentUtil
     */
    private $_paymentUtil;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
        $this->_customerSession = $this->_objectManager->get("\Magento\Customer\Model\Session");
        $this->_paymentUtil = $this->_objectManager->get("\PaymentExpress\PxPay2\Helper\PaymentUtil");
        $this->_logger = $this->_objectManager->get("\PaymentExpress\PxPay2\Logger\DpsLogger");
        $this->_logger->info(__METHOD__);
    }

    public function execute()
    {
        $this->_customerSession = $this->_objectManager->get("\Magento\Customer\Model\Session");
        
        $cardNumber = $this->getRequest()->getParam("cardNumber");
        $expiryDate = $this->getRequest()->getParam('expiryDate');
        $customerId = $this->_customerSession->getCustomerId();
        
        $this->_logger->info(__METHOD__ . " customerId:{$customerId} cardNumber:{$cardNumber} expiryDate:{$expiryDate}");
        $this->_paymentUtil->deleteCards($customerId, $cardNumber, $expiryDate);
        
        return $this->_redirect("pxpay2/customer/");
    }
}
