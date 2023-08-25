<?php
$conn = new PDO("mysql:host=$servername;dbname=$database",$username,$password);
$sql = "WITH leaderboard AS (
    SELECT a.*, ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY id DESC) AS top
    FROM activity AS a
    )
SELECT * from leaderboard LEFT JOIN users ON leaderboard.user_id = users.id WHERE top = 1 AND user_id IS NOT null ORDER BY value DESC";
$rows = getRows($conn->query($sql));
$loader = new Twig_Loader_Filesystem('./view');
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
try {
echo $twig->render('leaderboard.html', array(
	'rows' => $rows,
	'auth' => $auth,
	'home' => $home,
  'pagename' => 'Global Leaderboard',
	'tracking' => $tracking,
	'notification' => $notification
	));
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}
