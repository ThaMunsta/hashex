<?php
$loader = new Twig_Loader_Filesystem('./templates');
$twig = new Twig_Environment($loader, array(
    //'cache' => '../cache',
));
$filter = new Twig_Filter('db2str', function ($string) {
    return db2str($string);
});
$twig->addFilter($filter);
$filter = new Twig_Filter('d2h', function ($string) {
    return dechex($string);
});
$twig->addFilter($filter);
$filter = new Twig_Filter('time2str', function ($string) {
    return time2str($string);
});
$twig->addFilter($filter);

$bits = explode ("/", $_SERVER['REQUEST_URI']);
$depth = sizeof($bits)-$subdirs;
$lookup = $bits[(sizeof($bits)-2)];
if (strpos($lookup, '~') === 0) $package = substr($lookup, strpos($lookup, "~") + 1);
if ($depth > 4) {
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
$conn = new PDO("mysql:host=$servername;dbname=$database",$username,$password);
if (isset($package)) $sql ="SELECT activity.*, packages.hunt FROM activity LEFT JOIN packages ON activity.package = packages.id WHERE packages.id = '".hexdec($package)."' ORDER BY id DESC";
elseif ($lookup != 'activity') $sql ="SELECT activity.*, packages.hunt FROM activity LEFT JOIN packages ON activity.package = packages.id WHERE activity.player = '$lookup' ORDER BY id DESC";
else $sql ="SELECT activity.*, packages.hunt FROM activity LEFT JOIN packages ON activity.package = packages.id ORDER BY id DESC LIMIT 250";
	$result = $conn->prepare($sql);
	$result->execute();
$out = [];
if ($result) if ($result->rowCount() > 0) {
	while($row = getRow($result)) {
		$id = $row['package'];
		$sql = "SELECT id FROM packages WHERE hunt IN (SELECT hunt FROM packages WHERE id = '$id')";
		$row['packagecount'] = id2Count($conn->query($sql), $id);
		$out[] = $row;
	}
}
else {
	$row = 0;
}

if (isset($package)){
	$sql = "SELECT * FROM packages WHERE id = '".hexdec($package)."'";
	$packageDetails = getRow($conn->query($sql));
	$sql = "SELECT * FROM players WHERE display = '".$packageDetails['mayor']."'";
	$mayor = getRow($conn->query($sql));
	$sql = "SELECT * FROM players WHERE display = '".$packageDetails['owner']."'";
	$owner = getRow($conn->query($sql));
	if (isset($id)){
		$sql = "SELECT * FROM packages WHERE id = '$id'";
		$package = getRow($conn->query($sql));
		$transport = 'https://';
		if (!isset($_SERVER['HTTPS'])) $transport = 'http://';
		if ($package['img']) $packageDetails['img'] = file_get_contents($transport.$_SERVER['HTTP_HOST'].$home.'store/!'.$package['img']);
	}
	try {
	echo $twig->render('activity.filter.package.html', array(
		'package' => $packageDetails,
		'mayor' => $mayor,
		'owner' => $owner,
		'rows' => $out,
		'auth' => $auth,
		'home' => $home,
		'tracking' => $tracking,
		'notification' => $notification
		));
	} catch (Exception $e) {
	    echo $e->getMessage();
	    exit(1);
	}
}
elseif ($lookup != 'activity'){
	$sql = "SELECT * FROM players WHERE display = '$lookup'";
	$userDetails = getRow($conn->query($sql));
	$transport = 'https://';
	if (!isset($_SERVER['HTTPS'])) $transport = 'http://';
	if ($userDetails['img']) $userDetails['img'] = file_get_contents($transport.$_SERVER['HTTP_HOST'].$home.'store/!'.$userDetails['img']);
	try {
	echo $twig->render('activity.filter.user.html', array(
		'user' => $userDetails,
		'rows' => $out,
		'auth' => $auth,
		'home' => $home,
		'tracking' => $tracking,
		'notification' => $notification
		));
	} catch (Exception $e) {
	    echo $e->getMessage();
	    exit(1);
	}
}
else{
	try {
	echo $twig->render('activity.html', array(
		'rows' => $out,
		'auth' => $auth,
		'home' => $home,
		'tracking' => $tracking,
		'notification' => $notification
		));
	} catch (Exception $e) {
	    echo $e->getMessage();
	    exit(1);
	}
}
