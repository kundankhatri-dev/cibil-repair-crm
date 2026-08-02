<?php
session_start();
echo "Session check: ";
if (isset($_SESSION['user_id'])) {
    echo "User ID: " . $_SESSION['user_id'];
} else {
    echo "Not logged in";
}
?>