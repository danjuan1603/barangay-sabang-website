<?php
session_start();
$showLogin = false;
if (isset($_GET['login']) && $_GET['login'] == 'fail') {
    $showLogin = true;
}

// Database connection
include 'config.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Barangay Sabang</title>

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    /* ===== RESET ===== */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      font-family: "Inter", sans-serif;
      background: #f9fafb;
      color: #222;
      line-height: 1.5;
      scroll-behavior: smooth;
    }

    /* ===== NAVBAR BASE ===== */
    .main-navbar {
      background: #fff;
      position: sticky;
      top: 0;
      /* Put navbar above other UI overlays so its dropdowns stay clickable */
      z-index: 1000;
      width: 100%;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
    }
    .nav-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0.6rem 1rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    /* ===== BRAND ===== */
    .brand-link {
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
    }
    .brand-link img {
      width: 48px;
      height: 48px;
      border-radius: 8px;
      object-fit: cover;
    }
    .brand-link span {
      font-weight: 600;
      font-size: 1.1rem;
      color: #007bff;
    }

    /* ===== LINKS ===== */
    .nav-links {
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    .nav-links a,
    .nav-links button {
      color: #222;
      text-decoration: none;
      font-size: 0.95rem;
      font-weight: 500;
      padding: 10px 12px;
      border-radius: 6px;
      background: none;
      border: none;
      cursor: pointer;
      transition: 0.2s ease;
    }
    .nav-links a:hover,
    .nav-links button:hover {
      background: #eef5ff;
      color: #007bff;
      transform: translateY(-2px);
    }

    /* ===== DROPDOWN ===== */
    /* Dropdown: ensure it stays on top and is always clickable (useful when overlays or mobile menus toggle) */
    .dropdown {
      position: relative;
      z-index: 1200; /* keep dropdown above most page layers */
    }
    .dropdown .dropbtn {
      position: relative;
      z-index: 1210; /* ensure the button receives pointer events */
      background: none;
      border: none;
      cursor: pointer;
    }
    .dropdown-content {
      display: none;
      position: absolute;
      top: 110%;
      left: 0;
      background: #fff;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
      border-radius: 8px;
      overflow: hidden;
      min-width: 180px;
      z-index: 1220; /* highest among nav elements */
      pointer-events: auto; /* ensure clicks reach the menu */
    }
    .dropdown:hover .dropdown-content {
      display: block;
    }
    .dropdown-content a {
      display: block;
      padding: 10px 16px;
      text-decoration: none;
      color: #222;
      font-size: 0.93rem;
      transition: 0.2s;
    }
    .dropdown-content a:hover {
      background: #eef5ff;
      color: #007bff;
    }

    /* ===== PROFILE & LOGIN ===== */
    .welcome-msg {
      font-size: 0.9rem;
      color: #444;
      margin-right: 6px;
    }
    .nav-profile-btn {
      background: #007bff;
      color: #fff;
      padding: 8px 16px;
      border-radius: 20px;
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 500;
      transition: 0.2s;
    }
    .nav-profile-btn:hover {
      background: #0056b3;
      transform: translateY(-2px);
    }

    .login-btn {
      background: #007bff;
      color: #fff;
      border: none;
      padding: 8px 16px;
      border-radius: 20px;
      cursor: pointer;
      font-size: 0.9rem;
      font-weight: 500;
      transition: background 0.3s;
    }
    .login-btn:hover {
      background: #0056b3;
    }

    /* ===== NOTIFICATIONS ===== */
    /* Notifications: modern card-style dropdown */
    .nav-notification {
      position: relative;
      display: inline-block;
      margin-left: 10px;
    }
    .notif-bell {
      background: none;
      border: none;
      cursor: pointer;
      font-size: 20px;
      color: #0b6efd;
      position: relative;
      padding: 8px;
      border-radius: 8px;
      transition: background 0.18s, transform 0.12s;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .notif-bell:focus { outline: 2px solid rgba(11,110,253,0.15); }
    .notif-bell:hover { background: rgba(11,110,253,0.06); transform: translateY(-1px); }

    .notif-badge {
      position: absolute;
      top: -6px;
      right: -6px;
      background: #ef4444;
      color: #fff;
      font-size: 11px;
      font-weight: 700;
      border-radius: 999px;
      padding: 3px 6px;
      min-width: 20px;
      text-align: center;
      display: none;
      box-shadow: 0 2px 6px rgba(0,0,0,0.12);
    }

    .notif-dropdown {
      display: none;
      position: absolute;
      top: 44px;
      right: 0;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 12px 36px rgba(2,6,23,0.12);
      width: 320px;
      z-index: 7000;
      overflow: hidden;
      transform-origin: top right;
      animation: popIn 0.14s ease;
    }
    @keyframes popIn { from { opacity: 0; transform: scale(0.98) translateY(-6px); } to { opacity:1; transform:scale(1) translateY(0); } }

    .notif-header {
      display:flex; align-items:center; justify-content:space-between;
      font-weight:700; padding:12px 14px; color:#0b6efd; border-bottom:1px solid #f1f5f9; background:#fff;
    }
    .notif-header .small-action { font-size:0.85rem; color:#4b5563; background:none; border:none; cursor:pointer; padding:6px; border-radius:8px; }
    .notif-header .small-action:hover { background:#f1f5f9; color:#0b6efd; }

    .notif-list { max-height: 320px; overflow-y: auto; }
    .notif-empty { padding: 18px; color:#6b7280; text-align:center; font-size:0.95rem; }

    .notif-item {
      display:flex; gap:10px; align-items:flex-start; padding:12px 14px; border-bottom:1px solid #f1f5f9; cursor:pointer;
      transition: background 0.12s, transform 0.08s;
    }
    .notif-item.unread { background: linear-gradient(90deg, rgba(11,110,253,0.03), rgba(11,110,253,0.015)); }
    .notif-item:hover { background:#f8fafc; transform:translateY(-2px); }
    .notif-item .icon { width:36px; height:36px; border-radius:8px; background:#eef2ff; display:flex; align-items:center; justify-content:center; color:#0b6efd; font-size:18px; flex-shrink:0; }
    .notif-item .body { flex:1; }
    .notif-item .body .message { font-size:0.95rem; color:#0f172a; margin-bottom:4px; }
    .notif-item .body .time { font-size:0.8rem; color:#6b7280; }

    .notif-footer { display:flex; align-items:center; justify-content:space-between; gap:8px; padding:10px 12px; background:#fff; }
    .notif-footer .btn { padding:8px 10px; font-size:0.9rem; }
    .notif-footer .view-all { background:none; color:#0b6efd; border:none; cursor:pointer; }
    .notif-footer .close-btn { background:#0b6efd; color:#fff; border:none; border-radius:8px; cursor:pointer; }

    @media (max-width: 992px) {
      .notif-dropdown { 
        position: fixed;
        left: 10px; 
        right: 10px; 
        top: 64px; 
        width: auto; 
        max-width: 420px;
        z-index: 7000;
      }
      .notif-item { padding:14px; }
    }

    /* ===== BURGER ===== */
    .burger {
      display: none;
      flex-direction: column;
      justify-content: center;
      gap: 5px;
      width: 36px;
      height: 36px;
      background: none;
      border: none;
      cursor: pointer;
    }
    .burger span {
      height: 3px;
      width: 25px;
      background: #333;
      border-radius: 2px;
      transition: 0.25s;
    }
    .burger.active span:nth-child(1) {
      transform: translateY(8px) rotate(45deg);
    }
    .burger.active span:nth-child(2) {
      opacity: 0;
    }
    .burger.active span:nth-child(3) {
      transform: translateY(-8px) rotate(-45deg);
    }

    /* ===== MOBILE NAV ===== */
    .mobile-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.6);
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s ease;
      z-index: 5000;
      backdrop-filter: blur(2px);
    }
    .mobile-overlay.show {
      opacity: 1;
      pointer-events: auto;
    }
    .mobile-nav {
      position: fixed;
      top: 0;
      right: 0;
      height: 100vh;
      width: 320px;
      max-width: 85vw;
      background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
      box-shadow: -8px 0 40px rgba(0, 0, 0, 0.15);
      transform: translateX(110%);
      transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
      z-index: 6000;
      display: flex;
      flex-direction: column;
      padding: 0;
      overflow-y: auto;
    }
    .mobile-nav.show {
      transform: translateX(0);
    }
    
    /* Mobile nav header */
    .mobile-nav-header {
      padding: 24px 20px 16px;
      background: linear-gradient(135deg, #15b300 0%, #0e7c00 100%);
      color: white;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 2px 8px rgba(21, 179, 0, 0.2);
    }
    .mobile-nav-title {
      font-size: 1.25rem;
      font-weight: 700;
      margin: 0;
    }
    .mobile-nav-close {
      background: rgba(255, 255, 255, 0.2);
      border: none;
      color: white;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 20px;
      transition: background 0.2s;
    }
    .mobile-nav-close:hover {
      background: rgba(255, 255, 255, 0.3);
    }
    
    /* Mobile nav content */
    .mobile-nav-content {
      padding: 12px 0;
      flex: 1;
    }
    
    .mobile-nav a {
      padding: 16px 24px;
      font-size: 1rem;
      color: #2c3e50;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 12px;
      font-weight: 500;
      transition: all 0.2s ease;
      border-left: 4px solid transparent;
      position: relative;
    }
    .mobile-nav a i {
      width: 20px;
      text-align: center;
      font-size: 1.1rem;
      color: #15b300;
    }
    .mobile-nav a::before {
      content: '';
      width: 6px;
      height: 6px;
      background: #15b300;
      border-radius: 50%;
      opacity: 0;
      transition: opacity 0.2s;
    }
    /* Seen/Sent indicator (hidden by default, revealed when user clicks a message) */
    .seen-indicator {
      display: none; /* hidden until message is selected */
      font-size: 0.75rem;
      margin-top: 6px;
      align-self: flex-end;
      padding: 3px 8px;
      border-radius: 999px;
      line-height: 1;
      white-space: nowrap;
    }
    /* Visible when message is selected by the user */
    .message.selected .seen-indicator {
      display: inline-block;
    }
    /* Styles for seen vs sent states */
    .seen-indicator.seen {
      background: #10b981; /* green */
      color: #ffffff; /* white text when seen */
    }
    .seen-indicator.sent {
      background: #6b7280; /* gray */
      color: #ffffff; /* white text for sent as well */
      opacity: 0.95;
    }
    .mobile-nav a:hover {
      background: rgba(21, 179, 0, 0.08);
      color: #15b300;
      border-left-color: #15b300;
      padding-left: 28px;
    }
    .mobile-nav a:hover::before {
      opacity: 1;
    }
    .mobile-nav a:active {
      background: rgba(21, 179, 0, 0.15);
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 992px) {
      .nav-links {
        display: none;
      }
      .burger {
        display: flex;
      }
      .nav-container {
        padding: 0.6rem 1rem;
      }
      /* Keep notification bell visible on mobile */
      .nav-notification {
        display: inline-block;
        margin-left: auto;
        margin-right: 10px;
        z-index: 7000;
        position: relative;
      }
      /* Notification bell should be above mobile overlay */
      .notif-bell {
        position: relative;
        z-index: 7001;
      }
    }

    /* ===== MOBILE NAV RESPONSIVE ===== */
    @media (max-width: 480px) {
      .mobile-nav {
        width: 100%;
        max-width: 100vw;
      }
      .mobile-nav-header {
        padding: 20px 16px 14px;
      }
      .mobile-nav-title {
        font-size: 1.15rem;
      }
      .mobile-nav a {
        padding: 14px 20px;
        font-size: 0.95rem;
      }
      .dropdown-toggle {
        padding: 14px 20px;
        font-size: 0.95rem;
      }
      .dropdown-content a {
        padding: 10px 20px 10px 44px;
        font-size: 0.9rem;
      }
    }

    /* Smooth scrollbar for mobile nav */
    .mobile-nav::-webkit-scrollbar {
      width: 6px;
    }
    .mobile-nav::-webkit-scrollbar-track {
      background: rgba(0, 0, 0, 0.05);
    }
    .mobile-nav::-webkit-scrollbar-thumb {
      background: rgba(21, 179, 0, 0.3);
      border-radius: 3px;
    }
    .mobile-nav::-webkit-scrollbar-thumb:hover {
      background: rgba(21, 179, 0, 0.5);
    }

    /* ===== LOGIN FORMS RESPONSIVE STYLES ===== */
    .login-form-section {
      min-height: 100vh;
      background: linear-gradient(135deg, #f7faf7 0%, #e8f5e9 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .login-container {
      display: flex;
      max-width: 1000px;
      width: 100%;
      margin: 0 auto;
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 10px 40px rgba(21, 179, 0, 0.15);
      overflow: hidden;
    }

    .login-form-side {
      flex: 1;
      padding: 48px 40px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .login-header {
      margin-bottom: 32px;
    }

    .login-logo {
      width: 60px;
      height: 60px;
      margin-bottom: 20px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(21, 179, 0, 0.2);
    }

    .login-title {
      font-size: 2rem;
      font-weight: 700;
      color: #15b300;
      margin-bottom: 8px;
      line-height: 1.2;
    }

    .login-subtitle {
      color: #666;
      font-size: 1rem;
      line-height: 1.5;
    }

    .login-form {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
      position: relative;
    }

    .form-label {
      font-weight: 600;
      color: #15b300;
      font-size: 0.95rem;
    }

    .form-input {
      width: 100%;
      padding: 14px 16px;
      border: 2px solid #e0e0e0;
      border-radius: 10px;
      font-size: 1rem;
      transition: all 0.3s ease;
      background: #fafafa;
    }

    .form-input:focus {
      outline: none;
      border-color: #15b300;
      background: #fff;
      box-shadow: 0 0 0 4px rgba(21, 179, 0, 0.1);
    }

    .form-input::placeholder {
      color: #999;
    }

    .forgot-password-link {
      color: #15b300;
      font-size: 0.875rem;
      text-decoration: none;
      align-self: flex-end;
      margin-top: 4px;
      font-weight: 500;
      transition: color 0.2s;
    }

    .forgot-password-link:hover {
      color: #0e7c00;
      text-decoration: underline;
    }

    .captcha-image {
      margin: 8px 0;
      border-radius: 10px;
      background: #eee;
      border: 2px solid #e0e0e0;
      width: 150px;
      height: 50px;
      object-fit: contain;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .captcha-refresh-btn {
      background: #15b300;
      color: #fff;
      border: none;
      width: 50px;
      height: 50px;
      border-radius: 10px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s ease;
      font-size: 18px;
      box-shadow: 0 2px 8px rgba(21, 179, 0, 0.3);
      flex-shrink: 0;
    }

    .captcha-refresh-btn:hover {
      background: #0e7c00;
      transform: rotate(180deg);
      box-shadow: 0 4px 12px rgba(21, 179, 0, 0.4);
    }

    .captcha-refresh-btn:active {
      transform: rotate(180deg) scale(0.95);
    }

    /* Mobile responsive CAPTCHA */
    @media (max-width: 576px) {
      .captcha-image {
        width: 180px;
        height: 60px;
      }
      
      .captcha-refresh-btn {
        width: 60px;
        height: 60px;
        font-size: 20px;
      }
    }

    /* Password Toggle Styles */
    .password-input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
      width: 100%;
    }

    .password-toggle-btn {
      position: absolute;
      right: 4px;
      top: 50%;
      transform: translateY(-50%);
      background: transparent;
      border: none;
      cursor: pointer;
      color: #94a3b8;
      font-size: 18px;
      padding: 8px 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s ease;
      z-index: 10;
      border-radius: 6px;
      width: 36px;
      height: 36px;
      min-width: 36px;
      flex-shrink: 0;
    }

    .password-toggle-btn:hover {
      color: #15b300;
      background: rgba(21, 179, 0, 0.08);
    }

    .password-toggle-btn:active {
      transform: translateY(-50%) scale(0.92);
    }

    .password-toggle-btn:focus {
      outline: none;
      box-shadow: 0 0 0 2px rgba(21, 179, 0, 0.2);
    }

    .form-input.has-toggle {
      padding-right: 48px;
    }

    @media (max-width: 576px) {
      .password-toggle-btn {
        font-size: 20px;
        width: 40px;
        height: 40px;
        min-width: 40px;
      }
      
      .form-input.has-toggle {
        padding-right: 52px;
      }
    }

    .form-submit-btn {
      width: 100%;
      background: linear-gradient(135deg, #15b300 0%, #0e7c00 100%);
      color: #fff;
      font-weight: 600;
      padding: 14px;
      border: none;
      border-radius: 10px;
      font-size: 1.1rem;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-top: 8px;
      box-shadow: 0 4px 12px rgba(21, 179, 0, 0.3);
    }

    .form-submit-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(21, 179, 0, 0.4);
    }

    .form-submit-btn:active {
      transform: translateY(0);
    }

    .login-footer-text {
      margin-top: 24px;
      color: #666;
      font-size: 0.95rem;
      text-align: center;
    }

    .login-footer-link {
      color: #15b300;
      font-weight: 600;
      text-decoration: none;
      transition: color 0.2s;
    }

    .login-footer-link:hover {
      color: #0e7c00;
      text-decoration: underline;
    }

    .login-image-side {
      flex: 1;
      background: linear-gradient(135deg, #eafbe6 0%, #d4f1d0 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }

    .login-bg-image {
      width: 100%;
      height: 100%;
      object-fit: cover;
      opacity: 0.9;
    }

    /* ===== RESPONSIVE DESIGN ===== */
    @media (max-width: 992px) {
      .login-container {
        flex-direction: column;
        max-width: 500px;
      }

      .login-image-side {
        display: none;
      }

      .login-form-side {
        padding: 40px 32px;
      }

      .login-title {
        font-size: 1.75rem;
      }
    }

    @media (max-width: 576px) {
      .login-form-section {
        padding: 16px;
      }

      .login-container {
        border-radius: 16px;
      }

      .login-form-side {
        padding: 32px 24px;
      }

      .login-logo {
        width: 50px;
        height: 50px;
      }

      .login-title {
        font-size: 1.5rem;
      }

      .login-subtitle {
        font-size: 0.9rem;
      }

      .form-input {
        padding: 12px 14px;
        font-size: 0.95rem;
      }

      .form-submit-btn {
        padding: 12px;
        font-size: 1rem;
      }
    }
  </style>
</head>

<body>

  <!-- ===== NAVBAR ===== -->
  <?php
  // Prepare profile image src for logged-in user (default fallback)
  $profile_img_src = 'default_avatar.png';
  if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] && isset($_SESSION['userid'])) {
    $uid = $_SESSION['userid'];
    if (isset($conn)) {
      $stmt = $conn->prepare("SELECT profile_image FROM residents WHERE unique_id = ?");
      if ($stmt) {
        $stmt->bind_param("s", $uid);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
          if (!empty($row['profile_image']) && file_exists($row['profile_image'])) {
            $profile_img_src = $row['profile_image'];
          }
        }
        $stmt->close();
      }
    }
  }
  ?>

  <nav class="main-navbar">
    <div class="nav-container">
      <a href="#hero" class="brand-link">
        <img src="logo.jpg" alt="Barangay Logo">
      </a>

      <div class="nav-links" id="desktop-links">
        <a href="#hero">Home</a>
        <a href="#about">About</a>

        <div class="dropdown">
          <button class="dropbtn">Services</button>
          <div class="dropdown-content">
            <a href="#certificates-group">Certificates</a>
            <a href="#incident-reports-section">Incident Reports</a>
          </div>
        </div>

        <a href="#officials">Officials</a>
        <a href="#announcements">Announcements</a>

        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
          <a href="#job-finder-section">Job Finder 🔍</a>
        <?php endif; ?>

        <a href="#contact">Contact</a>

        <?php if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']): ?>
          <button class="login-btn" onclick="openRoleModal()">Login</button>
        <?php else: ?>
          <span class="welcome-msg">Welcome, <?= htmlspecialchars($_SESSION['surname']); ?></span>
          <a href="profile.php" class="nav-profile-btn" style="padding:0; display:inline-flex; align-items:center;" onclick="sessionStorage.setItem('internalNav', 'true');">
            <img src="<?= htmlspecialchars($profile_img_src) ?>" alt="Profile" style="width:36px; height:36px; border-radius:50%; object-fit:cover; display:block;">
          </a>
        <?php endif; ?>
      </div>

      <!-- Notification Bell -->
    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
      <div id="nav-notification" class="nav-notification" aria-live="polite">
            <button id="notif-bell" class="notif-bell" aria-haspopup="true" aria-expanded="false" aria-controls="notif-dropdown"><i class="fas fa-bell"></i></button>
            <span id="notif-badge" class="notif-badge" aria-hidden="true">0</span>
            <div id="notif-dropdown" class="notif-dropdown" role="dialog" aria-label="Notifications panel">
              <div class="notif-header">
                <div>Notifications</div>
                <div>
                  <button id="notif-clear" class="small-action" title="Clear all">Clear</button>
                </div>
              </div>
              <div id="notif-list" class="notif-list" role="list"></div>
              <div class="notif-footer">
                <button id="notif-close" class="close-btn">Close</button>
              </div>
            </div>
          </div>
          <?php endif; ?>

      <!-- Burger -->
      <button class="burger" id="burger">
        <span></span><span></span><span></span>
      </button>
    </div>
  </nav>

  <!-- Mobile Nav -->
<div class="mobile-overlay" id="mobile-overlay"></div>

<div class="mobile-nav" id="mobile-nav">
  <!-- Mobile Nav Header -->
  <div class="mobile-nav-header">
    <h3 class="mobile-nav-title">Menu</h3>
    <button class="mobile-nav-close" onclick="closeNav()" aria-label="Close menu">✕</button>
  </div>

  <!-- Mobile Nav Content -->
  <div class="mobile-nav-content">
    <a href="#hero"><i class="fas fa-home"></i> Home</a>
    <a href="#about"><i class="fas fa-info-circle"></i> About</a>

    <!-- Services dropdown - Click to show/hide -->
    <a href="javascript:void(0)" id="services-toggle" class="mobile-nav-item"><i class="fas fa-cog"></i> Services <span id="services-arrow" style="margin-left: auto;">▾</span></a>
    
    <div id="services-submenu" class="services-submenu" style="display: none; background: rgba(21, 179, 0, 0.03); border-left: 3px solid rgba(21, 179, 0, 0.2);">
      <a href="#certificates-group" class="submenu-link"><i class="fas fa-file-alt"></i> Certificates</a>
      <a href="#incident-reports-section" class="submenu-link"><i class="fas fa-clipboard-list"></i> Incident Reports</a>
    </div>

    <a href="#officials"><i class="fas fa-users"></i> Officials</a>
    <a href="#announcements"><i class="fas fa-bullhorn"></i> Announcements</a>
    <a href="#contact"><i class="fas fa-phone"></i> Contact</a>

    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
      <a href="#job-finder-section"><i class="fas fa-briefcase"></i> Job Finder</a>
      <a href="profile.php" onclick="sessionStorage.setItem('internalNav', 'true');"><i class="fas fa-user-circle"></i> Profile</a>
    <?php else: ?>
      <a href="javascript:void(0)" onclick="openRoleModal(); closeNav();"><i class="fas fa-sign-in-alt"></i> Login</a>
    <?php endif; ?>
  </div>
</div>

<!-- Mobile dropdown styles -->
<style>
.mobile-nav-item {
  padding: 16px 24px;
  font-size: 1rem;
  color: #2c3e50;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 500;
  border-left: 4px solid transparent;
  transition: all 0.2s ease;
  cursor: pointer;
}
.mobile-nav-item i {
  width: 20px;
  text-align: center;
  font-size: 1.1rem;
  color: #15b300;
}
.mobile-nav-item:hover {
  background: rgba(21, 179, 0, 0.08);
  color: #15b300;
  border-left-color: #15b300;
  padding-left: 28px;
}
.services-submenu {
  overflow: hidden;
  transition: all 0.3s ease;
}
.services-submenu.open {
  display: block !important;
}
.submenu-link {
  padding: 12px 24px 12px 48px;
  color: #495057;
  text-decoration: none;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
  gap: 10px;
  transition: all 0.2s ease;
}
.submenu-link i {
  width: 18px;
  text-align: center;
  font-size: 1rem;
  color: #15b300;
}
.submenu-link:hover {
  background: rgba(21, 179, 0, 0.12);
  color: #15b300;
  padding-left: 52px;
}
#services-arrow {
  transition: transform 0.3s ease;
}
#services-arrow.rotate {
  transform: rotate(180deg);
}

/* Profile link styling */
.mobile-nav a[href="profile.php"] {
  background: linear-gradient(135deg, rgba(21, 179, 0, 0.1) 0%, rgba(14, 124, 0, 0.1) 100%);
  border-left-color: #15b300;
  font-weight: 600;
  margin-top: 8px;
}

/* Login link styling */
.mobile-nav a[onclick*="openRoleModal"] {
  background: linear-gradient(135deg, #15b300 0%, #0e7c00 100%);
  color: white !important;
  margin: 16px 20px;
  border-radius: 12px;
  text-align: center;
  justify-content: center;
  border-left: none !important;
  box-shadow: 0 4px 12px rgba(21, 179, 0, 0.3);
  font-weight: 600;
}
.mobile-nav a[onclick*="openRoleModal"]:hover {
  background: linear-gradient(135deg, #0e7c00 0%, #0a5a00 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(21, 179, 0, 0.4);
  padding-left: 24px;
}
.mobile-nav a[onclick*="openRoleModal"]::before {
  display: none;
}
</style>

<!-- Services dropdown toggle script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  const servicesToggle = document.getElementById('services-toggle');
  const servicesSubmenu = document.getElementById('services-submenu');
  const servicesArrow = document.getElementById('services-arrow');

  if (servicesToggle && servicesSubmenu && servicesArrow) {
    servicesToggle.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation(); // Prevent event from bubbling up
      
      // Toggle the submenu
      if (servicesSubmenu.classList.contains('open')) {
        servicesSubmenu.classList.remove('open');
        servicesSubmenu.style.display = 'none';
        servicesArrow.classList.remove('rotate');
      } else {
        servicesSubmenu.classList.add('open');
        servicesSubmenu.style.display = 'block';
        servicesArrow.classList.add('rotate');
      }
    });
    
    // Prevent submenu clicks from closing the nav
    servicesSubmenu.addEventListener('click', function (e) {
      e.stopPropagation();
    });
  }
});
</script>

  

  <!-- ===== SCRIPT ===== -->
  <script>
  // Make closeNav globally accessible
  window.closeNav = function() {
    const burger = document.getElementById("burger");
    const mobileNav = document.getElementById("mobile-nav");
    const overlay = document.getElementById("mobile-overlay");
    burger.classList.remove("active");
    mobileNav.classList.remove("show");
    overlay.classList.remove("show");
    document.body.style.overflow = "";
  };

  // Function to show main sections and hide login forms
  window.showMainSections = function() {
    // Hide all login forms
    ["login-section", "admin-login-section", "newuser-section"].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.style.display = "none";
    });

    // Hide certificates and incident reports sections
    ["certificates-group", "incident-reports-section"].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.style.display = "none";
    });

    // Show main page sections (not the sub-sections like certificates-group)
    const mainSections = [
      "hero", "about", "services", "officials", "announcements", "contact"
    ];
    mainSections.forEach(id => {
      const el = document.getElementById(id);
      if (el) el.style.display = "block";
    });
  };

  // Function to show a specific section (for certificates, incident reports, etc.)
  window.showSpecificSection = function(sectionId) {
    showMainSections();
    const section = document.getElementById(sectionId);
    if (section) {
      section.style.display = "block";
      // Scroll to the section
      setTimeout(() => {
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }, 100);
    }
  };

  document.addEventListener("DOMContentLoaded", () => {
    const burger = document.getElementById("burger");
    const mobileNav = document.getElementById("mobile-nav");
    const overlay = document.getElementById("mobile-overlay");

    // Toggle Mobile Nav
    const toggleNav = () => {
      const active = burger.classList.toggle("active");
      mobileNav.classList.toggle("show", active);
      overlay.classList.toggle("show", active);
      document.body.style.overflow = active ? "hidden" : "";
    };

    burger.addEventListener("click", toggleNav);
    overlay.addEventListener("click", closeNav);

    // Close nav when clicking any link in mobile nav (prevent default and explicitly scroll)
    mobileNav.querySelectorAll("a").forEach(link => {
      link.addEventListener("click", (e) => {
        const href = link.getAttribute("href");
        
        // Skip the services toggle link - it has its own handler
        if (link.id === 'services-toggle') {
          return;
        }
        
        // Check if this link is inside the Services dropdown submenu
        const isSubmenuLink = link.closest('#services-submenu') !== null;
        
        // ensure we control navigation and scrolling
        if (href && href.startsWith("#")) {
          e.preventDefault();
          
          const sectionId = href.substring(1);
          
          // If it's a submenu link (Certificates or Incident Reports), don't close the nav
          if (isSubmenuLink && (sectionId === "certificates-group" || sectionId === "incident-reports-section")) {
            showSpecificSection(sectionId);
            // Keep the nav open - don't call closeNav()
          } else {
            // For other links, close the nav as usual
            closeNav();
            if (sectionId === "certificates-group" || sectionId === "incident-reports-section") {
              showSpecificSection(sectionId);
            } else {
              showMainSections();
              // scroll to target if it exists (delay slightly to allow nav close animation)
              const targetEl = document.getElementById(sectionId);
              if (targetEl) setTimeout(() => targetEl.scrollIntoView({ behavior: 'smooth' }), 150);
            }
          }
        } else if (href !== 'javascript:void(0)') {
          // non-hash links (e.g., profile/login) - just close the nav and allow default if it's a real url
          closeNav();
        }
      });
    });

    // Also handle desktop nav links to show main sections
    const desktopLinks = document.getElementById("desktop-links");
    if (desktopLinks) {
      desktopLinks.querySelectorAll("a[href^='#']").forEach(link => {
        link.addEventListener("click", (e) => {
          const href = link.getAttribute("href");
          if (href && href.startsWith("#")) {
            e.preventDefault();
            const sectionId = href.substring(1);
            if (sectionId === "certificates-group" || sectionId === "incident-reports-section") {
              showSpecificSection(sectionId);
            } else {
              showMainSections();
              const targetEl = document.getElementById(sectionId);
              if (targetEl) setTimeout(() => targetEl.scrollIntoView({ behavior: 'smooth' }), 50);
           }
            // update the address bar hash without jumping immediately
            history.replaceState(null, null, href);
          }
        });
      });
    }

    // Desktop dropdown toggle (click + touch support)
    document.querySelectorAll('.dropdown .dropbtn').forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const content = btn.nextElementSibling;
        if (!content || !content.classList.contains('dropdown-content')) return;
        const isOpen = content.style.display === 'block' || content.classList.contains('open');
        // close other dropdowns first
        document.querySelectorAll('.dropdown-content').forEach(d => { d.style.display = 'none'; d.classList.remove('open'); });
        if (!isOpen) { content.style.display = 'block'; content.classList.add('open'); }
      });

      // On touch devices make button respond to touchstart quickly
      btn.addEventListener('touchstart', function (e) {
        e.preventDefault();
        btn.click();
      }, { passive: false });
    });

    // Close any open dropdown when clicking outside
    window.addEventListener('click', function (event) {
      if (!event.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-content').forEach(d => { d.style.display = 'none'; d.classList.remove('open'); });
      }
    });

    // Handle brand link click
    const brandLink = document.querySelector(".brand-link");
    if (brandLink) {
      brandLink.addEventListener("click", (e) => {
        showMainSections();
      });
    }

    const notifBell = document.getElementById("notif-bell");
    const notifBadge = document.getElementById("notif-badge");
    const notifDropdown = document.getElementById("notif-dropdown");
    const notifList = document.getElementById("notif-list");
    const notifClose = document.getElementById("notif-close");
    const notifClear = document.getElementById("notif-clear");

    let notifications = [];

    function fetchNotifications() {
        // Fetch notifications (certificates + admin chat)
        fetch('get_notifications.php')
            .then(res => res.json())
            .then(notifData => {
                notifications = [];
                if (Array.isArray(notifData)) {
                    notifData.forEach(n => {
                        if (n.type === 'admin_chat') {
                            notifications.push({
                                message: n.message,
                                date: n.date,
                                highlight: true
                            });
                        } else {
                            notifications.push({
                                message: n.message,
                                date: n.date,
                                highlight: false
                            });
                        }
                    });
                }
                // Fetch unread jobfinder messages
                fetch('jobfinder.php?unread_count=1')
                    .then(res => res.json())
                    .then(msgData => {
                        if (msgData.count && msgData.count > 0) {
                            notifications.push({
                                message: `You have ${msgData.count} unread Job Finder message(s).`,
                                date: '',
                                highlight: true
                            });
                        }
                        updateNotifUI();
                    })
                    .catch(() => {
                        updateNotifUI();
                    });
            })
            .catch(() => {
                notifications = [];
                updateNotifUI();
            });
    }

    function updateNotifUI() {
        // Show total count (messages + certificates + admin chat)
        if (notifications.length > 0) {
            notifBadge.textContent = notifications.length;
            notifBadge.style.display = 'inline-block';
        } else {
            notifBadge.style.display = 'none';
        }
        notifList.innerHTML = '';
        if (notifications.length === 0) {
            notifList.innerHTML = '<div class="notif-empty">No new notifications.</div>';
        } else {
            notifications.forEach(n => {
                const item = document.createElement('div');
                item.className = 'notif-item' + (n.highlight ? ' unread' : '');
                item.setAttribute('role','listitem');
                item.tabIndex = 0;

                const icon = document.createElement('div'); 
                icon.className='icon'; 
                icon.textContent = '🔔';
                
                const body = document.createElement('div'); 
                body.className = 'body';
                
                const msg = document.createElement('div'); 
                msg.className='message'; 
                msg.style.color = n.highlight ? '#e53935' : '#34a853';
                msg.style.fontWeight = n.highlight ? 'bold' : 'normal';
                msg.textContent = n.message || '(No message)';
                
                const time = document.createElement('div'); 
                time.className='time'; 
                time.textContent = n.date || '';
                
                body.appendChild(msg); 
                body.appendChild(time);
                item.appendChild(icon); 
                item.appendChild(body);

                notifList.appendChild(item);
            });
        }
    }

    if (notifBell) {
        notifBell.addEventListener('click', function(e) {
            // Close mobile nav if it's open to avoid z-index conflicts
            if (window.innerWidth <= 992) {
                closeNav();
            }
            
            notifDropdown.style.display = notifDropdown.style.display === 'block' ? 'none' : 'block';
            if (notifDropdown.style.display === 'block') {
                fetchNotifications();
            }
            e.stopPropagation();
        });
    }
    
    if (notifClose) {
        notifClose.addEventListener('click', function() {
            notifDropdown.style.display = 'none';
        });
    }
    
    if (notifClear) {
        notifClear.addEventListener('click', function() {
            if (!confirm('Clear all notifications?')) return;
            fetch('clear_notifications.php', { method:'POST' })
                .then(() => {
                    notifications = [];
                    updateNotifUI();
                })
                .catch(() => {
                    alert('Unable to clear notifications');
                });
        });
    }
    
    document.addEventListener('click', function(e) {
        if (notifDropdown && !notifDropdown.contains(e.target) && e.target !== notifBell) {
            notifDropdown.style.display = 'none';
        }
    });

    // Poll for notifications every 30 seconds
    if (notifBell && notifBadge && notifList) {
        setInterval(fetchNotifications, 30000);
        fetchNotifications();
    }
  });
  </script>
