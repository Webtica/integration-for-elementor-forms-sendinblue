<?php

/**
 * Plugin Name: Sendinblue Elementor integration
 * Description: Connect and send data to sendinblue from submitted elementor forms
 * Author: Webtica
 * Author URI: https://webtica.be/
 * Version: 1.0.0
 */


//Get secrut url key
if(isset($_GET['key'])) {
	$urlsecret = $_GET['key'];
} else {
      exit;
}

if (empty($urlsecret)) {
    exit;
}

$key = "HEku8w5TDXKcfg6f96mkCp7X5wNvhtbycGHSC2Q3";
$inputdata    = file_get_contents('php://input');
$remoteobject = json_decode($inputdata);

if ($urlsecret == $key) {
	
	//get data from object
	$naam = $remoteobject->naam;
	$email = $remoteobject->email;

	//Sendinblue data
	$apikey = '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';

	//Send data to Sendinblue
	$curl = curl_init();

	curl_setopt_array($curl, [
	  CURLOPT_URL => "https://api.sendinblue.com/v3/contacts",
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_POSTFIELDS => "{\"attributes\":{\"FIRSTNAME\":\"".$naam."\"},\"listIds\":[4],\"updateEnabled\":true,\"email\":\"".$email."\"}",
	  CURLOPT_HTTPHEADER => [
	    "Accept: application/json",
	    "Content-Type: application/json",
	    "api-key:" . $apikey
	  ],
	]);

	$response = curl_exec($curl);
	$err = curl_error($curl);
	curl_close($curl);
}
else {
	exit;
}