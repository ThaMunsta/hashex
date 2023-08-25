<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
date_default_timezone_set('America/Toronto');
$discordinvite = "https://discord.gg/CHANGE-ME";
$jwtKey = 'CHANGE ME';

switch (__DIR__) {
    case stristr(__DIR__, 'hashcash') !== false: /////////////// DEV
    	$servername = "localhost";
  		$username = "root";
  		$password = "";
  		$database = "hashcash";
  		//root directory with leading and trailing slash
  		$GLOBALS['home'] = "/hashcash/";
  		//list how deep the root is in uri
  		$subdirs=3;
  		$env = "dev";
  		$tracking = 'tracking.html';
      //MAIL SETTINGS
      if (!isset($nomail)){
        require_once('vendor/phpmailer/phpmailer/src/Exception.php');
        require_once('vendor/phpmailer/phpmailer/src/PHPMailer.php');
        require_once('vendor/phpmailer/phpmailer/src/SMTP.php');
        $mail = new PHPMailer(true);                          // Passing `true` enables exceptions
        //Server settings
        //$mail->SMTPDebug = 2;                                 // Enable verbose debug output
        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = 'smtp.sendgrid.net';                            // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                              // Enable SMTP authentication
        $mail->Username = 'apikey';                 // SMTP username
        $mail->Password = 'CHANGE ME';                           // SMTP password
        $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 465;                                   // TCP port to connect to
      }
    	break;
    case stristr(__DIR__, '/home/mike') !== false: /////////////// DEV LINUX
    	$servername = "localhost";
  		$username = "root";
  		$password = "";
  		$database = "loot";
  		//root directory with leading and trailing slash
  		$GLOBALS['home'] = "/";
  		//list how deep the root is in uri
  		$subdirs=0;
  		$env = "dev";
  		$tracking = 'tracking.html';

      //MAIL SETTINGS
      if (isset($nomail)) if (!$nomail){
        require_once('vendor/phpmailer/phpmailer/src/Exception.php');
        require_once('vendor/phpmailer/phpmailer/src/PHPMailer.php');
        require_once('vendor/phpmailer/phpmailer/src/SMTP.php');
        $mail = new PHPMailer(true);                          // Passing `true` enables exceptions
        //Server settings
        //$mail->SMTPDebug = 2;                                 // Enable verbose debug output
        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = 'localhost';                            // Specify main and backup SMTP servers
        $mail->SMTPAuth = false;                              // Enable SMTP authentication
        $mail->Port = 2500;                                   // TCP port to connect to
      }
    	break;
    case stripos(__DIR__, 'staging') !== false: ///////////// STAGING
    	$servername = "localhost";
  		$username = "root";
  		$password = "";
  		$database = "loot";
  		//root directory with leading and trailing slash
  		$GLOBALS['home'] = "/staging/";
  		//list how deep the root is in uri
  		$subdirs=1;
  		$env = "staging";
  		$tracking = 'tracking.html';


      //MAIL SETTINGS
      require 'vendor/phpmailer/phpmailer/src/Exception.php';
      require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
      require 'vendor/phpmailer/phpmailer/src/SMTP.php';
      $mail = new PHPMailer(true);                          // Passing `true` enables exceptions
      //Server settings
      $mail->SMTPDebug = 2;                                 // Enable verbose debug output
      $mail->isSMTP();                                      // Set mailer to use SMTP
      $mail->Host = 'localhost';                            // Specify main and backup SMTP servers
      $mail->SMTPAuth = false;                              // Enable SMTP authentication
      $mail->Port = 2500;                                   // TCP port to connect to
      $mail->Username = 'user@example.com';                 // SMTP username
      $mail->Password = 'secret';                           // SMTP password
      $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted

    	break;
    default: //////////////////////////////////////////////// PRODUCTION
  		$servername = "localhost";
  		$username = "root";
  		$password = "";
  		$database = "loot";
  		//root directory with leading and trailing slash
  		$GLOBALS['home'] = "/";
  		//list how deep the root is in uri
  		$subdirs=0;
  		$env = "prod";
  		$tracking = 'tracking.html';
      if (!isset($nomail)){
        require_once('vendor/phpmailer/phpmailer/src/Exception.php');
        require_once('vendor/phpmailer/phpmailer/src/PHPMailer.php');
        require_once('vendor/phpmailer/phpmailer/src/SMTP.php');
        $mail = new PHPMailer(true);                          // Passing `true` enables exceptions
        //Server settings
        //$mail->SMTPDebug = 2;                                 // Enable verbose debug output
        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = 'smtp.sendgrid.net';                            // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                              // Enable SMTP authentication
        $mail->Username = 'apikey';                 // SMTP username
        $mail->Password = 'CHANGE ME';                           // SMTP password
        $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 465;                                   // TCP port to connect to
      }$servername = "localhost";
  		$username = "root";
  		$password = "";
  		$database = "hashcash";
  		//root directory with leading and trailing slash
  		$GLOBALS['home'] = "/";
  		//list how deep the root is in uri
  		$subdirs=3;
  		$env = "dev";
  		$tracking = 'tracking.html';
      //MAIL SETTINGS
      if (!isset($nomail)){
        require_once('vendor/phpmailer/phpmailer/src/Exception.php');
        require_once('vendor/phpmailer/phpmailer/src/PHPMailer.php');
        require_once('vendor/phpmailer/phpmailer/src/SMTP.php');
        $mail = new PHPMailer(true);                          // Passing `true` enables exceptions
        //Server settings
        //$mail->SMTPDebug = 2;                                 // Enable verbose debug output
        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = 'smtp.sendgrid.net';                            // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                              // Enable SMTP authentication
        $mail->Username = 'apikey';                 // SMTP username
        $mail->Password = 'CHANGE ME';                           // SMTP password
        $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 465;                                   // TCP port to connect to
      }
    	break;
}
