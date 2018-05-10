<?php
namespace PaymentExpress\PxPay2\Block\PxFusion\Adminhtml;

use \Magento\Framework\View\Element\Template\Context;

class Form extends \Magento\Payment\Block\Form
{
    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
        $this->_template = 'PaymentExpress_PxPay2::pxfusion/form.phtml';
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        // $this->_checkoutSession = $objectManager->get("\Magento\Checkout\Model\Session");
        $this->_logger = $objectManager->get("\PaymentExpress\PxPay2\Logger\DpsLogger");
        $now = new \DateTime('now');
        
        $this->setData("expiry_month", date('n'));
        $this->setData("expiry_year", (date('Y') - 2000));
        
        // do not submit
        $quoteSession = $objectManager->get("\Magento\Backend\Model\Session\Quote");
        $quote = $quoteSession->getQuote();
        $quoteId = $quote->getId();
        $this->_logger->info(__METHOD__ . " quoteId: {$quoteId}");
        
        $this->_logger->info(__METHOD__ . "  " . $this->getData('expiry_year'));
    }

    /**
     * Retrieve expiry months
     *
     * @return array
     */
    public function getExpiryMonths()
    {
        $this->_logger->info(__METHOD__);
        $months = $this->getData('expiry_months');
        if ($months === null) {
            $months = [
                0 => __('Month')
            ];
            $this->_logger->info(__METHOD__);
            for ($index = 1; $index < 13; $index++) {
                $item = sprintf("%02d", $index);
                $months[$item] = $item;
            }
            $this->setData('expiry_months', $months);
        }
        
        return $months;
    }

    /**
     * Retrieve expiry years
     *
     * @return array
     */
    public function getExpiryYears()
    {
        $this->_logger->info(__METHOD__);
        $years = $this->getData('expiry_years');
        if ($years === null) {
            $years = [0 => __('Year')];
            $first = date('Y');//(int)$this->_date->date('Y');
            for ($index = 0; $index <= 10; $index++) {
                $year = $first + $index;
                $key =  sprintf("%02d", ($year - 2000));
                $years[$key] = $year;
            }
            return $years;
            
            $this->setData('expiry_years', $years);
        }
        return $years;
    }

    public function getServiceUrl()
    {
        // $urlBuilder = \Magento\Framework\App\ObjectManager::getInstance()->get("\Magento\Framework\Url");
        // $url = $this->getUrl("/pxpay2/pxfusion/createtransaction", ['_secure' => true]);
        $url = $this->getUrl("pxpay2/pxfusion/createtransaction", [
            '_secure' => true
        ]);
        $this->_logger->info(__METHOD__ . " url: {$url}");
        return $url;
    }
}
