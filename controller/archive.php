<?php
$sqldate = date("Y-m-d", time());
$conn = new mysqli($servername, $username, $password, $database);
$sql = "SELECT * FROM `hash` WHERE `status` = 'delisted' ORDER BY RAND() LIMIT 100";
$result = $conn->query($sql);
$out = [];
if ($result) if ($result->num_rows > 0) {
	while($row = $result->fetch_assoc()) {
		$out[] = $row;
	}
}
else {
	$out = 0;
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
echo $twig->render('archive.html', array(
	'rows' => $out,
	'auth' => $auth,
	'home' => $home,
	'pagename' => 'Inactive Hashes',
	'tracking' => $tracking,
	'notification' => $notification
	));
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}
