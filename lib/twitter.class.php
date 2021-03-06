<?php
namespace Levu;

class Twitter extends \Codebird\Codebird {
	protected static $_instance = null;

	public static function getUserID() {
		return self::isLoggedIn() ? self::cachedCall('account_settings', array(), 'id_str') : null;
	}

	public static function getScreenName() {
		return self::isLoggedIn() ? self::cachedCall('account_settings', array(), 'screen_name') : null;
	}

	public static function login() {
		$cb = self::instance();

		if (isset($_SESSION['oauth_verify']) && !isset($_GET['oauth_verifier'])) {
			unset($_SESSION['oauth_token']);
			unset($_SESSION['oauth_token_secret']);
			unset($_SESSION['oauth_verify']);
		}
		if (! isset($_SESSION['oauth_token'])) {

				// get the request token
				$reply = $cb->oauth_requestToken([
						'oauth_callback' => 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
				]);

				// store the token
				$cb->setToken($reply->oauth_token, $reply->oauth_token_secret);
				$_SESSION['oauth_token'] = $reply->oauth_token;
				$_SESSION['oauth_token_secret'] = $reply->oauth_token_secret;
				$_SESSION['oauth_verify'] = true;

				// redirect to auth website
				$auth_url = $cb->oauth_authorize();
				header('Location: ' . $auth_url);
				die();

		} elseif (isset($_GET['oauth_verifier']) && isset($_SESSION['oauth_verify'])) {
				// verify the token
				$cb->setToken($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
				unset($_SESSION['oauth_verify']);

				// get the access token
				$reply = $cb->oauth_accessToken([
						'oauth_verifier' => $_GET['oauth_verifier']
				]);

				// store the token (which is different from the request token!)
				$_SESSION['oauth_token'] = $reply->oauth_token;
				$_SESSION['oauth_token_secret'] = $reply->oauth_token_secret;

				return [
					'token' => $reply->oauth_token,
					'secret' => $reply->oauth_token_secret	
				];
		}

		$cb->setToken($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

		Templating::redirect();
	}

	public static function isLoggedIn() {
		return isset($_SESSION['oauth_token']) && !isset($_SESSION['oauth_verify']);
	}

	public static function instance() {
		if (is_null(self::$_instance)) {
			\Codebird\Codebird::setConsumerKey(APP_KEY, APP_SECRET);
			self::$_instance = \Codebird\Codebird::getInstance();
			if (self::isLoggedIn()) {
				self::$_instance->setToken($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
			}
		}
		return self::$_instance;
	}

	public static function getOAuthToken() {
		if (self::isLoggedIn()) {
			return $_SESSION['oauth_token'];
		}
		return '';
	}

	public static function cachedCall($method, $parameters, $field = null, $lifetime = 3600) {
		$twitter = self::instance();
		$refresh = false;
		$fn = __DIR__ . '/../cache/' . md5(self::getOAuthToken() . $method . serialize($parameters));
		if (!file_exists($fn)) {
			$refresh = true;
		} else {
			if ((time() - filectime($fn)) > $lifetime) $refresh = true;
		}
		if ($refresh) {
			$data = $twitter->$method($parameters);
			if (isset($data->errors)) return null;
			file_put_contents($fn, json_encode($data));
		}
		$data = json_decode(file_get_contents($fn), true);
		if (!is_null($field)) {
			if (!isset($data[$field])) return null;
			$data = $data[$field];
		}
		return $data;
	}

}	
