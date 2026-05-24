<?php
$host = "127.0.0.1";
$user = "root";
$pass = "";
$dbname = "restauracja";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Błąd połączenia z bazą danych: " . $conn->connect_error);
}
?>