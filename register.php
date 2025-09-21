<?php
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
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // 🔎 First check if username or email already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $checkStmt->bind_param("ss", $username, $email);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Username or Email already exists! Please choose another.'); window.location.href='register.html';</script>";
    } else {
        // ✅ Safe to insert
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hashedPassword);

        if ($stmt->execute()) {
            echo "<script>alert('Registration Successful!'); window.location.href='login.html';</script>";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    $checkStmt->close();
}

$conn->close();
?>
