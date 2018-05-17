<?php

namespace MR\PartPay\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class Communication extends AbstractHelper
{

    private $_sensitiveFields = [
        "PxPayKey",
        "PostPassword"
    ];

    /**
     *
     * @var \MR\PartPay\Helper\PaymentUtil
     */
    private $_paymentUtil;

    private $_accessToken;

    /**
     *
     * @var \MR\PartPay\Helper\Configuration
     */
    private $_configuration;

    public function __construct(Context $context)
    {
        parent::__construct($context);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_logger = $objectManager->get("\MR\PartPay\Logger\PartPayLogger");
        $this->_configuration = $objectManager->get("\MR\PartPay\Helper\Configuration");
        $this->_paymentUtil = $objectManager->get("\MR\PartPay\Helper\PaymentUtil");
        $this->_logger->info(__METHOD__);
        $this->_accessToken = null;
    }

    public function getPartPayPage($requestData, $storeId = null)
    {
        $this->_logger->info(__METHOD__);

        $orderIncrementId = $requestData['merchantReference'];
        $requestData['merchant']['redirectConfirmUrl'] = $this->_getUrl('partpay/order/success', ['_secure' => true, '_nosid' => true, 'order_id' => $orderIncrementId]);
        $requestData['merchant']['redirectCancelUrl'] = $this->_getUrl('partpay/order/fail', ['_secure' => true, '_nosid' => true, 'order_id' => $orderIncrementId]);

        $this->_logger->info(__METHOD__ . " request: ". json_encode($requestData));
        $url = $this->_getApiUrl('order', $storeId);
        $header = ['Content-Type: application/json', 'Authorization: Bearer ' . $this->_getAccessToken($storeId)];
        $response = $this->_sendRequest($url, $header, [], \Magento\Framework\HTTP\ZendClient::POST, json_encode($requestData));
        return json_decode($response, true);
    }

    public function getTransactionStatus($userId, $token, $storeId = null)
    {
        $this->_logger->info(__METHOD__ . " pxPayUserId:{$userId} storeId:{$storeId}");
        $requestXml = $this->_buildProcessResponseRequest($userId, $token);

        $pxPayUrl = $this->_configuration->getPartPayApiEndpoint($storeId);
        $responseXml = $this->_sendRequest($requestXml, $pxPayUrl);

        $this->_logger->info(__METHOD__ . " responseXml:" . $responseXml);
        return $responseXml;
    }

    public function refund($amount, $currency, $dpsTxnRef, $storeId)
    {
        $this->_logger->info(__METHOD__ . " amount:{$amount} currency:{$currency} dpsTxnRef:{$dpsTxnRef} storeId:{$storeId}");
        $requestXml = $this->_buildRefundRequestXml($amount, $currency, $dpsTxnRef, $storeId);
        $url = $this->_configuration->getPxPostUrl($storeId);

        return $this->_sendRequest($requestXml, $url);
    }

    public function complete($amount, $currency, $dpsTxnRef, $storeId)
    {
        $this->_logger->info(__METHOD__ . " amount:{$amount} currency:{$currency} dpsTxnRef:{$dpsTxnRef} storeId:{$storeId}");
        $requestXml = $this->_buildCompleteRequestXml($amount, $currency, $dpsTxnRef, $storeId);
        $url = $this->_configuration->getPxPostUrl($storeId);

        return $this->_sendRequest($requestXml, $url);
    }

    private function _buildProcessResponseRequest($userId, $token)
    {
        $this->_logger->info(__METHOD__ . " pxPayUserId:{$userId} token:{$token}");
        $pxPayKey = "";
        if ($userId == $this->_configuration->getPartPayClientId()) {
            $pxPayKey = $this->_configuration->getPartPayClientSecret();
        }

        $requestObject = new \SimpleXMLElement("<ProcessResponse></ProcessResponse>");
        $requestObject->addChild("PxPayUserId", $userId);
        $requestObject->addChild("PxPayKey", $pxPayKey);
        $requestObject->addChild("Response", $token);
        $requestObject->addChild("ClientVersion", $this->_configuration->getModuleVersion());

        $requestXml = $requestObject->asXML();

        $this->_logger->info(__METHOD__ . " request: {$this->_obscureSensitiveFields($requestObject)}");

        return $requestXml;
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

    protected function _parseResult($response)
    {
        return json_decode($response->getBody());
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

    private function _buildCompleteRequestXml($amount, $currency, $dpsTxnRef, $storeId = null)
    {
        $this->_logger->info(__METHOD__);
        return $this->_buildPxPostRequestXml($amount, $currency, $dpsTxnRef, "Complete", $storeId);
    }

    private function _buildPxPostRequestXml($amount, $currency, $dpsTxnRef, $dpsTxnType, $storeId)
    {
        // <Txn>
        // <PostUsername>Pxpay_HubertFu</PostUsername>
        // <PostPassword>TestPassword</PostPassword>
        // <Amount>1.23</Amount>
        // <InputCurrency>NZD</InputCurrency>
        // <TxnType>Complete</TxnType>
        // <DpsTxnRef>000000600000005b</DpsTxnRef>
        // </Txn>
        $this->_logger->info(__METHOD__);
        $username = $this->_configuration->getPxPostUsername($storeId);
        $password = $this->_configuration->getPxPassword($storeId);

        $formattedAmount = $this->_paymentUtil->formatCurrency($amount, $currency);
        $requestObject = new \SimpleXMLElement("<Txn></Txn>");
        $requestObject->addChild("PostUsername", $username);
        $requestObject->addChild("PostPassword", $password);
        $requestObject->addChild("InputCurrency", $currency);
        $requestObject->addChild("Amount", $formattedAmount);
        $requestObject->addChild("DpsTxnRef", $dpsTxnRef);
        $requestObject->addChild("TxnType", $dpsTxnType);
        $requestObject->addChild("ClientVersion", $this->_configuration->getModuleVersion());

        $requestXml = $requestObject->asXML();

        $this->_logger->info(__METHOD__ . " request: {$this->_obscureSensitiveFields($requestObject)}");

        return $requestXml;
    }

    private function _obscureSensitiveFields($requestObject)
    {
        foreach ($requestObject->children() as $child) {
            $name = $child->getName();
            if (in_array($name, $this->_sensitiveFields)) {
                $child[0] = "****";
            }
        }
        return $requestObject->asXML();
    }
}
