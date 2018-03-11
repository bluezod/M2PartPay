<?php

/**
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */

namespace Magestore\OneStepCheckout\Block\Adminhtml\Widget\System\Config;

/**
 * Class Position
 * @package Magestore\OneStepCheckout\Block\Adminhtml\Widget\System\Config
 */
class Position extends \Magestore\OneStepCheckout\Block\Adminhtml\Widget\System\Config\ConfigAbstract
{
    /**
     * @var string
     */
    protected $_template = 'Magestore_OneStepCheckout::system/config/position.phtml';


    /**
     * @return bool
     */
    public function isHasPrefixName() {
        $prefixName = $this->_scopeConfig->getValue('customer/address/prefix_options');
        if ($prefixName) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function isHasMiddleName() {
        $middleName = $this->_scopeConfig->getValue('customer/address/middlename_show');
        if ($middleName) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function isHasSuffixName() {
        $suffix = $this->_scopeConfig->getValue('customer/address/suffix_show');
        if ($suffix) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function isHasVatId() {
        $taxVat = $this->_scopeConfig->getValue('customer/create_account/vat_frontend_visibility');
        if ($taxVat) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function isHasGender() {
        $gender = $this->_scopeConfig->getValue('customer/address/gender_show');
        if ($gender) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * @return array
     */
    public function getFieldOptions()
    {
        $fieldOptions = array(
            '0'          => __('Null'),
            'firstname'  => __('First Name'),
            'lastname'   => __('Last Name'),
            'company'    => __('Company'),
            'street'     => __('Address'),
            'country_id' => __('Country'),
            'region_id'     => __('State/Province'),
            'city'       => __('City'),
            'postcode'   => __('Zip/Postal Code'),
            'telephone'  => __('Telephone')
        );

        if ($this->isHasSuffixName()) {
            $fieldOptions['suffix'] =  __('Suffix Name');
        }

        if ($this->isHasMiddleName()) {
            $fieldOptions['middlename'] =  __('Middle Name');
        }

        if ($this->isHasPrefixName()) {
            $fieldOptions['prefix'] =  __('Prefix Name');
        }

        if ($this->isHasVatId()) {
            $fieldOptions['vat_id'] =  __('Tax/VAT number');
        }

        if ($this->isHasGender()) {
            $fieldOptions['gender'] =  __('Gender');
        }

        return $fieldOptions;
    }

    /**
     * @param $number
     *
     * @return mixed
     */
    public function getDefaultField($number, $scope, $scopeId)
    {
        return $this->_scopeConfig
            ->getValue('onestepcheckout/field_position_management/row_' . $number, $scope, $scopeId);
    }

    /**
     * @param $number
     * @param $scope
     * @param $scopeId
     *
     * @return mixed
     */
    public function getFieldEnableBackEnd($number, $scope, $scopeId)
    {
        $configCollection = $this->_dataConfigCollectionFactory->create()
            ->addFieldToFilter('scope', $scope)
            ->addFieldToFilter('scope_id', $scopeId)
            ->addFieldToFilter('path', 'onestepcheckout/field_position_management/row_' . $number);

        if (count($configCollection)) {
            return $configCollection->getFirstItem()->getData('value');
        } else {
            return null;
        }
    }

    /**
     * @param $number
     *
     * @return string
     */
    public function getElementHtmlId($number)
    {
        return 'onestepcheckout_field_position_management_row_' . $number;
    }

    /**
     * @param $number
     *
     * @return string
     */
    public function getElementHtmlName($number)
    {
        return 'groups[field_position_management][fields][row_' . $number . '][value]';
    }

    /**
     * @param $number
     *
     * @return string
     */
    public function getCheckBoxElementHtmlId($number)
    {
        return 'onestepcheckout_field_position_management_row_' . $number . '_inherit';
    }

    /**
     * @param $number
     *
     * @return string
     */
    public function getCheckBoxElementHtmlName($number)
    {
        return 'groups[field_position_management][fields][row_' . $number . '][inherit]';
    }
}