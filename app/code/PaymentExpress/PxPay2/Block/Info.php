<?php
namespace PaymentExpress\PxPay2\Block;

use \Magento\Framework\View\Element\Template\Context;

class Info extends \Magento\Payment\Block\Info
{

    /**
     *
     * @var string
     */
    protected $_template = 'PaymentExpress_PxPay2::info/default.phtml';

    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_logger = $objectManager->get("\PaymentExpress\PxPay2\Logger\DpsLogger");
        $this->_logger->info(__METHOD__);
    }

    protected function _prepareSpecificInformation($transport = null)
    {
        $this->_logger->info(__METHOD__);
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }

        $data = $this->getInfo()->getAdditionalInformation();
        $decodedData = [];
        foreach ($data as $key => $value) {
        	if (strtotime($key)) {
                $decodedValue = json_decode($value, true);
                // TODO: deprecate unserialize completely
                if (json_last_error() != JSON_ERROR_NONE)
                    $decodedValue = unserialize($value);
        		$decodedData[$key] = $decodedValue;
        	}
        	else {
        		$decodedData[$key] = $value;
        	}
        }
        
        $transport = parent::_prepareSpecificInformation($transport);

        unset($decodedData["Currency"]);
        $this->_paymentSpecificInformation = $transport->setData(array_merge($decodedData, $transport->getData()));

        return $this->_paymentSpecificInformation;
    }
}