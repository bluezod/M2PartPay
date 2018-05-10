<?php
namespace PaymentExpress\PxPay2\Controller\Order;

class Processed extends \Magento\Framework\App\Action\Action
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

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
        $this->_logger = $this->_objectManager->get("\PaymentExpress\PxPay2\Logger\DpsLogger");
        $this->_logger->info(__METHOD__);
    }

    public function execute()
    {
        $this->_logger->info(__METHOD__);
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getLayout()->initMessages();
        return $resultPage;
    }
}
