<?php
session_start();


$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "certificate_store";

$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

   
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username=? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashedPassword);
        $stmt->fetch();

        if (password_verify($password, $hashedPassword)) {
            
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;

            echo "<script>alert('Login Successful!'); window.location.href='index.html';</script>";
        } else {
            echo "<script>alert('Invalid Password!'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('No user found!'); window.history.back();</script>";
    }

    $stmt->close();
}
$conn->close();
?>
