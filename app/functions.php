<?php
use \Firebase\JWT\JWT;

function makeToken($size){
	$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	$string = '';
	$max = strlen($characters) - 1;
	for ($i = 0; $i < $size; $i++) {
		$string .= $characters[mt_rand(0, $max)];
	}
	return $string;
}

function rotnum($s, $n = 13) {
    static $letters = 'AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz';
    $n = (int)$n % 26;
    if (!$n) return $s;
    if ($n < 0) $n += 26;
    if ($n == 13) return str_rot13($s);
    $rep = substr($letters, $n * 2) . substr($letters, 0, $n * 2);
    return strtr($s, $letters, $rep);
}

function obsToken($string, $io){
	//$io should be "dirty" or "clean"
	$count=0;
	if ($io == "blur"){
		$clean = strtoupper($string);
		$count=0;
		$salt = makeToken(strlen($clean));
		$rotnum = (ord(substr($salt, 0, 1)));
		$cleanrot = rotnum($clean, $rotnum);
		$dirty="";
		while ($count < strlen($cleanrot)*2) {
			if ($count % 2 == 0) $dirty.= substr($salt, $count/2, 1);
			else $dirty.= substr($cleanrot, $count/2, 1);
			$count++;
		}
		$return = $dirty;
	}
	if ($io == "focus"){
		$rotnum = 26-(ord(substr($string, 0, 1)));
		$clean = $dirty = "";
		while ($count < strlen($string)) {
			if ($count % 2 == 0) $dirty.= substr($string, $count, 1);
			else $clean.= substr($string, $count, 1);
			$count++;
		}
		$return = rotnum($clean, $rotnum);
	}
	return $return;
}

function newUser($conn,$user,$pass,$ip){
	$token = makeToken(25);
	$insert = "INSERT INTO `users` (`token`, `display`, `pass`, `cash`, `research`, `IP`)
	VALUES ('$token', '$user', '$pass', 100, 1, '$ip')";
	$result = $conn->prepare($insert);
	$result->execute();

	$select = "SELECT * FROM `users` WHERE `token` = '$token'";
	$result = $conn->prepare($select);
	$result->execute();
	$valid = getRow($result);
	$id = $valid['id'];
	$researchDT = date('Y-m-d H:i:s', strtotime('now + 5 minutes'));
	$insert = "INSERT INTO `points` (`user_id`, `available`, `type`)
	VALUES ($id, '$researchDT', 'research')";
	$result = $conn->prepare($insert);
	$result->execute();

	setLogin($conn,$user,$id,$token,$ip);
}

function notify($conn,$user,$text){
	$insert = "INSERT INTO `notifications` (`player`, `text`)
	VALUES ('$user', '$text')";
	$result = $conn->prepare($insert);
	$result->execute();
}

function notificationCount($conn, $player){
	$sql = "SELECT * FROM `notifications` WHERE `player` = '$player' AND `seen` = FALSE";
	$result = $conn->prepare($sql);
	$result->bindParam(':user', $user, PDO::PARAM_STR);
	$result->execute();
	$notifications = getRows($result);
	if ($notifications != false){
		$notifications['count'] = count($notifications);
	}
	return $notifications;
}

function getRow($result){
	if (!$result) return false;
	if ($result) if ($result->rowCount() > 0) {
		$row = $result->fetch(PDO::FETCH_ASSOC);
		return $row;
	}
	else return false;
}

function getRows($result){
	if (!$result) return false;
	if ($result) if ($result->rowCount() > 0) {
		return $result->fetchAll();
	}
	else return false;
}

function getToken($conn,$user){
	$sql = "SELECT `token` FROM `users` WHERE `display` = :user";
	$result = $conn->prepare($sql);
	$result->bindParam(':user', $user, PDO::PARAM_STR);
	$result->execute();
	$row = getRow($result);
	return $row['token'];
}

function getRank($result, $name){
	if ($result) if ($result->rowCount() > 0) {
		$count=0;
		$last=0;
		while($row = getRow($result)) {
			if ($last != $row["score"]) $count++;
			$last = $row["score"];
			if ($name == $row['display']) return $count;
		}
	}
}

function calcWorth($count){
	if ($count == 0){
		return "500";
	}
	elseif ($count == 1){
		return "250";
	}
	elseif ($count == 2){
		return "100";
	}
	else return "50";
}

