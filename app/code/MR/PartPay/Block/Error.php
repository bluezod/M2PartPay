<?php

namespace MR\PartPay\Block;

use Magento\Framework\View\Element\Template\Context;

class Error extends \Magento\Framework\View\Element\Template
{

    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_logger = $objectManager->get("\MR\PartPay\Logger\PartPayLogger");

        $this->_logger->info(__METHOD__);
    }

    protected function _prepareLayout()
    {
        $this->setShowContinueButton(true);
        $error = $this->getRequest()->getParam("error");
        $this->_logger->info(__METHOD__ . " error:{$error}");
        $this->setError($error);
        return $this;
    }
}
