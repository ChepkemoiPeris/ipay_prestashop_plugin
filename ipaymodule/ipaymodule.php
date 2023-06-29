<?php

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_'))
	exit;

class IpayModule extends PaymentModule
{
	protected $_html = '';
	protected $_postErrors = array();
	public $vendor_id;
	public $merchant_name;
	public $callback_url;
	public $hashkey;
	public $mm;
	public $mb;
	public $dc;
	public $cc;
	public $live;
	public $is_eu_compatible;
	public $autopay;
	public $lbk;
	public function __construct()
	{
		$this->name = 'ipaymodule';
		$this->tab = 'payments_gateways';
		$this->version = '1.0.0';
		$this->author = 'iPay Ltd.';
		$this->controllers = array('payment', 'validation', 'callback');
		$this->is_eu_compatible = 1;
		$this->need_instance = 0;

		$this->currencies = true;
		$this->currencies_mode = 'checkbox';

		$config = Configuration::getMultiple(array('Ipay_VENDOR_ID', 'Ipay_MERCHANT_NAME', 'Ipay_CALLBACK_URL', 'Ipay_HASHKEY', 'Ipay_MOBILE_MONEY', 'Ipay_MOBILE_BANKING', 'Ipay_DEBIT_CARD', 'Ipay_CREDIT_CARD', 'Ipay_LIVE', 'Ipay_AUTOPAY', 'Ipay_LBK'));
		if (!empty($config['Ipay_VENDOR_ID']))
			$this->vendor_id = $config['Ipay_VENDOR_ID'];
		if (!empty($config['Ipay_MERCHANT_NAME']))
			$this->merchant_name = $config['Ipay_MERCHANT_NAME'];
		if (!empty($config['Ipay_CALLBACK_URL']))
			$this->callback_url = $config['Ipay_CALLBACK_URL'];
		if (!empty($config['Ipay_HASHKEY']))
			$this->hashkey = $config['Ipay_HASHKEY'];
		if (!empty($config['Ipay_MOBILE_MONEY']))
			$this->mm = $config['Ipay_MOBILE_MONEY'];
		else
			$this->mm = 0;
		if (!empty($config['Ipay_MOBILE_BANKING']))
			$this->mb = $config['Ipay_MOBILE_BANKING'];
		else
			$this->mb = 0;
		if (!empty($config['Ipay_DEBIT_CARD']))
			$this->dc = $config['Ipay_DEBIT_CARD'];
		else
			$this->dc = 0;
		if (!empty($config['Ipay_CREDIT_CARD']))
			$this->cc = $config['Ipay_CREDIT_CARD'];
		else
			$this->cc = 0;
		if (!empty($config['Ipay_LIVE']))
			$this->live = $config['Ipay_LIVE'];
		else
			$this->live = 0;
		if (!empty($config['Ipay_AUTOPAY']))
			$this->autopay = $config['Ipay_AUTOPAY'];
		else
			$this->autopay = 0;
		if (!empty($config['Ipay_LBK']))
			$this->lbk = $config['Ipay_LBK'];
		else
			$this->lbk = 0;
		parent::__construct();

		$this->displayName = $this->l('iPay');
		$this->description = $this->l('.:: iPay – Payments made Easy ::.');
		$this->confirmUninstall = $this->l('Are you sure you want to uninstall iPay?');
		if (!isset($this->vendor_id) || !isset($this->merchant_name) || !isset($this->callback_url) || !isset($this->hashkey))
			$this->warning = $this->l('Merchant details must be configured before you use module.');
		if (!count(Currency::checkPaymentCurrencies($this->id)))
			$this->warning = $this->l('No currency has been set for this module.');
	}

	public function install()
	{
		if (!parent::install() || $this->registerHook('paymentOptions') == false || $this->registerHook('paymentReturn') == false) {
			return false;
		} else {
			return true;
		}
	}

