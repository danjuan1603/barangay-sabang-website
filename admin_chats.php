<?php
session_start();
include 'config.php';

// --- SECURITY ---
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: index.php?admin_login=required");
    exit();
}

// ✅ sanitize userid
$userid = isset($_GET['userid']) ? intval($_GET['userid']) : 0;

// ✅ Insert admin message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['admin_message']) && $userid > 0) {
    $message = trim($_POST['admin_message']);
    if ($message !== '') {
  // Insert into admin_chats (is_read=0 so user sees as unread)
  $stmt = $conn->prepare("INSERT INTO admin_chats (userid, message, sender, is_read) VALUES (?, ?, 'admin', 0)");
  $stmt->bind_param("is", $userid, $message);
  $stmt->execute();
  $stmt->close();

        // --- Log admin action ---
        $admin_username = $_SESSION['admin_username'] ?? 'Unknown';
        $actionText = "Sent message to user ID $userid: " . substr($message, 0, 50); // log first 50 chars
        $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
        $log->bind_param("ss", $admin_username, $actionText);
        $log->execute();
        $log->close();
    }
    header("Location: admin_chats.php?userid=" . $userid);
    exit();
}



// ✅ PHP function to calculate time ago
function timeAgo($datetime) {
    if (!$datetime) return 'Never active';
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' min' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

// ✅ Fetch resident info
// ✅ Fetch resident info
$resident = null;
if ($userid > 0) {
    $stmt = $conn->prepare("
        SELECT u.userid, r.surname, r.first_name, r.profile_image,
               COALESCE(u.is_online, 0) AS is_online, u.last_active
        FROM useraccounts u
        JOIN residents r ON u.userid = r.unique_id
        WHERE u.userid = ?
    ");
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $resident = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // ✅ Mark all user's unread messages as read when admin views
    $stmt = $conn->prepare("UPDATE admin_chats SET is_read = 1 WHERE userid = ? AND sender = 'user'");
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<meta charset="UTF-8">
<title>Admin Chat</title>
<style>
:root {
  --primary-green: #10b981;
  --primary-dark: #059669;
  --primary-light: #d1fae5;
  --accent-green: #34d399;
  --bg-main: #f8fafc;
  --bg-sidebar: #ffffff;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --border-color: #e2e8f0;
  --shadow-sm: 0 2px 8px rgba(16, 185, 129, 0.08);
  --shadow-md: 0 4px 16px rgba(16, 185, 129, 0.12);
  --shadow-lg: 0 8px 24px rgba(16, 185, 129, 0.16);
  --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

* {
  box-sizing: border-box;
}

body {
  font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
  display: flex;
  height: 100vh;
  margin: 0;
  background: var(--bg-main);
  color: var(--text-primary);
  overflow: hidden;
}

/* Sidebar */
.sidebar {
  width: 340px;
  background: var(--bg-sidebar);
  border-right: 1px solid var(--border-color);
  display: flex;
  flex-direction: column;
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.sidebar-header {
  padding: 20px 16px 16px;
  flex-shrink: 0;
  border-bottom: 1px solid var(--border-color);
}

.sidebar-content {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
}

.sidebar-content::-webkit-scrollbar {
  width: 6px;
}

.sidebar-content::-webkit-scrollbar-track {
  background: transparent;
}

.sidebar-content::-webkit-scrollbar-thumb {
  background: #d0d0d0;
  border-radius: 10px;
}

.sidebar-content::-webkit-scrollbar-thumb:hover {
  background: #b0b0b0;
}

.sidebar h3 {
  margin: 0 0 12px;
  font-size: 13px;
  font-weight: 700;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.sidebar a {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 12px;
  margin-bottom: 8px;
  text-decoration: none;
  border-radius: 10px;
  color: var(--text-primary);
  background: transparent;
  transition: var(--transition);
  position: relative;
  overflow: hidden;
}

.sidebar a::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  height: 100%;
  width: 3px;
  background: var(--primary-green);
  transform: scaleY(0);
  transition: var(--transition);
}

.sidebar a.active {
  background: linear-gradient(135deg, var(--primary-green) 0%, var(--accent-green) 100%);
  color: #fff;
  font-weight: 600;
  box-shadow: var(--shadow-md);
  transform: translateX(4px);
}

.sidebar a.active::before {
  transform: scaleY(1);
}

.sidebar a:hover:not(.active) {
  background: var(--primary-light);
  transform: translateX(4px);
}

.badge {
  background: linear-gradient(135deg, #ff4757 0%, #ff6348 100%);
  color: white;
  border-radius: 20px;
  padding: 4px 10px;
  font-size: 11px;
  font-weight: 700;
  min-width: 22px;
  text-align: center;
  box-shadow: 0 2px 8px rgba(255, 71, 87, 0.3);
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.05); }
}

/* Chatroom */
.chatroom {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
  position: relative;
}

.chat-header {
  padding: 20px 24px;
  border-bottom: 1px solid var(--border-color);
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  font-weight: 600;
  box-shadow: var(--shadow-sm);
  z-index: 10;
}

.chat-header strong {
  font-size: 18px;
  color: var(--text-primary);
}

.chat-header small {
  color: var(--text-secondary);
  font-weight: 400;
}

.chat-box {
  flex: 1;
  overflow-y: auto;
  padding: 24px 24px 24px 24px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  background: transparent;
}

.chat-box::-webkit-scrollbar {
  width: 8px;
}

.chat-box::-webkit-scrollbar-track {
  background: transparent;
}

.chat-box::-webkit-scrollbar-thumb {
  background: #d0d0d0;
  border-radius: 10px;
}

.chat-box::-webkit-scrollbar-thumb:hover {
  background: #b0b0b0;
}

/* Messages */
.message {
  margin: 4px 0;
  padding: 12px 16px;
  border-radius: 16px;
  max-width: 70%;
  font-size: 14.5px;
  line-height: 1.5;
  position: relative;
  word-wrap: break-word;
  animation: messageSlideIn 0.3s ease-out;
  box-shadow: var(--shadow-sm);
  cursor: pointer;
  transition: all 0.2s ease;
}

@keyframes messageSlideIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.me {
  background: linear-gradient(135deg, var(--primary-green) 0%, var(--accent-green) 100%);
  color: white;
  align-self: flex-end;
  border-bottom-right-radius: 4px;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
  display: flex;
  align-items: flex-end;
  gap: 10px;
  padding: 10px 14px;
  flex-direction: row-reverse;
}

.me:hover {
  box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
  transform: translateY(-2px);
}

.me .profile-img {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.me .message-content {
  flex: 1;
  min-width: 0;
}

.me .message-text {
  margin-bottom: 4px;
  word-wrap: break-word;
}

.me .message-time {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.8);
  font-weight: 500;
  text-align: right;
}

.message.selected {
  box-shadow: 0 0 0 2px var(--primary-green);
}

/* Message status (Seen / Sent) - hidden until message is selected */
.message .message-status {
  display: none;
}
.message.selected .message-status {
  display: block;
}

/* Unsend text (plain link-like text to match design) */
.unsend-text {
  position: absolute;
  /* position the control slightly away from the message bubble to avoid covering text */
  right: calc(100% + 6px);
  top: 50%;
  transform: translateY(-50%);
  color: var(--danger, #dc3545);
  background: transparent;
  border: none;
  padding: 0; /* text-only */
  font-size: 12px;
  cursor: pointer;
  transition: color 0.12s ease, transform 0.12s ease;
  z-index: 10;
  display: none;
  font-weight: 700;
  white-space: nowrap;
  text-decoration: none;
  width: auto !important;
  min-width: 0 !important;
  box-sizing: content-box !important;
}

.unsend-text:hover,
.unsend-text:focus {
  color: var(--danger, #c82333) !important;
  text-decoration: underline;
  outline: none;
}

.message.selected .unsend-text {
  display: inline-block;
  animation: slideInLeft 0.16s ease-out;
}

/* Extra specificity to prevent global button:hover rules from changing unsend appearance */
.message .unsend-text:hover {
  background: transparent !important;
  color: var(--danger, #c82333) !important;
  box-shadow: none !important;
}

@keyframes slideInLeft {
  from {
    opacity: 0;
    transform: translateX(-6px) translateY(0);
  }
  to {
    opacity: 1;
    transform: translateX(0) translateY(0);
  }
}

@keyframes fadeOut {
  from { opacity: 1; transform: scale(1); }
  to { opacity: 0; transform: scale(0.8); }
}

.other {
  background: #f0f4f8;
  color: var(--text-primary);
  align-self: flex-start;
  border-bottom-left-radius: 4px;
  border: 1px solid var(--border-color);
  display: flex;
  align-items: flex-end;
  gap: 10px;
  padding: 10px 14px;
}

.other .profile-img {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}

.other .message-content {
  flex: 1;
  min-width: 0;
}

.other .message-text {
  margin-bottom: 4px;
  word-wrap: break-word;
}

.other .message-time {
  font-size: 11px;
  color: var(--text-secondary);
  font-weight: 500;
}

/* Input bar */
form {
  display: flex;
  justify-content: center; /* center the input area */
  padding: 18px 12px; /* slightly less vertical padding */
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-top: 1px solid var(--border-color);
  gap: 12px;
  box-shadow: 0 -2px 8px rgba(0,0,0,0.04);
}

/* Inner container to control width of input + button */
.input-inner {
  width: 68%;
  max-width: 860px;
  min-width: 320px;
  display: flex;
  gap: 12px;
  align-items: center;
}

form input[type="text"] {
  flex: 1;
  padding: 12px 18px;
  border-radius: 28px;
  border: 2px solid var(--border-color);
  outline: none;
  font-size: 14.2px;
  transition: var(--transition);
  background: #fbfdff;
  height: 44px;
}

form input[type="text"]:focus {
  border-color: var(--primary-green);
  background: #ffffff;
  box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15);
}

form button {
  padding: 10px 18px; /* smaller button */
  border: none;
  border-radius: 22px;
  background: linear-gradient(135deg, var(--primary-green) 0%, var(--accent-green) 100%);
  color: white;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  transition: var(--transition);
  box-shadow: 0 6px 18px rgba(16, 185, 129, 0.22);
  position: relative;
  overflow: hidden;
  height: 44px;
  display: inline-flex;
  align-items: center;
}

form button::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  transform: translate(-50%, -50%);
  transition: width 0.6s, height 0.6s;
}

form button:hover::before {
  width: 300px;
  height: 300px;
}

form button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
}

form button:active {
  transform: translateY(0);
}

/* Search Form */
.search-form {
  margin-bottom: 0;
  display: flex;
  gap: 0;
  box-shadow: var(--shadow-sm);
  border-radius: 10px;
  overflow: hidden;
}

.search-form input[type="number"] {
  flex: 1;
  padding: 10px 14px;
  border: 2px solid var(--border-color);
  border-right: none;
  border-radius: 10px 0 0 10px;
  outline: none;
  font-size: 13.5px;
  transition: var(--transition);
  background: #f8f9fa;
}

.search-form input[type="number"]:focus {
  border-color: var(--primary-green);
  background: #ffffff;
}

.search-form button {
  padding: 10px 18px;
  border: none;
  background: var(--primary-green);
  color: #fff;
  border-radius: 0 10px 10px 0;
  cursor: pointer;
  font-weight: 600;
  font-size: 13.5px;
  transition: var(--transition);
}

.search-form button:hover {
  background: var(--primary-dark);
}

/* Profile Images */
.profile-img {
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid var(--border-color);
  transition: var(--transition);
}

.sidebar a:hover .profile-img,
.sidebar a.active .profile-img {
  border-color: rgba(255, 255, 255, 0.5);
  transform: scale(1.05);
}

/* Responsive */
@media (max-width: 768px) {
  .sidebar {
    width: 280px;
    padding: 16px 12px;
  }
  
  .message {
    max-width: 85%;
  }
  
  .chat-box {
    padding-left: 16px;
    padding-right: 16px;
  }
  
  .unsend-text {
    left: 50% !important;
    right: auto !important;
    top: -35px !important;
    transform: translateX(-50%) !important;
    font-size: 11px !important;
    padding: 0 6px !important;
  }

  .unsend-text:hover {
    transform: translateX(-50%) scale(1.02) !important;
  }
  
  @keyframes slideInLeft {
    from {
      opacity: 0;
      top: -20px;
    }
    to {
      opacity: 1;
      top: -35px;
    }
  }
  
  form {
    padding: 12px;
  }

  .input-inner {
    width: 92% !important;
    min-width: 0;
  }
}

@media (max-width: 480px) {
  body {
    flex-direction: column;
  }
  
  .sidebar {
    width: 100%;
    max-height: 40vh;
    border-right: none;
    border-bottom: 1px solid var(--border-color);
  }
  
  .chatroom {
    height: 60vh;
  }
}
/* Unsend Modal */
.unsend-modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  z-index: 10000;
  justify-content: center;
  align-items: center;
}

.unsend-modal.active {
  display: flex;
}

.unsend-modal-content {
  background: white;
  border-radius: 12px;
  padding: 24px;
  max-width: 400px;
  width: 90%;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
  animation: zoomIn 0.3s ease-out;
  text-align: center;
}

@keyframes zoomIn {
  from {
    opacity: 0;
    transform: scale(0.8);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.unsend-modal-icon {
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
  font-size: 28px;
  color: white;
  box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
}

.unsend-modal-title {
  font-size: 1.4rem;
  font-weight: 700;
  color: #333;
  margin-bottom: 12px;
}

.unsend-modal-text {
  font-size: 0.95rem;
  color: #666;
  line-height: 1.5;
  margin-bottom: 24px;
}

.unsend-modal-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
}

.unsend-modal-btn {
  padding: 10px 24px;
  border: none;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  min-width: 100px;
}

.unsend-cancel {
  background: #f1f1f1;
  color: #333;
}

.unsend-cancel:hover {
  background: #e0e0e0;
  transform: translateY(-2px);
}

.unsend-confirm {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.unsend-confirm:hover {
  background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(220, 53, 69, 0.4);
}

/* Active Status Indicators */
.status-indicator {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  display: inline-block;
  position: relative;
  flex-shrink: 0;
}

.status-indicator.online {
  background: #28a745;
  box-shadow: 0 0 8px rgba(40, 167, 69, 0.6);
  animation: statusPulse 2s infinite;
}

.status-indicator.offline {
  background: #6c757d;
}

@keyframes statusPulse {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.7;
    transform: scale(1.1);
  }
}

.status-container {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 4px;
}

.status-text {
  font-size: 12px;
  color: #666;
  font-weight: 500;
}

.status-text.online {
  color: #28a745;
}
</style>
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="sidebar">
  <div class="sidebar-header">
    <h3>🔍 Search User</h3>
    <form method="GET" action="admin_chats.php" class="search-form" id="search-user-form">
      <input type="number" name="userid" placeholder="Enter User ID" required id="search-userid-input">
      <button type="submit">Go</button>
    </form>
  </div>

  <div class="sidebar-content">
    <h3>👥 Residents</h3>
    <?php
    $users = $conn->query("
      SELECT u.userid, CONCAT(r.surname,' ',r.first_name) AS fullname, r.profile_image,
      SUM(CASE WHEN c.is_read = 0 AND c.sender = 'user' THEN 1 ELSE 0 END) AS unread_count,
      COALESCE(u.is_online, 0) AS is_online, u.last_active
      FROM useraccounts u 
      JOIN residents r ON u.userid = r.unique_id
      LEFT JOIN admin_chats c ON u.userid = c.userid
      GROUP BY u.userid, r.surname, r.first_name, r.profile_image, u.is_online, u.last_active
      ORDER BY u.is_online DESC, unread_count DESC, fullname ASC
    ");
    while ($u = $users->fetch_assoc()): 
        $active = ($userid == $u['userid']) ? "active" : "";
        $unread = (int)$u['unread_count'];
        $img = !empty($u['profile_image']) ? $u['profile_image'] : 'default_avatar.png';
        $isOnline = (int)$u['is_online'];
        $statusText = $isOnline ? 'Active now' : timeAgo($u['last_active']);
        $statusClass = $isOnline ? 'online' : 'offline';
    ?>
      <a href="#" class="resident-item <?= $active ?>" data-userid="<?= $u['userid'] ?>">
        <div style="display:flex; align-items:center; gap:12px; flex:1; min-width:0;">
          <img src="<?= htmlspecialchars($img) ?>" 
               alt="Profile" 
               class="profile-img"
               style="width:40px; height:40px; flex-shrink:0;">
          <div style="flex:1; min-width:0;">
            <div style="font-weight:600; font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
              <?= htmlspecialchars($u['fullname']) ?>
            </div>
            <div class="status-container" style="margin-top:2px;">
              <span class="status-indicator <?= $statusClass ?>"></span>
              <span class="status-text <?= $isOnline ? 'online' : '' ?>" style="font-size:11px;"><?= htmlspecialchars($statusText) ?></span>
            </div>
          </div>
        </div>
        <?php if ($unread > 0): ?>
          <span class="badge"><?= $unread ?></span>
        <?php endif; ?>
      </a>
    <?php endwhile; ?>
  </div>
</div>


<div class="chatroom">
<div class="chat-header">
  <?php if ($resident): ?>
    <?php 
      $headerImg = !empty($resident['profile_image']) ? $resident['profile_image'] : 'default_avatar.png';
      $isOnline = (int)$resident['is_online'];
      $statusText = $isOnline ? 'Active now' : timeAgo($resident['last_active']);
      $statusClass = $isOnline ? 'online' : 'offline';
    ?>
    <div style="display:flex; align-items:center; gap:16px;">
      <img src="<?= htmlspecialchars($headerImg) ?>" 
           alt="Profile" 
           class="profile-img"
           style="width:50px; height:50px;">
      <div>
        <strong style="font-size:18px; color:var(--text-primary);"><?= htmlspecialchars($resident['surname'].' '.$resident['first_name']) ?></strong><br>
        <div style="display:flex; align-items:center; gap:8px; margin-top:4px;">
          <span class="status-indicator <?= $statusClass ?>"></span>
          <small class="status-text <?= $isOnline ? 'online' : '' ?>" style="font-size:13px;"><?= htmlspecialchars($statusText) ?></small>
          <small style="color:var(--text-secondary); font-size:13px;">• ID: <?= $resident['userid'] ?></small>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div style="color:var(--text-secondary); font-size:15px; font-weight:500;">💬 Select a resident to start chat</div>
  <?php endif; ?>
</div>


   
  <div class="chat-box" id="chat-box">
    <!-- Messages load here -->
  </div>

  <?php if ($userid): ?>
  <form method="POST" id="chat-form">
    <div class="input-inner">
      <input type="text" name="admin_message" id="admin_message" placeholder="Type a message..." required autocomplete="off">
      <button type="submit">Send</button>
    </div>
  </form>
  <?php endif; ?>
</div>

<!-- Unsend Confirmation Modal -->
<div id="unsendModal" class="unsend-modal">
  <div class="unsend-modal-content">
    <div class="unsend-modal-icon">
      <i class="fas fa-exclamation-triangle"></i>
    </div>
    <h3 class="unsend-modal-title">Unsend Message?</h3>
    <p class="unsend-modal-text">Are you sure you want to unsend this message? This action cannot be undone.</p>
    <div class="unsend-modal-actions">
      <button class="unsend-modal-btn unsend-cancel" onclick="closeUnsendModal()">Cancel</button>
      <button class="unsend-modal-btn unsend-confirm" onclick="confirmUnsend()">Unsend</button>
    </div>
  </div>
</div>

<!-- 🔔 Modern Red Popup Notification Modal -->
<div class="modal fade" id="newReportModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header" style="background: linear-gradient(90deg, #e53935, #b71c1c); color: #fff; border-bottom: none;">
        <h5 class="modal-title">🚨 New Incident Report</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="incidentDetails" style="font-size: 0.95rem; line-height: 1.4; color:#333;">
        <!-- Dynamic content injected via JS -->
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
let modalEl = document.getElementById('newReportModal');
let modal = new bootstrap.Modal(modalEl);
let modalVisible = false;

// Reset notifications function
function resetNotifications(callback) {
    fetch("check_new_reports.php?action=reset")
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                console.log("Notifications reset.");
                modalVisible = false; // allow modal to show again
                if (callback) callback();
            }
        });
}

// Modal close (X button)
modalEl.addEventListener('hidden.bs.modal', () => {
    resetNotifications();
}, { once: true });

// Fetch new reports every 3 seconds
setInterval(() => {
    if(modalVisible) return; // skip if modal is open

    fetch("check_new_reports.php?action=check")
        .then(res => res.json())
        .then(data => {
            if (data.new_reports > 0) {
                let details = `<p style="font-weight:600; color:#b71c1c;">${data.new_reports} new report(s) submitted:</p><ul style="padding-left:18px;">`;
                data.reports.forEach(rep => {
                    details += `<li><strong style="color:#e53935;">${rep.incident_type}</strong>: ${rep.incident_description}</li>`;
                });
                details += `</ul><br><a href="incident_reports.php" id="viewReportsBtn" class="btn btn-sm btn-danger w-100">View Reports</a>`;

                document.getElementById("incidentDetails").innerHTML = details;

                // Show modal
                modal.show();
                modalVisible = true;

                // Attach click listener to View Reports
                const viewBtn = document.getElementById("viewReportsBtn");
                if(viewBtn) {
                    viewBtn.onclick = (e) => {
                        e.preventDefault();
                        resetNotifications(() => {
                            modal.hide();
                        });
                    };
                }

                // Ensure X button also resets
                modalEl.addEventListener('hidden.bs.modal', () => {
                    resetNotifications();
                }, { once: true });
            }
        })
        .catch(err => console.error("Fetch error:", err));
}, 3000);

// Global variable to track current user
var currentUserId = <?= $userid > 0 ? $userid : 0 ?>;
var messageInterval = null;
let deletedMessageIds = new Set(); // Track deleted messages
let pendingUnsendChatId = null;
let pendingUnsendElement = null;

// JavaScript timeAgo function
function timeAgo(datetime) {
  if (!datetime) return 'Never active';
  
  const now = new Date();
  const ago = new Date(datetime);
  const diffMs = now - ago;
  const diffSec = Math.floor(diffMs / 1000);
  const diffMin = Math.floor(diffSec / 60);
  const diffHour = Math.floor(diffMin / 60);
  const diffDay = Math.floor(diffHour / 24);
  const diffMonth = Math.floor(diffDay / 30);
  const diffYear = Math.floor(diffDay / 365);
  
  if (diffYear > 0) return diffYear + ' year' + (diffYear > 1 ? 's' : '') + ' ago';
  if (diffMonth > 0) return diffMonth + ' month' + (diffMonth > 1 ? 's' : '') + ' ago';
  if (diffDay > 0) return diffDay + ' day' + (diffDay > 1 ? 's' : '') + ' ago';
  if (diffHour > 0) return diffHour + ' hour' + (diffHour > 1 ? 's' : '') + ' ago';
  if (diffMin > 0) return diffMin + ' min' + (diffMin > 1 ? 's' : '') + ' ago';
  return 'Just now';
}

// Function to update active status for all residents in sidebar
function updateActiveStatus() {
  document.querySelectorAll('.resident-item').forEach(function(item) {
    const userid = item.getAttribute('data-userid');
    
    fetch('get_user_status.php?userid=' + userid)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          const statusIndicator = item.querySelector('.status-indicator');
          const statusText = item.querySelector('.status-text');
          
          if (statusIndicator && statusText) {
            const isOnline = data.is_online == 1;
            const statusTextValue = isOnline ? 'Active now' : timeAgo(data.last_active);
            
            // Update indicator class
            if (isOnline) {
              statusIndicator.classList.remove('offline');
              statusIndicator.classList.add('online');
              statusText.classList.add('online');
            } else {
              statusIndicator.classList.remove('online');
              statusIndicator.classList.add('offline');
              statusText.classList.remove('online');
            }
            
            // Update status text
            statusText.textContent = statusTextValue;
          }
        }
      })
      .catch(err => console.error('Error updating status for user ' + userid + ':', err));
  });
  
  // Update chat header status if a user is selected
  if (currentUserId > 0) {
    updateChatHeaderStatus(currentUserId);
  }
}

