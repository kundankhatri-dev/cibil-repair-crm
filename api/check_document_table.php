<?php
$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo "DB connection failed";
    exit;
}

$result = mysqli_query($conn, "SHOW TABLES LIKE 'client_documents'");
if (mysqli_num_rows($result) == 0) {
    echo "Table 'client_documents' does not exist";
    exit;
}

$result = mysqli_query($conn, "DESCRIBE client_documents");
echo "Columns in client_documents table:\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . " | " . $row['Type'] . "\n";
}

mysqli_close($conn);
exit;
?>