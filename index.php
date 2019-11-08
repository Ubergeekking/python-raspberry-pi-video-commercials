<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

$drive_loc="/media/pi/750gb/";

if(isset($_POST["volume"])) {
	file_put_contents($drive_loc . 'volume.list', $_POST["volume"]);
	header("location: /\n\n");
}

if(isset($_GET["reboot"])) {
    header("Location: /\n\n");
	exec('sudo reboot');
}


if(isset($_GET["skip"])) {
    //header("Location: /\n\n");
	$result = exec('sudo ./kill.sh');
	die($result);
}

if(isset($_GET["video"])) {
	include("videostream.inc");
	$stream = new VideoStream($drive_loc . $_GET["video"]);
	$stream->start();
	die();
}

if(isset($_GET["delete"])) {
	unlink($drive_loc.$_GET["delete"]);
	header("Location: /\n\n");
	die();
}


$mysqli = new mysqli("localhost", "pi", "raspberry", "shows");

$csv = file_get_contents('https://docs.google.com/spreadsheets/d/1QADkcJlcQRP1PPGCcgFtUiBjNtF-gjDE1SO4lcrBosk/export?format=csv&id=1QADkcJlcQRP1PPGCcgFtUiBjNtF-gjDE1SO4lcrBosk&gid=0');

$acsv = str_getcsv(str_replace("\r\n", ",", $csv));

function getTvShowName($filename) {
	global $acsv;
	$afilename = strtolower(preg_replace("~[_\W\s]~", '', basename($filename)));
	for($i=13;$i<count($acsv);$i++) {
		if($acsv[$i]!="") {
			$tmp = strtolower(preg_replace("~[_\W\s]~", '', $acsv[$i]));
			if(strpos("a" . $afilename, $tmp)>0) return $acsv[$i];
		}
	}
	return $filename;
}


if(isset($_GET["getshowname2"])) {
	die(getTvShowName($_GET["getshowname"]));
}

if(isset($_GET["getshowname"])) {

//if day = sunday and time > 5am and time < 10am

	$dayofweek = date("l",time());
	$hourofday = date("G",time());

	$shortname=getTvShowName($_GET["getshowname"]);

	if($dayofweek=="Sunday" && $hourofday>=5 && $hourofday<=10) { //sunday morning, ignore replays
		$row=0;
	} elseif($shortname=="Mr Wizard") {
		$row=0;
	} else { //not sunday morning
		$res = $mysqli->query("SELECT played FROM played WHERE short_name='" . addslashes($shortname) . "' AND played<=" . (time()-1) . " AND played>=" . (time()-7200) . "  LIMIT 1") or die($mysqli->error);
		$row = $res->fetch_row()[0]*1;
	}
	
	die("$shortname|".$row);
}

$mntcont = strlen($drive_loc);

if(isset($_GET["current_video"])) {
	$mysqli->real_query("INSERT INTO played (short_name, name, played) VALUES ('" . addslashes(getTvShowName($_GET["current_video"])) . "', '" . addslashes(substr($_GET["current_video"], $mntcont)) . "', ". time() . ")");
	
	die(1);
}

if(isset($_GET["error"])) {
	$mysqli->real_query("INSERT INTO errors (name, played) VALUES ('" . addslashes($_GET["error"]) . "', ". time() . ")");
	
	die(1);
}

if(isset($_GET["current_comm"])) {
	$mysqli->real_query("INSERT INTO commercials (name, played) VALUES ('" . addslashes(substr($_GET["current_comm"], $mntcont)) . "', ". time() . ")");
	
	die(1);
}


$days=array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
$td = date("w", time());

	$f = fopen("/sys/class/thermal/thermal_zone0/temp","r");
	$int_temp = fgets($f);
	fclose($f);
	

echo '
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>TV Station</title>
<script type="text/javascript">
function openCity(cityName) {
  // Declare all variables
  var i, tabcontent, tablinks;

  // Get all elements with class="tabcontent" and hide them
  tabcontent = document.getElementsByClassName("tabcontent");
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }

  // Get all elements with class="tablinks" and remove the class "active"
  tablinks = document.getElementsByClassName("tablinks");
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].className = tablinks[i].className.replace(" active", "");
  }

  // Show the current tab, and add an "active" class to the button that opened the tab
  document.getElementById(cityName).style.display = "block";
  document.getElementById("btn" + cityName).className += " active";
  
	hash = cityName
	var node = document.getElementById(hash);
	node.id = "";
	document.location.hash = hash;
	node.id = hash;
}


window.onload = function() {
	if(window.location.hash) {
		openCity(window.location.hash.substr(1));
	}
}
</script>
<style type="text/css">
/* Style the tab */
.tab {
  overflow: hidden;
  border: 1px solid #ccc;
  background-color: #f1f1f1;
}

/* Style the buttons that are used to open the tab content */
.tab button {
  background-color: inherit;
  float: left;
  border: none;
  outline: none;
  cursor: pointer;
  padding: 14px 16px;
  transition: 0.3s;
}

/* Change background color of buttons on hover */
.tab button:hover {
  background-color: #ddd;
}

/* Create an active/current tablink class */
.tab button.active {
  background-color: #ccc;
}

/* Style the tab content */
.tabcontent {
  display: none;
  padding: 6px 12px;
  border: 1px solid #ccc;
  border-top: none;
}
</style>
</head>
<body>

