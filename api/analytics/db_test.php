<?php
$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if ($conn) {
    echo "Database connected successfully";
} else {
    echo "Connection failed: " . mysqli_connect_error();
}
?>