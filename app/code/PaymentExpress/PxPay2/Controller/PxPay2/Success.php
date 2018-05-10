<?php
namespace PaymentExpress\PxPay2\Controller\PxPay2;

use \Magento\Framework\App\Action\Context;
use \PaymentExpress\PxPay2\Controller\PxPay2\CommonAction;

class Success extends CommonAction
{
    
    /**
     *
     * @var \PaymentExpress\PxPay2\Logger\DpsLogger
     */
    private $_logger;

    public function __construct(Context $context)
    {
        parent::__construct($context);
        $this->_logger = $this->_objectManager->get("\PaymentExpress\PxPay2\Logger\DpsLogger");
        $this->_logger->info(__METHOD__);
    }

    public function execute()
    {
        $this->_logger->info(__METHOD__);
        $this->success();
    }
}
