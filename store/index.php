<?php
$nomail = true;
require_once '../app/autoload.php';
error_reporting(0);
// Create connection
$conn = new mysqli($servername, $username, $password, $database);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$api = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], "&api=") + 5);
$sql = "SELECT * FROM `api` WHERE `key` = '$api'";
$result = $conn->query($sql);
$api = false;
// if ($result->num_rows > 0){
// 	$api=true;
// 	while($row = $result->fetch_assoc()) {
// 		$update = $conn->query('UPDATE api
// 		SET calls = calls + 1
// 		WHERE id = '.$row["id"]);
// 	}
// }

if (strpos($_SERVER['REQUEST_URI'], '/top') !== false){
	$sql = "SELECT * FROM `images` ORDER BY `hits` DESC LIMIT 5";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			$file = "./store/".$row["private"].".".$row["ext"];
			$type = pathinfo($file, PATHINFO_EXTENSION);
			$data = file_get_contents($file);
			$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
			echo "<img height=25% src=".$base64." /><br>Public ID: ".$row["public"]."<br>Tags: ".$row["tags"]."<br>";
			$ip = $_SERVER['REMOTE_ADDR'];
			if ($ip !== $row["ip"]){
			$update = $conn->query('UPDATE images
			SET hits = hits + 1
			WHERE id = '.$row["id"]);
			$update = $conn->query('UPDATE images
			SET ip = "'.$ip.'"
			WHERE id = '.$row["id"]);
			}
		}
	}
	return;
}

if (strpos($_SERVER['REQUEST_URI'], '/new') !== false){
	$sql = "SELECT * FROM `images` ORDER BY `id` DESC LIMIT 5";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			$file = "./store/".$row["private"].".".$row["ext"];
			$type = pathinfo($file, PATHINFO_EXTENSION);
			$data = file_get_contents($file);
			$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
			echo "<img height=25% src=".$base64." /><br>Public ID: ".$row["public"]."<br>Tags: ".$row["tags"]."<br>";
			$ip = $_SERVER['REMOTE_ADDR'];
			if ($ip !== $row["ip"]){
			$update = $conn->query('UPDATE images
			SET hits = hits + 1
			WHERE id = '.$row["id"]);
			$update = $conn->query('UPDATE images
			SET ip = "'.$ip.'"
			WHERE id = '.$row["id"]);
			}
		}
	}
	return;
}

$public = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], "!") + 1);
$public = strtok($public, '&');
if (strlen($public) < 5){
	echo "Be more spesific, hacker.";
	die;
}
else {
	$sql = "SELECT * FROM `images` WHERE `public` LIKE '%$public%'";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			if ($api === true){
				echo "url: https://".$_SERVER['HTTP_HOST'].$GLOBALS['home']."img/uploads".$row["private"].".".$row["ext"];
				break;
			}
			$file = "../img/uploads/".$row["private"].".".$row["ext"];
			$type = pathinfo($file, PATHINFO_EXTENSION);
			$data = file_get_contents($file);
			$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
			echo "<img src=".$base64." />";
			return;
		}
		return;
	}
}
$tags = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], "?") + 1);
$tags = strtok($tags, '&');
if (strlen($tags) < 3){
	echo "Be more spesific, hacker.";
	die;
}
$tags=urldecode($tags);
$sql = "SELECT * FROM `images` WHERE `tags` LIKE '%$tags%'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
	while($row = $result->fetch_assoc()) {
		if ($api === true){
			echo "url: https://".$_SERVER['HTTP_HOST'].$GLOBALS['home']."img/uploads".$row["private"].".".$row["ext"];
		}
		else {
			$file = "../img/uploads/".$row["private"].".".$row["ext"];
			$type = pathinfo($file, PATHINFO_EXTENSION);
			$data = file_get_contents($file);
			$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
			echo "<img height=25% src=".$base64." /><br>Public ID: ".$row["public"]."<br>Tags: ".$row["tags"]."<br>";
		}
		$ip = $_SERVER['REMOTE_ADDR'];
		if ($ip !== $row["ip"]){
			$update = $conn->query('UPDATE images
			SET hits = hits + 1
			WHERE id = '.$row["id"]);
			$update = $conn->query('UPDATE images
			SET ip = "'.$ip.'"
			WHERE id = '.$row["id"]);
		}
	}
}
else{
	echo "No results";
}

?>
