<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

$drive_loc="/media/pi/750gb/";

if(isset($_POST["volumeSettings"])) {
	$strVolSet = "";
	foreach($_POST as $k=>$v) {
		if($k!="volumeSettings") {
			$k = str_replace("_-_", ".", $k);
			$k = str_replace("___", " ", $k);
			if($v!="No Change") $strVolSet .= "$k=$v\n";
		}
	}
	
	
	file_put_contents($drive_loc.'volume.list', $strVolSet);
	header("location: /#Settings\n\n");
	die();
	
}

if(isset($_POST["volume"])) {
	file_put_contents($drive_loc . 'volume.list', $_POST["volume"]);
	header("location: /\n\n");
}

if(isset($_GET["reboot"])) {
    header("Location: /\n\n");
	exec('sudo reboot');
}

if(isset($_GET["start"])) {
    header("Location: /\n\n");
	exec('python /home/pi/Desktop/_rnd80s.py');
}


if(isset($_GET["skip"])) {
    //header("Location: /\n\n");
	$result = exec('sudo ./kill.sh');
	die($result);
}

if(isset($_GET["video"])) {
	include("videostream.inc");
	header('Content-type: video/mp4');
	$stream = new VideoStream($drive_loc . $_GET["video"]);
	$stream->start();
	die();
}

if(isset($_GET["delete"])) {
	unlink($drive_loc.$_GET["delete"]);
	die("video deleted");
}

$mysqli = new mysqli("localhost", "pi", "raspberry", "shows");

function getShowNames($url) {
	$murl = md5($url) . ".cache";
	if (file_exists($murl) && (time() - 86400) < filemtime($murl)) {
		return file_get_contents($murl);
	}
	
	try {
		$str = file_get_contents($url);
		file_put_contents($murl, $str);
		return $str;
	} catch(Exception $e) {
		return file_get_contents($murl);
	}
}

$csv = getShowNames('https://docs.google.com/spreadsheets/d/1QADkcJlcQRP1PPGCcgFtUiBjNtF-gjDE1SO4lcrBosk/export?format=csv&id=1QADkcJlcQRP1PPGCcgFtUiBjNtF-gjDE1SO4lcrBosk&gid=0');

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





function parseCSV($csv) {
    $csv = array_map('str_getcsv', explode("\r\n", $csv));
	$narr = [];
	$keys = [];
	
	//create an array of types (reruns, primetime, cartoons, etc)
	//and also an array of keys for each type's name
	for($i=0;$i<count($csv[0]);$i++) {
		$narr[$csv[0][$i]] = [];
		$keys[$i] = $csv[0][$i];
	}

	for($i=1;$i<count($csv);$i++) {
		for($j=0;$j<count($csv[$i]);$j++) {
			if($csv[$i][$j]!="") {
				if(isset($shows[$csv[$i][$j]])) $cnt = $shows[$csv[$i][$j]]; else $cnt = 0;
				array_push($narr[$keys[$j]], $csv[$i][$j]);
			}
		}
	}
	
	return $narr;
}


function getShowType($sname, $csv) {

	foreach($csv as $k=>$v) {
		for($i=0;$i<count($v);$i++) {
			if($sname==$v[$i]) return $k;
		}
	}
	
	return "none";

}


$parsedShows = parseCSV($csv);

if(isset($_GET["getshowname2"])) {

	
	//var_dump($a);

		die("");
	
}

