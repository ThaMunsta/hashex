<?php
$ref=$errMsg='';
$conn = new PDO("mysql:host=$servername;dbname=$database",$username,$password);

// $secure = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
// if (!isset($_SERVER['HTTPS'])) header('Location: '.$secure);

if (isset($_POST['login'])){
	if(preg_match('/[^a-z_\-0-9]/i', $_POST['user'])){
		$errMsg = "Invalid Username";
	}
	elseif ($_POST['user'] == "" || $_POST['passwd'] == ""){
		(!$errMsg) ? $errMsg = "Please fill in all fields": $errMsg;
	}
	else {
		$user = $_POST['user'];
		$pass = pwtodb($_POST['passwd']);
		$sql = "SELECT * FROM `users` WHERE `display` = :user";
		$result = $conn->prepare($sql);
		$result->bindParam(':user', $user, PDO::PARAM_STR);
		$result->execute();
		$valid = getRow($result);
		if ($valid == false) newUser($conn,$user,$pass,$ip);
		else {
			if (pwverify($_POST['passwd'],$valid['pass']) == true) {
				$user = $valid['display'];
				$id = $valid['id'];
				$token = getToken($conn,$user);
				setLogin($conn,$user,$id,$token,$ip);
			}
		}
	}
	if (!checkLogin()) (!$errMsg) ? $errMsg = "Incorrect login information": $errMsg;
}
$auth = checkLogin();
if ($auth){
	$detail = (array) jwtDecode($_SESSION['user']);
	$name = $detail['user'];
	//if (isset($_SESSION['ref'])) header("Refresh:5; url=$home".$_SESSION['ref']." true, 303");
	if (isset($_SESSION['ref'])) {
		$ref = $_SESSION['ref'];
		$_SESSION['ref'] = null;
	}
}
else $name = '';
$loader = new Twig_Loader_Filesystem('./view');
$twig = new Twig_Environment($loader, array(
    //'cache' => '../cache',
));
$notification = notificationCount($conn, $name);
try {
echo $twig->render('login.html', array(
	'auth' => $auth,
	'username' => $name,
	'errMsg' => $errMsg,
	'home' => $home,
	'pagename' => 'Login',
	'tracking' => $tracking,
	'ref' => $ref,
	'notification' => $notification
	));
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}
