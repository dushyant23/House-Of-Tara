<?php

namespace Meetanshi\Ccavenue\Helper;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Session\SessionManager;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\RegionFactory;

/**
 * Class Data
 * @package Meetanshi\Ccavenue\Helper
 */
class Data extends AbstractHelper
{
    const CONFIG_CCAVENUE_ACTIVE = 'payment/ccavenue/active';

    const CONFIG_CCAVENUE_MODE = 'payment/ccavenue/mode';

    const CONFIG_CCAVENUE_LOGO = 'payment/ccavenue/show_logo';

    const CONFIG_CCAVENUE_INSTRUCTIONS = 'payment/ccavenue/instructions';

    const CONFIG_CCAVENUE_SANDBOX_MERCHANT_ID = 'payment/ccavenue/sandbox_merchant_id';
    const CONFIG_CCAVENUE_LIVE_MERCHANT_ID = 'payment/ccavenue/live_merchant_id';

    const CONFIG_CCAVENUE_SANDBOX_ACCESS_CODE = 'payment/ccavenue/sandbox_access_code';
    const CONFIG_CCAVENUE_LIVE_ACCESS_CODE = 'payment/ccavenue/live_access_code';

    const CONFIG_CCAVENUE_SANDBOX_ENCRYPTION_KEY = 'payment/ccavenue/sandbox_encryption_key';
    const CONFIG_CCAVENUE_LIVE_ENCRYPTION_KEY = 'payment/ccavenue/live_encryption_key';

    const CONFIG_CCAVENUE_SANDBOX_GATEWAY_URL = 'payment/ccavenue/sandbox_gateway_url';
    const CONFIG_CCAVENUE_LIVE_GATEWAY_URL = 'payment/ccavenue/live_gateway_url';

    const CONFIG_CCAVENUE_DEBUG = 'payment/ccavenue/debug';

    const CONFIG_CCAVENUE_INVOICE = 'payment/ccavenue/allow_invoice';

    /**
     * @var DirectoryList
     */
    protected $directoryList;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var Http
     */
    protected $request;
    /**
     * @var EncryptorInterface
     */
    protected $encryptor;
    /**
     * @var SessionManager
     */
    protected $sessionManager;
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;
    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var CountryFactory
     */
    protected $directoryFactory;

    /**
     * @var RegionFactory
     */
    protected $regionFactory;

    /**
     * Data constructor.
     * @param Context $context
     * @param EncryptorInterface $encryptor
     * @param DirectoryList $directoryList
     * @param StoreManagerInterface $storeManager
     * @param Http $request
     * @param SessionManager $sessionManager
     * @param Repository $repository
     * @param CheckoutSession $checkoutSession
     * @param CountryFactory $directoryFactory
     * @param RegionFactory $regionFactory
     */
    public function __construct(
        Context $context,
        EncryptorInterface $encryptor,
        DirectoryList $directoryList,
        StoreManagerInterface $storeManager,
        Http $request,
        SessionManager $sessionManager,
        Repository $repository,
        CheckoutSession $checkoutSession,
        CountryFactory $directoryFactory,
        RegionFactory $regionFactory
    ) {
        parent::__construct($context);
        $this->encryptor = $encryptor;
        $this->directoryList = $directoryList;
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->sessionManager = $sessionManager;
        $this->repository = $repository;
        $this->checkoutSession = $checkoutSession;
        $this->directoryFactory = $directoryFactory;
        $this->regionFactory = $regionFactory;
    }

