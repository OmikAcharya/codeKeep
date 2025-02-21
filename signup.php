<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        echo "<script>
                alert('All fields are required.');
                window.location.href = 'signup.php';
              </script>";
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>
                alert('Invalid email format.');
                window.location.href = 'signup.php';
              </script>";
        exit;
    }

    if (strlen($password) < 6) {
        echo "<script>
                alert('Password must be at least 6 characters long.');
                window.location.href = 'signup.php';
              </script>";
        exit;
    }

    $password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("SELECT email FROM USERS WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>
                alert('User already exists! Please login.');
                window.location.href = 'login.php';
              </script>";
        exit;
    } else {
        $stmt = $conn->prepare("INSERT INTO USERS(name, email, password) VALUES(?,?,?)");
        $stmt->bind_param("sss", $name, $email, $password);
        $stmt->execute();
        header("Location: login.php");
        exit;
    }
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
    <form class = "container" action="signup.php" method="POST"> 
        <img src="https://picsum.photos/1600/1200" alt="bg" />
        <div class = "login">
            <div class="box">
                <h1>Code<span style = "color: #1a4eaf">Keep</span></h1>
                <div class="input-form">
                    <input type="text" name="name" required=true>
                    <label style="color: white;">
                        <span style="transition-delay:0ms">N</span><span style="transition-delay:50ms">a</span><span style="transition-delay:100ms">m</span><span style="transition-delay:150ms">e</span>
                    </label>
                </div>
                <div class="input-form">
                    <input type="email" name="email" required=true>
                    <label style="color: white;">
                        <span style="transition-delay:0ms">E</span><span style="transition-delay:50ms">m</span><span style="transition-delay:100ms">a</span><span style="transition-delay:150ms">i</span><span style="transition-delay:200ms">l</span>
                    </label></label>
                </div>
                <div class="input-form">
                    <input type="password" name="password" required=true>
                    <label style="color: white;">
                        <span style="transition-delay:0ms">P</span><span style="transition-delay:50ms">a</span><span style="transition-delay:100ms">s</span><span style="transition-delay:150ms">s</span><span style="transition-delay:200ms">w</span><span style="transition-delay:250ms">o</span><span style="transition-delay:300ms">r</span><span style="transition-delay:350ms">d</span>
                    </label>
                </div>
                <button class="button">
                    Signup
                </button>
                <p style="padding: 12px 0;">Already have an account?</p>
                <p><a href="login.php" style="color: #1a4eaf; text-decoration: none; cursor: pointer;">Login</a></p>
  
            </div>
        </div>
    </form>
</body>
</html>