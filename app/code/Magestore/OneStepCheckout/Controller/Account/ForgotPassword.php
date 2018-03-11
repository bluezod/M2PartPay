<?php

/**
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */

namespace Magestore\OneStepCheckout\Controller\Account;

use Magento\Customer\Model\AccountManagement;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class ForgotPassword
 *
 * @category Magestore
 * @package  Magestore_OneStepCheckout
 * @module   OneStepCheckout
 * @author   Magestore Developer
 */
class ForgotPassword extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @var AccountManagement
     */
    protected $customerAccountManagement;

    /**
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $_dataObjectFactory;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;


    /**
     * ForgotPassword constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param Session $customerSession
     * @param AccountManagement $customerAccountManagement
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Escaper $escaper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\AccountManagement $customerAccountManagement,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Escaper $escaper
    )
    {
        $this->session = $customerSession;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->_dataObjectFactory = $dataObjectFactory;
        $this->escaper = $escaper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->storeManager = $storeManager;
        $this->_jsonHelper = $jsonHelper;
        parent::__construct($context);
    }


    /**
     * @return $this
     * @throws \Exception
     * @throws \Zend_Validate_Exception
     */
    public function execute()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            return $this;
        }
        //init variable
        $result = [];
        $result['success'] = '';
        $result['errorMessage'] = '';
        $result['successMessage'] = '';

        $resultJson = $this->resultJsonFactory->create();
        $paramsData = $this->_dataObjectFactory->create([
            'data' => $this->_jsonHelper->jsonDecode($this->getRequest()->getContent()),
        ]);
        $email = $paramsData->getData('email');
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        if ($email) {
            if (!\Zend_Validate::is($email, 'EmailAddress')) {
                $this->session->setForgottenEmail($email);
                $result['success'] = 'false';
                $result['errorMessage'] = __('Please correct the email address.');
                $resultJson = $this->resultJsonFactory->create();

                return $resultJson->setData($result);
            } else {
                $customer = $this->_objectManager->create('Magento\Customer\Model\Customer')
                    ->setWebsiteId($websiteId)
                    ->loadByEmail($email);
                if ($customer->getId()) {
                    try {
                        $this->customerAccountManagement->initiatePasswordReset(
                            $email,
                            AccountManagement::EMAIL_RESET
                        );
                    } catch (NoSuchEntityException $e) {
                        $this->_objectManager->get('Psr\Log\LoggerInterface')->notice($e->getMessage());
                    } catch (\Exception $exception) {
                        $this->messageManager->addExceptionMessage(
                            $exception,
                            __('We\'re unable to send the password reset email.')
                        );
                        $result['success'] = 'false';
                        $result['errorMessage'] = __('We\'re unable to send the password reset email.');

                        return $resultJson->setData($result);
                    }
                    $result['success'] = 'true';
                    $result['successMessage'] = $this->getSuccessMessage($email);

                    return $resultJson->setData($result);
                } else {
                    $result = ['success' => false, 'errorMessage' => 'The account does not exist.'];

                    return $resultJson->setData($result);
                }
            }
        } else {
            $result['success'] = 'false';
            $result['errorMessage'] = __('Please enter your email');

            return $resultJson->setData($result);
        }
    }

    /**
     * @param $email
     *
     * @return \Magento\Framework\Phrase
     */
    protected function getSuccessMessage($email)
    {
        return __(
            'If there is an account associated with %1 you will receive an email with a link to reset your password.',
            $this->escaper->escapeHtml($email)
        );
    }
}
