<?php
session_start();
include('includes/dbconnection.php');

// Ensure an admin is logged in
if (empty($_SESSION['sturecmsaid'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit();
}

$admin_id = $_SESSION['sturecmsaid'];
header('Content-Type: application/json');

// Endpoint to mark a single notification as read
if (isset($_GET['action']) && $_GET['action'] === 'mark_one_as_read') {
    $data = json_decode(file_get_contents('php://input'), true);
    $notif_id = $data['notif_id'] ?? 0;

    if ($notif_id > 0) {
        $sql_mark_one = "UPDATE tblnotif SET is_read = 1 WHERE recipient_id = ? AND recipient_type = 'admin' AND id = ?";
        $stmt = $dbh->prepare($sql_mark_one);
        if ($stmt->execute([$admin_id, $notif_id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database update failed.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid notification ID.']);
    }
    exit();
}

// Endpoint to mark all notifications as read (if you re-implement this feature)
if (isset($_GET['action']) && $_GET['action'] === 'mark_all_as_read') {
    $sql_mark_all = "UPDATE tblnotif SET is_read = 1 WHERE recipient_id = ? AND recipient_type = 'admin' AND is_read = 0";
    $stmt = $dbh->prepare($sql_mark_all);
    if ($stmt->execute([$admin_id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database update failed.']);
    }
    exit();
}

// Default response if no action is matched
echo json_encode(['success' => false, 'error' => 'No valid action specified.']);
exit();