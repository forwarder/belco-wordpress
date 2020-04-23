<?php
class Belco_API {
	public static function post($path, $data, $options = array()) {
		if (!empty($options['secret'])) {
			$secret = $options['secret'];
		} else {
			$secret = get_option('belco_secret');
		}

		if (!empty($options['shopId'])) {
			$shopId = $options['shopId'];
		} else {
			$shopId = get_option('belco_shop_id');
		}

		$protocol = BELCO_USE_SSL ? 'https://' : 'http://';

		$json = json_encode($data);

		$signature = hash_hmac('sha256', $json, $secret);

		$blocking = $options['blocking'];

		$response = wp_remote_post($protocol . BELCO_API_HOST . $path, array(
			'method' => 'POST',
			'body' => $json,
			'headers' => array(
				'Content-Type' => 'application/json',
				'X-Signature' => $signature,
				'X-Shop-Id' => $shopId
			),
			'blocking' => $blocking ? true : !WP_DEBUG,
			'timeout' => 5
		));

		if (!$blocking && !WP_DEBUG) {
			return true;
		}

		if ( is_wp_error( $response ) ) {
			error_log('Belco Error: ' + $response->get_error_message());
			return $response->get_error_message();
		}

		$body = json_decode($response['body']);

		if ($body->success === false) {
			error_log('Belco Error: ' + $body->message);
			return $body->message;
		}

		return true;
	}
}
?>
