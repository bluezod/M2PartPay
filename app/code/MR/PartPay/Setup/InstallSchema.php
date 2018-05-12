<?php

namespace MR\PartPay\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        $mrPartPayTableName = $installer->getTable('mr_partpay');
        // if exists, then this module should be installed before, just skip it. Use upgrade command to updata the table.
        if ($installer->getConnection()->isTableExists($mrPartPayTableName) != true) {
            $mrPartPayTable = $installer->getConnection()
                ->newTable($mrPartPayTableName)
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    11,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                    ],
                    'ID'
                )
                ->addColumn(
                    'token',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'Token'
                )
                ->addColumn(
                    'url',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'URL'
                )
                ->addColumn(
                    'expire',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'Expire'
                )
                ->addColumn(
                    'order_id',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'Order ID'
                )
                ->addColumn(
                    'partpay_id',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'PartPay ID'
                )
                ->addIndex('token', 'token')
                ->addIndex('order_id', 'order_id')
                ->addIndex('partpay_id', 'partpay_id')
                ->setComment('PartPay Orders Token Info etc.');
            $installer->getConnection()->createTable($mrPartPayTable);
        }


        $configurationTableName = $installer->getTable('mr_partpay_configuration');
        // if exists, then this module should be installed before, just skip it. Use upgrade command to updata the table.
        if ($installer->getConnection()->isTableExists($configurationTableName) != true) {
            $configurationTable = $installer->getConnection()
                ->newTable($configurationTableName)
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    11,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                    ],
                    'ID'
                )
                ->addColumn(
                    'store_id',
                    Table::TYPE_INTEGER,
                    11,
                    [],
                    'Store ID'
                )
                ->addColumn(
                    'min',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'Min'
                )
                ->addColumn(
                    'max',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'Max'
                )
                ->setComment('PartPay Configuration Table');
            $installer->getConnection()->createTable($configurationTable);
        }

        $installer->endSetup();

    }
}
