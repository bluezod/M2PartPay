<?php

/**
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *
 */

namespace Magestore\OneStepCheckout\Block\Adminhtml\Widget\System\Config;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class Notification
 * @package Magestore\OneStepCheckout\Block\Adminhtml\Widget\System\Config
 */
class Notification extends \Magestore\OneStepCheckout\Block\Adminhtml\Widget\System\Config\ConfigAbstract
{
    /**
     * @var string
     */
    protected $_template = 'Magestore_OneStepCheckout::system/config/notification.phtml';


    /**
     * @return bool
     */
    public function isHasLibrary()
    {
        if (class_exists('\GeoIp2\Database\Reader')) {
            return true;
        } else {
            return false;
        }
        
    }

    /**
     * @return bool
     */
    public function isHasGeoIpDataFile()
    {
        $directory = $this->fileSystem->getDirectoryRead(DirectoryList::MEDIA);
        if ($directory->isFile('magestore/osc/GeoLite2-City.mmdb')) {
            return true;
        } else {
            return false;
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