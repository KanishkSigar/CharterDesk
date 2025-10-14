<?php
// db_connect.php
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "ime_negotiation";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  http_response_code(500);
  echo json_encode(['status'=>'error','message'=>'DB connect error: '.$conn->connect_error]);
  exit;
}
$conn->set_charset("utf8mb4");
?>