function setLogin($conn,$user,$id,$token,$ip){
	$cookie = jwtToken($user,$id,$token);
	setcookie("user",$cookie, time()+604800, "/");
	$_SESSION['user'] = $cookie;
	$update = "UPDATE `users` SET `ip` = :ip WHERE `display` = :user";
	$result = $conn->prepare($update);
	$result->bindParam(':ip', $ip, PDO::PARAM_STR);
	$result->bindParam(':user', $user, PDO::PARAM_STR);
	$result->execute();
}

function checkLogin(){
	$auth = false;
	if (isset($_SESSION['user'])) {
		if (jwtDecode($_SESSION['user'])) $auth = true;
	}
	else {
		if (isset($_COOKIE['user'])) {
			if (jwtDecode($_COOKIE['user'])) {
				$auth = true;
				$_SESSION['user'] = $_COOKIE['user'];
			}
			else {
				dieLogin();
			}
		}
	}
	return $auth;
}

function dieLogin(){
	setcookie("user","", time()-3600, "/");
	unset($_SESSION['user']);
}

function jwtToken($user,$id,$token){
	global $jwtKey;
	//build the headers
	$headers = ['alg'=>'HS256','typ'=>'JWT'];
	$headers_encoded = jwt_build(json_encode($headers));

	//build the payload
	$payload = ['user'=>$user,'id'=> $id,'token'=>$token];
	$payload_encoded = jwt_build(json_encode($payload));

	//build the signature
	$signature = hash_hmac('SHA256',"$headers_encoded.$payload_encoded",$jwtKey,true);
	$signature_encoded = jwt_build($signature);

	//build and return the token
	$token = "$headers_encoded.$payload_encoded.$signature_encoded";

	return $token;
}

function jwt_build($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function jwtDecode($token){
	global $jwtKey;
	try {
		$decoded = JWT::decode($token, $jwtKey, array('HS256'));
	} catch (Exception $e) {
	    echo $e->getMessage();
	    $decoded = false;
	}
	return $decoded;
}

function pwtodb($pw){
	$options = [
    'cost' => 10,
    //'salt' => mcrypt_create_iv(22, MCRYPT_DEV_URANDOM), //DEPRECATED!!!
	];
	return password_hash($pw, PASSWORD_BCRYPT, $options);
}

function pwverify($pw, $hash){
	if (password_verify($pw, $hash)) return true;
	else return false;
}

function str2db($string){
	return base64_encode($string);
}

function db2str($string){
	return base64_decode($string);
}

function id2Count($result, $id){
// should be used with SELECT id FROM packages WHERE hunt IN (SELECT hunt FROM packages WHERE id = 'INSERT ID HERE')
	if ($result->rowCount() > 0) {
		$count=0;
		while($row = getRow($result)) {
			$count++;
			if ($id != $row["id"]) continue;
			else break;
		}
	}
	return $count;
}

function time2str($ts) {
	if(!ctype_digit($ts)) {
		$ts = strtotime($ts);
	}
	$diff = time() - $ts;
	if($diff == 0) {
		return 'now';
	} elseif($diff > 0) {
		$day_diff = floor($diff / 86400);
		if($day_diff == 0) {
			if($diff < 60) return 'just now';
			if($diff < 120) return '1 minute ago';
			if($diff < 3600) return floor($diff / 60) . ' minutes ago';
			if($diff < 7200) return '1 hour ago';
			if($diff < 86400) return floor($diff / 3600) . ' hours ago';
		}
		if($day_diff == 1) { return 'Yesterday'; }
		if($day_diff < 7) { return $day_diff . ' days ago'; }
		if($day_diff < 31) { return ceil($day_diff / 7) . ' weeks ago'; }
		if($day_diff < 60) { return 'last month'; }
		return date('F Y', $ts);
	} else {
		$diff = abs($diff);
		$day_diff = floor($diff / 86400);
		if($day_diff == 0) {
			if($diff < 120) { return 'in a minute'; }
			if($diff < 3600) { return 'in ' . floor($diff / 60) . ' minutes'; }
			if($diff < 7200) { return 'in an hour'; }
			if($diff < 86400) { return 'in ' . floor($diff / 3600) . ' hours'; }
		}
		if($day_diff == 1) { return 'Tomorrow'; }
		if($day_diff < 4) { return date('l', $ts); }
		if($day_diff < 7 + (7 - date('w'))) { return 'next week'; }
		if(ceil($day_diff / 7) < 4) { return 'in ' . ceil($day_diff / 7) . ' weeks'; }
		if(date('n', $ts) == date('n') + 1) { return 'next month'; }
		return date('F Y', $ts);
	}
}
