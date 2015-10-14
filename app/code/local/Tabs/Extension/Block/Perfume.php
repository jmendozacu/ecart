<?php
// app/code/local/Envato/Recentproducts/Block/Recentproducts.php
class Tabs_Extension_Block_Perfume extends Mage_Catalog_Block_Product_Abstract {

    protected $_defaultToolbarBlock = 'catalog/product_list_toolbar';
    protected $_productsCount = null;
    const DEFAULT_PRODUCTS_COUNT = 10;

    public function getLoadedProductCollection()
    { 
       
       $id = 65;
       // benchmarking
        $memory = memory_get_usage();
        $time = microtime();
        $catId = $id;
        /** @var $collection Mage_Catalog_Model_Resource_Product_Collection */
        $collection = Mage::getResourceModel('catalog/product_collection');
        // join sales order items column and count sold products
        $expression = new Zend_Db_Expr("SUM(oi.qty_ordered)");
        $condition = new Zend_Db_Expr("e.entity_id = oi.product_id AND oi.parent_item_id IS NULL");
        $collection->addAttributeToSelect('*')->getSelect()
            ->join(array('oi' => $collection->getTable('sales/order_item')),               
            $condition,
            array('sales_count' => $expression))
            ->group('e.entity_id')
            ->order('sales_count' . ' ' . 'desc');
            $collection->addFieldToFilter('status','1');
        //join brand 
        if($this->getRequest()->getParam('brands_id')!= null AND $this->getRequest()->getParam('brands_id')!= 0){
          $brands_id = $this->getRequest()->getParam('brands_id');
          $condition = new Zend_Db_Expr("cpie.entity_id = e.entity_id AND cpie.attribute_id = 81 AND cpie.value = $brands_id");
               $collection->getSelect()->join(array('cpie' => $collection->getTable('catalog_product_index_eav')),
               $condition,
               array('product_entity' => 'cpie.entity_id'));
               $condition = new Zend_Db_Expr(" br.option_id = cpie.value");
               $collection->getSelect()->join(array('br' => $collection->getTable('shopbybrand/brand')),
               $condition,
               array('brand_name' => 'br.name' , 'brand_optionid' => 'br.option_id' ));
         }    
        $condition = new Zend_Db_Expr("e.entity_id = stock.product_id AND is_in_stock = 1");
        $collection->getSelect()->join(array('stock' => $collection->getTable('cataloginventory_stock_item')),
        $condition,
        array());
        // join category
        $condition = new Zend_Db_Expr("e.entity_id = ccp.product_id");
        $condition2 = new Zend_Db_Expr("c.entity_id = ccp.category_id");
        $collection->getSelect()->join(array('ccp' => $collection->getTable('catalog/category_product')),
            $condition,
            array())->join(array('c' => $collection->getTable('catalog/category')),
            $condition2,
            array('cat_id' => 'c.entity_id'));
        $condition = new Zend_Db_Expr("c.entity_id = cv.entity_id AND ea.attribute_id = cv.attribute_id");
        // cutting corners here by hardcoding 3 as Category Entiry_type_id
        $condition2 = new Zend_Db_Expr("ea.entity_type_id = 3 AND ea.attribute_code = 'name'");
        $collection->getSelect()->join(array('ea' => $collection->getTable('eav/attribute')),
            $condition2,
            array())->join(array('cv' => $collection->getTable('catalog/category') . '_varchar'),
            $condition,
            array('cat_name' => 'cv.value'));
        // if Category filter is on
        if ($catId) {
            $collection->getSelect()->where('c.entity_id = ?', $catId)->limit(20);
        }

        // unfortunately I cound not come up with the sql query that could grab only 1 bestseller for each category
        // so all sorting work lays on php
        $result = array();
        foreach ($collection as $product) {
            /** @var $product Mage_Catalog_Model_Product */
            if (isset($result[$product->getCatId()])) {
                continue;
            }
            $result[$product->getCatId()] = 'Category:' . $product->getCatName() . '; Product:' . $product->getName() . '; Sold Times:'. $product->getSalesCount();
        }
       
        return $collection;

        
    } 

    protected function _getProductCollection()
    {
        $id = 65;
        $todayDate  = Mage::app()->getLocale()->date()->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
        $collection = Mage::getResourceModel('catalog/product_collection');
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
        
        $collection = $this->_addProductAttributesAndPrices($collection)
            ->addStoreFilter()
            ->addAttributeToFilter('news_from_date', array('date' => true, 'to' => $todayDate))
            ->addAttributeToFilter('news_to_date', array('or'=> array(
                0 => array('date' => true, 'from' => $todayDate),
                1 => array('is' => new Zend_Db_Expr('null')))
            ), 'left')
            ->addAttributeToSort('news_from_date', 'desc')
            ->setPageSize($this->getProductsCount())
            ->addAttributeToFilter('upcomingproduct', 0)
            ->setCurPage(20);

        if($categoryId = $id){
        $category = Mage::getModel('catalog/category')->load($categoryId);
        $collection->addCategoryFilter($category);
        } 
        
         if($this->getRequest()->getParam('brands_id')!= null AND $this->getRequest()->getParam('brands_id')!= 0){
          $brands_id = $this->getRequest()->getParam('brands_id');
          $condition = new Zend_Db_Expr("cpie.entity_id = e.entity_id AND cpie.attribute_id = 81 AND cpie.value = $brands_id");
               $collection->getSelect()->join(array('cpie' => $collection->getTable('catalog_product_index_eav')),
               $condition,
               array('product_entity' => 'cpie.entity_id'));
               $condition = new Zend_Db_Expr(" br.option_id = cpie.value");
               $collection->getSelect()->join(array('br' => $collection->getTable('shopbybrand/brand')),
               $condition,
               array('brand_name' => 'br.name' , 'brand_optionid' => 'br.option_id' ));
         }   

        return $collection;
    }

   
  
