<?php

namespace TaskVendor3\TaskModule3\Controller\Cart;

class CouponPost extends \Magento\Checkout\Controller\Cart\CouponPost
{
    protected $quoteRepository;
    protected $couponFactory;

    public function execute()
    {
        $couponCodes = $this->getRequest()->getParam('remove') == 1
            ? ''
            : trim($this->getRequest()->getParam('coupon_code'));

        $cartQuote = $this->cart->getQuote();
       
        $coupons = explode(",", $couponCodes);
        foreach($coupons as $couponCode){
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
                    $cartQuote->getShippingAddress()->setCollectShippingRates(true);
                    $cartQuote->setCouponCode($isCodeLengthValid ? $collectedCoupons : '')->collectTotals();
                    $this->quoteRepository->save($cartQuote);
                }

                if ($codeLength) {
                    $escaper = $this->_objectManager->get(\Magento\Framework\Escaper::class);
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
