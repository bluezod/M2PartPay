<?php

/**
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */

namespace Magestore\OneStepCheckout\Controller\Account;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;

/**
 * Class Login
 *
 * @category Magestore
 * @package  Magestore_OneStepCheckout
 * @module   OneStepCheckout
 * @author   Magestore Developer
 */
class Login extends \Magento\Framework\App\Action\Action
{
    /**
     * @var AccountManagementInterface
     */
    protected $_customerAccountManagement;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $_resultRawFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $_dataObjectFactory;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;


    /**
     * Login constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param AccountManagementInterface $accountManagement
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Customer\Api\AccountManagementInterface $accountManagement
    ) {
        parent::__construct($context);
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_resultRawFactory = $resultRawFactory;
        $this->_customerAccountManagement = $accountManagement;
        $this->_customerSession = $customerSession;
        $this->_dataObjectFactory = $dataObjectFactory;
        $this->_jsonHelper = $jsonHelper;
    }

    /**
     * @return $this
     */
    public function execute()
    {
        $credentials = null;
        $httpBadRequestCode = 400;

        $resultRaw = $this->_resultRawFactory->create();
        try {
            $paramsData = $this->_getParamDataObject();

            $username = $paramsData->getData('username');
            $password = $paramsData->getData('password');
            $credentials['username'] = $username;
            $credentials['password'] = $password;
        } catch (\Exception $e) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }
        if (!$credentials || $this->getRequest()->getMethod() !== 'POST' || !$this->getRequest()->isXmlHttpRequest()) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        $response = [
            'errors'  => false,
            'message' => __('Login successful.'),
        ];
        try {
            $customer = $this->_customerAccountManagement->authenticate(
                $credentials['username'],
                $credentials['password']
            );
            $this->_customerSession->setCustomerDataAsLoggedIn($customer);
            $this->_customerSession->regenerateId();
        } catch (EmailNotConfirmedException $e) {
            $response = [
                'errors'  => true,
                'message' => $e->getMessage(),
            ];
        } catch (InvalidEmailOrPasswordException $e) {
            $response = [
                'errors'  => true,
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            $response = [
                'errors'  => true,
                'message' => __('Invalid login or password.'),
            ];
        }

        $resultJson = $this->_resultJsonFactory->create();

        return $resultJson->setData($response);
    }

    /**
     * @return mixed
     */
    protected function _getParamDataObject()
    {
        return $this->_dataObjectFactory->create([
            'data' => $this->_jsonHelper->jsonDecode($this->getRequest()->getContent()),
        ]);
    }

}