// Function to update chat header status
function updateChatHeaderStatus(userid) {
  fetch('get_user_status.php?userid=' + userid)
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const chatHeader = document.querySelector('.chat-header');
        const statusIndicator = chatHeader.querySelector('.status-indicator');
        const statusText = chatHeader.querySelector('.status-text');
        
        if (statusIndicator && statusText) {
          const isOnline = data.is_online == 1;
          const statusTextValue = isOnline ? 'Active now' : timeAgo(data.last_active);
          
          // Update indicator class
          if (isOnline) {
            statusIndicator.classList.remove('offline');
            statusIndicator.classList.add('online');
            statusText.classList.add('online');
          } else {
            statusIndicator.classList.remove('online');
            statusIndicator.classList.add('offline');
            statusText.classList.remove('online');
          }
          
          // Update status text
          statusText.textContent = statusTextValue;
        }
      }
    })
    .catch(err => console.error('Error updating chat header status:', err));
}

// Function to update unread counts in sidebar
function updateUnreadCounts() {
  fetch('get_unread_counts.php', {
    method: 'GET',
    credentials: 'same-origin'
  })
  .then(function(response) { return response.json(); })
  .then(function(data) {
    // Update badges for each user
    data.forEach(function(user) {
      var residentItem = document.querySelector('.resident-item[data-userid="' + user.userid + '"]');
      if (residentItem) {
        var badge = residentItem.querySelector('.badge');
        if (user.unread_count > 0) {
          if (badge) {
            badge.textContent = user.unread_count;
            badge.style.display = 'inline-block';
          } else {
            // Create badge if it doesn't exist
            var newBadge = document.createElement('span');
            newBadge.className = 'badge';
            newBadge.textContent = user.unread_count;
            residentItem.appendChild(newBadge);
          }
        } else {
          if (badge) {
            badge.style.display = 'none';
          }
        }
      }
    });
  })
  .catch(function(error) {
    console.error('Error updating unread counts:', error);
  });
}

