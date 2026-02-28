<?php
session_start();
include('config/db.php');

// Set header for JSON response
header('Content-Type: application/json');

// Check if service_id is provided
if (!isset($_GET['service_id']) || empty($_GET['service_id'])) {
    echo json_encode(['success' => false, 'message' => 'Service ID is required']);
    exit();
}

$service_id = intval($_GET['service_id']);

// Fetch subcategories where parent_id matches the selected service
$sql = "SELECT id, name FROM services WHERE parent_id = ? AND is_active = 1 ORDER BY name ASC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $service_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$subcategories = [];
while ($row = mysqli_fetch_assoc($result)) {
    $subcategories[] = $row;
}

mysqli_stmt_close($stmt);

echo json_encode(['success' => true, 'data' => $subcategories]);
?>