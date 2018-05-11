<?php
namespace MR\PartPay\Model\ResourceModel\PaymentResult;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init('MR\PartPay\Model\PaymentResult', 'MR\PartPay\Model\ResourceModel\PaymentResult');
    }
}