     public function getLoadedProductCollectionnew()
    {
        return $this->_getProductCollection();
    }
    
      public function getLoadedProductCollectionbrand()
    {
         $id = 65;
        $collection = Mage::getModel('shopbybrand/brand')->getCollection()
        ->addFieldToSelect('*')
        ->addFieldToFilter('is_featured',array('gteq'=>0));
        $collection->getSelect()->order('main_table.brand_id ASC');      
        $condition = new Zend_Db_Expr("main_table.product_ids = ccp.product_id");
        $collection->getSelect()->join(array('ccp' => $collection->getTable('catalog/category_product')),
        $condition,
        array('product_id' => 'main_table.product_ids'));
        $collection->getSelect()->where('ccp.category_id = ?', $id);
        return $collection;
        /*$brand = $collection;
        foreach ($brand as $brands):
          $r = $brands->category_ids;
          $i=explode(",",$r);
          $y = 0;
          foreach ($i as $brandnew):
            if($i[$y]==$id){
                $collection->getSelect()->where('category_ids = ?', $id)->limit(5);
                return $collection;
               $y=$y+1;       
            }
          endforeach;     
        endforeach;*/

    }

   public function getproductsids()
    {
      
      
      if($this->getRequest()->getParam('brands_id')!= null ){      
      $brands_id = $this->getRequest()->getParam('brands_id');
      $collection = Mage::getModel('shopbybrand/brand')->getCollection()
        ->addFieldToSelect('product_ids');
      $collection->getSelect()->where('option_id = ?', $brands_id);  
      
      }
      return $collection;
      
    }
    public function getproductbrands(){
     $id = 65;
       // benchmarking
        $memory = memory_get_usage();
        $time = microtime();
        $catId = $id;
        /** @var $collection Mage_Catalog_Model_Resource_Product_Collection */
        $collection = Mage::getResourceModel('catalog/product_collection');
        // join sales order items column and count sold products
        $expression = new Zend_Db_Expr("SUM(oi.qty_ordered)");
        $condition = new Zend_Db_Expr("e.entity_id = oi.product_id AND oi.parent_item_id IS NULL");
        $condition = new Zend_Db_Expr("e.entity_id = oi.product_id AND oi.parent_item_id IS NULL");
        $collection->addAttributeToSelect('*')->getSelect()
            ->join(array('oi' => $collection->getTable('sales/order_item')),
            $condition,
            array('sales_count' => $expression))
            ->group('e.entity_id')
            ->order('sales_count' . ' ' . 'desc');
            $collection->addFieldToFilter('status','1');
            $condition = new Zend_Db_Expr("e.entity_id = stock.product_id AND is_in_stock = 1");
            $collection->getSelect()->join(array('stock' => $collection->getTable('cataloginventory_stock_item')),
            $condition,
            array());
        //join brand 
              $condition = new Zend_Db_Expr("cpie.entity_id = e.entity_id AND cpie.attribute_id = 81");
               $collection->getSelect()->join(array('cpie' => $collection->getTable('catalog_product_index_eav')),
               $condition,
               array('product_entity' => 'cpie.entity_id'));
               $condition = new Zend_Db_Expr(" br.option_id = cpie.value");
               $collection->getSelect()->join(array('br' => $collection->getTable('shopbybrand/brand')),
               $condition,
               array('brand_name' => 'br.name' , 'brand_optionid' => 'br.option_id' ));
              

        // join category
        $condition = new Zend_Db_Expr("e.entity_id = ccp.product_id");
        $condition2 = new Zend_Db_Expr("c.entity_id = ccp.category_id");
        $collection->getSelect()->join(array('ccp' => $collection->getTable('catalog/category_product')),
            $condition,
            array())->join(array('c' => $collection->getTable('catalog/category')),
            $condition2,
            array('cat_id' => 'c.entity_id'));
        $condition = new Zend_Db_Expr("c.entity_id = cv.entity_id AND ea.attribute_id = cv.attribute_id");
        // cutting corners here by hardcoding 3 as Category Entiry_type_id
        $condition2 = new Zend_Db_Expr("ea.entity_type_id = 3 AND ea.attribute_code = 'name'");
        $collection->getSelect()->join(array('ea' => $collection->getTable('eav/attribute')),
            $condition2,
            array())->join(array('cv' => $collection->getTable('catalog/category') . '_varchar'),
            $condition,
            array('cat_name' => 'cv.value'));
        
        // if Category filter is on
        if ($catId) {
             $collection->getSelect()->where('c.entity_id = ?', $catId); 
                
        }

        // unfortunately I cound not come up with the sql query that could grab only 1 bestseller for each category
        // so all sorting work lays on php
        $result = array();
        foreach ($collection as $product) {
            /** @var $product Mage_Catalog_Model_Product */
            if (isset($result[$product->getCatId()])) {
                continue;
            }
            $result[$product->getCatId()] = 'Category:' . $product->getCatName() . '; Product:' . $product->getName() . '; Sold Times:'. $product->getSalesCount();
        }
       
        return $collection;

        
   }

}