<?php
/*
 * Copyright (c) 2024 Payfast (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 */
if ( !defined( 'DIR_CORE' ) ) {
    header( 'Location: static_pages/' );
}

class ControllerResponsesExtensionPaygate extends AController
{

    public $data = array();

    const PAYMENT_TYPE_CC = 'CC';
    const PAYMENT_TYPE_BT = 'BT';
    const PAYMENT_TYPE_EW = 'EW';
    const INIT_TRANS_URL = 'https://secure.paygate.co.za/payweb3/initiate.trans';


    public function main()
    {
        $this->loadLanguage( 'paygate/paygate' );
        $this->data['action'] = filter_var( $this->html->getSecureURL( 'extension/paygate/initiatePayWeb' ), FILTER_SANITIZE_URL );

        $this->load->model( 'checkout/order' );

        $order_info = $this->model_checkout_order->getOrder( $this->session->data['order_id'] );

        $back_url = $this->getBackUrl();

        if ( !$order_info ) {
            $this->redirect( $back_url );
        }

        $this->data['user_agent'] = 'AbanteCart 1.3.x';

        $this->data['back'] = $this->html->buildElement(
            array(
                'type'  => 'button',
                'name'  => 'back',
                'text'  => $this->language->get( 'button_back' ),
                'style' => 'button',
                'href'  => $back_url ) );

        $this->data['cancel_url']     = $back_url;
        $this->data['button_confirm'] = $this->html->buildElement(
            array(
                'type'  => 'submit',
                'name'  => $this->language->get( 'button_confirm' ),
                'style' => 'button',
            ) );

        //payment methods
        $paymentMethods = $this->getPaymentMethods();

        // Pass the array to the view/template
        $this->data['paymentMethods'] = $paymentMethods;

        $this->view->batchAssign( $this->data );
        $this->processTemplate( 'responses/extension/paygate.tpl' );

    }

