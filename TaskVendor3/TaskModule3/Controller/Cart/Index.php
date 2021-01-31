<?php
namespace TaskVendor3\TaskModule3\Controller\Cart;

use Magento\Customer\Model\Session;
use Magento\Quote\Model\QuoteFactory;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

class Index extends \Magento\Checkout\Controller\Cart implements HttpGetActionInterface
{

    protected $resultPageFactory;

    protected $_quoteFactory;

    protected $_customer;

    protected $resultFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        QuoteFactory $quoteFactory,
        Session $customer
    ) {
        $this->resultFactory = $resultFactory;
        $this->_quoteFactory = $quoteFactory;
        $this->_customer = $customer;
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Shopping cart display action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        // error_log(__METHOD__." \n", 3, BP."/var/log/paulo.log");

        if($this->getRequest()->getParam('code') !== null){
            $escaper = $this->_objectManager->get(\Magento\Framework\Escaper::class);

            $code = $this->getRequest()->getParam('code');
            $customer = $this->_customer;
            $customerId = $customer->getId();
            $concat = "";

            $quoteCollection = $this->_quoteFactory->create()->load($customerId, "customer_id");
            $collection = $quoteCollection->getCollection();

            // foreach($collection as $a){
            //     print $a->getId()." \n";
            // }
            // die();
            $quote = $collection->getLastItem();
            $couponCodes = $quote->getCouponCode();
            // var_dump($couponCodes);
            // die();
            $resultArr = explode(",", $couponCodes);
            // error_log(print_r($resultArr, true)." \n", 3, BP."/var/log/paulo.log");
            if(in_array($code, $resultArr)){
                if(count($resultArr) == 1){
                    $quote->setCouponCode(NULL);
                    $quote->save();
                }else{
                    foreach($resultArr as $coupon){
                        if($coupon == $code)
                            continue;
                        else{
                            $concat = $concat.",".$coupon;
                            $finalResult = trim($concat, ",");
                            echo $finalResult;
    
                            $quote->setCouponCode($finalResult);
                            $quote->save();
                        }
                    }
                }
            }

            $this->messageManager->addSuccessMessage(
                __(
                    'You cancelled coupon code "%1".',
                    $escaper->escapeHtml($code)
                )
            );
            $redirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
            $redirect->setUrl('/magento/checkout/cart');
            return $redirect;
        }


        error_log("Nakasulod ko diri \n", 3, BP."/var/log/paulo.log");
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Shopping Cart'));
        return $resultPage;
    }
}
