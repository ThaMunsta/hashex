<?php

$loader = new Twig_Loader_Filesystem('./view');
$twig = new Twig_Environment($loader, array(
    //'cache' => '../cache',
));
$conn = new PDO("mysql:host=$servername;dbname=$database",$username,$password);
$userDetail = (array) jwtDecode($_SESSION['user']);
$sql = "SELECT * FROM `users` WHERE id = ".$userDetail['id'];
$fullUser = getRow($conn->query($sql));
$sql = "SELECT hash.name, SUM(`volume`) AS total_volume, ROUND(AVG(`cost`),2) AS avg_paid, hash.value as current_value FROM `trades` INNER JOIN `hash` on hash.id = trades.hash_id WHERE `user_id` = ".$userDetail['id']." AND trades.status = 'held' AND trades.type = 'long' GROUP BY `hash_id` ORDER BY `current_value` DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([$sql]);
$long = $stmt->fetchAll();
$sql = "SELECT hash.name, SUM(`volume`) AS total_volume, ROUND(AVG(`cost`),2) AS avg_paid, hash.value as current_value FROM `trades` INNER JOIN `hash` on hash.id = trades.hash_id WHERE `user_id` = ".$userDetail['id']." AND trades.status = 'held' AND trades.type = 'short' GROUP BY `hash_id` ORDER BY `current_value`";
$stmt = $conn->prepare($sql);
$stmt->execute([$sql]);
$short = $stmt->fetchAll();
$worth = $fullUser['cash'];
$invested = 0;
foreach ($long as $key => $investment) {
	$worth = $worth + ($investment['current_value'] * $investment['total_volume']);
	$invested = $invested + ($investment['avg_paid'] * $investment['total_volume']);
	$long[$key]['gainloss'] = ($investment['current_value'] * $investment['total_volume']) - ($investment['avg_paid'] * $investment['total_volume']);
}
foreach ($short as $key => $investment) {
	$worth = $worth + (($investment['avg_paid'] - $investment['current_value']) * $investment['total_volume']);
	$worth = $worth + ($investment['avg_paid'] * $investment['total_volume']);
	$invested = $invested + ($investment['avg_paid'] * $investment['total_volume']);
	$short[$key]['gainloss'] = (($investment['avg_paid'] - $investment['current_value']) * $investment['total_volume']);
}
$fullUser['worth'] = $worth;
$fullUser['total_invested'] = $invested;

$sql = 'SELECT * FROM `trades` INNER JOIN `hash` ON trades.hash_id = hash.id WHERE `user_id` = '.$userDetail['id'].' AND trades.status = \'pending\'';
$pending = getRows($conn->query($sql));

$select = "SELECT * FROM `activity` WHERE user_id = ".$fullUser['id']." ORDER BY id DESC LIMIT 1440";
$rows = getRows($conn->query($select));

$dataPoints = array();
if($rows){
  $rows = array_reverse($rows);
  for($i = 0; $i < count($rows); $i++){
  	array_push($dataPoints, array("x" => strtotime($rows[$i]['activity_date'])."000", "y" => $rows[$i]['value']));
  }

  $dataPoints = json_encode($dataPoints, JSON_NUMERIC_CHECK);
}


try {
echo $twig->render('portfolio.html', array(
	'user' => $fullUser,
	'username' => $userDetail['user'],
	'long' => $long,
	'short' => $short,
	'pending' => $pending,
	'dataPoints' => $dataPoints,
	'auth' => $auth,
	'home' => $home,
	'pagename' => 'Hashtag Investment Portfolio',
	'tracking' => $tracking,
	'notification' => $notification
	));
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}
