<?php
session_start();
session_unset();
session_destroy();
header("Location: index.php");
exit;
?>



<?php
session_start();

$_SESSION["username"] = "Om_Thanage";
$_SESSION["user_id"] = 12345;
$_SESSION["is_admin"] = true;

function getAllSessionVariables() {
    echo "<h3>All Session Variables:</h3>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
}

getAllSessionVariables();
?>




