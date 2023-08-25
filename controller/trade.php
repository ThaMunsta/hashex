<?php
$loader = new Twig_Loader_Filesystem('./view');
$twig = new Twig_Environment($loader, array(
	//'cache' => '../cache',
));
$filter = new Twig_Filter('time2str', function ($string) {
	return time2str($string);
});
$twig->addFilter($filter);
// $filter = new Twig_Filter('db2str', function ($string) {
// 	return db2str($string);
// });
// $twig->addFilter($filter);
// $filter = new Twig_Filter('d2h', function ($string) {
// 	return dechex($string);
// });
// $twig->addFilter($filter);
$joyMsg = $notiMsg = $errMsg = '';

$bits = explode ("/", $_SERVER['REQUEST_URI']);
$depth = sizeof($bits)-$subdirs;
$lookup = $bits[(sizeof($bits)-2)];
if ($depth > 4) $hunt = $bits[(sizeof($bits)-3)];
if ($depth > 5) {
	try {
	echo $twig->render('404.html', array(
		'auth' => $auth,
		'home' => $home,
		'tracking' => $tracking,
		'notification' => $notification
		));
	} catch (Exception $e) {
		echo $e->getMessage();
		exit(1);
	}
	die;
}
$sqldate = date("Y-m-d", time());
$conn = new PDO("mysql:host=$servername;dbname=$database",$username,$password);

// LIST CURRENT TRADES
if ($lookup == 'trade'){
	$sql = "SELECT * FROM `hash` WHERE `status` = 'active' ORDER BY RAND() LIMIT 100";
	$result = $conn->prepare($sql);
	$result->bindParam(':sqldate', $sqldate, PDO::PARAM_STR);
	$result->execute();
	$out = [];
	if ($result) if ($result->rowCount() > 0) {
		while($row = $result->fetch(PDO::FETCH_ASSOC)) {
			if ($row['name'] != "SURPRESS") $out[] = $row;
		}
	}
	else {
		$out = 0;
	}
	try {
	echo $twig->render('trade.html', array(
		'rows' => $out,
		'auth' => $auth,
		'home' => $home,
		'pagename' => 'Publicly Traded Hashes',
		'tracking' => $tracking,
		'notification' => $notification
		));
	} catch (Exception $e) {
		echo $e->getMessage();
		exit(1);
	}
}