	public function uninstall()
	{
		if (
			!parent::uninstall() || !Configuration::deleteByName('Ipay_VENDOR_ID') || !Configuration::deleteByName('Ipay_MERCHANT_NAME')
			|| !Configuration::deleteByName('Ipay_CALLBACK_URL') || !Configuration::deleteByName('Ipay_HASHKEY')
			|| !Configuration::deleteByName('Ipay_MOBILE_MONEY') || !Configuration::deleteByName('Ipay_MOBILE_BANKING')
			|| !Configuration::deleteByName('Ipay_DEBIT_CARD') || !Configuration::deleteByName('Ipay_CREDIT_CARD')
			|| !Configuration::deleteByName('Ipay_LIVE')
			|| !Configuration::deleteByName('Ipay_AUTOPAY')
			|| !Configuration::deleteByName('Ipay_LBK')
		) {
			return false;
		} else {
			return true;
		}
	}

	public function _postValidation()
	{
		if (Tools::isSubmit('submit')) {
			//   dump(Tools::getAllValues());exit;
			if (!Tools::getValue('Ipay_VENDOR_ID'))
				$this->_postErrors[] = $this->l('Your iPay Vendor ID is required.');
			elseif (!Tools::getValue('Ipay_MERCHANT_NAME'))
				$this->_postErrors[] = $this->l('Merchant Name is required.');
			elseif (!Tools::getValue('Ipay_HASHKEY'))
				$this->_postErrors[] = $this->l('Your iPay hashkey is required');
			// elseif (!isset($_POST['MOBILE_MONEY']) && !isset($_POST['MOBILE_BANKING']) && !isset($_POST['DEBIT_CARD']) && !isset($_POST['CREDIT_CARD']))
			// 	$this->_postErrors[] = $this->l('You need to select at least one payment method');
			/* elseif (!Tools::getValue('Ipay_LIVE'))
				$this->_postErrors[] = $this->l('The live parameter is required '); */
		}
	}

	public function _postProcess()
	{
		if (Tools::isSubmit('submit')) {
			Configuration::updateValue('Ipay_VENDOR_ID', Tools::getValue('Ipay_VENDOR_ID'));
			Configuration::updateValue('Ipay_MERCHANT_NAME', Tools::getValue('Ipay_MERCHANT_NAME'));
			Configuration::updateValue('Ipay_CALLBACK_URL', _PS_BASE_URL_ . __PS_BASE_URI__ . 'module/' . $this->name . '/callback'); //path to the callback.php file within folder
			Configuration::updateValue('Ipay_HASHKEY', Tools::getValue('Ipay_HASHKEY'));
			Configuration::updateValue('Ipay_LIVE', Tools::getValue('Ipay_LIVE'));
			Configuration::updateValue('Ipay_AUTOPAY', Tools::getValue('Ipay_AUTOPAY'));
			Configuration::updateValue('Ipay_LBK', Tools::getValue('Ipay_LBK'));
			// if (isset($_POST['MOBILE_MONEY_mm'])) {
			// 	$mmoney = $_POST['MOBILE_MONEY_mm'];
			// } else {
			// 	$mmoney = 0;
			// }
			// Configuration::updateValue('Ipay_MOBILE_MONEY', $mmoney);
			// if (isset($_POST['MOBILE_BANKING_mb'])) {
			// 	$mbank = $_POST['MOBILE_BANKING_mb'];
			// } else {
			// 	$mbank = 0;
			// }
			// Configuration::updateValue('Ipay_MOBILE_BANKING', $mbank);
			// if (isset($_POST['DEBIT_CARD_dc'])) {
			// 	$dcard = $_POST['DEBIT_CARD_dc'];
			// } else {
			// 	$dcard = 0;
			// }
			// Configuration::updateValue('Ipay_DEBIT_CARD', $dcard);
			// if (isset($_POST['CREDIT_CARD_cc'])) {
			// 	$ccard = $_POST['CREDIT_CARD_cc'];
			// } else {
			// 	$ccard = 0;
			// }
			// Configuration::updateValue('Ipay_CREDIT_CARD', $ccard);
			// if (isset($_POST['LIVE_live'])) {
			// 	$live = $_POST['LIVE_live'];
			// } else {
			// 	$live = 0;
			// }
			// $live  = $live;
			// Configuration::updateValue('Ipay_LIVE', $live);
		}
		$this->_html .= $this->displayConfirmation($this->l('Settings updated '));
	}

