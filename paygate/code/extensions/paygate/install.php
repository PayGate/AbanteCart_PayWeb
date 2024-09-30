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

$language_list = $this->model_localisation_language->getLanguages();

$rm = new AResourceManager();
$rm->setType( 'image' );

$result = copy( DIR_EXT . 'paygate/image/payment-methods-masterpass.png', DIR_RESOURCE . 'image/payment-methods-masterpass.png' );

$resource = array(
    'language_id'   => $this->config->get( 'storefront_language_id' ),
    'name'          => array(),
    'title'         => array(),
    'description'   => array(),
    'resource_path' => 'payment-methods-masterpass.png',
    'resource_code' => '',
);

foreach ( $language_list as $lang ) {
    $resource['name'][$lang['language_id']]        = 'payment-methods-masterpass.png';
    $resource['title'][$lang['language_id']]       = 'paygate_payment_storefront_icon';
    $resource['description'][$lang['language_id']] = 'Paygate Storefront Icon';
}
$resource_id = $rm->addResource( $resource );

if ( $resource_id ) {
    // get hexpath of resource (RL moved given file from rl-image-directory in own dir tree)
    $resource_info = $rm->getResource( $resource_id, $this->config->get( 'admin_language_id' ) );
    // write it path in settings (array from parent method "install" of extension manager)
    if ( version_compare( VERSION, '1.2.5', '>' ) ) {
        $settings['paygate_payment_storefront_icon'] = 'index.php?rt=r/common/resource/getImageThumbnail&resource_id=' . $resource_id;
    } else {
        // write it path in settings (array from parent method "install" of extension manager)
        $settings['paygate_payment_storefront_icon'] = 'image/' . $resource_info['resource_path'];
    }
}

// add currency

$this->load->model( 'localisation/currency' );
$all_currencies = $this->model_localisation_currency->getCurrencies();
if ( !in_array( 'ZAR', array_keys( $all_currencies ) ) ) {
    $this->model_localisation_currency->addCurrency(
        array(
            'title'         => 'Rand',
            'code'          => 'ZAR',
            'symbol_left'   => 'R',
            'symbol_right'  => '',
            'decimal_place' => '2',
            'value'         => '15.5611',
            'status'        => '1',
        )
    );
}
