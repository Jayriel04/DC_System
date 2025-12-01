<?php
// Ensure a session is started so we can detect logged-in users
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Determine web root path for assets. This tries to detect if the project is hosted under a folder like /DC_System.
// If not found, $base will be empty (site assumed at webroot). Adjust $project if your folder name differs.
$base = '';
if (isset($_SERVER['SCRIPT_NAME'])) {
  $project_folders = ['dental-clinic', 'DC_System'];
  foreach ($project_folders as $project) {
    if (strpos($_SERVER['SCRIPT_NAME'], '/' . $project . '/') !== false) {
      $base = '/' . $project;
      break; // Found the project folder, so we can stop looking.
    }
  }
}

// Broaden logged-in detection: include your app-specific session keys
$logged_in = false;
$user_firstname = '';
$user_surname = '';
$user_role = '';
$user_image = '';
$notif_count = 0;
$notifications_data = []; // Initialize notifications array
if (!empty($_SESSION)) {
  $possible_keys = ['user_id', 'userid', 'id', 'user', 'username', 'email', 'sturecmsnumber', 'sturecmsfirstname', 'sturecmssurname'];
  foreach ($possible_keys as $k) {
    if (isset($_SESSION[$k])) {
      $logged_in = true;
      break;
    }
  }
  // prefer your app-specific fields for display
  if ($logged_in && isset($_SESSION['sturecmsnumber'])) {
    // Fetch user details including the image from tblpatient
    $stmt = $dbh->prepare("SELECT firstname, surname, Image, sex FROM tblpatient WHERE number = :id");
    $stmt->bindParam(':id', $_SESSION['sturecmsnumber'], PDO::PARAM_INT);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user_data) {
      $user_firstname = $user_data['firstname'];
      $user_surname = $user_data['surname'];
      $user_image = $user_data['Image'];
      $user_sex = $user_data['sex'];

      if (empty($user_image)) {
          if ($user_sex === 'Male') {
              $user_image = 'man-icon.png';
          } else if ($user_sex === 'Female') {
              $user_image = 'woman-icon.jpg';
          }
      }
    }
  }
  // Fetch unread notification count for the logged-in patient
  if ($logged_in && isset($_SESSION['sturecmsnumber'])) {
      $patient_id = $_SESSION['sturecmsnumber'];
      
      // Fetch last 10 notifications for the dropdown
      $sql_notif = "SELECT id, message, url, is_read, created_at FROM tblnotif 
                    WHERE recipient_id = :patient_id AND recipient_type = 'patient' 
                    ORDER BY created_at DESC LIMIT 10";
      $query_notif = $dbh->prepare($sql_notif);
      $query_notif->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
      $query_notif->execute();
      $notifications = $query_notif->fetchAll(PDO::FETCH_ASSOC);

      $unread_count = 0;
      foreach ($notifications as $notif) {
          if ($notif['is_read'] == 0) {
              $unread_count++;
          }
          // Format for JSON
          $notifications_data[] = ['id' => $notif['id'], 'message' => $notif['message'], 'url' => $base . '/user/' . $notif['url'], 'is_read' => $notif['is_read'], 'time' => date('M d, Y g:i A', strtotime($notif['created_at']))];
      }
      $notif_count = $unread_count;
  }
  // also allow nested user array like $_SESSION['user']['id']
  if (!$logged_in && isset($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
    $logged_in = true;
    if (empty($user_firstname) && !empty($_SESSION['user']['firstname']))
      $user_firstname = $_SESSION['user']['firstname'];
    if (empty($user_role) && !empty($_SESSION['user']['role']))
      $user_role = $_SESSION['user']['role'];
  }
}
?>
<link rel="stylesheet" href="<?php echo $base; ?>/css/header.css">

