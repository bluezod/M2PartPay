<?php
namespace PaymentExpress\PxPay2\Model\ResourceModel\PaymentResult;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init('PaymentExpress\PxPay2\Model\PaymentResult', 'PaymentExpress\PxPay2\Model\ResourceModel\PaymentResult');
    }
}
