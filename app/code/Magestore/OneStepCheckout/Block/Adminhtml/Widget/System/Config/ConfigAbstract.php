<?php

/**
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */

namespace Magestore\OneStepCheckout\Block\Adminhtml\Widget\System\Config;

/**
 * Class ConfigAbstract
 * @package Magestore\OneStepCheckout\Block\Adminhtml\Widget\System\Config
 */
class ConfigAbstract extends \Magento\Backend\Block\Template
{

    /**
     * @var int
     */
    protected $_scopeId = 0;

    /**
     * @var string
     */
    protected $_scope = 'default';

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $fileSystem;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory
     */
    protected $_dataConfigCollectionFactory;

    /**
     * ConfigAbstract constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory $dataConfigCollectionFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory $dataConfigCollectionFactory,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->fileSystem = $context->getFilesystem();
        $this->_dataConfigCollectionFactory = $dataConfigCollectionFactory;
    }


    /**
     * @return \Magento\Framework\Filesystem
     */
    public function getFileSystem() {
        return $this->fileSystem;
    }
    /**
     * @param int $scopeId
     *
     * @return Position
     */
    public function setScopeId($scopeId)
    {
        $this->_scopeId = $scopeId;

        return $this;
    }

    /**
     * @param string $scope
     *
     * @return Position
     */
    public function setScope($scope)
    {
        $this->_scope = $scope;

        return $this;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->_scope;
    }

    /**
     * @return int
     */
    public function getScopeId()
    {
        return $this->_scopeId;
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();
        $storeCode = $this->getRequest()->getParam('store');
        $website = $this->getRequest()->getParam('website');
        if ($storeCode) {
            $scopeId = $this->_storeManager->getStore($storeCode)->getId();
            $scope = 'stores';
        } elseif ($website) {
            $scope = 'websites';
            $scopeId = $this->_storeManager->getWebsite($website)->getId();
        } else {
            $scope = 'default';
            $scopeId = 0;
        }

        $this->setScopeId($scopeId);
        $this->setScope($scope);

        return $this;
    }

}