<?php

namespace Meetanshi\Ccavenue\Block\Payment;

use Magento\Payment\Block\ConfigurableInfo;

/**
 * Class Info
 * @package Meetanshi\Ccavenue\Block\Payment
 */
class Info extends ConfigurableInfo
{
    /**
     * @var string
     */
    protected $_template = 'Meetanshi_Ccavenue::info.phtml';

    /**
     * @param string $field
     * @return \Magento\Framework\Phrase|string
     */
    public function getLabel($field)
    {
        switch ($field) {
            case 'payment_mode':
                return __('Payment Mode');
            case 'tran_id':
                return __('Transaction ID');
            case 'tracking_id':
                return __('Tracking ID');
            case 'order_status':
                return __('Order Status');
            default:
                break;
        }
    }
}
