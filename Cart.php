<?php

 namespace Ey\Productcart\Model;

 use Magento\Catalog\Api\ProductRepositoryInterface;
 use Magento\Checkout\Model\Session;

 class Cart extends \Magento\Checkout\Model\Cart{

   public function __construct(\Magento\Framework\Event\ManagerInterface $eventManager,
                               \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
                               \Magento\Store\Model\StoreManagerInterface $storeManager,
                               \Magento\Checkout\Model\ResourceModel\Cart $resourceCart,
                               Session $checkoutSession,
                               \Magento\Customer\Model\Session $customerSession,
                               \Magento\Framework\Message\ManagerInterface $messageManager,
                               \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
                               \Magento\CatalogInventory\Api\StockStateInterface $stockState,
                               \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
                               \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
                               array $data = [])
   {
       parent::__construct($eventManager, $scopeConfig, $storeManager, $resourceCart, $checkoutSession, $customerSession, $messageManager, $stockRegistry, $stockState, $quoteRepository, $productRepository, $data);
   }

     public function addProduct($productInfo, $requestInfo = null)
     {
         $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/Observer.log');
         $logger = new \Zend_Log();
         $logger->addWriter($writer);
         $logger->info(json_encode($productInfo->getData()));
         $logger->info(json_encode($requestInfo));
         $product = $this->_getProduct($productInfo);
         $productId = $product->getId();

         if ($productId) {
             $request = $this->getQtyRequest($product, $requestInfo);
             try {
                 $this->_eventManager->dispatch(
                     'checkout_cart_product_add_before',
                     ['info' => $requestInfo, 'product' => $product]
                 );
                 $result = $this->getQuote()->addProduct($product, $request);
                 //$logger->info(json_encode($result->getData()));
                 //$logger->info(json_encode($product->getData()));
                 $logger->info(json_encode($request));
                 $logger->info(json_encode($productInfo));
                 if($product->getIsAssignproduct() && $product->getAssignsProducts()){
                    //&& $product->getAssignsProducts()
                     $logger->info("Hello Starts");
                     $logger->info($product->getAssignsProducts());
                     $assignProductsString = $product->getAssignsProducts();
                     $assignProductsArray = explode(',',$product->getAssignsProducts());
                     foreach($assignProductsArray as $assignProductItem){
                         $logger->info($assignProductItem);
                         $productDetails = explode("=",$assignProductItem);
                         $logger->info(json_encode($productDetails));
                         $info = array("qty" => $productDetails[1]);
                         //$cartAddedProduct = $this->_getProduct($productDetails[0]);
                         //$this->getQuote()->addProduct($cartAddedProduct, null);
                     }
                     /*
                     $assignProducts = $product->getAssignProducts();
                     $assignProductsArray = explode(',', $assignProducts);
                     foreach ($assignProductsArray as $assignProductId) {
                         $logger->info($product->getIsAssignproduct());
                         $logger->info($product->getAssignProducts());
                         $vitualProduct = $this->_getProduct($assignProductId);
                         $this->getQuote()->addProduct($vitualProduct, null);
                     }*/
                 }
             } catch (\Magento\Framework\Exception\LocalizedException $e) {
                 $this->_checkoutSession->setUseNotice(false);
                 $result = $e->getMessage();
             }
             /**
              * String we can get if prepare process has error
              */
             if (is_string($result)) {
                 if ($product->hasOptionsValidationFail()) {
                     $redirectUrl = $product->getUrlModel()->getUrl(
                         $product,
                         ['_query' => ['startcustomization' => 1]]
                     );
                 } else {
                     $redirectUrl = $product->getProductUrl();
                 }
                 $this->_checkoutSession->setRedirectUrl($redirectUrl);
                 if ($this->_checkoutSession->getUseNotice() === null) {
                     $this->_checkoutSession->setUseNotice(true);
                 }
                 throw new \Magento\Framework\Exception\LocalizedException(__($result));
             }
         } else {
             throw new \Magento\Framework\Exception\LocalizedException(__('The product does not exist.'));
         }

         $this->_eventManager->dispatch(
             'checkout_cart_product_add_after',
             ['quote_item' => $result, 'product' => $product]
         );
         $this->_checkoutSession->setLastAddedProductId($productId);
         return $this;
     }

 }
