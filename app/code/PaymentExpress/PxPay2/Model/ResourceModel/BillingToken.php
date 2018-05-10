<?php
namespace PaymentExpress\PxPay2\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class BillingToken extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('paymentexpress_billingtoken', 'entity_id');
    }
}
