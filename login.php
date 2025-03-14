<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT name, email, password FROM USERS WHERE email=?");
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($name, $email, $hashed_pw);
        $stmt->fetch();
        if (password_verify($password, $hashed_pw)) {
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            header("Location: dashboard.php");
            exit;
        }
    }
    echo "<script>alert('Invalid credentials.');</script>";
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <link rel = "stylesheet" href = "login.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <div class="form-container">
        <div style="display: flex; flex-direction: row-reverse; justify-content: space-between; align-items: center;">
            <h1>Code<span style="color: #1a4eaf">Keep</span></h1>
            <h1><span style="text-align: left;">Login</span></h1>
        </div>
        <form>
            <div class="form-group">
                <label for="lastName">Enter Email</label>
                <input required type="email" id="lastName" placeholder="Email">
            </div>
            <div class="form-group" style = "padding-bottom: 10px">
                <label for="workEmail">Password</label>
                <input required type="password" id="workEmail" placeholder="Password">
            </div>
            <button type="submit" style = "padding-bottom: 10px">Login</button>
        </form>
    </div>
</body>
</html>

<!-- <h1>Code<span style = "color: #1a4eaf">Keep</span></h1> -->