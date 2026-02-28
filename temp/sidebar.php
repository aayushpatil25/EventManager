<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get user information
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Lexend:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 210px;
            --sidebar-collapsed: 64px;
            --header-height: 60px;
            --primary: #0ea5e9;
            --primary-foreground: #ffffff;
            --background: #ffffff;
            --card: #ffffff;
            --foreground: #1e293b;
            --muted-foreground: #64748b;
            --accent: #f1f5f9;
            --border: #e2e8f0;
            --destructive: #ef4444;
            --destructive-light: rgba(239, 68, 68, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Lexend', sans-serif;
            background: #f8fafc;
            color: #1e293b;
            transition: margin-left 0.3s ease;
            margin-left: var(--sidebar-width);
        }

        body.sidebar-collapsed {
            margin-left: var(--sidebar-collapsed);
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--card);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
            overflow: hidden;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.08);
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed);
        }

        .sidebar-header {
            height: var(--header-height);
            min-height: var(--header-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 6px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 12px;
            transition: opacity 0.2s ease;
            overflow: hidden;
        }

        .sidebar.collapsed .logo-container {
            opacity: 0;
            width: 0;
        }

        .logo-text {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
        }

        .toggle-btn {
            background: transparent;
            border: none;
            width: 32px;
            height: 32px;
            min-width: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--muted-foreground);
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .toggle-btn:hover {
            background: var(--accent);
            color: var(--foreground);
        }

        .toggle-btn i {
            transition: transform 0.3s ease;
        }

        .sidebar.collapsed .toggle-btn i {
            transform: rotate(180deg);
        }

        .nav-container {
            flex: 1;
            padding: 16px 8px;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .nav-menu {
            list-style: none;
            width: 100%;
        }

        .nav-item {
            margin-bottom: 4px;
            width: 100%;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            color: var(--muted-foreground);
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            white-space: nowrap;
            width: 100%;
        }

        .nav-link:hover {
            background: var(--accent);
            color: var(--foreground);
        }

        .nav-link.active {
            background: var(--primary);
            color: var(--primary-foreground);
        }

        .nav-link.logout:hover {
            background: var(--destructive-light);
            color: var(--destructive);
        }

        .nav-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .nav-text {
            transition: opacity 0.2s ease;
            opacity: 1;
            white-space: nowrap;
        }

        .sidebar.collapsed .nav-text {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }

        .logout-container {
            padding: 16px 8px;
            border-top: 1px solid var(--border);
            width: 100%;
        }

        /* Top Navigation Bar */
        .topbar {
            position: fixed;
            left: var(--sidebar-width);
            top: 0;
            right: 0;
            height: var(--header-height);
            background: white;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 0 24px;
            gap: 16px;
            z-index: 999;
            transition: left 0.3s ease;
        }

        body.sidebar-collapsed .topbar {
            left: var(--sidebar-collapsed);
        }

        .topbar-right {
            position: relative;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        /* Notification Button */
        .notification-btn {
            position: relative;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: var(--accent);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--muted-foreground);
            transition: all 0.2s ease;
        }

        .notification-btn:hover {
            background: #e2e8f0;
            color: var(--foreground);
        }

        .notification-badge {
            position: absolute;
            top: 6px;
            right: 6px;
            background: var(--destructive);
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: 600;
            min-width: 18px;
            text-align: center;
            display: none;
        }

        .notification-badge.show {
            display: block;
        }

        /* Notification Dropdown */
        .notification-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 80px;
            width: 380px;
            max-height: 500px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            display: none;
            flex-direction: column;
            overflow: hidden;
            animation: dropdownSlide 0.2s ease;
        }

        .notification-dropdown.show {
            display: flex;
        }

        @keyframes dropdownSlide {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .notification-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--foreground);
        }

        .notification-clear {
            background: none;
            border: none;
            color: var(--primary);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .notification-clear:hover {
            background: #dbeafe;
        }

        .notification-list {
            overflow-y: auto;
            max-height: 400px;
        }

        .notification-item {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            gap: 12px;
            align-items: start;
        }

        .notification-item:hover {
            background: var(--accent);
        }

        .notification-item.unread {
            background: #f0f9ff;
        }

        .notification-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 14px;
        }

        .notification-icon.event-due {
            background: #fef3c7;
            color: #b45309;
        }

        .notification-icon.new-event {
            background: #dbeafe;
            color: #0369a1;
        }

        .notification-icon.new-client {
            background: #e9d5ff;
            color: #7e22ce;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--foreground);
            margin-bottom: 4px;
        }

        .notification-message {
            font-size: 13px;
            color: var(--muted-foreground);
            line-height: 1.4;
        }

        .notification-time {
            font-size: 11px;
            color: var(--muted-foreground);
            margin-top: 4px;
        }

        .notification-empty {
            padding: 48px 20px;
            text-align: center;
            color: var(--muted-foreground);
        }

        .notification-empty i {
            font-size: 48px;
            margin-bottom: 12px;
            opacity: 0.3;
        }

        .notification-empty p {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 4px;
        }

        .notification-empty small {
            font-size: 12px;
            opacity: 0.7;
        }

        /* User Profile */
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .user-profile:hover {
            background: var(--accent);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--foreground);
        }

        .user-role {
            font-size: 12px;
            color: var(--muted-foreground);
        }

        .dropdown-icon {
            width: 16px;
            height: 16px;
            transition: transform 0.2s ease;
        }

        /* Profile Dropdown */
        .profile-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 24px;
            width: 220px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            display: none;
            flex-direction: column;
            overflow: hidden;
            animation: dropdownSlide 0.2s ease;
        }

        .profile-dropdown.show {
            display: flex;
        }

        .profile-dropdown-header {
            padding: 16px;
            border-bottom: 1px solid var(--border);
        }

        .profile-dropdown-header .user-info {
            gap: 4px;
        }

        .dropdown-menu {
            padding: 8px;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            color: var(--foreground);
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background: var(--accent);
        }

        .dropdown-item.logout {
            color: var(--destructive);
        }

        .dropdown-item.logout:hover {
            background: var(--destructive-light);
        }

        /* Backdrop */
        .dropdown-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.2);
            display: none;
            z-index: 998;
        }

        /* Main Content */
        .main-content {
            padding: 24px;
        }

        /* Mobile Styles */
        @media (max-width: 768px) {
            body {
                margin-left: 0;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .topbar {
                left: 0;
            }

            .notification-dropdown {
                right: 0;
                width: calc(100vw - 32px);
                margin: 0 16px;
            }

            .profile-dropdown {
                right: 16px;
            }
        }
    </style>
</head>
<body>
    <!-- Backdrop -->
    <div class="dropdown-backdrop" id="dropdownBackdrop"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo-container">
                    <img src="uploads/FinalLogos.png" style="height:40px;width:170px">
            </div>
            <button class="toggle-btn" id="sidebarToggle">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>

        <div class="nav-container">
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <span class="nav-icon">
                            <i class="fas fa-chart-line"></i>
                        </span>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="events.php" class="nav-link">
                        <span class="nav-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </span>
                        <span class="nav-text">Events</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="clients.php" class="nav-link">
                        <span class="nav-icon">
                            <i class="fas fa-users"></i>
                        </span>
                        <span class="nav-text">Clients</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="vendors.php" class="nav-link">
                        <span class="nav-icon">
                            <i class="fas fa-user-tie"></i>
                        </span>
                        <span class="nav-text">Vendors</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="services.php" class="nav-link">
                        <span class="nav-icon">
                            <i class="fas fa-concierge-bell"></i>
                        </span>
                        <span class="nav-text">Services</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="logout-container">
            <a href="logout.php" class="nav-link logout">
                <span class="nav-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </span>
                <span class="nav-text">Logout</span>
            </a>
        </div>
    </aside>

    <!-- Top Navigation Bar -->
    <nav class="topbar">
        <div class="topbar-right">
            <!-- Notification Button -->
            <button class="notification-btn" id="notificationBtn">
                <i class="fas fa-bell"></i>
                <span class="notification-badge" id="notificationBadge">0</span>
            </button>

            <!-- Notification Dropdown -->
            <div class="notification-dropdown" id="notificationDropdown">
                <div class="notification-header">
                    <h3>Notifications</h3>
                    <button class="notification-clear" id="clearNotifications">Clear All</button>
                </div>
                <div class="notification-list" id="notificationList">
                    <div class="notification-empty">
                        <i class="fas fa-bell"></i>
                        <p>No notifications</p>
                        <small>You're all caught up!</small>
                    </div>
                </div>
            </div>

            <!-- User Profile -->
            <div class="user-profile" id="userProfile">
                <div class="user-avatar">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
                    <div class="user-role">Administrator</div>
                </div>
                <svg class="dropdown-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M7 10l5 5 5-5z"/>
                </svg>
            </div>

            <!-- Profile Dropdown -->
            <div class="profile-dropdown" id="profileDropdown">
                <div class="profile-dropdown-header">
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
                    </div>
                </div>
                <div class="dropdown-menu">
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user"></i>
                        <span>Profile</span>
                    </a>
                    <a href="logout.php" class="dropdown-item logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page content will be included here -->
        <?php if (isset($page_content)) echo $page_content; ?>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        const body = document.body;
        const notificationBtn = document.getElementById('notificationBtn');
        const notificationDropdown = document.getElementById('notificationDropdown');
        const userProfile = document.getElementById('userProfile');
        const profileDropdown = document.getElementById('profileDropdown');
        const dropdownBackdrop = document.getElementById('dropdownBackdrop');
        const clearNotificationsBtn = document.getElementById('clearNotifications');
        const notificationBadge = document.getElementById('notificationBadge');
        const notificationList = document.getElementById('notificationList');
        
        // Load saved sidebar state
        const savedState = localStorage.getItem('sidebarCollapsed');
        if (savedState === 'true') {
            sidebar.classList.add('collapsed');
            body.classList.add('sidebar-collapsed');
        }
        
        // Toggle sidebar
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            body.classList.toggle('sidebar-collapsed');
            
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
            
            closeAllDropdowns();
        });
        
        // Highlight active nav item
        const currentPath = window.location.pathname;
        const fileName = currentPath.substring(currentPath.lastIndexOf('/') + 1);
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href === fileName || (fileName === '' && href === 'dashboard.php')) {
                link.classList.add('active');
            }
        });
        
        // Fetch notifications
        function fetchNotifications() {
            fetch('get_notifications.php?action=fetch')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        updateNotificationUI(data.notifications, data.unread_count);
                    } else {
                        console.error('Notification fetch failed:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error fetching notifications:', error);
                    // Show default empty state on error
                    notificationList.innerHTML = `
                        <div class="notification-empty">
                            <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i>
                            <p>Unable to load notifications</p>
                            <small>Please try again later</small>
                        </div>
                    `;
                    notificationBadge.classList.remove('show');
                });
        }
        
        // Update notification UI
        function updateNotificationUI(notifications, unreadCount) {
            // Update badge
            if (unreadCount > 0) {
                notificationBadge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                notificationBadge.classList.add('show');
            } else {
                notificationBadge.classList.remove('show');
            }
            
            // Update notification list
            if (notifications.length === 0) {
                notificationList.innerHTML = `
                    <div class="notification-empty">
                        <i class="fas fa-bell"></i>
                        <p>No notifications</p>
                        <small>You're all caught up!</small>
                    </div>
                `;
            } else {
                notificationList.innerHTML = '';
                notifications.forEach(notif => {
                    const item = createNotificationItem(notif);
                    notificationList.appendChild(item);
                });
            }
        }
        
        // Create notification item
        function createNotificationItem(notif) {
            const item = document.createElement('div');
            item.className = 'notification-item' + (notif.is_read == 0 ? ' unread' : '');
            item.dataset.notificationId = notif.id;
            
            const iconClass = getNotificationIconClass(notif.type);
            const timeAgo = getTimeAgo(notif.created_at);
            
            // Escape HTML to prevent XSS
            const title = escapeHtml(notif.title);
            const message = escapeHtml(notif.message);
            
            item.innerHTML = `
                <div class="notification-icon ${iconClass}">
                    <i class="fas ${getNotificationIcon(notif.type)}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${title}</div>
                    <div class="notification-message">${message}</div>
                    <div class="notification-time">${timeAgo}</div>
                </div>
            `;
            
            item.addEventListener('click', function(e) {
                e.stopPropagation();
                markNotificationAsRead(notif.id);
                
                // Navigate to related item if exists
                if (notif.related_type && notif.related_id) {
                    navigateToRelatedItem(notif.related_type, notif.related_id);
                }
            });
            
            return item;
        }
        
        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Navigate to related item
        function navigateToRelatedItem(type, id) {
            const pageMap = {
                'events': 'events.php',
                'clients': 'clients.php',
                'vendors': 'vendors.php'
            };
            
            const page = pageMap[type];
            if (page) {
                // Close dropdown and navigate
                setTimeout(() => {
                    window.location.href = `${page}?highlight=${id}`;
                }, 200);
            }
        }
        
        // Get notification icon
        function getNotificationIcon(type) {
            switch(type) {
                case 'event_due': return 'fa-calendar-check';
                case 'new_event': return 'fa-calendar-plus';
                case 'new_client': return 'fa-user-plus';
                case 'new_vendor': return 'fa-user-tie';
                default: return 'fa-bell';
            }
        }
        
        // Get notification icon class
        function getNotificationIconClass(type) {
            switch(type) {
                case 'event_due': return 'event-due';
                case 'new_event': return 'new-event';
                case 'new_client': return 'new-client';
                case 'new_vendor': return 'new-client';
                default: return 'new-event';
            }
        }
        
        // Get time ago
        function getTimeAgo(timestamp) {
            const now = new Date();
            const then = new Date(timestamp);
            const diffMs = now - then;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);
            
            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
            if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
            if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
            return then.toLocaleDateString();
        }
        
        // Mark notification as read
        function markNotificationAsRead(notificationId) {
            const formData = new FormData();
            formData.append('notification_id', notificationId);
            
            fetch('get_notifications.php?action=mark_read', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    fetchNotifications();
                }
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
            });
        }
        
        // Toggle notification dropdown
        notificationBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = notificationDropdown.classList.contains('show');
            
            closeAllDropdowns();
            
            if (!isOpen) {
                notificationDropdown.classList.add('show');
                dropdownBackdrop.style.display = 'block';
            }
        });
        
        // Toggle profile dropdown
        userProfile.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = profileDropdown.classList.contains('show');
            
            closeAllDropdowns();
            
            if (!isOpen) {
                profileDropdown.classList.add('show');
                dropdownBackdrop.style.display = 'block';
            }
        });
        
        // Clear all notifications
        clearNotificationsBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            
            if (!confirm('Are you sure you want to clear all notifications?')) {
                return;
            }
            
            fetch('get_notifications.php?action=clear_all', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    notificationList.innerHTML = `
                        <div class="notification-empty">
                            <i class="fas fa-check-circle" style="color: #10b981;"></i>
                            <p>Notifications cleared</p>
                            <small>All notifications have been removed</small>
                        </div>
                    `;
                    notificationBadge.classList.remove('show');
                    
                    setTimeout(() => {
                        notificationList.innerHTML = `
                            <div class="notification-empty">
                                <i class="fas fa-bell"></i>
                                <p>No notifications</p>
                                <small>You're all caught up!</small>
                            </div>
                        `;
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error clearing notifications:', error);
                alert('Failed to clear notifications. Please try again.');
            });
        });
        
        // Close dropdowns when clicking backdrop
        dropdownBackdrop.addEventListener('click', closeAllDropdowns);
        
        // Prevent notification dropdown from closing when clicking inside (except notification items)
        notificationDropdown.addEventListener('click', function(e) {
            // Allow notification items to trigger actions
            if (e.target.closest('.notification-item')) {
                return; // Let the notification item handler work
            }
            // Prevent closing when clicking other areas
            e.stopPropagation();
        });

        // Profile dropdown - allow links to work and close dropdown after navigation
        profileDropdown.addEventListener('click', function(e) {
            // Allow links to work normally and close dropdown
            if (e.target.closest('a')) {
                // Close dropdown after a brief delay to allow navigation
                setTimeout(() => {
                    closeAllDropdowns();
                }, 100);
                return;
            }
            // Prevent closing when clicking other areas
            e.stopPropagation();
        });
        
        // Close all dropdowns
        function closeAllDropdowns() {
            notificationDropdown.classList.remove('show');
            profileDropdown.classList.remove('show');
            dropdownBackdrop.style.display = 'none';
        }
        
        // Handle escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAllDropdowns();
            }
        });
        
        // Fetch notifications on page load
        fetchNotifications();
        
        // Check for new notifications every 30 seconds
        setInterval(fetchNotifications, 30000);
    });
</script>
</body>
</html>