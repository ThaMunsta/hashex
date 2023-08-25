<?php
session_start();
require_once(__DIR__.'/functions.php');
require_once(__DIR__.'/conf.php');

if (empty($_SERVER['HTTP_CLIENT_IP']) === false) {   //check ip from share internet
	$ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (empty($_SERVER['HTTP_X_FORWARDED_FOR']) === false) {  //to check ip is pass from proxy
	$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
	$ip = $_SERVER['REMOTE_ADDR'];
}