    public function callback()
    {
        // notify paygate that notification has been received, if you dont notify Paygate then this callback will be called three times
        $post     = $this->request->post;
        $order_id = $this->session->data['order_id'];

        $this->load->model( 'checkout/order' );
        $order_info = $this->model_checkout_order->getOrder( $this->session->data['order_id'] );
        if ( !$order_info ) {
            return null;
        }

        // if all fine update order status
        $orderStatus = new AOrderStatus();

        switch ($post['TRANSACTION_STATUS']) {
            case '1':
            case '990017':
                $orderStatusId = $orderStatus->getStatusByTextId('processing');
                $message = 'Transaction Approved, Pay Request ID: ' . $post['PAY_REQUEST_ID'];
                $redirectUrl = filter_var($this->html->getSecureURL('checkout/success'), FILTER_SANITIZE_URL);
                break;
            case '4':
                $orderStatusId = $orderStatus->getStatusByTextId('canceled_by_customer');
                $message = 'Paygate Notify Response: User cancelled transaction, Pay Request ID: ' . $post['PAY_REQUEST_ID'];
                $redirectUrl = $this->html->getSecureURL('checkout/payment');
                break;
            default:
                $orderStatusId = $orderStatus->getStatusByTextId('failed');
                $message = 'Transaction Declined, Pay Request ID: ' . $post['PAY_REQUEST_ID'];
                $redirectUrl = $this->html->getSecureURL('checkout/payment');
        }

        $this->model_checkout_order->confirm($order_info['order_id'], $orderStatusId, $message);
        $this->model_checkout_order->update($order_id, $orderStatusId, $message);
        $this->model_checkout_order->updatePaymentMethodData($order_id, $post);

        if ($redirectUrl) {
            header('Location: ' . $redirectUrl);
            exit;
        } else {
            echo "<h1>Transaction Declined!</h1>";
            echo "This page will redirect automatically to homepage in 5 seconds...";
            $protocol = ( $_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off" ) ? "https" : "http";
            $home     = $protocol . "://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
            header( "refresh:5;url=" . $home );
        }
    }

    /**
     * @param $order_info
     *
     * @return float|int
     */
    public function getPreAmount($order_info): int|float
    {
        return $this->currency->format(
                $order_info['total'],
                $order_info['currency'],
                $order_info['value'],
                false
            ) * 100;
    }

    /**
     * @return array
     */
    public function getPaymentMethods(): array {
        $paymentMethods = array();
        $imagePath = 'extensions/paygate/image/';

        if ($this->config->get('paygate_credit_card') == '1') {
            $paymentMethods[] = ['name' => 'Credit Card', 'image' => $imagePath . 'mastercard-visa.svg'];
        }
        if ($this->config->get('paygate_bank_transfer') == '1') {
            $paymentMethods[] = ['name' => 'Bank Transfer', 'image' => $imagePath . 'sid.svg'];
        }
        if ($this->config->get('paygate_applepay') == '1') {
            $paymentMethods[] = ['name' => 'ApplePay', 'image' => $imagePath . 'apple-pay.svg'];
        }
        if ($this->config->get('paygate_samsungpay') == '1') {
            $paymentMethods[] = ['name' => 'SamsungPay', 'image' => $imagePath . 'samsung-pay.svg'];
        }
        if ($this->config->get('paygate_mobicred') == '1') {
            $paymentMethods[] = ['name' => 'Mobicred', 'image' => $imagePath . 'mobicred.svg'];
        }
        if ($this->config->get('paygate_momopay') == '1') {
            $paymentMethods[] = ['name' => 'MomoPay', 'image' => $imagePath . 'momopay.svg'];
        }
        if ($this->config->get('paygate_scan_to_pay') == '1') {
            $paymentMethods[] = ['name' => 'Scan To Pay', 'image' => $imagePath . 'scan-to-pay.svg'];
        }
        if ($this->config->get('paygate_snapscan') == '1') {
            $paymentMethods[] = ['name' => 'SnapScan', 'image' => $imagePath . 'snapscan.svg'];
        }
        if ($this->config->get('paygate_rcs') == '1') {
            $paymentMethods[] = ['name' => 'RCS', 'image' => $imagePath . 'rcs.svg'];
        }
        if ($this->config->get('paygate_zapper') == '1') {
            $paymentMethods[] = ['name' => 'Zapper', 'image' => $imagePath . 'zapper.svg'];
        }

        return $paymentMethods;
    }


    public function initiatePayWeb() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $selectedPaymentMethod = $this->request->post['payment_method'];
            $paymentDetail = '';

            switch ($selectedPaymentMethod) {
                case 'Credit Card':
                    $paymentDetail = 'pw3_credit_card';
                    $sm = self::PAYMENT_TYPE_CC;
                    break;
                case 'ApplePay':
                    $paymentDetail = 'Applepay';
                    $sm = self::PAYMENT_TYPE_CC;
                    break;
                case 'RCS':
                    $paymentDetail = 'RCS';
                    $sm = self::PAYMENT_TYPE_CC;
                    break;
                case 'Bank Transfer':
                    $paymentDetail = 'SID';
                    $sm = self::PAYMENT_TYPE_BT;
                    break;
                case 'SamsungPay':
                    $paymentDetail = 'Samsungpay';
                    $sm = self::PAYMENT_TYPE_EW;
                    break;
                case 'SnapScan':
                    $paymentDetail = 'SnapScan';
                    $sm = self::PAYMENT_TYPE_EW;
                    break;
                case 'Scan To Pay':
                    $paymentDetail = 'MasterPass';
                    $sm = self::PAYMENT_TYPE_EW;
                    break;
                case 'MomoPay':
                    $paymentDetail = 'Momopay';
                    $sm = self::PAYMENT_TYPE_EW;
                    break;
                case 'Zapper':
                case 'Mobicred':
                    $sm = self::PAYMENT_TYPE_EW;
                    $paymentDetail = $selectedPaymentMethod;
                    break;
                default:
                    $sm = '';// Default payment method code
            }
            $this->load->model( 'checkout/order' );
            $order_info = $this->model_checkout_order->getOrder( $this->session->data['order_id'] );

            $back_url = $this->getBackUrl();

            if ( !$order_info ) {
                $this->redirect( $back_url );
            }

            $preAmount = $this->getPreAmount($order_info);

            $paygateID = strip_tags( $this->config->get( 'paygate_merchant_id' ));
            $reference = strip_tags( 'abc_order' . $order_info['order_id'] );
            $amount    = filter_var( $preAmount, FILTER_SANITIZE_NUMBER_INT );
            $currency  = strip_tags( $order_info['currency'] );
            $notifyUrl = filter_var( $this->html->getSecureURL( 'extension/paygate/callback' ), FILTER_SANITIZE_URL );
            $returnUrl = filter_var( $this->html->getSecureURL( 'extension/paygate/callback' ), FILTER_SANITIZE_URL );
            $transDate = strip_tags( date( 'Y-m-d H:i:s' ) );
            $locale    = strip_tags( 'en-us' );

            $country_id = $order_info['payment_country_id'];
            $this->loadModel( 'localisation/country' );
            $country_info = $this->model_localisation_country->getCountry( $country_id );

            $country = strip_tags( $country_info['iso_code_3'] );
            $email   = filter_var( $order_info['email'], FILTER_SANITIZE_EMAIL );

            $userField1 = $this->session->data['order_id'];
            $userField2 = filter_var( $this->config->get( 'store_main_email' ), FILTER_SANITIZE_EMAIL );
            $userField3 = 'abantecart-v1.1.0';

            $encryption_key = $this->config->get( 'paygate_merchant_key' );

            $data = [
                'PAYGATE_ID'        => $paygateID,
                'REFERENCE'         => $reference,
                'AMOUNT'            => $amount,
                'CURRENCY'          => $currency,
                'RETURN_URL'        => $returnUrl,
                'TRANSACTION_DATE'  => $transDate,
                'LOCALE'            => $locale,
                'COUNTRY'           => $country,
                'EMAIL'             => $email,
                'PAY_METHOD'        => $sm,
                'PAY_METHOD_DETAIL' => $paymentDetail,
                'NOTIFY_URL'        => $notifyUrl,
                'USER1'             => $userField1,
                'USER2'             => $userField2,
                'USER3'             => $userField3
            ];

            // checksum
            $checksum        = $this->getChecksum($encryption_key, $data);
            $data['CHECKSUM'] = $checksum;


            //Now make curl request to initiate endpoint
            $fieldsString = http_build_query($data);

            $result = $this->doCurl(self::INIT_TRANS_URL, $fieldsString);

            parse_str($result, $output);

            if (isset($output['ERROR'])) {
                $errorText = sprintf($this->language->get('paygate_error_text'), $output['ERROR'], $this->html->getSecureURL('content/contact'));
                $error = new AError($errorText);
                $error->toLog()->toDebug()->toMessages();
                $this->data['error'] = $errorText;

                $return = filter_var( $this->html->getSecureURL( 'checkout' ), FILTER_SANITIZE_URL );
                header( 'Location: ' . $return );
            } else {
                echo  $this->getPaygatePostForm($output['PAY_REQUEST_ID'], $output['CHECKSUM']);
            }

        } else {
            // Handle the case when the form is not submitted properly
            $return = filter_var( $this->html->getSecureURL( 'checkout' ), FILTER_SANITIZE_URL );
            header( 'Location: ' . $return );
        }
    }

    /**
     * @return mixed
     */
    public function getBackUrl()
    {
        if ($this->request->get['rt'] != 'checkout/guest_step_3') {
            $back_url = $this->html->getSecureURL('checkout/payment');
        } else {
            $back_url = $this->html->getSecureURL('checkout/guest_step_2');
        }

        return $back_url;
    }

    private function getChecksum($key, $data)
    {
        $check = '';
        if ($data) {
            foreach ($data as $d) {
                $check .= $d;
            }
        }
        $check .= $key;

        return md5($check);
    }

    /**
     * @param $url
     * @param $fieldsString
     *
     * @return bool|string
     */
    private function doCurl($url, $fieldsString)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldsString);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    public static function getPaygatePostForm($payRequestId, $checksum)
    {
        return <<<HTML
    <form action="https://secure.paygate.co.za/payweb3/process.trans" method="post" name="paygate">
        <input name="PAY_REQUEST_ID" type="hidden" value="{$payRequestId}" />
        <input name="CHECKSUM" type="hidden" value="$checksum" />
    </form>
    <script type="text/javascript">
        document.forms['paygate'].submit();
    </script>
HTML;
    }
}