</body>
</html>



<div id="mobile-menu" class="mobile-menu"></div>

<div id="role-modal" class="modal" style="display:none;">
  <div class="modal-content modern-modal glass-modal animate-modal">
    <div class="modal-header-icon">
      <svg width="56" height="56" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="12" cy="12" r="10" stroke="url(#gradient1)" stroke-width="2" fill="none"/>
        <path d="M12 8V12L15 15" stroke="url(#gradient1)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <defs>
          <linearGradient id="gradient1" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#15b300;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#0e7c00;stop-opacity:1" />
          </linearGradient>
        </defs>
      </svg>
    </div>
    <h3 class="modal-title" style="font-size:1.75rem; font-weight:700; color:#1a1a1a; margin-bottom:0.5rem; letter-spacing:-0.02em;">Select Login Role</h3>
    <p style="color:#6b7280; font-size:0.95rem; margin-bottom:2rem;">Choose your account type to continue</p>
    <div class="role-buttons single-green-vertical">
      <button class="btn role-btn user-btn" onclick="showLoginForm('user')">
        <div class="btn-icon-wrapper">
          <svg class="role-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z" fill="currentColor"/>
            <path d="M12 14C6.47715 14 2 18.4772 2 24H22C22 18.4772 17.5228 14 12 14Z" fill="currentColor" opacity="0.7"/>
          </svg>
        </div>
        <span class="role-text">User Login</span>
        <svg class="arrow-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
      <!-- Admin login removed - admin users use the regular User Login form -->
      <button class="btn role-btn newuser-btn" onclick="showLoginForm('newuser')">
        <div class="btn-icon-wrapper">
          <svg class="role-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/>
            <path d="M12 8V16M8 12H16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>
        <span class="role-text">New User</span>
        <svg class="arrow-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
    </div>
    <button class="btn cancel-btn" onclick="sessionStorage.setItem('internalNav', 'true'); window.location.href='index.php'">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
      Cancel
    </button>
  </div>
</div>

<!-- Login Success Modal -->
<div id="login-success-modal" class="modal" style="display:none;">
  <div class="modal-content modern-modal glass-modal animate-modal" style="text-align:center;">
    <div style="margin-bottom:1.5rem;">
      <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:inline-block;">
        <circle cx="12" cy="12" r="10" fill="#28a745" opacity="0.2"/>
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="#28a745"/>
      </svg>
    </div>
    <h3 class="modal-title" style="font-size:1.8rem; font-weight:700; color:#28a745; margin-bottom:0.8rem; letter-spacing:0.5px;">Login Successful!</h3>
    <p style="color:#666; font-size:1rem; margin-bottom:1.5rem;">Welcome back! You have successfully logged in.</p>
    <button class="btn" style="background:#28a745; color:#fff; padding:12px 32px; border-radius:10px; border:none; font-size:1rem; font-weight:600; cursor:pointer; transition:all 0.3s;" onclick="closeLoginSuccessModal()">Continue</button>
  </div>
</div>

<!-- Generic Notification Modal -->
<div id="notification-modal" class="modal" style="display:none;">
  <div class="modal-content modern-modal glass-modal animate-modal" style="text-align:center;">
    <div id="notification-icon" style="margin-bottom:1.5rem;">
      <!-- Icon will be inserted dynamically -->
    </div>
    <h3 id="notification-title" class="modal-title" style="font-size:1.8rem; font-weight:700; margin-bottom:0.8rem; letter-spacing:0.5px;"></h3>
    <p id="notification-message" style="color:#666; font-size:1rem; margin-bottom:1.5rem;"></p>
    <button class="btn" id="notification-btn" style="padding:12px 32px; border-radius:10px; border:none; font-size:1rem; font-weight:600; cursor:pointer; transition:all 0.3s;" onclick="closeNotificationModal()">OK</button>
  </div>
</div>
  
