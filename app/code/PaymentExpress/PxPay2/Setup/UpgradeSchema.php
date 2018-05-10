<?php
namespace PaymentExpress\PxPay2\Setup;

use \Magento\Framework\Setup\UpgradeSchemaInterface;
use \Magento\Framework\Setup\ModuleContextInterface;
use \Magento\Framework\Setup\SchemaSetupInterface;
use \Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        // reference: http://magento.stackexchange.com/questions/86085/magento2-how-to-database-schema-upgrade
        $setup->startSetup();

        $data = [];
        $data[] = [
            'status' => 'paymentexpress_unpaid',
            'label' => __('Unpaid')
        ];
        if (version_compare($context->getVersion(), '0.5.28.0') < 0) {
            $setup->getConnection()->insertArray(
                $setup->getTable('sales_order_status'),
                [ 'status', 'label' ],
                $data
            );
        }

        if (version_compare($context->getVersion(), '0.5.31.10') < 0) {
            $this->_createPaymentResultTalbe($setup);
        }

        if (version_compare($context->getVersion(), '0.5.31.15') < 0) {
            $this->_addUserInfoColumnsToResultTable($setup);
        }

        $setup->endSetup();
    }

    private function _createPaymentResultTalbe(SchemaSetupInterface $installer)
    {
        $tableName = $installer->getTable('paymentexpress_paymentresult');
        // if exists, then this module should be installed before, just skip it. Use upgrade command to updata the table.
        if ($installer->getConnection()->isTableExists($tableName) !== true) {
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
                    'quote_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true
                    ],
                    'Quote Id'
                )
                ->addColumn(
                    'reserved_order_id',
                    Table::TYPE_TEXT,
                    64,
                    [
                        'nullable' => false,
                        'unsigned' => true
                    ],
                    'Order Increment Id'
                )
                ->addColumn(
                    'method',
                    Table::TYPE_TEXT,
                    64,
                    [
                        'nullable' => false,
                        'default' => ''
                    ],
                    'Payment Method'
                )
                ->addColumn(
                    'updated_time',
                    Table::TYPE_DATETIME,
                    null,
                    [
                        'nullable' => false
                    ],
                    'PaymentResponse'
                )
                ->addColumn(
                    'dps_transaction_type',
                    Table::TYPE_TEXT,
                    16,
                    [
                        'nullable' => false,
                        'default' => ''
                    ],
                    'Transaction Type'
                )
                ->addColumn(
                    'dps_txn_ref',
                    Table::TYPE_TEXT,
                    128,
                    [
                        'nullable' => false,
                        'default' => ''
                    ],
                    'DPSTxnRef'
                )
                ->addColumn(
                    'raw_xml',
                    Table::TYPE_TEXT,
                    2048,
                    [
                        'nullable' => false,
                        'default' => ''
                    ],
                    'PaymentResponse'
                )
                ->setComment('Payment Express BillingToken');
            $installer->getConnection()->createTable($table);
        }
    }

    private function _addUserInfoColumnsToResultTable(SchemaSetupInterface $installer)
    {
        $tableName = $installer->getTable('paymentexpress_paymentresult');
        $columns = [
            'user_name' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => '255',
                'nullable' => true,
                'comment' => 'PxPay/PxFusion user'
            ],
            'token' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => '255',
                'nullable' => true,
                'comment' => 'PxPay/PxFusion token'
            ]
        ];

        $connection = $installer->getConnection();
        foreach ($columns as $name => $definition) {
            $connection->addColumn($tableName, $name, $definition);
        }
    }
}
