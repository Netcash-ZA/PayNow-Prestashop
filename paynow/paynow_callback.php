<?php
/**
 * paynow_callback.php
 *
 * Copyright (c) 2011 PayFast (Pty) Ltd
 *
 * LICENSE:
 *
 * This payment module is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation; either version 3 of the License, or (at
 * your option) any later version.
 *
 * This payment module is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public
 * License for more details.
 *
 */

include( dirname(__FILE__).'/../../config/config.inc.php' );
include( dirname(__FILE__).'/paynow.php' );
include( dirname(__FILE__).'/paynow_common.inc' );

$route = "index.php?controller=my-account";
$url_for_redirect = _PS_BASE_URL_.__PS_BASE_URI__.$route;

// Check if this is an ITN request
// Has to be done like this (as opposed to "exit" as processing needs
// to continue after this check.
// if( ( $_GET['itn_request'] == 'true' ) ) {
function pn_do_transaction() {
    // Variable Initialization
    $pnError = false;
    $pnErrMsg = '';
    $pnDone = false;
    $pnData = array();
    $pnOrderId = '';
    $pnParamString = '';

    $paynow = new PayNow();

    pnlog( 'SagePayNow Validation initiated.' );

    //// Notify SagePayNow that information has been received
    if( !$pnError && !$pnDone )
    {
        header( 'HTTP/1.0 200 OK' );
        flush();
    }

    //// Get data sent by SagePayNow
    if( !$pnError && !$pnDone )
    {
        pnlog( 'Get posted data' );

        // Posted variables from ITN
        $pnData = pnGetData();

        pnlog( 'SagePayNow Data: '. print_r( $pnData, true ) );

        if( $pnData === false )
        {
            $pnError = true;
            $pnErrMsg = PN_ERR_BAD_ACCESS;
        }
    }

    //// Verify security signature
    // if( !$pnError && !$pnDone )
    // {
    //     pnlog( 'Verify security signature' );

    //     //  pasphrase not used with sage. TODO: Fix validation
    //     $passPhrase = "";
    //     $pnPassPhrase = empty( $passPhrase ) ? null : $passPhrase;

    //     // If signature different, log for debugging
    //     if( !pnValidSignature( $pnData, $pnParamString, $pnPassPhrase ) )
    //     {
    //         $pnError = true;
    //         $pnErrMsg = PN_ERR_INVALID_SIGNATURE;
    //     }
    // }

    //// Verify source IP (If not in debug mode)
    if( !$pnError && !$pnDone && !PN_DEBUG )
    {
        pnlog( 'Verify source IP' );

        if( !pnValidIP( $_SERVER['REMOTE_ADDR'] ) )
        {
            // $pnError = true;
            // $pnErrMsg = PN_ERR_BAD_SOURCE_IP;
        }
    }

    //// Get internal cart
    if( !$pnError && !$pnDone )
    {
        // Get order data
        $cart = new Cart((int) $pnData['Extra2']);

        pnlog( "Purchase:\n". print_r( $cart, true )  );
    }

    //// Verify data received
    if( !$pnError )
    {
        pnlog( 'Verify data received' );

        $pnParamString = "RequestTrace=".$pnData['RequestTrace'];
        $pnValid = pnValidData( $pnParamString, $pnData );

        if( !$pnValid )
        {
            $pnError = true;
            $pnErrMsg = PN_ERR_BAD_ACCESS;
        }
    }

    //// Check data against internal order
    if( !$pnError && !$pnDone )
    {
       // pnlog( 'Check data against internal order' );
        $fromCurrency = new Currency(Currency::getIdByIsoCode('ZAR'));
        $toCurrency = new Currency((int)$cart->id_currency);

        $total = Tools::convertPriceFull( $pnData['Amount'], $fromCurrency, $toCurrency );

        // Check order amount Extra3 is sent as m6
        if( strcasecmp( $pnData['Extra3'], $cart->secure_key ) != 0 )
        {
            $pnError = true;
            $pnErrMsg = PN_ERR_SESSIONID_MISMATCH;
        }
    }

    $vendor_name = Configuration::get('PS_SHOP_NAME');
    $vendor_url = Tools::getShopDomain(true, true);

    //// Check status and update order
    if( !$pnError && !$pnDone )
    {
        pnlog( 'Check status and update order' );

        $sessionid = $pnData['Extra3'];
        $transaction_id = $pnData['Extra2'];

        if (empty(Context::getContext()->link))
        Context::getContext()->link = new Link();

        switch( $pnData['TransactionAccepted'] )
        {
            case 'true':
                pnlog( '- Complete' );

                $id_cart = (int)$pnData['Extra2'];
                // Make sure order doesn't exist
                if ($cart->OrderExists() === 0) {

	                // Update the purchase status
	                $paynow->validateOrder($id_cart, _PS_OS_PAYMENT_, (float)$total ,
	                    $paynow->displayName, NULL, array('transaction_id'=>$transaction_id), NULL, false, $pnData['Extra3']);
	            }

                pnlog( '- Redirecting to order-confirmation' );
                Tools::redirect('index.php?controller=order-confirmation&'.$_SERVER['QUERY_STRING']);
            break;

            case 'false':
                pnlog( '- Failed' );

                // If payment fails, delete the purchase log
                $paynow->validateOrder((int)$pnData['Extra2'], _PS_OS_ERROR_, (float)$total ,
                    $paynow->displayName, NULL,array('transaction_id'=>$transaction_id), NULL, false, $pnData['Extra3']);

                pnlog( '- Redirecting to order history (a)' );
                Tools::redirect('index.php?controller=history');

            break;

            default:
                // If unknown status, do nothing (safest course of action)
                pnlog( '- Redirecting to order history (b)' );
                Tools::redirect('index.php?controller=history');
            break;
        }
    }

    // If an error occurred
    if( $pnError )
    {
        pnlog( 'Error occurred: '. $pnErrMsg );
    }

    // Close log
    pnlog( '', true );
    // exit();
}

if( isset($_POST) && !empty($_POST) ) {

    // This is the notification OR CC payment coming in!
    // Act as an IPN request and forward request to Credit Card method.
    // Logic is exactly the same

    // DO your thang
    pn_do_transaction();
    die();

} else {
    // Probably calling the "redirect" URL

    pnlog(__FILE__ . ' Probably calling the "redirect" URL');

    if( $url_for_redirect ) {
        header ( "Location: {$url_for_redirect}" );
    } else {
        die( "No 'redirect' URL set." );
    }
}
