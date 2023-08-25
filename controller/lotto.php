<?php
$sqldate = date("Y-m-d", time());
$errMsg = '';
$conn = new PDO("mysql:host=$servername;dbname=$database",$username,$password);

if (!$auth) header("Location: ".$home."login");
$detail = (array) jwtDecode($_SESSION['user']);
$sql = "SELECT * FROM `players` WHERE `display` = :user";
$result = $conn->prepare($sql);
$result->bindParam(':user', $detail['user'], PDO::PARAM_STR);
$result->execute();
$row = getRow($result);
if ($row['lotto'] == $sqldate){
	$errMsg = "You already played today. $nextMsg Good luck for next time!";
}

$link = $GLOBALS['home']."loot/LOTT/";
$winner = mt_rand(0, 4);
$out = "Click on a box to see if you won!<br><h1>";
for ($i = 0; $i < 5; $i++) {
	if ($i == $winner){
	}
}
$out.="</h1>";
$loader = new Twig_Loader_Filesystem('./view');
$twig = new Twig_Environment($loader, array(
    //'cache' => '../cache',
));
try {
echo $twig->render('lotto.html', array(
	'errMsg' => $errMsg,
	'boxes' => $out,
	'auth' => $auth,
	'home' => $home,
	'tracking' => $tracking,
	'notification' => $notification
	));
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}
