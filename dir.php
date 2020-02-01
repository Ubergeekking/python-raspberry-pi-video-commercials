<?php
$root = "/media/pi/750gb";

if(isset($_GET["play"])) {
	//echo $root."/".$_GET["play"];
	file_put_contents("/home/pi/Desktop/next.video",$root."/".$_GET["play"]);
	die();
}

function is_in_dir($file, $directory, $recursive = true, $limit = 1000) {
    $directory = realpath($directory);
	echo "dir: $directory\n";
    $parent = $file;
	echo "par: $parent\n";
    $i = 0;
    while ($parent) {
        if ($directory == $parent) return true;
        if ($parent == dirname($parent) || !$recursive) break;
        $parent = dirname($parent);
    }
    return false;
}

$path = null;
if (isset($_GET['file'])) {
    $path = "/".$_GET['file'];
	
}

if (is_file($root.$path)) {
    readfile($root.$path);
    return;
}

echo '
<html>
<head>
<title>Browse Videos '.$path.'</title>
</head>
<body>
';

if ($path) echo '<div style="vertical-align:middle;"><a href="?file='.urlencode(substr(dirname($root.$path), strlen($root) + 1)).'" style="position:relative;top:-5px;font-size:20px;text-decoration:none;"><img src="return.png" style="width:20px;height:20px;" /></a></div>';
echo "\n<!-- $root$path".'/*' . "--!>\n";
foreach (glob($root.$path.'/*') as $file) {
    $file = realpath($file);
    $link = substr($file, strlen($root) + 1);
	if(strpos(strtolower($file), ".commercials")===false) {
		if(is_dir($file)) {
			echo '<div style="vertical-align:middle;"><img src="folder.png" style="width:20px;height:20px;" /> <a href="?file='.urlencode($link).'" style="position:relative;top:-5px;font-size:20px;text-decoration:none;">'.basename($file).'</a></div>';
		} elseif(strpos(strtolower($file), ".avi")==true || strpos(strtolower($file), ".mp4")==true || strpos(strtolower($file), ".mkv")==true || strpos(strtolower($file), ".mov")==true || strpos(strtolower($file), ".mpg")==true || strpos(strtolower($file), ".m4v")==true || strpos(strtolower($file), ".flv")==true || strpos(strtolower($file), ".mpeg")==true) {
			echo '<div style="vertical-align:middle;"><img src="video.png" style="width:20px;height:20px;" /> <a href="?play='.urlencode($link).'" style="position:relative;top:-5px;font-size:20px;text-decoration:none;">'.basename($file).'</a> <a href="http://192.168.1.198/?video='.urlencode($link).'" style="position:relative;top:-5px;font-size:20px;text-decoration:none;">[>]</a></div>';
		}
	}
}

?>

</body>
</html>