<style>
  /* Modal Overlay */
  .modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(30, 30, 40, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 999;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    animation: fadeInBg 0.4s ease-out;
  }

  /* Glassmorphism Modal Box */
  .glass-modal {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 28px;
    box-shadow: 0 25px 80px rgba(0,0,0,0.15), 0 0 0 1px rgba(0,0,0,0.08), 0 8px 32px rgba(21, 179, 0, 0.1);
    padding: 2.5rem 2rem 2rem 2rem;
    width: 520px;
    max-width: 95%;
    text-align: center;
    position: relative;
    overflow: hidden;
  }

  .glass-modal::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #15b300, #0e7c00, #15b300);
    background-size: 200% 100%;
    animation: shimmer 3s linear infinite;
  }

  .modal-header-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.5rem;
    background: linear-gradient(135deg, rgba(21, 179, 0, 0.1) 0%, rgba(14, 124, 0, 0.1) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: float 3s ease-in-out infinite;
    box-shadow: 0 8px 24px rgba(21, 179, 0, 0.15);
  }

  .modal-header-icon svg {
    animation: rotate 4s linear infinite;
  }

  .animate-modal {
    animation: scaleInModal 0.5s cubic-bezier(.4,2,.6,1);
  }
  
  @keyframes scaleInModal {
    from { 
      transform: scale(0.85) translateY(20px); 
      opacity: 0; 
    }
    to { 
      transform: scale(1) translateY(0); 
      opacity: 1; 
    }
  }
  
  @keyframes fadeInBg {
    from { opacity: 0; }
    to { opacity: 1; }
  }

  @keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
  }

  @keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
  }

  @keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
  }

  @keyframes ripple {
    0% {
      transform: scale(0);
      opacity: 0.6;
    }
    100% {
      transform: scale(2.5);
      opacity: 0;
    }
  }

  .modal-title {
    margin-bottom: 1.5rem;
    font-size: 1.35rem;
    font-weight: 700;
    color: #222;
    letter-spacing: 0.5px;
    text-shadow: 0 1px 0 #fff;
  }

  /* Button Styles */
  .btn {
    border: none;
    outline: none;
    cursor: pointer;
    font-size: 1rem;
    padding: 1.2rem 1.5rem;
    border-radius: 16px;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    min-width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    position: relative;
    background: #f8f9fa;
    color: #1a1a1a;
    border: 2px solid transparent;
    overflow: hidden;
  }

  .btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
  }

  .btn:active::before {
    width: 300px;
    height: 300px;
  }

  .btn:active {
    transform: scale(0.97);
  }

  .btn.role-btn {
    background: linear-gradient(135deg, #15b300 0%, #0e7c00 100%);
    color: #ffffff;
    box-shadow: 0 4px 16px rgba(21, 179, 0, 0.25);
    animation: slideInUp 0.5s ease-out backwards;
  }

  .btn.role-btn:nth-child(1) { animation-delay: 0.1s; }
  .btn.role-btn:nth-child(2) { animation-delay: 0.2s; }
  .btn.role-btn:nth-child(3) { animation-delay: 0.3s; }

  .btn.role-btn:hover {
    box-shadow: 0 12px 32px rgba(21, 179, 0, 0.35);
    transform: translateY(-3px) scale(1.02);
    background: linear-gradient(135deg, #17c700 0%, #0f8a00 100%);
  }

  .btn-icon-wrapper {
    width: 44px;
    height: 44px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.3s ease;
  }

  .btn.role-btn:hover .btn-icon-wrapper {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(10deg) scale(1.1);
  }

  .btn.role-btn .role-icon {
    width: 24px;
    height: 24px;
    flex-shrink: 0;
    transition: transform 0.3s ease;
  }

  .btn.role-btn:hover .role-icon {
    transform: scale(1.1);
  }

  .btn.role-btn .role-text {
    flex: 1;
    text-align: left;
    font-size: 1.05rem;
    letter-spacing: -0.01em;
    margin-left: 8px;
  }

  .btn.role-btn .arrow-icon {
    width: 20px;
    height: 20px;
    opacity: 0.7;
    transition: all 0.3s ease;
  }

  .btn.role-btn:hover .arrow-icon {
    opacity: 1;
    transform: translateX(4px);
  }

  @keyframes slideInUp {
    from {
      opacity: 0;
      transform: translateY(20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
  .btn.cancel-btn {
    background: #f3f4f6;
    color: #6b7280;
    width: 100%;
    font-weight: 600;
    margin-top: 1rem;
    box-shadow: none;
    border-radius: 14px;
    padding: 1rem 0;
    transition: all 0.3s ease;
    justify-content: center;
    border: 2px solid #e5e7eb;
    gap: 8px;
    animation: slideInUp 0.5s ease-out 0.4s backwards;
  }

  .btn.cancel-btn svg {
    transition: transform 0.3s ease;
  }

  .btn.cancel-btn:hover {
    background: #e5e7eb;
    color: #374151;
    border-color: #d1d5db;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  }

  .btn.cancel-btn:hover svg {
    transform: rotate(90deg);
  }

  .role-buttons.single-green-vertical {
    display: flex;
    flex-direction: column;
    gap: 14px;
    justify-content: center;
    align-items: stretch;
    margin-bottom: 1.5rem;
  }

  /* Hide admin login button on mobile devices */
  @media (max-width: 768px) {
    .admin-login-btn {
      display: none !important;
    }
  }

  /* Responsive adjustments for role modal */
  @media (max-width: 576px) {
    .glass-modal {
      padding: 2rem 1.5rem 1.5rem 1.5rem;
      border-radius: 20px;
    }
    .modal-title {
      font-size: 1.5rem !important;
    }
    .btn.role-btn {
      padding: 1rem 1.2rem;
      font-size: 0.95rem;
    }
    .btn.role-btn .role-text {
      font-size: 1rem;
    }
    .btn.cancel-btn {
      padding: 0.9rem 0;
      font-size: 0.95rem;
    }
  }
</style>


<!-- Admin Login -->
<section id="admin-login-section" class="login-form-section" style="display:none;">
  <div class="login-container">
    <!-- Form Side -->
    <div class="login-form-side">
      <div class="login-header">
        <img src="logo.jpg" alt="Barangay Logo" class="login-logo">
        <h2 class="login-title">Admin Login</h2>
        <p class="login-subtitle">Sign in as barangay administrator.</p>
      </div>
      <form method="POST" action="login.php" class="login-form">
        <div class="form-group">
          <label for="admin-username" class="form-label">Admin ID</label>
          <input type="text" id="admin-username" name="admin_username" required class="form-input" placeholder="Enter your admin ID">
        </div>
        <div class="form-group">
          <label for="admin-password" class="form-label">Password</label>
          <div class="password-input-wrapper">
            <input type="password" id="admin-password" name="admin_password" required class="form-input has-toggle" placeholder="Enter your password">
            <button type="button" class="password-toggle-btn" onclick="togglePassword('admin-password', this)">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>
        <button type="submit" class="form-submit-btn">Sign In</button>
      </form>
    </div>
    <!-- Image Side -->
    <div class="login-image-side">
      <img src="brgy.jpg" alt="Barangay Sabang" class="login-bg-image">
    </div>
  </div>
</section>

<!-- User Login -->
<section id="login-section" class="login-form-section" style="display:none;">
  <div class="login-container">
    <!-- Form Side -->
    <div class="login-form-side">
      <div class="login-header">
        <img src="logo.jpg" alt="Barangay Logo" class="login-logo">
        <h2 class="login-title">Sign In to Barangay Sabang</h2>
        <p class="login-subtitle">Welcome back! Please login to your account.</p>
      </div>
      <form method="POST" action="login.php" class="login-form">
        <div class="form-group">
          <label for="login-username" class="form-label">User ID</label>
          <input type="text" id="login-username" name="username" required class="form-input" placeholder="Enter your user ID">
        </div>
        <div class="form-group">
          <label for="login-password" class="form-label">Password</label>
          <div class="password-input-wrapper">
            <input type="password" id="login-password" name="password" required class="form-input has-toggle" placeholder="Enter your password">
            <button type="button" class="password-toggle-btn" onclick="togglePassword('login-password', this)">
              <i class="fas fa-eye"></i>
            </button>
          </div>
          <a href="forgot-password.php" class="forgot-password-link">Forgot Password?</a>
        </div>
        <div class="form-group">
          <label for="captcha" class="form-label">CAPTCHA</label>
          <div style="display: flex; align-items: center; gap: 10px;">
            <img src="captcha.php" alt="CAPTCHA" class="captcha-image" id="captcha-image">
            <button type="button" class="captcha-refresh-btn" onclick="refreshCaptcha()" title="Get new CAPTCHA">
              <i class="fas fa-sync-alt"></i>
            </button>
          </div>
          <input type="text" id="captcha" name="captcha" maxlength="5" required placeholder="Enter code above" class="form-input">
        </div>
          <button type="submit" class="form-submit-btn">Sign In</button>
      </form>
    </div>
    <!-- Image Side -->
    <div class="login-image-side">
      <img src="brgy.jpg" alt="Barangay Sabang" class="login-bg-image">
    </div>
  </div>
</section>

<!-- New User Registration -->
<section id="newuser-section" class="login-form-section" style="display:none;">
  <div class="login-container">
    <!-- Form Side -->
    <div class="login-form-side">
      <div class="login-header">
        <img src="logo.jpg" alt="Barangay Logo" class="login-logo">
        <h2 class="login-title">Register New Account</h2>
        <p class="login-subtitle">Start your journey as a resident of Barangay Sabang.</p>
      </div>
      <form method="POST" action="login.php" class="login-form">
        <div class="form-group">
          <label for="new-username" class="form-label">User ID</label>
          <input type="text" id="new-username" name="new_username" required class="form-input" placeholder="Enter your user ID">
        </div>
        <div class="form-group">
          <label for="new-password" class="form-label">Password (minimum 9 characters)</label>
          <div class="password-input-wrapper">
            <input type="password" id="new-password" name="new_password" required minlength="9" class="form-input has-toggle" placeholder="Create a password ">
            <button type="button" class="password-toggle-btn" onclick="togglePassword('new-password', this)">
              <i class="fas fa-eye"></i>
            </button>
          </div>
          <small id="password-hint" style="color:#64748b; font-size:0.8rem; margin-top:4px; display:block;">
            <i class="fas fa-info-circle"></i> Password must be at least 9 characters long
          </small>
        </div>
        <div class="form-group">
          <label for="confirm-password" class="form-label">Confirm Password</label>
          <div class="password-input-wrapper">
            <input type="password" id="confirm-password" name="confirm_password" required minlength="9" class="form-input has-toggle" placeholder="Confirm your password">
            <button type="button" class="password-toggle-btn" onclick="togglePassword('confirm-password', this)">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>
        <button type="submit" class="form-submit-btn" id="registerSubmitBtn">Register</button>
      </form>
      <p class="login-footer-text">Already have an account? <a href="javascript:void(0);" onclick="showLoginForm('user')" class="login-footer-link">Sign In</a></p>
    </div>
    <!-- Image Side -->
    <div class="login-image-side">
      <img src="brgy.jpg" alt="Barangay Sabang" class="login-bg-image">
    </div>
  </div>
</section>

<script>
// Registration form validation
document.addEventListener('DOMContentLoaded', function() {
  const registerForm = document.querySelector('#newuser-section form');
  
  if (registerForm) {
    registerForm.addEventListener('submit', function(e) {
      const password = document.getElementById('new-password').value;
      const confirmPassword = document.getElementById('confirm-password').value;
      
      // Check password length
      if (password.length < 9) {
        e.preventDefault();
        showNotificationModal('warning', 'Password Too Short', 'Password must be at least 9 characters long. Please create a stronger password.');
        return false;
      }
      
      // Check if passwords match
      if (password !== confirmPassword) {
        e.preventDefault();
        showNotificationModal('error', 'Password Mismatch', 'Passwords do not match. Please make sure both passwords are identical.');
        return false;
      }
    });
    
    // Real-time password validation feedback
    const newPasswordInput = document.getElementById('new-password');
    const passwordHint = document.getElementById('password-hint');
    
    if (newPasswordInput && passwordHint) {
      newPasswordInput.addEventListener('input', function() {
        const length = this.value.length;
        
        if (length === 0) {
          passwordHint.style.color = '#64748b';
          passwordHint.innerHTML = '<i class="fas fa-info-circle"></i> Password must be at least 9 characters long';
        } else if (length < 9) {
          passwordHint.style.color = '#ef4444';
          passwordHint.innerHTML = '<i class="fas fa-exclamation-circle"></i> Too short! ' + (9 - length) + ' more character(s) needed';
        } else {
          passwordHint.style.color = '#15b300';
          passwordHint.innerHTML = '<i class="fas fa-check-circle"></i> Password length is good!';
        }
      });
    }
  }
});
</script>

<script>
function openRoleModal() {
  document.getElementById('role-modal').style.display = 'flex';
  hideAllForms();
}

function closeRoleModal() {
  document.getElementById('role-modal').style.display = 'none';
}

function closeLoginSuccessModal() {
  const successModal = document.getElementById('login-success-modal');
  if (successModal) {
    successModal.style.display = 'none';
    // Clean URL
    window.history.replaceState({}, document.title, window.location.pathname);
  }
}

function showNotificationModal(type, title, message) {
  const modal = document.getElementById('notification-modal');
  const iconDiv = document.getElementById('notification-icon');
  const titleEl = document.getElementById('notification-title');
  const messageEl = document.getElementById('notification-message');
  const btnEl = document.getElementById('notification-btn');
  
  // Set content
  titleEl.textContent = title;
  messageEl.textContent = message;
  
  // Set icon and colors based on type
  if (type === 'error') {
    iconDiv.innerHTML = `
      <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:inline-block;">
        <circle cx="12" cy="12" r="10" fill="#dc3545" opacity="0.2"/>
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" fill="#dc3545"/>
      </svg>
    `;
    titleEl.style.color = '#dc3545';
    btnEl.style.background = '#dc3545';
  } else if (type === 'success') {
    iconDiv.innerHTML = `
      <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:inline-block;">
        <circle cx="12" cy="12" r="10" fill="#28a745" opacity="0.2"/>
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="#28a745"/>
      </svg>
    `;
    titleEl.style.color = '#28a745';
    btnEl.style.background = '#28a745';
  } else if (type === 'warning') {
    iconDiv.innerHTML = `
      <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:inline-block;">
        <circle cx="12" cy="12" r="10" fill="#ffc107" opacity="0.2"/>
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" fill="#ffc107"/>
      </svg>
    `;
    titleEl.style.color = '#ffc107';
    btnEl.style.background = '#ffc107';
  }
  
  btnEl.style.color = '#fff';
  modal.style.display = 'flex';
}

function closeNotificationModal() {
  const modal = document.getElementById('notification-modal');
  if (modal) {
    modal.style.display = 'none';
    // Clean URL
    window.history.replaceState({}, document.title, window.location.pathname);
  }
}

function hideAllForms() {
  ["login-section", "admin-login-section", "newuser-section"].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.style.display = "none"; // must use "none"
  });
}


function showLoginForm(role) {
  hideAllForms(); // hide all login forms
  document.getElementById('role-modal').style.display = 'none'; // hide modal

  // Hide main page sections (if required)
  const mainSections = [
    "hero", "about", "services", "officials", "announcements", 
    "job-finder-section", "contact" 
  ];
  mainSections.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.style.display = "none";
  });

  // Map role to section and input. Use unified user login for admin as well.
  const formMap = {
    'user': { section: 'login-section', input: 'login-username' },
    'admin': { section: 'login-section', input: 'login-username' }, // unified form
    'newuser': { section: 'newuser-section', input: 'new-username' }
  };

  if (formMap[role]) {
    const sectionEl = document.getElementById(formMap[role].section);
    if (sectionEl) {
      sectionEl.style.display = 'flex'; // show selected form
      const inputEl = document.getElementById(formMap[role].input);
      if (inputEl) inputEl.focus(); // focus first input
    }
  }
 
}

</script>

<script>
window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);

    if (urlParams.get('login') === 'success') {
      // Show success modal
      const successModal = document.getElementById('login-success-modal');
      if (successModal) {
        successModal.style.display = 'flex';
        // Auto-close after 3 seconds
        setTimeout(() => {
          successModal.style.display = 'none';
          // Clean URL
          window.history.replaceState({}, document.title, window.location.pathname);
        }, 3000);
      }
    }

    if (urlParams.get('login') === 'fail') {
      let reason = urlParams.get('reason');
      if (reason === 'captcha') {
        showNotificationModal('error', 'Login Failed', 'Incorrect CAPTCHA code. Please try again.');
      } else {
        showNotificationModal('error', 'Login Failed', 'Wrong password. Please check your credentials and try again.');
      }
      // Reset user login fields and show user login form only
      const userForm = document.querySelector('#login-section form');
      if (userForm) {
        userForm.reset();
        document.getElementById('login-section').style.display = 'flex';
      }
      // Hide admin and newuser forms
      const adminSection = document.getElementById('admin-login-section');
      if (adminSection) adminSection.style.display = 'none';
      const newuserSection = document.getElementById('newuser-section');
      if (newuserSection) newuserSection.style.display = 'none';
        // Hide all other main sections
        ["hero", "about", "services", "officials", "announcements", "job-finder-section", "contact"].forEach(id => {
          const el = document.getElementById(id);
          if (el) el.style.display = 'none';
        });
    }

    if (urlParams.get('login') === 'notfound') {
        showNotificationModal('error', 'Login Failed', 'User ID not found. Please check your credentials.');
    }

    if (urlParams.get('register') === 'fail') {
        showNotificationModal('error', 'Registration Failed', 'User does not exist or already has a password.');
    }

    if (urlParams.get('register') === 'nomatch') {
        showNotificationModal('error', 'Password Mismatch', 'Passwords do not match! Please try again.');
    }

    if (urlParams.get('register') === 'success') {
        showNotificationModal('success', 'Registration Successful', 'Password set successfully! You are now logged in.');
    }

    if (urlParams.get('set_password') === 'required') {
        showNotificationModal('warning', 'Password Required', 'Your account requires a password. Please set your password.');
    }

    if (urlParams.get('admin_login') === 'fail') {
        showNotificationModal('error', 'Admin Login Failed', 'Wrong credentials. Please check your admin ID and password.');
      // Reset admin login attempt and show unified user login form (admins use same form now)
      const userForm = document.querySelector('#login-section form');
      if (userForm) {
        userForm.reset();
        document.getElementById('login-section').style.display = 'flex';
      }
      // Hide admin-specific and newuser forms
      const adminSection = document.getElementById('admin-login-section');
      if (adminSection) adminSection.style.display = 'none';
      const newuserSection = document.getElementById('newuser-section');
      if (newuserSection) newuserSection.style.display = 'none';
        // Hide all other main sections
        ["hero", "about", "services", "officials", "announcements", "job-finder-section", "contact"].forEach(id => {
          const el = document.getElementById(id);
          if (el) el.style.display = 'none';
        });
    }
});
</script>

<?php if ($showLogin): ?>
<?php endif; ?>

  <div id="mobile-menu" class="mobile-menu"></div>

  <section class="hero" id="hero">
    <div class="container">
      <h1>Welcome to Barangay Sabang</h1>
      <p>A peaceful and progressive community dedicated to serving its residents</p>
      <a href="#contact">Contact Us</a>
    </div>
  </section>
  <script>
    const heroSection = document.getElementById('hero');
    const backgrounds = [
      'url("background.jpg")'
      
    ];
    let bgIndex = 0;
    let slideInterval;
    
    function changeBackground() {
      // Add fade transition
      heroSection.style.transition = 'background-image 0.5s ease-in-out';
      
      // On mobile, use only background.jpg
      if (window.innerWidth <= 768) {
        heroSection.style.backgroundImage = 'url("background.jpg")';
        heroSection.style.backgroundPosition = 'center center';
        heroSection.style.backgroundAttachment = 'scroll'; // Better performance on mobile
      } else {
        heroSection.style.backgroundImage = backgrounds[bgIndex];
        heroSection.style.backgroundAttachment = 'fixed';
        bgIndex = (bgIndex + 1) % backgrounds.length;
      }
      
      heroSection.style.backgroundSize = 'cover';
      heroSection.style.backgroundPosition = 'center';
    }
    
    // Start slideshow
    function startSlideshow() {
      // Clear any existing interval
      if (slideInterval) clearInterval(slideInterval);
      
      // Adjust interval based on device
      const interval = window.innerWidth <= 768 ? 4000 : 3000; // Slower on mobile
      slideInterval = setInterval(changeBackground, interval);
    }
    
    // Initialize
    changeBackground();
    startSlideshow();
    
    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function() {
        changeBackground();
        startSlideshow();
      }, 250);
    });
    
    // Pause slideshow when page is not visible (performance optimization)
    document.addEventListener('visibilitychange', function() {
      if (document.hidden) {
        if (slideInterval) clearInterval(slideInterval);
      } else {
        startSlideshow();
      }
    });
    
    // Touch swipe support for mobile
    if ('ontouchstart' in window) {
      let touchStartX = 0;
      let touchEndX = 0;
      
      heroSection.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
      }, { passive: true });
      
      heroSection.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
      }, { passive: true });
      
      function handleSwipe() {
        const swipeThreshold = 50;
        if (touchEndX < touchStartX - swipeThreshold) {
          // Swipe left - next slide
          changeBackground();
          startSlideshow(); // Restart interval
        } else if (touchEndX > touchStartX + swipeThreshold) {
          // Swipe right - previous slide
          bgIndex = (bgIndex - 2 + backgrounds.length) % backgrounds.length;
          changeBackground();
          startSlideshow(); // Restart interval
        }
      }
    }
  </script>

