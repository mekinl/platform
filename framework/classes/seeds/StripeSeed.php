<?php
/**
 * 
 *
 **/
class StripeSeed extends SeedBase {
	protected $error_message, $token, $client_id, $redirect_uri, $client_secret,$publishable_key, $access_token;
	
	public function __construct($user_id, $connection_id, $token=false) {
		$this->settings_type = 'com.mailchimp';
		$this->user_id = $user_id;
		$this->connection_id = $connection_id;
		if ($this->getCASHConnection()) {
			$this->client_id  = $this->settings->getSetting('client_id');
			$this->redirect_uri  = $this->settings->getSetting('redirect_uri');
			$this->client_secret = $this->settings->getSetting('client_secret');
			$this->publishable_key = $this->settings->getSetting('publishable_key');
			$this->access_token = $this->settings->getSetting('access_token');
			if (!$this->client_id || !$this->redirect_uri || !$this->client_secret) {
				$connections = CASHSystem::getSystemSettings('system_connections');
				if (isset($connections['com.stripe'])) {
					$this->client_id   = $connections['com.stripe']['client_id'];
					$this->redirect_uri   = $connections['com.stripe']['redirect_uri'];
					$this->client_secret  = $connections['com.stripe']['client_secret'];
				}
			}
	
			$this->token = $token;
			
		} else {
			$this->error_message = 'could not get connection settings';
		}
	}
	

	public static function getRedirectMarkup($data=false) {
		$connections = CASHSystem::getSystemSettings('system_connections');
		if (isset($connections['com.stripe'])) {
			//while (list($key, $value) = each($connections['com.stripe'])) {
			// error_log("Key: $key; Value: $value");
			//}
			//$login_url = "https://connect.stripe.com/oauth/authorize?response_type=code&client_id=ca_5eCOhyxL07uaKmLYp44UPuAWzrPx1CKi";
			$login_url = StripeSeed::getAuthorizationUrl($connections['com.stripe']['client_id'],$connections['com.stripe']['client_secret']);
			$return_markup = '<h4>Stripe</h4>'
						   . '<p>This will redirect you to a secure login at Stripe and bring you right back.</p>'
						   . '<a href="' . $login_url . '" class="button">Connect your Stripe</a>';
			return $return_markup;
		} else {
			return 'Please add default stripe api credentials.';
		}
	}
	public static function handleRedirectReturn($data=false) {
		if (isset($data['code'])) {
			$connections = CASHSystem::getSystemSettings('system_connections');
			/*
			foreach ($data as &$value) {
				error_log($value);
			}
			*/
			// will be moved to method or new classes
			if (isset($connections['com.stripe'])) {
			 //error_log($data['code']."*****)))");
			/* 
			$token_request_body = array(
				'grant_type' => 'authorization_code',
				'client_id' => 'ca_5eCOhyxL07uaKmLYp44UPuAWzrPx1CKi',
				'code' => $data['code'],
				'client_secret' => 'sk_test_Q8qTx3blDe9wIfORkxLFIAHb'
			);

			 $req = curl_init('https://connect.stripe.com/oauth/token');
			 curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
			 curl_setopt($req, CURLOPT_POST, true );
			 curl_setopt($req, CURLOPT_POSTFIELDS, http_build_query($token_request_body));
									
			// TODO: Additional error handling
			  $respCode = curl_getinfo($req, CURLINFO_HTTP_CODE);
			  $resp = json_decode(curl_exec($req), true);
			  curl_close($req);
			*/
			$credentials = StripeSeed::exchangeCode($data['code'],
								$connections['com.stripe']['client_id'],
								$connections['com.stripe']['client_secret']);
			//error_log("*******".$resp[access_token]);
			//foreach ($credentials as $value => $v) {
			//	error_log("\$a[$value] => $v.\n");
			//}
			error_log($credentials['userid']);
				if (isset($credentials['refresh'])) {
					$user_info = StripeSeed::getUserInfo($credentials['access']);
					$new_connection = new CASHConnection(AdminHelper::getPersistentData('cash_effective_user'));
					$result = $new_connection->setSettings(
						$user_info['email'] . ' (Stripe)',
						'com.stripe',
						array(
							'access_token'   => $credentials['access'],
							'publishable_key' => $credentials['publish'],
							'user_id' => $credentials['userid']
						)
					);
				if ($result) {
					AdminHelper::formSuccess('Success. Connection added. You\'ll see it in your list of connections.','/settings/connections/');
				} else {
					AdminHelper::formFailure('Error. Could not save connection.','/settings/connections/');
				}
				}else{
					return 'Could not find a refresh token from Stripe';
				}
			} else {
				return 'Please add default stripe app credentials.';
			}
		} else {
			return 'There was an error. (session) Please try again.';
		}
	}

	protected function setErrorMessage($msg) {
		$this->error_message = $msg;
	}
	
	public function getErrorMessage() {
		return $this->error_message;
	}
	
	public static function getUserInfo($credentials) {
		require_once(CASH_PLATFORM_ROOT.'/lib/stripe/Stripe.php');
		Stripe::setApiKey($credentials);
		$user_info = Stripe_Account::retrieve();
		//error_log("****USERINFO***".$user_info['email']);
		return $user_info;
	}
	
