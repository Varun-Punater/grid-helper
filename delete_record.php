<?php
session_start();

// Database connection details
$host = "";
$user = "";
$pass = "";
$db = "";

// Establish MySQL Connection.
$mysqli = new mysqli($host, $user, $pass, $db);

// Check for any Connection Errors.
if($mysqli->connect_errno) {
    echo $mysqli->connect_error;
    exit();
}

// Delete the record
if(isset($_SESSION['last_inserted_id'])) {
    $lastId = $_SESSION['last_inserted_id'];

    $stmt = $mysqli->prepare("DELETE FROM fandom WHERE id = ?");
    $stmt->bind_param("i", $lastId);
    $stmt->execute();
    $stmt->close();

    // Clear the session variable
    unset($_SESSION['last_inserted_id']);
}

$mysqli->close();

// Redirect back to the main page or handle as needed
header("Location: fandom.php");
exit();
?>
