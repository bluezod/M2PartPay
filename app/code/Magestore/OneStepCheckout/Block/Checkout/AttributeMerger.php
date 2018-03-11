<?php
/**
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */
namespace Magestore\OneStepCheckout\Block\Checkout;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Helper\Address as AddressHelper;
use Magestore\OneStepCheckout\Helper\Config as OneStepConfig;
use Magento\Directory\Model\ResourceModel\Region\Collection as RegionCollection;

/**
 * Class AttributeMerger
 * @package Magestore\OneStepCheckout\Block\Checkout
 */
class AttributeMerger extends \Magento\Checkout\Block\Checkout\AttributeMerger
{
    /**
     * @var OneStepConfig
     */
    protected $_oneStepConfig;
    /**
     * @var RegionCollection
     */
    protected $_regionCollection;
    /**
     * @var DirectoryHelper
     */
    protected $_directoryHelper;

    /**
     * AttributeMerger constructor.
     * @param AddressHelper $addressHelper
     * @param Session $customerSession
     * @param CustomerRepository $customerRepository
     * @param DirectoryHelper $directoryHelper
     * @param OneStepConfig $oneStepConfig
     * @param RegionCollection $regionCollection
     */
    public function __construct(
        AddressHelper $addressHelper,
        Session $customerSession,
        CustomerRepository $customerRepository,
        DirectoryHelper $directoryHelper,
        OneStepConfig $oneStepConfig,
        RegionCollection $regionCollection
    )
    {
        $this->_oneStepConfig = $oneStepConfig;
        $this->_regionCollection = $regionCollection;
        $this->_directoryHelper = $directoryHelper;
        parent::__construct($addressHelper, $customerSession, $customerRepository, $directoryHelper);
    }

    /**
     * @param string $attributeCode
     * @return null|string
     */
    protected function getDefaultValue($attributeCode)
    {
        $defaultInformation = $this->_oneStepConfig->getDefaultAddressInformation();
        if ($this->_oneStepConfig->getFullRequest() == 'checkout_index_index') {
            switch ($attributeCode) {
                case 'firstname':
                    if ($this->getCustomer()) {
                        return $this->getCustomer()->getFirstname();
                    }
                    break;
                case 'lastname':
                    if ($this->getCustomer()) {
                        return $this->getCustomer()->getLastname();
                    }
                    break;
                case 'country_id':
                    if ($defaultInformation['country_id']) {
                        return $defaultInformation['country_id'];
                    } else {
                        return $this->_directoryHelper->getDefaultCountry();
                    }

                case 'region_id':
                    return $defaultInformation['region_id'];
                case 'postcode':
                    return $defaultInformation['postcode'];
                case 'city':
                    return $defaultInformation['city'];
            }
            return null;
        } else {
            return parent::getDefaultValue($attributeCode);
        }
    }
}
