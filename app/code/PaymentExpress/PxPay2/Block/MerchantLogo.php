<?php
namespace PaymentExpress\PxPay2\Block;

class MerchantLogo extends \Magento\Framework\View\Element\Template
{

    /**
     *
     * @var string
     */
    private $_logoPrefix;

    /**
     *
     * @var \PaymentExpress\PxPay2\Helper\Configuration
     */
    private $_configuration;

    /**
     *
     * @var \Magento\MediaStorage\Helper\File\Storage\Database
     */
    private $_fileStorageHelper;

    /**
     *
     * @param \Magento\Framework\View\Element\Template\Context   $context
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageHelper
     * @param \PaymentExpress\PxPay2\Helper\Configuration        $configuration
     * @param array                                              $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context, 
        \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageHelper, 
        \PaymentExpress\PxPay2\Helper\Configuration $configuration, 
        array $data = []
    ) {
        $this->_fileStorageHelper = $fileStorageHelper;
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        parent::__construct($context, $data);
        $this->_logger = $objectManager->get("\PaymentExpress\PxPay2\Logger\DpsLogger");
        $this->_configuration = $configuration;
        $this->_logger->info(__METHOD__);
    }

    public function setLogoPathPrefix($logoPrefix)
    {
        $this->_logoPrefix = $logoPrefix;
        $this->_logger->info(__METHOD__ . " logoPrefix:" . $this->_logoPrefix);
    }

    /**
     * Get logo image URL
     *
     * @return string
     */
    public function getLogoUrl()
    {
        $this->_logger->info(__METHOD__);
        $folderName = \PaymentExpress\PxPay2\Model\Config\Backend\MerchantLogo::UPLOAD_DIR;
        $storeLogoPath = $this->_configuration->getLogoSource($this->_logoPrefix);
        $path = $folderName . '/' . $storeLogoPath;
        $logoUrl = $this->_urlBuilder->getBaseUrl(
            [
            '_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            ]
        ) . $path;
        
        $url = "";
        if ($storeLogoPath !== null && $this->_isFile($path)) {
            $url = $logoUrl;
        } elseif ($this->getLogoFile()) {
            $url = $this->getViewFileUrl($this->getLogoFile());
        }
        $this->_logger->info(__METHOD__ . " url:{$url}");
        return $url;
    }

    /**
     * Retrieve logo text
     *
     * @return string
     */
    public function getLogoAlt()
    {
        $this->_logger->info(__METHOD__);
        return $this->_configuration->getLogoAlt($this->_logoPrefix);
    }

    /**
     * Retrieve logo width
     *
     * @return int
     */
    public function getLogoWidth()
    {
        $this->_logger->info(__METHOD__);
        return $this->_configuration->getLogoWidth($this->_logoPrefix);
    }

    /**
     * Retrieve logo height
     *
     * @return int
     */
    public function getLogoHeight()
    {
        $this->_logger->info(__METHOD__);
        return $this->_configuration->getLogoHeight($this->_logoPrefix);
    }

    /**
     * If DB file storage is on - find there, otherwise - just file_exists
     *
     * @param  string $filename
     *            relative path
     * @return bool
     */
    private function _isFile($filename)
    {
        $this->_logger->info(__METHOD__);
        if ($this->_fileStorageHelper->checkDbUsage() && !$this->getMediaDirectory()->isFile($filename)) {
            $this->_fileStorageHelper->saveFileToFilesystem($filename);
        }
        
        return $this->getMediaDirectory()->isFile($filename);
    }
}