<section id="about">
  <div class="container">
    <h2>About Our Barangay</h2>
    <div class="section-divider"></div>
    <div class="about-content">
      
      <!-- IMAGE ON LEFT -->
      <div class="about-image">
        <img 
          src="brgy.jpg" 
          alt="Barangay Sabang" 
          style="width: 100%; border-radius: 8px; height: 100%;">
      </div>

      <!-- TEXT ON RIGHT -->
      <div class="about-text">
        <h3>Barangay Sabang</h3>
        <p>Barangay Sabang is a vibrant community located in the northern part of Dasmariñas City, Cavite. The name “Sabang” comes from a local word meaning “to overflow,” inspired by the creeks that run through the area.</p>

        <p id="about-history">
          Historically, Sabang was a farming village known for its fertile rice fields. Over time, it has transformed into a modern residential barangay with subdivisions, schools, and local businesses. It is also famous as the birthplace of Leonardo “Nardong Putik,” a well-known folk hero. Today, Barangay Sabang continues to grow while preserving its strong sense of community and rich local history.
        </p>

        <div class="grid">
          <div><h4>Population</h4><p>5,287 (2023)</p></div>
          <div><h4>Area</h4><p>2.5 sq km</p></div>
          <div><h4>Puroks</h4><p>8</p></div>
        </div>
      </div>
      
    </div>
  </div>
</section>



  <section id="services">
  <div class="container">

<?php
// Fetch only enabled certificates from the database
$certs = $conn->query("SELECT certificate_name FROM certificate_options WHERE is_enabled = 1");
?>

<?php if ($certs->num_rows > 0): ?>
<div id="certificates-group" class="service-card-group" style="display:none";>
  <h3 id="certificates-heading">Available Certificates</h3>

  <?php while($cert = $certs->fetch_assoc()): 
      $name = $cert['certificate_name']; 
  ?>
  <div class="services-card certificate-card" data-cert="<?= htmlspecialchars($name) ?>" role="button" tabindex="0">
          <h3><?= htmlspecialchars($name) ?></h3>
          <p>
            <?php 
            // Custom description per certificate
            switch ($name) {
                case 'Barangay Clearance':
                    echo "Apply for a barangay clearance for employment, travel, or other purposes.";
                    break;
                case 'Certificate of Indigency':
                    echo "Certifies that the applicant is an indigent resident of the barangay.";
                    break;
                case 'Certificate of Residency':
                    echo "Verifies your current address within Barangay Sabang for various purposes.";
                    break;
                default:
                    echo "Description not available.";
            }
            ?>
          </p>
      </div>
  <?php endwhile; ?>
</div>
<?php endif; ?>

<div id="confirmation-modal" class="modal" style="display: none;">
  <div class="modal-content">
    <h3 id="modal-cert-title">Certificate Confirmation</h3>
    <div class="modal-body">
      <p><strong>Last Name:</strong> <span id="last-name"></span></p>
      <p><strong>First Name:</strong> <span id="first-name"></span></p>
      <p><strong>Middle Name:</strong> <span id="middle-name"></span></p>
      <p><strong>Address:</strong> <span id="address"></span></p>
      <p><strong>Gender:</strong> <span id="gender"></span></p>
      <p><strong>Citizenship:</strong> <span id="citizenship"></span></p>
      <p><strong>Age:</strong> <span id="age"></span></p>
      <p><strong>Birthday:</strong> <span id="birthday"></span></p>
    </div>

    <label for="purpose-select"><strong>Purpose:</strong></label>
    <div style="position: relative;">
      <select id="purpose-select">
        <option value="" selected>Select a purpose...</option>
        <option value="Educational Assistance">Educational Assistance</option>
        <option value="Scholarship Application">Scholarship Application</option>
        <option value="School Enrollment">School Enrollment</option>
        <option value="Employment">Employment</option>
        <option value="Job Application">Job Application</option>
        <option value="Local Employment">Local Employment</option>
        <option value="Overseas Employment">Overseas Employment</option>
        <option value="Travel">Travel</option>
        <option value="Passport Application">Passport Application</option>
        <option value="Visa Application">Visa Application</option>
        <option value="Medical Assistance">Medical Assistance</option>
        <option value="Hospital Admission">Hospital Admission</option>
        <option value="PhilHealth">PhilHealth</option>
        <option value="Financial Assistance">Financial Assistance</option>
        <option value="Loan Application">Loan Application</option>
        <option value="Bank Requirements">Bank Requirements</option>
        <option value="Business Permit">Business Permit</option>
        <option value="Business Registration">Business Registration</option>
        <option value="Legal Requirements">Legal Requirements</option>
        <option value="Court Requirements">Court Requirements</option>
        <option value="Police Clearance">Police Clearance</option>
        <option value="Government Transaction">Government Transaction</option>
        <option value="SSS">SSS</option>
        <option value="GSIS">GSIS</option>
        <option value="Pag-IBIG">Pag-IBIG</option>
        <option value="Voter's Registration">Voter's Registration</option>
        <option value="Senior Citizen Application">Senior Citizen Application</option>
        <option value="PWD Application">PWD Application</option>
        <option value="4Ps Application">4Ps Application</option>
        <option value="Housing Loan">Housing Loan</option>
        <option value="Insurance">Insurance</option>
        <option value="Postal ID">Postal ID</option>
        <option value="Driver's License">Driver's License</option>
        <option value="Marriage License">Marriage License</option>
        <option value="Death Certificate">Death Certificate</option>
        <option value="Birth Certificate">Birth Certificate</option>
        <option value="custom">✏️ Type your own...</option>
      </select>
    </div>
    
    <div id="custom-purpose-container" style="display: none; margin-top: 12px;">
      <input type="text" id="custom-purpose-input" placeholder="Type your custom purpose..." style="margin: 0;">
    </div>
    
    <textarea id="purpose-description" rows="3" placeholder="Add additional details (optional)..." style="display: none; margin-top: 12px;"></textarea>

    <div class="modal-actions">
      <button id="submit-btn" class="btn primary">Submit</button>
      <button id="cancel-btn" class="btn secondary">Cancel</button>
    </div>
  </div>
</div>

<div id="success-modal" class="modal" style="display: none;">
  <div class="modal-content success">
    <img src="https://png.pngtree.com/png-clipart/20230918/original/pngtree-glitter-mark-and-check-mark-icon-vectors-successful-form-illustration-vector-png-image_12613161.png" 
          alt="Success" width="100" style="display:block; margin: 0 auto 15px auto;">
    <h3>Submitted Successfully!</h3>
    <div id="submitted-info" style="font-size:14px; text-align:left;"></div>
  </div>
</div>

<style>
/* ===== Modal Styles ===== */
.modal { 
  position: fixed; 
  top: 0; 
  left: 0; 
  width: 100%; 
  height: 100%; 
  background: rgba(0, 0, 0, 0.6); 
  display: flex; 
  justify-content: center; 
  align-items: center; 
  z-index: 9999; 
  padding: 20px;
  overflow-y: auto;
}

.modal-content { 
  background: #fff; 
  border-radius: 16px; 
  padding: 28px 32px; 
  width: 540px; 
  max-width: 95%; 
  box-shadow: 0px 12px 32px rgba(0, 0, 0, 0.2); 
  animation: fadeIn 0.3s ease-in-out;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-content h3 { 
  margin-top: 0; 
  text-align: center; 
  font-size: 1.5rem; 
  font-weight: 700; 
  color: #1a1a1a; 
  border-bottom: 2px solid #e9ecef; 
  padding-bottom: 14px; 
  margin-bottom: 24px; 
  line-height: 1.3;
  word-wrap: break-word;
}

.modal-body { 
  margin-bottom: 24px; 
}

.modal-content p { 
  margin: 10px 0; 
  font-size: 0.95rem; 
  color: #495057; 
  text-align: left; 
  line-height: 1.6;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.modal-content p strong { 
  font-weight: 600; 
  color: #1a1a1a;
  min-width: 120px;
}

.modal-content p span { 
  font-weight: 400; 
  word-break: break-word;
  flex: 1;
}

.modal-content label { 
  display: block; 
  font-weight: 600; 
  color: #1a1a1a; 
  margin-bottom: 8px; 
  font-size: 0.95rem; 
}

#confirmation-modal input[type="text"],
#confirmation-modal select,
#confirmation-modal textarea { 
  width: 100%; 
  padding: 12px 14px; 
  border: 2px solid #e9ecef; 
  border-radius: 10px; 
  font-size: 0.95rem; 
  font-family: inherit; 
  transition: all 0.3s ease; 
  box-sizing: border-box;
  background-color: #fff;
}

#confirmation-modal select {
  cursor: pointer;
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right 12px center;
  background-size: 20px;
  padding-right: 40px;
  margin: 0 0 12px 0;
}

#confirmation-modal input[type="text"] {
  margin: 0 0 12px 0;
}

#confirmation-modal textarea { 
  resize: vertical; 
  min-height: 80px;
  margin: 0 0 24px 0; 
}

#confirmation-modal input[type="text"]:focus,
#confirmation-modal select:focus,
#confirmation-modal textarea:focus { 
  outline: none; 
  border-color: #007bff; 
  box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.1); 
}

.modal-actions { 
  display: flex; 
  justify-content: center; 
  gap: 12px;
  flex-wrap: wrap;
}

.modal-actions .btn { 
  flex: 1; 
  min-width: 140px;
  padding: 14px 24px; 
  border-radius: 10px; 
  border: none; 
  font-size: 1rem; 
  font-weight: 600; 
  cursor: pointer; 
  transition: all 0.3s ease;
  white-space: nowrap;
}

.btn.primary { 
  background: #007bff; 
  color: white; 
  box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3); 
}

.btn.primary:hover { 
  background: #0056b3; 
  transform: translateY(-2px); 
  box-shadow: 0 6px 16px rgba(0, 123, 255, 0.4); 
}

.btn.secondary { 
  background: #f8f9fa; 
  color: #495057; 
  border: 2px solid #dee2e6; 
}

.btn.secondary:hover { 
  background: #e9ecef; 
  transform: translateY(-2px); 
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.btn:active { 
  transform: translateY(0); 
}

.modal-content.success { 
  text-align: center; 
  padding: 36px 32px; 
}

.modal-content.success h3 { 
  color: #28a745; 
  border-bottom: none; 
}

@keyframes fadeIn { 
  from { opacity: 0; transform: scale(0.95); } 
  to { opacity: 1; transform: scale(1); } 
}

/* Tablet Responsive Styles */
@media (max-width: 768px) {
  .modal-content { 
    padding: 24px 24px; 
    width: 90%;
  }
  
  .modal-content h3 { 
    font-size: 1.3rem; 
  }
  
  .modal-actions .btn {
    min-width: 120px;
    padding: 12px 20px;
  }
}

/* Mobile Responsive Styles */
@media (max-width: 576px) {
  .modal { 
    padding: 16px;
    align-items: flex-start;
    padding-top: 40px;
  }
  
  .modal-content { 
    padding: 24px 20px; 
    border-radius: 14px;
    width: 100%;
    max-width: 100%;
  }
  
  .modal-content h3 { 
    font-size: 1.15rem; 
    padding-bottom: 12px; 
    margin-bottom: 20px;
    line-height: 1.4;
  }
  
  .modal-body {
    margin-bottom: 20px;
  }
  
  .modal-content p { 
    margin: 8px 0;
    font-size: 0.9rem;
    flex-direction: column;
    gap: 4px;
  }
  
  .modal-content p strong {
    min-width: auto;
  }
  
  .modal-actions { 
    flex-direction: column; 
    gap: 10px;
    width: 100%;
  }
  
  .modal-actions .btn { 
    width: 100%;
    min-width: auto;
    padding: 14px 20px; 
    font-size: 1rem;
  }
  
  #confirmation-modal textarea { 
    font-size: 1rem; 
    padding: 12px;
    min-height: 100px;
  }
  
  .modal-content.success {
    padding: 28px 20px;
  }
}

/* Mobile optimizations for select and inputs */
@media (max-width: 768px) {
  #confirmation-modal select,
  #custom-purpose-input {
    font-size: 16px; /* Prevents zoom on iOS */
    min-height: 44px; /* Better touch target */
  }
  
  #confirmation-modal select option {
    padding: 10px;
  }
}

/* Extra small devices */
@media (max-width: 380px) {
  .modal-content h3 {
    font-size: 1.05rem;
  }
  
  .modal-content p {
    font-size: 0.85rem;
  }
  
  .modal-actions .btn {
    font-size: 0.95rem;
    padding: 12px 16px;
  }
  
  #confirmation-modal select {
    font-size: 16px;
  }
}
</style>

<script>
  
let selectedCertificate = "";

// Handle purpose select change - show custom input or description textarea
document.getElementById("purpose-select").addEventListener("change", function() {
  const customContainer = document.getElementById("custom-purpose-container");
  const customInput = document.getElementById("custom-purpose-input");
  const purposeDescription = document.getElementById("purpose-description");
  
  if (this.value === "custom") {
    // Show custom input field
    customContainer.style.display = "block";
    customInput.focus();
    purposeDescription.style.display = "block";
  } else if (this.value.trim()) {
    // Hide custom input, show description
    customContainer.style.display = "none";
    customInput.value = "";
    purposeDescription.style.display = "block";
  } else {
    // Hide everything if no selection
    customContainer.style.display = "none";
    customInput.value = "";
    purposeDescription.style.display = "none";
    purposeDescription.value = "";
  }
});

// Handle custom purpose input - show description when typing
document.getElementById("custom-purpose-input").addEventListener("input", function() {
  const purposeDescription = document.getElementById("purpose-description");
  if (this.value.trim()) {
    purposeDescription.style.display = "block";
  }
});

document.querySelectorAll(".certificate-card").forEach(card => {
  card.addEventListener("click", () => {
    selectedCertificate = card.getAttribute("data-cert");
    document.getElementById("modal-cert-title").textContent = `Confirm Request: ${selectedCertificate}`;

    // Reset purpose fields
    document.getElementById("purpose-select").value = "";
    document.getElementById("custom-purpose-container").style.display = "none";
    document.getElementById("custom-purpose-input").value = "";
    document.getElementById("purpose-description").value = "";
    document.getElementById("purpose-description").style.display = "none";

    // ✅ Fetch resident info from database
    fetch("get_resident.php")
      .then(res => res.json())
      .then(data => {
        if (data.error) {
          showNotificationModal('error', 'Error', data.error);
          return;
        }

        document.getElementById("last-name").textContent = data.surname;
        document.getElementById("first-name").textContent = data.first_name;
        document.getElementById("middle-name").textContent = data.middle_name || "";
        document.getElementById("address").textContent = data.address;
        document.getElementById("gender").textContent = data.sex;
        document.getElementById("citizenship").textContent = data.citizenship;
        document.getElementById("age").textContent = data.age;
        document.getElementById("birthday").textContent = data.birthdate;
      })
      .catch(err => {
        console.error("Error fetching resident info:", err);
        showNotificationModal('error', 'Error', 'Unable to fetch resident info. Please check the server.');
      });

    document.getElementById("confirmation-modal").style.display = "flex";
  });
});

// Cancel button
document.getElementById("cancel-btn").addEventListener("click", function () {
  document.getElementById("confirmation-modal").style.display = "none";
  document.getElementById("purpose-select").value = "";
  document.getElementById("custom-purpose-container").style.display = "none";
  document.getElementById("custom-purpose-input").value = "";
  document.getElementById("purpose-description").value = "";
  document.getElementById("purpose-description").style.display = "none";
});

// Submit button (Updated with debugging)
document.getElementById("submit-btn").addEventListener("click", async function () {
  const selectValue = document.getElementById("purpose-select").value.trim();
  const customValue = document.getElementById("custom-purpose-input").value.trim();
  const purposeDescription = document.getElementById("purpose-description").value.trim();
  
  // Determine the actual purpose value
  let purposeInput = "";
  if (selectValue === "custom") {
    purposeInput = customValue;
  } else {
    purposeInput = selectValue;
  }
  
  // Validate purpose input
  if (!purposeInput) {
    document.getElementById("confirmation-modal").style.display = "none";
    showNotificationModal('warning', 'Purpose Required', 'Please enter or select a purpose for your certificate request.');
    return;
  }
  
  // Set purpose and description
  const purpose = purposeInput;
  const description = purposeDescription;

  try {
    const response = await fetch("submit_request.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `certificate=${encodeURIComponent(selectedCertificate)}&purpose=${encodeURIComponent(purpose)}&description=${encodeURIComponent(description)}`
    });

    // ✅ Always check raw text first
    const text = await response.text();
    console.log("Raw response from submit_request.php:", text);

    let resp;
    try {
      resp = JSON.parse(text);
    } catch (e) {
      showNotificationModal('error', 'Server Error', 'Server did not return valid JSON. Open console for details.');
      return;
    }

    if (resp.success) {
      document.getElementById("confirmation-modal").style.display = "none";
      document.getElementById("purpose-select").value = "";
      document.getElementById("custom-purpose-container").style.display = "none";
      document.getElementById("custom-purpose-input").value = "";
      document.getElementById("purpose-description").value = "";
      document.getElementById("purpose-description").style.display = "none";

      // Fill and show success modal
      let successInfo = `
        <p><strong>Certificate:</strong> ${selectedCertificate}</p>
        <p><strong>Purpose:</strong> ${purpose}</p>
      `;
      
      if (description) {
        successInfo += `<p><strong>Additional Details:</strong> ${description}</p>`;
      }
      
      successInfo += `
        <p><strong>First Name:</strong> ${document.getElementById("first-name").textContent}</p>
        <p><strong>Last Name:</strong> ${document.getElementById("last-name").textContent}</p>
        <p><strong>Address:</strong> ${document.getElementById("address").textContent}</p>
        <p><strong>Gender:</strong> ${document.getElementById("gender").textContent}</p>
        <p><strong>Citizenship:</strong> ${document.getElementById("citizenship").textContent}</p>
        <p><strong>Age:</strong> ${document.getElementById("age").textContent}</p>
        <p><strong>Birthday:</strong> ${document.getElementById("birthday").textContent}</p>
      `;
      
      document.getElementById("submitted-info").innerHTML = successInfo;

      let successModal = document.getElementById("success-modal");
      successModal.style.display = "flex";
      setTimeout(() => successModal.style.display = "none", 3000);
    } else {
      // Close confirmation modal first before showing error
      document.getElementById("confirmation-modal").style.display = "none";
      showNotificationModal('error', 'Submission Failed', resp.error || 'An unknown error occurred.');
    }
  } catch (error) {
    console.error("Fetch error:", error);
    showNotificationModal('error', 'Connection Error', 'Failed to reach the server. Please check your connection or PHP errors.');
  }
});

</script>

<!-- Scripts -->
<script src="script.js"></script>
<link rel="stylesheet" href="styles.css">

<style>
/* ===== INCIDENT & CERTIFICATES: better mobile spacing and full-width fields ===== */
/* Container for incident reports (hidden by default, shown via script) */
#incident-reports-section {
  display: none;
  background: #fff;
  padding: 1.6rem;
  border-radius: 12px;
  box-shadow: 0 6px 22px rgba(0, 0, 0, 0.06);
  max-width: 820px;
  margin: 24px auto;
  font-family: "Poppins", sans-serif;
  transition: all 0.18s ease-in-out;
}

/* Certificates group (cards) */
.service-card-group {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 20px;
  align-items: stretch;
  margin: 1rem 0 1.5rem 0;
  padding: 0.5rem;
}

