<?php

namespace Meetanshi\Ccavenue\Plugin\Sales\Block\Adminhtml\Order;

use Magento\Sales\Block\Adminhtml\Order\View as OrderView;
use Magento\Sales\Model\OrderFactory;

class View
{
    private $orderFactory;

    public function __construct(OrderFactory $orderFactory)
    {
        $this->orderFactory = $orderFactory;
    }

    public function beforeSetLayout(OrderView $subject)
    {
        $order = $this->orderFactory->create()->load($subject->getOrderId());
        $name = $order->getPayment()->getMethod();
        if ($name == 'ccavenue') {
            $buttonUrl = $subject->getUrl(
                'ccavenue/payment/request',
                ['order_id' => $subject->getOrderId(), 'form_key' => $subject->getFormKey()]
            );

            $subject->addButton(
                'ccavenue_inquiry_button',
                [
                    'label' => __('Ccavenue Status'),
                    'class' => __('custom-button'),
                    'id' => 'order-view-custom-button',
                    'onclick' => 'setLocation(\'' . $buttonUrl . '\')'
                ]
            );
        }
    }
}
