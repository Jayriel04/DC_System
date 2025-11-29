<link rel="stylesheet" href="css/header.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="css/notification.css">

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
        $row = $results[0];
        $adminName = $row->AdminName;
        $adminEmail = $row->Email;
    }
    ?>
    <div class="header-left">
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
                    <img class="user-avatar" src="images/faces/profile.png" alt="Profile image">
                    <div class="user-info">
                        <span class="name"><?php echo htmlentities($adminName); ?></span>
                        <span class="role">Administrator</span>
                    </div>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">

                <a class="dropdown-item" href="profile.php"><i class="dropdown-item-icon icon-user "></i> My Profile</a>
                <a class="dropdown-item" href="change-password.php"><i class="dropdown-item-icon icon-energy"></i> Setting</a>
                <a class="dropdown-item" href="logout.php"><i class="dropdown-item-icon icon-power"></i>Sign Out</a>
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