.certificate-card {
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
  padding: 1.5rem;
  border-radius: 16px;
  border: 1px solid rgba(0, 123, 255, 0.1);
  box-shadow: 0 4px 20px rgba(0, 123, 255, 0.08), 0 2px 8px rgba(0, 0, 0, 0.04);
  cursor: pointer;
  display: flex;
  flex-direction: column;
  gap: 10px;
  justify-content: center;
  position: relative;
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.certificate-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 4px;
  background: linear-gradient(90deg, #007bff 0%, #0056b3 100%);
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.3s ease;
}
.certificate-card h3 { 
  font-size: 1.15rem; 
  margin: 0 0 8px 0; 
  color: #1a1a1a;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 8px;
}
.certificate-card h3::before {
  content: '📄';
  font-size: 1.3rem;
  filter: grayscale(0.3);
}
.certificate-card p { 
  margin: 0; 
  color: #6b7280; 
  font-size: 0.92rem; 
  line-height: 1.6;
}
.certificate-card:hover {
  transform: translateY(-6px) scale(1.02);
  box-shadow: 0 12px 40px rgba(0, 123, 255, 0.15), 0 4px 12px rgba(0, 0, 0, 0.08);
  border-color: rgba(0, 123, 255, 0.3);
}
.certificate-card:hover::before {
  transform: scaleX(1);
}
.certificate-card:active { 
  transform: translateY(-2px) scale(1.01); 
}

/* Ensure service cards are clickable (pointer-events) after toggles */
.service-card-group, .certificate-card, #incident-reports-section { pointer-events: auto !important; }

/* Paragraph intro */
#incident-reports-section > p {
  font-size: 0.95rem;
  margin-bottom: 1rem;
  color: #444;
  line-height: 1.5;
}

/* Form groups and labels */
.incident-form .form-group { margin-bottom: 1rem; display:flex; flex-direction:column; }
.incident-form label { font-weight:600; margin-bottom:0.35rem; color:#222; }
.incident-form em { font-size:0.83rem; color:#666; margin-bottom:0.45rem; }

/* Inputs: default to full-width unless desktop overrides apply */
.incident-form input[type="text"],
.incident-form input[type="tel"],
.incident-form input[type="file"],
.incident-form select,
.incident-form textarea {
  padding: 0.8rem 0.95rem;
  font-size: 1rem;
  border-radius: 8px;
  border: 1px solid #d1d5db;
  background-color: #fff;
  width: 100%;
  box-sizing: border-box;
}

.incident-form select { cursor:pointer; }
.incident-form input:focus, .incident-form select:focus, .incident-form textarea:focus { outline:none; border-color:#28a745; box-shadow:0 0 6px rgba(40,167,69,0.12); }

/* Buttons layout */
.incident-buttons { display:flex; justify-content:flex-end; gap:10px; margin-top:12px; }
.incident-buttons button { border:none; padding:0.7rem 1.2rem; font-size:1rem; border-radius:8px; cursor:pointer; }
.cancel-btn { background:#f3f4f6; color:#374151; }
.submit-btn { background:#28a745; color:#fff; }

/* Modal Popup */
#popup {
  display: flex;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  z-index: 9999;
  align-items: center;
  justify-content: center;
  animation: fadeIn 0.3s ease;
}

#popup > div {
  background: #fff;
  padding: 30px;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  max-width: 400px;
  width: 90%;
  text-align: center;
  animation: slideUp 0.3s ease;
}

#popup h3 {
  margin-bottom: 15px;
  font-size: 1.5rem;
  color: #28a745;
}

#popup p {
  margin-bottom: 20px;
  color: #555;
  font-size: 1rem;
}

#popup button {
  background: #28a745;
  color: #fff;
  border: none;
  padding: 10px 30px;
  font-size: 1rem;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s ease;
}

#popup button:hover {
  background: #218838;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from {
    transform: translateY(30px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

/* ===== MOBILE ADAPTATIONS: maximize space and touch targets ===== */
@media (max-width: 992px) {
  /* Make certificate cards take full width on small tablets/phones */
  .service-card-group { 
    grid-template-columns: repeat(1, 1fr); 
    gap:16px; 
    margin: 12px 10px; 
    padding: 0.3rem;
  }
  .certificate-card, .services-card { 
    padding: 1.2rem; 
    border-radius: 14px; 
  }
  .certificate-card h3 {
    font-size: 1.1rem;
  }
  .certificate-card p {
    font-size: 0.9rem;
  }

  /* Incident container should use the viewport width with small gutters */
  #incident-reports-section { 
    margin: 16px 10px; 
    padding: 1.5rem; 
    border-radius: 16px;
  }

  /* Ensure form content stretches and inputs are comfortably big */
  .incident-form { padding: 10px; gap:10px; max-width: 100%; }
  .incident-form input, .incident-form textarea, .incident-form select { width: 100% !important; font-size: 1rem; padding: 0.9rem; }

  /* Buttons stack vertically for easier tapping */
  .incident-buttons { flex-direction: column-reverse; gap:10px; }
  .incident-buttons button { width: 100%; }
}

/* Desktop: allow slightly narrower input visuals if desired */
@media (min-width: 993px) {
  .service-card-group { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
  /* optional: inputs can be constrained visually by parent layout (kept 100% here) */
}

/* Incident type select styling */
#incident-type {
  text-align: left;
}

#incident-type option {
  text-align: left;
  padding: 8px;
}

/* Style the "Other" option with a distinct color */
#incident-type option[value="Other"] {
  background-color: #e3f2fd;
  color: #1565c0;
  font-weight: 600;
}
</style>

<div class="services-card" id="incident-reports-section">
  <p>Report incidents such as theft, public disturbance, damage to property, and more.</p>

  <?php if (isset($_SESSION['incident_error'])): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        showNotificationModal('error', 'Submission Blocked', '<?= addslashes($_SESSION['incident_error']) ?>');
      });
    </script>
    <?php unset($_SESSION['incident_error']); ?>
  <?php endif; ?>

  <form class="incident-form" method="POST" action="submit_incident.php" enctype="multipart/form-data">
    
    <div class="form-group">
      <label for="incident-type"><strong>What type of incident is this?</strong></label>
      <em>(e.g., theft, public disturbance, damage to property, suspicious activity, etc.)</em>
      <select id="incident-type" name="incident_type" required>
  <option value="" disabled selected>Select type of incident</option>

  <!-- 🔴 Urgent / Emergency -->
  <optgroup label="🚨 Urgent / Emergency">
    <option value="Fire">Fire / Sunog</option>
    <option value="Fire Incident">Fire Incident / Sunog</option>
    <option value="Explosion">Explosion / Pagsabog</option>
    <option value="Gas Leak">Gas Leak / Tagas ng Gas</option>
    <option value="Chemical Spill">Chemical Spill / Pagtagas ng Kemikal</option>
    <option value="Accident/Injury">Accident/Injury</option>
    <option value="Damage to Property">Damage to Property / Pinsala sa Ari-arian</option>
    <option value="Assault">Assault / Pananakit</option>
    <option value="Armed Assault">Armed Assault / Armadong Pananakit</option>
    <option value="Homicide">Homicide / Pagpatay</option>
    <option value="Murder">Murder / Pagpatay</option>
    <option value="Shooting">Shooting / Barilan</option>
    <option value="Stabbing">Stabbing / Saksakan</option>
    <option value="Violent">Violent / Karahasan</option>
    <option value="Emergency">Emergency / Emerhensiya</option>
    <option value="Medical">Medical / Medikal</option>
    <option value="Heart Attack">Heart Attack / Atake sa Puso</option>
    <option value="Stroke">Stroke</option>
    <option value="Unconscious Person">Unconscious Person / Walang Malay</option>
    <option value="Car Accident">Car Accident / Aksidente sa Sasakyan</option>
    <option value="Serious Injury">Serious Injury / Malubhang Pinsala</option>
    <option value="Domestic Violence">Domestic Violence / Karahasan sa Tahanan</option>
    <option value="Kidnapping">Kidnapping / Pagdukot</option>
    <option value="Child Abuse">Child Abuse / Pang-aabuso sa Bata</option>
    <option value="Sexual Assault">Sexual Assault / Panghahalay</option>
    <option value="Building Collapse">Building Collapse / Pagguho ng Gusali</option>
    <option value="Natural Disaster">Natural Disaster / Kalikasan</option>
    <option value="Earthquake">Earthquake / Lindol</option>
    <option value="Flood">Flood / Baha</option>
    <option value="Electrocution">Electrocution / Kuryente</option>
  </optgroup>

  <!-- 🟠 Moderate -->
  <optgroup label="⚠️ Moderate">
    <option value="Theft">Theft / Pagnanakaw</option>
    <option value="Vandalism">Vandalism / Paninira</option>
    <option value="Public Disturbance">Public Disturbance / Istorbo sa Publiko</option>
    <option value="Burglary">Burglary / Pagnanakaw sa Bahay</option>
    <option value="Robbery">Robbery / Panghoholdap</option>
    <option value="Damage">Damage / Pinsala</option>
    <option value="Trespassing">Trespassing / Panggagambala</option>
    <option value="Hit and Run">Hit and Run / Banggaan</option>
    <option value="Minor Accident">Minor Accident</option>
    <option value="Property Damage">Property Damage / Pinsala</option>
    <option value="Harassment">Harassment / Pang-aasar</option>
    <option value="Threat">Threat / Banta</option>
    <option value="Missing Person">Missing Person / Nawalang Tao</option>
    <option value="Fraud">Fraud / Panlilinlang</option>
    <option value="Illegal Dumping">Illegal Dumping / Basurang Itinatapon</option>
    <option value="Shoplifting">Shoplifting / Pandurukot</option>
    <option value="Verbal Abuse">Verbal Abuse / Pang-aasar</option>
    <option value="Scam">Scam / Panloloko</option>
    <option value="Identity Theft">Identity Theft</option>
    <option value="Public Intoxication">Public Intoxication / Pag-inom sa Publiko</option>
    <option value="Illegal Parking">Illegal Parking / Illegal na Paradahan</option>
    <option value="Reckless Driving">Reckless Driving / Pabaya sa Pagmamaneho</option>
  </optgroup>

  <!-- 🟢 Minor -->
  <optgroup label="🟢 Minor">
    <option value="Noise">Noise / Ingay</option>
    <option value="Noise Complaint">Noise Complaint / Reklamo sa Ingay</option>
    <option value="Minor">Minor / Maliit</option>
    <option value="Loitering">Loitering / Paglalaboy</option>
    <option value="Littering">Littering / Pagtatapon ng Basura</option>
    <option value="Public Nuisance">Public Nuisance / Istorbo</option>
    <option value="Lost Item">Lost Item / Nawalang Gamit</option>
    <option value="Animal Complaint">Animal Complaint / Reklamo sa Hayop</option>
    <option value="Barking Dog">Barking Dog / Tahol ng Aso</option>
    <option value="Illegal Posting">Illegal Posting / Illegal na Poster</option>
    <option value="Curfew Violation">Curfew Violation / Labag sa Curfew</option>
    <option value="Jaywalking">Jaywalking / Tumawid sa Maling Daan</option>
    <option value="Unauthorized Selling">Unauthorized Selling / Walang Permit na Tindero</option>
   
    <option value="Graffiti">Graffiti</option>
    <option value="Disorderly Conduct">Disorderly Conduct / Gulo sa Publiko</option>
    <option value="Drunk in Public">Drunk in Public / Lasing sa Publiko</option>
    <option value="Trespassing (Non-violent)">Trespassing (Non-violent) / Panggugulo</option>
    <option value="Neighborhood Dispute">Neighborhood Dispute / Alitan sa Kapitbahay</option>
    <option value="Unauthorized Entry (Non-violent)">Unauthorized Entry (Non-violent) / Hindi Awtorisadong Pagpasok</option>
  <option value="Other">Other / Iba pa</option>
  </optgroup>
</select>

      <input type="text" id="incident-type-other" name="incident_type_other" placeholder="Please specify the incident type" style="display:none; margin-top: 10px;" />
    </div>

    <div class="form-group">
      <label for="contact-number"><strong>Your Contact Number:</strong></label>
      <em>(We may contact you for more details about the incident.)</em>
  <input type="tel" id="contact-number" name="contact_number" placeholder="Enter your contact number" pattern="[0-9]{11}" maxlength="11" inputmode="numeric" autocomplete="tel" required />
    </div>

    <div class="form-group">
      <label for="incident-details"><strong>Please describe the incident in detail.</strong></label>
      <em>(Include date, time, location, and persons involved if any.)</em>
      <textarea id="incident-details" name="incident_description" rows="4" placeholder="Describe the incident here" required></textarea>
    </div>

    <div class="form-group">
      <label for="incident-image"><strong>Upload image (optional):</strong></label>
      <input type="file" id="incident-image" name="incident_image" accept="image/*" />
    </div>

    <div class="incident-buttons">
      <button type="reset" class="cancel-btn">Cancel</button>
      <button type="submit" class="submit-btn">Submit</button>
    </div>
  </form>

  <?php if (isset($_GET['incident_submitted']) && $_GET['incident_submitted'] == 1): ?>
  <div id="popup" onclick="if(event.target === this) this.style.display='none'">
    <div>
      <h3>✅ Success</h3>
      <p>Incident report submitted successfully!</p>
      <button onclick="document.getElementById('popup').style.display='none'">OK</button>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
// Handle "Other" incident type selection
document.addEventListener('DOMContentLoaded', function() {
  const incidentTypeSelect = document.getElementById('incident-type');
  const incidentTypeOther = document.getElementById('incident-type-other');
  
  if (incidentTypeSelect && incidentTypeOther) {
    incidentTypeSelect.addEventListener('change', function() {
      if (this.value === 'Other') {
        incidentTypeOther.style.display = 'block';
        incidentTypeOther.required = true;
      } else {
        incidentTypeOther.style.display = 'none';
        incidentTypeOther.required = false;
        incidentTypeOther.value = '';
      }
    });
  }
});
</script>







          </div>
          
      </div>
  </section>
<section id="officials">
  <div class="container">
    <h2 class="text-center mb-3">Our Barangay Officials</h2>
    <div class="section-divider" style="width: 60px; height: 3px; background: #007bff; margin: 0 auto 1rem;"></div>
    <p class="text-center mb-4">Meet our dedicated barangay officials serving the community.</p>

    <div class="officials-grid" 
      style="display: flex; flex-wrap: wrap; gap: 2.5rem; justify-content: center; align-items: flex-start;">

      <?php
      include 'config.php';
$stmt = $conn->prepare("
  SELECT * FROM manage_brgy_officials 
  ORDER BY 
    CASE 
      WHEN position = 'Barangay Captain' THEN 1 
      ELSE 2 
    END, 
    start_date DESC
");
$stmt->execute();
$result = $stmt->get_result();


      while ($off = $result->fetch_assoc()): ?>
        <div class="official-card-flip" style="width:320px; height:420px; perspective:1200px; margin-bottom:2rem;">
          <div class="official-card-inner" style="position:relative; width:100%; height:100%; transition:transform 0.7s cubic-bezier(.4,2,.3,1); transform-style:preserve-3d;">
            <div class="official-card-front" style="position:absolute; width:100%; height:100%; backface-visibility:hidden; background:#fff; border-radius:18px; box-shadow:0 8px 32px rgba(21,179,0,0.10); display:flex; flex-direction:column; align-items:center; justify-content:center; cursor:pointer;">
              <img src="<?= htmlspecialchars($off['photo'] ?: 'default.jpg') ?>" alt="<?= htmlspecialchars($off['name']) ?>" style="width:180px; height:200px; object-fit:cover; border-radius:12px; margin-bottom:18px; box-shadow:0 2px 12px rgba(0,0,0,0.08);">
              <h4 style="margin:0 0 0.5rem 0; font-weight:700; font-size:1.35rem; color:#222;"><?= htmlspecialchars($off['name']) ?></h4>
              <span style="font-size:1.1rem; color:#15b300; font-weight:500; margin-bottom:8px;"> <?= htmlspecialchars($off['position']) ?> </span>
              <span style="font-size:0.95rem; color:#555;">Click to view details</span>
            </div>
            <div class="official-card-back" style="position:absolute; width:100%; height:100%; backface-visibility:hidden; background:#f7faf7; border-radius:18px; box-shadow:0 8px 32px rgba(21,179,0,0.10); display:flex; flex-direction:column; align-items:center; justify-content:center; transform:rotateY(180deg); padding:2rem 1.2rem; cursor:pointer;">
              <h4 style="font-weight:700; color:#15b300; margin-bottom:0.5rem; font-size:1.25rem;"> <?= htmlspecialchars($off['name']) ?> </h4>
              <div style="font-size:1.08rem; color:#222; font-weight:500; margin-bottom:0.7rem;"> <?= htmlspecialchars($off['position']) ?> </div>
              <div style="font-size:1rem; color:#444; text-align:center; margin-bottom:1.2rem;"> <?= nl2br(htmlspecialchars($off['description'])) ?> </div>
              <!-- Removed Back button -->
            </div>
          </div>
        </div>
      <?php endwhile; ?>

    </div>
  </div>
</section>

<script>
  // Flip card animation
  document.querySelectorAll('.official-card-flip').forEach(card => {
    const inner = card.querySelector('.official-card-inner');
    card.addEventListener('click', function(e) {
      inner.classList.toggle('flipped');
    });
  });
  // Add flip animation via CSS
  const style = document.createElement('style');
  style.innerHTML = `
    .official-card-inner { transition: transform 0.7s cubic-bezier(.4,2,.3,1); }
    .official-card-inner.flipped { transform: rotateY(180deg); }
    .official-card-flip:hover .official-card-inner:not(.flipped) { box-shadow:0 12px 32px rgba(21,179,0,0.18); transform:scale(1.03); }
  `;
  document.head.appendChild(style);
</script>


  </div>
</section>

<?php
include 'config.php'; // MySQLi connection ($conn)



// Fetch announcements
$anns = [];
$sql = "SELECT * FROM announcements ORDER BY date_posted DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $anns[] = $row;
    }
}
?>
<section id="announcements"> 
    <div class="container" style="max-width:1200px; margin:0 auto; padding:40px 20px;">
        <h2 style="text-align:center; margin-bottom:10px; font-size:2.2rem; font-weight:700; color:#2c3e50;">
            📢 Announcements
        </h2>
        <div class="section-divider" style="width:80px; height:4px; background:#007BFF; margin:12px auto 25px auto; border-radius:2px;"></div>
        <p style="text-align:center; color:#666; font-size:1rem;">Stay updated with the latest news and announcements.</p>

        <div class="announcements-grid" 
             style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:24px; margin-top:30px;">
            
            <?php if (!empty($anns)): ?>
                <?php foreach ($anns as $index => $a): ?>
                    <div class="announcement-card <?= $index >= 9 ? 'hidden-announcement' : '' ?>" 
                         style="background:#fff; border-radius:14px; overflow:hidden; box-shadow:0 4px 15px rgba(0,0,0,0.06); transition:transform 0.3s, box-shadow 0.3s; cursor:pointer; <?= $index >= 9 ? 'display:none;' : '' ?>">
                        
                        <?php if ($a['image']): ?> 
                            <img src="uploads/<?= htmlspecialchars($a['image']) ?>" 
                                 style="width:100%; height:200px; object-fit:cover;">
                        <?php endif; ?>

                        <div style="padding:20px;">
                            <h3 style="margin-bottom:12px; font-weight:600; font-size:1.3rem; color:#2c3e50; text-align:center;">
                                <?= htmlspecialchars($a['title']) ?>
                            </h3>

              <p style="text-align:justify; margin-bottom:15px; color:#555; font-size:0.95rem;
                    display:-webkit-box; -webkit-line-clamp:3; line-clamp:3; -webkit-box-orient:vertical; overflow:hidden;">
                                <?= nl2br(htmlspecialchars($a['content'])) ?>
                            </p>

                            <div style="margin-top:auto; padding-top:10px; border-top:1px solid #f0f0f0;">
                                <small style="display:block; text-align:center; color:#999; font-size:0.85rem; margin-bottom:10px;">
                                    📅 <?= htmlspecialchars($a['date_posted']) ?>
                                </small>

                                <a href="javascript:void(0)" 
                                   class="read-more-link" 
                                   data-title="<?= htmlspecialchars($a['title']) ?>" 
                                   data-content="<?= htmlspecialchars($a['content']) ?>" 
                                   data-image="<?= $a['image'] ? 'uploads/'.htmlspecialchars($a['image']) : '' ?>" 
                                   data-date="<?= htmlspecialchars($a['date_posted']) ?>"
                                   style="display:block; text-align:center; color:#007BFF; 
                                          font-weight:500; text-decoration:none; transition:0.3s;">
                                    Read More →
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center; grid-column:1 / -1;">No announcements yet.</p>
            <?php endif; ?>
        </div>

        <?php if (count($anns) > 9): ?>
        <div style="text-align:center; margin-top:40px; display:flex; justify-content:center;">
            <button id="viewAllAnnouncementsBtn" 
                    style="background:linear-gradient(135deg, #007BFF 0%, #0056b3 100%); 
                           color:white; border:none; padding:10px 20px; border-radius:25px; 
                           font-size:0.9rem; font-weight:600; cursor:pointer; 
                           box-shadow:0 3px 12px rgba(0,123,255,0.3); 
                           transition:all 0.3s ease; display:inline-flex; align-items:center; gap:8px;
                           width:auto; max-width:fit-content;">
                <i class="fas fa-eye"></i>
                <span>View All Announcements</span>
            </button>
            <button id="showLessAnnouncementsBtn" 
                    style="background:linear-gradient(135deg, #6c757d 0%, #495057 100%); 
                           color:white; border:none; padding:10px 20px; border-radius:25px; 
                           font-size:0.9rem; font-weight:600; cursor:pointer; 
                           box-shadow:0 3px 12px rgba(108,117,125,0.3); 
                           transition:all 0.3s ease; display:none; align-items:center; gap:8px;
                           width:auto; max-width:fit-content;">
                <i class="fas fa-eye-slash"></i>
                <span>Show Less</span>
            </button>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
