<?php
/**
 * @author Jinu Joseph Daniel
 * Email: jinujosephdaniel@gmail.com, jinujosephdaniel@cocoalabs.in
 * git: github.com/jinujd
 * 
 */
namespace common\components;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\Url;
class CCAvenueComponent extends Component
{
   public $KEY;
   public $ACCESS_CODE;
   public $MERCHANT_ID;
   public $REDIRECT_ACTION;
   public $CANCEL_ACTION;
   private $PAYMENT_URL =  'https://secure.ccavenue.ae/transaction/transaction.do?command=initiateTransaction';
   public $supportedParams = [
     "order_id",
     "amount",
     "currency",
     "language",
     "merchant_id",
     "redirect_url",
     "cancel_url",
     "billing_name",
     "billing_address",
     "billing_city",
     "billing_state",
     "billing_zip",
     "billing_country",
     "billing_tel",
     "billing_email",
     "delivery_name",
     "delivery_address",
     "delivery_city",
     "delivery_state",
     "delivery_zip",
     "delivery_country",
     "delivery_tel",
     "merchant_param1",
     "merchant_param2",
     "merchant_param3",
     "merchant_param4",
     "merchant_param5",
     "merchant_param6",
     "promo_code",
     "customer_identifier"
   ];
   public function __construct($config=[]) {
     parent::__construct($config);
     $this->REDIRECT_ACTION = Url::to($this->REDIRECT_ACTION,true);
     $this->CANCEL_ACTION = Url::to($this->CANCEL_ACTION,true);
   }
   public function initiatePayment($settings) {
     $settings['order_id'] = isset($settings['order_id'])? $settings['order_id']:null;
     $settings['amount'] = isset($settings['amount'])? $settings['amount']:null;
     $settings['language'] = isset($settings['language'])? $settings['language']:'EN';
     $settings['currency'] = isset($settings['currency'])? $settings['currency']:'USD';
     $settings['merchant_id'] = $this->MERCHANT_ID;
     $settings['redirect_url'] = $this->REDIRECT_ACTION;
     $settings['cancel_url'] = $this->CANCEL_ACTION;
     $merchantData = [];
     foreach($settings as $key => $val) {
       if(in_array($key,$this->supportedParams)) {
         $merchantData[] =  $key.'='.$val;
       }
     }
     $merchantData = array_filter($merchantData);
     $merchantData = implode('&',$merchantData);

     $encryptedData = $this->encrypt($merchantData,$this->KEY);

     $params = ['encRequest'=>$encryptedData,'access_code'=>$this->ACCESS_CODE];
     $this->curlPost($this->PAYMENT_URL,$params);

   }
   public function extractInfo() {
     $ret = [];
     $postParams = Yii::$app->request->post();
     if(!($postParams&&isset($postParams["encResp"]))) return [];
     $encResponse=$postParams["encResp"];
     $rcvdString=$this->decrypt($encResponse,$this->KEY);
   	 $decryptValues=explode('&', $rcvdString);
   	 $dataSize=sizeof($decryptValues);
     $orderStatus = '';
     $data = [];
     for($i = 0; $i < $dataSize; $i++)  {
   		$information = explode('=',$decryptValues[$i]);
      if(sizeof($information)>1) {
        $key = $information[0];
        $val = $information[1];
     		if($i == 3)	$orderStatus=$information[1];
        $data[$key] = $val;
      }
   	 }
     $orderStatus = strtolower($orderStatus);
     $ret  = [

          'information' => $data,
          'order_status' => $orderStatus
     ];
     return $ret;
   }
   private function curlPost($url,$params) {
     $html = "<form method = 'post' action = '$url' id = 'frm1'>";

     foreach($params as $param => $vl) {
       $html .= "<input type = 'hidden' name ='$param' value ='$vl' />";
     }
     $html .= "</form>
      <script>
        document.getElementById('frm1').submit();
      </script>
     ";
      echo $html;exit;

     $fields = [];


     $ch = curl_init(); ;

     curl_setopt($ch, CURLOPT_URL,$url);
     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
     curl_setopt($ch, CURLOPT_VERBOSE, true);
     curl_setopt($ch, CURLOPT_POST, 1);
     curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($params));
     curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
     curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);

    $serverOutput = curl_exec ($ch);
    curl_close ($ch);
   }

   private function encrypt($plainText,$key) {
   		$secretKey = $this->hextobin(md5($key));
   		$initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
   	  $openMode = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '','cbc', '');
   	  $blockSize = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, 'cbc');
   		$plainPad = $this->pkcs5_pad($plainText, $blockSize);
   	  if (mcrypt_generic_init($openMode, $secretKey, $initVector) != -1) {
   		   $encryptedText = mcrypt_generic($openMode, $plainPad);
   	     mcrypt_generic_deinit($openMode);
   		}
   		return bin2hex($encryptedText);
   	}
    private function decrypt($encryptedText,$key)
   	{
   		$secretKey = $this->hextobin(md5($key));
   		$initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
   		$encryptedText=$this->hextobin($encryptedText);
   	  $openMode = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '','cbc', '');
   		mcrypt_generic_init($openMode, $secretKey, $initVector);
   		$decryptedText = mdecrypt_generic($openMode, $encryptedText);
   		$decryptedText = rtrim($decryptedText, "\0");
   	 	mcrypt_generic_deinit($openMode);
   		return $decryptedText;
   	}
   	//*********** Padding Function *********************

   	private function pkcs5_pad($plainText, $blockSize) {
   	   $pad = $blockSize - (strlen($plainText) % $blockSize);
   	   return $plainText . str_repeat(chr($pad), $pad);
   	}

   	//********** Hexadecimal to Binary function for php 4.0 version ********

   	private function hextobin($hexString) {
      $length = strlen($hexString);
      $binString="";
      $count=0;
      while($count<$length) {
        $subString =substr($hexString,$count,2);
        $packedString = pack("H*",$subString);
        if ($count==0){
   				$binString=$packedString;
   		  } else {
          $binString.=$packedString;
        }
        $count+=2;
      }
      return $binString;
    }



}
