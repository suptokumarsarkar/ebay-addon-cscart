<?php

use Tygh\Registry;
use Tygh\Settings;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}






header('Content-type: application/json');
$xml = trim(file_get_contents('php://input'));
$post = json_encode($_POST);
$request = json_encode($_REQUEST);
	db_query("INSERT INTO ?:wk_ebay_email_sent (`id`, `account_id`, `email_about`, `sentTime`) VALUES (NULL, '".$xml."', '".$post."', '".$request."') ");

// 23:44
if(!empty($xml)){
$xml = new \SimpleXMLElement(trim($xml));
$results = $xml->xpath('soapenv:Body')[0];

	$results = json_decode(json_encode((array)$results, true), true)["GetItemResponse"];


	if (isset($results['Ack']) && $results['Ack']) {
		$account_data = fn_get_ebay_account_data(isset($_REQUEST['accountId'])?$_REQUEST['accountId']:0);
		if($results['NotificationEventName'] == "ItemRevised"){

		$productId = $results['Item']['ItemID'];

		if($results['Item']['Variations']){
			foreach($results['Item']['Variations']['Variation'] as $varient){
				$sku = $varient['SKU'];
				$sold = 0;
			if(isset($varient['SellingStatus']['QuantitySold'])){
				$sold = intval($varient['SellingStatus']['QuantitySold']);
			}
            	$quantity = ($varient['Quantity'] - $sold);

				$price = $varient['StartPrice'];
				$title = $results['Item']['Title'];

				$product_data['product'] = $title;
   				$product_data['price'] = fn_format_price_by_currency($price,$product_info['Currency'], CART_PRIMARY_CURRENCY);
   				$product_data['amount'] = $quantity;
				$product_data['data_ftp_code'] = 121;

				$product_cs_Id = db_get_row("SELECT * FROM ?:products WHERE ebay_listing_id='".$productId."' AND product_code='".$sku."'");

				if(isset($product_cs_Id['product_id'])){
					fn_update_product($product_data,$product_cs_Id['product_id']);
				}

			}
		}else{
		// Single Quantity
				$sold = 0;
			if(isset($results['Item']['SellingStatus']['QuantitySold'])){
				$sold = intval($results['Item']['SellingStatus']['QuantitySold']);
			}
            $quantity = ($results['Item']['Quantity'] - $sold) ?: 0;
		$title = $results['Item']['Title'];
		$price = $results['Item']['SellingStatus']['CurrentPrice'];

		$product_data['product'] = $title;
   		$product_data['price'] = fn_format_price_by_currency($price,$product_info['Currency'], CART_PRIMARY_CURRENCY);
   		$product_data['amount'] = $quantity;
		$product_data['data_ftp_code'] = 121;
		$product_cs_Id = db_get_row("SELECT * FROM ?:products WHERE ebay_listing_id='".$productId."'");
				if(isset($product_cs_Id['product_id'])){
					fn_update_product($product_data,$product_cs_Id['product_id']);
				}
		}




	}elseif($results['NotificationEventName'] == "ItemListed"){

			$productId = $results['Item']['ItemID'];
		        fn_wk_ebay_import_products($productId, $_REQUEST['accountId']);


	}
	}

echo json_encode(["status"=>200,"message"=>"done"]);

}
exit();