// TRADE LIST
if ($lookup != "trade" && !isset($hunt)) {
	if ($auth){
		$userDetail = (array) jwtDecode($_SESSION['user']);
	}
	else {
		 $userDetail['id'] = 0;
	}

	$sql = "SELECT * FROM `hash` WHERE `names` = ?";
	$stmt = $conn->prepare($sql);
  $stmt->execute([$lookup]);
  $result = $stmt->fetchAll();
	$list = [];
	$count=0;
	if ($result) if (count($result) > 0) {
		foreach($result as $row) {
			$count++;
			$row['pos'] = $count;
			$list[] = $row;
		}
	}
	else {
		$list = 0;
	}
  $sql = "SELECT * FROM `hash` WHERE `name` = '".$lookup."'";
  $hashDetail = getRow($conn->query($sql));
  $template = 'trade.details.html';
  if ($hashDetail['status'] != 'active'){
		$sql = "SELECT * FROM `wip` WHERE `hash` = '".$lookup."' AND `listed` IS NULL";
		$wip = getRow($conn->query($sql));
		if ($wip == false){
		  $template = 'trade.inactive.html';
			$long = $short = '';
		}
		else{
		  $template = 'trade.pending.html';
		  $hashDetail['active'] = $wip['active'];
			$long = $short = '';
			$hashDetail['id'] = '';
		}
  }
  else{
		if($hashDetail['name'] !== $lookup){
			header('Location: '.$home.'trade/'.$hashDetail['name']);
		}
  	$sql = "SELECT SUM(`volume`) AS total_volume, ROUND(AVG(`cost`),2) AS avg_paid FROM `trades` WHERE `user_id` = ".$userDetail['id']." AND `hash_id` = ".$hashDetail['id']." AND `type` = 'long' AND `status` = 'held'";
	$long = getRow($conn->query($sql));
  	$sql = "SELECT SUM(`volume`) AS total_volume, ROUND(AVG(`cost`),2) AS avg_paid FROM `trades` WHERE `user_id` = ".$userDetail['id']." AND `hash_id` = ".$hashDetail['id']." AND `type` = 'short' AND `status` = 'held'";
  	$short = getRow($conn->query($sql));

  	$select = 'SELECT MIN(VALUE) AS value FROM `activity` WHERE hash_id = '.$hashDetail['id'].' and activity_date > DATE_SUB(NOW(), INTERVAL 1 HOUR)';
	$min['hour'] = getRow($conn->query($select));
	 $select = 'SELECT MIN(VALUE) AS value FROM `activity` WHERE hash_id = '.$hashDetail['id'].' and activity_date > DATE_SUB(NOW(), INTERVAL 1 DAY)';
	$min['day'] = getRow($conn->query($select));
	 $select = 'SELECT MIN(VALUE) AS value FROM `activity` WHERE hash_id = '.$hashDetail['id'].' and activity_date > DATE_SUB(NOW(), INTERVAL 7 DAY)';
	$min['week'] = getRow($conn->query($select));

  	$select = 'SELECT ROUND(AVG(VALUE),2) AS value FROM `activity` WHERE hash_id = '.$hashDetail['id'].' and activity_date > DATE_SUB(NOW(), INTERVAL 1 HOUR)';
	$avg['hour'] = getRow($conn->query($select));
	 $select = 'SELECT ROUND(AVG(VALUE),2) AS value FROM `activity` WHERE hash_id = '.$hashDetail['id'].' and activity_date > DATE_SUB(NOW(), INTERVAL 1 DAY)';
	$avg['day'] = getRow($conn->query($select));
	 $select = 'SELECT ROUND(AVG(VALUE),2) AS value FROM `activity` WHERE hash_id = '.$hashDetail['id'].' and activity_date > DATE_SUB(NOW(), INTERVAL 7 DAY)';
	$avg['week'] = getRow($conn->query($select));

	$select = 'SELECT MAX(VALUE) AS value FROM `activity` WHERE hash_id = '.$hashDetail['id'].' and activity_date > DATE_SUB(NOW(), INTERVAL 1 HOUR)';
	$max['hour'] = getRow($conn->query($select));
	 $select = 'SELECT MAX(VALUE) AS value FROM `activity` WHERE hash_id = '.$hashDetail['id'].' and activity_date > DATE_SUB(NOW(), INTERVAL 1 DAY)';
	$max['day'] = getRow($conn->query($select));
	 $select = 'SELECT MAX(VALUE) AS value FROM `activity` WHERE hash_id = '.$hashDetail['id'].' and activity_date > DATE_SUB(NOW(), INTERVAL 7 DAY)';
	$max['week'] = getRow($conn->query($select));
  }

  if(!empty($_POST)){
	if ($_POST['action'] == 'Use Research Point'){
	  $sql = 'SELECT * FROM `points` WHERE `user_id` = \''.$userDetail['id'].'\' and `available` < \''.date("Y-m-d H:i:s").'\' and `type` = \'research\' and `redeemed` IS NULL';
	  $points = getRow($conn->query($sql));
	  if($points){
		$sql = "UPDATE `points` SET `redeemed` = '".date("Y-m-d H:i:s")."' WHERE `id` = ".$points['id'];
		getRow($conn->query($sql));
		$sql = "INSERT INTO `points` (`user_id`, `available`, `type`)
		  VALUES (:user_id,:available, 'research')";
		$result = $conn->prepare($sql);
		$result->bindParam(':user_id', $userDetail['id']);
		$availableDT = date("Y-m-d H:i:s", strtotime("+6 hour"));
		$result->bindParam(':available', $availableDT);
		$result->execute();
		$sql = "INSERT INTO `wip`(`hash`, `active`, `point_id`)
		  VALUES (:hash,:available,:point_id)";
		$result = $conn->prepare($sql);
		$result->bindParam(':hash', $lookup);
		$availableDT = date("Y-m-d H:i:s", strtotime("+10 minutes"));
		$result->bindParam(':available', $availableDT);
		$result->bindParam(':point_id', $points['id']);
		$result->execute();
		header("Refresh:0");
		die();
	  }
		else{
			header('Location: '.$home.'research/');
			die();
		}
	}
	  $datetime = date('Y-m-d H:i:s');
	  $sql = "INSERT INTO `trades`(`user_id`, `hash_id`, `cost`, `volume`, `original_volume`, `type`, `status`, `trade_date`)
		VALUES (:user,:hash,:cost,:volume, :original_volume,:type,'pending', :date)";
	  $result = $conn->prepare($sql);
	  $result->bindParam(':user', $userDetail['id']);
	  $result->bindParam(':hash', $hashDetail['id']);
	  $result->bindParam(':cost', $hashDetail['value']);
	  $result->bindParam(':volume', $_POST['shares']);
	  $result->bindParam(':original_volume', $_POST['shares']);
	  if ($_POST['action'] == 'Buy'){
		$type = "long";
	  }
	  elseif ($_POST['action'] == 'Short'){
		$type = 'short';
	  }
	  elseif ($_POST['action'] == 'Sell'){
		$type = 'sell';
	  }
	  elseif ($_POST['action'] == 'Cover'){
		$type = 'cover';
	  }
	  $result->bindParam(':type', $type);
	  $result->bindParam(':date', $datetime);
	  $result->execute();
	  $joyMsg = "Order placed with a broker but may take a moment to process";
		header('Location: '.$home.'portfolio/');
  }

	$select = "SELECT * FROM `activity` WHERE hash_id = ".$hashDetail['id']." ORDER BY id DESC LIMIT 672";
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
	echo $twig->render($template, array(
		'list' => $list,
		'name' => $lookup,
		'pagename' => '#'.$lookup,
		'username' => $userDetail['user'],
		'hashDetail' => $hashDetail,
		'long' => $long,
		'short' => $short,
		'dataPoints' => $dataPoints,
		'min' => $min,
		'avg' => $avg,
		'max' => $max,
		'auth' => $auth,
		'home' => $home,
		'tracking' => $tracking,
		'notification' => $notification
		));
	} catch (Exception $e) {
		echo $e->getMessage();
		exit(1);
	}
	die;
}
