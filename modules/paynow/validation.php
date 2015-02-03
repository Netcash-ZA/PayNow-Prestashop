<?php
/**
 * validation.php
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

// Check if this is an ITN request
// Has to be done like this (as opposed to "exit" as processing needs
// to continue after this check.
// if( ( $_GET['itn_request'] == 'true' ) ) {
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

                // Update the purchase status
                $paynow->validateOrder((int)$pnData['Extra2'], _PS_OS_PAYMENT_, (float)$total ,
                    $paynow->displayName, NULL, array('transaction_id'=>$transaction_id), NULL, false, $pnData['Extra3']);

                break;

            case 'false':
                pnlog( '- Failed' );

                // If payment fails, delete the purchase log
                $paynow->validateOrder((int)$pnData['Extra2'], _PS_OS_ERROR_, (float)$total ,
                    $paynow->displayName, NULL,array('transaction_id'=>$transaction_id), NULL, false, $pnData['Extra3']);

                break;

            default:
                // If unknown status, do nothing (safest course of action)
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
// }
