<?php
namespace PaymentExpress\PxPay2\Model\ResourceModel\BillingToken;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    protected function _construct()
    {
        $this->_init('PaymentExpress\PxPay2\Model\BillingToken', 'PaymentExpress\PxPay2\Model\ResourceModel\BillingToken');
    }
}
