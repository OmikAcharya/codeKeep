<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm-password'] ?? '';

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
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

    if ($password !== $confirm_password) {
        echo "<script>
                alert('Passwords do not match.');
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

    // Check if connection is valid
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check if user already exists
    $stmt = $conn->prepare("SELECT email FROM USERS WHERE email = ?");
    if (!$stmt) {
        echo "<script>
                alert('Database error: " . $conn->error . "');
                window.location.href = 'signup.php';
              </script>";
        exit;
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>
                alert('User already exists! Please login.');
                window.location.href = 'login.php';
              </script>";
        $stmt->close();
        exit;
    } else {
        $stmt->close();
        
        // Insert new user
        $insert_stmt = $conn->prepare("INSERT INTO USERS(name, email, password) VALUES(?, ?, ?)");
        if (!$insert_stmt) {
            echo "<script>
                    alert('Database error: " . $conn->error . "');
                    window.location.href = 'signup.php';
                  </script>";
            exit;
        }
        
        $insert_stmt->bind_param("sss", $name, $email, $password);
        
        if ($insert_stmt->execute()) {
            $insert_stmt->close();
            echo "<script>
                    alert('Account created successfully! Please login.');
                    window.location.href = 'login.php';
                  </script>";
            exit;
        } else {
            // Error
            echo "<script>
                    alert('Error creating account: " . $insert_stmt->error . "');
                    window.location.href = 'signup.php';
                  </script>";
            $insert_stmt->close();
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="login.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup</title>
</head>
<body>
    <div class="form-container">
        <div style="display: flex; flex-direction: row-reverse; justify-content: space-between; align-items: center;">
            <h1>&lt;Code<span style="color: #1a4eaf">Keep&gt;</span></h1>
            <h1><span style="text-align: left;">Create an account</span></h1>
        </div>
        <form action="signup.php" method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="firstName">Enter Name</label>
                    <input required type="text" id="firstName" placeholder="Name" name="name">
                </div>
                <div class="form-group">
                    <label for="email">Enter Email</label>
                    <input required type="email" id="email" placeholder="Email" name="email">
                </div>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input required type="password" id="password" placeholder="Password" name="password">
            </div>
            <div class="form-group">
                <label for="confirm-password">Confirm Password</label>
                <input required type="password" id="confirm-password" placeholder="Re-enter Password" name="confirm-password">
            </div>
            <div class="checkbox-container">
                <input required type="checkbox" id="terms" name="terms">
                <label for="terms" class="checkbox-label">I accept the <a href="#">Terms and Conditions</a></label>
            </div>
            <button type="submit">Create an account</button>
            <div style="display: flex; justify-content: center; margin-top: 10px; gap: 5px;">Already have an account?   <a href="./login.php">Login</a></div>
        </form>
    </div>
</body>
</html>