	/**
	 * Exchange an authorization code for OAuth 2.0 credentials.
	 *
	 * @param String $authorization_code Authorization code to exchange for OAuth 2.0 credentials.
	 * @return String Json representation of the OAuth 2.0 credentials.
	 */
	public static function exchangeCode($authorization_code,$client_id,$client_secret) {
		require_once(CASH_PLATFORM_ROOT.'/lib/stripe/StripeOAuth.class.php');
		try {
			$client = new StripeOAuth($client_id, $client_secret);
			$token =  $client->getTokens($authorization_code);
			$publishable = array(
					'publish' => $client->getPublishableKey(),
					'userid' => $client->getUserId()
					);
			return array_merge($token, $publishable);
		} catch (Exception $e) {
			return false;
		}
	}
	
	public static function getAuthorizationUrl($client_id,$client_secret) {
		require_once(CASH_PLATFORM_ROOT.'/lib/stripe/StripeOAuth.class.php');
		$client = new StripeOAuth($client_id, $client_secret);
		$auth_url = $client->getAuthorizeUri();
		return $auth_url;	
	}
	
	public function getTokenInformation() {
		if ($this->token) {
			require_once(CASH_PLATFORM_ROOT.'/lib/stripe/Stripe.php');
			Stripe::setApiKey($this->client_secret);
			$tokenInfo = Stripe_Token::retrieve($this->token);
			if (!$tokenInfo) {
				$this->setErrorMessage('getTokenInformation failed: ' . $this->getErrorMessage());
				return false;
			} else {
				return $tokenInfo;
			}
		} else {
			$this->setErrorMessage("No token was found.");
			return false;
		}
	}
	


	
	public function doCharge($amount, $email) {
		if ($this->token) {
				require_once(CASH_PLATFORM_ROOT.'/lib/stripe/Stripe.php');
				Stripe::setApiKey($this->client_secret);			
				$charge = Stripe_Charge::create(array(
				  "amount" => $amount*100,
				  "currency" => "usd",
				  "source" => $this->token, // obtained with Stripe.js
				  "description" => "Charge for ".$email
				));
				return $charge;
						
		} else {
			$this->setErrorMessage("No token was found.");
			return false;
		}
	}
	
	
	public function setExpressCheckout(
		$payment_amount,
		$ordersku,
		$ordername,
		$return_url,
		$cancel_url,
		$request_shipping_info=true,
		$allow_note=false,
		$currency_id='USD', /* 'USD', 'GBP', 'EUR', 'JPY', 'CAD', 'AUD' */
		$payment_type='Sale', /* 'Sale', 'Order', or 'Authorization' */
		$invoice=false
	) {
		error_log("SetExpressCheckout Stripe");
		// Set NVP variables:
		$nvp_parameters = array(
			'PAYMENTREQUEST_0_AMT' => $payment_amount,
			'PAYMENTREQUEST_0_PAYMENTACTION' => $payment_type,
			'PAYMENTREQUEST_0_CURRENCYCODE' => $currency_id,
			'PAYMENTREQUEST_0_ALLOWEDPAYMENTMETHOD' => 'InstantPaymentOnly',
			'PAYMENTREQUEST_0_DESC' => $ordername,
			'RETURNURL' => $return_url,
			'CANCELURL' => $cancel_url,
			'L_PAYMENTREQUEST_0_AMT0' => $payment_amount,
			'L_PAYMENTREQUEST_0_NUMBER0' => $ordersku,
			'L_PAYMENTREQUEST_0_NAME0' => $ordername,
			'NOSHIPPING' => '0',
			'ALLOWNOTE' => '0',
			'SOLUTIONTYPE' => 'Sole',
			'LANDINGPAGE' => 'Billing'
		);
		if (!$request_shipping_info) {
			$nvp_parameters['NOSHIPPING'] = 1;
		}
		if ($allow_note) {
			$nvp_parameters['ALLOWNOTE'] = 1;
		}
		if ($invoice) {
			$nvp_parameters['PAYMENTREQUEST_0_INVNUM'] = $invoice;
		}
		
		$this->cash_base_url = CASH_PUBLIC_URL."/stripe.php";
		$pk=$this->publishable_key;
		$desc=$ordername;
		$amnt=$payment_amount*100;
		$secretParams = "desc=$desc&pk=$pk&amnt=$amnt";
		
		$encryptedSecret = Stripeseed::cryptoJsAesEncrypt("cashmusic", $secretParams);
		$stripe_url = $this->cash_base_url . "?return_url=$return_url&cancel_url=$cancel_url&param=".base64_encode($encryptedSecret);
		return $stripe_url;
	}
/*
 * copy from here
http://stackoverflow.com/questions/24337317/encrypt-with-php-decrypt-with-javascript-cryptojs	
*/
	function cryptoJsAesEncrypt($passphrase, $value){
	    $salt = openssl_random_pseudo_bytes(8);
	    $salted = '';
	    $dx = '';
	    while (strlen($salted) < 48) {
		$dx = md5($dx.$passphrase.$salt, true);
		$salted .= $dx;
	    }
	    $key = substr($salted, 0, 32);
	    $iv  = substr($salted, 32,16);
	    $encrypted_data = openssl_encrypt(json_encode($value), 'aes-256-cbc', $key, true, $iv);
	    $data = array("ct" => base64_encode($encrypted_data), "iv" => bin2hex($iv), "s" => bin2hex($salt));
	    return json_encode($data);
	}	

	

} // END class 
?>