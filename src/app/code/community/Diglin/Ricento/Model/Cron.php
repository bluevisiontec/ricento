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
 * Class Diglin_Ricento_Model_Cron
 */
class Diglin_Ricento_Model_Cron
{
    protected $_syncProcess = array(
        //Diglin_Ricento_Model_Sync_Job::TYPE_CHECK_LIST, //** Check list before to sync to ricardo.ch - @deprecated move to Diglin_Ricento_Adminhtml_Products_ListingController to start quickly the check
        Diglin_Ricento_Model_Sync_Job::TYPE_LIST, //** List to ricardo.ch
        Diglin_Ricento_Model_Sync_Job::TYPE_STOP, //** Stop the list on ricardo.ch if needed
        //Diglin_Ricento_Model_Sync_Job::TYPE_RELIST //** Relist to ricardo.ch
    );

    protected $_asyncProcess = array(
        Diglin_Ricento_Model_Sync_Job::TYPE_SYNCLIST, //** Sync List before getting orders
        Diglin_Ricento_Model_Sync_Job::TYPE_ORDER //** Get new orders
    );

    /**
     * Process Cron tasks - should be run in a short period of time
     */
    public function process()
    {
        if (!Mage::helper('diglin_ricento')->isEnabled()) {
            return;
        }

		// set memory limit to 512M if current is lower
        $memoryLimit = ini_get('memory_limit');
		if (preg_match('/^(\d+)(.)$/', $memoryLimit, $memoryLimitParts)) {
			if ($memoryLimitParts[2] == 'G') {
				$memoryLimit = $memoryLimitParts[1] * 1024 * 1024 * 1024;
			} else if ($memoryLimitParts[2] == 'M') {
				$memoryLimit = $memoryLimitParts[1] * 1024 * 1024;
			} else if ($memoryLimitParts[2] == 'K') {
				$memoryLimit = $memoryLimitParts[1] * 1024;
			}
		}
		if($memoryLimit < 512*1024*1024 && $memoryLimit > 0) {
			ini_set('memory_limit', '512M');
		}
		
        //** Launch Pending Jobs

        // @todo check that the API token is not expired or that an error may occur, in this case send only once an email to the admin

        try {
            foreach ($this->_syncProcess as $jobType) {
                $this->dispatch($jobType);
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Process Cron tasks which needs to be run on a longer period of time
     */
    public function async()
    {
        if (!Mage::helper('diglin_ricento')->isEnabled()) {
            return;
        }

        ini_set('memory_limit', '512M');

        try {
            foreach ($this->_asyncProcess as $jobType) {
                $this->dispatch($jobType);
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        //** Cleanup

        $this->_processCleanupJobs();
    }

    /**
     * Clean old jobs passed on the last X days
     *
     * @return $this
     */
    protected function _processCleanupJobs()
    {
        if (Mage::getStoreConfigFlag(Diglin_Ricento_Helper_Data::CFG_CLEAN_JOBS_ENABLED)) {
            $daysKeep = (int)Mage::getStoreConfig(Diglin_Ricento_Helper_Data::CFG_CLEAN_JOBS_KEEP_DAYS);

            try {
                $coreResource = Mage::getSingleton('core/resource');
                $write = $coreResource->getConnection('core_write');

                $select = $write->select()
                    ->from(array('main_table' => $coreResource->getTableName('ricento_sync_job')))
                    ->where('((TO_DAYS(main_table.created_at) + ?) < TO_DAYS(now()))', $daysKeep);

                $query = $select->deleteFromSelect('main_table');
                $write->query($query);
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return $this;
    }

    /**
     * @return Diglin_Ricento_Model_Dispatcher
     */
    protected function _getDisptacher()
    {
        return Mage::getSingleton('diglin_ricento/dispatcher');
    }

    /**
     * @param int $type
     * @return $this
     */
    protected function dispatch($type)
    {
        return $this->_getDisptacher()->dispatch($type)->proceed();
    }
}