#viewAllAnnouncementsBtn:hover,
#showLessAnnouncementsBtn:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,123,255,0.4);
}

#viewAllAnnouncementsBtn:active,
#showLessAnnouncementsBtn:active {
    transform: translateY(-1px);
}

@media (max-width: 768px) {
    #viewAllAnnouncementsBtn,
    #showLessAnnouncementsBtn {
        padding: 8px 16px;
        font-size: 0.85rem;
    }
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const viewAllBtn = document.getElementById("viewAllAnnouncementsBtn");
    const showLessBtn = document.getElementById("showLessAnnouncementsBtn");
    const hiddenAnnouncements = document.querySelectorAll(".hidden-announcement");

    if (viewAllBtn) {
        viewAllBtn.addEventListener("click", function() {
            hiddenAnnouncements.forEach(function(card) {
                card.style.display = "block";
                setTimeout(() => {
                    card.style.opacity = "0";
                    card.style.transform = "translateY(20px)";
                    card.style.transition = "opacity 0.3s ease, transform 0.3s ease";
                    setTimeout(() => {
                        card.style.opacity = "1";
                        card.style.transform = "translateY(0)";
                    }, 10);
                }, 10);
            });
            viewAllBtn.style.display = "none";
            showLessBtn.style.display = "inline-flex";
        });
    }

    if (showLessBtn) {
        showLessBtn.addEventListener("click", function() {
            hiddenAnnouncements.forEach(function(card) {
                card.style.display = "none";
            });
            viewAllBtn.style.display = "inline-flex";
            showLessBtn.style.display = "none";
            
            // Scroll to announcements section
            document.getElementById("announcements").scrollIntoView({ behavior: "smooth", block: "start" });
        });
    }
});
</script>

<!-- Popup Modal -->
<div id="announcementModal" 
     style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
            background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:1000;">
    <div style="background:#fff; border-radius:14px; max-width:800px; width:90%; max-height:85%; 
                overflow-y:auto; padding:30px; position:relative; 
                box-shadow:0 8px 30px rgba(0,0,0,0.2); animation:fadeIn 0.3s ease;">
        
        <span id="closeModal" 
              style="position:absolute; top:12px; right:18px; font-size:1.8rem; cursor:pointer; color:#555; font-weight:bold;">
              &times;
        </span>
        
        <h2 id="modalTitle" style="text-align:center; margin-bottom:15px; font-size:1.6rem; font-weight:700; color:#2c3e50;"></h2>
        <img id="modalImage" src="" style="width:100%; max-height:350px; object-fit:cover; border-radius:10px; display:none; margin-bottom:20px;">
        <p id="modalContent" style="text-align:justify; white-space:pre-wrap; color:#444; line-height:1.6; font-size:1rem;"></p>
        <small id="modalDate" style="display:block; text-align:right; color:#999; margin-top:15px; font-size:0.9rem;"></small>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const links = document.querySelectorAll(".read-more-link");
    const modal = document.getElementById("announcementModal");
    const modalTitle = document.getElementById("modalTitle");
    const modalContent = document.getElementById("modalContent");
    const modalImage = document.getElementById("modalImage");
    const modalDate = document.getElementById("modalDate");
    const closeModal = document.getElementById("closeModal");

    links.forEach(link => {
        link.addEventListener("click", function() {
            modalTitle.textContent = this.dataset.title;
            modalContent.textContent = this.dataset.content;
            modalDate.textContent = "Posted: " + this.dataset.date;

            if (this.dataset.image) {
                modalImage.src = this.dataset.image;
                modalImage.style.display = "block";
            } else {
                modalImage.style.display = "none";
            }

            modal.style.display = "flex";
        });
    });

    closeModal.addEventListener("click", () => {
        modal.style.display = "none";
    });

    modal.addEventListener("click", (e) => {
        if (e.target === modal) modal.style.display = "none";
    });
});
</script>

<style>
/* Card hover effect */
.announcement-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

/* Modal fade-in animation */
@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}

/* Contact Section Styles */
.contact-layout {
    display: flex;
    flex-direction: row;
    gap: 25px;
    margin-bottom: 40px;
    align-items: stretch;
}

.contact-layout-stacked {
    display: flex;
    flex-direction: column;
    gap: 50px;
    margin-bottom: 50px;
}

.contact-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    width: 100%;
}

.contact-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

.card-header {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 20px 25px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.card-header i {
    font-size: 24px;
}

.card-header h3 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 600;
}

.emergency-header {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
}

.card-body {
    padding: 25px;
}

.contact-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 15px;
    margin-bottom: 10px;
    background: #f8f9fa;
    border-radius: 8px;
    transition: background 0.2s ease;
}

.contact-item:hover {
    background: #e9ecef;
}

.contact-item i {
    font-size: 20px;
    color: #10b981;
    margin-top: 3px;
    min-width: 20px;
}

.contact-item strong {
    display: block;
    color: #2c3e50;
    font-size: 0.95rem;
    margin-bottom: 5px;
}

.contact-item p {
    margin: 0;
    color: #555;
    font-size: 0.9rem;
    line-height: 1.5;
}

.emergency-notice {
    background: #d1fae5;
    border-left: 4px solid #10b981;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.emergency-notice i {
    color: #059669;
    font-size: 20px;
}

.emergency-notice p {
    margin: 0;
    color: #065f46;
    font-size: 0.95rem;
}

.emergency-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.emergency-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 15px;
    background: #fff;
    border: 2px solid #f0f0f0;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.emergency-item:hover {
    border-color: #22c55e;
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.15);
    transform: translateY(-2px);
}

.emergency-item i {
    font-size: 22px;
    color: #16a34a;
    margin-top: 2px;
}

.emergency-item strong {
    display: block;
    color: #2c3e50;
    font-size: 0.9rem;
    margin-bottom: 5px;
    font-weight: 600;
}

.emergency-item p {
    margin: 0;
    color: #555;
    font-size: 0.85rem;
    line-height: 1.4;
}

/* Map Section */
.map-section {
    margin-top: 30px;
    margin-bottom: 40px;
}

.map-section .contact-card {
    max-width: 100%;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .contact-layout {
        flex-direction: column;
        gap: 20px;
    }
    
    .contact-layout-stacked {
        gap: 30px;
    }
    
    .emergency-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .map-section {
        margin-top: 20px;
    }
}

@media (max-width: 768px) {
    .contact-layout-stacked {
        gap: 25px;
    }
    
    .emergency-grid {
        grid-template-columns: 1fr;
    }
    
    .card-header h3 {
        font-size: 1.1rem;
    }
    
    .contact-item {
        padding: 12px;
    }
}

@media (min-width: 1200px) {
    .emergency-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>



  


  <section id="contact">
    <div class="container">
      <h2>Contact Us</h2>
      <div class="section-divider"></div>
      <p style="text-align: center; color: #666; margin-bottom: 40px;">We're here to help. Reach out to us with your concerns or inquiries.</p>

      <!-- Contact Cards Stacked -->
      <div class="contact-layout-stacked">
        
        <!-- Barangay Hall Details Card -->
        <div class="contact-card">
          <div class="card-header">
            <i class="fas fa-building"></i>
            <h3>Barangay Hall Details</h3>
          </div>
          <div class="card-body">
            <div class="contact-item">
              <i class="fas fa-map-marker-alt"></i>
              <div>
                <strong>Address</strong>
                <p>Don Placido Campos Avenue, Dasmarinas, Cavite</p>
              </div>
            </div>
            <div class="contact-item">
              <i class="fas fa-phone"></i>
              <div>
                <strong>Barangay Hall</strong>
                <p>(046) 432-0454</p>
              </div>
            </div>
            <div class="contact-item">
              <i class="fas fa-shield-alt"></i>
              <div>
                <strong>Tanod Hotline</strong>
                <p>0976-5104322</p>
              </div>
            </div>
            <div class="contact-item">
              <i class="fas fa-envelope"></i>
              <div>
                <strong>Email</strong>
                <p>sabangdasmarinas@gmail.com</p>
              </div>
            </div>
            <div class="contact-item">
              <i class="fas fa-clock"></i>
              <div>
                <strong>Office Hours</strong>
                <p>Mon–Fri: 8AM–5PM<br>Sat: 8AM–12NN<br>Sun: Closed</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Emergency Hotlines Card -->
        <div class="contact-card emergency-card">
          <div class="card-header emergency-header">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Emergency Hotlines</h3>
          </div>
          <div class="card-body">
            <div class="emergency-notice">
              <i class="fas fa-info-circle"></i>
              <p>For emergencies, dial <strong>911</strong> for immediate assistance</p>
            </div>
            
            <div class="emergency-grid">
              <div class="emergency-item">
                <i class="fas fa-ambulance"></i>
                <div>
                  <strong>National Emergency</strong>
                  <p>911</p>
                </div>
              </div>
              
              <div class="emergency-item">
                <i class="fas fa-fire-extinguisher"></i>
                <div>
                  <strong>Dasma Fire Dept.</strong>
                  <p>416-08-75<br>0995-336-9534</p>
                </div>
              </div>
              
              <div class="emergency-item">
                <i class="fas fa-user-shield"></i>
                <div>
                  <strong>Dasma PNP</strong>
                  <p>416 29-24<br>0956-800-3329<br>0995-598-5598</p>
                </div>
              </div>
              
              <div class="emergency-item">
                <i class="fas fa-hospital"></i>
                <div>
                  <strong>City Health Office</strong>
                  <p>(046) 416-08-09</p>
                </div>
              </div>
              
              <div class="emergency-item">
                <i class="fas fa-plus-square"></i>
                <div>
                  <strong>Red Cross</strong>
                  <p>143 / (02) 790-2300</p>
                </div>
              </div>
              
              <div class="emergency-item">
                <i class="fas fa-broadcast-tower"></i>
                <div>
                  <strong>CDRRMC</strong>
                  <p>0917-721-8825<br>0995843-5477<br>(046) 513-1766</p>
                </div>
              </div>
              
              <div class="emergency-item">
                <i class="fas fa-video"></i>
                <div>
                  <strong>CCTV Rescue Center</strong>
                  <p>(046) 435-0183<br>(046) 481-0555</p>
                </div>
              </div>
              
              <div class="emergency-item">
                <i class="fas fa-clinic-medical"></i>
                <div>
                  <strong>Pagamutan ng Dasma</strong>
                  <p>481-44-00<br>435-01-80</p>
                </div>
              </div>
              
              <div class="emergency-item">
                <i class="fas fa-bolt"></i>
                <div>
                  <strong>Meralco Hotline</strong>
                  <p>16211 / 416-17-03</p>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Map Section (Separate) -->
      <div class="map-section">
        <div class="contact-card map-card">
          <div class="card-header">
            <i class="fas fa-map-marker-alt"></i>
            <h3>Find Us Here</h3>
          </div>
          <div class="card-body" style="padding: 0;">
            <iframe
              src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3863.8947!2d120.9220328!3d14.3453483!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397d4ec827327c7%3A0x86ccec9cc38c77fe!2sSabang%20Barangay%20Hall!5e0!3m2!1sen!2sph!4v1692540000000!5m2!1sen!2sph"
              width="100%"
              height="450"
              style="border:0; border-radius: 0 0 12px 12px;"
              allowfullscreen=""
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade">
            </iframe>
          </div>
        </div>
      </div>

</script>

<!-- Modern Suggestion Form Section -->
<style>
.suggestion-section {
  max-width: 1200px;
  margin: 3rem auto;
  padding: 0 1.5rem;
}

.suggestion-card {
  background: linear-gradient(135deg, #ffffff 0%, #f8fafb 100%);
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(21, 179, 0, 0.1);
  overflow: hidden;
  border: 1px solid rgba(21, 179, 0, 0.08);
}

.suggestion-header {
  background: linear-gradient(135deg, #15b300 0%, #0e7c00 100%);
  padding: 2rem 2.5rem;
  text-align: center;
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.suggestion-header::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
  animation: pulse 3s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); opacity: 0.5; }
  50% { transform: scale(1.1); opacity: 0.8; }
}

.suggestion-header-icon {
  width: 60px;
  height: 60px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
  font-size: 28px;
  color: white;
  backdrop-filter: blur(10px);
  position: relative;
  z-index: 1;
}

.suggestion-header h3 {
  color: white;
  font-size: 1.75rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
  position: relative;
  z-index: 1;
}

.suggestion-header p {
  color: rgba(255, 255, 255, 0.9);
  font-size: 0.95rem;
  margin: 0;
  position: relative;
  z-index: 1;
  text-align: center;
}

.suggestion-body {
  padding: 2.5rem;
}

.modern-form-group {
  margin-bottom: 1.5rem;
  position: relative;
}

.modern-form-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.5rem;
  font-size: 0.95rem;
}

.modern-form-label i {
  color: #15b300;
  font-size: 1rem;
}

.modern-input,
.modern-textarea {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 2px solid #e0e7ed;
  border-radius: 12px;
  font-size: 1rem;
  font-family: inherit;
  transition: all 0.3s ease;
  background: #f8fafb;
  color: #2c3e50;
}

.modern-input:focus,
.modern-textarea:focus {
  outline: none;
  border-color: #15b300;
  background: white;
  box-shadow: 0 0 0 4px rgba(21, 179, 0, 0.1);
  transform: translateY(-2px);
}

.modern-textarea {
  resize: vertical;
  min-height: 140px;
  line-height: 1.6;
}

.modern-input::placeholder,
.modern-textarea::placeholder {
  color: #94a3b8;
}

.char-counter {
  text-align: right;
  font-size: 0.8rem;
  color: #64748b;
  margin-top: 0.25rem;
}

