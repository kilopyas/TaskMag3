<?php

namespace TaskVendor3\TaskModule3\Controller\Cart;

class CouponPost extends \Magento\Checkout\Controller\Cart\CouponPost
{
    protected $quoteRepository;
    protected $couponFactory;

    public function execute()
    {
        $escaper = $this->_objectManager->get(\Magento\Framework\Escaper::class);

        if($this->getRequest()->getParam('code') !== null){
            $cartQuote = $this->cart->getQuote();
            $coupons = $cartQuote->getCouponCode();
            error_log(__METHOD__."\n", 3, BP."/var/log/paulo.log");
            return $this->_goBack();
        }   
        // $this->_eventManager->dispatch('taskvendor3_customobserver_coupon', ['custom_text' => 'Custom Observer']);

        $couponCodes = $this->getRequest()->getParam('remove') == 1
            ? ''
            : trim($this->getRequest()->getParam('coupon_code'));

        $cartQuote = $this->cart->getQuote();
        $coupons = explode(",", $couponCodes);
        // error_log(print_r($coupons, true)."here \n", 3, BP."/var/log/paulo.log");
        foreach($coupons as $couponCode){
            $result = explode(",", $cartQuote->getCouponCode());
            if(in_array($couponCode, $result)){
                $this->messageManager->addErrorMessage(
                    __(
                        'The coupon code "%1" is already in use.',
                        $escaper->escapeHtml($couponCode)
                    )
                );
                continue;
            }else{
                $coupon = $this->couponFactory->create();
                $coupon->load(trim($couponCode), 'code');
                if($coupon['code'] == NULL){
                    $this->messageManager->addErrorMessage(
                        __(
                            'The coupon code "%1" is not valid.',
                            $escaper->escapeHtml($couponCode)
                        )
                    );
                    continue;
                }
            }
            $oldCouponCode = $cartQuote->getCouponCode();
            $collectedCoupons = trim($oldCouponCode . ','.$couponCode, ',');
            $codeLength = strlen($couponCode);
            if (!$codeLength && !strlen($oldCouponCode)) {
                return $this->_goBack();
            }

            try {
                $isCodeLengthValid = $codeLength && $codeLength <= \Magento\Checkout\Helper\Cart::COUPON_CODE_MAX_LENGTH;

                $itemsCount = $cartQuote->getItemsCount();
                if ($itemsCount) {
                    //error_log("SULOD \n", 3, BP."/var/log/paulo.log");
                    $cartQuote->getShippingAddress()->setCollectShippingRates(true);
                    $cartQuote->setCouponCode($isCodeLengthValid ? $collectedCoupons : '')->collectTotals();
                    $this->quoteRepository->save($cartQuote);
                }

                if ($codeLength) {
                    $coupon = $this->couponFactory->create();
                    $coupon->load(trim($couponCode), 'code');
                    if (!$itemsCount) {
                        if ($isCodeLengthValid && $coupon->getId()) {
                            $this->_checkoutSession->getQuote()->setCouponCode($collectedCoupons)->save();
                            $this->messageManager->addSuccessMessage(
                                __(
                                    'You used coupon code "%1".',
                                    $escaper->escapeHtml($couponCode)
                                )
                            );
                        } else {
                            $this->messageManager->addErrorMessage(
                                __(
                                    'The coupon code "%1" is not valid.',
                                    $escaper->escapeHtml($couponCode)
                                )
                            );
                        }
                    } else {
                        $oldCouponCode = $cartQuote->getCouponCode();
                        $oldCouponCodes = explode(",", $oldCouponCode);
                        if ($isCodeLengthValid && $coupon->getId() &&  in_array($couponCode, $oldCouponCodes)) {
                            $this->messageManager->addSuccessMessage(
                                __(
                                    'You used coupon code "%1".',
                                    $escaper->escapeHtml($couponCode)
                                )
                            );
                        } else {
                            $this->messageManager->addErrorMessage(
                                __(
                                    'The coupon code "%1" is not valid.',
                                    $escaper->escapeHtml($couponCode)
                                )
                            );
                        }
                    }
                } else {
                    $this->messageManager->addSuccessMessage(__('You canceled the coupon code.'));
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('We cannot apply the coupon code.'));
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            }
        }

        return $this->_goBack();
    }
}
