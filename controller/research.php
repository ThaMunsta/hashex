<?php
$loader = new Twig_Loader_Filesystem('./view');
$twig = new Twig_Environment($loader, array(
    //'cache' => '../cache',
));

$conn = new PDO("mysql:host=$servername;dbname=$database",$username,$password);

$userDetail = (array) jwtDecode($_SESSION['user']);
$sql = "SELECT * FROM `users` WHERE id = ".$userDetail['id'];
$fullUser = getRow($conn->query($sql));
$sql = 'SELECT * FROM `points` INNER JOIN `wip` ON wip.point_id = points.id WHERE `listed` IS NOT NULL AND `user_id` = \''.$fullUser['id'].'\' ORDER BY `active` desc LIMIT 10';
$complete = getRows($conn->query($sql));
$sql = 'SELECT * FROM `points` INNER JOIN `wip` ON wip.point_id = points.id WHERE `listed` IS NULL AND `user_id` = \''.$fullUser['id'].'\'';
$pending = getRows($conn->query($sql));
$sql = 'SELECT count(*) as count FROM `points` WHERE `user_id` = \''.$fullUser['id'].'\' and `available` < \''.date("Y-m-d H:i:s").'\' and `type` = \'research\' and `redeemed` IS NULL';
$available = getRow($conn->query($sql));
$sql = 'SELECT * FROM `points` WHERE `user_id` = \''.$fullUser['id'].'\' and `available` > \''.date("Y-m-d H:i:s").'\' and `type` = \'research\' and `redeemed` IS NULL';
$points = getRow($conn->query($sql));

$filter = new Twig_Filter('time2str', function ($string) {
    return time2str($string);
});
$twig->addFilter($filter);
try {
echo $twig->render('research.html', array(
	'complete' => $complete,
	'pending' => $pending,
  'available' => $available,
  'points' => $points,
	'auth' => $auth,
	'home' => $home,
  'username' => $userDetail['user'],
  'pagename' => 'Research',
	'tracking' => $tracking,
	'notification' => $notification
	));
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}