<div class="tab">
	<button class="tablinks">' . date("h:i A \o\\n ", time()) . $days[$td] . date(" m/d/Y", time()) .'</button>
	<button class="tablinks">Free Space: ' . floor( disk_free_space( $drive_loc ) / ( 1024 * 1024 * 1024 ) ) . 'GB</button>
	<button class="tablinks">Load: ' . (sys_getloadavg()[0]*100) . '%</button>
	<button class="tablinks">Temp: ' . (round((($int_temp/1000) * (9/5))) + 32) . '<sup>&deg;</sup></button>
	<button class="tablinks" style="background-color:crimson;"><a href="/?reboot=now" style="color:white;">Reboot</a></button>
	<button class="tablinks" style="background-color:lightgreen;"><a href="/phpmyadmin" style="color:white">phpMyAdmin</a></button>
	<button class="tablinks" style="background-color:lightblue;"><a href="/?skip=now" style="color:white;">Skip >></a></button>
</div>

<div class="tab">
  <button id="btnShows" class="tablinks active" onclick="openCity(\'Shows\')">Shows</button>
  <button id="btnCommercials" class="tablinks" onclick="openCity(\'Commercials\')">Commercials</button>
  <button id="btnErrors" class="tablinks" onclick="openCity(\'Errors\')">Errors</button>
  <button id="btnSettings" class="tablinks" onclick="openCity(\'Settings\')">Settings</button>
</div>

<!-- Tab content -->
<div id="Shows" class="tabcontent" style="display:block;">
  <h3>Today\'s Shows</h3>
  <p>';

$shows_cnt = 0;

$res = $mysqli->query("SELECT * FROM played WHERE played>=" . strtotime('today 00:00') . " AND played<=" . strtotime('today 23:59') . "  ORDER BY id DESC") or die($mysqli->error);

while ($row = $res->fetch_assoc()) {
	if(!$row) break;
	if(date("w", $row['played'])!=$td) break;
	echo '<li><a href="/?video=' . $row["name"] . '" title="'.strtolower(preg_replace("~[_\W\s]~", '', basename($row["name"]))).'">' . getTvShowName($row["name"]) . "</a> played at " . date("h:i A \o\\n ", $row["played"]) . $days[date("w", $row["played"])] . date(" m/d/Y", $row["played"]) . '</li>';
	$shows_cnt++;
}


echo '
' . $shows_cnt . ' shows aired today.<br />
</p>
</div>

<div id="Commercials" class="tabcontent">
  <h3>Commercials</h3>
  <p>';

$comms_cnt = 0;


//echo strtotime('today 00:01');
//echo strtotime('today 23:59');

$res = $mysqli->query("SELECT * FROM commercials WHERE played>=" . strtotime('today 00:00') . " AND played<=" . strtotime('today 23:59') . " ORDER BY id DESC") or die($mysqli->error);

while ($row = $res->fetch_assoc()) {
	//if(date("w", $row["played"])!=$td) break;
	echo '<li><a href="/?video=' . $row["name"] . '">' . $row["name"] . "</a> played at " . date("h:i A \o\\n w m/d/Y", $row["played"]) . ' <a href="/?delete=' . $row["name"] . '">[delete]</a></li>';
	$comms_cnt++;
}

echo '
' . $comms_cnt . ' commercials today.<br />
</p>
</div>

<div id="Errors" class="tabcontent">
  <h3>Errors</h3>
  <p>';

$res = $mysqli->query("SELECT * FROM errors ORDER BY id DESC LIMIT 10") or die($mysqli->error);

while ($row = $res->fetch_assoc()) {
	//if(date("w", $row["played"])!=$td) break;
	echo '<li>' . $row["name"] . " at " . date("h:i A \o\\n w m/d/Y", $row["played"]) . '</li>';
}

echo '
</p>
</div>
';

	$folder = "undefined";
	$d = date('N');
	$h = date('H');
	$month = date('n');
	
	$dayfolder = "";
	
	if($d==1) { #monday
		$dayfolder .= "/monday/";
	} elseif ($d==2) { #tuesday
		$dayfolder .= "/tuesday/";
	} elseif ($d==3) { #wedsnesday
		$dayfolder .= "/wedsnesday/";
	} elseif ($d==4) { #thursday
		$dayfolder .= "/thursday/";
	} elseif ($d==5) { #friday
		$dayfolder .= "/friday/";
	} elseif ($d==6) { #saturday
		$dayfolder .= "/saturday/";
	} elseif ($d==0) { #sunday
		$dayfolder .= "/sunday/";
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
		$folder =  "primetime" . $dayfolder;
	} elseif ($h==23 && ($d>=1 && $d<=5)) {
		#latenight monday through friday
		$folder =  "latenight - $d";
	} elseif ($h==23 && $d==6) {
		#saturday night
		$folder =  "latenight/snl";
	} elseif ($h==23 && $d==7) {
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

	if ($d==6 && $h>=4 && $h<10) {
		#sunday morning
		$folder =  "specials/sunday_morning";
	}



//var_dump($acsv);

echo '
<div id="Settings" class="tabcontent">
  <h3>Volume</h3>
  <p><form method="POST">
<textarea name="volume" style="width:100%; height:100%;">' . file_get_contents($drive_loc.'volume.list') . '</textarea><br />
<input type="submit" /><br />
<br />
'."<br />Day: $d ($dayfolder) - Hour: $h - Month: $month - Folder: $folder<br />".'
	</p>
</div>

';

$res = $mysqli->query("SELECT *, COUNT(`short_name`) AS `value_occurrence` FROM `played` GROUP BY `short_name` ORDER BY `value_occurrence` DESC LIMIT 20") or die($mysqli->error);

while ($row = $res->fetch_assoc()) {
	echo '<li>' . $row["short_name"] . ' - ' . $row["value_occurrence"] . '</li>';
}

echo '

</body>
</html>';

?>