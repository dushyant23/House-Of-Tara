<?php

namespace Meetanshi\Ccavenue\Controller\Adminhtml\Payment;

use Magento\Sales\Model\OrderFactory;
use Meetanshi\Ccavenue\Helper\Data;
use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Data\Form\FormKey\Validator;

class Request extends Action
{
    protected $orderFactory;
    protected $helper;
    protected $formKeyValidator;

    public function __construct(
        Action\Context $context,
        OrderFactory $orderFactory,
        Validator $formKeyValidator,
        Data $helper,
        $params = []
    )
    {
        $this->orderFactory = $orderFactory;
        $this->helper = $helper;
        $this->formKeyValidator = $formKeyValidator;
        parent::__construct($context);
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(__('Unable to process fetch order status.'));
            return $this->resultRedirectFactory->create()->setPath(
                'sales/order/view',
                [
                    'order_id' => $params['order_id']
                ]
            );
        }

        if ($params) {
            $order = $this->orderFactory->create()->load($params['order_id']);
            $incremtnId = $order->getIncrementId();
            $merchant_json_data =
                array(
                    'order_no' => "$incremtnId",
                    'reference_no' => ''
                );
            $order = $this->orderFactory->create()->load($params['order_id']);
            $access_code = $this->helper->getAccessCode();

            if ($this->helper->getMode()) {
                $apiUrl = "https://apitest.ccavenue.com/apis/servlet/DoWebTrans";
            } else {
                $apiUrl = "https://api.ccavenue.com/apis/servlet/DoWebTrans";
            }

            $merchant_data = json_encode($merchant_json_data);
            $encrypted_data = $this->helper->encrypt($merchant_data, $this->helper->getEncryptionKey());
            $final_data = 'command=orderStatusTracker&enc_request=' . $encrypted_data . '&access_code=' . $access_code . '&request_type=JSON&response_type=JSON';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: text/html,Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $final_data);

            $result = curl_exec($ch);
            curl_close($ch);
            $status = '';
            $information = explode('&', $result);

            $dataSize = sizeof($information);
            for ($i = 0; $i < $dataSize; $i++) {
                $info_value = explode('=', $information[$i]);
                if ($info_value[0] == 'enc_response') {
                    $status = $this->helper->decrypt(trim($info_value[1]), $this->helper->getEncryptionKey());

                }
            }

            $obj = json_decode($status, true);
            if (is_array($obj)) {
                if (isset($obj['Order_Status_Result'])) {
                    if (isset($obj['Order_Status_Result']['order_status'])) {
                        $payments = $order->getPayment();
                        $payments->setAdditionalInformation('order_status', $obj['Order_Status_Result']['order_status']);
                        $payments->save();
                        $order->save();

                        $this->messageManager->addSuccessMessage(__('You have successfully fetch order status.'));
                    }
                }
            }

            return $this->resultRedirectFactory->create()->setPath(
                'sales/order/view',
                [
                    'order_id' => $order->getId()
                ]
            );
        }
    }

    protected function _isAllowed()
    {
        return true;
    }
}
