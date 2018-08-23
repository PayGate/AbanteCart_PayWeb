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

$controllers = array(
    'storefront' => array(
        'responses/extension/paygate' ),
    'admin'      => array() );

$models = array(
    'storefront' => array(
        'extension/paygate' ),
    'admin'      => array() );

$templates = array(
    'storefront' => array(
        'responses/extension/paygate.tpl' ),
    'admin'      => array() );

$languages = array(
    'storefront' => array(
        'paygate/paygate' ),
    'admin'      => array(
        'paygate/paygate' ) );
