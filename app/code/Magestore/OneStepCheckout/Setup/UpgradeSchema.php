<?php

/**
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *
 */

namespace Magestore\OneStepCheckout\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        if (version_compare($context->getVersion(), '2.0.0', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('onestepcheckout_delivery'),
                'osc_security_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('sales_creditmemo'),
                'onestepcheckout_giftwrap_amount',
                'DECIMAL(12,4)'
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('sales_creditmemo'),
                'onestepcheckout_base_giftwrap_amount',
                'DECIMAL(12,4)'
            );
        }

        $installer->endSetup();
    }
}