	public function getContent()
	{
		$this->html = '';
		if (Tools::isSubmit('submit')) {
			$this->_postValidation();
			if (!count($this->_postErrors))
				$this->_postProcess();
			else
				foreach ($this->_postErrors as $err)
					$this->_html .= $this->displayError($err);
		} else
			$this->_html = "<h2>" . $this->displayName . " Payment Module</h2>";
		$this->_html .= $this->displayForm();
		return $this->_html;
	}

	public function hookPaymentOptions($params)
	{
		if (!$this->active)
			return;
		if (!$this->checkCurrency($params['cart']))
			return;

		$iPayOption = new PaymentOption();
		$iPayOption->setModuleName($this->name)
			->setCallToActionText($this->l('Pay using Credit or Debit Card'))
			->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
			->setLogo(Media::getMediaPath(_MODULE_DIR_ . $this->name . '/ipay_10_payment_channels_small.png'));
		return array($iPayOption);
	}

	public function hookPaymentReturn($params)
	{
		if (!$this->active)
			return;
		if (!isset($params['order']) || ($params['order']->module != $this->name)) {
			return false;
		}
		$this->console_log($params);
		$customer = new Customer((int)$this->context->customer->id);
		$customerInfo = $customer->getAddresses((int)$this->context->customer->id_lang); //get details of the logged on customer
		$live = Configuration::get('Ipay_LIVE');
		$oid = $params['order']->id;
		if ($customerInfo[0]['phone_mobile'] != "") {
			$tel = $customerInfo[0]['phone_mobile'];
		} elseif ($customerInfo[0]['phone'] != "") {
			$tel = $customerInfo[0]['phone'];
		}
		$tel = str_replace(array(' ', '<', '>', '&', '{', '}', '*', "+", '!', '@', '#', "$", '%', '^', '&'), "", $tel);
		//  $this->console_log($customerInfo);
		//  $tel = '254726583945'; 
		$cart = $params['cart'];
		$eml = $this->context->customer->email;
		$ttl = $params['order']->getOrdersTotalPaid();
		$mer = $this->merchant_name;
		$vid = $this->vendor_id;

		$return_url = $this->context->link->getModuleLink('ipaymodule', 'callback', array('cart_id' => $cart->id, 'key' => $customer->secure_key, 'vsc' => $oid), true);

		//  $cbk = $this->callback_url;
		$cbk = $return_url;

		//  $callback = "Callback ".$cbk;
		//$callback = "Callback " . $return_url;
		$this->console_log($params['order']);

		$hashkey = $this->hashkey;
		$mm = $this->mm;
		$mb = $this->mb;
		$dc = $this->dc;
		$cc = $this->cc;

		$inv = $oid;
		$currency = new Currency((int)($params['cart']->id_currency));
		$currency_code = trim($currency->iso_code);
		$curr = $currency_code;
		$p1 = ''; //Allows sending & receiving your custom parameters
		$p2 = ''; //Allows sending & receiving your custom parameters
		$p3 = ''; //Allows sending & receiving your custom parameters
		$p4 = ''; //Allows sending & receiving your custom parameters
		$cst = 1;
		$crl = 0;
		//datastring
		$datastring = $live . $oid . $inv . $ttl . $tel . $eml . $vid . $curr . $p1 . $p2 . $p3 . $p4 . $cbk . $cst . $crl;
		$cbk = urlencode($cbk);

		//Generating unique Hash ID
		$hsh = hash_hmac('sha1', $datastring, $hashkey);

		// shopping cart identifier
		$channel = "PrestaShop";

		// mandatory payment channels
		$vooma = '1';
		$bonga = '1';
		$mpesa = '0';
		$autopay = $this->autopay;

		//lbk
		if ($this->lbk == 1) {
			$ipay_url_get_params = "?live=" . $live . "&oid=" . $oid . "&inv=" . $inv . "&ttl=" . $ttl . "&tel=" . $tel . "&eml=" . $eml . "&vid=" . $vid . "&p1=" . $p1 . "&p2=" . $p2 . "&p3=" . $p3 . "&p4=" . $p4 . "&crl=" . $crl . "&cbk=" . $cbk . "&cst=" . $cst . "&curr=" . $curr . "&hsh=" . $hsh . "&lbk=" . $cbk . "&autopay=" . $autopay . "&bonga=" . $bonga . "&channel=" . $channel . "&vooma=" . $vooma;
		} else {
			$ipay_url_get_params = "?live=" . $live . "&oid=" . $oid . "&inv=" . $inv . "&ttl=" . $ttl . "&tel=" . $tel . "&eml=" . $eml . "&vid=" . $vid . "&p1=" . $p1 . "&p2=" . $p2 . "&p3=" . $p3 . "&p4=" . $p4 . "&crl=" . $crl . "&cbk=" . $cbk . "&cst=" . $cst . "&curr=" . $curr . "&hsh=" . $hsh . "&autopay=" . $autopay . "&bonga=" . $bonga . "&channel=" . $channel . "&vooma=" . $vooma;
		}

		// payment redirect
		$ipay_url = 'https://payments.ipayafrica.com/v3/ke';

		//$ipay_url_get_params = "?live=" . $live . "&oid=" . $oid . "&inv=" . $inv . "&ttl=" . $ttl . "&tel=" . $tel . "&eml=" . $eml . "&vid=" . $vid . "&p1=" . $p1 . "&p2=" . $p2 . "&p3=" . $p3 . "&p4=" . $p4 . "&crl=" . $crl . "&cbk=" . $cbk . "&cst=" . $cst . "&curr=" . $curr . "&hsh=" . $hsh . "&bonga=" . $bonga . "&channel=" . $channel . "&vooma=" . $vooma . "&mpesa=" . $mpesa;
		$this->context->smarty->assign(array(
			'url' =>  $ipay_url . $ipay_url_get_params
		));
		// return $this->fetch('ipaymodule/views/templates/hook/payment_return.tpl');
		// return $this->fetch('module:ipaymodule/views/templates/hook/payment_return.tpl');
		Tools::redirect($ipay_url . $ipay_url_get_params);
	}
	private function console_log($data)
	{
		echo '<script>';
		echo 'console.log(' . json_encode($data) . ')';
		//print_r('console.log('. json_encode( $data ) .')');
		echo '</script>';
	}

