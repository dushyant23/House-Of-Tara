<?php

namespace Meetanshi\Ccavenue\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Store\Model\StoreManagerInterface;
use Meetanshi\Ccavenue\Helper\Data;

/**
 * Class CcavenueConfigProvider
 * @package Meetanshi\Ccavenue\Model
 */
class CcavenueConfigProvider implements ConfigProviderInterface
{
    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var array
     */
    protected $methodCodes = ['ccavenue'];
    /**
     * @var array
     */
    protected $methods = [];

    /**
     * CcavenueConfigProvider constructor.
     * @param Data $helper
     * @param PaymentHelper $paymentHelper
     * @param StoreManagerInterface $storeManager
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(Data $helper, PaymentHelper $paymentHelper, StoreManagerInterface $storeManager)
    {
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $paymentHelper->getMethodInstance($code);
        }
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getConfig()
    {
        $redirectUrl = $this->storeManager->getStore()->getBaseUrl() . 'ccavenue/payment/redirect';
        $showLogo = $this->helper->showLogo();
        $imageUrl = $this->helper->getPaymentLogo();

        $config = [];
        $config['payment']['ccavenue_payment']['imageurl'] = ($showLogo) ? $imageUrl : '';
        $config['payment']['ccavenue_payment']['is_active'] = $this->helper->isActive();
        $config['payment']['ccavenue_payment']['payment_instruction'] = trim($this->helper->getPaymentInstructions());
        $config['payment']['ccavenue_payment']['redirect_url'] = $redirectUrl;

        return $config;
    }
}
