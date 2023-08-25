<?php
require_once './vendor/autoload.php';
require_once './app/autoload.php';
$auth = checkLogin();
$notification = false;
if ($auth){
    $conn = new PDO("mysql:host=$servername;dbname=$database",$username,$password);
    $detail = (array) jwtDecode($_SESSION['user']);
    $name = $detail['user'];
    $notification = notificationCount($conn, $name);
    $conn = null;
}
$regex = str_replace('/', '\/', $home);
$from = '/'.$regex.'/';
$to = '';
$content = $_SERVER['REQUEST_URI'];
$request = preg_replace($from, $to, $content, 1);
//if ($request[strlen($request) - 1] != '/') $request .= '/';
if (strlen($request) > 1 && $request[strlen($request) - 1] != '/') header("Location: $home$request/");

// ROUTER
switch ($request) {
    case '/' :
    case '' :
        require __DIR__ . '/controller/index.php';
        break;
    case stristr($request, 'portfolio') :
        require __DIR__ . '/controller/portfolio.php';
        break;
    case stristr($request, 'trade') :
        require __DIR__ . '/controller/trade.php';
        break;
    case stristr($request, 'archive') :
        require __DIR__ . '/controller/archive.php';
        break;
    case 'leaderboard/' :
    case 'score/' :
        require __DIR__ . '/controller/leaderboard.php';
        break;
    case stristr($request, 'notifications') :
        require __DIR__ . '/controller/notifications.php';
        break;
    case stristr($request, 'activity') :
        require __DIR__ . '/controller/activity.php';
        break;
    case stristr($request, 'research') :
        require __DIR__ . '/controller/research.php';
        break;
    case stristr($request, 'search') :
        require __DIR__ . '/controller/search.php';
        break;
    case stristr($request, 'bankruptcy') :
            require __DIR__ . '/controller/bankruptcy.php';
            break;
    case 'lottery/' :
    case 'lotto/' :
        require __DIR__ . '/controller/lotto.php';
        break;
    case 'chat/' :
    case 'social/' :
        require __DIR__ . '/controller/social.php';
        break;
    case 'faq/' :
    case 'help/' :
        require __DIR__ . '/controller/help.php';
        break;
    case 'login/' :
    case 'register/' :
        require __DIR__ . '/controller/login.php';
        break;
    case 'logout/' :
        require __DIR__ . '/controller/logout.php';
        break;
    case stristr($request, 'reset') :
        require __DIR__ . '/controller/reset.php';
        break;
    case 'cron/' :
        require __DIR__ . '/controller/cron.php';
        break;
    default:
        require __DIR__ . '/controller/404.php';
        break;
}
