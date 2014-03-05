<?php
require_once 'modules/admin/models/GatewayPlugin.php';
require_once 'modules/billing/models/Invoice.php';

/**
* @package Plugins
*/
class Plugin2checkout extends GatewayPlugin
{
    function getVariables()
    {
        $variables = array (
                   /*T*/"Plugin Name"/*/T*/ => array (
                                        "type"          =>"hidden",
                                        "description"   =>/*T*/"How CE sees this plugin (not to be confused with the Signup Name)"/*/T*/,
                                        "value"         =>/*T*/"2Checkout"/*/T*/
                                       ),
                   /*T*/"Seller ID"/*/T*/ => array (
                                        "type"          =>"text",
                                        "description"   =>/*T*/"ID used to identify you to 2checkout.com.<br>NOTE: This ID is required if you have selected 2checkout as a payment gateway for any of your clients."/*/T*/,
                                        "value"         =>""
                                       ),
                   /*T*/"Secret Word"/*/T*/ => array (
                                        "type"          =>"text",
                                        "description"   =>/*T*/"'Secret Word' used to calculate the MD5 hash. <br>NOTE: Please take in count, you will also need to set the 'Secret Word' on the 2Checkout Site Management page, and it is to avoid frauds."/*/T*/,
                                        "value"         =>""
                                       ),
                   /*T*/"Purchase Routine"/*/T*/=> array(
                                        "type"          => "options",
                                        "description"   => /*T*/"This setting allows you to determine which purchase routine will be better suited for your site."/*/T*/,
                                        "options"       => array(0 => /*T*/ "Standard Purchase Routine" /*/T*/,
                                                                 1 => /*T*/ "Single Page Checkout" /*/T*/),
                                        "value"         => 0
                                       ),
                    /*T*/"Demo Mode"/*/T*/ => array (
                                        "type"          =>"yesno",
                                        "description"   =>/*T*/"Select YES if you want to set 2checkout into Demo Mode for testing. (<b>NOTE:</b> You must set to NO before accepting actual payments through this processor.)"/*/T*/,
                                        "value"         =>"0"
                                       ),
                   /*T*/"Accept CC Number"/*/T*/ => array (
                                        "type"          =>"hidden",
                                        "description"   =>/*T*/"Selecting YES allows the entering of CC numbers when using this plugin type. No will prevent entering of cc information"/*/T*/,
                                        "value"         =>"0"
                                       ),
                   /*T*/"Visa"/*/T*/ => array (
                                        "type"          =>"yesno",
                                        "description"   =>/*T*/"Select YES to allow Visa card acceptance with this plugin.  No will prevent this card type."/*/T*/,
                                        "value"         =>"0"
                                       ),
                   /*T*/"MasterCard"/*/T*/ => array (
                                        "type"          =>"yesno",
                                        "description"   =>/*T*/"Select YES to allow MasterCard acceptance with this plugin. No will prevent this card type."/*/T*/,
                                        "value"         =>"0"
                                       ),
                   /*T*/"AmericanExpress"/*/T*/ => array (
                                        "type"          =>"yesno",
                                        "description"   =>/*T*/"Select YES to allow American Express card acceptance with this plugin. No will prevent this card type."/*/T*/,
                                        "value"         =>"0"
                                       ),
                   /*T*/"Discover"/*/T*/ => array (
                                        "type"          =>"yesno",
                                        "description"   =>/*T*/"Select YES to allow Discover card acceptance with this plugin. No will prevent this card type."/*/T*/,
                                        "value"         =>"0"
                                       ),
                   /*T*/"Invoice After Signup"/*/T*/ => array (
                                        "type"          =>"yesno",
                                        "description"   =>/*T*/"Select YES if you want an invoice sent to the customer after signup is complete."/*/T*/,
                                        "value"         =>"1"
                                       ),
                   /*T*/"Signup Name"/*/T*/ => array (
                                        "type"          =>"text",
                                        "description"   =>/*T*/"Select the name to display in the signup process for this payment type. Example: eCheck or Credit Card."/*/T*/,
                                        "value"         =>"Credit Card"
                                       ),
                   /*T*/"Dummy Plugin"/*/T*/ => array (
                                        "type"          =>"hidden",
                                        "description"   =>/*T*/"1 = Only used to specify a billing type for a customer. 0 = full fledged plugin requiring complete functions"/*/T*/,
                                        "value"         =>"0"
                                       ),
                   /*T*/"Auto Payment"/*/T*/ => array (
                                        "type"          =>"hidden",
                                        "description"   =>/*T*/"No description"/*/T*/,
                                        "value"         =>"0"
                                       ),
                   /*T*/"30 Day Billing"/*/T*/ => array (
                                        "type"          =>"hidden",
                                        "description"   =>/*T*/"Select YES if you want ClientExec to treat monthly billing by 30 day intervals.  If you select NO then the same day will be used to determine intervals."/*/T*/,
                                        "value"         =>"0"
                                       ),
                   /*T*/"Check CVV2"/*/T*/ => array (
                                        "type"          =>"hidden",
                                        "description"   =>/*T*/"Select YES if you want to accept CVV2 for this plugin."/*/T*/,
                                        "value"         =>"0"
                                       )
        );
        return $variables;
    }

