<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright  Copyright (c) 2006-2015 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Product list
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Tabs_Extension_Block_Sale extends Mage_Catalog_Block_Product_Abstract
{
    /**
     * Default toolbar block name
     *
     * @var string
     */
    protected $_defaultToolbarBlock = 'catalog/product_list_toolbar';

    /**
     * Product Collection
     *
     * @var Mage_Eav_Model_Entity_Collection_Abstract
     */
    protected $_productCollection;

    /**
     * Retrieve loaded category collection
     *
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    protected function _getProductCollections($page_id)
    {
        if (is_null($this->_productCollection)) {
            $layer = $this->getLayer();
            if ($this->getShowRootCategory()) {
                $this->setCategoryId(Mage::app()->getStore()->getRootCategoryId());
            }

            // if this is a product view page
            if (Mage::registry('product')) {
                // get collection of categories this product is associated with
                $categories = Mage::registry('product')->getCategoryCollection()
                    ->setPage(1, 1)
                    ->load();
                // if the product is associated with any category
                if ($categories->count()) {
                    // show products from this category
                    $this->setCategoryId(current($categories->getIterator()));
                }
            }

            $origCategory = null;
            if ($this->getCategoryId()) {
                $category = Mage::getModel('catalog/category')->load($this->getCategoryId());
                if ($category->getId()) {
                    $origCategory = $layer->getCurrentCategory();
                    $layer->setCurrentCategory($category);
                    $this->addModelTags($category);
                }
            }
            /* @var $layer Mage_Catalog_Model_Layer */
            /* @var $layer Mage_Catalog_Model_Layer */
        $this->_productCollection = $layer->getProductCollection();
        //Mage::getSingleton('core/session', array('name' => 'frontend'));
       $condition = new Zend_Db_Expr("special_price < price");
       $this->_productCollection = Mage::getResourceModel('catalogsearch/advanced_collection')
        ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
        ->addMinimalPrice()
        ->addStoreFilter()
        ->setPageSize(20)
        ->addAttributeToFilter('upcomingproduct', 0);
        //->load();
       
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($this->_productCollection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInSearchFilterToCollection($this->_productCollection);
        Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($this->_productCollection);
        
        
        $todayDate = date('m/d/y');
        $tomorrow = mktime(0, 0, 0, date('m'), date('d'), date('y'));
        $tomorrowDate = date('m/d/y', $tomorrow);
        $this->_productCollection->addAttributeToFilter('special_from_date', array('date' => true, 'to' => $todayDate))
        ->addAttributeToFilter('special_to_date', array('or'=> array(
        0 => array('date' => true, 'from' => $tomorrowDate),
        1 => array('is' => new Zend_Db_Expr('null')))
        ), 'left');
        
        $this->_productCollection->addAttributeToFilter('special_price', array('neq' => 'null'));
        //$this->_productCollection->addFieldToFilter('special_price', array('lt' => 'price'));
        if($this->getRequest()->getParam('cat_id')!= null){
            //echo "hdjkdjdksjdks";
        $categoryId = $this->getRequest()->getParam('cat_id'); 
        $category = Mage::getModel('catalog/category')->load($categoryId);
        $this->_productCollection->addCategoryFilter($category);
        } 

        $select = $this->_productCollection->getSelect();
        $currentUrl = $this->helper('core/url')->getCurrentUrl();
        $str = "sale";
        $str1 = "superdeals";
        if (strpos($currentUrl, $str) == true):
           $select->where('price_index.final_price < price_index.price');
        elseif(strpos($currentUrl, $str1) == true):
            $select->where('price_index.final_price < price_index.price AND (100 - (price_index.final_price/price_index.price) * 100) > 20');
        else:
            $select->where('price_index.final_price < price_index.price');
        endif;


        //exit;
            
        //print_r($collection);
        //$this->_productCollection = $layer->getProductCollections();
        //print_r($this->_productCollection);

        }

        return $this->_productCollection;
    }

    /**
     * Get catalog layer model
     *
     * @return Mage_Catalog_Model_Layer
     */
    public function getLayer()
    {
        $layer = Mage::registry('current_layer');
        if ($layer) {
            return $layer;
        }
        return Mage::getSingleton('catalog/layer');
    }

    /**
     * Retrieve loaded category collection
     *
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function getLoadedProductCollection($page_id)
    {
        return $this->_getProductCollections($page_id);
    }

    /**
     * Retrieve current view mode
     *
     * @return string
     */
    public function getMode()
    {
        return $this->getChild('toolbar')->getCurrentMode();
    }

    /**
     * Need use as _prepareLayout - but problem in declaring collection from
     * another block (was problem with search result)
     */
    protected function _beforeToHtml()
    {
        $toolbar = $this->getToolbarBlock();

        // called prepare sortable parameters
        $collection = $this->_getProductCollections();

        // use sortable parameters
        $orders = array('entity_id' => $this->__('Latest'), 'price' => $this->__('Price') ); 
            $toolbar->setAvailableOrders($orders);
        
        if ($sort = $this->getSortBy()) {
            $toolbar->setAvailableOrders($orders);
            $toolbar->setDefaultOrder('entity_id');
            $toolbar->setDefaultDirection('desc');
        }
        if ($modes = $this->getModes()) {
        $toolbar->setModes($modes);
    }
 
    // set collection to tollbar and apply sort
    $toolbar->setCollection($collection);
 
    $this->setChild('toolbar', $toolbar);
    Mage::dispatchEvent('catalog_block_product_list_collection', array(
        'collection'=>$this->_getProductCollections(),
    ));
 
    $this->_getProductCollections()->load();
    Mage::getModel('review/review')->appendSummary($this->_getProductCollections());
    return parent::_beforeToHtml();
    }


    /**
     * Retrieve Toolbar block
     *
     * @return Mage_Catalog_Block_Product_List_Toolbar
     */
    public function getToolbarBlock()
    {
        if ($blockName = $this->getToolbarBlockName()) {
            if ($block = $this->getLayout()->getBlock($blockName)) {
                return $block;
            }
        }
        $block = $this->getLayout()->createBlock($this->_defaultToolbarBlock, microtime());
        return $block;
    }

    /**
     * Retrieve additional blocks html
     *
     * @return string
     */
    public function getAdditionalHtml()
    {
        return $this->getChildHtml('additional');
    }

    /**
     * Retrieve list toolbar HTML
     *
     * @return string
     */
    public function getToolbarHtml()
    {
        return $this->getChildHtml('toolbar');
    }

    public function setCollection($collection)
    {
        $this->_productCollection = $collection;
        return $this;
    }

    public function addAttribute($code)
    {
        $this->_getProductCollections()->addAttributeToSelect($code);
        return $this;
    }

    public function getPriceBlockTemplate()
    {
        return $this->_getData('price_block_template');
    }

    /**
     * Retrieve Catalog Config object
     *
     * @return Mage_Catalog_Model_Config
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('catalog/config');
    }

    /**
     * Prepare Sort By fields from Category Data
     *
     * @param Mage_Catalog_Model_Category $category
     * @return Mage_Catalog_Block_Product_List
     */
    public function prepareSortableFieldsByCategory($category) {
        if (!$this->getAvailableOrders()) {
            $this->setAvailableOrders($category->getAvailableSortByOptions());
        }
        $availableOrders = $this->getAvailableOrders();
        if (!$this->getSortBy()) {
            if ($categorySortBy = $category->getDefaultSortBy()) {
                if (!$availableOrders) {
                    $availableOrders = $this->_getConfig()->getAttributeUsedForSortByArray();
                }
                if (isset($availableOrders[$categorySortBy])) {
                    $this->setSortBy($categorySortBy);
                }
            }
        }

        return $this;
    }

    /**
     * Retrieve block cache tags based on product collection
     *
     * @return array
     */
    public function getCacheTags()
    {
        return array_merge(
            parent::getCacheTags(),
            $this->getItemsTags($this->_getProductCollections())
        );
    }
    
    public function getTotalOrder($id){
         $query = Mage::getResourceModel('sales/order_item_collection');
         $query->getSelect()->reset(Zend_Db_Select::COLUMNS)
         ->columns(array('sku','SUM(qty_ordered) as purchased'))
         ->group(array('sku'))
         ->where('product_id = ?',array($id))
         ->limit(1);
         return $query;
    }

    public function getcategories(){
       $categories = Mage::getModel('catalog/category')->getCollection()
       ->addAttributeToSelect('*')//or you can just add some attributes
       ->addAttributeToFilter('level', 2)//2 is actually the first level
       ->addAttributeToFilter('is_active', 1)//if you want only active categories
       ;
       return $categories;
    }
    
}
