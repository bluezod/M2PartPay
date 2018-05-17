<?php

namespace MR\PartPay\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class Communication extends AbstractHelper
{
    /**
     *
     * @var \MR\PartPay\Helper\PaymentUtil
     */
    private $_paymentUtil;

    private $_accessToken;

    private $_date;

    /**
     *
     * @var \MR\PartPay\Helper\Configuration
     */
    private $_configuration;

    public function __construct(Context $context, \Magento\Framework\Stdlib\DateTime\DateTime $date)
    {
        parent::__construct($context);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_logger = $objectManager->get("\MR\PartPay\Logger\PartPayLogger");
        $this->_configuration = $objectManager->get("\MR\PartPay\Helper\Configuration");
        $this->_paymentUtil = $objectManager->get("\MR\PartPay\Helper\PaymentUtil");
        $this->_logger->info(__METHOD__);
        $this->_accessToken = null;
        $this->_date = $date;
    }

    public function getPartPayPage($requestData, $storeId = null)
    {
        $this->_logger->info(__METHOD__);

        $orderIncrementId = $requestData['merchantReference'];
        $requestData['merchant']['redirectConfirmUrl'] = $this->_getUrl('partpay/order/success', ['_secure' => true, '_nosid' => true, 'mage_order_id' => $orderIncrementId]);
        $requestData['merchant']['redirectCancelUrl'] = $this->_getUrl('partpay/order/fail', ['_secure' => true, '_nosid' => true, 'mage_order_id' => $orderIncrementId]);

        $this->_logger->info(__METHOD__ . " request: ". json_encode($requestData));
        $url = $this->_getApiUrl('order', $storeId);
        $header = ['Content-Type: application/json', 'Authorization: Bearer ' . $this->_getAccessToken($storeId)];
        $response = $this->_sendRequest($url, $header, [], \Magento\Framework\HTTP\ZendClient::POST, json_encode($requestData));
        return json_decode($response, true);
    }

    public function getTransactionStatus($partpayId, $storeId = null)
    {
        $this->_logger->info(__METHOD__ . " partpayId:{$partpayId} storeId:{$storeId}");

        $partPayUrl = $this->_getApiUrl('/order/'. $partpayId, $storeId);
        $header = ['Authorization: Bearer ' . $this->_getAccessToken($storeId)];
        $response = $this->_sendRequest($partPayUrl, $header);

        $this->_logger->info(__METHOD__ . " response:" . $response);
        return json_decode($response, true);
    }

    public function refund($orderIncrementId, $partpayId, $amount, $storeId = null)
    {
        $this->_logger->info(__METHOD__ . "order:{$orderIncrementId} partpayId:{$partpayId} storeId:{$storeId}");


        $requestData = [
            'amount'=> $amount,
            'merchantRefundReference' => $orderIncrementId.'-'.$amount.' '.$this->_date->date(),
            ];
        $partPayUrl = $this->_getApiUrl('/order/' . $partpayId . '/refund/', $storeId);
        $header = ['Content-Type: application/json', 'Authorization: Bearer ' . $this->_getAccessToken($storeId)];
        $response = $this->_sendRequest($partPayUrl, $header, [],\Magento\Framework\HTTP\ZendClient::POST, $requestData);

        $this->_logger->info(__METHOD__ . " response:" . $response);
        return json_decode($response, true);
    }

    protected function _getAccessToken($storeId = null)
    {
        if (!$this->_accessToken) {
            $accessTokenParam = [
                'grant_type' => 'client_credentials',
                'client_id' => $this->_configuration->getPartPayClientId($storeId),
                'client_secret' => $this->_configuration->getPartPayClientSecret($storeId),
                'audience' => $this->_configuration->getPartPayApiAudience($storeId),
            ];

            $headers = [
                'Content-Type: application/json'
            ];
            $url = $this->_configuration->getPartPayAuthTokenEndpoint($storeId);

            try {
                $accessTokenResult = json_decode($this->_sendRequest($url, $headers, [], \Magento\Framework\HTTP\ZendClient::POST, json_encode($accessTokenParam)), true);
            } catch (\Exception $ex) {
                $this->_logger->error($ex->getMessage());
                return false;
            }
            if (!$accessTokenResult || !isset($accessTokenResult['access_token'])) {
                $errorMessage = 'Can\'t get PartPay AccessToken with the credential.';
                throw new \Magento\Framework\Exception\PaymentException(__($errorMessage));
            }
            $this->_accessToken = $accessTokenResult['access_token'];
        }
        return $this->_accessToken;
    }

    protected function _getApiUrl($path, $storeId = null)
    {
        $baseUrl = $this->_configuration->getPartPayApiEndpoint($storeId);
        $apiUrl = rtrim($baseUrl, '/') . '/' . trim($path, '/');
        return $apiUrl;
    }

    private function _sendRequest($url, $header = [], $params = [], $method = \Magento\Framework\HTTP\ZendClient::GET, $postBody = null)
    {
        $this->_logger->info(__METHOD__ . " postUrl: {$url}");
        $ch = curl_init();
        switch ($method) {
            case "POST":
                curl_setopt($ch, CURLOPT_POST, 1);

                if ($postBody)
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postBody);
                break;
            case "PUT":
                curl_setopt($ch, CURLOPT_PUT, 1);
                break;
            default:
                if (!empty($params))
                    $url = sprintf("%s?%s", $url, http_build_query($params));
        }
        curl_setopt($ch, CURLOPT_URL, $url);

        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        if (!$response) {
            $errorMessage = " Error:" . curl_error($ch) . " Error Code:" . curl_errno($ch);
            $this->_logger->critical(__METHOD__ . $errorMessage);
        } else {
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpcode && substr($httpcode, 0, 2) != "20") {
                $errorMessage = " HTTP CODE: {$httpcode} for URL: {$url}";
                $this->_logger->critical(__METHOD__ . $errorMessage);
            }
        }
        curl_close($ch);

        $this->_logger->info(__METHOD__ . " response from PartPay:" . $response);

        return $response;
    }

    private function _buildRefundRequestXml($amount, $currency, $dpsTxnRef, $storeId = null)
    {
        $this->_logger->info(__METHOD__);
        return $requestObject = $this->_buildPxPostRequestXml($amount, $currency, $dpsTxnRef, "Refund", $storeId);
    }
}
