# Yii2-ccavenue
CCAvenue payment gateway integration for  PHP - Yii2 framework
By: Jinu Joseph Daniel, jinujosephdaniel@gmail.com, jinujosephdaniel@cocoalabs.in

How to configure
----------------
1. Put the CCAvenueComponent.php file to /common/components.
2. Add the component in main.php. Sample code in main.php is given below
<?php
return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'timeZone' => 'Asia/Kolkata',
    'components' => [ 

     'ccavenue' => [
        'class' => 'common\components\CCAvenueComponent',
        'KEY' => '<YOUR KEY>',
        'ACCESS_CODE' => '<YOUR ACCESS CODE>',
        'MERCHANT_ID' => '<YOUR MERCHANT ID>',
        'REDIRECT_ACTION' => <ACTION TO HANDLE PAYMENT SUCCESS/FAILURE>, // ex: ['bookings/success']
        'CANCEL_ACTION' => <ACTION TO HANDLE PAYMENT CANCELLATION> //ex: ['bookings/cancel'] .Happens when cancel button in ccavenue window is clicked

     ],  
];
?>

In the above code , configure the redirect and cancel actions appropriately.
In the redirect action or cancel action, you can extract the received parameters using 
$params = Yii::$app->ccavenue->extractInfo();
In $params order status and mechant parameters will be available. 




