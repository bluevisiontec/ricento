<?php

/**
 * ricardo.ch AG - Switzerland
 *
 * @author      Sylvain Rayé <support at diglin.com>
 * @category    Diglin
 * @package     Diglin_Ricento
 * @copyright   Copyright (c) 2014 ricardo.ch AG (http://www.ricardo.ch)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
abstract class Diglin_Ricento_Controller_Adminhtml_Action extends Mage_Adminhtml_Controller_Action
{
    protected function _construct()
    {
        // Important to get appropriate translation from this module
        $this->setUsedModuleName('Diglin_Ricento');
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('ricento/listing');
    }

    /**
     * @return bool|Diglin_Ricento_Model_Products_Listing
     */
    protected function _initListing()
    {
        $registeredListing = $this->_getListing();
        if ($registeredListing) {
            return $registeredListing;
        }
        $id = (int)$this->getRequest()->getParam('id');
        if (!$id) {
            $this->_getSession()->addError('Products Listing not found.');
            return false;
        }

        $productsListing = Mage::getModel('diglin_ricento/products_listing')->load($id);
        Mage::register('products_listing', $productsListing);

        return $this->_getListing();
    }

    /**
     * @return Diglin_Ricento_Model_Products_Listing
     */
    protected function _getListing()
    {
        return Mage::registry('products_listing');
    }

    protected function isApiReady()
    {
        $helper = Mage::helper('diglin_ricento');
        $helperApi = Mage::helper('diglin_ricento/api');
        $websiteId = $this->_initListing()->getWebsiteId();

        return $helper->isEnabled($websiteId) && $helper->isConfigured($websiteId) && !$helperApi->apiTokenCredentialGoingToExpire($websiteId);
    }

    /**
     * @return boolean
     */
    protected function _savingAllowed()
    {
        return true;
    }
}