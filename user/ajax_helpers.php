<?php
session_start();
include('includes/dbconnection.php');

// Ensure a user is logged in
if (empty($_SESSION['sturecmsnumber'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit();
}

$patient_id = $_SESSION['sturecmsnumber'];
header('Content-Type: application/json');

// Endpoint to mark notifications as read
if (isset($_GET['action']) && $_GET['action'] === 'mark_as_read') {
    $data = json_decode(file_get_contents('php://input'), true);
    $notif_ids = $data['ids'] ?? [];

    if (!empty($notif_ids) && is_array($notif_ids)) {
        $placeholders = implode(',', array_fill(0, count($notif_ids), '?'));
        $sql_mark_read = "UPDATE tblnotif SET is_read = 1 WHERE recipient_id = ? AND recipient_type = 'patient' AND id IN ($placeholders)";
        $stmt = $dbh->prepare($sql_mark_read);
        
        $params = array_merge([$patient_id], $notif_ids);
        $stmt->execute($params);
        echo json_encode(['success' => true]);
    }
    exit();
}
?>