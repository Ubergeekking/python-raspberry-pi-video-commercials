<?php
$mysqli = new mysqli("localhost", "pi", "raspberry", "shows");
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}

$mysqli->real_query("SELECT * FROM played ORDER BY id ASC");
$res = $mysqli->use_result();

echo "Result set order...<br/>";
while ($row = $res->fetch_assoc()) {
    echo " id = " . $row['name'] . "<br/>";
}


?>