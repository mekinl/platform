<?php
/**
 * 
 *
 **/
class StripeSeed extends SeedBase {
	// These need to be fixed based on the call from commerceplant
	protected $api_username, $api_password, $api_signature, $api_endpoint, $api_version, $paypal_base_url, $error_message, $token;
	protected $merchant_email = false;
	//Have to fix constructor based on what the charge method need
	public function __construct($user_id, $connection_id, $token=false) {
		$this->settings_type = 'com.mailchimp';
		error_log("********##Constructor called");
		$this->user_id = $user_id;
		$this->connection_id = $connection_id;
		if ($this->getCASHConnection()) {
			$this->api_version   = '94.0';
			$this->api_username  = $this->settings->getSetting('username');
			$this->api_password  = $this->settings->getSetting('password');
			$this->api_signature = $this->settings->getSetting('signature');

			if (!$this->api_username || !$this->api_password || !$this->api_signature) {
				$connections = CASHSystem::getSystemSettings('system_connections');
				if (isset($connections['com.paypal'])) {
					$this->merchant_email = $this->settings->getSetting('merchant_email'); // present in multi
					$this->api_username   = $connections['com.paypal']['username'];
					$this->api_password   = $connections['com.paypal']['password'];
					$this->api_signature  = $connections['com.paypal']['signature'];
				}
			}

			$this->token = $token;
			
			$this->api_endpoint = "https://api-3t.paypal.com/nvp";
			$this->paypal_base_url = "https://www.paypal.com/webscr&cmd=";
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
			 error_log($data['code']."*****)))");
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
			//foreach ($resp as &$value) {
			//	error_log($value);
			//}
			error_log("****************".$credentials['access']."-------");
				if (isset($credentials['refresh'])) {
					$user_info = StripeSeed::getUserInfo($credentials['access']);
					$new_connection = new CASHConnection(AdminHelper::getPersistentData('cash_effective_user'));
					$result = $new_connection->setSettings(
						$user_info['email'] . ' (Stripe)',
						'com.stripe',
						array(
							'access_token'   => $credentials['access']
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
			return $client->getTokens($authorization_code);
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
	
	
	
	
	
	

	

	

} // END class 
?>