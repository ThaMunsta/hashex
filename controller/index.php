<?php

$auth = checkLogin();
$loader = new Twig_Loader_Filesystem('./view');
$twig = new Twig_Environment($loader, array(
    //'cache' => '../cache',
));
try {
echo $twig->render('index.html', array(
	'auth' => $auth,
	'home' => $home,
	'pagename' => 'Hashtag Exchange',
	'tracking' => $tracking,
	'notification' => $notification
	));
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}
