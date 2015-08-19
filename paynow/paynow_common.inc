<?php
/**
 * paynow_common.inc
 *
 * Copyright (c) 2009-2011 PayFast (Pty) Ltd
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

//// Create user agent string
// User agent constituents (for cURL)
define( 'PN_SOFTWARE_NAME', 'PrestaShop' );
define( 'PN_SOFTWARE_VER', Configuration::get('PS_INSTALL_VERSION') );
define( 'PN_MODULE_NAME', 'PayNow-Prestashop' );
define( 'PN_MODULE_VER', '2.0.0' );
define( 'PN_DEBUG', ( Configuration::get('SAGEPAY_LOGS')  ? true : false ) );

// Features
// - PHP
$pnFeatures = 'PHP '. phpversion() .';';

// - cURL
if( in_array( 'curl', get_loaded_extensions() ) )
{
    define( 'PN_CURL', '' );
    $pnVersion = curl_version();
    $pnFeatures .= ' curl '. $pnVersion['version'] .';';
}
else
    $pnFeatures .= ' nocurl;';

// Create user agrent
define( 'PN_USER_AGENT', PN_SOFTWARE_NAME .'/'. PN_SOFTWARE_VER .' ('. trim( $pnFeatures ) .') '. PN_MODULE_NAME .'/'. PN_MODULE_VER );

// General Defines
define( 'PN_TIMEOUT', 15 );
define( 'PN_EPSILON', 0.01 );

// Messages
// Error
define( 'PN_ERR_AMOUNT_MISMATCH', 'Amount mismatch' );
define( 'PN_ERR_BAD_ACCESS', 'Bad access of page' );
define( 'PN_ERR_BAD_SOURCE_IP', 'Bad source IP address' );
define( 'PN_ERR_CONNECT_FAILED', 'Failed to connect to SagePay' );
define( 'PN_ERR_INVALID_SIGNATURE', 'Security signature mismatch' );
define( 'PN_ERR_MERCHANT_ID_MISMATCH', 'Merchant ID mismatch' );
define( 'PN_ERR_NO_SESSION', 'No saved session found for ITN transaction' );
define( 'PN_ERR_ORDER_ID_MISSING_URL', 'Order ID not present in URL' );
define( 'PN_ERR_ORDER_ID_MISMATCH', 'Order ID mismatch' );
define( 'PN_ERR_ORDER_INVALID', 'This order ID is invalid' );
define( 'PN_ERR_ORDER_NUMBER_MISMATCH', 'Order Number mismatch' );
define( 'PN_ERR_ORDER_PROCESSED', 'This order has already been processed' );
define( 'PN_ERR_PDT_FAIL', 'PDT query failed' );
define( 'PN_ERR_PDT_TOKEN_MISSING', 'PDT token not present in URL' );
define( 'PN_ERR_SESSIONID_MISMATCH', 'Session ID mismatch' );
define( 'PN_ERR_UNKNOWN', 'Unkown error occurred' );

// General
define( 'PN_MSG_OK', 'Payment was successful' );
define( 'PN_MSG_FAILED', 'Payment has failed' );
define( 'PN_MSG_PENDING',
    'The payment is pending. Please note, you will receive another Instant'.
    ' Transaction Notification when the payment status changes to'.
    ' "Completed", or "Failed"' );


/**
 * pnlog
 *
 * Log function for logging output.
 *
 * @param $msg String Message to log
 * @param $close Boolean Whether to close the log file or not
 */
function pnlog( $msg = '', $close = false )
{
    static $fh = 0;
    global $module;

    // Only log if debugging is enabled
    if( PN_DEBUG )
    {
        if( $close )
        {
            fclose( $fh );
        }
        else
        {
            // If file doesn't exist, create it
            if( !$fh )
            {
                $pathinfo = pathinfo( __FILE__ );
                $fh = fopen( $pathinfo['dirname'] .'/paynow.log', 'a+' );
            }

            // If file was successfully created
            if( $fh )
            {
                $line = date( 'Y-m-d H:i:s' ) .' : '. $msg ."\n";

                fwrite( $fh, $line );
            }
        }
    }
}

/**
 * pnGetData
 *
 */