function checkShowPlayAmount($sname, $csv) {
	global $mysqli;

    $csv = array_map('str_getcsv', explode("\r\n", $csv));
	$narr = [];
	$keys = [];
	
	//create an array of types (reruns, primetime, cartoons, etc)
	//and also an array of keys for each type's name
	for($i=0;$i<count($csv[0]);$i++) {
		$narr[$csv[0][$i]] = [];
		$keys[$i] = $csv[0][$i];
	}

	$res = $mysqli->query("SELECT *, COUNT(`short_name`) AS `value_occurrence` FROM `played` GROUP BY `short_name` ORDER BY `value_occurrence` DESC") or die($mysqli->error);
	$shows = [];
	//load each show that has been played and their play count
	while ($brow = $res->fetch_assoc()) {
		$shows[$brow["short_name"]] = $brow["value_occurrence"]*1;
	}

	$selected = null;
	//populate each type with its shows and how many times it has been played
	//and whilst doing so, set the current type so we can compare the played times against only the same type of shows
	for($i=1;$i<count($csv);$i++) {
		for($j=0;$j<count($csv[$i]);$j++) {
			if($csv[$i][$j]!="") {
				if(isset($shows[$csv[$i][$j]])) $cnt = $shows[$csv[$i][$j]]; else $cnt = 0;
				array_push($narr[$keys[$j]], [$csv[$i][$j], $cnt]);
				if($csv[$i][$j] == $sname) {
					$selected = $keys[$j];
				}
			}
		}
	}

	$highest = -1;
	$lowest = 999999;
	$lowshow = "";
	$list = [];
	//shift the most played shows to the top
	//and everything else to the bottom
	for($i=0;$i<count($narr[$selected]);$i++) {
		//if($i>6) break;
		if($narr[$selected][$i][1] >= $highest) {
			$highest = $narr[$selected][$i][1];
			array_unshift($list, $narr[$selected][$i]);
		} else {
			array_push($list, $narr[$selected][$i]);
		}
		
		if($narr[$selected][$i][1] < $lowest) {
			$lowest = $narr[$selected][$i][1];
			$lowshow = $narr[$selected][$i][0];
		}
	}

	//echo "$lowest $lowshow <br />\n";

	//test the first few only
	/*
	for($i=0;$i<100;$i++) {
		if(isset($list[$i])) {
			if($i>6) { 
				unset($list[$i]);
			}
		} else {
			break;
		}
	}

	var_dump($list);
	*/

	$exit=true;

	//check to make sure not every show has been played an equal amount of times
	//by seeing if the highest play count is the same for each show
	for($i=0;$i<count($list);$i++) {
		//echo($list[$i][0] . ", " . $list[$i][1] . ", " . $highest . "\n");
		if($list[$i][1] != $highest) { 
			$exit=false;
		}
	}
	
	//if each show has been played the same amount of times, then play whichever show this one is because it doesn't matter.
	if($exit) return true;

	//see if the current show is in the list of highest shows
	//if it is, it shouldn't be played
	for($i=0;$i<count($list);$i++) {
		if($list[$i][1] != $lowest) { 
			if($sname==$list[$i][0]) {
				return false;
			}
		}
	}

	//play the current show
	return true;
}

