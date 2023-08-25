<?php
include('simple_html_dom.php');
date_default_timezone_set('America/Toronto');
$getTop = $getActive = $broker = $research = $delist = $usersWorth = $cleanup = time();
$servername = "localhost";
$username = "root";
$password = "";
$database = "hashcash";
$conn = new PDO("mysql:host=$servername;dbname=$database",$username,$password);
define("TOKEN", 'CHANGE ME'); //Access token
define("TOKEN_SECRET", 'CHANGE ME'); //Access token secret
//Consumer API keys
define("CONSUMER_KEY", 'CHANGE ME'); //API key
define("CONSUMER_SECRET", 'CHANGE ME'); //API secret key

while (true){
	if (time() >= $getActive){
		$sql = 'SELECT * FROM `hash`
			WHERE status = \'active\'
			AND last_update < \''.date('Y-m-d H:i:s', strtotime("now - 15 min")).'\'
			OR id IN (SELECT hash_id FROM trades WHERE status = \'held\' OR trade_date > \''.date('Y-m-d H:i:s', strtotime("now - 1 hour")).'\')
			OR name IN (SELECT hash FROM wip WHERE listed > \''.date('Y-m-d H:i:s', strtotime("now - 1 day")).'\')
			ORDER BY `hash`.`last_update`';
		$row = getRow($conn->query($sql));
		if($row){
			if(strtotime($row['last_update']) <= strtotime("now - 15 minutes")){
				notice("Fetching for #".$row['name']);
				$rows = getRite($row['name']);
				newTagAsDelisted($conn,$rows);
				$rawHash = getRawHash($row['name']);
				if(isset($rawHash['error'])){
					if($rawHash['error'] == 'ratelimit'){
						$getActive = $rawHash['wait'];
						notice("Next hash check: ".date('Y-m-d H:i:s',$getActive));
						continue;
					}
				}
				tagUpdates($conn, $rawHash);
				$getActive = strtotime(" + 5 seconds");
			}
			else{
			$getActive = strtotime($row['last_update']." + 15 minutes");
			}
		}
		else{
			$getActive = strtotime(" + 5 minutes");
		}
		notice("Next hash check: ".date('Y-m-d H:i:s',$getActive));
	}
///////////////////////////////////////////////////////////////////////////////	
///////////////////////////////////////////////////////////////////////////////	
	if (time() >= $broker){
		$select = 'SELECT * FROM `trades` WHERE `status` = \'pending\' ORDER BY trade_date';
		$result = $conn->prepare($select);
		$result->execute();
		$row = getRow($result);
		if($row){
			notice("Broker running trade.");
			broker($conn, $row);
		}
		if(getRow($result)){
			$broker = strtotime("+5 seconds");
		}
		else{
			$broker = strtotime("+1 minute");
		}
		notice("Next broker: ".date('Y-m-d H:i:s',$broker));
	}
///////////////////////////////////////////////////////////////////////////////	
///////////////////////////////////////////////////////////////////////////////	
	if (time() >= $getTop){
		notice("Fetching top hashtags.");
		newTagAsDelisted($conn,getTop());
		$getTop = strtotime("+60 minutes");
		notice("Next top hashtags check: ".date('Y-m-d H:i:s',$getTop));
	}
///////////////////////////////////////////////////////////////////////////////	
///////////////////////////////////////////////////////////////////////////////	
	if(time() >= $research){
		$sql = 'SELECT * FROM `wip` WHERE `listed` IS NULL ORDER BY listed';
		$wip = getRow($conn->query($sql));
		if($wip){
			notice("Found research job");
			if($wip['active'] > date('Y-m-d H:i:s')){
				$research = strtotime($wip['active']);
			}
			else{
				$rows = getRite($wip['hash']);
				newTagAsDelisted($conn,$rows);
				$rawHash = getRawHash($wip['hash']);
				if(isset($rawHash['error'])){
					if($rawHash['error'] == 'ratelimit'){
						$research = $rawHash['wait'];
						notice("Next research check: ".date('Y-m-d H:i:s',$getActive));
						continue;
					}
				}
				tagUpdates($conn, $rawHash);
				$sql = 'UPDATE `wip` SET `listed` =\''.date('Y-m-d H:i:s').'\' WHERE `id` = '.$wip['id'];
				getRow($conn->query($sql));
				$research = strtotime("+5 seconds");
			}
		}
		else{
			$research = strtotime("+10 minutes");
		}
		notice("Next research check: ".date('Y-m-d H:i:s',$research));
	}
///////////////////////////////////////////////////////////////////////////////	
///////////////////////////////////////////////////////////////////////////////	
	if(time() >= $usersWorth){
		notice("Calculating all users worth as new activity");
		$allUsersWorth = getUsersWorth($conn);
		newActivity($conn, $allUsersWorth);
		$usersWorth = strtotime("+30 minutes");
		notice("Next user worth calcuation: ".date('Y-m-d H:i:s',$usersWorth));
	}
///////////////////////////////////////////////////////////////////////////////	
///////////////////////////////////////////////////////////////////////////////	
	if(time() >= $delist){
		$sql = 'SELECT * FROM `hash` 
			WHERE hash.status = \'active\'
			AND id NOT IN (SELECT hash_id FROM trades WHERE status = \'held\' OR trade_date > \''.date('Y-m-d H:i:s', strtotime("now - 1 hour")).'\')
			AND name NOT IN (SELECT hash FROM wip WHERE active > \''.date('Y-m-d H:i:s', strtotime("now - 1 day")).'\' OR listed > \''.date('Y-m-d H:i:s', strtotime("now - 1 day")).'\')';
		$delist = getRows($conn->query($sql));
		if($delist){
			notice("Found delist job");
			delistHash($conn, $delist);
		}
		$delist = strtotime("+1 hour");
		notice("Next delist check: ".date('Y-m-d H:i:s',$delist));
	}
///////////////////////////////////////////////////////////////////////////////	
///////////////////////////////////////////////////////////////////////////////	
	if(time() >= $cleanup){
		notice("Deleting old activity data");
		$sql = 'DELETE FROM `activity` 
		WHERE hash_id in (select id FROM hash WHERE STATUS != \'active\') 
		AND activity_date < NOW() - INTERVAL 7 DAY';
		getRow($conn->query($sql));
		$sql = 'DELETE FROM `activity` 
		WHERE activity_date < NOW() - INTERVAL 30 DAY';
		getRow($conn->query($sql));
		$sql = 'DELETE FROM `hash` 
		WHERE status != \'active\'
		AND last_update < NOW() - INTERVAL 30 DAY';
		getRow($conn->query($sql));
		$cleanup = strtotime("+1 day");
		notice("Next cleanup: ".date('Y-m-d H:i:s',$cleanup));
	}
///////////////////////////////////////////////////////////////////////////////	
///////////////////////////////////////////////////////////////////////////////	
	sleep(1);
}
die();

// ███████╗██╗   ██╗███╗   ██╗ ██████╗████████╗██╗ ██████╗ ███╗   ██╗███████╗
// ██╔════╝██║   ██║████╗  ██║██╔════╝╚══██╔══╝██║██╔═══██╗████╗  ██║██╔════╝
// █████╗  ██║   ██║██╔██╗ ██║██║        ██║   ██║██║   ██║██╔██╗ ██║███████╗
// ██╔══╝  ██║   ██║██║╚██╗██║██║        ██║   ██║██║   ██║██║╚██╗██║╚════██║
// ██║     ╚██████╔╝██║ ╚████║╚██████╗   ██║   ██║╚██████╔╝██║ ╚████║███████║
// ╚═╝      ╚═════╝ ╚═╝  ╚═══╝ ╚═════╝   ╚═╝   ╚═╝ ╚═════╝ ╚═╝  ╚═══╝╚══════╝
																		  
function notice($message){
	echo getSqlNow().": ".$message.PHP_EOL;
}

function getRow($result){
	if (!$result) return false;
	if ($result) if ($result->rowCount() > 0) {
		$row = $result->fetch(PDO::FETCH_ASSOC);
		return $row;
	}
	else return false;
}

function getRows($result){
	if (!$result) return false;
	if ($result) if ($result->rowCount() > 0) {
		return $result->fetchAll();
	}
	else return false;
}

function getSqlNow(){
	  $datetime = date('Y-m-d H:i:s');
	return $datetime;
}

function getRite($tag){
	$hash = array();
	$base = 'https://ritetag.com/best-hashtags-for/';
	$html = file_get_html($base.$tag);
// $html = file_get_html("hash.txt");
	if(!$html){
		notice("WARNING: Couldn't retrieve the related hashtags");
		return;
	}
	foreach($html->find('.good') as $key => $value) {
		$name = str_replace('#','',$value->plaintext);
		if($name == $tag) continue;
		$hash[$key]['tag'] = $name;
		if ($key == 19) break;
	}
	foreach($html->find('.htagUniqueTweets') as $key => $value) {
		$hash[$key]['volume'] = (floatval(str_replace(',','',$value->plaintext)) / 100);
		if ($key == 19) break;
	}
	return $hash;
}

function delistHash($conn, $rows){
	foreach ($rows as $key => $value) {
		$sql = 'UPDATE `hash` SET `status` = \'delisted\' WHERE `name` = \''.$value['name'].'\'';
		getRow($conn->query($sql));
		notice("Delisted: #".$value['name']);
	}
}

function getTop(){
	$trending = 'https://ritetag.com/hashtag-search?green=0';
	$html = file_get_html($trending);
	// $html = file_get_html("trending.txt");
	if(!$html){
		notice("WARNING: Couldn't retrieve the top hashtags");
		return;
	}
	foreach($html->find('a[class*="taglink"]') as $key => $value) {
		$hash[$key]['tag'] = str_replace('#','',$value->plaintext);
	}
	foreach($html->find('span[class="htagUniqueTweets"]') as $key => $value) {
		$hash[$key]['volume'] = (floatval(str_replace(',','',$value->plaintext))/100);
	}
	return $hash;
}

function tagUpdates($conn, $rows){
	if(!$rows){
		notice("WARNING: Didn't rows for hashtag update");
		return;
	}
	foreach ($rows as $key => $value) {
		if(!isset($value['volume']) || !isset($value['tag'])){
			continue;
		}
		if(strlen($value['tag']) <= 3) continue;
		if($value['volume'] < 0.25){
			$value['volume'] = 0.25;
		}
		else {
			$value['quickmaths'] = true; // setting to true because this should run almost every time to buffer spikes
		}
		$select = 'SELECT * FROM `hash` WHERE `name` = \''.$value['tag'].'\'';
		$result = $conn->prepare($select);
		$result->execute();
		$row = getRow($result);
		if($row){
			$update = 'UPDATE `hash` SET `name` = \''.$value['tag'].'\', `value` = \''.$value['volume'].'\', `last_update` = \''.date('Y-m-d H:i:s').'\', `status` = \'active\' WHERE `name` = \''.$value['tag'].'\'';
			if(isset($value['quickmaths'])){
				notice('Quick Maths!');
				$update = 'UPDATE `hash` SET `name` = \''.$value['tag'].'\', `value` = ROUND((`value` + \''.$value['volume'].'\') / 2,2), `last_update` = \''.date('Y-m-d H:i:s').'\', `status` = \'active\' WHERE `name` = \''.$value['tag'].'\'';
				$value['volume'] = round(($value['volume'] + $row['value']) / 2, 2);
			}
			$result = $conn->prepare($update);
			$result->execute();
			$activity[0]['hash'] = $row['id'];
			$activity[0]['worth'] = $value['volume'];
			newActivity($conn, $activity);
			notice("Updated ".$value['tag']);
		}
		else{
			 $sql = "INSERT INTO `hash`(`name`, `value`, `status`)
			VALUES (:name,:value,'active')";
		$result = $conn->prepare($sql);
		$result->bindParam(':name', $value['tag']);
		$result->bindParam(':value', $value['volume']);
		$result->execute();
		notice("Insert new ".$value['tag']);
		}
	}
}

function newTagAsDelisted($conn, $rows){
	if(!$rows){
		notice("WARNING: Didn't rows to push as delisted");
		return;
	}
	foreach ($rows as $key => $value) {
		if(!isset($value['volume']) || !isset($value['tag'])){
			continue;
		}
		if(strlen($value['tag']) <= 3) continue;
		if($value['volume'] < 0.25) $value['volume'] = 0.25;
		$sql = 'SELECT * FROM `hash` WHERE `name` = \''.$value['tag'].'\'';
		$row = getRow($conn->query($sql));
		if($row === false){
			$sql = "INSERT INTO `hash`(`name`, `value`, `status`)
				VALUES (:name,:value,'delisted')";
			$result = $conn->prepare($sql);
			$result->bindParam(':name', $value['tag']);
			$result->bindParam(':value', $value['volume']);
			$result->execute();
			notice("Insert new delisted ".$value['tag']);
		}
		elseif($row['status'] != 'active'){
			$update = 'UPDATE `hash` SET `value` = \''.$value['volume'].'\', `last_update` = \''.date('Y-m-d H:i:s').'\', `status` = \'delisted\' WHERE `name` = \''.$value['tag'].'\'';
			$result = $conn->prepare($update);
			$result->execute();
			$activity[0]['hash'] = $row['id'];
			$activity[0]['worth'] = $value['volume'];
			newActivity($conn, $activity);
			notice("Pushed as delisted: ".$value['tag']);
		}
	}
}

function getUsersWorth($conn){
	$worths = array();
	$sql = 'SELECT * FROM `users`';
	$users = getRows($conn->query($sql));
	foreach ($users as $key => $user) {
		$worth = $user['cash'];
		$sql = "SELECT hash.name, SUM(`volume`) AS total_volume, ROUND(AVG(`cost`),2) AS avg_paid, hash.value as current_value FROM `trades` INNER JOIN `hash` on hash.id = trades.hash_id WHERE `user_id` = ".$user['id']." AND trades.status = 'held' AND trades.type = 'long' GROUP BY `hash_id`";
		$long = getRows($conn->query($sql));
		$sql = "SELECT hash.name, SUM(`volume`) AS total_volume, ROUND(AVG(`cost`),2) AS avg_paid, hash.value as current_value FROM `trades` INNER JOIN `hash` on hash.id = trades.hash_id WHERE `user_id` = ".$user['id']." AND trades.status = 'held' AND trades.type = 'short' GROUP BY `hash_id`";
		$short = getRows($conn->query($sql));
		if($long){
			foreach ($long as $longkey => $investment) {
				$worth = $worth + ($investment['current_value'] * $investment['total_volume']);
			}
		}
		if($short){
			foreach ($short as $shortkey => $investment) {
				$worth = $worth + (($investment['avg_paid'] - $investment['current_value']) * $investment['total_volume']);
				$worth = $worth + ($investment['avg_paid'] * $investment['total_volume']);
			}
		}
		$worths[$key]['user'] = $user['id'];
		$worths[$key]['worth'] = $worth;
	}
	return $worths;
}

function newActivity($conn, $rows){
	foreach ($rows as $key => $activity) {
		if(isset($activity['user'])){
			$sql = "INSERT INTO `activity` (`user_id`, `value`) 
			  VALUES ('".$activity['user']."', '".$activity['worth']."')";
			getRow($conn->query($sql));
		}
		if(isset($activity['hash'])){
			$sql = "INSERT INTO `activity` (`hash_id`, `value`) 
			  VALUES ('".$activity['hash']."', '".$activity['worth']."')";
			getRow($conn->query($sql));
		}
	}
}

function broker($conn, $trade){
	switch($trade['type']){
		case 'long':
		$sql = 'SELECT * FROM `users` WHERE `id` = \''.$trade['user_id'].'\'';
		$user = getRow($conn->query($sql));
		$cost = $trade['volume'] * $trade['cost'];
		if($user['cash'] > $cost){
			$sql = 'UPDATE `users` SET `cash` = `cash` - \''.$cost.'\' WHERE `id` = \''.$trade['user_id'].'\'';
			getRow($conn->query($sql));
			$sql = 'UPDATE `trades` SET `status` = \'held\' WHERE `id` = \''.$trade['id'].'\'';
			getRow($conn->query($sql));
			notice("Trade complete");
			notice("Cost ".$cost);
		}
		else{
			$sql = 'UPDATE `trades` SET `status` = \'NSF\' WHERE `id` = \''.$trade['id'].'\'';
			getRow($conn->query($sql));
			notice("Trade incomplete: NSF");
		}
		break;

		case 'sell':
		$sql = 'SELECT * FROM `trades` WHERE `hash_id` = \''.$trade['hash_id'].'\' and `status` = \'held\' and `type` = \'long\' and `user_id` = \''.$trade['user_id'].'\' AND `volume` > 0 ORDER BY trade_date';
		$available = getRow($conn->query($sql));
		if(!$available){
			$sql = 'UPDATE `trades` SET `status` = \'NSF\' WHERE `id` = \''.$trade['id'].'\'';
			getRow($conn->query($sql));
			notice("Trade incomplete: NSF");
			break;
		}
		if($available['volume'] == $trade['volume']){
			$earnings = $trade['cost'] * $trade['volume'];
			$sql = 'UPDATE `trades` SET `volume` = 0, `status` = \'closed\' WHERE `id` = \''.$trade['id'].'\' OR `id` = \''.$available['id'].'\'';
			getRow($conn->query($sql));
		}
		elseif($available['volume'] > $trade['volume']){
			$remainder = $available['volume'] - $trade['volume'];
			$earnings = $trade['cost'] * $trade['volume'];
			$sql = 'UPDATE `trades` SET `volume` = '.$remainder.' WHERE `id` = \''.$available['id'].'\'';
			getRow($conn->query($sql));
			$sql = 'UPDATE `trades` SET `volume` = 0, `status` = \'closed\' WHERE `id` = \''.$trade['id'].'\'';
			getRow($conn->query($sql));
		}
		else{
			$remainder = $trade['volume'] - $available['volume'];
			$earnings = $trade['cost'] * $available['volume'];
			$sql = 'UPDATE `trades` SET `volume` = '.$remainder.' WHERE `id` = \''.$trade['id'].'\'';
			getRow($conn->query($sql));
			$sql = 'UPDATE `trades` SET `volume` = 0, `status` = \'closed\' WHERE `id` = \''.$available['id'].'\'';
			getRow($conn->query($sql));
		}
		$sql = 'UPDATE `users` SET `cash` = `cash` + \''.$earnings.'\' WHERE `id` = \''.$trade['user_id'].'\'';
		getRow($conn->query($sql));
		notice("Trade complete");
		notice("Earnings ".$earnings);
		break;

		case 'short':
		$sql = 'SELECT * FROM `users` WHERE `id` = \''.$trade['user_id'].'\'';
		$user = getRow($conn->query($sql));
		$cost = $trade['volume'] * $trade['cost'];
		if($user['cash'] > $cost){
			$sql = 'UPDATE `users` SET `cash` = `cash` - \''.$cost.'\' WHERE `id` = \''.$trade['user_id'].'\'';
			getRow($conn->query($sql));
			$sql = 'UPDATE `trades` SET `status` = \'held\' WHERE `id` = \''.$trade['id'].'\'';
			getRow($conn->query($sql));
			notice("Trade complete");
			notice("Cost ".$cost);
		}
		else{
			$sql = 'UPDATE `trades` SET `status` = \'NSF\' WHERE `id` = \''.$trade['id'].'\'';
			getRow($conn->query($sql));
			notice("Trade incomplete: NSF");
		}
		break;

		case 'cover':
		$sql = 'SELECT * FROM `trades` WHERE `hash_id` = \''.$trade['hash_id'].'\' and `status` = \'held\' and `type` = \'short\' and `user_id` = \''.$trade['user_id'].'\' AND `volume` > 0 ORDER BY trade_date';
		$available = getRow($conn->query($sql));
		if(!$available){
			$sql = 'UPDATE `trades` SET `status` = \'NSF\' WHERE `id` = \''.$trade['id'].'\'';
			getRow($conn->query($sql));
			notice("Trade incomplete: NSF");
			break;
		}
		if($available['volume'] == $trade['volume']){
			$earnings = ($available['cost'] - $trade['cost']) * $trade['volume'];
			$earnings = $earnings + ($available['cost'] * $trade['volume']);
			$sql = 'UPDATE `trades` SET `volume` = 0, `status` = \'closed\' WHERE `id` = \''.$trade['id'].'\' OR `id` = \''.$available['id'].'\'';
			getRow($conn->query($sql));
		}
		elseif($available['volume'] > $trade['volume']){
			$remainder = $available['volume'] - $trade['volume'];
			$earnings = ($available['cost'] - $trade['cost']) * $trade['volume'];
			$earnings = $earnings + ($available['cost'] * $trade['volume']);
			$sql = 'UPDATE `trades` SET `volume` = \''.$remainder.'\' WHERE `id` = \''.$available['id'].'\'';
			getRow($conn->query($sql));
			$sql = 'UPDATE `trades` SET `volume` = 0, `status` = \'closed\' WHERE `id` = \''.$trade['id'].'\'';
			getRow($conn->query($sql));
		}
		else{
			$remainder = $trade['volume'] - $available['volume'];
			$earnings = ($available['cost'] - $trade['cost']) * $available['volume'];
			$earnings = $earnings + ($available['cost'] * $available['volume']);
			$sql = 'UPDATE `trades` SET `volume` = '.$remainder.' WHERE `id` = \''.$trade['id'].'\'';
			getRow($conn->query($sql));
			$sql = 'UPDATE `trades` SET `volume` = 0, `status` = \'closed\' WHERE `id` = \''.$available['id'].'\'';
			getRow($conn->query($sql));
		}
		$sql = 'UPDATE `users` SET `cash` = `cash` + \''.$earnings.'\' WHERE `id` = \''.$trade['user_id'].'\'';
		getRow($conn->query($sql));
		notice("Trade complete");
		notice("Earnings ".$earnings);
		break;
	}
}

function getRawHash($tag){
	$host='api.twitter.com';
	$path='/1.1/search/tweets.json'; //API call path
	$ratepath='/1.1/application/rate_limit_status.json';
	$url="https://$host$path";
	$rateurl="https://$host$ratepath";
	//Query parameters
	$query = array(
		'q' => '#'.$tag,		  /* Word to search */
		'count' => '100',			   /* Specifies a maximum number of tweets you want to get back, up to 100. As you have 100 API calls per hour only, you want to max it */
		'result_type' => 'recent',	  /* Return only the most recent results in the response */
		'include_entities' => 'false'   /* Saving unnecessary data */
	);
	$ratequery = array(
		'resources' => 'search'
	);

	//Authentication
	$oauth = array(
		'oauth_consumer_key' => CONSUMER_KEY,
		'oauth_token' => TOKEN,
		'oauth_nonce' => (string)mt_rand(), //A stronger nonce is recommended
		'oauth_timestamp' => time(),
		'oauth_signature_method' => 'HMAC-SHA1',
		'oauth_version' => '1.0'
	);
	//Initializing
	$countTweets=0;	 //Tweets fetched
	$apiCalls=0;     //API Calls
	$properCase = array();
	$done = false;
	$maxapi = 50;
	$seconds = 0;
	$tps = 0;
	$twitter_data = new stdClass();
	$rateDetails = (twitter_search($ratequery,$oauth,$rateurl));
	$rateRemaining = $rateDetails->resources->search->{"/search/tweets"}->remaining;
	$rateReset = $rateDetails->resources->search->{"/search/tweets"}->reset;
	$rateWait = ($rateReset - time());
	notice($rateRemaining." API calls remaining");
	if($maxapi > $rateRemaining){
		if($rateRemaining < 25){
			notice("Rate limit warning. Need to wait till ".date('H:i:s',$rateReset));
			return ['error' => 'ratelimit', 'wait' => $rateReset];
		}
		$maxapi = $rateRemaining;
	}
	do{
		$twitter_data = twitter_search($query,$oauth,$url);
		if(!$twitter_data || isset($twitter_data->errors)){
			notice("WARNING: Didn't get the right response from Twitter");
			notice($twitter_data->errors[0]->message);
			if($twitter_data->errors[0]->message == 'Rate limit exceeded'){
				notice("Rate limit warning. Need to wait till ".date('H:i:s',$rateReset));
				return ['error' => 'ratelimit', 'wait' => $rateReset];
			}
			return;
		}
		$apiCalls++;
		if(count($twitter_data->statuses) > 0){
			if($apiCalls == 1){
				$first = date('Y-m-d H:i:s', strtotime($twitter_data->statuses[0]->created_at));
			}
			$dirtyCount = 0;
			foreach ($twitter_data->statuses as $key => $value) {
				$tweetDate = date('Y-m-d H:i:s', strtotime($value->created_at));
				$matches = get_hashtags($value->text);
				foreach ($matches as $key => $case) {
					if (strlen($case) > 0){
						$properCase[] = $case;
					}
				}
				if($tweetDate > date('Y-m-d H:i:s', strtotime("now - 60 minutes"))){
					$countTweets++;
					$lastid = $dirtyCount;
				}
				else{
					$done = true;
					$seconds = 3600;
				}
				$dirtyCount++;
			}
			if($seconds < 3600){
				$last = date('Y-m-d H:i:s', strtotime($twitter_data->statuses[$lastid]->created_at));
				$seconds = strtotime($first) - strtotime($last);
			}
			if($countTweets < 1){
				$done = true;
			}
			if($seconds < 3600) {
				notice($seconds." seconds is less than 60 minutes");
				$string="?max_id="; 
				$parse=explode("&",$twitter_data->search_metadata->next_results);       
				$maxID=substr($parse[0],strpos($parse[0],$string)+strlen($string));  
				$query['max_id'] = $maxID;
			}
			else{
				$done = true;
			}
		}
		else {
			// no tweets found but lets not divide by zero later
			$countTweets++;
			$done = true;
		}
		if($apiCalls >= $maxapi){
			$done = true;
		}
		if($countTweets > 0 && $seconds > 0){
			$lastTPS = $tps;
			$tps = round(($countTweets / $seconds) * 3600,4);
			$tpsDiff = $lastTPS - $tps;
			$tpsPerc = $tpsDiff / $tps * 100;
		}
		if($countTweets > 1200){
			notice("Cost adjusts by: $".round($tpsDiff,2)." ".round($tpsPerc,2)."% new cost: ".$tps);
			if($tpsPerc < 1 && $tpsPerc > -1){
				notice('Enough data to do math');
				if ($seconds < 900){
					$quickmaths = true;
				}
				$done = true;
			}
		}
	}while(!$done);
	if($seconds > 3600){
		$seconds = 3600;
	}
	$pop = popularArray($properCase);
	$popkey = array_search(strtolower($tag), array_map('strtolower', $pop));
	if($popkey != false){
		$tagCase = $pop[$popkey];
	}
	notice($seconds." seconds");
	notice("RT: ".$countTweets." API: ".$apiCalls);
	if($countTweets > 1){
		$countTweets = round(($countTweets / $seconds) * 3600,4);
	}
	$hash[0]['volume'] = $countTweets / 100;
	if (isset($tagCase)){
		$hash[0]['tag'] = $tagCase;
	}
	else{
		$hash[0]['tag'] = $tag;
	}
	if(isset($quickmaths)){
		$hash[0]['quickmaths'] = true;
	}
	notice("Worth ".$countTweets / 100);
	return $hash;
}

//Used in Twitter's demo
function add_quotes($str) { return '"'.$str.'"'; }

//Searchs Twitter for a word and get a couple of results
function twitter_search($query, $oauth, $url){  
	$method='GET';

	$arr=array_merge($oauth, $query); //Combine the values THEN sort
	asort($arr); //Secondary sort (value)
	ksort($arr); //Primary sort (key)
	$querystring=http_build_query($arr,'','&');
	//Mash everything together for the text to hash
	$base_string=$method."&".rawurlencode($url)."&".rawurlencode($querystring);
	//Same with the key
	$key=rawurlencode(CONSUMER_SECRET)."&".rawurlencode(TOKEN_SECRET);
	//Generate the hash
	$signature=rawurlencode(base64_encode(hash_hmac('sha1', $base_string, $key, true)));
	//This time we're using a normal GET query, and we're only encoding the query params (without the oauth params)
	$url=str_replace("&amp;","&",$url."?".http_build_query($query));
	$oauth['oauth_signature'] = $signature; //Don't want to abandon all that work!
	ksort($oauth); //Probably not necessary, but twitter's demo does it
	$oauth=array_map("add_quotes", $oauth); //Also not necessary, but twitter's demo does this too  
	//This is the full value of the Authorization line
	$auth="OAuth ".urldecode(http_build_query($oauth, '', ', '));
	//If you're doing post, you need to skip the GET building above and instead supply query parameters to CURLOPT_POSTFIELDS
	$options=array( CURLOPT_HTTPHEADER => array("Authorization: $auth"),
		//CURLOPT_POSTFIELDS => $postfields,
		CURLOPT_HEADER => false,
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_SSL_VERIFYPEER => false);
	//Query Twitter API
	$feed=curl_init();
	curl_setopt_array($feed, $options);
	curl_setopt($feed,CURLOPT_TIMEOUT,1000);
	$json=curl_exec($feed);
	curl_close($feed);
	//Return decoded response
	return json_decode($json);
};

function get_hashtags($string, $str = 1) {
    preg_match_all('/#(\w+)/',$string,$matches);
    $i = 0;
    $keywords = array();
    if ($str) {
        foreach ($matches[1] as $match) {
            $count = count($matches[1]);
            $keywords[] = "$match";
            $i++;
        }
    } else {
        foreach ($matches[1] as $match) {
            $keyword[] = $match;
        }
        $keywords = $keyword;
    }
    return $keywords;
}

function popularArray($array){
	$values = array_count_values($array);
	arsort($values);
	$popular = array_slice(array_keys($values), 0, 5, true);
	return $popular;
}