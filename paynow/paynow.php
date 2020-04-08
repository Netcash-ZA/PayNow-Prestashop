<?php
/**
 * paynow.php
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

if (!defined('_PS_VERSION_'))
    exit;

class PayNow extends PaymentModule
{
    const DISABLE = -1;

    public function __construct()
    {
        $this->name = 'paynow';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->currencies = true;
        $this->currencies_mode = 'radio';

        parent::__construct();

        $this->author  = 'Sage PayNow';
        $this->page = basename(__FILE__, '.php');

        $this->displayName = $this->l('PayNow');
        $this->description = $this->l('Accept online payments through Sage Pay Now.');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details?');



        /* For 1.4.3 and less compatibility */
        $updateConfig = array('PS_OS_CHEQUE' => 1, 'PS_OS_PAYMENT' => 2, 'PS_OS_PREPARATION' => 3, 'PS_OS_SHIPPING' => 4, 'PS_OS_DELIVERED' => 5, 'PS_OS_CANCELED' => 6,
                      'PS_OS_REFUND' => 7, 'PS_OS_ERROR' => 8, 'PS_OS_OUTOFSTOCK' => 9, 'PS_OS_BANKWIRE' => 10, 'PS_OS_PAYPAL' => 11, 'PS_OS_WS_PAYMENT' => 12);
        foreach ($updateConfig as $u => $v)
            if (!Configuration::get($u) || (int)Configuration::get($u) < 1)
            {
                if (defined('_'.$u.'_') && (int)constant('_'.$u.'_') > 0)
                    Configuration::updateValue($u, constant('_'.$u.'_'));
                else
                    Configuration::updateValue($u, $v);
            }

    }

    public function install()
    {
        unlink(dirname(__FILE__).'/../../cache/class_index.php');
        if ( !parent::install()
            OR !$this->registerHook('payment')
            OR !$this->registerHook('paymentReturn')
            OR !Configuration::updateValue('SAGEPAY_ACCOUNT_NUMBER', '')
            OR !Configuration::updateValue('SAGEPAY_SERVICE_KEY', '')
            OR !Configuration::updateValue('SAGEPAY_LOGS', '1')
            // OR !Configuration::updateValue('SAGEPAY_MODE', 'test')
            OR !Configuration::updateValue('SAGEPAY_PAYNOW_TEXT', 'Pay Now With')
            OR !Configuration::updateValue('SAGEPAY_PAYNOW_LOGO', 'on')
            OR !Configuration::updateValue('SAGEPAY_PAYNOW_ALIGN', 'right')
           )
        {
            return false;
        }


        return true;
    }

    public function uninstall()
    {
        unlink(dirname(__FILE__).'/../../cache/class_index.php');
        return ( parent::uninstall()
            AND Configuration::deleteByName('SAGEPAY_SERVICE_KEY')
            AND Configuration::deleteByName('SAGEPAY_ACCOUNT_NUMBER')
            AND Configuration::deleteByName('SAGEPAY_LOGS')
            // AND Configuration::deleteByName('SAGEPAY_MODE')
            AND Configuration::deleteByName('SAGEPAY_PAYNOW_TEXT')
            AND Configuration::deleteByName('SAGEPAY_PAYNOW_LOGO')
            AND Configuration::deleteByName('SAGEPAY_PAYNOW_ALIGN')
            );

    }

    public function getContent()
    {
        global $cookie;
        $errors = array();
        $html = '<div style="width:550px">';

        // $html .= '<p style="text-align:center;"><a href="https://www.paynow.co.za" target="_blank"><img src="'.__PS_BASE_URI__.'modules/paynow/secure_logo.png" alt="SagePayNow" border="0" /></a></p><br />';



        // Update config
        if ( Tools::isSubmit( 'submitSagePayNow' ) )
        {
            if( $paynow_text =  Tools::getValue( 'paynow_text' ) )
            {
                 Configuration::updateValue( 'SAGEPAY_PAYNOW_TEXT', $paynow_text );
            }

            if( $paynow_logo =  Tools::getValue( 'paynow_logo' ) )
            {
                 Configuration::updateValue( 'SAGEPAY_PAYNOW_LOGO', $paynow_logo );
            }
            if( $paynow_align =  Tools::getValue( 'paynow_align' ) )
            {
                 Configuration::updateValue( 'SAGEPAY_PAYNOW_ALIGN', $paynow_align );
            }

            $account_number = Tools::getValue( 'paynow_account_number' );
            $service_key = Tools::getValue( 'paynow_service_key' );

            $pnErrors = [];
            if( $account_number && $service_key ) {
                if(class_exists('SoapClient')) {
                    // We can continue, SOAP is installed

                    require_once(dirname(__FILE__).'/PayNowValidator.php');
                    $Validator = new SagePay\PayNowValidator();
                    $Validator->setVendorKey('de2c157a-04fb-4cca-beb5-8aa20f686ac6');

                    try {
                        $result = $Validator->validate_paynow_service_key($account_number, $service_key);

                        if( $result !== true ) {
                            $pnErrors[] = (isset($result[$service_key]) ? $result[$service_key] : '<strong>Account Number:</strong> ' . $result) . ' ';
                            $pnErrors[] = (isset($result[$service_key]) ? $result[$service_key] : '<strong>Service Key</strong> could not be validated.') . ' ';
                        } else {

                            // Success
                            Configuration::updateValue( 'SAGEPAY_ACCOUNT_NUMBER', $account_number );
                            Configuration::updateValue( 'SAGEPAY_SERVICE_KEY', $service_key );

                        }
                    } catch(\Exception $e) {
                        $pnErrors[] = $e->getMessage() . ' ';
                    }
                } else {
                    $pnErrors[] = 'Cannot validate. Please install the PHP SOAP extension.';
                }
            } else {
                $pnErrors[] = 'Please specify an account number and service key.</div>';
            }

            if(!empty($pnErrors)) {
                $pnErrors[] = "Please contact your Sage Pay Account manager on 0861 338 338 for assistance.";
                foreach ($pnErrors as $error) {
                    $errors[] = "<div class='warning warn'>{$error}</div>";
                }
            }

            /* $mode = ( Tools::getValue( 'paynow_mode' ) == 'live' ? 'live' : 'test' ) ;
            Configuration::updateValue('SAGEPAY_MODE', $mode );
            if( $mode != 'test' )
            {
                if( ( $service_key = Tools::getValue( 'paynow_service_key' ) ) AND preg_match('/[0-9]/', $service_key ) )
                {
                    Configuration::updateValue( 'SAGEPAY_SERVICE_KEY', $service_key );
                }
                else
                {
                    $errors[] = '<div class="warning warn"><h3>'.$this->l( 'Service key is invalid' ).'</h3></div>';
                }


                if( !sizeof( $errors ) )
                {
                    //Tools::redirectAdmin( $currentIndex.'&configure=paynow&token='.Tools::getValue( 'token' ) .'&conf=4' );
                }

            }*/

            if( Tools::getValue( 'paynow_logs' ) )
            {
                Configuration::updateValue( 'SAGEPAY_LOGS', 1 );
            }
            else
            {
                Configuration::updateValue( 'SAGEPAY_LOGS', 0 );
            }

            // foreach(array('displayLeftColumn', 'displayRightColumn', 'displayFooter') as $hookName) {
            //     if ($this->isRegisteredInHook($hookName)) {
            //         $this->unregisterHook($hookName);
            //     }
            // }

            if( method_exists ('Tools','clearSmartyCache') )
            {
                Tools::clearSmartyCache();
            }

        }



        /* Display errors */
        if (sizeof($errors))
        {
            $html .= '<ul style="color: red; font-weight: bold; margin-bottom: 30px; width: 506px; background: #FFDFDF; border: 1px dashed #BBB; padding: 10px;">';
            foreach ($errors AS $error)
                $html .= '<li>'.$error.'</li>';
            $html .= '</ul>';
        }


    $currentLogoBlockPosition = -1;


    /* Display settings form */
        $html .= '
        <form action="'.$_SERVER['REQUEST_URI'].'" method="post">
          <fieldset>
          <legend><img src="'.__PS_BASE_URI__.'modules/paynow/logo.png" />'.$this->l('Settings').'</legend>
            <p>'.$this->l('You can find your Account Number in your Sage Pay Now account.').'</p>
            <label>
              '.$this->l('Account Number').'
            </label>
            <div class="margin-form">
              <input type="text" name="paynow_account_number" value="'.trim(Tools::getValue('paynow_account_number', Configuration::get('SAGEPAY_ACCOUNT_NUMBER'))).'" />
            </div>
            <p>'.$this->l('You can find your Service Key in your Sage Pay Now account.').'</p>
            <label>
              '.$this->l('Service Key').'
            </label>
            <div class="margin-form">
              <input type="text" name="paynow_service_key" value="'.trim(Tools::getValue('paynow_service_key', Configuration::get('SAGEPAY_SERVICE_KEY'))).'" />
            </div>
            <p>'.$this->l('You can log the server-to-server communication. The log file for debugging can be found at ').' '.__PS_BASE_URI__.'modules/paynow/paynow.log. '.$this->l('If activated, be sure to protect it by putting a .htaccess file in the same directory. If not, the file will be readable by everyone.').'</p>
            <label>
              '.$this->l('Debug').'
            </label>
            <div class="margin-form" style="margin-top:5px">
              <input type="checkbox" name="paynow_logs"'.(Tools::getValue('paynow_logs', Configuration::get('SAGEPAY_LOGS')) ? ' checked="checked"' : '').' />
            </div>
            <p>'.$this->l('During checkout the following is what the client gets to click on to pay with PayNow.').'</p>
            <label>&nbsp;</label>
            <div class="margin-form" style="margin-top:5px">
                '.Configuration::get('SAGEPAY_PAYNOW_TEXT');

           if(Configuration::get('SAGEPAY_PAYNOW_LOGO')=='on')
            {
                $html .= '<img align="'.Configuration::get('SAGEPAY_PAYNOW_ALIGN').'" alt="Pay With Pay Now" title="Pay With Pay Now" src="'.__PS_BASE_URI__.'modules/paynow/logo.png">';
            }
            $html .='</div>
            <label>
            '.$this->l('Pay Now Text').'
            </label>
            <div class="margin-form" style="margin-top:5px">
                <input type="text" name="paynow_text" value="'. Configuration::get('SAGEPAY_PAYNOW_TEXT').'">
            </div>
            <label>
            '.$this->l('Pay Now Logo').'
            </label>
            <div class="margin-form" style="margin-top:5px">
                <input type="radio" name="paynow_logo" value="off" '.( Configuration::get('SAGEPAY_PAYNOW_LOGO')=='off' ? ' checked="checked"' : '').'"> &nbsp; '.$this->l('None').'<br>
                <input type="radio" name="paynow_logo" value="on" '.( Configuration::get('SAGEPAY_PAYNOW_LOGO')=='on' ? ' checked="checked"' : '').'"> &nbsp; <img src="'.__PS_BASE_URI__.'modules/paynow/logo.png">
            </div>
            <label>
            '.$this->l('Pay Now Logo Align').'
            </label>
            <div class="margin-form" style="margin-top:5px">
                <input type="radio" name="paynow_align" value="left" '.( Configuration::get('SAGEPAY_PAYNOW_ALIGN')=='left' ? ' checked="checked"' : '').'"> &nbsp; '.$this->l('Left').'<br>
                <input type="radio" name="paynow_align" value="right" '.( Configuration::get('SAGEPAY_PAYNOW_ALIGN')=='right' ? ' checked="checked"' : '').'"> &nbsp; '.$this->l('Right').'
            </div>

            <div style="float:right;"><input type="submit" name="submitSagePayNow" class="button" value="'.$this->l('   Save   ').'" /></div><div class="clear"></div>
          </fieldset>
        </form>
        <br /><br />
        <fieldset>
          <legend><img src="../img/admin/warning.gif" />'.$this->l('Information').'</legend>
          <p>- '.$this->l('In order to use this PayNow module, you must insert your PayNow Service Key above.').'</p>
          <p>- '.$this->l('Any orders in currencies other than ZAR will be converted by prestashop prior to be sent to the PayNow payment gateway.').'<p>
          <p>- '.$this->l('It is possible to setup an automatic currency rate update using crontab. You will simply have to create a cron job with currency update link available at the bottom of "Currencies" section.').'<p>
        </fieldset>
        </div>';

        return $html;
    }

    public function hookPayment($params)
    {
        global $cookie, $cart;
        if (!$this->active)
        {
            return;
        }

        // Buyer details
        $customer = new Customer((int)($cart->id_customer));

        $toCurrency = new Currency(Currency::getIdByIsoCode('ZAR'));
        $fromCurrency = new Currency((int)$cookie->id_currency);

        $total = $cart->getOrderTotal();

        $pnAmount = Tools::convertPriceFull( $total, $fromCurrency, $toCurrency );

        $data = array();

        $currency = $this->getCurrency((int)$cart->id_currency);
        if ($cart->id_currency != $currency->id)
        {
            // If SagePayNow currency differs from local currency
            $cart->id_currency = (int)$currency->id;
            $cookie->id_currency = (int)$cart->id_currency;
            $cart->update();
        }

        // Service Key
        $software_vendor_key = 'de2c157a-04fb-4cca-beb5-8aa20f686ac6';
        $data['info']['m1'] = Configuration::get('SAGEPAY_SERVICE_KEY');
        $data['info']['m2'] = $software_vendor_key;
        $data['paynow_url'] = 'https://paynow.sagepay.co.za/site/paynow.aspx';

        $data['paynow_text'] = Configuration::get('SAGEPAY_PAYNOW_TEXT');
        $data['paynow_logo'] = Configuration::get('SAGEPAY_PAYNOW_LOGO');
        $data['paynow_align'] = Configuration::get('SAGEPAY_PAYNOW_ALIGN');

        // URLs
        $return_vars = 'key='.$cart->secure_key.'&id_cart='.(int)($cart->id).'&id_module='.(int)($this->id); //.'&return_url='.urlencode($this->context->link->getPageLink( 'order-confirmation', null, null, ''));
        $return_url = $this->context->link->getPageLink( 'order-confirmation', null, null, $return_vars);
        // $data['info']['return_url'] = $return_url
        $data['info']['cancel_url'] = Tools::getHttpHost( true ).__PS_BASE_URI__;
        $data['info']['notify_url'] = Tools::getHttpHost( true ).__PS_BASE_URI__.'modules/paynow/validation.php?itn_request=true';

        $customerName = "{$customer->firstname} {$customer->lastname}";
        $orderID = $cart->id;
        $customerID = $cart->id_customer;
        $sageGUID = $software_vendor_key;

        $data['info']['p3'] = "{$customerName} | {$orderID}";
        // $data['info']['m3'] = $sageGUID;
        $data['info']['m4'] = "{$customerID}";


        // First name
        // $data['info']['m4'] = $customer->firstname;
        // Cart Id sent as Extra2
        $data['info']['m5'] = $cart->id;
        // Secure key (Returend as Extra3)
        $data['info']['m6'] = $cart->secure_key;

        // Unique Order Id
        $data['info']['p2'] = $cart->id . "_" . date("Ymds");
        // Item Name
        // $data['info']['p3'] = 'Purchase from ' . Configuration::get('PS_SHOP_NAME') .', Cart Item ID #'. $cart->id;
        // Price
        $data['info']['p4'] = number_format( sprintf( "%01.2f", $pnAmount ), 2, '.', '' );

        $data['info']['m9'] = $customer->email;
        // Custom data
        $data['info']['m10'] = $return_vars;
        // $data['info']['m10'] = "notify=" . urlencode($data['info']['notify_url']);


        // $data['info']['name_first'] = $customer->firstname;
        // $data['info']['name_last'] = $customer->lastname;
        // $data['info']['email_address'] = $customer->email;
        // $data['info']['m_payment_id'] = $cart->id;
        // $data['info']['amount'] = number_format( sprintf( "%01.2f", $pfAmount ), 2, '.', '' );
        // $data['info']['item_name'] = Configuration::get('PS_SHOP_NAME') .' purchase, Cart Item ID #'. $cart->id;
        // $data['info']['custom_int1'] = $cart->id;
        // $data['info']['custom_str1'] = $cart->secure_key;


        $output = '';
        // Create output string
        foreach( ($data['info']) as $key => $val )
            $output .= $key .'='. urlencode( trim( $val ) ) .'&';

        // Remove trailing "&"
        $output = substr( $output, 0, -1 );

        $data['info']['signature'] = md5( $output );

        $this->context->smarty->assign( 'data', $data );

        return $this->display(__FILE__, 'paynow.tpl');
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active)
        {
            return;
        }
        $test = __FILE__;

        return $this->display($test, 'paynow_success.tpl');

    }

}


