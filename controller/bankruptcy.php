<?php

$loader = new Twig_Loader_Filesystem('./view');
$twig = new Twig_Environment($loader, array(
    //'cache' => '../cache',
));
$template = 'bankruptcy.html';
$pagename = 'Bankruptcy';

if(!empty($_POST)){
	$template = 'bankruptcy.error.html';
	$pagename = 'Bankruptcy - Error';
	if(isset($_POST['idiot'])){
		$template = 'bankruptcy.idiot.html';
		$pagename = 'Bankruptcy - Seriously?';
	}
	if(isset($_POST['confirm'])){
		if($_POST['confirm'] == 'RESETALL'){

			$conn = new PDO("mysql:host=$servername;dbname=$database",$username,$password);
			$userDetail = (array) jwtDecode($_SESSION['user']);
			$id = $userDetail['id'];

			//DELETE
			$sql = 'DELETE FROM `WIP` WHERE `point_id` IN (SELECT `id` FROM `points` WHERE `user_id` = \''.$userDetail['id'].'\')';
		  getRow($conn->query($sql));
			$sql = 'DELETE FROM `points` WHERE `user_id` = \''.$id.'\'';
		  getRow($conn->query($sql));
			$sql = 'DELETE FROM `trades` WHERE `user_id` = \''.$id.'\'';
		  getRow($conn->query($sql));
			$sql = 'DELETE FROM `activity` WHERE `user_id` = \''.$id.'\'';
		  getRow($conn->query($sql));

			//REBUILD
			$researchDT = date('Y-m-d H:i:s', strtotime('now + 5 minutes'));
			$insert = "INSERT INTO `points` (`user_id`, `available`, `type`)
			VALUES ($id, '$researchDT', 'research')";
			$result = $conn->prepare($insert);
			$result->execute();
			$sql = 'UPDATE `users` SET `cash` = 100 WHERE `id` = \''.$id.'\'';
			getRow($conn->query($sql));

			$template = 'bankruptcy.done.html';
			$pagename = 'Bankruptcy - It Is Done';
		}
	}
}
try {
echo $twig->render($template, array(
	'auth' => $auth,
	'home' => $home,
	'pagename' => $pagename,
	'tracking' => $tracking,
	'notification' => $notification
	));
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}
