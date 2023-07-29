<?php

namespace Meetanshi\Ccavenue\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Meetanshi\Ccavenue\Helper\Data as HelperData;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;

/**
 * Class CcavenueUrl
 * @package Meetanshi\Ccavenue\Model\Resolver
 */
class CcavenueUrl implements ResolverInterface
{
    /**
     * @var HelperData
     */
    protected $helper;
    /**
     * @var UrlInterface
     */
    protected $_url;
    /**
     * @var OrderInterfaceFactory
     */
    protected $orderFactory;

    /**
     * CcavenueUrl constructor.
     * @param HelperData $helper
     * @param OrderInterfaceFactory $orderFactory
     * @param UrlInterface $url
     */
    public function __construct(
        HelperData $helper,
        OrderInterfaceFactory $orderFactory,
        UrlInterface $url
    )
    {
        $this->helper = $helper;
        $this->_url = $url;
        $this->orderFactory = $orderFactory;
    }

    /**
     * @param Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|\Magento\Framework\GraphQl\Query\Resolver\Value|mixed
     * @throws GraphQlNoSuchEntityException
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    )
    {
        if (!isset($args['input']['order_increment_id'])) {
            throw new GraphQlNoSuchEntityException(__("Please pass order_increment_id"));
        }
        if (!isset($args['input']['callBackUrl'])) {
            throw new GraphQlNoSuchEntityException(__("Please pass callBackUrl"));
        }

        $orderIncrementId = $args['input']['order_increment_id'];
        $callBackUrl = $args['input']['callBackUrl'];

        $returnData = [];
        $returnData['success'] = false;
        $returnData['gatewayurl'] = "";
        $returnData['encRequest'] = "";
        $returnData['access_code'] = "";

        $order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);

        if ($order) {
            $data = [];
            $amount = $order->getGrandTotal();
            $currencyCode = $order->getOrderCurrencyCode();
            $merchantID = $this->helper->getMerchantID();
            $encryptionKey = $this->helper->getEncryptionKey();
            $accessCode = $this->helper->getAccessCode();
            $orderID = $order->getIncrementId();

            $billingAddress = $order->getBillingAddress()->getData();

            $streetData = $billingAddress['street'];
            $streetList = preg_split("/\\r\\n|\\r|\\n/", $streetData);

            $billingStreetAddress = $streetList[0];

            $bilingRegionCode = $shippingRegionCode = '';

            if ($billingAddress['region_id']) {
                $bilingRegionCode = $this->helper->getRegionCode($billingAddress['region_id']);
            }

            if (!$order->getIsVirtual()) {
                $shippingAddress = $order->getShippingAddress()->getData();
            } else {
                $shippingAddress = $order->getBillingAddress()->getData();
            }

            if ($shippingAddress['region_id']) {
                $shippingRegionCode = $this->helper->getRegionCode($shippingAddress['region_id']);
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
                $data['redirect_url'] = $callBackUrl;
                $data['cancel_url'] = $callBackUrl;
                $data['language'] = 'EN';
                $data['billing_name'] = $billingAddress['firstname'] . ' ' . $billingAddress['lastname'];
                $data['billing_address'] = $billingStreetAddress;
                $data['billing_city'] = $billingAddress['city'];
                $data['billing_state'] = $bilingRegionCode;
                $data['billing_zip'] = $billingAddress['postcode'];
                $data['billing_country'] = $this->helper->getCountryName($billingAddress['country_id']);
                $data['billing_tel'] = $billingAddress['telephone'];
                $data['billing_email'] = $billingAddress['email'];
                $data['delivery_name'] = $shippingAddress['firstname'] . ' ' . $shippingAddress['lastname'];
                $data['delivery_address'] = $shippingStreetAddress;
                $data['delivery_city'] = $shippingAddress['city'];
                $data['delivery_state'] = $shippingRegionCode;
                $data['delivery_zip'] = $shippingAddress['postcode'];
                $data['delivery_country'] = $this->helper->getCountryName($shippingAddress['country_id']);
                $data['delivery_tel'] = $shippingAddress['telephone'];
                $data['merchant_param1'] = '';
                $data['merchant_param2'] = '';
                $data['merchant_param3'] = '';
                $data['merchant_param4'] = '';
                $data['promo_code'] = $couponCode;
                $data['customer_identifier'] = '';
            } catch (\Exception $e) {
                $this->helper->debug('Error', (array)$e->getMessage());
                throw new GraphQlNoSuchEntityException(__($e->getMessage()));
            }

            $this->helper->debug('Request', $data);
            $merchantData = '2';
            foreach ($data as $key => $value) {
                $merchantData .= $key . '=' . $value . '&';
            }

            $encryptedData = $this->helper->encrypt($merchantData, $encryptionKey);

            $returnData['success'] = true;
            $returnData['gatewayurl'] = $this->helper->getGatewayUrl();
            $returnData['encRequest'] = $encryptedData;
            $returnData['access_code'] = $accessCode;

        } else {
            throw new GraphQlNoSuchEntityException(__("Order Not Found."));
        }

        return $returnData;

    }
}