	public function checkCurrency($cart)
	{
		$currency_order = new Currency($cart->id_currency);
		$currencies_module = $this->getCurrency($cart->id_currency);

		if (is_array($currencies_module))
			foreach ($currencies_module as $currency_module)
				if ($currency_order->id == $currency_module['id_currency'])
					return true;
		return false;
	}

	public function displayForm()
	{
		$fields_form[0]['form'] = array(
			'legend' => array(
				'title' => $this->l('Merchant Details'),
				'icon' => 'icon-envelope'
			),
			'input' => array(
				array(
					'type' => 'select',
					'label' => 'Country',
					'name' => ' Ipay_COUNTRY',
					'required' => true,
					'options' => array(
						'query' => array(
							array(
								'id' => 'KE',
								'name' => 'Kenya'
							),
							array(
								'id' => 'UG',
								'name' => 'Uganda'
							),
							array(
								'id' => 'TZ',
								'name' => 'Tanzania'
							),
							array(
								'id' => 'TG',
								'name' => 'Togo'
							),
							array(
								'id' => 'MW',
								'name' => 'Malawi'
							),
							array(
								'id' => 'SO',
								'name' => 'Somali'
							),
							array(
								'id' => 'NG',
								'name' => 'Naigeria'
							),
							array(
								'id' => 'RW',
								'name' => 'Rwanda'
							),
							array(
								'id' => 'ZM',
								'name' => 'Zambia'
							),
						),
						'id' => 'id',
						'name' => 'name'
					)
				),
				array(
					'type' => 'text',
					'label' => $this->l('Vendor ID(As assigned by iPay. Set in Lower Case)'),
					//'desc' => 'Vendor ID assigned by iPay. Set in Lower Case',
					'name' => 'Ipay_VENDOR_ID',
					'size' => 20,
					'required' => true
				),
				array(
					'type' => 'text',
					'label' => $this->l('Merchant Name'),
					'name' => 'Ipay_MERCHANT_NAME',
					'size' => 20,
					'required' => true
				),
				array(
					'type' => 'text',
					'label' => $this->l('Hashkey'),
					'name' => 'Ipay_HASHKEY',
					'size' => 20,
					'required' => true
				),
				array(
					'name' => 'Ipay_LIVE',
					'type' => 'switch',
					'label' => $this->l('Live?'),
					'values' => array(
						array(
							'id' => 'live',
							'value' => 1,
							'label' => $this->l('Enabled')
						),
						array(
							'id' => 'live',
							'value' => 0,
							'label' => $this->l('Disabled')
						),
					)
				),
				array(
					'name' => 'Ipay_AUTOPAY',
					'type' => 'switch',
					'label' => $this->l('Autopay?'),
					'values' => array(
						array(
							'id' => 'autopay',
							'value' => 1,
							'label' => $this->l('Enabled')
						),
						array(
							'id' => 'autopay',
							'value' => 0,
							'label' => $this->l('Disabled')
						),
					)
				),
				array(
					'name' => 'Ipay_LBK',
					'type' => 'switch',
					'label' => $this->l('LBK?'),
					'values' => array(
						array(
							'id' => 'lbk',
							'value' => 1,
							'label' => $this->l('Enabled')
						),
						array(
							'id' => 'lbk',
							'value' => 0,
							'label' => $this->l('Disabled')
						),
					)
				),
			),
			'submit' => array(
				'title' => $this->l('Save'),
				'class' => 'button'
			)
		);

		$helper = new HelperForm();
		$helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
		$helper->title = $this->displayName;
		$helper->submit_action = 'submit';
		$helper->toolbar_btn = array(
			'save' => array(
				'desc' => $this->l('Save'),
			)
		);
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues()
		);
		return $helper->generateForm($fields_form);
	}
	public function getConfigFieldsValues()
	{
		return array(
			'Ipay_VENDOR_ID' => Tools::getValue('Ipay_VENDOR_ID', Configuration::get('Ipay_VENDOR_ID')),
			'Ipay_MERCHANT_NAME' => Tools::getValue('Ipay_MERCHANT_NAME', Configuration::get('Ipay_MERCHANT_NAME')),
			'Ipay_CALLBACK_URL' => Tools::getValue('Ipay_CALLBACK_URL', Configuration::get('Ipay_CALLBACK_URL')),
			'Ipay_HASHKEY' => Tools::getValue('Ipay_HASHKEY', Configuration::get('Ipay_HASHKEY')),
			'Ipay_LIVE' => Tools::getValue('LIVE_live', Configuration::get('Ipay_LIVE')),
			'Ipay_AUTOPAY' => Tools::getValue('Ipay_AUTOPAY', Configuration::get('Ipay_AUTOPAY')),
			'Ipay_LBK' => Tools::getValue('Ipay_LBK', Configuration::get('Ipay_LBK')),
		);
	}
}