// Function to load chat for a specific user
function loadChatForUser(userid) {
  currentUserId = userid;
  
  // Update URL without reload
  history.pushState(null, '', '?userid=' + userid);
  
  // Fetch and update chat header and messages
  fetch('get_chat_user.php?userid=' + userid, {
    method: 'GET',
    credentials: 'same-origin'
  })
  .then(function(response) { return response.json(); })
  .then(function(data) {
    // Update chat header
    var chatHeader = document.querySelector('.chat-header');
    if (data.resident) {
      var img = data.resident.profile_image || 'default_avatar.png';
      var isOnline = data.resident.is_online == 1;
      var statusText = isOnline ? 'Active now' : timeAgo(data.resident.last_active);
      var statusClass = isOnline ? 'online' : 'offline';
      var statusTextClass = isOnline ? 'status-text online' : 'status-text';
      
      chatHeader.innerHTML = `
        <div style="display:flex; align-items:center; gap:16px;">
          <img src="${img}" 
               alt="Profile" 
               class="profile-img"
               style="width:50px; height:50px;">
          <div>
            <strong style="font-size:18px; color:var(--text-primary);">${data.resident.fullname}</strong><br>
            <div style="display:flex; align-items:center; gap:8px; margin-top:4px;">
              <span class="status-indicator ${statusClass}"></span>
              <small class="${statusTextClass}" style="font-size:13px;">${statusText}</small>
              <small style="color:var(--text-secondary); font-size:13px;">• ID: ${data.resident.userid}</small>
            </div>
          </div>
        </div>
      `;
    }
    
    // Show/create chat form if not exists
    var chatroom = document.querySelector('.chatroom');
    var existingForm = document.getElementById('chat-form');
    if (!existingForm && userid > 0) {
      var formHtml = `
        <form method="POST" id="chat-form">
          <div class="input-inner">
            <input type="text" name="admin_message" id="admin_message" placeholder="Type a message..." required autocomplete="off">
            <button type="submit">Send</button>
          </div>
        </form>
      `;
      chatroom.insertAdjacentHTML('beforeend', formHtml);
      attachChatFormHandler();
    }
    
    // Update active state in sidebar
    document.querySelectorAll('.resident-item').forEach(function(item) {
      item.classList.remove('active');
      if (item.getAttribute('data-userid') == userid) {
        item.classList.add('active');
      }
    });
    
    // Load messages
    loadMessagesForUser(userid);
    
    // Update unread counts (marks as read)
    updateUnreadCounts();
    
    // Clear old interval and start new one
    if (messageInterval) clearInterval(messageInterval);
    messageInterval = setInterval(function() {
      loadMessagesForUser(userid);
      updateUnreadCounts();
    }, 3000);
  })
  .catch(function(error) {
    console.error('Error loading chat:', error);
  });
}

