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

/**
 * Class Diglin_Ricento_Model_Dispatcher_Relist
 */
class Diglin_Ricento_Model_Dispatcher_Relist extends Diglin_Ricento_Model_Dispatcher_Abstract
{
    /**
     * @var int
     */
    protected $_logType = Diglin_Ricento_Model_Products_Listing_Log::LOG_TYPE_RELIST;

    /**
     * @var string
     */
    protected $_jobType = Diglin_Ricento_Model_Sync_Job::TYPE_RELIST;

    /**
     * @return $this
     * @deprecated since 1.0
     * @todo Relist is not used as we list again instead of relist, keep it at the moment
     */
    protected function _proceed()
    {
        $job = $this->_currentJob;
        $jobListing = $this->_currentJobListing;

        $sell = Mage::getSingleton('diglin_ricento/api_services_sell');
        $sell->setCurrentWebsite($this->_getListing()->getWebsiteId());

        $relistedArticle = null;
        $articleId = null;
        $hasSuccess = false;

        $itemCollection = $this->_getItemCollection(array(Diglin_Ricento_Helper_Data::STATUS_SOLD), $jobListing->getLastItemId());

        if ($itemCollection->count() == 0) {
            $job->setJobMessage(array($this->_getNoItemMessage()));
            $this->_progressStatus = Diglin_Ricento_Model_Sync_Job::PROGRESS_COMPLETED;
            return $this;
        }

        /* @var $item Diglin_Ricento_Model_Products_Listing_Item */
        foreach ($itemCollection->getItems() as $item) {

            try {
                $relistedArticle = $sell->relistArticle($item);

                $articleId = $item->getRicardoArticleId();
                if (!empty($articleId)) {
                    $this->_itemStatus = Diglin_Ricento_Model_Products_Listing_Log::STATUS_SUCCESS;
                    $this->_itemMessage = array('relisted_article' => print_r($relistedArticle, true));
                    $hasSuccess = true;
                    $item->getResource()->saveCurrentItem($item->getId(), array('status' => Diglin_Ricento_Helper_Data::STATUS_LISTED));
                } else {
                    $this->_jobHasError = true;
                    $this->_itemStatus = Diglin_Ricento_Model_Products_Listing_Log::STATUS_ERROR;
                    $item->getResource()->saveCurrentItem($item->getId(), array('status' => Diglin_Ricento_Helper_Data::STATUS_ERROR));
                }

            } catch (Exception $e) {
                $this->_handleException($e);
                $e = null;
                // keep going for the next item - no break
            }

            /**
             * Save item information and eventual error messages
             */
            $this->_getListingLog()->saveLog(array(
                'job_id' => $job->getId(),
                'product_title' => $item->getProductTitle(),
                'products_listing_id' => $this->_productsListingId,
                'product_id' => $item->getProductId(),
                'message' => (is_array($this->_itemMessage)) ? $this->_jsonEncode($this->_itemMessage) : $this->_itemMessage,
                'log_status' => $this->_itemStatus,
                'log_type' => $this->_logType,
                'created_at' => Mage::getSingleton('core/date')->gmtDate()
            ));

            /**
             * Save the current information of the process to allow live display via ajax call
             */
            $jobListing->saveCurrentJob(array(
                'total_proceed' => ++$this->_totalProceed,
                'total_success' => ($jobListing->getTotalSuccess() + $this->_totalSuccess),
                'total_error' => ($jobListing->getTotalError() + $this->_totalError),
                'last_item_id' => $item->getId()
            ));

            $this->_itemMessage = null;
            $this->_itemStatus = null;
        }

        if ($hasSuccess) {
            $listing = Mage::getModel('diglin_ricento/products_listing')->load($this->_productsListingId);
            $listing
                ->setStatus(Diglin_Ricento_Helper_Data::STATUS_LISTED)
                ->save();
        }

        return $this;
    }
}
