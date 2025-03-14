<!-- <div?php
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
?> -->

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel = "stylesheet" href = "login.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup</title>
</head>
<body>
    <div class="form-container">
        <div style="display: flex; flex-direction: row-reverse; justify-content: space-between; align-items: center;">
            <h1>&lt;Code<span style="color: #1a4eaf">Keep&gt;</span></h1>
            <h1><span style="text-align: left;">Create a account</span></h1>
        </div>
        <form>
            <div class="form-row">
                <div class="form-group">
                    <label for="firstName">Enter Name</label>
                    <input required type="text" id="firstName" placeholder="Name">
                </div>
                <div class="form-group">
                    <label for="lastName">Enter Email</label>
                    <input required type="email" id="lastName" placeholder="Email">
                </div>
            </div>
            <div class="form-group">
                <label for="workEmail">Password</label>
                <input required type="password" id="workEmail" placeholder="Password">
            </div>
            <div class="form-group">
                <label for="workEmail">Confirm Password</label>
                <input required type="password" id="workEmail" placeholder="Re-enter Password">
            </div>
            <div class="checkbox-container">
                <input required type="checkbox" id="terms">
                <label for="terms" class="checkbox-label">I accept the <a href="#">Terms and Conditions</a></label>
            </div>
            <button type="submit">Create an account</button>
            <div style="display: flex; justify-content: center; margin-top: 10px; gap: 5px;">Already have an account?   <a href="./login.php">Login</a></div>

        </form>
    </div>
</body>
</html>

<!-- <h1>Code<span style = "color: #1a4eaf">Keep</span></h1> -->