// Function to load messages for specific user
function loadMessagesForUser(userid) {
  fetch("get_chats.php?userid=" + userid, {
    credentials: "include"
  })
  .then(res => res.json())
  .then(data => {
    const chatBox = document.getElementById("chat-box");
    
    // Create a hash of the new messages to compare with existing
    const newMessagesHash = JSON.stringify(data);
    
    // Only update if messages have changed
    if (chatBox.dataset.messagesHash !== newMessagesHash) {
      const wasAtBottom = chatBox.scrollHeight - chatBox.scrollTop <= chatBox.clientHeight + 50;
      
      chatBox.innerHTML = "";
      data.forEach(chat => {
        // Skip deleted messages
        if (deletedMessageIds.has(chat.chat_id)) return;
        
        const div = document.createElement("div");
        div.classList.add("message");
        div.setAttribute("data-id", chat.chat_id);

        if (chat.sender === "admin") {
          div.classList.add("me");
          
          // Add unsend text control (text-only, accessible)
          const unsendText = document.createElement('button');
          unsendText.classList.add('unsend-text');
          unsendText.setAttribute('aria-label', 'Unsend message');
          unsendText.type = 'button';
          unsendText.textContent = 'Unsend';
          unsendText.onclick = function(e) {
            e.stopPropagation();
            unsendMessage(chat.chat_id, div);
          };
          div.appendChild(unsendText);
          
          // Create profile image for admin
          const img = document.createElement('img');
          img.src = 'admin-panel.png'; // Use admin avatar
          img.alt = 'Admin';
          img.className = 'profile-img';
          div.appendChild(img);
          
          // Create message content container
          const contentDiv = document.createElement('div');
          contentDiv.className = 'message-content';
          
          // Add message text
          const textDiv = document.createElement('div');
          textDiv.className = 'message-text';
          textDiv.innerHTML = chat.message;
          contentDiv.appendChild(textDiv);
          
          // Add timestamp
          const timeSmall = document.createElement('small');
          timeSmall.className = 'message-time';
          timeSmall.textContent = chat.created_at;
          contentDiv.appendChild(timeSmall);

          // Add seen/sent status (hidden until message is selected)
          const statusSmall = document.createElement('small');
          statusSmall.className = 'message-status';
          statusSmall.style.marginTop = '4px';
          statusSmall.style.fontSize = '11px';
          statusSmall.style.opacity = '0.95';
          // Use is_read returned by get_chats.php to determine status text (no IDs)
          if (chat.is_read && parseInt(chat.is_read) === 1) {
            statusSmall.textContent = 'Seen';
          } else {
            statusSmall.textContent = 'Sent';
          }
          contentDiv.appendChild(statusSmall);
          
          div.appendChild(contentDiv);
          
          // Add click handler
          div.addEventListener('click', function(e) {
            if (e.target.closest('.unsend-text')) return;
            document.querySelectorAll('.message.selected').forEach(msg => {
              if (msg !== div) msg.classList.remove('selected');
            });
            div.classList.toggle('selected');
          });
        } else {
          div.classList.add("other");
          let profileImg = chat.profile_image && chat.profile_image.trim() !== "" 
            ? chat.profile_image 
            : "default_avatar.png";

          // Create profile image
          const img = document.createElement('img');
          img.src = profileImg;
          img.alt = 'Profile';
          img.className = 'profile-img';
          div.appendChild(img);
          
          // Create message content container
          const contentDiv = document.createElement('div');
          contentDiv.className = 'message-content';
          
          // Create message text
          const textDiv = document.createElement('div');
          textDiv.className = 'message-text';
          textDiv.textContent = chat.message;
          contentDiv.appendChild(textDiv);
          
          // Create timestamp
          const timeSmall = document.createElement('small');
          timeSmall.className = 'message-time';
          timeSmall.textContent = chat.created_at;
          contentDiv.appendChild(timeSmall);
          
          div.appendChild(contentDiv);
        }
        chatBox.appendChild(div);
      });
      
      // Store the hash for next comparison
      chatBox.dataset.messagesHash = newMessagesHash;
      
      // Only auto-scroll if user was already at bottom
      if (wasAtBottom) {
        chatBox.scrollTop = chatBox.scrollHeight;
      }
    }
  })
  .catch(err => console.error("Fetch error:", err));
}

