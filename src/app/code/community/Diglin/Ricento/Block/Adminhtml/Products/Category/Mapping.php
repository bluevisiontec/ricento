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
 * Class Diglin_Ricento_Block_Adminhtml_Products_Category_Mapping
 * Mapping from the Ricardo categories
 *
 * @method int getCategoryId() getCategoryId()
 * @method int getLevels() getLevels()
 * @method Diglin_Ricento_Block_Adminhtml_Products_Category_Children setCategoryId() setCategoryId(int $categoryId)
 * @method Diglin_Ricento_Block_Adminhtml_Products_Category_Children setLevels() setLevels(int $levels)
 *
 */
class Diglin_Ricento_Block_Adminhtml_Products_Category_Mapping extends Mage_Adminhtml_Block_Template
{
    protected function _beforeToHtml()
    {
        $this->setChild('toplevel',
            $this->getLayout()
                ->createBlock('diglin_ricento/adminhtml_products_category_children')
                ->setTemplate('ricento/products/category/children.phtml')
                ->setLevel(1)
                ->setCategoryId(1)
        );

        $this->setChild('sublevel',
            $this->getLayout()->createBlock('core/text_list')
        );

        if ($this->getCategoryId() != Diglin_Ricento_Model_Products_Category_Mapping::ROOT_CATEGORY_ID) {

            /* @var $mapping Diglin_Ricento_Model_Products_Category_Mapping */
            $mapping = Mage::getModel('diglin_ricento/products_category_mapping');
            $category = $mapping->getCategory($this->getCategoryId());
            $this->setLevels($category ? $category->getLevel() : 1);

            while ($category && $category->getParentId() != Diglin_Ricento_Model_Products_Category_Mapping::ROOT_CATEGORY_ID) {
                $this->getChild('sublevel')->insert(
                    $this->getLayout()
                        ->createBlock('diglin_ricento/adminhtml_products_category_children')
                        ->setLevel($category->getLevel())
                        ->setCategoryId($category->getParentId())
                        ->setSelectedCategoryId($category->getId()),
                    '', false, 'sublevel-' . $category->getLevel()
                );
                $category = $mapping->getCategory($category->getParentId()); //TODO reference parent from child
            }

            if ($category) {
                $this->getChild('toplevel')->setSelectedCategoryId($category->getId());
            }
        }
        return parent::_beforeToHtml();
    }

    /**
     * Determine if the columns have to be resized
     *
     * @return bool
     */
    public function shouldResize()
    {
        return $this->getLevels() >= 5;
    }

}