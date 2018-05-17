<?php
namespace MR\PartPay\Model\ResourceModel\RequestToken;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init('MR\PartPay\Model\RequestToken', 'MR\PartPay\Model\ResourceModel\RequestToken');
    }
}
