<link rel="stylesheet" href="css/header.css">
<link rel="stylesheet" href="css/responsive.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="css/notification.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <meta charset="utf-8">
<header class="admin-header">
    <?php
    // Determine the page title and description based on the current script
    $currentPage = basename($_SERVER['PHP_SELF']);
    $pageTitle = 'Dashboard'; // Default title
    $pageDescription = 'Welcome back, manage your dental practice'; // Default description
    
    switch ($currentPage) {
        case 'dashboard.php':
            $pageTitle = 'Dashboard';
            $pageDescription = 'Welcome back, manage your dental practice';
            break;
        case 'mac.php':
        case 'mas.php':
            $pageTitle = 'Appointments';
            $pageDescription = 'Manage consultation and service appointments';
            break;
        case 'manage-patient.php':
        case 'add-patient.php':
        case 'edit-patient-detail.php':
        case 'view-ph.php':
        case 'view-er.php':
            $pageTitle = 'Patients';
            $pageDescription = 'Manage your patient records and information';
            break;
        case 'manage-service.php':
        case 'add-service.php':
        case 'edit-service.php':
            $pageTitle = 'Services';
            $pageDescription = 'Manage your dental services';
            break;
        case 'manage-inventory.php':
            $pageTitle = 'Inventory';
            $pageDescription = 'Track and manage your product inventory';
            break;
        case 'calendar.php':
            $pageTitle = 'Calendar';
            $pageDescription = 'View and manage schedule availability';
            break;
        case 'manage-reviews.php':
            $pageTitle = 'Feedback';
            $pageDescription = 'Review patient feedback and ratings';
            break;
        case 'profile.php':
        case 'change-password.php':
            $pageTitle = 'Settings';
            $pageDescription = 'Manage your administrator profile and settings';
            break;
    }

    $aid = $_SESSION['sturecmsaid'];
    $sql = "SELECT * from tbladmin where ID=:aid";

    $query = $dbh->prepare($sql);
    $query->bindParam(':aid', $aid, PDO::PARAM_STR);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_OBJ);

    $adminName = 'Admin'; // Default
    $adminEmail = '';
    if ($query->rowCount() > 0) {
        $admin_row = $results[0];
        $adminName = $admin_row->AdminName;
        $adminEmail = $admin_row->Email;
        $adminImage = '';
        // Check if image is set and the file exists
        if (!empty($admin_row->image) && file_exists('images/' . $admin_row->image)) {
            $adminImage = 'images/' . $admin_row->image;
        }
    }

    ?>
    <div class="header-left">
        <button class="mobile-nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
            <i class="fas fa-bars"></i>
        </button>
        <div class="logo-container">
            <img src="images/Jf logo.png" alt="Logo" class="logo-img">
            <div class="logo-text-group">
                <span class="main">JF Dental Care</span>
                <span class="sub">Admin Panel</span>
            </div>
        </div>
        <div class="dashboard-info">
            <h1><?php echo htmlentities($pageTitle); ?></h1>
            <p><?php echo htmlentities($pageDescription); ?></p>
        </div>
    </div>

    <div class="header-right">
        <?php
            // --- Check for inventory alerts and create notifications if needed ---
            $admin_id_for_notif = $_SESSION['sturecmsaid'];
            $low_stock_threshold = 2;

            // 1. Find items that are low or out of stock
            $sql_stock_check = "SELECT name, quantity FROM tblinventory WHERE quantity <= :threshold";
            $query_stock_check = $dbh->prepare($sql_stock_check);
            $query_stock_check->bindParam(':threshold', $low_stock_threshold, PDO::PARAM_INT);
            $query_stock_check->execute();
            $problem_items = $query_stock_check->fetchAll(PDO::FETCH_OBJ);

            if ($problem_items) {
                foreach ($problem_items as $item) {
                    $message = '';
                    if ($item->quantity == 0) {
                        $message = "Item '" . htmlentities($item->name) . "' is out of stock.";
                    } else {
                        $message = "Item '" . htmlentities($item->name) . "' is running low on stock (" . $item->quantity . ").";
                    }

                    // 2. Check if a similar unread notification already exists to avoid duplicates
                    $sql_check_notif = "SELECT COUNT(*) FROM tblnotif WHERE recipient_id = :rid AND recipient_type = 'admin' AND message = :msg AND is_read = 0";
                    $query_check_notif = $dbh->prepare($sql_check_notif);
                    $query_check_notif->execute([':rid' => $admin_id_for_notif, ':msg' => $message]);
                    $existing_notif_count = $query_check_notif->fetchColumn();

                    // 3. If no similar unread notification exists, create one
                    if ($existing_notif_count == 0) {
                        $sql_insert_notif = "INSERT INTO tblnotif (recipient_id, recipient_type, message, url) VALUES (:rid, 'admin', :msg, 'manage-inventory.php')";
                        $dbh->prepare($sql_insert_notif)->execute([':rid' => $admin_id_for_notif, ':msg' => $message]);
                    }
                }
            }
        ?>
        <a href="javascript:void(0)" id="notifIcon" class="icon-button notif-icon" aria-label="Notifications" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-bell"></i>
            <?php
            // Fetch ALL notifications from tblnotif
            $admin_id = $_SESSION['sturecmsaid'];
            $sql_notif = "SELECT id, message, url, created_at, is_read FROM tblnotif WHERE recipient_id = :admin_id AND recipient_type = 'admin' ORDER BY created_at DESC";
            $query_notif = $dbh->prepare($sql_notif);
            $query_notif->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
            $query_notif->execute();
            $all_notifications = $query_notif->fetchAll(PDO::FETCH_ASSOC);

            // Calculate unread count for the badge
            $notif_count = 0;
            foreach ($all_notifications as $notification) {
                if ($notification['is_read'] == 0) {
                    $notif_count++;
                }
            }


            // Prepare notifications for display
            $grouped_notifications = [
                'Today' => [],
                'This Week' => [],
                'This Month' => [],
                'Older' => [],
            ];

            $now = new DateTime();
            $today_start = new DateTime('today');
            $week_start = new DateTime('today - 7 days');
            $month_start = new DateTime('today - 30 days');

            foreach ($all_notifications as &$notification) { // Use reference to modify the original array
                $notif_date = new DateTime($notification['created_at']);
                $notification['text'] = $notification['message'];
                $notification['time'] = date('M d, Y g:i A', strtotime($notification['created_at']));
                $notification['sort_time'] = strtotime($notification['created_at']);

            }
            unset($notification); // Unset the reference
            foreach ($all_notifications as $notification) { 
                $notif_date = new DateTime($notification['created_at']);
                if ($notif_date >= $today_start) {
                    $grouped_notifications['Today'][] = $notification;
                } elseif ($notif_date >= $week_start) {
                    $grouped_notifications['This Week'][] = $notification;
                } elseif ($notif_date >= $month_start) {
                    $grouped_notifications['This Month'][] = $notification;
                } else {
                    $grouped_notifications['Older'][] = $notification;
                }
            }

            ?><span class="notif-badge" id="notifBadge" style="<?php echo $notif_count > 0 ? '' : 'display:none;'; ?>"><?php echo $notif_count; ?></span>
        </a>

        <div class="user-profile nav-item dropdown">
            <a class="nav-link dropdown-toggle" id="UserDropdown" href="#" data-toggle="dropdown" aria-expanded="false">
                    <?php if (!empty($adminImage)) { ?>
                        <img class="user-avatar" src="<?php echo htmlentities($adminImage); ?>" alt="Profile image">
                    <?php } else { ?>
                        <div class="user-avatar-initials"><?php echo substr($adminName, 0, 1); ?></div>
                    <?php } ?>
                    <div class="user-info">
                        <span class="name"><?php echo htmlentities($adminName); ?></span>
                        <span class="role">Administrator</span>
                    </div>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">

                <a class="dropdown-item" href="profile.php"><i class="icon-user"></i> My Profile</a>
                <a class="dropdown-item" href="change-password.php"><i class="icon-key"></i> Change Password</a>
                <a class="dropdown-item" href="logout.php"><i class="icon-logout"></i> Sign Out</a>
            </div>
        </div>

        <!-- Notification panel (hidden by default) -->
        <div id="notifPanel" class="notif-panel" role="dialog" aria-label="Notifications" aria-hidden="true">
            <div class="panel-header">
              <span>Notifications</span>
            </div>
            <div class="notif-tabs">
                <button class="notif-tab active" data-tab="unread">Unread</button>
                <button class="notif-tab" data-tab="all">All</button>
            </div>
            <div class="panel-body" id="notifBody">
              <div class="notif-empty">No new notifications.</div>
            </div>
        </div>
    </div>
</header>

<!-- Notification panel script -->
<script>
    // Pass PHP data to global JavaScript variables
    var notificationsData = <?php echo json_encode($grouped_notifications); ?>;
    var allNotificationsData = <?php echo json_encode($all_notifications); ?>;
</script>
<script src="js/notification.js"></script>