function unsendMessage(chatId, messageElement) {
  pendingUnsendChatId = chatId;
  pendingUnsendElement = messageElement;
  document.getElementById('unsendModal').classList.add('active');
}

function closeUnsendModal() {
  document.getElementById('unsendModal').classList.remove('active');
  pendingUnsendChatId = null;
  pendingUnsendElement = null;
}

function confirmUnsend() {
  if (!pendingUnsendChatId || !pendingUnsendElement) return;

  fetch('admin_unsend_chat.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'chat_id=' + pendingUnsendChatId
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      // Add to deleted IDs immediately
      deletedMessageIds.add(pendingUnsendChatId);
      
      // Close any selected messages
      document.querySelectorAll('.message.selected').forEach(msg => {
        msg.classList.remove('selected');
      });
      
      // Remove message with animation
      if (pendingUnsendElement && pendingUnsendElement.parentNode) {
        pendingUnsendElement.style.animation = 'fadeOut 0.3s ease forwards';
        pendingUnsendElement.style.pointerEvents = 'none';
        
        setTimeout(() => {
          if (pendingUnsendElement && pendingUnsendElement.parentNode) {
            pendingUnsendElement.parentNode.removeChild(pendingUnsendElement);
          }
          closeUnsendModal();
        }, 300);
      } else {
        closeUnsendModal();
      }
    } else {
      alert('Failed to unsend message: ' + (data.error || 'Unknown error'));
      closeUnsendModal();
    }
  })
  .catch(err => {
    console.error('Unsend error:', err);
    alert('Failed to unsend message. Please try again.');
    closeUnsendModal();
  });
}

