<?php


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="../assets/css/duels.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duels</title>
</head>
<body style = "font-family: 'Poppins', sans-serif;">
    <div class = "container">
        <aside class = "sidebar">
            <a href="./dashboard.php" class="sidebar-button">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            </a>
        </aside>

        <div class = "centerAlign">
            <div class = "pleaseRegister">
                <h1>Please verify your</h1>
                <h1>CodeForces Account first!</h1>
                <button onclick="window.location.href='./verifyForces.php'">Verify Now</button>
            </div>
        </div>
        
    </div>
</body>
</html>