if(isset($_GET["getshowname"])) {

//if day = sunday and time > 5am and time < 10am

	$dayofweek = date("l",time());
	$hourofday = date("G",time());

	$shortname=getTvShowName($_GET["getshowname"]);
	$showType=getShowType($shortname, $parsedShows);
	
	$row=0;
	$brow=0;

	if($showType=="Specials" || $showType=="Movies" || $showType=="Primetime") {
		$row=0;
	} else { //not sunday morning
		$time_diff = 7200;
		$row = 0;
		//xmas time, double the time difference
		if(date('n')==12) $time_diff = $time_diff*2;
		$res = $mysqli->query("SELECT played FROM played WHERE short_name='" . addslashes($shortname) . "' AND played<=" . (time()-1) . " AND played>=" . (time()-$time_diff) . "  LIMIT 1") or die($mysqli->error);
		$row = $res->fetch_row()[0]*1;
		
		if(checkShowPlayAmount(getTvShowName($_GET["getshowname"]), $csv)) {
			//play the show
			$row=0;
		} else {
			//show is one of the top of its category, play something else
			if($row==0) {
				$row=time();
			}
		}		
	}
	
	die("$shortname|".$row);//."|".$brow["value_occurrence"]);
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
<link href="data:image/x-icon;base64,AAABAAEAEBAAAAEACABoBQAAFgAAACgAAAAQAAAAIAAAAAEACAAAAAAAAAEAAAAAAAAAAAAAAAEAAAAAAAAAAAAA2tnYAI+MigB/fXsASdG7AEtJSQAkMLsAZGFgAPf39wCvst0AMKTpACg2zABWVFQAN656ABwppwB6d3UAX11cAIZBZwDT2/YAsnWiALiRQgDh4N8AlpORAJ1PeQCQR28AdzdbALR3pQCUl7gAd+j3ACiIWgAysvUA0c/OAHCM8gBN2MMAheD9AKGjyAAumeEAAqvJAGhmZQBDQUIAvLq4ANHZ9QAAw+EAx55IAHFvbQCzt+MA1KhNAEaOhADm5eQAkujwAFdVVQBDxagAesDVAInj/wBgXl0AMazwAN6wUABGREQAL5ZnADu4gwBRT04ASsD5AADW8ACYTHUAlvX+ACw52QBMy7kAT01MAHOP9QDz05MALY/XADOkcgCLc0IAdnRyAEpOYwCTkI4APz4+AO/PjwABt9UAIXdNAJGOjAAAzeoAqazUAEJAQQCkgjkAER+QAI/x+wAuPOMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAATExMTExMTExMTExMAAAAU1MDVUYlT09IMlNTD1MAADlJGw4kTh0dVBk5OTk5AAAFHyMGCio6OhQRNgUoBQAAPAFSCzdRR0crGBA8PDwAADIVCUEePg0NLj8MMjIyAAAQMC1XPRwzOzgXQxAQEAAAAggpICJWBARNEwcmJiYAAFAnEkQ1QCEhRRpQUFAsAABLS0xKNDFCL0xLS0tLSwAAABYWFhYWFhYWFhYWFgAAAAAAAAAAFgAAFgAAAAAAAAAAAAAAABYAABYAAAAAAAAAAAAAABYAAAAAFgAAAAAAAAAAFhYWAAAAABYWFgAAAP//AADAAwAAgAEAAIABAACAAQAAgAEAAIABAACAAQAAgAEAAIABAACAAQAAwAMAAP2/AAD9vwAA+98AAOPHAAA=" rel="icon" type="image/x-icon" />
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

.clr-january { background-color:LemonChiffon; }
.clr-february { background-color:DarkOrange; }
.clr-march { background-color:DeepPink; }
.clr-april { background-color:Gold; }
.clr-may { background-color:LightSalmon; }
.clr-june { background-color:SandyBrown; }
.clr-july { background-color:Brown; }
.clr-august { background-color:Thistle; }
.clr-september { background-color:YellowGreen; }
.clr-october { background-color:Peru; }
.clr-november { background-color:LightGray; }
.clr-december {	background-color:LightSeaGreen; }
.clr-local { background-color:CornflowerBlue; }

</style>
</head>
<body>
';
$the_date = "today";

if(isset($_GET["date"])) {
	$tmp_date = DateTime::createFromFormat('m/d/Y', $_GET["date"]);
	$tmp_date_errors = DateTime::getLastErrors();
 
	if ($tmp_date_errors["warning_count"] + $tmp_date_errors["error_count"]==0) $the_date = $tmp_date->format('m/d/y');
}

echo '
<div class="tab">
	<button class="tablinks"><a href="/?date=' . urlencode(date('m/d/y', strtotime($the_date) - 86400)) . '">&lt;&lt;&lt;</a> | <a href="/">'.date("h:i A \o\\n ", time()) . $days[$td] . date(" m/d/Y", time()).'</a></button>
	<button class="tablinks">Free Space: ' . floor( disk_free_space( $drive_loc ) / ( 1024 * 1024 * 1024 ) ) . 'GB</button>
	<button class="tablinks">Load: ' . (sys_getloadavg()[0]*100) . '%</button>
	<button class="tablinks">Temp: ' . (round((($int_temp/1000) * (9/5))) + 32) . '<sup>&deg;</sup></button>
	<button class="tablinks" style="background-color:crimson;"><a href="/?reboot=now" style="color:white;">Reboot</a></button>
	<button class="tablinks" style="background-color:lightgreen;"><a href="/phpmyadmin" style="color:white">phpMyAdmin</a></button>
	<button class="tablinks" style="background-color:lightblue;"><a href="/?skip=now" style="color:white;">Skip >></a></button>
	<button class="tablinks" style="background-color:orange;"><a href="/dir.php" style="color:red;">Browse Videos</a></button>
</div>

<div class="tab">
  <button id="btnShows" class="tablinks active" onclick="openCity(\'Shows\')">Shows</button>
  <button id="btnCommercials" class="tablinks" onclick="openCity(\'Commercials\')">Commercials</button>
  <button id="btnErrors" class="tablinks" onclick="openCity(\'Errors\')">Errors</button>
  <button id="btnSettings" class="tablinks" onclick="openCity(\'Settings\')">Settings</button>
  <button id="btnStats" class="tablinks" onclick="openCity(\'Stats\')">Stats</button>
</div>

<!-- Tab content -->
<div id="Shows" class="tabcontent" style="display:block;">
  <h3>' . $the_date . '\'s Shows</h3>
<table border=1 cellspacing=1 callpadding=8 width="40%">
<tr style="background:lightgray;"><td align=center>Time</td><td style="padding-left:20px;">Show Name</td><td align=center>Type</td></tr>
  ';
  
  
$showTypeColors = ["Reruns"=>"F8FFA2","Cartoons"=>"A2FFEF","Specials"=>"FFA2A2","Primetime"=>"dd8888","Gameshows"=>"88AAFF","Movies"=>"A2FFAC", "Christmas"=>"A2B9FF","Monday"=>"D5A2FF","Tuesday"=>"F5A2DF","Wednesday"=>"A5F2DF","Thursday"=>"D5F2AF","Friday"=>"F5F2FF","Saturday"=>"F5D2AF","Sunday"=>"A5D2FF","none"=>"fff"];

$shows_cnt = 0;



$res = $mysqli->query("SELECT * FROM played WHERE played>=" . strtotime($the_date .' 00:00') . " AND played<=" . strtotime($the_date . ' 23:59') . "  ORDER BY id DESC") or die($mysqli->error);

while ($row = $res->fetch_assoc()) {
	if(!$row) break;
	$showType = getShowType($row["short_name"], $parsedShows);
	echo '<tr style="background:#'.$showTypeColors[$showType].';"><td align=center>' . date("h:i A", $row["played"]) . '</td><td style="padding-left:20px;"><a href="/?video=' . $row["name"] . '" title="'.strtolower(preg_replace("~[_\W\s]~", '', basename($row["name"]))).'">' . $row["short_name"] . '</a> </td><td align=center>'.$showType.'</td></tr>';
	$shows_cnt++;
}


echo '</table><br />
<br />
' . $shows_cnt . ' shows aired today.<br />

</div>

<div id="Commercials" class="tabcontent">
  <h3>Commercials</h3>
<table border=1 cellspacing=1 callpadding=8 width="25%">
<tr style="background:lightgray;"><td align=center>Time</td><td align=center>Month</td><td style="padding-left:20px;">Commercial</td><td>Delete</td></tr>
';

$comms_cnt = 0;
$bit = 1;

//echo strtotime('today 00:01');
//echo strtotime('today 23:59');

$res = $mysqli->query("SELECT * FROM commercials WHERE played>=" . strtotime($the_date . ' 00:00') . " AND played<=" . strtotime($the_date . ' 23:59') . " ORDER BY id DESC") or die($mysqli->error);
$comm_months = [];


while ($row = $res->fetch_assoc()) {
	//if(date("w", $row["played"])!=$td) break;
	$splits = explode('/', $row["name"]);
	if(array_key_exists($splits[1], $comm_months)==false) { $comm_months[$splits[1]]=1; } else { $comm_months[$splits[1]]++; }
	echo '<tr class="clr-'.$splits[1].'"><td align=center>' . date("h:i&\\nb\\sp;A", $row["played"]) . '</td><td align=center><a href="/commercials.php?folder=' . $splits[1] . '">' . $splits[1] . '</a></td><td style="padding-left:20px;"><a href="/?video=' . $row["name"] . '">' . $splits[2] . '</a></td><td><a href="/?delete=' . $row["name"] . '">[delete]</a></td></tr>';
	$comms_cnt++;
}

echo '</table><br />
<br />
';

foreach($comm_months as $k=>$v) {
	
	echo '<span class="clr-'.$k.'">'."$v commercials from $k (" . ceil(($v / $comms_cnt)*100) . "%)</span><br />\n";

}

echo '
<br />' . $comms_cnt . ' commercials today.<br />
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

	$folder  = "undefined";
	$folder2 = "undefined";
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
		$folder =  "xmas/" . $folder;
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
<table border=1 cellspacing=1 callpadding=8 width="25%">
<form method="POST">
<tr style="background:lightgray;"><td>Show</td><td>Volume Boost</td></tr>
';

$bit = 1;

$afiles = file($drive_loc.'volume.list', FILE_IGNORE_NEW_LINES);

function checkVolumeSetting($sname, $afiles) {
	foreach($afiles as $v) {
		$tmp = explode('=', $v);
		if($tmp[0]==$sname) return $tmp[1];
	}
	return null;
}

$selCount = 0;
foreach($parsedShows as $v) {
	foreach($v as $vv) {
		if($vv!="") {
			$chkv = checkVolumeSetting($vv, $afiles);
			echo '<tr'.($bit==0 ? ' style="background:#eee;"' : '') .'><td>' . $vv . '</td><td><select name="'.str_replace(".", "_-_", str_replace(" ", "___", $vv)).'" id="settingsVolume_'.$selCount.'">'.($chkv!=null ? "<option>$chkv</option>" : '') . '<option>No Change</option><option>1</option><option>2</option><option>3</option></select></td></tr>'."\n";
			$selCount++;
			$bit=($bit==0?1:0);
		}
	}
}

echo '</table><br />
<br />
<input type="submit" name="volumeSettings" value="submit" />
</form>
';

/*
echo '

 
  <p><form method="POST">
<textarea name="volume" style="width:100%; height:100%;">' . file_get_contents($drive_loc.'volume.list') . '</textarea><br />
<input type="submit" /><br />
<br />
'."<br />Day: $d ($dayfolder) - Hour: $h - Month: $month - Folder: $folder<br />".'
	</p>
';
*/

echo '
</div>

';

echo '
<div id="Stats" class="tabcontent">
<table border=1 cellspacing=1 callpadding=8 width="25%">
<tr style="background:lightgray;"><td>Type</td><td>Show Name</td><td>Count</td></tr>
  ';
  
$res = $mysqli->query("SELECT *, COUNT(`short_name`) AS `value_occurrence` FROM `played` GROUP BY `short_name` ORDER BY `value_occurrence` DESC") or die($mysqli->error);

$arrTypes = [];
$arrCount = [];
while ($row = $res->fetch_assoc()) {
	$showType = getShowType($row["short_name"], $parsedShows);
	if(isset($arrTypes[$showType])) {
		$arrCount[$showType] += ($row["value_occurrence"]*1);
		$arrTypes[$showType]++;
	} else {
		$arrCount[$showType] = ($row["value_occurrence"]*1);
		$arrTypes[$showType] = 1;
	}
	echo '<tr style="background:#'.$showTypeColors[$showType].';"><td>' . $showType . '</td><td>' . $row["short_name"] . '</td><td align="center">' . $row["value_occurrence"] . '</td></tr>';
}

echo '</table><br />
<br />
<table border=1 cellspacing=1 callpadding=8 width="25%">
<tr style="background:lightgray;"><td>Type</td><td>Shows</td><td>Count</td></tr>
  ';

arsort($arrTypes);
foreach($arrTypes as $k=>$v) {
	echo '<tr style="background:#'.$showTypeColors[$k].';"><td>' . $k . '</td><td align="center">' . $v . '</td><td align="center">' . $arrCount[$k] . '</td></tr>';
}
  
  echo '</table>
</div>

';


echo '

</body>
</html>';

?>