// Close unsend buttons when clicking outside
document.addEventListener('click', function(e) {
  if (!e.target.closest('.message')) {
    document.querySelectorAll('.message.selected').forEach(msg => {
      msg.classList.remove('selected');
    });
  }
});

// Handle resident item clicks without page reload
document.querySelectorAll('.resident-item').forEach(function(item) {
  item.addEventListener('click', function(e) {
    e.preventDefault();
    var userid = this.getAttribute('data-userid');
    loadChatForUser(userid);
  });
});

// Handle search form submission without page reload
var searchForm = document.getElementById('search-user-form');
if (searchForm) {
  searchForm.addEventListener('submit', function(e) {
    e.preventDefault();
    var userid = document.getElementById('search-userid-input').value;
    loadChatForUser(userid);
  });
}

// Function to attach chat form handler
function attachChatFormHandler() {
  var chatForm = document.getElementById('chat-form');
  if (chatForm) {
    chatForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      var messageInput = document.getElementById('admin_message');
      var message = messageInput.value.trim();
      
      if (message === '' || currentUserId === 0) return;
      
      var formData = new FormData(chatForm);
      
      fetch('admin_chats.php?userid=' + currentUserId, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      })
      .then(function(response) { return response.text(); })
      .then(function(html) {
        // Clear input
        messageInput.value = '';
        
        // Reload messages immediately
        loadMessagesForUser(currentUserId);
      })
      .catch(function(error) {
        console.error('Error sending message:', error);
        alert('Error sending message. Please try again.');
      });
    });
  }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
  // Attach handler on page load if form exists
  attachChatFormHandler();

  // If a user is already selected on page load, start loading messages
  if (currentUserId > 0) {
    console.log('Loading messages for user:', currentUserId);
    loadMessagesForUser(currentUserId);
    updateUnreadCounts();
    updateActiveStatus();
    messageInterval = setInterval(function() {
      loadMessagesForUser(currentUserId);
      updateUnreadCounts();
      updateActiveStatus();
    }, 3000);
  } else {
    // Even if no user selected, update unread counts and status periodically
    setInterval(function() {
      updateUnreadCounts();
      updateActiveStatus();
    }, 5000);
  }
});

</script>

<!-- Automatic logout script for logged-in admins -->
<?php if (isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true): ?>
<script src="auto_logout.js"></script>
<?php endif; ?>

</body>
</html>