function pnGetData()
{
    // Posted variables from ITN
    $pnData = $_POST;

    // $pnData['signature'] = $_GET['key'];

    // Strip any slashes in data
    foreach( $pnData as $key => $val )
        $pnData[$key] = stripslashes( $val );

    // Return "false" if no data was received
    if( sizeof( $pnData ) == 0 )
        return( false );
    else
        return( $pnData );
}

/**
 * pnValidSignature
 *
 */
function pnValidSignature( $pnData = null, &$pnParamString = null )
{
    // Dump the submitted variables and calculate security signature
    foreach( $pnData as $key => $val )
    {
        if( $key != 'signature' )
        {
            $pnParamString .= $key .'='. urlencode( $val ) .'&';
        }
        else
        {
            break;
        }
    }

    $pnParamString = substr( $pnParamString, 0, -1 );
    $tempParamString = $pnParamString;

    $signature = md5( $tempParamString );

    $result = ( $pnData['signature'] == $signature );

    pnlog( 'Signature = '. ( $result ? 'valid' : 'invalid' ) );

    return( $result );
}



/**
 * Compare original callback TransactionAccepted value against the requestTrace TransactionAccepted value.
 * RequestTrace returns boolean FALSE on failed whereas the callback uses a string..
 *
 * @param  mixed $valOne The original callback TransactionAccepted value
 * @param  mixed $valTwo The requestTrace TransactionAccepted value
 * @return bool          True if both are true. False if both are false
 */
function pnTransactionAcceptedMatch($valOne, $valTwo) {
    $retOne = true;
    $retTwo = true;

    switch ($valOne) {
        // FALSE
        case $valOne === '':
            pnLog( "valOne: Empty STRING!" );
            $retOne = false;
            break;
        case strtolower($valOne) === 'false':
            pnLog( "valOne: 'false' STRING!" );
            $retOne = false;
            break;
        case $valOne === false:
            pnLog( "valOne: 'false' BOOLEAN!" );
            $retOne = false;
            break;
        case $valOne === 0:
            pnLog( "valOne: '0' INT!" );
            $retOne = false;
            break;
    }

    switch ($valTwo) {

        // FALSE
        case $valTwo === '':
            pnLog( "valTwo: Empty STRING!" );
            $retTwo = false;
            break;
        case strtolower($valTwo) === 'false':
            pnLog( "valTwo: 'false' STRING!" );
            $retTwo = false;
            break;
        case $valTwo === false:
            pnLog( "valTwo: 'false' BOOLEAN!" );
            $retTwo = false;
            break;
        case $valTwo === 0:
            pnLog( "valTwo: '0' INT!" );
            $retTwo = false;
            break;
    }

    pnLog( " --> valOne: [" . gettype($valOne) . "] " . $valOne );
    pnLog( " --> valTwo: [" . gettype($valTwo) . "] " . $valTwo );

    return $retOne === $retTwo;
}

/**
 * pnValidData
 *
 * @param $pnParamString String Parameter string to send
 * @param $proxy String Address of proxy to use or NULL if no proxy
 */
