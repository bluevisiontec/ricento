<?php
/**
 * Diglin GmbH - Switzerland
 *
 * @author Sylvain Rayé <sylvain.raye at diglin.com>
 * @category    Diglin
 * @package     Diglin_Ricento
 * @copyright   Copyright (c) 2011-2014 Diglin (http://www.diglin.com)
 */
class Diglin_Ricento_Model_Config_Source_ShippingCalculation
{
    /**
     * Create option array to display the list of possible options for shipping calculation
     *
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('diglin_ricento');

        return array(

            array(
                'value' => 'highest_price' ,
                'label' => $helper->__('Highest Price')
            ),
            array(
                'value' => 'cumulative' ,
                'label' => $helper->__('Cumulative')
            )
        );
    }
}