.modern-submit-btn {
  width: 100%;
  background: linear-gradient(135deg, #15b300 0%, #0e7c00 100%);
  color: white;
  border: none;
  padding: 1rem 2rem;
  border-radius: 12px;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(21, 179, 0, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  margin-top: 1.5rem;
}

.modern-submit-btn:hover:not(:disabled) {
  transform: translateY(-3px);
  box-shadow: 0 6px 20px rgba(21, 179, 0, 0.4);
}

.modern-submit-btn:active:not(:disabled) {
  transform: translateY(-1px);
}

.modern-submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.modern-submit-btn i {
  font-size: 1.2rem;
}

.form-message {
  margin-top: 1.5rem;
  padding: 1rem 1.25rem;
  border-radius: 10px;
  font-weight: 500;
  display: none;
  align-items: center;
  gap: 0.75rem;
  animation: slideIn 0.3s ease;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.form-message.success {
  background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
  color: #155724;
  border: 1px solid #c3e6cb;
  display: flex;
}

.form-message.error {
  background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
  color: #721c24;
  border: 1px solid #f5c6cb;
  display: flex;
}

.form-message i {
  font-size: 1.25rem;
}

.spinner {
  width: 18px;
  height: 18px;
  border: 3px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 768px) {
  .suggestion-section {
    margin: 2rem auto;
    padding: 0 1rem;
  }
  
  .suggestion-header {
    padding: 1.5rem 1.5rem;
  }
  
  .suggestion-header h3 {
    font-size: 1.5rem;
  }
  
  .suggestion-body {
    padding: 1.5rem;
  }
  
  .modern-submit-btn {
    font-size: 1rem;
    padding: 0.875rem 1.5rem;
  }
}

@media (max-width: 480px) {
  .suggestion-header h3 {
    font-size: 1.25rem;
  }
  
  .suggestion-header p {
    font-size: 0.875rem;
  }
  
  .suggestion-body {
    padding: 1.25rem;
  }
}
</style>

<div class="suggestion-section">
  <div class="suggestion-card">
    <div class="suggestion-header">
      <div class="suggestion-header-icon">
        <i class="fas fa-paper-plane"></i>
      </div>
      <h3>Send Us a Suggestion</h3>
      <p>We value your feedback and ideas to improve our services</p>
    </div>
    
    <div class="suggestion-body">
      <form id="contactForm">
        <div class="modern-form-group">
          <label class="modern-form-label">
            <i class="fas fa-tag"></i>
            Subject
          </label>
          <input 
            type="text" 
            name="subject" 
            class="modern-input" 
            placeholder="Enter the subject of your suggestion" 
            required 
            maxlength="100"
          />
        </div>
        
        <div class="modern-form-group">
          <label class="modern-form-label">
            <i class="fas fa-comment-dots"></i>
            Message
          </label>
          <textarea 
            name="message" 
            class="modern-textarea" 
            placeholder="Share your thoughts, suggestions, or feedback with us..." 
            required
            maxlength="500"
            id="messageTextarea"
          ></textarea>
          <div class="char-counter">
            <span id="charCount">0</span> / 500 characters
          </div>
        </div>
        
        <button type="submit" class="modern-submit-btn" id="submitBtn">
          <i class="fas fa-paper-plane"></i>
          <span>Send Suggestion</span>
        </button>
        
        <div id="formMessage" class="form-message"></div>
      </form>
    </div>
  </div>
</div>

<script>
// Character counter
const messageTextarea = document.getElementById('messageTextarea');
const charCount = document.getElementById('charCount');

if (messageTextarea && charCount) {
  messageTextarea.addEventListener('input', function() {
    charCount.textContent = this.value.length;
  });
}

// Form submission
document.getElementById("contactForm").addEventListener("submit", function(e) {
  e.preventDefault();

  let form = this;
  let formData = new FormData(form);
  let messageBox = document.getElementById("formMessage");
  let button = document.getElementById("submitBtn");
  let buttonText = button.querySelector("span");
  let buttonIcon = button.querySelector("i");

  // Disable button and show loading state
  button.disabled = true;
  buttonText.textContent = "Sending...";
  buttonIcon.className = "";
  buttonIcon.innerHTML = '<div class="spinner"></div>';
  messageBox.style.display = "none";
  messageBox.className = "form-message";

  fetch("save_suggestion.php", {
      method: "POST",
      body: formData
  })
  .then(res => res.json())
  .then((data) => {
      // Show response message
      messageBox.innerHTML = `
        <i class="fas fa-${data.status === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${data.message}</span>
      `;
      messageBox.className = `form-message ${data.status}`;
      messageBox.style.display = "flex";

      if (data.status === "success") {
          form.reset();
          charCount.textContent = "0";
          
          // Auto-hide success message after 5 seconds
          setTimeout(() => {
            messageBox.style.display = "none";
          }, 5000);
      }
  })
  .catch(err => {
      console.error("Request failed:", err);
      messageBox.innerHTML = `
        <i class="fas fa-exclamation-triangle"></i>
        <span>Something went wrong. Please try again.</span>
      `;
      messageBox.className = "form-message error";
      messageBox.style.display = "flex";
  })
  .finally(() => {
      // Re-enable button and restore original state
      button.disabled = false;
      buttonText.textContent = "Send Suggestion";
      buttonIcon.innerHTML = "";
      buttonIcon.className = "fas fa-paper-plane";
  });
});
</script>

  



  <script>
  // Helper to show only the relevant cards inside the services section
  function showOnlyServiceCard(cardType) {
    // Hide all main sections except services
    const mainSections = [
      "hero", "about", "services", "officials", "announcements",
      "job-finder-section", "contact", "login-section",
      "admin-login-section", "newuser-section",
    ];
    mainSections.forEach(id => {
      const el = document.getElementById(id);
      if (el) el.style.display = (id === "services") ? "block" : "none";
    });

    // Hide both groups first
    const certGroup = document.getElementById("certificates-group");
    const incidentGroup = document.getElementById("incident-reports-section");
    if (certGroup) certGroup.style.display = "none";
    if (incidentGroup) incidentGroup.style.display = "none";

    // Show only the selected group
    if (cardType === "certificates" && certGroup) {
      certGroup.style.display = "block";
      certGroup.style.pointerEvents = 'auto';
      _closeOverlays();
    } else if (cardType === "incident" && incidentGroup) {
      incidentGroup.style.display = "block";
      incidentGroup.style.pointerEvents = 'auto';
      _closeOverlays();
    }
  }

  // Close mobile overlay/nav if open and ensure no overlay blocks clicks
  function _closeOverlays() {
    const mobileNav = document.getElementById('mobile-nav');
    const overlay = document.getElementById('mobile-overlay');
    const mobileMenu = document.getElementById('mobile-menu');
    const burger = document.getElementById('burger');
    if (mobileNav) mobileNav.classList.remove('show');
    if (overlay) overlay.classList.remove('show');
    if (mobileMenu) { mobileMenu.classList.remove('show'); mobileMenu.innerHTML = ''; }
    if (burger) burger.classList.remove('active');
    document.body.style.overflow = '';
  }


  // ✅ Helper: hide all service cards
  function hideServiceCards() {
    const serviceIds = [
      "certificates-group",
      "incident-reports-section",
      "barangay-clearance-section",
      "certificate-indigency-section",
      "certificate-residency-section"
    ];
    serviceIds.forEach(id => {
      const el = document.getElementById(id);
      if (el) el.style.display = "none";
    });
  }

  document.addEventListener("DOMContentLoaded", function() {
    // Certificates link - attach to all matching anchors (desktop + mobile)
    document.querySelectorAll('a[href="#certificates-group"]').forEach(function(el) {
      el.addEventListener("click", function(e) {
        e.preventDefault();
        showOnlyServiceCard("certificates");
        
        // Only close dropdown for DESKTOP menu, not mobile
        const dropdownContent = this.closest('.dropdown-content');
        if (dropdownContent) {
          const dropdown = dropdownContent.closest('.dropdown');
          // Check if this is inside mobile nav
          const isMobileNav = this.closest('#mobile-nav') !== null;
          
          if (dropdown && !isMobileNav) {
            // Only close for desktop dropdown
            dropdown.classList.add('force-close');
            setTimeout(() => {
              dropdown.classList.remove('force-close');
            }, 200);
          }
        }
      });
    });

    // Incident Reports link - attach to all matching anchors (desktop + mobile)
    document.querySelectorAll('a[href="#incident-reports-section"]').forEach(function(el) {
      el.addEventListener("click", function(e) {
        e.preventDefault();
        showOnlyServiceCard("incident");
        
        // Only close dropdown for DESKTOP menu, not mobile
        const dropdownContent = this.closest('.dropdown-content');
        if (dropdownContent) {
          const dropdown = dropdownContent.closest('.dropdown');
          // Check if this is inside mobile nav
          const isMobileNav = this.closest('#mobile-nav') !== null;
          
          if (dropdown && !isMobileNav) {
            // Only close for desktop dropdown
            dropdown.classList.add('force-close');
            setTimeout(() => {
              dropdown.classList.remove('force-close');
            }, 200);
          }
        }
      });
    });

    // Add CSS to support force-close class for dropdown
    const style = document.createElement('style');
    style.innerHTML = `.dropdown.force-close .dropdown-content { display: none !important; pointer-events: none !important; }`;
    document.head.appendChild(style);

    // Restore all sections and hide service cards when clicking any main nav link
    const mainNavLinks = [
      'a[href="#hero"]',
      'a[href="#about"]',
      'a[href="#officials"]',
      'a[href="#announcements"]',
      'a[href="#job-finder-section"]',
      'a[href="#contact"]',
      'a[href="#login"]'
    ];

    mainNavLinks.forEach(function(selector) {
      const link = document.querySelector(selector);
      if (link) {
        link.addEventListener("click", function(e) {
          const mainSections = [
            "hero", "about", "services", "officials", "announcements",
            "job-finder-section", "contact", "login-section",
            "admin-login-section", "newuser-section",
          ];

          if (selector === 'a[href="#login"]') {
            // Show only login-section
            mainSections.forEach(id => {
              const el = document.getElementById(id);
              if (el) el.style.display = (id === "login-section") ? "block" : "none";
            });
          } else {
            // Show everything except login/admin/newuser
            mainSections.forEach(id => {
              const el = document.getElementById(id);
              if (el) { 
                el.style.display =
                  (id !== "login-section" && id !== "admin-login-section" && id !== "newuser-section")
                    ? "block"
                    : "none";
              }
            });
            // ✅ Hide service cards when exiting certificates/incident
            hideServiceCards();
          }
        });
      }
    });

    // MOBILE MENU: Add nav links to mobile menu on toggle
    const menuToggle = document.getElementById('menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileLinks = [
      { href: '#hero', label: 'Home' },
      { href: '#about', label: 'About' },
      { href: '#certificates-group', label: 'Certificates' },
      { href: '#incident-reports-section', label: 'Incident Reports' },
      { href: '#officials', label: 'Officials' },
      { href: '#announcements', label: 'Announcements' },
        { href: '#job-finder-section', label: 'Job Finder 🔍' },
      { href: '#contact', label: 'Contact' },
          

      { href: '#login', label: 'Login', class: 'login-btn' }
    ];

    function isMobile() {
      return window.innerWidth <= 1024;
    }

    if (menuToggle) {
      menuToggle.addEventListener('click', function() {
        if (!isMobile()) return;
        if (mobileMenu.classList.contains('show')) {
          mobileMenu.classList.remove('show');
          mobileMenu.innerHTML = '';
          document.body.style.overflow = '';
        } else {
          let html = '';
          mobileLinks.forEach(link => {
            html += `<a href="${link.href}"${link.class ? ` class="${link.class}"` : ''}>${link.label}</a>`;
          });
          mobileMenu.innerHTML = html;
          mobileMenu.classList.add('show');
        }
      });
    }

    if (mobileMenu) {
      mobileMenu.addEventListener('click', function(e) {
        if (e.target.tagName === 'A') {
          mobileMenu.classList.remove('show');
          mobileMenu.innerHTML = '';
          document.body.style.overflow = '';
        }
      });
    }

    window.addEventListener('resize', function() {
      if (!isMobile()) {
        mobileMenu.classList.remove('show');
        mobileMenu.innerHTML = '';
        document.body.style.overflow = '';
      }
    });

    // OFFICIALS CARD: Toggle more-info on click
    document.querySelectorAll('.official-card').forEach(function(card) {
      function toggleInfo(e) {
        e.stopPropagation();
        const info = card.querySelector('.more-info');
        const isVisible = info && (info.style.display === 'block');
        document.querySelectorAll('.official-card .more-info').forEach(function(otherInfo) {
          otherInfo.style.display = 'none';
        });
        if (info && !isVisible) {
          info.style.display = 'block';
        }
      }
      card.addEventListener('click', function(e) {
        if (e.target.classList.contains('official-image') || e.currentTarget === e.target) {
          toggleInfo(e);
        }
      });
      const img = card.querySelector('.official-image');
      if (img) {
        img.addEventListener('click', function(e) {
          toggleInfo(e);
        });
      }
    });

    // Close dropdown when clicking anywhere inside the dropdown-content
    document.querySelectorAll('.dropdown-content').forEach(content => {
      content.addEventListener('click', function(e) {
        const dropdown = this.closest('.dropdown');
        if (dropdown) {
          const btn = dropdown.querySelector('.dropbtn');
          if (btn) btn.blur();
        }
      });
    });

    // Delegated handler: catch clicks on anchors that may be added dynamically (mobile-menu)
    document.addEventListener('click', function(e) {
      const certLink = e.target.closest && e.target.closest('a[href="#certificates-group"]');
      if (certLink) {
        e.preventDefault();
        showOnlyServiceCard('certificates');
        // If the link is inside a mobile menu, ensure it closes
        const mobileMenuEl = certLink.closest('#mobile-menu');
        if (mobileMenuEl) {
          mobileMenuEl.classList.remove('show');
          mobileMenuEl.innerHTML = '';
          document.body.style.overflow = '';
        }
        return;
      }

      const incLink = e.target.closest && e.target.closest('a[href="#incident-reports-section"]');
      if (incLink) {
        e.preventDefault();
        showOnlyServiceCard('incident');
        const mobileMenuEl = incLink.closest('#mobile-menu');
        if (mobileMenuEl) {
          mobileMenuEl.classList.remove('show');
          mobileMenuEl.innerHTML = '';
          document.body.style.overflow = '';
        }
        return;
      }

      // Ensure Job Finder links on mobile navigate to the standalone page
      const jobLink = e.target.closest && e.target.closest('a[href="#job-finder-section"]');
      if (jobLink) {
        e.preventDefault();
        // Close sliding mobile nav if open
        const mobileNavEl = jobLink.closest('#mobile-nav');
        if (mobileNavEl) {
          mobileNavEl.classList.remove('show');
          const overlay = document.getElementById('mobile-overlay');
          if (overlay) overlay.classList.remove('show');
          const burger = document.getElementById('burger');
          if (burger) burger.classList.remove('active');
          document.body.style.overflow = '';
        }
        // Close dynamic mobile menu if open
        const mobileMenuEl = jobLink.closest('#mobile-menu');
        if (mobileMenuEl) {
          mobileMenuEl.classList.remove('show');
          mobileMenuEl.innerHTML = '';
          document.body.style.overflow = '';
        }
        // Navigate to the Job Finder page
        sessionStorage.setItem('internalNav', 'true');
        window.location.href = 'jobfinder.php';
        return;
      }
    });
  });
</script>


<!-- Floating Chat Button -->
<?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>

   

  <button class="floating-chat-btn" onclick="toggleChat()">
    <i class="fas fa-comments"></i>
    <span id="unreadBadge" class="unread-badge" style="display:none;">0</span>
  </button>


  <!-- Chatbox Container -->
  <div class="chatbox" id="chatbox">
    <div class="chat-header">
      <div>
        <div style="font-weight:bold; font-size:16px;">Barangay Resident Support</div>
        <div id="adminStatusContainer" style="display:flex; align-items:center; gap:6px; margin-top:4px;">
          <span id="adminStatusIndicator" class="admin-status-indicator"></span>
          <span id="adminStatusText" style="font-size:12px; font-weight:normal;">Checking...</span>
        </div>
      </div>
    </div>

    <div class="messages" id="messages"></div>

    <div class="chat-input">
      <!-- Suggestion Questions -->
      <div id="support-suggestions" class="support-suggestions">
        <!-- suggestions injected by JS -->
      </div>
      
      <input type="text" id="userInput" placeholder="Type your message..."
        onkeydown="if(event.key==='Enter') sendMessage()">
      <button onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
    </div>
   </div>

  <!-- Unsend Confirmation Modal -->
  <div id="unsendModal" class="unsend-modal" style="display: none;">
    <div class="unsend-modal-overlay"></div>
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

  <style>
    /* Floating Button */
    .floating-chat-btn {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: #15b300ff;
      color: white;
      border: none;
      border-radius: 50%;
      width: 60px;
      height: 60px;
      font-size: 24px;
      cursor: pointer;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      transition: transform 0.2s, background 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .floating-chat-btn:hover {
      background: #15b300ff;
      transform: scale(1.1);
    }

    /* 🔴 Unread Badge */
    .unread-badge {
      position: absolute;
      top: 8px;
      right: 8px;
      background: red;
      color: white;
      font-size: 12px;
      font-weight: bold;
      border-radius: 50%;
      padding: 3px 6px;
      min-width: 18px;
      height: 18px;
      text-align: center;
      line-height: 12px;
    }

    /* Chatbox */
    .chatbox {
      position: fixed;
      bottom: 90px;
      right: 20px;
      width: 400px;
      max-height:500px;
      display: none;
      flex-direction: column;
      background: white;
      border-radius: 15px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.25);
      overflow: hidden;
      animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .chat-header {
      background: #15b300ff;
      color: white;
      padding: 16px;
      font-weight: bold;
      font-size: 16px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    /* Admin Status Indicator */
    .admin-status-indicator {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      display: inline-block;
      background: #6c757d;
      flex-shrink: 0;
    }

    .admin-status-indicator.online {
      background: #fff;
      box-shadow: 0 0 8px rgba(255, 255, 255, 0.8);
      animation: adminPulse 2s infinite;
    }

    @keyframes adminPulse {
      0%, 100% {
        opacity: 1;
        transform: scale(1);
      }
      50% {
        opacity: 0.7;
        transform: scale(1.1);
      }
    }

   .messages {
  flex: 1;
  max-height: 500px;
  overflow-y: auto;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  scroll-behavior: smooth;
}

.message {
  padding: 10px 14px;
  border-radius: 18px;
  max-width: 70%;
  word-wrap: break-word;
  font-size: 14px;
  display: flex;
  align-items: flex-end;
  gap: 8px;
  animation: popIn 0.2s ease;
  position: relative;
  cursor: pointer;
  transition: background 0.2s ease;
  line-height: 1.4;
}

.message.user:hover {
  background: #009399;
}

.message.admin:hover {
  background: #e8e8e8;
}

/* Unsend button */
.unsend-btn {
  position: absolute;
  /* position the button slightly away from the message bubble to avoid covering text */
  right: calc(100% + 6px);
  top: 50%;
  transform: translateY(-50%);
  background: transparent;
  color: #dc3545; /* red text */
  border: none;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 11px;
  cursor: pointer;
  transition: all 0.12s ease;
  z-index: 10;
  display: none;
  font-weight: 600;
  white-space: nowrap;
  text-decoration: underline;
  width: auto !important;
  min-width: 0 !important;
  box-sizing: content-box !important;
}

.unsend-btn:hover {
  opacity: 0.95;
  transform: translateY(-50%);
  background: transparent !important;
  color: #c82333 !important;
  box-shadow: none !important;
}

.message.selected {
  box-shadow: 0 0 0 2px #15b300;
}

.message.selected .unsend-btn {
  display: inline-block;
  animation: slideInLeft 0.16s ease-out;
}

/* Extra specificity to prevent global button:hover rules from changing unsend appearance */
.message .unsend-btn:hover {
  background: transparent !important;
  color: #c82333 !important;
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


    @keyframes popIn {
      from { opacity: 0; transform: scale(0.9); }
      to { opacity: 1; transform: scale(1); }
    }

    @keyframes fadeOut {
      from { opacity: 1; transform: scale(1); }
      to { opacity: 0; transform: scale(0.8); }
    }

    /* User message (right, blue) */
    .message.user {
      background: #00adb3ff;
      color: white;
      margin-left: auto;
      border-bottom-right-radius: 4px;
      flex-direction: row;
      justify-content: flex-end;
    }

    .message.user .profile-img {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      object-fit: cover;
      flex-shrink: 0;
      border: 2px solid rgba(255, 255, 255, 0.3);
      order: 2;
    }

    .message.user > div {
      order: 1;
    }

    /* Admin message (left, gray) */
    .message.admin {
      background: #f1f1f1;
      color: #333;
      margin-right: auto;
      border-bottom-left-radius: 4px;
    }

    .message.admin .profile-img {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      object-fit: cover;
      flex-shrink: 0;
      border: 2px solid #e0e0e0;
    }

    .message .emoji {
      font-size: 18px;
      flex-shrink: 0;
      display: none;
    }

    .timestamp {
      font-size: 10px;
      color: rgba(0,0,0,0.5);
      display: block;
      margin-top: 3px;
      white-space: nowrap;
    }
    
    .message.user .timestamp {
      color: rgba(255,255,255,0.7);
      text-align: right;
    }
    
    .message.admin .timestamp {
      text-align: left;
    }

    .chat-input {
      display: flex;
      padding: 12px;
      border-top: 1px solid #ddd;
      background: #fafafa;
      gap: 8px;
      position: relative;
    }
    .chat-input input {
      flex: 1;
      padding: 10px 14px;
      border-radius: 20px;
      border: 1px solid #ccc;
      outline: none;
      font-size: 15px;
    }
    .chat-input button {
      background: #007bff;
      color: white;
      border: none;
      border-radius: 50%;
      width: 44px;
      height: 44px;
      cursor: pointer;
      font-size: 18px;
      transition: background 0.2s;
    }
    .chat-input button:hover {
      background: #15b300ff;
    }

    /* Suggestion Questions for Barangay Resident Support */
    .support-suggestions {
      display: none;
      position: absolute;
      bottom: 100%;
      left: 12px;
      right: 12px;
      background: white;
      border: 1px solid #ddd;
      border-radius: 12px;
      box-shadow: 0 -4px 12px rgba(0,0,0,0.15);
      max-height: 200px;
      overflow-y: auto;
      z-index: 1000;
      margin-bottom: 8px;
      padding: 6px;
    }

    .support-suggestion-item {
      padding: 10px 12px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 14px;
      color: #333;
      transition: background 0.2s;
      margin-bottom: 4px;
    }

    .support-suggestion-item:hover {
      background: #f0f0f0;
    }

    .support-suggestion-item:last-child {
      margin-bottom: 0;
    }

    /* Unsend Modal */
    .unsend-modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 10000;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .unsend-modal-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(4px);
      animation: fadeIn 0.2s ease;
    }

    .unsend-modal-content {
      position: relative;
      background: white;
      border-radius: 12px;
      padding: 24px;
      max-width: 400px;
      width: 90%;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
      animation: slideUp 0.3s ease;
      text-align: center;
      z-index: 10001;
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

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    /* Mobile Responsive */
    @media(max-width: 500px) {
      .chatbox {
        width: 95%;
        right: 10px;
        bottom: 80px;
        max-height: 400px;
      }
      
      .chat-header {
        padding: 8px 10px;
        font-size: 13px;
      }
      
      .chat-header span {
        font-size: 13px;
      }
      
      .messages {
        max-height: 280px;
        padding: 10px;
        gap: 10px;
      }
      
      .unsend-btn {
        right: calc(100% + 5px);
        font-size: 10px;
        padding: 4px 8px;
      }
      
      .message {
        font-size: 13px;
        padding: 8px 12px;
        max-width: 80%;
      }
      
      .message .emoji {
        font-size: 14px;
      }
      
      .timestamp {
        font-size: 10px;
      }
      
      .chat-input {
        padding: 8px;
      }
      
      .chat-input input {
        font-size: 13px;
        padding: 6px 10px;
      }
      
      .chat-input button {
        width: 32px;
        height: 32px;
        font-size: 14px;
      }

      .unsend-modal-content {
        padding: 20px;
      }

      .unsend-modal-title {
        font-size: 1.2rem;
      }

      .unsend-modal-text {
        font-size: 0.9rem;
      }

      .unsend-modal-actions {
        flex-direction: column;
      }

      .unsend-modal-btn {
        width: 100%;
      }
    }
  </style>
<script> 
let lastMessageId = 0;
let unreadCount = 0;
let chatInterval = null;
let deletedMessageIds = new Set(); // Track deleted messages

function appendMessage(sender, text, createdAt = Date.now(), id = null, profileImage = null, isRead = 0) {
    const messages = document.getElementById('messages');
    // Don't add if message was deleted or already exists
    if (id && (deletedMessageIds.has(id) || messages.querySelector(`[data-id="${id}"]`))) return;

    const message = document.createElement('div');
    message.classList.add('message', sender);
    message.setAttribute("data-id", id);

    // Create profile image element
    const profileImg = document.createElement('img');
    profileImg.classList.add('profile-img');
    profileImg.alt = sender === 'user' ? 'User' : 'Admin';
    
    // Set profile image source
    if (sender === 'admin') {
        // Always use admin-panel.png for admin messages
        profileImg.src = 'admin-panel.png';
    } else if (profileImage && profileImage.trim() !== '') {
        // Use user's profile image if available
        profileImg.src = profileImage;
    } else {
        // Fallback to default avatar for users
        profileImg.src = 'default_avatar.png';
    }

    const msgContent = document.createElement('div');
    msgContent.style.display = 'flex';
    msgContent.style.flexDirection = 'column';
    msgContent.style.flex = '1';
    msgContent.style.minWidth = '0';

    const msgText = document.createElement('span');
    msgText.textContent = text;

    const timestamp = document.createElement('span');
    timestamp.classList.add('timestamp');
    timestamp.textContent = new Date(createdAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    msgContent.appendChild(msgText);
    msgContent.appendChild(timestamp);



    if (sender === 'user') {
        message.appendChild(msgContent);
        message.appendChild(profileImg);
    } else {
        message.appendChild(profileImg);
        message.appendChild(msgContent);
    }

    // Add unsend button for user messages
    if (sender === 'user' && id) {
  const unsendBtn = document.createElement('button');
  unsendBtn.classList.add('unsend-btn');
  unsendBtn.textContent = 'Unsend';
        unsendBtn.onclick = function(e) {
            e.stopPropagation();
            unsendMessage(id, message);
        };
        message.appendChild(unsendBtn);

        // Add click handler to toggle selection
        message.addEventListener('click', function(e) {
            // Don't toggle if clicking the button itself
            if (e.target.closest('.unsend-btn')) return;
            
            // Close other selected messages
            document.querySelectorAll('.message.selected').forEach(msg => {
                if (msg !== message) msg.classList.remove('selected');
            });
            // Toggle this message's selection
            message.classList.toggle('selected');
        });
    }

  // For user messages always add a status indicator element (Sent or Seen)
  if (sender === 'user') {
    const status = document.createElement('span');
    status.classList.add('seen-indicator');
    if (Number(isRead) === 1) {
      status.classList.add('seen');
      status.textContent = 'Seen';
    } else {
      status.classList.add('sent');
      status.textContent = 'Sent';
    }
    msgContent.appendChild(status);
  }

    // ✅ Add new message at the bottom
    messages.appendChild(message);

    // ✅ Always scroll to the latest chat
    messages.scrollTop = messages.scrollHeight;

    if (id > lastMessageId) lastMessageId = id; // Update lastMessageId
}


function sendMessage() {
    const input = document.getElementById('userInput');
    const message = input.value.trim();
    if (!message) return;
    sendToAdmin(message);
    input.value = '';
    
    // Hide suggestions after sending
    const suggestionsContainer = document.getElementById('support-suggestions');
    if (suggestionsContainer) suggestionsContainer.style.display = 'none';
}

function sendToAdmin(message) {
    fetch("send_chats.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "message=" + encodeURIComponent(message)
    })
  .then(res => res.json().catch(() => null))
  .then(data => {
    if (!data) {
      console.error('Unexpected non-JSON response from send_chats.php');
      return;
    }
    if (data.status === 'success') {
      // Refresh chats to pick up immediate bot/admin reply inserted by server
      loadChats();
    } else if (data.status === 'duplicate') {
      console.log('Duplicate prevented');
    } else if (data.status === 'unauthorized') {
      console.log('User not logged in; message not saved to support.');
    } else {
      console.error('Send error:', data);
    }
  })
  .catch(err => console.error("Fetch error:", err));
}

function loadChats() {
    fetch("get_chats.php?userid=<?= $_SESSION['userid'] ?>&last_id=" + lastMessageId)
        .then(res => res.json())
        .then(data => {
            let newUnread = 0;

            data.forEach(chat => {
                appendMessage(chat.sender, chat.message, chat.created_at, chat.chat_id, chat.profile_image, chat.is_read);
                if (chat.sender === "admin" && chat.is_read == 0) newUnread++;
            });

            unreadCount = newUnread;
            updateBadge();
        })
        .catch(err => console.error("Load error:", err));
}

function reloadAllChats() {
    // Clear all messages and reload from scratch
    const messagesContainer = document.getElementById('messages');
    messagesContainer.innerHTML = '';
    lastMessageId = 0;
    deletedMessageIds.clear(); // Clear deleted IDs to sync with server
    
    fetch("get_chats.php?userid=<?= $_SESSION['userid'] ?>&last_id=0")
        .then(res => res.json())
        .then(data => {
            let newUnread = 0;

            data.forEach(chat => {
                appendMessage(chat.sender, chat.message, chat.created_at, chat.chat_id, chat.profile_image, chat.is_read);
                if (chat.sender === "admin" && chat.is_read == 0) newUnread++;
            });

            unreadCount = newUnread;
            updateBadge();
        })
        .catch(err => console.error("Reload error:", err));
}

function updateBadge() {
    const badge = document.getElementById("unreadBadge");
    if (unreadCount > 0) {
        badge.style.display = "inline-block";
        badge.textContent = unreadCount;
    } else {
        badge.style.display = "none";
    }
}

function toggleChat() {
    const chatbox = document.getElementById("chatbox");
    const isVisible = chatbox.style.display === "flex";

    if (isVisible) {
        chatbox.style.display = "none";
    } else {
        chatbox.style.display = "flex";

        // Don't auto-focus to prevent automatic keyboard popup
        // const chatInput = document.getElementById("userInput");
        // if (chatInput) {
        //     setTimeout(() => {
        //         chatInput.focus();
        //     }, 100);
        // }

        // Mark admin messages as read
        fetch("mark_read.php")
            .then(res => res.json())
            .then(data => {
                console.log("Marked as read:", data);
                unreadCount = 0; 
                updateBadge();
                loadChats();

                // ✅ Scroll to bottom after opening
                const messages = document.getElementById("messages");
                setTimeout(() => {
                    messages.scrollTop = messages.scrollHeight;
                }, 300); // small delay to wait for messages to render
            })
            .catch(err => console.error("Mark read error:", err));
    }
}


let pendingUnsendChatId = null;
let pendingUnsendElement = null;

function unsendMessage(chatId, messageElement) {
    pendingUnsendChatId = chatId;
    pendingUnsendElement = messageElement;
    document.getElementById('unsendModal').style.display = 'flex';
}

function closeUnsendModal() {
    document.getElementById('unsendModal').style.display = 'none';
    pendingUnsendChatId = null;
    pendingUnsendElement = null;
}

function confirmUnsend() {
    if (!pendingUnsendChatId || !pendingUnsendElement) return;

    fetch('unsend_chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'chat_id=' + pendingUnsendChatId
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Add to deleted IDs immediately to prevent re-adding
            deletedMessageIds.add(pendingUnsendChatId);
            
            // Close any selected messages
            document.querySelectorAll('.message.selected').forEach(msg => {
                msg.classList.remove('selected');
            });
            
            // Remove message with smooth animation
            if (pendingUnsendElement && pendingUnsendElement.parentNode) {
                pendingUnsendElement.style.animation = 'fadeOut 0.3s ease forwards';
                pendingUnsendElement.style.pointerEvents = 'none';
                
                setTimeout(() => {
                    if (pendingUnsendElement && pendingUnsendElement.parentNode) {
                        pendingUnsendElement.parentNode.removeChild(pendingUnsendElement);
                    }
                    // Close modal after animation completes
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

// JavaScript timeAgo function for admin status
function timeAgo(datetime) {
    if (!datetime) return 'No admin available';
    
    const now = new Date();
    const ago = new Date(datetime);
    const diffMs = now - ago;
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);
    
    if (diffDay > 0) return 'Last seen ' + diffDay + ' day' + (diffDay > 1 ? 's' : '') + ' ago';
    if (diffHour > 0) return 'Last seen ' + diffHour + ' hour' + (diffHour > 1 ? 's' : '') + ' ago';
    if (diffMin > 0) return 'Last seen ' + diffMin + ' min' + (diffMin > 1 ? 's' : '') + ' ago';
    return 'Last seen just now';
}

// Function to update admin status
function updateAdminStatus() {
    fetch('get_admin_status.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const indicator = document.getElementById('adminStatusIndicator');
                const statusText = document.getElementById('adminStatusText');
                
                if (indicator && statusText) {
                    const isOnline = data.is_online == 1;
                    
                    if (isOnline) {
                        indicator.classList.add('online');
                        statusText.textContent = 'Admin available now';
                    } else {
                        indicator.classList.remove('online');
                        statusText.textContent = timeAgo(data.last_active);
                    }
                }
            }
        })
        .catch(err => {
            console.error('Error updating admin status:', err);
            const statusText = document.getElementById('adminStatusText');
            if (statusText) {
                statusText.textContent = 'Status unavailable';
            }
        });
}

// Initial load
document.addEventListener('DOMContentLoaded', () => {
    loadChats();
    chatInterval = setInterval(loadChats, 3000);
    
    // Update admin status immediately and every 5 seconds
    updateAdminStatus();
    setInterval(updateAdminStatus, 5000);
});
</script>


<?php endif; ?>

<script>
// Smooth scroll to section when clicking nav links, without hiding any section
document.addEventListener("DOMContentLoaded", function() {
  // List of all main nav links and their section IDs
  const navLinks = [
    { selector: 'a[href="#hero"]', section: 'hero' },
    { selector: 'a[href="#about"]', section: 'about' },
    { selector: 'a[href="#officials"]', section: 'officials' },
    { selector: 'a[href="#announcements"]', section: 'announcements' },
    { selector: 'a[href="#job-finder-section"]', section: 'job-finder-section' },
    { selector: 'a[href="#contact"]', section: 'contact' }
  ];

  navLinks.forEach(linkObj => {
    const link = document.querySelector(linkObj.selector);
    if (link) {
      // Remove any previous click handlers to avoid duplicate scrolls
      link.onclick = null;
      // Direct to jobfinder.php if job-finder-section link is clicked
      if (linkObj.selector === 'a[href="#job-finder-section"]') {
        link.addEventListener("click", function(e) {
          e.preventDefault();
          sessionStorage.setItem('internalNav', 'true');
          window.location.href = "jobfinder.php";
        });
      } else {
        link.addEventListener("click", function(e) {
          e.preventDefault();
          const section = document.getElementById(linkObj.section);
          if (section) {
            // Only scroll if not already at the section (fixes double-click jump)
            const sectionTop = section.getBoundingClientRect().top + window.pageYOffset;
            const scrollOffset = Math.abs(window.pageYOffset - sectionTop);
            if (scrollOffset > 5) {
              section.scrollIntoView({ behavior: "smooth" });
            }
          }
        });
      }
    }
  });
});
</script>

<script>
// Ensure clicking Home (or its mobile equivalent) hides login UI and restores main sections
document.addEventListener('click', function (e) {
  try {
    const heroLink = e.target.closest && (e.target.closest('a[href="#hero"]') || e.target.closest('a[href="#home"]'));
    if (!heroLink) return;

    // Close role modal if open
    const roleModal = document.getElementById('role-modal');
    if (roleModal) roleModal.style.display = 'none';

    // Hide login forms
    ["login-section", "admin-login-section", "newuser-section"].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.style.display = 'none';
    });

    // Show main sections if helper exists
    if (typeof showMainSections === 'function') {
      showMainSections();
    } else {
      // fallback: ensure hero/other main sections are visible
      ["hero","about","services","officials","announcements","contact","job-finder-section"].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'block';
      });
    }
  } catch (err) {
    console.warn('home-click handler error', err);
  }
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('incident_submitted') === '1') {
    showOnlyServiceCard("incident");
  }
});
</script>

<script>
// Simple translation dictionary
const translations = {
  en: {
    welcome: "Welcome to Barangay Sabang",
    heroDesc: "A peaceful and progressive community dedicated to serving its residents",
    contactUs: "Contact Us",
    aboutTitle: "About Our Barangay",
    aboutDesc: "Barangay Sabang is a vibrant community located in the northern part of Dasmarinas City, Cavite. The name “Sabang” comes from a local word meaning “to overflow,” inspired by the creeks that run through the area.",
    aboutHistory: "Historically, Sabang was a farming village known for its fertile rice fields. Over time, it has transformed into a modern residential barangay with subdivisions, schools, and local businesses. It is also famous as the birthplace of Leonardo “Nardong Putik,” a well-known folk hero. Today, Barangay Sabang continues to grow while preserving its strong sense of community and rich local history.",
    officialsTitle: "Our Barangay Officials",
    announcements: "📢 Announcements",
    contact: "Contact Us",
    sendMsg: "Send Us a Message",
    subject: "Subject",
    message: "Message",
    sendMessage: "Send Message",
    // Add more keys as needed
  },
  tl: {
    welcome: "Maligayang Pagdating sa Barangay Sabang",
    heroDesc: "Isang mapayapa at maunlad na komunidad na handang maglingkod sa mga residente",
    contactUs: "Makipag-ugnayan",
    aboutTitle: "Tungkol sa Aming Barangay",
    aboutDesc: "Ang Barangay Sabang ay isang masiglang komunidad sa hilagang bahagi ng Dasmarinas City, Cavite. Ang pangalan na “Sabang” ay mula sa salitang lokal na nangangahulugang “pag-apaw,” na inspirasyon mula sa mga sapa sa lugar.",
    aboutHistory: "Noong una, ang Sabang ay isang baryong pansakahan na kilala sa matabang palayan. Sa paglipas ng panahon, ito ay naging modernong barangay na may mga subdivision, paaralan, at negosyo. Kilala rin ito bilang lugar ng kapanganakan ni Leonardo “Nardong Putik,” isang kilalang bayani ng bayan. Hanggang ngayon, patuloy na umuunlad ang Barangay Sabang habang pinananatili ang matibay na samahan at mayamang kasaysayan.",
    officialsTitle: "Mga Opisyal ng Barangay",
    announcements: "📢 Mga Anunsyo",
    contact: "Makipag-ugnayan",
    sendMsg: "Magpadala ng Mensahe",
    subject: "Paksa",
    message: "Mensahe",
    sendMessage: "Ipadala ang Mensahe",
    // Add more keys as needed
  }
};

// Map element IDs/classes to translation keys
const transMap = [
  { selector: "h1", key: "welcome" },
  { selector: ".hero p", key: "heroDesc" },
  { selector: '.hero a', key: "contactUs" },
  { selector: "#about h2", key: "aboutTitle" },
  { selector: "#about .about-content .about-text p:first-of-type", key: "aboutDesc" },
  { selector: "#about-history", key: "aboutHistory" }, // <-- FIX: Add this line!
  { selector: "#officials h2", key: "officialsTitle" },
  { selector: "#announcements h2", key: "announcements" },
  { selector: "#contact h2", key: "contact" },
  { selector: ".contact-form h3", key: "sendMsg" },
  { selector: '.contact-form input[name="subject"]', key: "subject", attr: "placeholder" },
  { selector: '.contact-form textarea[name="message"]', key: "message", attr: "placeholder" },
  { selector: '.contact-form button[type="submit"]', key: "sendMessage" }
  // Add more mappings as needed
];

const navTrans = {
  en: ["Home", "About", "Services ▼", "Certificates", "Incident Reports", "Officials", "Announcements", "Job Finder 🔍", "Contact", "Login"],
  tl: ["Home", "Tungkol", "Serbisyo ▼", "Sertipiko", "Ulat ng Insidente", "Mga Opisyal", "Mga Anunsyo", "Hanapbuhay 🔍", "Makipag-ugnayan", "Mag-login"]
};

function setLanguage(lang) {
  // Update page text
  transMap.forEach(item => {
    const el = document.querySelector(item.selector);
    if (el) {
      if (item.attr) {
        el.setAttribute(item.attr, translations[lang][item.key]);
      } else {
        el.textContent = translations[lang][item.key];
      }
    }
  });

  // Update navbar links
  const navLinks = document.querySelectorAll("#desktop-nav-links a, #desktop-nav-links .dropbtn, #desktop-nav-links .dropdown-content a");
  const labels = navTrans[lang];
  let i = 0;
  navLinks.forEach(link => {
    if (labels[i]) link.textContent = labels[i];
    i++;
  });

  // Highlight active language button (both navbar and profile)
  if (document.getElementById("lang-en")) document.getElementById("lang-en").classList.toggle("btn-primary", lang === "en");
  if (document.getElementById("lang-tl")) document.getElementById("lang-tl").classList.toggle("btn-danger", lang === "tl");
  if (document.getElementById("nav-lang-en")) document.getElementById("nav-lang-en").classList.toggle("btn-primary", lang === "en");
  if (document.getElementById("nav-lang-tl")) document.getElementById("nav-lang-tl").classList.toggle("btn-danger", lang === "tl");

  localStorage.setItem("lang", lang);
}

// Sync both sets of buttons
if (document.getElementById("lang-en")) document.getElementById("lang-en").onclick = () => setLanguage("en");
if (document.getElementById("lang-tl")) document.getElementById("lang-tl").onclick = () => setLanguage("tl");
if (document.getElementById("nav-lang-en")) document.getElementById("nav-lang-en").onclick = () => setLanguage("en");
if (document.getElementById("nav-lang-tl")) document.getElementById("nav-lang-tl").onclick = () => setLanguage("tl");

// On page load, set language from localStorage or default to English
document.addEventListener("DOMContentLoaded", () => {
  setLanguage(localStorage.getItem("lang") || "en");
});

// Refresh CAPTCHA function
function refreshCaptcha() {
  const captchaImg = document.getElementById('captcha-image');
  if (captchaImg) {
    // Add timestamp to prevent caching
    captchaImg.src = 'captcha.php?' + new Date().getTime();
    // Clear the captcha input field
    const captchaInput = document.getElementById('captcha');
    if (captchaInput) {
      captchaInput.value = '';
      captchaInput.focus();
    }
  }
}

// Toggle Password Visibility function
function togglePassword(inputId, button) {
  const input = document.getElementById(inputId);
  const icon = button.querySelector('i');
  
  if (input && icon) {
    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.remove('fa-eye');
      icon.classList.add('fa-eye-slash');
    } else {
      input.type = 'password';
      icon.classList.remove('fa-eye-slash');
      icon.classList.add('fa-eye');
    }
  }
}

// ===== Suggestion Questions for Barangay Resident Support =====
(function setupSupportSuggestions() {
  let suggestions = [];

  const container = document.getElementById('support-suggestions');
  const userInput = document.getElementById('userInput');
  
  if (!container || !userInput) return;

  // Make container scrollable
  container.style.overflow = 'auto';

  // Fetch questions from chatbot_responses table
  fetch('get_suggestion_questions.php')
    .then(res => res.json())
    .then(data => {
      if (data.success && data.questions && data.questions.length > 0) {
        suggestions = data.questions;
      } else {
        // Fallback questions if fetch fails
        suggestions = [
          'How do I request a barangay clearance?',
          'What are the office hours?',
          'How to report an incident?',
          'Who are the barangay officials?'
        ];
      }
    })
    .catch(err => {
      console.error('Failed to load suggestion questions:', err);
      // Fallback questions if fetch fails
      suggestions = [
        'How do I request a barangay clearance?',
        'What are the office hours?',
        'How to report an incident?',
        'Who are the barangay officials?'
      ];
    });

  function render(list) {
    container.innerHTML = '';
    if (list.length === 0) {
      container.style.display = 'none';
      return;
    }
    container.style.display = 'block';
    
    list.forEach(item => {
      const el = document.createElement('div');
      el.className = 'support-suggestion-item';
      el.textContent = item;
      
      // Track touch for mobile
      let touchStartY = 0;
      let touchMoved = false;
      
      el.addEventListener('touchstart', function(e) {
        touchStartY = e.touches[0].clientY;
        touchMoved = false;
      }, { passive: true });
      
      el.addEventListener('touchmove', function(e) {
        const touchY = e.touches[0].clientY;
        if (Math.abs(touchY - touchStartY) > 10) {
          touchMoved = true;
        }
      }, { passive: true });
      
      el.addEventListener('touchend', function(e) {
        if (!touchMoved) {
          e.preventDefault();
          userInput.value = item;
          container.style.display = 'none';
          userInput.focus();
        }
      });
      
      // Desktop click
      el.addEventListener('click', function() {
        userInput.value = item;
        container.style.display = 'none';
        userInput.focus();
      });
      
      container.appendChild(el);
    });
  }

  // Initial setup: keep hidden
  container.style.display = 'none';
  let userHasFocused = false;

  // Filter while typing
  userInput.addEventListener('input', function() {
    if (!userHasFocused) return;
    
    const q = this.value.trim().toLowerCase();
    if (!q) {
      render(suggestions);
    } else {
      const filtered = suggestions.filter(s => s.toLowerCase().includes(q));
      render(filtered);
    }
  });

  // Show suggestions when focusing
  userInput.addEventListener('focus', function() {
    userHasFocused = true;
    render(suggestions);
  });

  // Hide on blur (delay to allow click)
  userInput.addEventListener('blur', function() {
    setTimeout(() => {
      container.style.display = 'none';
    }, 200);
  });
})();

</script>

<!-- Automatic logout script for logged-in users -->
<?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
<script src="auto_logout.js"></script>
<?php endif; ?>
