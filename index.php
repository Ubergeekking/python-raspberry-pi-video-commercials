<?php

$path_to_db = "/mnt/content/";
$___db = array();

function db_load($name){
	global $path_to_db;
	global $___db;
	if(file_exists($path_to_db . $name . ".db")) {
		$___db[$name] = unserialize(file_get_contents($path_to_db . $name . ".db"));
		return true;
	} else {
		$___db[$name] = array();
		return false;
	}
}

function db_save($name) {
	global $path_to_db;
	global $___db;
	return file_put_contents($path_to_db . $name . ".db", serialize($___db[$name]));
}

function db_add($name, $value) {
	global $___db;
	if(!$___db[$name]) $___db[$name] = array();
	array_push($___db[$name], $value);
	return count($___db[$name]);
}

function db_length($name) {
	global $___db;
	return count($___db[$name]);
}

function db_geti($name, $index) {
	global $___db;
	return $___db[$name][$index];
}

function db_getw($name, $key, $value) {
	global $___db;
	$ret = array();
	for($i=0; $i<count($___db[$name]); $i++) {
		if($___db[$name][$i][$key]==$value) {
			array_push($ret, array($i, $___db[$name][$i]));
		}
	}
	return $ret;
/*
$sim = similar_text('bafoobar', 'barfoo', $perc);
echo "similarity: $sim ($perc %)\n";
*/

}


if(isset($_GET["current_video"])) {
	db_load("shows");
	db_add("shows", array("name"=>$_GET["current_video"], "time"=>time()));
	db_save("shows");
	die(1);
}

if(isset($_GET["error"])) {
	db_load("errors");
	db_add("errors", array("name"=>$_GET["error"], "time"=>time()));
	db_save("errors");
	die(1);
}

if(isset($_GET["current_comm"])) {
	db_load("commercials");
	db_add("commercials", array("name"=>$_GET["current_comm"], "time"=>time()));
	db_save("commercials");
	die(1);
}


$days=array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");

echo '
<html>
<head>
<title>TV Station</title>
</head>
<body>
<h2>Current Time: ' . date("h:i A \o\\n ", time()) . $days[date("w", time())] . date(" m/d/Y", time()) .'</h2>
<h2>Last 5 Videos:</h2>
<ul>';
db_load("shows");
$sl = db_length("shows");

for($i=$sl-1;$i>$sl-6;$i--) {
	$temp = db_geti("shows", $i);
	echo '<li>' . $temp["name"] . " played at " . date("h:i A \o\\n ", $temp["time"]) . $days[date("w", $temp["time"])] . date(" m/d/Y", $temp["time"]) . '</li>';
}



echo '
</ul>
<h2>Last 5 Commercials:</h2>
<ul>';

db_load("commercials");
$sl = db_length("commercials");
for($i=$sl-1;$i>$sl-6;$i--) {
	$temp = db_geti("commercials", $i);
	echo '<li>' . $temp["name"] . " played at " . date("h:i A \o\\n w m/d/Y", $temp["time"]) . '</li>';
}

//$csv = ;
echo '
</ul>
<h2>Last 10 Errors:</h2>
<ul>';

db_load("errors");
$sl = db_length("errors");
$bck = 11;
if($bck>$sl) $bck=$sl;
for($i=$sl-1;$i>$sl-$bck;$i--) {
	$temp = db_geti("errors", $i);
	echo '<li>' . $temp["name"] . " played at " . date("h:i A \o\\n w m/d/Y", $temp["time"]) . '</li>';
}

echo '
</ul>
';

	$d = date('w');
	$h = date('H');
	$month = date('n');
	
	if($d==1) { #monday
		$dayfolder += "/monday/";
	} elseif ($d==2) { #tuesday
		$dayfolder += "/tuesday/";
	} elseif ($d==3) { #wedsnesday
		$dayfolder += "/wedsnesday/";
	} elseif ($d==4) { #thursday
		$dayfolder += "/thursday/";
	} elseif ($d==5) { #friday
		$dayfolder += "/friday/";
	} elseif ($d==6) { #saturday
		$dayfolder += "/saturday/";
	} elseif ($d==0) { #sunday
		$dayfolder += "/sunday/";
	}

	if ($d==5 && $h>=4 && $h<2) {
		#saturday morning cartoon
		$folder =  "cartoons";
	} elseif ($h>=0 && $h<4) {
		$folder =  "movies";
	} elseif ($h>=4 && $h<10) {
		$folder =  "cartoons";
	} elseif ($h>=10 && $h<15) {
		$folder =  "old_reruns";
		$folder2 = "gameshows";
	} elseif ($h>=15 && $h<17) {
		$folder =  "cartoons";
	} elseif ($h==17) {
		#news at 5
		$folder =  "news";
	} elseif ($h>=18 && $h<20) {
		$folder =  "new_reruns";
	} elseif ($h>= 20 && $h<23) {
		$folder =  "primetime" + dayfolder;
	} elseif ($h==23 && (d>=0 && $d<=4)) {
		#latenight monday through friday
		$folder =  "latenight";
	} elseif ($h==23 && $d==5) {
		#saturday night
		$folder =  "latenight/snl";
	} elseif ($h==23 && $d==6) {
		#sunday night
		$folder =  "latenight";
	} else {
		#just in case
		$folder =  "cartoons";
	}

	if ($month==12) {
		#christmas programming
		$folder =  "xmas/" . folder;
		$folder2 = "";
	}

	if ($d==6 && $h>=4 && $h<13) {
		#sunday morning
		$folder =  "specials/sunday_morning";
	} elseif ($d==5 && $h>=7 && $h<=9 && random.randint(1,9)==5) {
		#saturday night, take a chance for a movie of the week!
		$folder =  "specials/movies";
	} elseif ($d==5 && $h>=2 && $h<=3 && random.randint(1,5)==2 && month>=4 && month <=9) {
		#saturday afternoon, in spring/summer, take a chance for a baseball game
		$folder =  "specials/baseball";
	}

echo "<br />$d - $h - $month - $folder<br />";

/*
$csv = file_get_contents('https://docs.google.com/spreadsheets/d/1QADkcJlcQRP1PPGCcgFtUiBjNtF-gjDE1SO4lcrBosk/export?format=csv&id=1QADkcJlcQRP1PPGCcgFtUiBjNtF-gjDE1SO4lcrBosk&gid=0');

$acsv = str_getcsv($csv,  "\n");

for($i=0;$i<count($acsv);$i++) {
	echo [$d]."\n<br />";
}


var_dump($acsv);
*/
echo '
</body>
</html>';

?>