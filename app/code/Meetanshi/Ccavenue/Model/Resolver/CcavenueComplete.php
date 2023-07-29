<?php

namespace Meetanshi\Ccavenue\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Meetanshi\Ccavenue\Helper\Data as HelperData;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\Builder;
use Magento\Sales\Model\OrderNotifier;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;

/**
 * Class CcavenueComplete
 * @package Meetanshi\Ccavenue\Model\Resolver
 */
class CcavenueComplete implements ResolverInterface
{
    /**
     * @var HelperData
     */
    protected $helper;
    /**
     * @var OrderInterfaceFactory
     */
    protected $orderFactory;
    /**
     * @var Builder
     */
    protected $transactionBuilder;
    /**
     * @var OrderNotifier
     */
    protected $orderSender;
    /**
     * @var InvoiceService
     */
    protected $invoiceService;
    /**
     * @var TransactionFactory
     */
    protected $transactionFactory;
    /**
     * @var InvoiceSender
     */
    protected $invoiceSender;

    /**
     * CcavenueComplete constructor.
     * @param HelperData $helper
     * @param OrderInterfaceFactory $orderFactory
     * @param Builder $transactionBuilder
     * @param OrderNotifier $orderSender
     * @param InvoiceService $invoiceService
     * @param TransactionFactory $transactionFactory
     * @param InvoiceSender $invoiceSender
     */
    public function __construct(
        HelperData $helper,
        OrderInterfaceFactory $orderFactory,
        Builder $transactionBuilder,
        OrderNotifier $orderSender,
        InvoiceService $invoiceService,
        TransactionFactory $transactionFactory,
        InvoiceSender $invoiceSender
    )
    {
        $this->helper = $helper;
        $this->orderFactory = $orderFactory;
        $this->transactionBuilder = $transactionBuilder;
        $this->orderSender = $orderSender;
        $this->invoiceService = $invoiceService;
        $this->transactionFactory = $transactionFactory;
        $this->invoiceSender = $invoiceSender;
    }

    /**
     * @param Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|\Magento\Framework\GraphQl\Query\Resolver\Value|mixed
     * @throws GraphQlNoSuchEntityException
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    )
    {
        if (!isset($args['input']['orderNo'])) {
            throw new GraphQlNoSuchEntityException(__('Required parameter "orderNo" is missing.'));
        }
        if (!isset($args['input']['encResp'])) {
            throw new GraphQlNoSuchEntityException(__('Required parameter "encResp" is missing.'));
        }

        $returnData = [];
        $returnData['success'] = false;
        $returnData['order_id'] = "";

        $order = $this->orderFactory->create()->loadByIncrementId($args['input']['orderNo']);
        $payment = $order->getPayment();
        $workingKey = $this->helper->getEncryptionKey();
        $encResponse = $args['input']['encResp'];
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
            try {
                $this->_redirect('checkout/onepage/success');
                $returnData['success'] = true;
                $returnData['order_id'] = $order->getIncrementId();
                return $returnData;

            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->helper->debug('Order Response Error', (array)$e->getMessage());
            }
        } else {
            $errorMsg = 'Thank you for shopping with us. However, the transaction has been declined.';
            if ($paymentMode) {
                $payment->setAdditionalInformation('payment_mode', $paymentMode);
            }
            if ($transactionID) {
                $payment->setAdditionalInformation('tran_id', $transactionID);
            }
            if ($orderStatus) {
                $payment->setAdditionalInformation('order_status', $orderStatus);
            }
            if ($trackingID) {
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
            $order->save();

            $returnData['success'] = false;
            $returnData['order_id'] = $order->getIncrementId();
            return $returnData;
        }

        return $returnData;
    }
}
