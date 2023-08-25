<?php
require_once './vendor/autoload.php';
require_once './app/autoload.php';
dieLogin();
$loader = new Twig_Loader_Filesystem('./view');
$twig = new Twig_Environment($loader, array(
    //'cache' => '../cache',
));
try {
echo $twig->render('logout.html', array(
	'home' => $home,
	'tracking' => $tracking
	));
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}
