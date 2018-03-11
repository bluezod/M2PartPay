<?php
/**
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *
 */

namespace Magestore\OneStepCheckout\Helper;
use Magento\Framework\App\Filesystem\DirectoryList;
/**
 * Class DetectCountry
 * @package Magestore\OneStepCheckout\GeoIp
 */
class DetectCountry {

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $_remoteAddress;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $fileSystem;

    /**
     * DetectCountry constructor.
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Filesystem $filesystem
     */
    public function __construct(
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Filesystem $filesystem
    )
    {
        $this->_remoteAddress = $remoteAddress;
        $this->urlBuilder = $urlBuilder;
        $this->fileSystem = $filesystem;
    }

    /**
     * @return string
     */
    public function detect()
    {
        $remoteAddress = $this->_remoteAddress->getRemoteAddress();
        try {
            if (class_exists('\GeoIp2\Database\Reader')) {
                $directory = $this->fileSystem->getDirectoryRead(DirectoryList::MEDIA);
                if ($directory->isFile('magestore/osc/GeoLite2-City.mmdb')) {
                    $reader = new \GeoIp2\Database\Reader($this->getGeoIpDataFile());
                    $record = $reader->city($remoteAddress);
                    $information = array();
                    if ($record) {
                        $information['country_id'] = $record->country->isoCode;
                        $information['city'] = $record->city->name;
                        $information['region'] = $record->mostSpecificSubdivision->name;
                        $information['postcode'] = $record->postal->code;
                        return $information;
                    } else {
                        return null;
                    }
                }
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getGeoIpDataFile() {
        $mediaDirectory = $this->fileSystem->getDirectoryRead(DirectoryList::MEDIA);
        $url = $mediaDirectory->getAbsolutePath('magestore/osc/GeoLite2-City.mmdb');
        return $url;
    }
}