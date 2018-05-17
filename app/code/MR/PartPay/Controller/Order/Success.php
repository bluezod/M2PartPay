<?php

namespace MR\PartPay\Controller\Order;

use Magento\Framework\App\Action\Context;

class Success extends CommonAction
{

    /**
     *
     * @var \MR\PartPay\Logger\PartPayLogger
     */
    private $_logger;

    public function __construct(Context $context)
    {
        parent::__construct($context);
        $this->_logger = $this->_objectManager->get("\MR\PartPay\Logger\PartPayLogger");
        $this->_logger->info(__METHOD__);
    }

    public function execute()
    {
        $this->_logger->info(__METHOD__);
        $this->success();
    }
}
