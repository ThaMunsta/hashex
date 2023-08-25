<?php
$conn = new PDO("mysql:host=$servername;dbname=$database",$username,$password);
$detail = (array) jwtDecode($_SESSION['user']);
$name = $detail['user'];
$sql = "SELECT * FROM `notifications` WHERE `player` = '$name' order by id desc";
$out = getRows($conn->query($sql));

if ($out != false){
	foreach ($out as $key => $value) {
		$sql = "UPDATE `notifications` SET `seen` = TRUE where `id` = :id";
		$result = $conn->prepare($sql);
		$result->bindParam(':id', $value['id'], PDO::PARAM_STR);
		$result->execute();
	}
}

$loader = new Twig_Loader_Filesystem('./view');
$twig = new Twig_Environment($loader, array(
    //'cache' => '../cache',
));
$filter = new Twig_Filter('time2str', function ($string) {
    return time2str($string);
});
$twig->addFilter($filter);
try {
echo $twig->render('notifications.html', array(
	'auth' => $auth,
	'home' => $home,
	'tracking' => $tracking,
	'rows' => $out
	));
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}