function pnValidData( $pnParamString = '', $pnData, $pnProxy = null )
{
    $pnHost = "gateway.sagepay.co.za";
    pnlog( 'Host = '. $pnHost );
    pnlog( 'Params = '. $pnParamString );

    // Use cURL (if available)
    if( defined( 'PN_CURL' ) )
    {
        // Variable initialization
        $url = 'https://'. $pnHost .'/transactionstatus';

        pnlog( 'curl_init' );

        // Create default cURL object
        $ch = curl_init();

        // Set cURL options - Use curl_setopt for freater PHP compatibility
        // Base settings
        curl_setopt( $ch, CURLOPT_USERAGENT, PN_USER_AGENT );  // Set user agent
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );      // Return output as string rather than outputting it
        curl_setopt( $ch, CURLOPT_HEADER, false );             // Don't include header in output
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

        // Standard settings
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $pnParamString );
        curl_setopt( $ch, CURLOPT_TIMEOUT, PN_TIMEOUT );
        if( !empty( $pnProxy ) )
            curl_setopt( $ch, CURLOPT_PROXY, $proxy );

        // Execute CURL
        $response = curl_exec( $ch );
        curl_close( $ch );
    }
    // Use fsockopen
    else
    {
        // Variable initialization
        $header = '';
        $res = '';
        $headerDone = false;

        // Construct Header
        $header = "POST /transactionstatus HTTP/1.0\r\n";
        $header .= "Host: ". $pnHost ."\r\n";
        $header .= "User-Agent: ". PN_USER_AGENT ."\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: " . strlen( $pnParamString ) . "\r\n\r\n";

        pnlog( 'fsockopen' );

        // Connect to server
        $socket = fsockopen( 'ssl://'. $pnHost, 443, $errno, $errstr, PN_TIMEOUT );

        // Send command to server
        fputs( $socket, $header . $pnParamString );

        // Read the response from the server
        while( !feof( $socket ) )
        {
            $line = fgets( $socket, 1024 );

            // Check if we are finished reading the header yet
            if( strcmp( $line, "\r\n" ) == 0 )
            {
                // read the header
                $headerDone = true;
            }
            // If header has been processed
            else if( $headerDone )
            {
                // Read the main response
                $response .= $line;
            }
        }

    }

    pnlog( "Response:\n". print_r( $response, true ) );

    // Interpret Response
    // $lines = explode( "\r\n", $response );
    // $verifyResult = trim( $lines[0] );

    if( $pnData['Method'] == 'CreditCard' ) {
        $responseArray = json_decode($response, true);
        pnlog( 'SagePayNow RequestTrace Response: '. print_r( $responseArray, true ) );
    } else {
        // This is temporary.
        // Cannot verify via RequestTrace for Retail/EFT at this stage.
        $responseArray = $pnData;
    }
    $valid = true;

    if( $responseArray["Amount"] !== $pnData["Amount"] ) {
        $valid = false;
        pnLog( "Amount: Invalid!" );
    } else {
        pnLog( "Amount: Valid!" );
    }

    $transactionAcceptedMatches = pnTransactionAcceptedMatch( $responseArray["TransactionAccepted"], $pnData["TransactionAccepted"] );

    if( !$transactionAcceptedMatches ) {
        $valid = false;
        pnLog( "TransactionAccepted: Invalid! (response / pnData) " . print_r($responseArray["TransactionAccepted"], true) . " / " . print_r($pnData["TransactionAccepted"], true) );
        pnLog( "TransactionAccepted Types: " .
            print_r( gettype($responseArray["TransactionAccepted"]), true) . " / " .
            print_r( gettype($pnData["TransactionAccepted"]), true) );
    } else {
        pnLog( "TransactionAccepted: Valid!" );
    }

    if( $responseArray["Reference"] !== $pnData["Reference"] ) {
        $valid = false;
        pnLog( "Reference: Invalid!" );
    } else {
        pnLog( "Reference: Valid!" );
    }

    if( $responseArray["RequestTrace"] !== $pnData["RequestTrace"] ) {
        $valid = false;
        pnLog( "RequestTrace: Invalid!" );
    } else {
        pnLog( "RequestTrace: Valid!" );
    }

    pnLog( "pnValidData: " . ($valid ? "true" : "false") );

    return $valid;

    // if( strcasecmp( $verifyResult, 'VALID' ) == 0 )
    //     return( true );
    // else
    //     return( false );
}

/**
 * pnValidIP
 *
 * @param $sourceIP String Source IP address
 */
function pnValidIP( $sourceIP )
{
    // Variable initialization
    $validHosts = array(
        'www.sagepay.co.za',
        'paynow.sagepay.co.za'
        );

    $validIps = array();

    foreach( $validHosts as $pnHostname )
    {
        $ips = gethostbynamel( $pnHostname );

        if( $ips !== false )
            $validIps = array_merge( $validIps, $ips );
    }

    // Remove duplicates
    $validIps = array_unique( $validIps );

    pnlog( "Valid IPs:\n". print_r( $validIps, true ) );

    if( in_array( $sourceIP, $validIps ) )
        return( true );
    else
        return( false );
}

/**
 * pnAmountsEqual
 *
 * Checks to see whether the given amounts are equal using a proper floating
 * point comparison with an Epsilon which ensures that insignificant decimal
 * places are ignored in the comparison.
 *
 * eg. 100.00 is equal to 100.0001
 *
 * @param $amount1 Float 1st amount for comparison
 * @param $amount2 Float 2nd amount for comparison
 */
function pnAmountsEqual( $amount1, $amount2 )
{
    if( abs( floatval( $amount1 ) - floatval( $amount2 ) ) > PN_EPSILON )
        return( false );
    else
        return( true );
}
// }}}
?>