(function () {
  var notifIcon = document.getElementById('notifIcon');
  var notifPanel = document.getElementById('notifPanel');
  var notifBody = document.getElementById('notifBody');
  var notifBadge = document.getElementById('notifBadge');
  var panelVisible = false;

  // Note: notificationsData and allNotificationsData are expected to be defined globally
  // in a <script> tag in the main HTML file before this script is loaded.

  function openPanel() {
    if (!notifPanel) return;
    notifPanel.classList.add('show');
    notifPanel.setAttribute('aria-hidden', 'false');
    if (notifIcon) notifIcon.setAttribute('aria-expanded', 'true');
    panelVisible = true;

    renderNotifications('unread'); // Render unread notifications by default
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

  // Example function to render notifications (can be adapted for dynamic data)
  function renderNotifications(filter) {
    if (!notifBody) return;
    try {
      var html = '';
      var hasNotifications = false;

      if (filter === 'all') {
          // Render all notifications without grouping
          if (allNotificationsData && allNotificationsData.length > 0) {
              hasNotifications = true;
              allNotificationsData.forEach(function (n) {
                  var text = (n.message || n.text || '').toString();
                  var time = n.time; // Use the pre-formatted time from PHP
                  var url = n.url || '#';
                  var itemClass = n.is_read == 0 ? 'notif-item unread' : 'notif-item';
                  html += '<a href="' + escapeHtml(url) + '" class="' + itemClass + '" data-id="' + n.id + '" style="text-decoration: none; color: inherit;">';
                  html += '<div class="dot"></div>';
                  html += '<div class="msg"><div style="font-weight:600; margin-bottom:4px;">' + escapeHtml(text) + '</div><div style="font-size:12px;color:#888;">' + escapeHtml(time) + '</div></div>';
                  html += '</a>';
              });
          }
      } else { // 'unread' filter
          // Render unread notifications with grouping
          var groupOrder = ['Today', 'This Week', 'This Month', 'Older'];
          groupOrder.forEach(function(groupName) {
              var groupItems = notificationsData[groupName] || [];
              var filteredItems = groupItems.filter(n => n.is_read == 0);

              if (filteredItems.length > 0) {
                  hasNotifications = true;
                  html += '<div class="notif-group-header">' + groupName + '</div>';
                  filteredItems.forEach(function (n) {
                      var text = (n.message || n.text || '').toString();
                      var time = n.time || n.date || '';
                      var url = n.url || '#';
                      var itemClass = 'notif-item unread';
                      html += '<a href="' + escapeHtml(url) + '" class="' + itemClass + '" data-id="' + n.id + '" style="text-decoration: none; color: inherit;">';
                      html += '<div class="dot"></div>';
                      html += '<div class="msg"><div style="font-weight:600; margin-bottom:4px;">' + escapeHtml(text) + '</div><div style="font-size:12px;color:#888;">' + escapeHtml(time) + '</div></div>';
                      html += '</a>';
                  });
              }
          });
      }

      if (!hasNotifications) {
        notifBody.innerHTML = '<div class="notif-empty">No ' + (filter === 'unread' ? 'new ' : '') + 'notifications.</div>';
      } else {
        notifBody.innerHTML = html;
      }

      // Add click listeners to each notification item
      document.querySelectorAll('.notif-item').forEach(function(item) {
          item.addEventListener('click', function(e) {
              e.preventDefault(); // Prevent immediate navigation
              var notifId = this.dataset.id;
              var redirectUrl = this.href;
              var isUnread = this.classList.contains('unread');

              // If it's not unread, just navigate
              if (!isUnread) {
                  window.location.href = redirectUrl;
                  return;
              }

              // Mark this single notification as read
              fetch('ajax_helpers.php?action=mark_one_as_read', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json' },
                  body: JSON.stringify({ notif_id: notifId })
              }).then(res => res.json()).then(data => {
                  if (data.success) {
                      var currentCount = parseInt(notifBadge.textContent || '0', 10);
                      var newCount = Math.max(0, currentCount - 1);
                      notifBadge.textContent = newCount;
                      if (newCount === 0) {
                          notifBadge.style.display = 'none';
                      }
                  }
              }).finally(() => {
                  window.location.href = redirectUrl; // Navigate after attempting to mark as read
              });
          });
      });
    } catch (e) {
      notifBody.innerHTML = '<div class="notif-empty">Error rendering notifications.</div>';
    }
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (m) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": "&#39;" }[m]; });
  }

  if (notifIcon) notifIcon.addEventListener('click', function (e) { e.preventDefault(); togglePanel(); });

  document.addEventListener('click', function (e) {
    if (!panelVisible) return;
    var t = e.target;
    if (notifPanel && !notifPanel.contains(t) && notifIcon && !notifIcon.contains(t)) {
      closePanel();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && panelVisible) closePanel();
  });

  // Tab switching logic
  document.querySelectorAll('.notif-tab').forEach(function(tab) {
      tab.addEventListener('click', function() {
          document.querySelectorAll('.notif-tab').forEach(t => t.classList.remove('active'));
          this.classList.add('active');
          var filter = this.dataset.tab;
          renderNotifications(filter);
      });
  });
})();