<!-- small header-specific styles to match the screenshot (badge on bell + avatar + name/role) -->
<style>
  .header .hdr-icons {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .hdr-icons .notif-icon {
    position: relative;
    display: inline-flex;
    align-items: center;
    color: var(--primary-color, #333);
    text-decoration: none;
  }

  .hdr-icons .notif-icon svg {
    display: block;
  }

  .hdr-icons .notif-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #e74c3c;
    color: #fff;
    font-size: 12px;
    line-height: 18px;
    min-width: 18px;
    height: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    padding: 0 5px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, .2);
  }

  .hdr-icons .profile-link {
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    color: inherit;
  }

  .hdr-icons .avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #2ecc71;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
  }

  .hdr-icons .profile-text {
    display: flex;
    flex-direction: column;
    line-height: 1;
    font-size: 13px;
  }

  .hdr-icons .profile-text .name {
    font-weight: 600;
  }

  .hdr-icons .profile-text .role {
    font-size: 11px;
    color: #666;
    margin-top: 2px;
  }

  .hdr-icons .logout-btn {
    color: #c0392b;
    text-decoration: none;
    font-size: 13px;
  }

  @media (max-width:600px) {
    .hdr-icons .profile-text {
      display: none;
    }
  }

  /* Notification panel styles */
  .notif-panel {
    position: absolute;
    left: 1382px;
    right: 8px;
    top: calc(100% + 10px);
    width: 320px;
    max-height: 420px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, .12);
    overflow: hidden;
    display: none;
    z-index: 1050;
    border: 1px solid rgba(0, 0, 0, .06);
  }
  .notif-tabs {
    display: flex;
    border-bottom: 1px solid #f1f1f1;
    padding: 0 12px;
  }
  .notif-tab {
    padding: 8px 12px;
    font-size: 14px;
    font-weight: 500;
    color: #6c757d;
    background: none;
    border: none;
    cursor: pointer;
    position: relative;
    border-bottom: 2px solid transparent;
  }
  .notif-tab.active {
    color: #007bff;
    border-bottom-color: #007bff;
  }
  .notif-tab:hover {
    color: #0056b3;
  }

  .notif-panel.show {
    display: block;
    animation: fadeIn .12s ease-out;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(-4px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .notif-panel .panel-header {
    padding: 10px 12px;
    border-bottom: 1px solid #f1f1f1;
    font-weight: 600;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .notif-panel .panel-body {
    max-height: 340px;
    overflow: auto;
    padding: 8px;
  }

  .notif-panel .notif-item {
    display: flex;
    gap: 10px;
    padding: 8px;
    border-radius: 6px;
    align-items: flex-start;
  }

  .notif-panel .notif-item+.notif-item {
    margin-top: 6px;
  }

  .notif-panel .notif-item .dot {
    width: 10px;
    height: 10px;
    background: #2ecc71;
    border-radius: 50%;
    margin-top: 6px;
    flex-shrink: 0;
  }

  .notif-panel .notif-item .msg {
    font-size: 13px;
    color: #222;
  }

  .notif-panel .panel-footer {
    padding: 8px 12px;
    border-top: 1px solid #f1f1f1;
    text-align: center;
    font-size: 13px;
  }

  .notif-empty {
    padding: 18px;
    text-align: center;
    color: #666;
    font-size: 13px;
  }

  /* small responsive tweak */
  @media (max-width:480px) {
    .notif-panel {
      right: 6px;
      left: 6px;
      width: auto;
    }
  }

  /* Profile Dropdown Styles */
  .profile-dropdown {
    position: relative;
  }

  .profile-dropdown-menu {
    position: absolute;
    right: -32px;
    top: calc(100% + 10px);
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, .12);
    min-width: 180px;
    z-index: 1050;
    border: 1px solid rgba(0, 0, 0, .06);
    display: none;
    padding: 6px;
    top: 55px;
  }

  .profile-dropdown-menu.show {
    display: block;
    animation: fadeIn .12s ease-out;
  }

  .profile-dropdown-menu a {
    display: block;
    padding: 8px 12px;
    color: #333;
    text-decoration: none;
    font-size: 14px;
    border-radius: 4px;
  }

  .profile-dropdown-menu a:hover {
    background-color: #f1f5f9;
  }

  .profile-dropdown-menu a i {
    margin-right: 8px;
    color: #64748b;
  }
</style>

<header class="header">
  <div class="header-container">
    <div class="header-content">
      <div class="logo-section">
        <div class="logo-container">

          <!-- use $base and url-encode the filename to avoid space issues in the URL -->
          <img src="<?php echo $base; ?>./images/<?php echo rawurlencode('Jf logo.png'); ?>" alt="JF Dental Care Logo"
            class="logo-img" style="height:40px; margin-right:10px;">
          <span class="logo-text">JF DENTAL CARE</span>
        </div>
      </div>
      <?php
      // Build a stable link to the site's index page.
      // If $base is set (e.g. '/dental-clinic'), use that. Otherwise fall back to the known project
      // folder so links don't point to the server root (XAMPP welcome page).
      if (!empty($base)) {
        $index_url = rtrim($base, '/') . '/index.php';
      } else {
        // Fallback for webroot or if base detection fails. Assumes a default project folder.
        $index_url = '/dental-clinic/index.php';
      }
      ?>
      <nav class="desktop-nav">
        <a href="<?php echo $index_url; ?>#home" class="nav-link scroll">Home</a>
        <a href="<?php echo $index_url; ?>#about" class="nav-link scroll">About Us</a>
        <a href="<?php echo $index_url; ?>#services" class="nav-link scroll">Services</a>
        <a href="<?php echo $index_url; ?>#contact" class="nav-link scroll">Contact Us</a>
      </nav>
      <div class="action-buttons">
        <?php if (!$logged_in): ?>
          <!-- Not logged in: show Login / Sign up -->
          <a href="<?php echo $base; ?>./user/login.php" class="login-btn">LOGIN</a>
          <a href="<?php echo $base; ?>./user/create_account.php" class="signup-btn">SIGN UP</a>
        <?php else: ?>
          <!-- Logged in: bell with badge (activity) + profile avatar/name + logout -->
          <div class="hdr-icons" style="position:relative;">
            <a href="javascript:void(0)" id="notifIcon" class="notif-icon" aria-haspopup="true" aria-expanded="false">
              <!-- bell SVG -->
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                aria-hidden="true">
                <path
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11c0-3.07-1.64-5.64-4.5-6.32V4a1.5 1.5 0 0 0-3 0v0.68C7.64 5.36 6 7.929 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5"
                  stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
              <?php if (!empty($notif_count)): ?>
                <span class="notif-badge" id="notifBadge"><?php echo $notif_count; ?></span>
              <?php else: ?>
                <span class="notif-badge" id="notifBadge" style="display:none;"></span>
              <?php endif; ?>
            </a>

            <div class="profile-dropdown">
              <a href="javascript:void(0)" id="profileLink" class="profile-link" aria-haspopup="true"
                aria-expanded="false">
                <?php
                $avatar_image = !empty($user_image) ? htmlentities($user_image) : 'avatar.png'; // Fallback if sex is not set
                ?>
                <img src="<?php echo $base; ?>../admin/images/<?php echo $avatar_image; ?>" alt="Avatar" class="avatar"
                  style="object-fit: cover;">
                <div class="profile-text" style="color: black;">
                  <div class="name"><?php echo htmlspecialchars(trim($user_firstname . ' ' . $user_surname)); ?></div>
                  <?php if (!empty($user_role)) { ?>
                    <div class="role"><?php echo htmlspecialchars($user_role); ?></div><?php } ?>
                </div>
              </a>

              <div class="profile-dropdown-menu" id="profileDropdownMenu" role="menu">
                <a href="<?php echo $base; ?>/user/profile.php" role="menuitem"><i class="ri-user-line"></i>My Profile</a>
                <a href="<?php echo $base; ?>/user/change-password.php" role="menuitem"><i
                    class="ri-lock-password-line"></i>Change Password</a>
                <div style="height:1px; background:#eee; margin: 6px 0;"></div>
                <a href="<?php echo $base; ?>/user/logout.php" role="menuitem" style="color: #c0392b;"><i
                    class="ri-logout-box-r-line"></i>Sign Out</a>
              </div>
            </div>
          </div>

          <!-- Notification panel (hidden by default) -->
          <div id="notifPanel" class="notif-panel" role="dialog" aria-label="Notifications" aria-hidden="true">
            <div class="panel-header">
              <span>Notifications</span>
            </div>
            <div class="notif-tabs">
                <button class="notif-tab active" data-tab="all">All</button>
                <button class="notif-tab" data-tab="unread">Unread</button>
            </div>
            <div class="panel-body" id="notifBody">
              <div class="notif-empty">Loading...</div>
            </div>
          </div>

        <?php endif; ?>
      </div>
    </div>
  </div>
</header>

<!-- Notification panel script -->
<script>
  (function () {
    var notifIcon = document.getElementById('notifIcon');
    var notifPanel = document.getElementById('notifPanel');
    var notifBody = document.getElementById('notifBody');
    var notifBadge = document.getElementById('notifBadge');
    var panelVisible = false;
    var userNotifications = <?php echo json_encode($notifications_data); ?>;

    function openPanel() {
      if (!notifPanel) return;
      notifPanel.classList.add('show');
      notifPanel.setAttribute('aria-hidden', 'false');
      if (notifIcon) notifIcon.setAttribute('aria-expanded', 'true');
      panelVisible = true;
      
      // Set the 'All' tab as active and render all notifications by default
      document.querySelector('.notif-tab[data-tab="all"]').classList.add('active');
      document.querySelector('.notif-tab[data-tab="unread"]').classList.remove('active');
      renderNotifications(userNotifications, 'all');

      // After showing, mark them as read on the server
      // This still requires a call to a server file, but it's only for updating state.
      var unreadIds = userNotifications.filter(n => n.is_read == 0).map(n => n.id);
      if (unreadIds.length > 0) {
        fetch('<?php echo $base; ?>/user/ajax_helpers.php?action=mark_as_read', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ ids: unreadIds })
        }).then(() => { if(notifBadge) notifBadge.style.display = 'none'; });
      }
    }
    function closePanel() {
      if (!notifPanel) return;
      notifPanel.classList.remove('show');
      notifPanel.setAttribute('aria-hidden', 'true');
      if (notifIcon) notifIcon.setAttribute('aria-expanded', 'false');
      panelVisible = false;
    }
    function togglePanel() {
      if (panelVisible) closePanel(); else openPanel();
    }

    function renderNotifications(data, filter = 'all') {
      if (!notifBody) return;
      try {
        if (!data || !Array.isArray(data)) {
          notifBody.innerHTML = '<div class="notif-empty">No notifications.</div>';
          if (notifBadge) notifBadge.style.display = 'none';
          return;
        }
        // if data contains items array
        var items = Array.isArray(data) ? data : (data.items || []);
        if (!items || items.length === 0) {
          notifBody.innerHTML = '<div class="notif-empty">You have no notifications.</div>';
          if (notifBadge) notifBadge.style.display = 'none';
          return;
        }

        var filteredItems = (filter === 'unread') ? items.filter(n => n.is_read == 0) : items;
        if (filteredItems.length === 0) {
          notifBody.innerHTML = `<div class="notif-empty">No ${filter} notifications.</div>`;
          return;
        }
        var html = '';
        items.forEach(function (n) {
          var text = (n.message || '').toString();
          var time = n.time || '';
          var url = n.url || '#';
          var isUnread = n.is_read == 0;
          html += '<a href="' + escapeHtml(url) + '" class="notif-item ' + (isUnread ? 'unread' : '') + '" data-id="' + n.id + '">';
          if(isUnread) html += '<div class="dot"></div>';
          html += '<div class="msg"><div class="msg-text">' + escapeHtml(text) + '</div><div class="msg-time">' + escapeHtml(time) + '</div></div>';
          html += '</a>';
        });
        notifBody.innerHTML = html;
      } catch (e) {
        notifBody.innerHTML = '<div class="notif-empty">Error rendering notifications.</div>';
      }
    }

    function escapeHtml(s) {
      return String(s).replace(/[&<>"']/g, function (m) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": "&#39;" }[m]; });
    }

    // toggle on icon click
    if (notifIcon) notifIcon.addEventListener('click', function (e) { e.preventDefault(); togglePanel(); });

    // close on outside click
    document.addEventListener('click', function (e) {
      if (!panelVisible) return;
      var t = e.target;
      if (notifPanel && !notifPanel.contains(t) && notifIcon && !notifIcon.contains(t)) {
        closePanel();
      }
    });

    // Handle tab clicks
    var tabsContainer = document.querySelector('.notif-tabs');
    if (tabsContainer) tabsContainer.addEventListener('click', function(e) {
        if (e.target.matches('.notif-tab')) {
            var filter = e.target.getAttribute('data-tab');
            // Update active tab
            tabsContainer.querySelector('.notif-tab.active').classList.remove('active');
            e.target.classList.add('active');
            
            // Re-render notifications with the new filter
            renderNotifications(userNotifications, filter);
        }
    });

    // close on Escape
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && panelVisible) closePanel();
    });
  })();

  // Profile Dropdown Script
  (function () {
    var profileLink = document.getElementById('profileLink');
    var profileMenu = document.getElementById('profileDropdownMenu');
    var menuVisible = false;

    if (!profileLink || !profileMenu) return;

    function toggleMenu() {
      menuVisible = !menuVisible;
      profileMenu.classList.toggle('show', menuVisible);
      profileLink.setAttribute('aria-expanded', menuVisible);
    }

    profileLink.addEventListener('click', function (e) {
      e.preventDefault();
      toggleMenu();
    });

    document.addEventListener('click', function (e) {
      if (menuVisible && !profileLink.contains(e.target) && !profileMenu.contains(e.target)) {
        toggleMenu();
      }
    });
  })();

  // Add some styles for the new notification structure
  var style = document.createElement('style');
  style.innerHTML = `
    .notif-panel .notif-item {
      text-decoration: none;
      color: inherit;
      display: flex;
      gap: 10px;
      padding: 10px;
      border-radius: 6px;
      align-items: flex-start;
      transition: background-color .15s;
    }
    .notif-panel .notif-item:hover {
      background-color: #f8f9fa;
    }
    .notif-panel .notif-item.unread {
      background-color: #e9f5ff;
    }
    .notif-panel .notif-item .dot {
      width: 8px;
      height: 8px;
      background: #007bff;
      border-radius: 50%;
      margin-top: 5px;
      flex-shrink: 0;
    }
    .notif-panel .notif-item .msg {
      font-size: 14px;
      color: #333;
      flex-grow: 1;
    }
    .notif-panel .notif-item .msg-text {
      margin-bottom: 4px;
    }
    .notif-panel .notif-item .msg-time {
      font-size: 12px;
      color: #6c757d;
    }
  `;
  document.head.appendChild(style);
</script>