    function credit($params)
    { }

    function singlepayment($params)
    {
        //Function needs to build the url to the payment processor, then redirect
        //Plugin variables can be accesses via $params["plugin_[pluginname]_[variable]"] (ex. $params["plugin_2checkout_SellerID"])

        $return_url = mb_substr($params['clientExecURL'],-1,1) == "//" ? $params['clientExecURL']."plugins/gateways/2checkout/callback.php" : $params['clientExecURL']."/plugins/gateways/2checkout/callback.php";

        if ($params["userCountry"]=="US") $params["userCountry"]="USA";

        $tPrice = $params["invoiceTotal"] - $params["invoiceSetup"];

        // Start building the URL that will be used to send customers to 2CO for payment.
        //OLD URL
        //$strURL = "https://www2.2checkout.com/2co/buyer/purchase";

        // NEW URLs
        if(isset($params["plugin_2checkout_Purchase Routine"]) && $params["plugin_2checkout_Purchase Routine"] == 1){
            $strURL = "https://www.2checkout.com/checkout/spurchase";
        }else{
            $strURL = "https://www.2checkout.com/checkout/purchase";
        }

        include_once 'modules/billing/models/Currency.php';
        $currency = new Currency($this->user);
        // Basic parameters
        $strURL .= "?x_login=".$params["plugin_2checkout_Seller ID"];
        $strURL .= "&x_invoice_num=".$params["invoiceNumber"];
        $strURL .= "&x_amount=".$currency->format($this->settings->get('Default Currency'), $params["invoiceTotal"]);
        $strURL .= "&id_type=1";

        // Product Creation code (so CE can send 2CO on-the-fly orders,
        // as it does not store 2CO information yet.)
        /*
        // IT SEEMS THIS PARAMS ARE NO LONGER NEEDED.
        $strURL .= "&c_prod=ce_".$params["invoiceNumber"];
        $strURL .= "&c_name=".$params["companyName"]." - Subscription";
        $strURL .= "&c_description=".$params["companyName"]." - Subscription";
        $strURL .= "&c_price=".$currency->format($this->settings->get('Default Currency'), $tPrice);
        $strURL .= "&c_tangible=N";
        */

        // If Demo Mode is set, pass appropriate parameter.
        if ($params["plugin_2checkout_Demo Mode"]==1)
            $strURL .= "&demo=Y";

        $strURL .= "&acc_can=Y&acc_int=Y&diff_ship=N&can_handling=0.00&int_handling=0.00&fixed=Y";

        // Billing Information so the 2checkout form is pre-filled.
        //$strURL .= "&card_holder_name=".$params["userFirstName"]." ".$params["userLastName"];
        $strURL .= "&x_First_Name=".$params["userFirstName"];
        $strURL .= "&x_Last_Name=".$params["userLastName"];
        $strURL .= "&x_Email=".$params["userEmail"];
        $strURL .= "&x_Address=".$params["userAddress"];
        $strURL .= "&x_City=".$params["userCity"];
        $strURL .= "&x_State=".$params["userState"];
        $strURL .= "&x_Zip=".$params["userZipcode"];
        $strURL .= "&x_Phone=".$params["userPhone"];
        $strURL .= "&x_Country=".$params["userCountry"];

        //$strURL .= "&credit_card_processed=";
        $strURL .= "&x_receipt_link_url=".$return_url;

        // Custom Parameters Passed thru 2CO back to CE
        if ($params['isSignup']==1)
            $strURL .= "&signup=1";
        else
            $strURL .= "&signup=0";
        $strURL .= "&ce_invoice_num=".$params['invoiceNumber'];

        $tempInvoice = new Invoice($params['invoiceNumber']);
        $tInvoiceHash = $tempInvoice->generateInvoiceHash($params['invoiceNumber']);
        if(!is_a($tInvoiceHash, 'CE_Error')){
            $strURL .= "&ce_invoice_hash=".$tInvoiceHash;
        }else{
            $strURL .= "&ce_invoice_hash="."WRONGHASH";
        }

        // Send to 2CO for payment
        header("Location: $strURL");
        exit;
     }
}

?>
