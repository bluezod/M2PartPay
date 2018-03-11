<?php
/**
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_OneStepCheckout
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */
namespace Magestore\OneStepCheckout\Setup;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
/**
 * Class InstallSchema
 *
 * @category Magestore
 * @package  Magestore_OneStepCheckout
 * @module   OneStepCheckout
 * @author   Magestore Developer
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     *
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $setup->getConnection()->dropTable($setup->getTable('onestepcheckout_delivery'));
        $setup->getConnection()->dropTable($setup->getTable('onestepcheckout_survey'));
        $table = $installer->getConnection()->newTable(
            $installer->getTable('onestepcheckout_delivery')
        )->addColumn(
            'delivery_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            NULL,
            ['identity' => TRUE, 'unsigned' => TRUE, 'nullable' => FALSE, 'primary' => TRUE],
            'delivery_id'
        )->addColumn(
            'delivery_time_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '16',
            ['nullable' => TRUE, 'default' => ''],
            'delivery_time_date'
        )->addColumn(
            'order_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            NULL,
            ['nullable' => TRUE, 'default' => 0],
            'order_id'
        );
        $installer->getConnection()->createTable($table);
        $table = $installer->getConnection()->newTable(
            $installer->getTable('onestepcheckout_survey')
        )->addColumn(
            'survey_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            NULL,
            ['identity' => TRUE, 'unsigned' => TRUE, 'nullable' => FALSE, 'primary' => TRUE],
            'survey_id'
        )->addColumn(
            'question',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '255',
            ['nullable' => TRUE, 'default' => ''],
            'question'
        )->addColumn(
            'answer',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '255',
            ['nullable' => TRUE, 'default' => ''],
            'answer'
        )->addColumn(
            'order_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            NULL,
            ['nullable' => FALSE, 'unsigned' => TRUE, 'default' => 0],
            'order_id'
        );
        $installer->getConnection()->createTable($table);
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order'),
            'onestepcheckout_order_comment',
            'text NULL'
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order'),
            'onestepcheckout_giftwrap_amount',
            'DECIMAL(12,4)'
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order'),
            'onestepcheckout_base_giftwrap_amount',
            'DECIMAL(12,4)'
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_invoice'),
            'onestepcheckout_giftwrap_amount',
            'DECIMAL(12,4)'
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_invoice'),
            'onestepcheckout_base_giftwrap_amount',
            'DECIMAL(12,4)'
        );
        $installer->endSetup();
    }
}