    /**
     * @return mixed
     */
    public function isDebugActive()
    {
        return $this->scopeConfig->getValue(self::CONFIG_CCAVENUE_DEBUG, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function isAutoInvoice()
    {
        return $this->scopeConfig->getValue(self::CONFIG_CCAVENUE_INVOICE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function isActive()
    {
        return $this->scopeConfig->getValue(self::CONFIG_CCAVENUE_ACTIVE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getPaymentInstructions()
    {
        return $this->scopeConfig->getValue(self::CONFIG_CCAVENUE_INSTRUCTIONS, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     */
    public function isPaymentAvailable()
    {
        $installationID = trim($this->getMerchantID());
        if (!$installationID) {
            return false;
        }
        return true;
    }

    /**
     * @return string
     */
    public function getMerchantID()
    {
        if ($this->getMode()) {
            return $this->scopeConfig->getValue(self::CONFIG_CCAVENUE_SANDBOX_MERCHANT_ID, ScopeInterface::SCOPE_STORE);
        } else {
            return $this->scopeConfig->getValue(self::CONFIG_CCAVENUE_LIVE_MERCHANT_ID, ScopeInterface::SCOPE_STORE);
        }
    }

    /**
     * @return string
     */
    public function getAccessCode()
    {
        if ($this->getMode()) {
            return $this->encryptor->decrypt($this->scopeConfig->getValue(self::CONFIG_CCAVENUE_SANDBOX_ACCESS_CODE,
                ScopeInterface::SCOPE_STORE));
        } else {
            return $this->encryptor->decrypt($this->scopeConfig->getValue(self::CONFIG_CCAVENUE_LIVE_ACCESS_CODE,
                ScopeInterface::SCOPE_STORE));
        }
    }

    /**
     * @return string
     */
    public function getEncryptionKey()
    {
        if ($this->getMode()) {
            return $this->encryptor->decrypt($this->scopeConfig->getValue(self::CONFIG_CCAVENUE_SANDBOX_ENCRYPTION_KEY,
                ScopeInterface::SCOPE_STORE));
        } else {
            return $this->encryptor->decrypt($this->scopeConfig->getValue(self::CONFIG_CCAVENUE_LIVE_ENCRYPTION_KEY,
                ScopeInterface::SCOPE_STORE));
        }
    }

    /**
     * @return mixed
     */
    public function getMode()
    {
        return $this->scopeConfig->getValue(self::CONFIG_CCAVENUE_MODE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param $order
     * @return string
     */
    public function getPaymentForm($order)
    {
        $data = [];
        $amount = $order->getGrandTotal();
        $currencyCode = $order->getOrderCurrencyCode();
        $merchantID = $this->getMerchantID();
        $encryptionKey = $this->getEncryptionKey();
        $accessCode = $this->getAccessCode();
        $orderID = $order->getIncrementId();

        $billingAddress = $order->getBillingAddress()->getData();

        $streetData = $billingAddress['street'];
        $streetList = preg_split("/\\r\\n|\\r|\\n/", $streetData);

        $billingStreetAddress = $streetList[0];

        $bilingRegionCode = $shippingRegionCode = '';

        if ($billingAddress['region_id']) {
            $bilingRegionCode = $this->getRegionCode($billingAddress['region_id']);
        }

        if (!$order->getIsVirtual()) {
            $shippingAddress = $order->getShippingAddress()->getData();
        } else {
            $shippingAddress = $order->getBillingAddress()->getData();
        }

        if ($shippingAddress['region_id']) {
            $shippingRegionCode = $this->getRegionCode($shippingAddress['region_id']);
        }

        $shippingStreetData = $shippingAddress['street'];

        $shippingStreetList = preg_split("/\\r\\n|\\r|\\n/", $shippingStreetData);

        $shippingStreetAddress = $shippingStreetList[0];

        $couponCode = '';
        if ($order->getCouponCode()) {
            $couponCode = $order->getCouponCode();
        }

        try {

            $data['tid'] = time();

            $data['merchant_id'] = $merchantID;

            $data['order_id'] = $orderID;

            $data['amount'] = $amount;

            $data['currency'] = $currencyCode;

            $data['redirect_url'] = $this->getCallbackUrl();

            $data['cancel_url'] = $this->getCallbackUrl();

            $data['language'] = 'EN';

            $data['billing_name'] = $billingAddress['firstname'] . ' ' . $billingAddress['lastname'];

            $data['billing_address'] = $billingStreetAddress;

            $data['billing_city'] = $billingAddress['city'];

            $data['billing_state'] = $bilingRegionCode;

            $data['billing_zip'] = $billingAddress['postcode'];

            $data['billing_country'] = $this->getCountryName($billingAddress['country_id']);

            $data['billing_tel'] = $billingAddress['telephone'];

            $data['billing_email'] = $billingAddress['email'];

            $data['delivery_name'] = $shippingAddress['firstname'] . ' ' . $shippingAddress['lastname'];

            $data['delivery_address'] = $shippingStreetAddress;

            $data['delivery_city'] = $shippingAddress['city'];

            $data['delivery_state'] = $shippingRegionCode;

            $data['delivery_zip'] = $shippingAddress['postcode'];

            $data['delivery_country'] = $this->getCountryName($shippingAddress['country_id']);

            $data['delivery_tel'] = $shippingAddress['telephone'];

            $data['merchant_param1'] = '';
            $data['merchant_param2'] = '';
            $data['merchant_param3'] = '';
            $data['merchant_param4'] = '';

            $data['promo_code'] = '';

            $data['customer_identifier'] = '';
        } catch (\Exception $e) {
            $this->debug('Error', (array)$e->getMessage());
        }

        $this->debug('Request', $data);
        $merchantData = '2';
        foreach ($data as $key => $value) {
            $merchantData .= $key . '=' . $value . '&';
        }

        $encryptedData = $this->encrypt($merchantData, $encryptionKey);

        $html = "<form id='CcavenueForm' name='ccavenuehostedsubmit' action='" . $this->getGatewayUrl() . "' method='POST'>";
        $html .= "<input type='hidden' name='encRequest' value='" . $encryptedData . "' />";
        $html .= "<input type='hidden' name='access_code' value='" . $accessCode . "' />";
        $html .= "</form>";

        return $html;
    }

    /**
     * @return mixed
     */
    public function getGatewayUrl()
    {
        if ($this->getMode()) {
            return $this->scopeConfig->getValue(self::CONFIG_CCAVENUE_SANDBOX_GATEWAY_URL, ScopeInterface::SCOPE_STORE);
        } else {
            return $this->scopeConfig->getValue(self::CONFIG_CCAVENUE_LIVE_GATEWAY_URL, ScopeInterface::SCOPE_STORE);
        }
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCallbackUrl()
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        return $baseUrl . "ccavenue/payment/success";
    }

    /**
     * @return string
     */
    public function getPaymentSubject()
    {
        $subject = trim($this->scopeConfig->getValue('general/store_information/name', ScopeInterface::SCOPE_STORE));
        if (!$subject) {
            return "Magento 2 order";
        }

        return $subject;
    }

    /**
     * @return mixed
     */
    public function showLogo()
    {
        return $this->scopeConfig->getValue(self::CONFIG_CCAVENUE_LOGO, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getPaymentLogo()
    {
        $params = ['_secure' => $this->request->isSecure()];
        return $this->repository->getUrlWithParams('Meetanshi_Ccavenue::images/ccavenue.png', $params);
    }

    /**
     * @param $plainText
     * @param $key
     * @return string
     */
    public function encrypt($plainText, $key)
    {
        $key = $this->hextobin(hash('md5', $key));
        $initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d,
            0x0e, 0x0f);
        $openMode = openssl_encrypt($plainText, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $initVector);
        $encryptedText = bin2hex($openMode);
        return $encryptedText;
    }

    /**
     * @param $encryptedText
     * @param $key
     * @return false|string
     */
    public function decrypt($encryptedText, $key)
    {
        $key = $this->hextobin(hash('md5', $key));
        $initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d,
            0x0e, 0x0f);
        $encryptedText = $this->hextobin($encryptedText);
        $decryptedText = openssl_decrypt($encryptedText, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $initVector);
        return $decryptedText;
    }

    //*********** Padding Function *********************

    /**
     * @param $plainText
     * @param $blockSize
     * @return string
     */
    public function pkcs5_pad($plainText, $blockSize)
    {
        $pad = $blockSize - (strlen($plainText) % $blockSize);
        return $plainText . str_repeat(chr($pad), $pad);
    }

    //********** Hexadecimal to Binary function for php 4.0 version ********

    /**
     * @param $hexString
     * @return false|string
     */
    public function hextobin($hexString)
    {
        $length = strlen($hexString);
        $binString = "";
        $count = 0;
        while ($count < $length) {
            $subString = substr($hexString, $count, 2);
            $packedString = pack("H*", $subString);
            if ($count == 0) {
                $binString = $packedString;
            } else {
                $binString .= $packedString;
            }

            $count += 2;
        }
        return $binString;
    }

    /**
     * @param $code
     * @return string
     */
    public function getCountryName($code)
    {
        return $this->directoryFactory->create()->load($code)->getName();
    }

    /**
     * @param $message
     * @param array $context
     */
    public function debug($message, array $context = [])
    {
        if ($this->isDebugActive()) {
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/ccavenue.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $message = "CCAvenue : " . $message;
            if (!is_array($context)) {
                $context = [$context];
            }
            $logger->info($message);
            $logger->info(print_r($context, true));
        }
    }

    /**
     * @param $regionId
     * @return string
     */
    public function getRegionCode($regionId)
    {
        $region = $this->regionFactory->create()->load($regionId);
        return $region->getCode();
    }
}
