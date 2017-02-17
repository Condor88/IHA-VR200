<?php
class Api {
	public static function request($url, $payload = array(), $method = "POST", $headers = array()) {
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

		if($method == "POST") {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		}

		$requestHeaders = array(
			'Accept: application/vnd.neato.nucleo.v1'
		);

		if(count($headers) > 0) {
			$requestHeaders = array_merge($requestHeaders, $headers);
		}

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);

		$result = curl_exec($ch);
		curl_close($ch);

		return json_decode($result, true);
	}
}
