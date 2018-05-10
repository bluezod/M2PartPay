<?php
namespace PaymentExpress\PxPay2\Setup;

use \Magento\Framework\Setup\InstallSchemaInterface;
use \Magento\Framework\Setup\ModuleContextInterface;
use \Magento\Framework\Setup\SchemaSetupInterface;
use \Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $tableName = $installer->getTable('paymentexpress_billingtoken');
        // if exists, then this module should be installed before, just skip it. Use upgrade command to updata the table.
        if ($installer->getConnection()->isTableExists($tableName) != true) {
            $table = $installer->getConnection()
                ->newTable($tableName)
                ->addColumn(
                    'entity_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                    ],
                    'ID'
                )
                ->addColumn(
                    'customer_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true
                    ],
                    'Customer Id'
                )
                ->addColumn(
                    'order_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true
                    ],
                    'Order Id'
                )
                ->addColumn(
                    'store_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true
                    ],
                    'Store Id'
                )
                ->addColumn(
                    'masked_card_number',
                    Table::TYPE_TEXT,
                    32,
                    [
                        'nullable' => false,
                        'default' => ''
                    ],
                    'Masked Card Number'
                )
                ->addColumn(
                    'cc_expiry_date',
                    Table::TYPE_TEXT,
                    5,
                    [
                        'nullable' => false,
                        'default' => ''
                    ],
                    'Credit Card Expiry Date'
                )
                ->addColumn(
                    'dps_billing_id',
                    Table::TYPE_TEXT,
                    32,
                    [
                        'nullable' => false,
                        'default' => ''
                    ],
                    'DPS Billing Id'
                )
                ->setComment('Payment Express BillingToken');
            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}
