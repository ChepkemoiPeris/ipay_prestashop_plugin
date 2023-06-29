<?php
class IpaymoduleCallbackModuleFrontController extends ModuleFrontController
{
	private function processOrder()
	{
		$cart_id = (int)Tools::getValue('cart_id');
		$secure_key = Tools::getValue('key');
		/* @var CartCore $cart */
		$cart = new Cart($cart_id);
		/* @var CustomerCore $customer */
		$customer = new Customer($cart->id_customer);

		$order_details = array(
			'id_cart' => $cart_id,
			'id_module' => $this->module->id,
			'ipaymodule-status' => 'successful',
			'key' => $secure_key
		);

		$this->console_log($order_details);

		Tools::redirectLink($this->context->link->getPageLink('order-confirmation', true, null, $order_details));
	}
	private function console_log($data)
	{
		echo '<script>';
		// echo 'console.log('. json_encode( $data ) .')';
		print_r('console.log(' . json_encode($data) . ')');
		echo '</script>';
	}
	public function postProcess()
	{
		$val = Configuration::get('Ipay_VENDOR_ID');
		$key = Configuration::get('Ipay_HASHKEY');
		$status = $oid = '';

		if (
			isset($_GET['id'])
			&& isset($_GET['ivm'])
			&& isset($_GET['qwh'])
			&& isset($_GET['afd'])
			&& isset($_GET['poi'])
			&& isset($_GET['uyt'])
			&& isset($_GET['ifd'])
			&& isset($_GET['p1'])
			&& isset($_GET['p1'])
		) {
			$oid = $_GET['id'];  //order id
			$val2 = $_GET['ivm'];
			$val3 = $_GET['qwh'];
			$val4 = $_GET['afd'];
			$val5 = $_GET['poi'];
			$val6 = $_GET['uyt'];
			$val7 = $_GET['ifd'];
			$val8 = $_GET['p1'];
			$val9 = $_GET['p2'];
			$ipnurl = "https://www.ipayafrica.com/ipn/?vendor=" . $val . "&id=" . $oid . "&ivm=" . $val2 . "&qwh=" . $val3 . "&afd=" . $val4 . "&poi=" . $val5 . "&uyt=" . $val6 . "&ifd=" . $val7;

			if ($val == "demo") {
				$status = "aei7p7yrx4ae34";
			} else {
				$fp = fopen($ipnurl, "rb");
				$status = stream_get_contents($fp, -1, -1);
				fclose($fp);
			}
		} elseif (isset($_GET['vsc']) && !empty($_GET['vsc'])) {

			$oid = $_GET['vsc'];
			$dataString = $oid . $val;
			$generated_hash = hash_hmac('sha256', $dataString, $key);

			$payload = json_encode(array(
				"oid" => $oid,
				"vid" => $val,
				"hash" => $generated_hash,
			));

			$result = $this->simple_curl($payload);

			if (isset($result['status']) && $result['status'] == 1) {
				$status = "aei7p7yrx4ae34";
			} else {
				$status = "fe2707etr5s4wq";
			}
		}



		if ($status == 'fe2707etr5s4wq') { //failed
			$paymentStatus = 'Transaction FAILED. Please try again.';
			Db::getInstance()->Execute('UPDATE `' . _DB_PREFIX_ . 'order_history` SET `id_order_state`=8 WHERE `id_order` = ' . (int)($oid));
			Db::getInstance()->Execute('UPDATE `' . _DB_PREFIX_ . 'orders` SET `current_state`=8 WHERE `id_order` = ' . (int)($oid));
			echo $paymentStatus;
			//Tools::redirect('');
		} else if ($status == 'aei7p7yrx4ae34') { //success
			$paymentStatus = 'Payment Successful';
			Db::getInstance()->Execute('UPDATE `' . _DB_PREFIX_ . 'order_history` SET `id_order_state`=2 WHERE `id_order` = ' . (int)($oid));
			Db::getInstance()->Execute('UPDATE `' . _DB_PREFIX_ . 'orders` SET `current_state`=2 WHERE `id_order` = ' . (int)($oid));
			$this->processOrder();
		} else if ($status == 'bdi6p2yy76etrs') { //pending
			$paymentStatus = 'Incoming Mobile Money Transaction Not found. Please try again in 5 minutes.';
		} else if ($status == 'cr5i3pgy9867e1') { //used
			$paymentStatus = 'The code you are attempting to pass has been used already.';
		} else if ($status == 'dtfi4p7yty45wq') { //less
			$paymentStatus = 'The amount that you have sent via mobile money is LESS than what was required to validate this transaction.';
		} else if ($status == 'eq3i7p5yt7645e') { //more
			$paymentStatus = 'The amount that you have sent via mobile money is MORE than what was required to validate this transaction.';
			Db::getInstance()->Execute('UPDATE `' . _DB_PREFIX_ . 'order_history` SET `id_order_state`=2 WHERE `id_order` = ' . (int)($oid));
			Db::getInstance()->Execute('UPDATE `' . _DB_PREFIX_ . 'orders` SET `current_state`=2 WHERE `id_order` = ' . (int)($oid));
		}
		echo "
		<p>
		  Thank you for choosing iPay.
		  <br/><br />
		  Payments made Easy.
		  <br/><br />
		  Payment status for order ID <span><b>$oid</b></span> : <span><b>$paymentStatus</b></span> 
		  <br/><br />		   
		</p>";
		exit();
	}

	private function simple_curl($payload)
	{
		// Prepare new cURL resource
		$ch = curl_init("https://apis.ipayafrica.com/payments/v2/transaction/search");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($payload)
			)
		);

		$response = curl_exec($ch);
		if ($response == false) {
			$err = curl_error($ch);
			$result = array(
				"status" => "400",
				"description" => $err,
			);
			curl_close($ch);

			return $result;
		}

		curl_close($ch);
		return json_decode($response, true);
	}
}
