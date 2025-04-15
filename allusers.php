<?php
require 'config.php';
$sql = "SELECT name, email FROM users";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="login.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Users</title>
</head>
<body>
    <div class="decorDarkBlue"></div>
    <div class="decorLightBlue"></div>
    <div class="container" style="display: flex; height: 100vh; justify-content: center; align-items: center;">
        <div class="login">
            <div class="box">
                <h1>Code<span style="color: #1a4eaf">Keep</span></h1>
                <h2>All Users</h2>
                <table border="1">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                    </tr>
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr><td>" . $row["name"]. "</td><td>" . $row["email"]. "</td><td>";
                        }
                    } else {
                        echo "<tr><td colspan='3'>No users found</td></tr>";
                    }
                    $conn->close();
                    ?>
                </table>
            </div>
        </div>
    </div>
</body>
</html>