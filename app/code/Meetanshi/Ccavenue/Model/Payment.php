<?php

namespace Meetanshi\Ccavenue\Model;

use Meetanshi\Ccavenue\Helper\Data as CcavenueHelper;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Url;
use Magento\Directory\Model\RegionFactory;
use Magento\Directory\Model\CountryFactory;
use Magento\Checkout\Model\Session;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Quote\Api\Data\CartInterface;
use Meetanshi\Ccavenue\Block\Payment\Info;
use Magento\Payment\Model\InfoInterface;

/**
 * Class Payment
 * @package Meetanshi\Ccavenue\Model
 */
class Payment extends AbstractMethod
{
    /**
     *
     */
    const CODE = 'ccavenue';

    /**
     * @var string
     */
    protected $_code = self::CODE;
    /**
     * @var string
     */
    protected $_infoBlockType = Info::class;
    /**
     * @var bool
     */
    protected $_isGateway = true;
    /**
     * @var bool
     */
    protected $_canCapture = true;
    /**
     * @var bool
     */
    protected $_canRefund = true;
    /**
     * @var bool
     */
    protected $_canAuthorize = true;
    /**
     * @var bool
     */
    protected $_canUseInternal = false;

    /**
     * @var CcavenueHelper
     */
    protected $ccavenueHelper;

    /**
     * Payment constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param PaymentHelper $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param ModuleListInterface $moduleList
     * @param TimezoneInterface $localeDate
     * @param OrderFactory $orderFactory
     * @param Url $urlBuilder
     * @param RegionFactory $region
     * @param CountryFactory $country
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param CcavenueHelper $ccavenueHelper
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(Context $context, Registry $registry, ExtensionAttributesFactory $extensionFactory, AttributeValueFactory $customAttributeFactory, PaymentHelper $paymentData, ScopeConfigInterface $scopeConfig, Logger $logger, ModuleListInterface $moduleList, TimezoneInterface $localeDate, OrderFactory $orderFactory, Url $urlBuilder, RegionFactory $region, CountryFactory $country, Session $checkoutSession, StoreManagerInterface $storeManager, CcavenueHelper $ccavenueHelper, AbstractResource $resource = null, AbstractDb $resourceCollection = null, array $data = [])
    {
        $this->urlBuilder = $urlBuilder;
        $this->moduleList = $moduleList;
        $this->scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->region = $region;
        $this->country = $country;
        $this->logger = $logger;
        $this->ccavenueHelper = $ccavenueHelper;

        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $resource, $resourceCollection, $data);
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     * @return AbstractMethod
     */
    public function initialize($paymentAction, $stateObject)
    {
        return parent::initialize($paymentAction, $stateObject);
    }

    /**
     * @param CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(CartInterface $quote = null)
    {
        $available = $this->ccavenueHelper->isPaymentAvailable();
        if (!$available) {
            return false;
        } else {
            return parent::isAvailable($quote);
        }
    }

    /**
     * @return AbstractMethod
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validate()
    {
        return parent::validate();
    }

    /**
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return $this->urlBuilder->getUrl('ccavenue/payment/redirect', ['_secure' => true]);
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return AbstractMethod
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        return parent::authorize($payment, $amount);
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return AbstractMethod
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function capture(InfoInterface $payment, $amount)
    {
        return parent::capture($payment, $amount);
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return AbstractMethod
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund(InfoInterface $payment, $amount)
    {
        return parent::refund($payment, $amount);
    }
}
