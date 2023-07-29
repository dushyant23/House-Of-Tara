<?php

namespace Meetanshi\Ccavenue\Controller\Payment;

use Meetanshi\Ccavenue\Controller\Payment as CcavenuePayment;
use Magento\Sales\Model\Order;

/**
 * Class Redirect
 * @package Meetanshi\Ccavenue\Controller\Payment
 */
class Redirect extends CcavenuePayment
{
    /**
     * @return bool|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if ($this->getRequest()->isAjax()) {
            $result = $this->jsonFactory->create();
            try {
                $order = $this->checkoutSession->getLastRealOrder();
                $order->setState(Order::STATE_PENDING_PAYMENT, true);
                $order->setStatus(Order::STATE_PENDING_PAYMENT);
                $order->save();
                $html = $this->helper->getPaymentForm($order);
                return $result->setData(['error' => false, 'success' => true, 'html' => $html]);
            } catch (\Exception $e) {
                $this->helper->debug('Payment Exeption', (array)$e->getMessage());
                $this->checkoutSession->restoreQuote();
                return $result->setData(['error' => true, 'success' => false, 'message' => __('Payment exception')]);
            }
        }
        return false;
    }
}
