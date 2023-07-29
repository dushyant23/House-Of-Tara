<?php

namespace Meetanshi\Ccavenue\Controller\Payment;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction;
use Meetanshi\Ccavenue\Controller\Payment as CcavenuePayment;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

/**
 * Class Success
 * @package Meetanshi\Ccavenue\Controller\Payment
 */
class Success extends CcavenuePayment implements CsrfAwareActionInterface
{

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        $this->helper->debug('Response Params', $params);

        if (is_array($params) && !empty($params)) {
            if (array_key_exists('encResp', $params)) {
                $order = $this->orderFactory->create()->loadByIncrementId($params['orderNo']);
                $payment = $order->getPayment();
                $workingKey = $this->helper->getEncryptionKey();
                $encResponse = $params["encResp"];
                $decryptData = $this->helper->decrypt($encResponse, $workingKey);

                $mapMain = [];

                $pairArray = explode("&", $decryptData);
                foreach ($pairArray as $pair) {
                    $param = explode("=", $pair);
                    if ($param[1] != 'Unsupported') {
                        $mapMain[urldecode($param[0])] = urldecode($param[1]);
                    }
                }

                $this->helper->debug('Order Response Data', (array)$mapMain);

                $trackingID = $mapMain['tracking_id'];
                $orderStatus = $mapMain['order_status'];
                $transactionID = $mapMain['bank_ref_no'];
                $paymentMode = $mapMain['payment_mode'];

                if ($orderStatus === "Success") {
                    $payment->setTransactionId($transactionID);
                    $payment->setLastTransId($transactionID);
                    $payment->setAdditionalInformation('payment_mode', $paymentMode);
                    $payment->setAdditionalInformation('tran_id', $transactionID);
                    $payment->setAdditionalInformation('order_status', $orderStatus);
                    $payment->setAdditionalInformation('tracking_id', $trackingID);

                    $payment->setAdditionalInformation((array)$payment->getAdditionalInformation());
                    $trans = $this->transactionBuilder;
                    $transaction = $trans->setPayment($payment)->setOrder($order)->setTransactionId($transactionID)->setAdditionalInformation((array)$payment->getAdditionalInformation())->setFailSafe(true)->build(Transaction::TYPE_CAPTURE);

                    $payment->addTransactionCommentsToOrder($transaction, 'Transaction is approved by the bank');
                    $payment->setParentTransactionId(null);

                    $payment->save();

                    $this->orderSender->notify($order);

                    $order->setStatus(Order::STATE_PROCESSING);
                    $order->setState(Order::STATE_PROCESSING);

                    $order->addStatusHistoryComment(__('Transaction is approved by the bank'),
                        Order::STATE_PROCESSING)->setIsCustomerNotified(true);

                    $order->save();

                    $transaction->save();

                    if ($this->helper->isAutoInvoice()) {
                        if (!$order->canInvoice()) {
                            $order->addStatusHistoryComment('Sorry, Order cannot be invoiced.', false);
                        }
                        $invoice = $this->invoiceService->prepareInvoice($order);
                        if (!$invoice) {
                            $order->addStatusHistoryComment('Can\'t generate the invoice right now.', false);
                        }

                        if (!$invoice->getTotalQty()) {
                            $order->addStatusHistoryComment('Can\'t generate an invoice without products.', false);
                        }
                        $invoice->setRequestedCaptureCase(Invoice::CAPTURE_OFFLINE);
                        $invoice->register();
                        $invoice->getOrder()->setCustomerNoteNotify(true);
                        $invoice->getOrder()->setIsInProcess(true);
                        $transactionSave = $this->transactionFactory->create()->addObject($invoice)->addObject($invoice->getOrder());
                        $transactionSave->save();

                        try {
                            $this->invoiceSender->send($invoice);
                        } catch (\Magento\Framework\Exception\LocalizedException $e) {
                            $order->addStatusHistoryComment('Can\'t send the invoice Email right now.', false);
                        }

                        $order->addStatusHistoryComment('Automatically Invoice Generated.', false);
                        $order->save();
                    }
                    $this->messageManager->addSuccessMessage(__('Thank you for shopping with us. Your credit card has been charged and your transaction is successful. We will be shipping your order to you soon.'));
                    try {
                        $this->_redirect('checkout/onepage/success');
                        return;
                    } catch (\Magento\Framework\Exception\LocalizedException $e) {
                        $this->helper->debug('Order Response Error', (array)$e->getMessage());
                    }
                } else {
                    $errorMsg = 'Thank you for shopping with us. However, the transaction has been declined.';
                    if($paymentMode) {
                        $payment->setAdditionalInformation('payment_mode', $paymentMode);
                    }
                    if($transactionID) {
                        $payment->setAdditionalInformation('tran_id', $transactionID);
                    }
                    if($orderStatus) {
                        $payment->setAdditionalInformation('order_status', $orderStatus);
                    }
                    if($trackingID) {
                        $payment->setAdditionalInformation('tracking_id', $trackingID);
                    }

                    $payment->setAdditionalInformation((array)$payment->getAdditionalInformation());

                    $order->cancel()->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true,
                        'Gateway has declined the payment.');
                    $payment->setStatus('DECLINED');
                    $payment->setShouldCloseParentTransaction(1)->setIsTransactionClosed(1);
                    $payment->save();
                    $order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
                    $order->addStatusToHistory($order->getStatus(), $errorMsg);
                    $this->messageManager->addErrorMessage($errorMsg);
                    $this->checkoutSession->restoreQuote();
                    $order->save();
                    $this->_redirect('checkout/cart');
                    return;
                }
            }
        }
    }

    /**
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCheckoutSession()
    {
        return $this->checkoutSession;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
