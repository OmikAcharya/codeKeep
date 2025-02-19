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
    <div class = "decorDarkBlue"></div>
    <div class = "decorLightBlue"></div>
    <form class = "container" action="login.php" method="POST"> 
        <img src="https://picsum.photos/1600/1200" alt="bg" />
        <div class = "login">
            <div class="box">
                <h1>Code<span style = "color: #1a4eaf">Keep</span></h1>
                <div class="input-form">
                    <input type="email" name="email" required>
                    <label style="color: white;">
                        <span style="transition-delay:0ms">E</span><span style="transition-delay:50ms">m</span><span style="transition-delay:100ms">a</span><span style="transition-delay:150ms">i</span><span style="transition-delay:200ms">l</span>
                    </label>
                </div>
                <div class="input-form">
                    <input type="password" name="password" required=true>
                    <label style="color: white;">
                        <span style="transition-delay:0ms">P</span><span style="transition-delay:50ms">a</span><span style="transition-delay:100ms">s</span><span style="transition-delay:150ms">s</span><span style="transition-delay:200ms">w</span><span style="transition-delay:250ms">o</span><span style="transition-delay:300ms">r</span><span style="transition-delay:350ms">d</span>
                    </label>
                </div>
                <button class="button">
                    Login
                </button>
                <p style="padding: 12px 0;">Don't have an account?</p>
                <p><a href="signup.php" style="color: #1a4eaf; text-decoration: none; cursor: pointer;">Sign Up</a></p>
  
            </div>
        </div>
    </form>
</body>
</html>