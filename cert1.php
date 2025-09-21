<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "certificate_store";

$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$message = "";

 
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT filename FROM cert1s WHERE id=? AND user_id=? LIMIT 1");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $stmt->bind_result($filename);
    if ($stmt->fetch()) {
        $filePath = __DIR__ . "/cert1s/" . $filename;
        if (file_exists($filePath)) unlink($filePath);
    }
    $stmt->close();
    $stmt = $conn->prepare("DELETE FROM cert1s WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: cert1.php");
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'download' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT filename FROM cert1s WHERE id=? AND user_id=? LIMIT 1");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $stmt->bind_result($filename);
    if ($stmt->fetch()) {
        $filePath = __DIR__ . "/cert1s/" . $filename;
        if (file_exists($filePath)) {
            header("Content-Description: File Transfer");
            header("Content-Type: application/octet-stream");
            header("Content-Disposition: attachment; filename=" . basename($filePath));
            header("Content-Length: " . filesize($filePath));
            readfile($filePath);
            exit;
        }
    }
    $stmt->close();
    die("File not found or no permission.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photos'])) {
    $uploadDir = __DIR__ . "/cert1s/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    foreach ($_FILES['photos']['tmp_name'] as $key => $tmpName) {
        if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
            $originalName = basename($_FILES['photos']['name'][$key]);
            $ext = pathinfo($originalName, PATHINFO_EXTENSION);
            $newName = uniqid("img_") . "." . strtolower($ext);
            $targetPath = $uploadDir . $newName;

            if (move_uploaded_file($tmpName, $targetPath)) {
                $stmt = $conn->prepare("INSERT INTO cert1s (user_id, filename) VALUES (?, ?)");
                $stmt->bind_param("is", $user_id, $newName);
                $stmt->execute();
                $stmt->close();
                $message = "Upload Successful!";
            } else {
                $message = "Failed to upload file.";
            }
        }
    }
}

$stmt = $conn->prepare("SELECT id, filename, uploaded_at FROM cert1s WHERE user_id=? ORDER BY uploaded_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="ta">
<head>
<meta charset="UTF-8">
<title>Other Certificate One</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<style>

:root {
    --primary: #4f46e5;
    --primary-hover: #08ff6fff;
    --danger: #ef4444;
    --danger-hover: #f2c253ff;
    --bg: #f3f4f6;
    --card: #ffffff;
    --text: #111827;
    --shadow: rgba(0, 0, 0, 0.1);
}
* { 
    box-sizing: border-box; 
    margin:0; padding:0; 
}
body { 
    font-family: 'Roboto', sans-serif; 
    background: var(--bg); 
    color: var(--text); 
}

.container { 
  max-width: 1000px; 
  margin: 50px auto; 
  background: var(--card); 
  padding: 40px; 
  border-radius: 16px; 
  box-shadow: 0 10px 25px var(--shadow); 
}
h1, h2 {
   margin-bottom: 25px; 
   font-weight: 500; 
}
p.message { 
  padding: 12px; 
  background: #d1fae5; 
  color: #065f46; 
  border-radius: 8px;
  margin-bottom: 20px; 
}

form { 
  display: flex; 
  flex-wrap: wrap; 
  gap: 12px; 
  margin-bottom: 35px; 
}
input[type="file"] { 
  flex: 1; 
  padding: 8px; 
  border-radius: 8px; 
  border: 1px solid #ccc; 
}
.btn { 
  padding: 10px 22px; 
  background: var(--primary); 
  color: #fff; border: none; 
  border-radius: 10px; 
  cursor: pointer; 
  text-decoration: none; 
  font-weight: 500; 
  transition: all 0.3s ease; 
}
.btn:hover { 
  background: var(--primary-hover); 
}
.btn.ghost { 
  background: var(--danger); 
}
.btn.ghost:hover { 
  background: var(--danger-hover); 
}

.preview-grid { 
  display: grid; 
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
  gap: 25px; 
}
.preview-card { 
  background: var(--card); 
  border-radius: 14px; 
  overflow: hidden; 
  position: relative; 
  box-shadow: 0 4px 20px var(--shadow); 
  transition: transform 0.3s ease, box-shadow 0.3s ease; 
  display: flex; 
  flex-direction: column; 
}
.preview-card:hover { 
  transform: translateY(-5px); 
  box-shadow: 0 10px 30px var(--shadow); 
}
.preview-card img { 
  width: 100%; 
  height: 160px; 
  object-fit: cover; 
  transition: transform 0.3s ease; 
}
.preview-card:hover img { 
  transform: scale(1.05); 
}
.file-info { 
  padding: 12px 15px; 
  flex: 1; 
  display: flex; 
  flex-direction: column; 
  justify-content: space-between; 
}
.file-info strong { 
  display: block; 
  font-size: 15px; 
  margin-bottom: 5px; 
  color: #1f2937; 
}
.file-info span { 
  font-size: 13px; 
  color: #6b7280; 
}
.actions { 
  display: flex; 
  justify-content: space-between; 
  padding: 0 15px 15px 15px;
  gap: 10px; 
}
.actions a { 
  flex: 1; 
  text-align: center; 
}

@media (max-width:600px){ .preview-grid { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<div class="container">
 <center> <h2><..Other Certificate One Gallery..></h2> </center>

  <?php if ($message): ?>
    <p class="message"><?php echo $message; ?></p>
  <?php endif; ?>

 
  <form action="cert1.php" method="post" enctype="multipart/form-data">
      <input type="file" name="photos[]" multiple required>
      <button type="submit" class="btn">Upload</button>
  </form>

  <h3>Other Certificate One Upload Photos.</h3><br>
  <div class="preview-grid">
    <?php while ($row = $result->fetch_assoc()): ?>
      <div class="preview-card">
        <img src="cert1s/<?php echo htmlspecialchars($row['filename']); ?>" alt="photo">
        <div class="file-info">
          <strong><?php echo htmlspecialchars($row['filename']); ?></strong>
          <span><?php echo $row['uploaded_at']; ?></span>
        </div>
        <div class="actions">
          <a class="btn" href="cert1.php?action=download&id=<?php echo $row['id']; ?>">Download</a>
          <a class="btn ghost" href="cert1.php?action=delete&id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</div>
</body>
</html>
