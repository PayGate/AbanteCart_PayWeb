<?php
/*
 * Copyright (c) 2018 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 * 
 * Released under the GNU General Public License
 */
if ( !defined( 'DIR_CORE' ) ) {
    header( 'Location: static_pages/' );
}

class ControllerResponsesExtensionPayGate extends AController
{

    public $data = array();

    public function main()
    {

        $this->loadLanguage( 'paygate/paygate' );
        $this->data['action'] = 'https://secure.paygate.co.za/payweb3/process.trans';

        $this->load->model( 'checkout/order' );
        $order_info = $this->model_checkout_order->getOrder( $this->session->data['order_id'] );

        if ( $this->request->get['rt'] != 'checkout/guest_step_3' ) {
            $back_url = $this->html->getSecureURL( 'checkout/payment' );
        } else {
            $back_url = $this->html->getSecureURL( 'checkout/guest_step_2' );
        }

        if ( !$order_info ) {
            $this->redirect( $back_url );
        }

        $this->data['user_agent'] = 'AbanteCart 1.2.x';

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

        $preAmount = $this->currency->format( $order_info['total'], $order_info['currency'], $order_info['value'], false ) * 100;

        $paygateID = filter_var( $this->config->get( 'paygate_merchant_id' ), FILTER_SANITIZE_STRING );
        $reference = filter_var( 'abc_order' . $order_info['order_id'], FILTER_SANITIZE_STRING );
        $amount    = filter_var( $preAmount, FILTER_SANITIZE_NUMBER_INT );
        $currency  = filter_var( $order_info['currency'], FILTER_SANITIZE_STRING );
        $notifyUrl = filter_var( $this->html->getSecureURL( 'extension/paygate/callback' ), FILTER_SANITIZE_URL );
        $returnUrl = filter_var( $this->html->getSecureURL( 'extension/paygate/callback' ), FILTER_SANITIZE_URL );
        $transDate = filter_var( date( 'Y-m-d H:i:s' ), FILTER_SANITIZE_STRING );
        $locale    = filter_var( 'en-us', FILTER_SANITIZE_STRING );

        $country_id = $order_info['payment_country_id'];
        $this->loadModel( 'localisation/country' );
        $country_info = $this->model_localisation_country->getCountry( $country_id );

        $country = filter_var( $country_info['iso_code_3'], FILTER_SANITIZE_STRING );
        $email   = filter_var( $order_info['email'], FILTER_SANITIZE_EMAIL );

        $userField1 = $this->session->data['order_id'];
        $userField2 = filter_var( $this->config->get( 'store_main_email' ), FILTER_SANITIZE_EMAIL );
        $userField3 = 'abantecart-v1.0.1';

        $encryption_key = $this->config->get( 'paygate_merchant_key' );

        $checksum_source = $paygateID;
        $checksum_source .= $reference;
        $checksum_source .= $amount;
        $checksum_source .= $currency;
        $checksum_source .= $returnUrl;
        $checksum_source .= $transDate;

        if ( $locale ) {
            $checksum_source .= $locale;
        }

        if ( $country ) {
            $checksum_source .= $country;
        }

        if ( $email ) {
            $checksum_source .= $email;
        }

        if ( $notifyUrl ) {
            $checksum_source .= $notifyUrl;
        }

        if ( $userField1 ) {
            $checksum_source .= $userField1;
        }

        if ( $userField2 ) {
            $checksum_source .= $userField2;
        }

        if ( $userField3 ) {
            $checksum_source .= $userField3;
        }

        $checksum_source .= $encryption_key;

        $checksum = md5( $checksum_source );

        $initiateData = array(
            'PAYGATE_ID'       => $paygateID,
            'REFERENCE'        => $reference,
            'AMOUNT'           => $amount,
            'CURRENCY'         => $currency,
            'RETURN_URL'       => $returnUrl,
            'TRANSACTION_DATE' => $transDate,
            'LOCALE'           => $locale,
            'COUNTRY'          => $country,
            'EMAIL'            => $email,
            'NOTIFY_URL'       => $notifyUrl,
            'USER1'            => $userField1,
            'USER2'            => $userField2,
            'USER3'            => $userField3,
            'CHECKSUM'         => $checksum,
        );
        $CHECKSUM       = null;
        $PAY_REQUEST_ID = null;
        $fields_string  = '';
        foreach ( $initiateData as $key => $value ) {
            $fields_string .= $key . '=' . urlencode( $value ) . '&';
        }
        $fields_string = rtrim( $fields_string, '&' );

        // open connection
        $ch = curl_init();
        // set the url, number of POST vars, POST data
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt( $ch, CURLOPT_URL, 'https://secure.paygate.co.za/payweb3/initiate.trans' );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_NOBODY, false );
        curl_setopt( $ch, CURLOPT_REFERER, $_SERVER['HTTP_HOST'] );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $fields_string );
        // execute post
        $result = curl_exec( $ch );
        // close connection
        curl_close( $ch );

        parse_str( $result );

        if ( isset( $ERROR ) ) {
            $error_text = sprintf( $this->language->get( 'paygate_error_text' ), $ERROR, $this->html->getSecureURL( 'content/contact' ) );
            $error      = new AError( $error_text );
            $error->toLog()->toDebug()->toMessages();
            $this->data['error'] = $error_text;
        } else {
            $this->data['CHECKSUM']       = $CHECKSUM;
            $this->data['PAY_REQUEST_ID'] = $PAY_REQUEST_ID;
        }

        $this->view->batchAssign( $this->data );
        $this->processTemplate( 'responses/extension/paygate.tpl' );
    }

    public function callback()
    {
        // notify paygate that notification has been received, if you dont notify PayGate then this callback will be called three times
        $post     = $this->request->post;
        $order_id = $this->session->data['order_id'];

        $this->load->model( 'checkout/order' );
        $order_info = $this->model_checkout_order->getOrder( $this->session->data['order_id'] );
        if ( !$order_info ) {
            return null;
        }

        // if all fine update order status
        if ( $post['TRANSACTION_STATUS'] == '1' || $post['RESULT_CODE'] == '990017' ) {
            $order_status = new AOrderStatus();
            $this->model_checkout_order->confirm( $order_info['order_id'], $order_status->getStatusByTextId( 'pending' ), 'Redirecting to PayGate payment page' );
            $this->model_checkout_order->update( $order_id, $this->config->get( 'paygate_order_status_id' ), 'Transaction Approved, Pay Request ID: ' . $post['PAY_REQUEST_ID'] );
            $this->model_checkout_order->updatePaymentMethodData( $order_id, $post );
        } else if ( $post['TRANSACTION_STATUS'] == '4' ) {
            $order_status = new AOrderStatus();
            $this->model_checkout_order->update( $order_id, $order_status->getStatusByTextId( 'canceled_by_customer' ), 'PayGate Notify Response: User cancelled transaction, Pay Request ID: ' . $post['PAY_REQUEST_ID'] );
            $this->model_checkout_order->updatePaymentMethodData( $order_id, $post );
        } else {
            $order_status = new AOrderStatus();
            $this->model_checkout_order->update( $order_id, $order_status->getStatusByTextId( 'failed' ), 'PayGate Notify Response', 'Transaction declined, Pay Request ID: ' . $post['PAY_REQUEST_ID'] );
            $this->model_checkout_order->updatePaymentMethodData( $order_id, $post );
        }
        if ( $post['TRANSACTION_STATUS'] == '1' || $post['RESULT_CODE'] == '990017' ) {
            $return = filter_var( $this->html->getSecureURL( 'checkout/success' ), FILTER_SANITIZE_URL );
            header( 'Location: ' . $return );
        } elseif ( $post['TRANSACTION_STATUS'] == '4' ) {

            echo "<h1>Transaction Declined by user!</h1>";

            echo "This page will redirect automatically to homepage in 5 seconds...";
            $protocol = ( $_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off" ) ? "https" : "http";
            $home     = $protocol . "://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
            header( "refresh:5;url=" . $home );
        } else {

            echo "<h1>Transaction Declined!</h1>";

            echo "This page will redirect automatically to homepage in 5 seconds...";
            $protocol = ( $_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off" ) ? "https" : "http";
            $home     = $protocol . "://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
            header( "refresh:5;url=" . $home );
        }
    }
}
