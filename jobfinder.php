<?php
session_start();
include 'config.php';

// ✅ Set timezone to Philippines (UTC+8)
date_default_timezone_set('Asia/Manila');

// --- Unread count endpoint ---
if (isset($_GET['unread_count'])) {
    header('Content-Type: application/json');
    if (!isset($_SESSION['userid'])) {
        echo json_encode(['count' => 0]);
        exit;
    }
    $userid = $_SESSION['userid'];
    $stmt = $conn->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id=? AND is_read=0");
    $stmt->bind_param("s", $userid);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    echo json_encode(['count' => (int)$count]);
    exit;
}

$currentUserId = $_SESSION['userid'];

// Check if current user is blocked from using jobfinder and get profile image
$check_blocked = $conn->prepare("SELECT COALESCE(blocked_from_jobfinder, 0) as is_blocked, profile_image FROM residents WHERE unique_id = ?");
$check_blocked->bind_param("s", $currentUserId);
$check_blocked->execute();
$check_blocked->bind_result($is_blocked, $current_user_profile_image);
$check_blocked->fetch();
$check_blocked->close();

$user_is_blocked = ($is_blocked == 1);
$current_user_profile_image = !empty($current_user_profile_image) ? $current_user_profile_image : 'default_avatar.png';

// ✅ Fetch residents with skills (exclude logged-in user and blocked users), with average rating, sorted by rating desc
$sql = "
  SELECT r.unique_id, r.surname, r.first_name, r.age, r.occupation_skills, r.profile_image, r.skill_description,
       COALESCE(AVG(cr.rating), 0) AS avg_rating, COUNT(cr.rating) AS rating_count,
       COALESCE(r.jobfinder_verified, 0) AS jobfinder_verified,
       COALESCE(u.is_online, 0) AS is_online, u.last_active
  FROM residents r
  JOIN useraccounts u ON u.userid = r.unique_id
  LEFT JOIN chat_ratings cr ON cr.receiver_id = r.unique_id
  WHERE r.occupation_skills IS NOT NULL 
    AND r.occupation_skills != ''
    AND r.unique_id != ?
    AND COALESCE(r.blocked_from_jobfinder, 0) = 0
    AND COALESCE(r.jobfinder_active, 1) = 1
  GROUP BY r.unique_id
  ORDER BY avg_rating DESC, r.surname ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $currentUserId);
$stmt->execute();
$residents = $stmt->get_result();

if ($residents === false) {
  die("SQL Error: " . $conn->error);
}

// ✅ Function to calculate time ago
function timeAgo($datetime) {
    if (empty($datetime)) return 'Never active';
    
    try {
        $timezone = new DateTimeZone('Asia/Manila');
        $now = new DateTime('now', $timezone);
        $ago = new DateTime($datetime, $timezone);
        $diff = $now->diff($ago);
        
        if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
        if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
        if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        if ($diff->i > 0) return $diff->i . ' min' . ($diff->i > 1 ? 's' : '') . ' ago';
        return 'Just now';
    } catch (Exception $e) {
        return 'Unknown';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Job Finder with Chat</title>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet"/>

  <style>
    /* Modal Animation */
    #profileModal, #howToUseModal {
      transition: opacity 0.35s cubic-bezier(.4,2,.6,1), transform 0.35s cubic-bezier(.4,2,.6,1);
      opacity: 0;
      pointer-events: none;
    }
    #profileModal.show, #howToUseModal.show {
      opacity: 1;
      pointer-events: auto;
      transform: scale(1.04);
    }

    /* Chat bubble entrance */
    .message {
      animation: bubbleIn 0.5s cubic-bezier(.4,2,.6,1);
    }
    @keyframes bubbleIn {
      from { opacity: 0; transform: translateY(20px) scale(0.95); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* Button ripple effect */
    .chat-btn:active::after {
      content: '';
      position: absolute;
      left: 50%; top: 50%;
      transform: translate(-50%,-50%);
      width: 120%; height: 120%;
      background: rgba(67,233,123,0.18);
      border-radius: 50%;
      z-index: 2;
      animation: ripple 0.4s linear;
    }
    @keyframes ripple {
      from { opacity: 0.7; }
      to { opacity: 0; }
    }

    /* Glowing unread badge */
    .unread-badge {
      box-shadow: 0 0 8px 2px #ff3b3b88, 0 0 0 0 #ff3b3b;
      animation: glowBadge 1.2s infinite alternate;
    }
    @keyframes glowBadge {
      from { box-shadow: 0 0 8px 2px #ff3b3b88; }
      to { box-shadow: 0 0 16px 4px #ff3b3bcc; }
    }
    
    /* Reviews scrollbar */
    #modalReviewsList::-webkit-scrollbar,
    #ratingModalReviewsList::-webkit-scrollbar {
      width: 6px;
    }
    #modalReviewsList::-webkit-scrollbar-track,
    #ratingModalReviewsList::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }
    #modalReviewsList::-webkit-scrollbar-thumb,
    #ratingModalReviewsList::-webkit-scrollbar-thumb {
      background: #28a745;
      border-radius: 10px;
    }
    #modalReviewsList::-webkit-scrollbar-thumb:hover,
    #ratingModalReviewsList::-webkit-scrollbar-thumb:hover {
      background: #20c997;
    }
    
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: "Inter", sans-serif;
      display: flex;
      height: 100vh;
      background: #f1f4f9;
      color: #333;
      overflow: hidden;
    }

    /* Sidebar */
    .sidebar {
      width: 240px;
      background: linear-gradient(180deg, #28a745, #1f7c32);
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 2rem 1rem;
      box-shadow: 4px 0 20px rgba(0,0,0,0.1);
      color: white;
    }
    .sidebar a {
      display: inline-block;
      margin-bottom: 2rem;
      color: #fff;
      font-weight: 600;
      text-decoration: none;
      background: rgba(255,255,255,0.1);
      padding: 10px 16px;
      border-radius: 10px;
      transition: 0.3s;
    }
    .sidebar a:hover {
      background: rgba(255,255,255,0.2);
    }
    .sidebar img {
      width: 100px;
      margin-top: 1rem;
      border-radius: 14px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.25);
    }

    /* Main Content */
    .main-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      border-radius: 12px 0 0 12px;
      background: #fff;
    }

    /* Header */
    .header {
      background: #fff;
      padding: 1rem 2rem;
      display: flex;  
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid #eee;
    }
    .header div {
      font-weight: 600;
      font-size: 1.3rem;
      color: #28a745;
    }
    .header-controls {
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    .search-box input {
      padding: 10px 16px;
      border: 1px solid #ddd;
      border-radius: 25px;
      font-size: 0.9rem;
      transition: 0.3s;
      width: 200px;
    }
    .search-box input:focus {
      border-color: #28a745;
      outline: none;
      box-shadow: 0 0 8px rgba(40,167,69,0.2);
    }
    .chat-btn {
      background: linear-gradient(90deg,#28a745 80%,#43e97b 100%);
      color: white;
      border: none;
      padding: 10px 18px;
      border-radius: 25px;
      font-size: 0.9rem;
      cursor: pointer;
      transition: 0.22s cubic-bezier(.4,2,.6,1);
      box-shadow: 0 4px 12px rgba(40,167,69,0.13);
      font-weight: 600;
      letter-spacing: 0.5px;
      position: relative;
      z-index: 1;
    }
    .chat-btn:hover {
      background: linear-gradient(90deg,#43e97b 80%,#28a745 100%);
      box-shadow: 0 8px 24px rgba(40,167,69,0.18);
      transform: scale(1.06);
    }

    /* Job List */
    .job-list {
      flex: 1;
      overflow-y: auto;
      padding: 1.5rem;
      background: #f9fafc;
    }
    .job-card {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: white;
      border-radius: 14px;
      padding: 1rem 1.2rem;
      margin-bottom: 1rem;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      transition: 0.3s cubic-bezier(.4,2,.6,1);
      position: relative;
      overflow: hidden;
      animation: cardFadeIn 0.7s cubic-bezier(.4,2,.6,1);
    }
    .job-card::before {
      content: "";
      position: absolute;
      left: 0; top: 0; width: 100%; height: 100%;
      background: linear-gradient(90deg, rgba(40,167,69,0.08) 0%, rgba(67,233,123,0.08) 100%);
      opacity: 0.7;
      pointer-events: none;
      z-index: 0;
      border-radius: 14px;
    }
    .job-card:hover {
      transform: translateY(-6px) scale(1.03);
      box-shadow: 0 12px 32px rgba(40,167,69,0.13);
      border: 1.5px solid #28a74522;
    }
    @keyframes cardFadeIn {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .job-details {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .job-card img {
      width: 50px;
      height: 50px;
      border-radius: 50%;
    }
    .job-info p { margin: 2px 0; font-size: 0.9rem; }
    .job-info strong { font-size: 1rem; color: #222; }
    .skills { color: #666; font-style: italic; font-size: 0.85rem; }

    /* Active Status Indicator */
    .status-container {
      display: flex;
      align-items: center;
      gap: 6px;
      margin-top: 4px;
    }
    .status-indicator {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      display: inline-block;
      position: relative;
    }
    .status-indicator.online {
      background: #28a745;
      box-shadow: 0 0 8px rgba(40, 167, 69, 0.6);
      animation: pulse 2s infinite;
    }
    .status-indicator.offline {
      background: #6c757d;
    }
    @keyframes pulse {
      0%, 100% {
        box-shadow: 0 0 8px rgba(40, 167, 69, 0.6);
      }
      50% {
        box-shadow: 0 0 15px rgba(40, 167, 69, 0.9);
      }
    }
    .status-text {
      font-size: 0.8rem;
      color: #666;
      font-weight: 500;
    }
    .status-text.online {
      color: #28a745;
    }

    /* Chatroom */
    .chatroom {
      display: none;
      flex-direction: column;
      height: 100%;
      background: #f5f5f5;
    }
    .chat-header {
      background: #ffffff;
      color: #222;
      padding: 1rem 1.5rem;
      font-weight: bold;
      display: flex;
      align-items: center;
      gap: 12px;
      border-bottom: 1px solid #e0e0e0;
    }
    .back-arrow {
      background: white;
      border: none;
      padding: 6px 12px;
      border-radius: 50%;
      font-size: 1rem;
      color: #28a745;
      cursor: pointer;
      box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    }

    /* Chat Box - Maximized */
    .chat-box {
      flex: 1;
      padding: 1.5rem;
      padding-left: 1.5rem;
      overflow-y: auto;
      overflow-x: hidden;
      display: flex;
      flex-direction: column;
      gap: 8px;
      scroll-behavior: smooth;
      background: #ffffff;
      min-height: 0;
    }
    .message {
      max-width: 70%;
      padding: 12px 16px;
      border-radius: 18px;
      font-size: 0.9rem;
      line-height: 1.4;
      word-wrap: break-word;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      position: relative;
      cursor: pointer;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .message:hover {
      transform: scale(1.02);
      box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }
    
    .message.selected {
      box-shadow: 0 0 0 2px #28a745;
    }
    
    .me {
      align-self: flex-end;
      background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
      color: white;
      border-radius: 18px 18px 4px 18px;
      margin-left: 80px;
    }
    .other {
      align-self: flex-start;
      background: #f0f0f0;
      color: #222;
      border: 1px solid #e0e0e0;
      border-radius: 18px 18px 18px 4px;
      margin-right: 80px;
    }
    
    /* Unsend Button */
    .unsend-btn {
      position: absolute;
      left: -70px;
      top: 50%;
      transform: translateY(-50%);
      background: transparent;
      color: #dc3545; /* keep red color to indicate destructive action */
      border: none;
      border-radius: 0;
      padding: 0; /* text-only */
      font-size: 0.9rem;
      font-weight: 700;
      cursor: pointer;
      box-shadow: none;
      transition: color 0.15s, transform 0.12s;
      white-space: nowrap;
      z-index: 100;
      display: none;
    }
    
    .unsend-btn:hover {
      color: #a71d25;
      transform: translateY(-50%) scale(1.02);
    }
    
    .message.selected .unsend-btn {
      display: block;
      animation: slideInLeft 0.3s ease-out;
    }
    
    @keyframes slideInLeft {
      from {
        opacity: 0;
        left: -30px;
      }
      to {
        opacity: 1;
        left: -70px;
      }
    }
    
    /* Add margin to chat box to accommodate unsend button */
    .chat-box {
      padding-left: 80px;
      padding-right: 1.5rem;
    }
    
    /* Image Modal */
    .image-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.95);
      z-index: 9999;
      justify-content: center;
      align-items: center;
      animation: fadeIn 0.3s ease-out;
    }
    
    .image-modal.active {
      display: flex;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    
    .image-modal-content {
      position: relative;
      max-width: 85%;
      max-height: 80%;
      display: flex;
      flex-direction: column;
      animation: zoomIn 0.3s ease-out;
      padding-top: 60px;
    }
    
    @keyframes zoomIn {
      from {
        transform: scale(0.8);
        opacity: 0;
      }
      to {
        transform: scale(1);
        opacity: 1;
      }
    }
    
    .image-modal-content img {
      max-width: 100%;
      max-height: 75vh;
      border-radius: 12px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
      object-fit: contain;
    }
    
    .image-modal-close {
      position: absolute;
      top: 0;
      right: 0;
      background: #dc3545;
      color: white;
      border: none;
      border-radius: 50%;
      width: 44px;
      height: 44px;
      font-size: 26px;
      font-weight: bold;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s;
      box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
    }
    
    .image-modal-close:hover {
      background: #c82333;
      transform: rotate(90deg) scale(1.1);
    }
    
    .image-modal-download {
      position: absolute;
      top: 0;
      right: 54px;
      background: #28a745;
      color: white;
      border: none;
      border-radius: 8px;
      padding: 10px 18px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
      box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
      height: 44px;
    }
    
    .image-modal-download:hover {
      background: #218838;
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(40, 167, 69, 0.5);
    }
    
    .image-modal-download svg {
      width: 16px;
      height: 16px;
    }
    
    /* Unsend Confirmation Modal */
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
      animation: fadeIn 0.2s ease-out;
    }
    
    .unsend-modal.active {
      display: flex;
    }
    
    .unsend-modal-content {
      background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
      border-radius: 20px;
      padding: 2rem 1.8rem;
      max-width: 400px;
      width: 90%;
      text-align: center;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      animation: zoomIn 0.3s ease-out;
    }
    
    .unsend-modal-icon {
      width: 70px;
      height: 70px;
      background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
      box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
    }
    
    .unsend-modal h3 {
      color: #1a1a1a;
      margin-bottom: 0.8rem;
      font-size: 1.5rem;
      font-weight: 700;
    }
    
    .unsend-modal p {
      color: #6c757d;
      margin-bottom: 1.8rem;
      font-size: 0.95rem;
      line-height: 1.5;
    }
    
    .unsend-modal-buttons {
      display: flex;
      gap: 12px;
      justify-content: center;
    }
    
    .unsend-modal-btn {
      padding: 12px 28px;
      border: none;
      border-radius: 25px;
      font-weight: 600;
      font-size: 0.95rem;
      cursor: pointer;
      transition: all 0.2s;
      letter-spacing: 0.3px;
    }
    
    .unsend-modal-btn-confirm {
      background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
      color: white;
      box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
    }
    
    .unsend-modal-btn-confirm:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(220, 53, 69, 0.4);
    }
    
    .unsend-modal-btn-cancel {
      background: #e9ecef;
      color: #495057;
    }
    
    .unsend-modal-btn-cancel:hover {
      background: #dee2e6;
    }

    /* Input Area - Modern MessageBox Design */
    .input-area {
      display: flex;
      padding: 15px;
      background: #ffffff;
      border-top: 1px solid #e0e0e0;
      gap: 10px;
      align-items: center;
      justify-content: center;
    }
    
    .messageBox {
      width: 100%;
      max-width: 800px;
      height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: #f5f5f5;
      padding: 0 15px;
      border-radius: 10px;
      border: 1px solid #d0d0d0;
      transition: border 0.3s;
    }
    
    .messageBox:focus-within {
      border: 1px solid #28a745;
    }
    
    .fileUploadWrapper {
      width: fit-content;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: Arial, Helvetica, sans-serif;
    }
    
    #imageInput {
      display: none;
    }
    
    .fileUploadWrapper label {
      cursor: pointer;
      width: fit-content;
      height: fit-content;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }
    
    .fileUploadWrapper label svg {
      height: 20px;
      width: 20px;
    }
    
    .fileUploadWrapper label svg path {
      transition: all 0.3s;
    }
    
    .fileUploadWrapper label svg circle {
      transition: all 0.3s;
    }
    
    .fileUploadWrapper label:hover svg path {
      stroke: #28a745;
    }
    
    .fileUploadWrapper label:hover svg circle {
      stroke: #28a745;
      fill: #e8e8e8;
    }
    
    .fileUploadWrapper label:hover .tooltip {
      display: block;
      opacity: 1;
    }
    
    .tooltip {
      position: absolute;
      top: -40px;
      display: none;
      opacity: 0;
      color: white;
      font-size: 10px;
      text-wrap: nowrap;
      background-color: #333;
      padding: 6px 10px;
      border: 1px solid #555;
      border-radius: 5px;
      box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.3);
      transition: all 0.3s;
    }
    
    #messageInput {
      flex: 1;
      height: 100%;
      background-color: transparent;
      outline: none;
      border: none;
      padding-left: 10px;
      color: #222;
      font-size: 0.95rem;
    }
    
    #messageInput::placeholder {
      color: #999;
    }
    
    #messageInput:focus ~ #sendButton svg path,
    #messageInput:valid ~ #sendButton svg path {
      fill: #28a745;
      stroke: white;
    }
    
    #sendButton {
      width: fit-content;
      height: 100%;
      background-color: transparent;
      outline: none;
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s;
      padding: 0 8px;
    }
    
    #sendButton svg {
      height: 22px;
      width: 22px;
      transition: all 0.3s;
    }
    
    #sendButton svg path {
      transition: all 0.3s;
      stroke: #888;
      fill: transparent;
    }
    
    #sendButton:hover svg path {
      fill: #28a745;
      stroke: white;
    }
    
    #imagePreview {
      display: none;
      align-items: center;
      margin: 0 8px;
    }
    
    #imagePreview img {
      max-width: 40px;
      max-height: 40px;
      border-radius: 6px;
      object-fit: cover;
    }
    
    /* Image Preview Container Above Input */
    #imagePreviewContainer {
      display: none;
      padding: 12px 15px;
      background: #f8f9fa;
      border-bottom: 1px solid #e0e0e0;
      justify-content: flex-start;
      animation: slideDown 0.3s ease-out;
    }
    
    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    #imagePreviewWrapper {
      position: relative;
      display: inline-block;
    }
    
    #imagePreviewContainer img {
      max-width: 120px;
      max-height: 120px;
      border-radius: 8px;
      object-fit: cover;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      display: block;
    }
    
    #cancelImageBtn {
      position: absolute;
      top: -8px;
      right: -8px;
      background: #dc3545;
      color: white;
      border: none;
      border-radius: 50%;
      width: 28px;
      height: 28px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 18px;
      font-weight: bold;
      transition: all 0.2s;
      box-shadow: 0 2px 8px rgba(220,53,69,0.4);
      line-height: 1;
      padding: 0;
      z-index: 10;
    }
    
    #cancelImageBtn:hover {
      background: #c82333;
      transform: scale(1.15);
      box-shadow: 0 3px 12px rgba(220,53,69,0.5);
    }

    /* Unread badge */
    .unread-badge {
      background: red;
      color: white;
      font-size: 0.7rem;
      font-weight: bold;
      border-radius: 50%;
      padding: 4px 8px;
      min-width: 18px;
      text-align: center;
    }

    /* Mobile */
    @media(max-width: 768px) {
      .sidebar { display: none; }
      .main-content { border-radius: 0; }
      .search-box input { width: 140px; }
      
      /* Profile modal mobile adjustments */
      #profileCard {
        min-width: 90vw !important;
        max-width: 90vw !important;
        padding: 2rem 1.5rem 1.5rem 1.5rem !important;
        margin: 1rem;
      }
      
      #modalProfileImg {
        width: 100px !important;
        height: 100px !important;
      }
      
      #modalProfileName {
        font-size: 1.5em !important;
      }
      
      #modalProfileComments {
        max-height: 100px !important;
      }
      
      /* Maximize chat space on mobile */
      .chat-header {
        padding: 0.7rem 0.9rem !important;
        min-height: 56px !important;
        gap: 6px !important;
        flex-wrap: nowrap !important;
        background: #ffffff !important;
        border-bottom: 1px solid #e0e0e0 !important;
      }
      
      .chat-header > div:first-child {
        gap: 6px !important;
      }
      
      .chat-header img {
        width: 34px !important;
        height: 34px !important;
      }
      
      #chatUser {
        font-size: 0.85rem !important;
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        min-width: 0;
      }
      
      #mobileBackBtn span {
        display: none !important;
      }
      
      #mobileBackBtn svg {
        width: 22px !important;
        height: 22px !important;
      }
      
      /* Make avatar smaller on mobile */
      .chat-header .chat-header img,
      .chat-header > div img {
        width: 32px !important;
        height: 32px !important;
      }
      
      /* Optimize chat box spacing */
      .chat-box {
        padding: 0.6rem 0.8rem !important;
        padding-bottom: 80px !important;
        padding-left: 0.8rem !important;
        gap: 4px !important;
      }
      
      /* Make messages use more width */
      .message {
        max-width: 85% !important;
        padding: 10px 14px !important;
        font-size: 0.95rem !important;
        margin: 4px 0 !important;
        line-height: 1.45 !important;
      }
      
      .message.me {
        margin-left: 60px !important;
      }
      
      .message.other {
        margin-right: 60px !important;
      }
      
      /* Unsend button on mobile - position on left side of message */
      .unsend-btn {
        left: -60px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        font-size: 0.9rem !important;
        padding: 0 !important;
      }
      
      .unsend-btn:hover {
        transform: translateY(-50%) scale(1.05) !important;
      }
      
      @keyframes slideInLeft {
        from {
          opacity: 0;
          left: -30px;
        }
        to {
          opacity: 1;
          left: -60px;
        }
      }
      
      .message p {
        margin: 0 !important;
        word-break: break-word !important;
      }
      
      .message img {
        max-width: 200px !important;
        margin-top: 4px !important;
      }
      
      /* Timestamp styling on mobile */
      .message > div[style*="fontSize"] {
        font-size: 0.75rem !important;
        margin-top: 3px !important;
      }
    }

    /* Mobile chat behavior: when body has .mobile-chat-active hide the left list and show chat full-width */
    body.mobile-chat-active .left-sidebar { display: none !important; }
    body.mobile-chat-active #chatProfileSidebar { display: none !important; }
    body.mobile-chat-active .right-content { flex: 1 1 100% !important; width: 100vw !important; }

    /* Mobile back button in chat header - hidden by default */
    #mobileBackBtn { 
      display: none; 
      background: transparent; 
      border: none; 
      color: #28a745; 
      font-weight:700; 
      cursor:pointer; 
      align-items:center; 
      gap:4px;
      padding: 4px 8px;
      border-radius: 6px;
      transition: background 0.2s;
    }
    #mobileBackBtn:active {
      background: rgba(40,167,69,0.1);
    }
    @media(max-width: 900px) {
      /* Image modal mobile styles */
      .image-modal-content {
        max-width: 90% !important;
        max-height: 75% !important;
        padding-top: 70px !important;
      }
      
      .image-modal-content img {
        max-height: 65vh !important;
      }
      
      .image-modal-close {
        top: 10px !important;
        right: 10px !important;
        width: 40px !important;
        height: 40px !important;
        font-size: 22px !important;
      }
      
      .image-modal-download {
        top: 10px !important;
        right: 60px !important;
        padding: 8px 14px !important;
        font-size: 13px !important;
        height: 40px !important;
      }
      
      /* show a compact back button inside the chat header when mobile-chat-active is set */
      body.mobile-chat-active #mobileBackBtn { display: inline-flex; }
      /* make chat header fixed to top for easier navigation on small devices */
      .chat-header { 
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        z-index: 1200 !important;
      }
      
      /* Adjust chat box for fixed header */
      body.mobile-chat-active .chat-box {
        margin-top: 56px !important;
        height: calc(100vh - 56px) !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch !important;
      }
      
      /* Ensure right-content takes full height */
      body.mobile-chat-active .right-content {
        height: 100vh !important;
        max-height: 100vh !important;
      }
      
      .left-sidebar{
        width: 100% !important;
        max-width: 100% !important;
        height: auto !important;
        min-height: 0 !important;
        position: relative !important;
      }
     .jobfinder-hero h1 {
  font-size: 1.2rem;
  text-align: center;
  position: relative;
  display: inline-block;
  padding: 8px 16px;
  color: #222;
  border: 2px solid transparent;
  border-radius: 6px;
  animation: borderGlow 2s linear infinite;
}

/* Center horizontally */
.jobfinder-hero {
  text-align: center;
  padding: 0.5rem 0;
}

/* Green glowing border animation */
@keyframes borderGlow {
  0% {
    border-color: #28a745;
    box-shadow: 0 0 5px #28a745;
  }
  50% {
    border-color: #00ff7f;
    box-shadow: 0 0 15px #00ff7f;
  }
  100% {
    border-color: #28a745;
    box-shadow: 0 0 5px #28a745;
  }
}

    }

    /* Tabs */
    .tab-btn {
      flex:1;
      padding:0.6rem 0;
      background:none;
      border:none;
      font-weight:600;
      font-size:0.95em;
      color:#28a745;
      border-bottom:2px solid transparent;
      cursor:pointer;
      transition:0.2s;
    }
    .tab-btn.active {
      background:#fff;
      border-bottom:2.5px solid #28a745;
      color:#1f7c32;
    }
    .sidebar-card {
      display:flex;
      align-items:center;
      gap:10px;
      padding:12px 18px;
      border-bottom:1px solid #f0f0f0;
      cursor:pointer;
      transition:background 0.2s;
    }
    .sidebar-card:hover {
      background:#eafbe7;
    }
    .resident-card .chat-btn {
      margin-left:auto;
    }
    .unread-badge {
      background:red;
      color:white;
      font-size:0.8em;
      border-radius:50%;
      padding:3px 8px;
      min-width:18px;
      text-align:center;
    }
    .message.me {
      align-self:flex-end;
      background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
      color: white;
      border-radius:18px 18px 4px 18px;
      margin:4px 0;
      padding:10px 16px;
      max-width:70%;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      display: flex;
      align-items: flex-end;
      gap: 10px;
      flex-direction: row-reverse;
    }
    .message.me .profile-img {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      object-fit: cover;
      flex-shrink: 0;
      border: 2px solid rgba(255, 255, 255, 0.3);
    }
    .message.me .message-content {
      flex: 1;
      min-width: 0;
    }
    .message.other {
      align-self:flex-start;
      background:#f0f0f0;
      color: #222;
      border:1px solid #e0e0e0;
      border-radius:18px 18px 18px 4px;
      margin:4px 0;
      padding:10px 16px;
      max-width:70%;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      display: flex;
      align-items: flex-end;
      gap: 10px;
    }
    .message.other .profile-img {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      object-fit: cover;
      flex-shrink: 0;
      border: 2px solid #e0e0e0;
    }
    .message.other .message-content {
      flex: 1;
      min-width: 0;
    }
    .chat-box {
      display:flex;
      flex-direction:column;
      gap:6px;
    }
    .back-btn {
      display: flex;
      align-items: center;
      gap: 6px;
      margin: 18px 18px 10px 18px;
      padding: 10px 18px;
      background: #eafbe7;
      color: #28a745;
      font-weight: 600;
      font-size: 1.05em;
      border-radius: 25px;
      text-decoration: none;
      box-shadow: 0 2px 8px rgba(40,167,69,0.07);
      transition: background 0.18s, color 0.18s;
    }
    .back-btn:hover {
      background: #28a745;
      color: #fff;
    }
    .back-btn svg path {
      transition: stroke 0.18s;
    }
    .back-btn:hover svg path {
      stroke: #fff;
    }

 /* Chat Profile Sidebar */
/* Chat Profile Sidebar */
#chatProfileSidebar {
  width: 320px;
  background: #ffffffff;
  border: 1px solid #ddd;   /* <-- added border */
  border-right: 1px solid #e0e0e0; /* keep right border consistent */
  border-radius: 12px;      /* rounded corners */
  box-shadow: 0 4px 12px rgba(0,0,0,0.08); /* soft shadow */
  display: none;
  flex-direction: column;
  align-items: center;
  justify-content: center; /* centers vertically */
  padding: 2.5rem 1.5rem;
  height: 100vh; /* make it full height */
}



    #chatProfileSidebar img {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      margin-bottom: 18px;
      object-fit: cover;
      background: #f3f3f3;
    }
    #chatProfileName {
      font-size: 1.3em;
      font-weight: 700;
      color: #222;
      margin-bottom: 4px;
      text-align: center;
    }
    #chatProfileId {
      color: #28a745;
      font-weight: 600;
      margin-bottom: 6px;
      font-size: 1em;
      text-align: center;
    }
    #chatProfileAge {
      font-size: 1em;
      color: #444;
      margin-bottom: 8px;
      text-align: center;
    }
    #chatProfileSkills {
      font-size: 0.98em;
      color: #666;
      font-style: italic;
      margin-bottom: 10px;
      text-align: center;
    }
    #chatProfileSkillDesc {
      font-size: 0.98em;
      color: #333;
      margin-bottom: 18px;
      text-align: center;
      background: #f7fafd;
      border-radius: 10px;
      padding: 10px 12px;
      width: 100%;
    }

    /* Animations */
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    @keyframes slideOut {
      from {
        opacity: 1;
        transform: translateY(0);
      }
      to {
        opacity: 0;
        transform: translateY(10px);
      }
    }

    /* Modern Toast Notification */
    .toast-notification {
      position: fixed;
      top: 20px;
      right: 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 20px;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.15);
      z-index: 9999;
      opacity: 0;
      transform: translateX(400px);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      min-width: 280px;
      max-width: 400px;
      font-weight: 500;
      font-size: 0.95rem;
    }
    
    .toast-notification.toast-show {
      opacity: 1;
      transform: translateX(0);
    }
    
    .toast-success {
      background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
      color: #fff;
    }
    
    .toast-error {
      background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
      color: #fff;
    }
    
    .toast-warning {
      background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
      color: #fff;
    }
    
    .toast-info {
      background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
      color: #fff;
    }
    
    .toast-icon {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 28px;
      height: 28px;
      background: rgba(255,255,255,0.2);
      border-radius: 50%;
      flex-shrink: 0;
    }
    
    .toast-message {
      flex: 1;
      line-height: 1.4;
    }
    
    @media (max-width: 768px) {
      .toast-notification {
        top: 10px;
        right: 10px;
        left: 10px;
        min-width: auto;
        max-width: none;
        font-size: 0.9rem;
        padding: 12px 16px;
      }
    }
  </style>
  <!-- Strong mobile overrides for input area to override inline styles -->
  <style>
    @media (max-width: 900px) {
      /* Force the input-area to be compact and sticky */
      .input-area {
        display: flex !important;
        padding: 10px !important;
        position: fixed !important;
        bottom: 0 !important;
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        z-index: 1600 !important;
        background: #ffffff !important;
        border-top: 1px solid #e0e0e0 !important;
        box-shadow: 0 -3px 10px rgba(0,0,0,0.1) !important;
      }

      .messageBox {
        height: 45px !important;
        padding: 0 12px !important;
      }

      #messageInput { 
        font-size: 0.9rem !important; 
      }
      
      #sendButton svg { 
        height: 20px !important; 
        width: 20px !important; 
      }
      
      .fileUploadWrapper label svg { 
        width: 18px !important; 
        height: 18px !important; 
      }
      
      #imagePreview img { 
        max-width: 35px !important; 
        max-height: 35px !important; 
      }
      
      #imagePreviewContainer {
        padding: 10px !important;
        position: fixed !important;
        bottom: 52px !important;
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        z-index: 1500 !important;
        background: #f8f9fa !important;
        border-top: 1px solid #e0e0e0 !important;
        border-bottom: 1px solid #e0e0e0 !important;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1) !important;
      }
      
      #imagePreviewContainer img {
        max-width: 100px !important;
        max-height: 100px !important;
      }
      
      #cancelImageBtn {
        width: 26px !important;
        height: 26px !important;
        font-size: 16px !important;
        top: -6px !important;
        right: -6px !important;
      }

      /* Mobile Done Chat button in header */
      #doneChatBtn {
        padding: 6px 14px !important;
        font-size: 0.85em !important;
        border-radius: 20px !important;
      }

      /* keep chat messages visible above input and image preview */
      .chat-box { 
        padding-bottom: 70px !important;
        overflow-x: hidden !important;
      }
      
      /* Add extra padding when image preview is visible */
      body.mobile-chat-active .chat-box {
        padding-bottom: 180px !important;
      }
      /* hide the chat area on small screens until a chat is opened (mobile-chat-active toggled by JS) */
      .right-content { display: none !important; }
      body.mobile-chat-active .right-content { display: flex !important; width: 100% !important; }
    }
  </style>
  
  <!-- Responsive styles for How to Use Modal -->
  <style>
    /* Desktop styles */
    .how-to-modal-content {
      scrollbar-width: thin;
      scrollbar-color: #28a745 #f1f1f1;
    }
    
    .how-to-modal-content::-webkit-scrollbar {
      width: 8px;
    }
    
    .how-to-modal-content::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }
    
    .how-to-modal-content::-webkit-scrollbar-thumb {
      background: #28a745;
      border-radius: 10px;
    }
    
    .how-to-modal-content::-webkit-scrollbar-thumb:hover {
      background: #1f7c32;
    }
    
    /* Mobile responsive styles */
    @media (max-width: 768px) {
      #howToUseModal {
        padding: 10px !important;
        align-items: flex-start !important;
      }
      
      .how-to-modal-content {
        padding: 1.5rem 1rem !important;
        border-radius: 16px !important;
        max-width: 100% !important;
        max-height: 95vh !important;
        margin-top: 10px !important;
      }
      
      .how-to-modal-content h2 {
        font-size: 1.3em !important;
      }
      
      .how-to-modal-content p {
        font-size: 0.88em !important;
      }
      
      .how-to-modal-content ol {
        font-size: 0.8em !important;
        padding-left: 18px !important;
      }
      
      .how-to-modal-content ol li {
        margin-bottom: 8px !important;
      }
      
      /* Smaller icon on mobile */
      .how-to-modal-content > div:first-of-type {
        width: 48px !important;
        height: 48px !important;
        margin-bottom: 0.8rem !important;
      }
      
      .how-to-modal-content > div:first-of-type svg {
        width: 24px !important;
        height: 24px !important;
      }
      
      /* Adjust numbered steps on mobile */
      .how-to-modal-content > div > div > div[style*="min-width: 28px"] {
        min-width: 24px !important;
        height: 24px !important;
        font-size: 0.8em !important;
      }
      
      /* Verification badge on mobile */
      .how-to-modal-content > div > div > div[style*="width: 24px"] {
        width: 22px !important;
        height: 22px !important;
        font-size: 0.85em !important;
      }
    }
    
    @media (max-width: 480px) {
      .how-to-modal-content {
        padding: 1.2rem 0.9rem !important;
        border-radius: 12px !important;
      }
      
      .how-to-modal-content h2 {
        font-size: 1.15em !important;
      }
      
      .how-to-modal-content p {
        font-size: 0.85em !important;
      }
      
      .how-to-modal-content ol {
        font-size: 0.78em !important;
      }
    }
    
    /* Landscape mobile */
    @media (max-width: 900px) and (max-height: 500px) {
      #howToUseModal {
        align-items: flex-start !important;
      }
      
      .how-to-modal-content {
        max-height: 95vh !important;
        margin-top: 5px !important;
        margin-bottom: 5px !important;
      }
    }
  </style>
</head>
<body>

  <!-- Jobfinder Blocked Modal -->
  <?php if ($user_is_blocked): ?>
  <div id="jobfinderBlockedModal" style="
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    backdrop-filter: blur(5px);
  ">
    <div style="
      background: white;
      border-radius: 20px;
      padding: 3rem 2.5rem;
      max-width: 480px;
      width: 90%;
      box-shadow: 0 25px 70px rgba(0, 0, 0, 0.4);
      text-align: center;
      animation: modalBounceIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    ">
      <!-- Icon -->
      <div style="
        width: 90px;
        height: 90px;
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.8rem;
        box-shadow: 0 10px 25px rgba(220, 53, 69, 0.4);
        animation: iconPulse 2s infinite;
      ">
        <svg width="45" height="45" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="15" y1="9" x2="9" y2="15"></line>
          <line x1="9" y1="9" x2="15" y2="15"></line>
        </svg>
      </div>
      
      <!-- Title -->
      <h2 style="
        font-size: 1.75rem;
        font-weight: 700;
        color: #dc3545;
        margin-bottom: 1rem;
        font-family: 'Inter', sans-serif;
      ">Access Denied</h2>
      
      <!-- Message -->
      <p style="
        font-size: 1.05rem;
        color: #555;
        line-height: 1.7;
        margin-bottom: 2.5rem;
        font-family: 'Inter', sans-serif;
      ">You have been blocked from using the Jobfinder feature. Please contact the barangay administrator for more information.</p>
      
      <!-- Close Button -->
      <button onclick="sessionStorage.setItem('internalNav', 'true'); window.location.href='index.php'" style="
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        border: none;
        padding: 14px 40px;
        border-radius: 10px;
        font-size: 1.05rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(220, 53, 69, 0.35);
        font-family: 'Inter', sans-serif;
      " onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 20px rgba(220, 53, 69, 0.45)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 5px 15px rgba(220, 53, 69, 0.35)';">
        Return to Home
      </button>
    </div>
  </div>

  <style>
  @keyframes modalBounceIn {
    0% {
      opacity: 0;
      transform: scale(0.3) translateY(-50px);
    }
    50% {
      transform: scale(1.05);
    }
    70% {
      transform: scale(0.95);
    }
    100% {
      opacity: 1;
      transform: scale(1) translateY(0);
    }
  }
  
  @keyframes iconPulse {
    0%, 100% {
      transform: scale(1);
      box-shadow: 0 10px 25px rgba(220, 53, 69, 0.4);
    }
    50% {
      transform: scale(1.05);
      box-shadow: 0 15px 35px rgba(220, 53, 69, 0.6);
    }
  }
  </style>
  <?php endif; ?>

  <div class="main-container" style="display:flex; height:100vh; width:100vw; <?php echo $user_is_blocked ? 'filter: blur(8px); pointer-events: none;' : ''; ?>">
    
    <!-- Sidebar with tabs -->
    <div class="left-sidebar" style="width:320px; background:#f7fafd; border-right:1px solid #e0e0e0; display:flex; flex-direction:column;">
      <!-- Back button -->
         <!-- Modern Hero Header -->
 <div class="jobfinder-hero">
  <div class="jobfinder-header">
    <a href="index.php" class="back-btn" onclick="sessionStorage.setItem('internalNav', 'true');">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
        <path d="M15 19l-7-7 7-7" stroke="#28a745" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <span>Back</span>
    </a>

    <button class="how-btn" title="How to Use" onclick="showHowToUseCard()">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
        <circle cx="12" cy="12" r="10" stroke="#28a745" stroke-width="2"/>
        <path d="M12 8v2m0 4h.01" stroke="#28a745" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </button>
  </div>
  
  <h1>Barangay JobFinder</h1>
</div>

<style>
.jobfinder-hero {
  text-align: center;
  margin-bottom: 20px;
}

.jobfinder-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  max-width: 600px;
  margin: 0 auto;
  position: relative;
}

.jobfinder-hero h1 {
  text-align: center;
  font-size: 1.4rem;
  color: #28a745;
  margin-top: 15px;
  margin-bottom: 0;
}

.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #28a745;
  text-decoration: none;
  font-weight: 500;
  border: 1px solid #28a745;
  border-radius: 20px;
  padding: 6px 10px;
  transition: all 0.3s ease;
  background: #fff;
}

.back-btn:hover {
  background: #28a745;
  color: #fff;
}

.how-btn {
  background: #fff;
  color: #28a745;
  border: 1px solid #28a745;
  border-radius: 50%;
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
}

.how-btn:hover {
  background: #28a745;
  color: #fff;
}

@keyframes borderPulse {
  0% { text-shadow: 0 0 5px #28a745; }
  50% { text-shadow: 0 0 15px #28a745; }
  100% { text-shadow: 0 0 5px #28a745; }
}

@media (max-width: 480px) {
  .jobfinder-hero h1 {
    font-size: 1rem;
  }
  .back-btn span {
    display: none;
  }
  .back-btn {
    padding: 4px 8px !important;
  }
  .how-btn {
    width: 32px !important;
    height: 32px !important;
  }
  .how-btn svg {
    width: 16px !important;
    height: 16px !important;
  }
}
</style>

      <!-- Tabs -->
      <div style="display:flex; border-bottom:1px solid #e0e0e0;">
        <button id="tabResidents" class="tab-btn active" onclick="showTab('residents')">Residents</button>
        <button id="tabChats" class="tab-btn" onclick="showTab('chats')" style="position:relative;">
          Chats
          <span id="chatsBadge" style="display:none;position:absolute;top:8px;right:18px;background:red;color:white;font-size:0.8em;font-weight:bold;border-radius:50%;padding:2px 7px;min-width:18px;text-align:center;"></span>
        </button>
      </div>
      <!-- Search -->
      <div id="residentsSearchBox" style="padding:1rem; border-bottom:1px solid #e0e0e0;">
        <input type="text" id="searchInput" placeholder="Search skills or name" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ccc;">
      </div>
      <!-- Residents List -->
      <div id="residentsList" class="sidebar-list" style="flex:1; overflow-y:auto;">
        <?php if ($residents->num_rows > 0): ?>
          <?php while ($r = $residents->fetch_assoc()): ?>
  <?php
    $img = (!empty($r['profile_image']))
      ? htmlspecialchars($r['profile_image'])
      : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
    $fullName = htmlspecialchars($r['surname'] . ', ' . $r['first_name']);
    $skills = htmlspecialchars($r['occupation_skills']);
    $skillDesc = htmlspecialchars($r['skill_description']);
    $age = htmlspecialchars($r['age']);
    $userid = htmlspecialchars($r['unique_id']);
    $isVerified = (int)$r['jobfinder_verified'];
    $isOnline = (int)$r['is_online'];
    $lastActive = $r['last_active'];
    $statusText = $isOnline ? 'Active now' : timeAgo($lastActive);
  ?>
  <div class="sidebar-card resident-card" data-name="<?= strtolower($r['surname'] . ' ' . $r['first_name']) ?>" data-skills="<?= strtolower($r['occupation_skills']) ?>">
    <div style="position:relative; display:inline-block;">
      <img src="<?= $img ?>" alt="Avatar" style="width:44px; height:44px; border-radius:50%;">
      <?php if ($isVerified): ?>
        <span title="Verified by Admin" style="position:absolute; bottom:-2px; right:-2px; display:flex; align-items:center; justify-content:center; background:#10b981; color:#fff; border-radius:50%; width:16px; height:16px; font-size:10px; font-weight:bold; border:2px solid #fff; box-shadow:0 2px 4px rgba(16,185,129,0.3);">✓</span>
      <?php endif; ?>
    </div>
    <div style="flex:1; margin-left:10px;">
      <div style="font-weight:600; display:flex; align-items:center; gap:8px;">
        <?= $fullName ?>
      </div>
      <div style="font-size:0.9em; color:#888;">
        <span><?= $skills ?></span>
      </div>
      <?php if ($r['avg_rating'] > 0): ?>
        <div style="display:flex; align-items:center; gap:4px; margin-top:2px;">
          <span title="Average rating" style="color:#FFD700; font-size:0.95em; display:inline-flex; align-items:center;">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <?php if ($i <= round($r['avg_rating'])): ?>&#9733;<?php else: ?>&#9734;<?php endif; ?>
            <?php endfor; ?>
            <span style="color:#888; font-size:0.88em; margin-left:2px;">(<?= number_format($r['avg_rating'],1) ?>)</span>
          </span>
        </div>
      <?php endif; ?>
      <!-- Active Status -->
      <div class="status-container">
        <span class="status-indicator <?= $isOnline ? 'online' : 'offline' ?>"></span>
        <span class="status-text <?= $isOnline ? 'online' : '' ?>"><?= $statusText ?></span>
      </div>
    </div>
    <button class="chat-btn" style="padding:6px 18px; font-size:0.95em; background:linear-gradient(90deg,#43e97b 0%,#38f9d7 100%); box-shadow:0 2px 8px rgba(67,233,123,0.13); border-radius:22px; font-weight:600; letter-spacing:0.2px; transition:background 0.18s, box-shadow 0.18s;" onclick="showProfileCard('<?= $userid ?>','<?= $fullName ?>','<?= $img ?>','<?= $age ?>','<?= $skills ?>','<?= $skillDesc ?>',<?= number_format($r['avg_rating'],1) ?>,<?= (int)$r['rating_count'] ?>,<?= $isVerified ?>)">View</button>
  </div>
<?php endwhile; ?>
        <?php else: ?>
          <p style="padding:1rem;">No available residents with skills right now.</p>
        <?php endif; ?>
      </div>
      <!-- Chats List -->
      <div id="chatsList" class="sidebar-list" style="flex:1; overflow-y:auto; display:none;"></div>
    </div>

    <!-- Chat area -->
    <div class="right-content" style="flex:1; display:flex; flex-direction:column; background:#ffffff;">
      <!-- Chat header -->
      <div class="chat-header" style="background:#ffffff; border-bottom:1px solid #e0e0e0; padding:1rem 1.5rem; display:flex; align-items:center; gap:10px; justify-content:space-between;">
        <div style="display:flex; align-items:center; gap:10px; flex:1; min-width:0;">
          <button id="mobileBackBtn" aria-label="Back to list" onclick="exitChatView()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" style="vertical-align:middle;">
              <path d="M15 19l-7-7 7-7" stroke="#28a745" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span style="font-size:0.9em;">Back</span>
          </button>
          <div style="position:relative; display:inline-block;">
            <img id="chatAvatar" src="chat.png" alt="Avatar" style="width:40px; height:40px; border-radius:50%; display:none;">
            <div id="chatAvatarVerifiedBadge" style="position:absolute; bottom:-2px; right:-2px; background:#10b981; color:#fff; border-radius:50%; width:16px; height:16px; display:none; align-items:center; justify-content:center; font-size:10px; font-weight:bold; border:2px solid #fff; box-shadow:0 2px 4px rgba(16,185,129,0.3);">✓</div>
          </div>
          <div style="display:flex; flex-direction:column; gap:2px; flex:1; min-width:0;">
            <span id="chatUser" style="font-weight:600; color:#28a745; font-size:1rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">Select a conversation</span>
            <div id="chatHeaderStatus" style="display:none; align-items:center; gap:6px;"></div>
          </div>
        </div>
        <!-- Report and Done Chat Buttons in Header -->
        <div style="display: flex; gap: 8px; align-items: center;">
          <button id="reportUserBtn" onclick="showReportModal()" style="
            background: linear-gradient(90deg,#dc3545 0%,#c82333 100%);
            color: #fff;
            border: none;
            border-radius: 25px;
            padding: 8px 18px;
            font-weight: 700;
            cursor: pointer;
            font-size: 0.85em;
            box-shadow: 0 2px 8px rgba(220,53,69,0.2);
            transition: all 0.2s;
            letter-spacing: 0.3px;
            display: none;
            white-space: nowrap;
          " onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'" title="Report this user">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" style="vertical-align: middle; margin-right: 4px;">
              <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <line x1="12" y1="9" x2="12" y2="13" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
              <line x1="12" y1="17" x2="12.01" y2="17" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Report
          </button>
          <button id="doneChatBtn" onclick="showRatingModal()" style="
            background: linear-gradient(90deg,#ff5858 0%,#f09819 100%);
            color: #fff;
            border: none;
            border-radius: 25px;
            padding: 8px 20px;
            font-weight: 700;
            cursor: pointer;
            font-size: 0.9em;
            box-shadow: 0 2px 8px rgba(255,88,88,0.2);
            transition: all 0.2s;
            letter-spacing: 0.3px;
            display: none;
            white-space: nowrap;
          " onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">Done Chat</button>
        </div>
        <input type="hidden" id="ratingReceiverId" value="">
      </div>  
      <!-- Chat box -->
      <div class="chat-box" id="chatBox" style="flex:1; padding:1.5rem; overflow-y:auto; background:#ffffff;"></div>
      <!-- Image Preview Container Above Input -->
      <div id="imagePreviewContainer">
        <div id="imagePreviewWrapper">
          <img id="previewImage" src="" alt="Preview">
          <button id="cancelImageBtn" onclick="cancelImage()" title="Remove image">×</button>
        </div>
      </div>
      <!-- Input area -->
      <div class="input-area" style="display:none;">
        <div class="messageBox">
          <div class="fileUploadWrapper">
            <label for="imageInput">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="13" r="3" stroke="#888" stroke-width="1.5"></circle>
                <path d="M3 16.8V9.2C3 8.08 3 7.52 3.218 7.092C3.41 6.716 3.716 6.41 4.092 6.218C4.52 6 5.08 6 6.2 6H7.26C7.82 6 8.1 6 8.358 5.91C8.586 5.83 8.796 5.704 8.976 5.538C9.18 5.35 9.328 5.09 9.624 4.57L10.376 3.43C10.672 2.91 10.82 2.65 11.024 2.462C11.204 2.296 11.414 2.17 11.642 2.09C11.9 2 12.18 2 12.74 2H11.26C11.82 2 12.1 2 12.358 2.09C12.586 2.17 12.796 2.296 12.976 2.462C13.18 2.65 13.328 2.91 13.624 3.43L14.376 4.57C14.672 5.09 14.82 5.35 15.024 5.538C15.204 5.704 15.414 5.83 15.642 5.91C15.9 6 16.18 6 16.74 6H17.8C18.92 6 19.48 6 19.908 6.218C20.284 6.41 20.59 6.716 20.782 7.092C21 7.52 21 8.08 21 9.2V16.8C21 17.92 21 18.48 20.782 18.908C20.59 19.284 20.284 19.59 19.908 19.782C19.48 20 18.92 20 17.8 20H6.2C5.08 20 4.52 20 4.092 19.782C3.716 19.59 3.41 19.284 3.218 18.908C3 18.48 3 17.92 3 16.8Z" stroke="#888" stroke-width="1.5"></path>
              </svg>
              <span class="tooltip">Add Image</span>
            </label>
            <input type="file" id="imageInput" accept="image/*">
          </div>
          <input type="text" id="messageInput" placeholder="Type a message..." required>
          <button id="sendButton" onclick="sendMessage()">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M22 2L11 13M22 2L15 22L11 13L2 9L22 2Z" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>
      </div>

    </div>

    <!-- Chat Profile Sidebar (hidden by default) -->
    <div id="chatProfileSidebar" style="width:320px; background:#fff; border-right:1px solid #e0e0e0; display:none; flex-direction:column; align-items:center; padding:2.5rem 1.5rem;">
       <div style="font-size:1.4em; font-weight:700; color:#111; margin-bottom:20px; text-align:center;">
    Profile
  </div>
      <div style="position:relative; margin-bottom:18px;">
        <img id="chatProfileImg" src="" alt="Avatar" style="width:100px; height:100px; border-radius:50%; object-fit:cover; background:#f3f3f3;">
        <div id="chatProfileVerifiedBadge" style="position:absolute; bottom:7px; right:5px; background:#10b981; color:#fff; border-radius:50%; width:28px; height:28px; display:none; align-items:center; justify-content:center; font-size:16px; font-weight:bold; border:3px solid #fff; box-shadow:0 2px 8px rgba(16,185,129,0.3);" title="Verified by Admin">✓</div>
      </div>
      <div id="chatProfileName" style="font-size:1.3em; font-weight:700; color:#222; margin-bottom:4px; text-align:center;"></div>
      <div id="chatProfileId" style="color:#28a745; font-weight:600; margin-bottom:6px; font-size:1em; text-align:center;"></div>
      <div id="chatProfileAge" style="font-size:1em; color:#444; margin-bottom:8px; text-align:center;"></div>
      <div id="chatProfileSkills" style="font-size:0.98em; color:#666; font-style:italic; margin-bottom:10px; text-align:center;"></div>
      <div id="chatProfileSkillDesc" style="font-size:0.98em; color:#333; margin-bottom:18px; text-align:center; background:#f7fafd; border-radius:10px; padding:10px 12px; width:100%;"></div>
    </div>
  </div>

<!-- Profile Card Modal -->
<div id="profileModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); z-index:1000; align-items:center; justify-content:center;">
  <div id="profileCard" style="
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 24px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
    padding: 1.5rem 1.5rem 1.5rem 1.5rem;
    min-width: 380px;
    max-width: 440px;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    transition: all 0.3s ease;
  ">
    <button onclick="closeProfileCard()" style="
      position: absolute;
      top: 16px;
      right: 16px;
      background: #e9ecef;
      border: none;
      font-size: 1.5em;
      color: #6c757d;
      cursor: pointer;
      transition: all 0.2s;
      z-index: 2;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      line-height: 1;
    " aria-label="Close" onmouseover="this.style.background='#dc3545'; this.style.color='#fff'" onmouseout="this.style.background='#e9ecef'; this.style.color='#6c757d'">&times;</button>
    
    <div style="position: relative; margin-bottom: 12px;">
      <img id="modalProfileImg" src="" alt="Avatar" style="
        width: 100px;
        height: 100px;
        border-radius: 50%;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        object-fit: cover;
        background: #e9ecef;
        border: 4px solid #fff;
      ">
      <div id="verifiedBadge" style="
        position: absolute;
        bottom: 5px;
        right: 5px;
        width: 32px;
        height: 32px;
        background: #10b981;
        border: 3px solid #fff;
        border-radius: 50%;
        box-shadow: 0 2px 8px rgba(16,185,129,0.3);
        display: none;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: bold;
        font-size: 18px;
      ">✓</div>
    </div>
    
    <div id="modalProfileName" style="
      font-size: 1.4em;
      font-weight: 700;
      color: #212529;
      margin-bottom: 4px;
      text-align: center;
      letter-spacing: -0.5px;
    "></div>
    
    <div id="modalProfileId" style="
      color: #28a745;
      font-weight: 600;
      margin-bottom: 8px;
      font-size: 0.85em;
      text-align: center;
      background: #e7f5ec;
      padding: 3px 12px;
      border-radius: 12px;
    "></div>
    
    <div id="modalProfileAge" style="
      font-size: 0.9em;
      color: #6c757d;
      margin-bottom: 10px;
      text-align: center;
      font-weight: 500;
    "></div>
    
    <div id="modalProfileSkills" style="
      font-size: 0.95em;
      color: #495057;
      font-style: italic;
      margin-bottom: 8px;
      text-align: center;
      font-weight: 500;
    "></div>
    
    <div id="modalProfileSkillDesc" style="
      font-size: 0.85em;
      color: #6c757d;
      margin-bottom: 12px;
      text-align: center;
      background: #f8f9fa;
      border-radius: 10px;
      padding: 10px 12px;
      width: 100%;
      box-sizing: border-box;
      border: 1px solid #e9ecef;
      line-height: 1.4;
    "></div>
    
    <!-- Customer Reviews Section -->
    <div id="modalReviewsSection" style="width: 100%; margin-bottom: 12px;">
      <h4 style="color: #28a745; font-size: 0.9em; margin-bottom: 8px; font-weight: 600; display: flex; align-items: center; gap: 6px;">
        💬 Customer Reviews
      </h4>
      <div id="modalReviewsList" style="max-height: 180px; overflow-y: auto; padding-right: 4px;"></div>
    </div>
    
    <button class="chat-btn" style="
      background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
      padding: 12px 0;
      font-size: 1em;
      border-radius: 50px;
      box-shadow: 0 6px 20px rgba(40,167,69,0.25);
      font-weight: 700;
      letter-spacing: 0.5px;
      width: 100%;
      transition: all 0.3s ease;
      border: none;
      color: #fff;
      cursor: pointer;
      text-transform: uppercase;
    " id="modalChatBtn" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(40,167,69,0.35)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 6px 20px rgba(40,167,69,0.25)'">Chat</button>
  </div>
</div>

<!-- Compact How to Use Modal -->
<div id="howToUseModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); backdrop-filter:blur(4px); z-index:2000; align-items:center; justify-content:center; padding:20px; overflow-y:auto;">
  <div class="how-to-modal-content" style="
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    padding: 2rem 1.5rem;
    max-width: 520px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    animation: modalSlideIn 0.3s ease-out;
    margin: auto;
  ">
    <button onclick="closeHowToUseCard()" style="
      position: absolute;
      top: 12px;
      right: 12px;
      background: transparent;
      border: none;
      font-size: 1.5em;
      color: #999;
      cursor: pointer;
      width: 28px;
      height: 28px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      transition: all 0.2s;
    " onmouseover="this.style.background='#f0f0f0'; this.style.color='#333'" onmouseout="this.style.background='transparent'; this.style.color='#999'">&times;</button>
    
    <div style="
      width: 56px;
      height: 56px;
      background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem auto;
      box-shadow: 0 4px 12px rgba(40,167,69,0.3);
    ">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
        <circle cx="12" cy="12" r="10" stroke="#fff" stroke-width="2"/>
        <path d="M12 16v-4m0-4h.01" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </div>
    
    <h2 style="color: #1a1a1a; font-weight: 700; font-size: 1.5em; margin-bottom: 0.3rem; text-align: center;">Barangay JobFinder</h2>
    <p style="color: #6c757d; text-align: center; margin-bottom: 1rem; font-size: 0.9em;">Connect with skilled residents in your community</p>
    
    <!-- What is JobFinder -->
    <div style="background: linear-gradient(135deg, #e7f5ec 0%, #d4edda 100%); border-radius: 12px; padding: 12px 14px; margin-bottom: 1.2rem; border: 1px solid #c3e6cb;">
      <p style="margin: 0; color: #155724; font-size: 0.88em; line-height: 1.5; text-align: center;">
        <strong>🏘️ What is JobFinder?</strong><br>
        A platform to find and hire skilled residents for services like carpentry, plumbing, tutoring, and more. Browse profiles, view ratings, chat directly, and rate your experience!
      </p>
    </div>
    
    <div style="text-align: left; font-size: 0.9em;">
      <div style="display: flex; gap: 10px; margin-bottom: 0.8rem; align-items: start;">
        <div style="min-width: 28px; height: 28px; background: linear-gradient(135deg, #28a745, #20c997); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 0.85em; flex-shrink: 0;">1</div>
        <p style="margin: 0; color: #495057; line-height: 1.4;"><strong>Browse & Search:</strong> View residents sorted by rating. Search by name or skill.</p>
      </div>
      
      <div style="display: flex; gap: 10px; margin-bottom: 0.8rem; align-items: start;">
        <div style="min-width: 28px; height: 28px; background: linear-gradient(135deg, #28a745, #20c997); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 0.85em; flex-shrink: 0;">2</div>
        <p style="margin: 0; color: #495057; line-height: 1.4;"><strong>View Profile:</strong> Click "View" to see ratings, skills, and comments.</p>
      </div>
      
      <div style="display: flex; gap: 10px; margin-bottom: 0.8rem; align-items: start;">
        <div style="min-width: 28px; height: 28px; background: linear-gradient(135deg, #28a745, #20c997); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 0.85em; flex-shrink: 0;">3</div>
        <p style="margin: 0; color: #495057; line-height: 1.4;"><strong>Chat:</strong> Click "Chat" to start messaging. Send text and images.</p>
      </div>
      
      <div style="display: flex; gap: 10px; margin-bottom: 0.8rem; align-items: start;">
        <div style="min-width: 28px; height: 28px; background: linear-gradient(135deg, #28a745, #20c997); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 0.85em; flex-shrink: 0;">4</div>
        <p style="margin: 0; color: #495057; line-height: 1.4;"><strong>Rate:</strong> Click "Done Chat" in header to rate your experience.</p>
      </div>
      
      <div style="display: flex; gap: 10px; margin-bottom: 0.8rem; align-items: start;">
        <div style="min-width: 28px; height: 28px; background: linear-gradient(135deg, #28a745, #20c997); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 0.85em; flex-shrink: 0;">5</div>
        <p style="margin: 0; color: #495057; line-height: 1.4;"><strong>Manage:</strong> Use "Chats" tab to view all conversations.</p>
      </div>
    </div>
    
    <!-- Verification Information -->
    <div style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border-radius: 12px; padding: 14px; margin-top: 1.2rem; border-left: 3px solid #2196f3;">
      <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
        <div style="width: 24px; height: 24px; background: #2196f3; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 0.9em;">✓</div>
        <p style="margin: 0; color: #1565c0; font-weight: 700; font-size: 0.9em;">How to Get Verified Badge</p>
      </div>
      <ol style="margin: 0; padding-left: 20px; color: #0d47a1; font-size: 0.82em; line-height: 1.6;">
        <li><strong>Visit Barangay Hall</strong> - Go to the barangay office</li>
        <li><strong>Present Proof</strong> - Show certificates, ID, portfolio, or documents proving your skills</li>
        <li><strong>Admin Verification</strong> - Admin will review and verify your profile</li>
        <li><strong>Get Badge</strong> - Verified users get a green check mark (✓) on their profile</li>
      </ol>
    </div>
    
    <div style="background: #e7f5ec; border-radius: 10px; padding: 10px 12px; margin-top: 1rem; border-left: 3px solid #28a745;">
      <p style="margin: 0; color: #1e7e34; font-size: 0.85em; line-height: 1.4;">
        <strong>💡 Tip:</strong> Higher-rated and verified residents appear first!
      </p>
    </div>
  </div>
</div>

<script>

// Image preview effect - Display above input area
document.getElementById('imageInput').addEventListener('change', function(e) {
  const previewContainer = document.getElementById('imagePreviewContainer');
  const previewImage = document.getElementById('previewImage');
  
  if (this.files && this.files[0]) {
    const file = this.files[0];
    const reader = new FileReader();
    reader.onload = function(ev) {
      previewImage.src = ev.target.result;
      previewContainer.style.display = 'flex';
    };
    reader.readAsDataURL(file);
  } else {
    previewContainer.style.display = 'none';
    previewImage.src = '';
  }
});

// Cancel/Remove image function
function cancelImage() {
  const imageInput = document.getElementById('imageInput');
  const previewContainer = document.getElementById('imagePreviewContainer');
  const previewImage = document.getElementById('previewImage');
  
  imageInput.value = '';
  previewImage.src = '';
  previewContainer.style.display = 'none';
}

// Add fadeIn animation for image preview
const style = document.createElement('style');
style.innerHTML = `@keyframes fadeInImg { from { opacity:0; transform:scale(0.95);} to { opacity:1; transform:scale(1);} }`;
document.head.appendChild(style);

let currentUserId = null;
let currentUserName = null;
let currentUserProfileImage = "<?= htmlspecialchars($current_user_profile_image) ?>";

// ✅ Update user activity every 30 seconds to keep them online
function updateUserActivity() {
  fetch('update_activity.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    }
  })
  .then(response => response.json())
  .then(data => {
    if (!data.success) {
      console.log('Activity update failed:', data.message);
    }
  })
  .catch(error => console.error('Error updating activity:', error));
}

// Update activity immediately on page load
updateUserActivity();

// Update activity every 30 seconds (30000 milliseconds)
setInterval(updateUserActivity, 30000);

// Check for inactive users every 60 seconds and refresh the page to update status
setInterval(function() {
  fetch('check_inactive_users.php')
    .then(response => response.json())
    .then(data => {
      if (data.success && data.affected_rows > 0) {
        // Reload the residents list to show updated statuses
        location.reload();
      }
    })
    .catch(error => console.error('Error checking inactive users:', error));
}, 60000);

// Update activity on user interaction (mouse move, click, keypress)
let activityTimeout;
function scheduleActivityUpdate() {
  clearTimeout(activityTimeout);
  activityTimeout = setTimeout(updateUserActivity, 2000); // Update 2 seconds after last interaction
}

document.addEventListener('mousemove', scheduleActivityUpdate);
document.addEventListener('click', scheduleActivityUpdate);
document.addEventListener('keypress', scheduleActivityUpdate);

// Tab switching logic
function showTab(tab) {
  document.getElementById('tabResidents').classList.toggle('active', tab === 'residents');
  document.getElementById('tabChats').classList.toggle('active', tab === 'chats');
  document.getElementById('residentsList').style.display = tab === 'residents' ? '' : 'none';
  document.getElementById('residentsSearchBox').style.display = tab === 'residents' ? '' : 'none';
  document.getElementById('chatsList').style.display = tab === 'chats' ? '' : 'none';
  if (tab === 'chats') loadChatRoomList();
}
showTab('residents');

// Residents search
// Tagalog to English skill mapping
const tagalogSkillMap = {
  // A
  "abogado": "lawyer",
  "accountant": "accountant",
  "aktor": "actor",
  "aktres": "actress",
  "tagapangasiwa": "administrator",
  "agriculturist": "agriculturist",
  "teknisyan ng aircon": "aircon technician",
  "mekaniko ng eroplano": "aircraft mechanic",
  "drayber ng ambulansya": "ambulance driver",
  "animator": "animator",
  "developer ng app": "app developer",
  "arkitekto": "architect",
  "assembler": "assembly worker",
  "katulong": "assistant",
  "mekaniko ng sasakyan": "auto mechanic",
  "tagapaganda ng kotse": "auto detailer",

  // B
  "babaeng tagalinis": "female cleaner",
  "panadero": "baker",
  "tagabangko": "bank teller",
  "barbero": "barber",
  "beterinaryo": "veterinarian",
  "basurero": "garbage collector",
  "parlorista": "beautician",
  "bellboy": "bellboy",
  "taga-ingat ng libro": "bookkeeper",
  "tagalagay ng ladrilyo": "bricklayer",
  "bumbero": "firefighter",
  "konduktor ng bus": "bus conductor",
  "drayber ng bus": "bus driver",
  "matadero": "butcher",

  // C
  "ahente ng tawag": "call center agent",
  "tagakuha ng larawan": "camera operator",
  "karpintero": "carpenter",
  "kasiher": "cashier",
  "chef": "chef",
  "teknikong kimikal": "chemical technician",
  "tagalinis": "cleaner",
  "manggagawang konstruksyon": "construction worker",
  "tagaluto": "cook",
  "teknisyan ng kompyuter": "computer technician",
  "tagapangalaga": "caretaker",
  "manunulat ng kopya": "copywriter",
  "tagapaghahatid": "courier",
  "serbisyo sa kustomer": "customer service",
  "opisyal ng adwana": "customs officer",
  "inhinyerong sibil": "civil engineer",
  "tagapakinis ng kotse": "car detailer",
  "klerk": "clerk",

  // D
  "tagasuri ng datos": "data analyst",
  "tagapag-encode ng datos": "data encoder",
  "guro sa daycare": "daycare teacher",
  "rider ng delivery": "delivery rider",
  "dentista": "dentist",
  "doktor": "doctor",
  "drayber": "driver",
  "tagahugas ng pinggan": "dishwasher",
  "drowingista": "draftsman",
  "developer": "developer",
  "disenyo": "designer",
  "digital marketer": "digital marketer",
  "katulong sa bahay": "domestic helper",
  "tagabantay ng pinto": "doorman",
  "dispatcher": "dispatcher",

  // E
  "elektrisyan": "electrician",
  "inhinyero": "engineer",
  "encoder": "encoder",
  "editor": "editor",
  "estudyante": "student",
  "tagapag-ayos ng kaganapan": "event coordinator",
  "lineman ng kuryente": "lineman",
  "embalsamador": "embalmer",
  "teknisyan ng elevator": "elevator technician",

  // F
  "manggagawa sa pabrika": "factory worker",
  "magsasaka": "farmer",
  "mangingisda": "fisherman",
  "bumbero": "firefighter",
  "tagabukid": "farm worker",
  "tagagupit ng bulaklak": "florist",
  "tagapangasiwa": "foreman",
  "tagasilbi ng pagkain": "food server",
  "operator ng forklift": "forklift operator",
  "tagatanggap sa harapan": "front desk officer",
  "empleyado sa burol": "funeral staff",

  // G
  "hardinero": "gardener",
  "graphic artist": "graphic artist",
  "graphic designer": "graphic designer",
  "guwardiya": "security guard",
  "tagasanay sa gym": "gym trainer",

  // H
  "tagagupit ng buhok": "hair stylist",
  "katulong": "helper",
  "kasambahay": "house helper",
  "tagapaglinis ng bahay": "housekeeper",
  "empleyado sa hotel": "hotel staff",
  "tauhan ng HR": "human resource",
  "teknisyan ng HVAC": "HVAC technician",

  // I
  "manggagawa sa pabrika": "industrial worker",
  "teknisyan ng internet": "internet technician",
  "tagakabit": "installer",
  "tagapagsalin": "interpreter",
  "IT support": "IT support",
  "inspektor": "inspector",
  "tagaimbak": "inventory clerk",
  "ahente ng seguro": "insurance agent",

  // J
  "tagalinis": "janitor",
  "drayber ng jeep": "jeepney driver",
  "mamamahayag": "journalist",
  "hukom": "judge",
  "gumagawa ng alahas": "jewelry maker",

  // K
  "kasambahay": "house helper",
  "kargador": "porter",
  "konduktor": "conductor",
  "teknisyan ng kuryente": "electrical technician",

  // L
  "labandera": "laundry worker",
  "tagapag-ayos ng halaman": "landscaper",
  "lineman": "lineman",
  "limpador": "cleaner",
  "tagaputol ng kahoy": "logger",
  "tagapahiram ng libro": "librarian",
  "tagapagligtas": "lifeguard",
  "opisyal ng pautang": "loan officer",
  "tagagawa ng kandado": "locksmith",

  // M
  "tagagawa ng makina": "machinist",
  "operator ng makina": "machine operator",
  "tauhan ng maintenance": "maintenance staff",
  "mananahi": "tailor",
  "manggagawa": "worker",
  "manicurista": "manicurist",
  "mason": "mason",
  "mekaniko": "mechanic",
  "mensahero": "messenger",
  "hilot": "midwife",
  "katulong sa marketing": "marketing assistant",
  "teknolohistang medikal": "medical technologist",
  "embalsamador": "mortician",
  "guro sa musika": "music teacher",
  "tagapamahala": "manager",

  // N
  "teknisyan ng kuko": "nail technician",
  "nars": "nurse",
  "katulong ng nars": "nurse aide",
  "teknisyan ng network": "network technician",

  // O
  "obrero": "laborer",
  "empleyado sa opisina": "office staff",
  "operator": "operator",
  "optometrista": "optometrist",
  "nagbebenta online": "online seller",
  "tagamasid": "overseer",

  // P
  "panday": "blacksmith",
  "pintor": "painter",
  "parmasyutiko": "pharmacist",
  "litratista": "photographer",
  "tubero": "plumber",
  "pulis": "police officer",
  "kartero": "postman",
  "manggagawa sa produksyon": "production worker",
  "programador": "programmer",
  "tagapamahala ng proyekto": "project manager",
  "promodiser": "sales promoter",
  "tagapangalaga ng ari-arian": "property caretaker",
  "manlilok": "sculptor",

  // Q
  "tagasuri ng kalidad": "quality inspector",
  "tagasukat": "quantity surveyor",

  // R
  "tagatanggap": "receptionist",
  "tagapag-ayos": "repairman",
  "tagapukpok ng bubong": "roofer",
  "mananaliksik": "researcher",
  "rider": "rider",
  "ahente ng bahay": "real estate agent",
  "tagapaglinis ng silid": "room attendant",

  // S
  "tagabenta": "salesperson",
  "sekretarya": "secretary",
  "guwardiya": "security guard",
  "tagasilbi": "service crew",
  "sapatero": "shoemaker",
  "mananahero": "sewer",
  "tagapangalaga sa lipunan": "social worker",
  "tindera": "saleslady",
  "tindero": "salesman",
  "tagapamahala ng tindahan": "storekeeper",
  "stylist": "stylist",
  "tagapangasiwa": "supervisor",
  "tagasukat": "surveyor",
  "guro sa paglangoy": "swimming instructor",
  "tagapangasiwa ng sistema": "system administrator",

  // T
  "mananahi": "tailor",
  "guro": "teacher",
  "teknisyan": "technician",
  "operator ng telepono": "telephone operator",
  "tagatinda": "vendor",
  "tagalinis": "cleaner",
  "tagapangalaga": "caretaker",
  "tagahatid": "delivery rider",
  "tagaalaga ng bata": "babysitter",
  "tagaalaga ng hayop": "pet caretaker",
  "tagaayos ng aircon": "aircon technician",
  "tagaayos ng kuryente": "electrician",
  "tagaayos ng tubo": "plumber",
  "tagaayos ng sasakyan": "auto mechanic",
  "tagaayos ng computer": "computer technician",
  "tagaayos ng bubong": "roofer",
  "tagaayos ng sahig": "floor installer",
  "tagaayos ng pader": "wall fixer",
  "tagaayos ng internet": "internet technician",
  "tagaayos ng cellphone": "cellphone technician",
  "tagaayos ng motor": "motorcycle mechanic",
  "tagahugas": "dishwasher",
  "tagahakot": "hauler",
  "tagakabit": "installer",
  "taga-deliver": "delivery person",
  "tagapagluto": "cook",
  "tagapintura ng bahay": "house painter",
  "tagapintura ng kotse": "car painter",
  "tagapaglinis ng opisina": "office cleaner",
  "tanod": "barangay watchman",
  "tagapagsalin": "translator",
  "drayber ng trak": "truck driver",
  "drayber ng traysikel": "tricycle driver",
  "tour guide": "tour guide",
  "tagapagturo": "tutor",
  "katulong sa pananahi": "tailoring assistant",

  // U
  "utility worker": "utility worker",
  "tagagawa ng upuan": "upholsterer",
  "tagalibing": "undertaker",
  "planner sa lungsod": "urban planner",

  // V
  "nagbebenta": "vendor",
  "beterinaryo": "veterinarian",
  "editor ng video": "video editor",
  "virtual assistant": "virtual assistant",
  "boluntaryo": "volunteer",
  "voice actor": "voice actor",

  // W
  "tagasilbi ng pagkain": "waiter",
  "tagasilbi": "waitress",
  "tauhan sa bodega": "warehouse staff",
  "bantay": "watchman",
  "manlilikhang habi": "weaver",
  "web developer": "web developer",
  "welding": "welder",
  "manlilok sa kahoy": "woodworker",
  "manunulat": "writer"
};


document.getElementById("searchInput").addEventListener("input", function() {
  let filter = this.value.toLowerCase();
  // Translate Tagalog to English if possible
  let translated = tagalogSkillMap[filter] || filter;
  document.querySelectorAll(".resident-card").forEach(card => {
    let name = card.dataset.name;
    let skills = card.dataset.skills;
    // Check both original and translated filter
    card.style.display = (
      name.includes(filter) ||
      skills.includes(filter) ||
      skills.includes(translated)
    ) ? "" : "none";
  });
});

// Add this function to fetch and show the chat partner's profile, including average rating
function showChatProfileSidebar(userId) {
  fetch('get_profile.php?userid=' + encodeURIComponent(userId))
    .then(res => res.json())
    .then(profile => {
      var profileImg = profile.profile_image || 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
      
      // Update chat header avatar
      document.getElementById('chatAvatar').src = profileImg;
      document.getElementById('chatAvatar').style.display = 'block';
      
      // Update profile sidebar
      document.getElementById('chatProfileSidebar').style.display = 'flex';
      document.getElementById('chatProfileImg').src = profileImg;
      document.getElementById('chatProfileName').textContent = profile.surname + ', ' + profile.first_name;
      document.getElementById('chatProfileId').textContent = 'User ID: ' + profile.unique_id;
      
      // Show/hide verified badge in sidebar
      var sidebarBadge = document.getElementById('chatProfileVerifiedBadge');
      if (profile.jobfinder_verified == 1 || profile.jobfinder_verified === '1') {
        sidebarBadge.style.display = 'flex';
      } else {
        sidebarBadge.style.display = 'none';
      }
      
      // Show/hide verified badge in chat header
      var headerBadge = document.getElementById('chatAvatarVerifiedBadge');
      if (profile.jobfinder_verified == 1 || profile.jobfinder_verified === '1') {
        headerBadge.style.display = 'flex';
        headerBadge.title = 'Verified by Admin';
      } else {
        headerBadge.style.display = 'none';
      }
      document.getElementById('chatProfileAge').textContent = 'Age: ' + profile.age;
      document.getElementById('chatProfileSkills').innerHTML = '<b>Skills:</b> ' + (profile.occupation_skills || 'None');
      document.getElementById('chatProfileSkillDesc').innerHTML = profile.skill_description ? `<b>Description:</b> ${profile.skill_description}` : '';

      // Fetch average rating for this user
      fetch('get_profile_rating.php?userid=' + encodeURIComponent(userId))
        .then(res => res.json())
        .then(ratingData => {
          let ratingDiv = document.getElementById('chatProfileAvgRating');  
          if (!ratingDiv) {
            ratingDiv = document.createElement('div');
            ratingDiv.id = 'chatProfileAvgRating';
            ratingDiv.style.margin = '10px 0 8px 0';
            ratingDiv.style.textAlign = 'center';
            document.getElementById('chatProfileSidebar').insertBefore(ratingDiv, document.getElementById('chatProfileId').nextSibling);
          }
          if (ratingData && ratingData.avg_rating > 0) {
            let html = '<span title="Average rating" style="color:#FFD700; font-size:1.18em; display:inline-flex; align-items:center;">';
            for (let i = 1; i <= 5; i++) {
              html += (i <= Math.round(ratingData.avg_rating)) ? '&#9733;' : '&#9734;';
            }
            html += `<span style=\"color:#888; font-size:0.98em; margin-left:4px;\">(${parseFloat(ratingData.avg_rating).toFixed(1)}`;
            if (ratingData.rating_count > 0) html += `, ${ratingData.rating_count} rating${ratingData.rating_count>1?'s':''}`;
            html += ")</span></span>";
            ratingDiv.innerHTML = html;
          } else {
            ratingDiv.innerHTML = '<span style="color:#bbb; font-size:1.08em;">No ratings yet</span>';
          }
        });
    });
}

// Open chat with resident or from chat list
function openChat(userId, userName, isOnline = null, lastActive = null) {
  currentUserId = userId;
  currentUserName = userName;
  
  // Update chat header with name
  document.getElementById("chatUser").textContent = userName;
  
  // Update active status in chat header
  updateChatHeaderStatus(userId, isOnline, lastActive);
  
  document.getElementById("chatAvatar").style.display = "";
  document.querySelector(".input-area").style.display = "flex";
  loadChatHistory(userId);
  fetch("mark_read.php?other_id=" + encodeURIComponent(userId));
  startChatPolling(userId);
  showChatProfileSidebar(userId);
}

// Update chat header active status
function updateChatHeaderStatus(userId, isOnline = null, lastActive = null) {
  // If status not provided, fetch it
  if (isOnline === null) {
    fetch('get_user_status.php?userid=' + encodeURIComponent(userId))
      .then(res => res.json())
      .then(data => {
        displayChatHeaderStatus(data.is_online, data.last_active);
      })
      .catch(() => {
        // Silently fail
      });
  } else {
    displayChatHeaderStatus(isOnline, lastActive);
  }
}

function displayChatHeaderStatus(isOnline, lastActive) {
  let statusEl = document.getElementById('chatHeaderStatus');
  if (!statusEl) return;
  
  const statusText = isOnline ? 'Active now' : timeAgo(lastActive);
  const statusClass = isOnline ? 'online' : 'offline';
  const statusColor = isOnline ? '#28a745' : '#666';
  
  statusEl.style.display = 'flex';
  statusEl.innerHTML = `
    <span class="status-indicator ${statusClass}"></span>
    <span style="font-size:0.75rem; color:${statusColor}; font-weight:500;">${statusText}</span>
  `;
}

// Mobile view helpers: show only chat on small screens and provide back button
function isSmallScreen() {
  return window.matchMedia && window.matchMedia('(max-width: 900px)').matches;
}

function enterChatView() {
  // add a class to body to indicate mobile chat is active
  document.body.classList.add('mobile-chat-active');
  // hide the residents list to give full width to chat
  document.getElementById('residentsList').style.display = 'none';
  document.getElementById('residentsSearchBox').style.display = 'none';
  document.getElementById('chatsList').style.display = 'none';
}

function exitChatView() {
  document.body.classList.remove('mobile-chat-active');
  // restore the residents tab UI
  document.getElementById('residentsList').style.display = '';
  document.getElementById('residentsSearchBox').style.display = '';
  // hide the chat input if no conversation selected
  // Clear chat state and UI so Back fully closes the chat room
  try {
    currentUserId = null;
    currentUserName = null;
    const chatUserEl = document.getElementById('chatUser');
    if (chatUserEl) chatUserEl.textContent = 'Select a conversation';
    const avatar = document.getElementById('chatAvatar');
    if (avatar) avatar.style.display = 'none';
    const chatBox = document.getElementById('chatBox');
    if (chatBox) chatBox.innerHTML = '';
    const inputArea = document.querySelector('.input-area');
    if (inputArea) inputArea.style.display = 'none';
    const doneBtn = document.getElementById('doneChatBtn');
    if (doneBtn) doneBtn.style.display = 'none';
    const reportBtn = document.getElementById('reportUserBtn');
    if (reportBtn) reportBtn.style.display = 'none';
    const profileSidebar = document.getElementById('chatProfileSidebar');
    if (profileSidebar) profileSidebar.style.display = 'none';
    stopChatPolling();
  } catch (e) {
    console.warn('Error while exiting chat view', e);
  }
}

// Ensure opening a chat on small screens enters chat-only view
const _openChat = openChat;
openChat = function(userId, userName) {
  _openChat(userId, userName);
  if (isSmallScreen()) enterChatView();
};

// Load chat history
function loadChatHistory(userId) {
  const chatBox = document.getElementById("chatBox");
  fetch("get_messages.php?other_id=" + encodeURIComponent(userId))
    .then(res => res.json())
    .then(data => {
      chatBox.innerHTML = "";
      let userHasSent = false;
      data.forEach(msg => {
        const sender = (msg.sender_id === "<?= $_SESSION['userid'] ?>") ? "me" : "other";
        if (sender === 'me') userHasSent = true;
        // Pass datetime, message ID, is_read flag and profile image to addMessage
        addMessage(sender, msg.message, msg.image_path, msg.datetime || msg.created_at || "", msg.id, msg.profile_image, msg.is_read ? 1 : 0);
      });
      // Show Done Chat and Report buttons when the logged-in user has at least one message in this conversation
      const doneBtn = document.getElementById('doneChatBtn');
      const reportBtn = document.getElementById('reportUserBtn');
      if (userHasSent) {
        doneBtn.style.display = 'inline-block';
        reportBtn.style.display = 'inline-block';
      } else {
        doneBtn.style.display = 'none';
        reportBtn.style.display = 'none';
      }
    })
    .catch(() => {
      chatBox.innerHTML = "<p style='color:red;'>Failed to load messages.</p>";
      const doneBtn = document.getElementById('doneChatBtn');
      if (doneBtn) doneBtn.style.display = 'none';
      const reportBtn = document.getElementById('reportUserBtn');
      if (reportBtn) reportBtn.style.display = 'none';
    });
}

// Polling to refresh messages and update seen status when chat is open
let chatPollInterval = null;
function startChatPolling(userId) {
  stopChatPolling();
  chatPollInterval = setInterval(() => {
    // Only refresh if the same conversation is open
    if (!currentUserId || currentUserId != userId) return;
    fetch('get_messages.php?other_id=' + encodeURIComponent(userId))
      .then(res => res.json())
      .then(data => {
          // Update seen state for outgoing messages
          data.forEach(msg => {
            if (msg.sender_id === "<?= $_SESSION['userid'] ?>") {
              // Find the message element by data-message-id
              const el = document.querySelector(`.message.me[data-message-id="${msg.id}"]`);
              if (el) {
                const previous = el.getAttribute('data-is-read');
                const nowRead = msg.is_read ? '1' : '0';
                if (previous !== nowRead) {
                  el.setAttribute('data-is-read', nowRead);
                  // If currently selected, update visible Seen label
                  if (el.classList.contains('selected')) {
                    const seenEl = el.querySelector('.message-seen');
                    if (seenEl) {
                      seenEl.style.display = nowRead === '1' ? 'block' : 'none';
                      seenEl.textContent = nowRead === '1' ? 'Seen' : '';
                    }
                  }
                }
              }
            }
          });
        }).catch(() => {});
  }, 5000);
}

function stopChatPolling() {
  if (chatPollInterval) {
    clearInterval(chatPollInterval);
    chatPollInterval = null;
  }
}

// Add message to chat box
function addMessage(sender, text, imagePath = null, datetime = null, messageId = null, profileImage = null, isRead = 0) {
  const chatBox = document.getElementById("chatBox");
  const div = document.createElement("div");
  div.classList.add("message", sender);
  
  console.log('Adding message:', { sender, messageId, text }); // Debug log
  
  // Store message ID if available
  if (messageId) {
    div.setAttribute('data-message-id', messageId);
    // store read state on the element for later use
    div.setAttribute('data-is-read', isRead ? '1' : '0');
  }

  // Add unsend button for own messages
  if (sender === "me" && messageId) {
    const unsendBtn = document.createElement("button");
    unsendBtn.classList.add("unsend-btn");
    unsendBtn.textContent = "Unsend";
    unsendBtn.onclick = (e) => {
      e.stopPropagation();
      unsendMessage(messageId, div);
    };
    div.appendChild(unsendBtn);
  }

  // Add profile image
  const profileImg = document.createElement("img");
  profileImg.classList.add("profile-img");
  profileImg.alt = "Profile";
  profileImg.src = profileImage && profileImage.trim() !== "" ? profileImage : "default_avatar.png";
  div.appendChild(profileImg);

  // Create message content container
  const contentDiv = document.createElement("div");
  contentDiv.classList.add("message-content");

  // Add text if exists
  if (text && text.trim() !== "") {
    const p = document.createElement("p");
    p.textContent = text;
    contentDiv.appendChild(p);
  }

  // Add image if exists
  if (imagePath) {
    const img = document.createElement("img");
    img.src = imagePath;
    img.style.maxWidth = "200px";
    img.style.marginTop = "6px";
    img.style.borderRadius = "12px";
    img.style.cursor = "pointer";
    img.onclick = (e) => {
      e.stopPropagation();
      openImageModal(imagePath);
    };
    contentDiv.appendChild(img);
  }

  // Add date/time if exists
  if (datetime) {
    const timeDiv = document.createElement("div");
    timeDiv.style.fontSize = "0.78em";
    timeDiv.style.color = sender === "me" ? "#e8f5e9" : "#555";
    timeDiv.style.marginTop = "4px";
    timeDiv.style.textAlign = sender === "me" ? "right" : "left";
    timeDiv.textContent = datetime;
    contentDiv.appendChild(timeDiv);
  }

  // If this is an outgoing message, create a hidden Seen label; reveal on click
  let seenDiv = null;
  if (sender === 'me') {
    seenDiv = document.createElement('div');
    seenDiv.style.fontSize = '0.75em';
    seenDiv.style.color = '#e8f5e9';
    seenDiv.style.marginTop = '6px';
    seenDiv.style.textAlign = 'right';
    seenDiv.classList.add('message-seen');
    // hidden by default until user clicks the message
    seenDiv.textContent = '';
    seenDiv.style.display = 'none';
    contentDiv.appendChild(seenDiv);
  }

  div.appendChild(contentDiv);
  
  // Make message clickable to toggle selection (only for own messages)
  if (sender === "me" && messageId) {
    div.onclick = function(e) {
      // Don't toggle if clicking on image or unsend button
      if (e.target.tagName === 'IMG' || e.target.classList.contains('unsend-btn')) {
        return;
      }
      
      console.log('Message clicked:', messageId); // Debug log
      
      // Remove selection from all other messages
      document.querySelectorAll('.message.selected').forEach(msg => {
        if (msg !== div) msg.classList.remove('selected');
      });
      
      // Toggle selection on this message
      div.classList.toggle('selected');
      // After toggling, reveal or hide the Seen/Send label based on selection and current is_read state
      try {
        const isSelected = div.classList.contains('selected');
        const isReadAttr = div.getAttribute('data-is-read');
        const seenEl = div.querySelector('.message-seen');
        if (seenEl) {
          if (isSelected) {
            seenEl.style.display = 'block';
            // Show 'Seen' if read, otherwise show 'Send'
            seenEl.textContent = (isReadAttr === '1') ? 'Seen' : 'Send';
          } else {
            seenEl.style.display = 'none';
            seenEl.textContent = '';
          }
        }
      } catch (err) {
        console.warn('Error showing seen/send label', err);
      }
    };
    
    // Also make it visually clear it's clickable
    div.style.userSelect = 'none';
  }

  chatBox.appendChild(div);
  chatBox.scrollTop = chatBox.scrollHeight;

  // Show Done Chat and Report buttons if at least one message exists from current user
  const doneBtn = document.getElementById('doneChatBtn');
  const reportBtn = document.getElementById('reportUserBtn');
  if (chatBox.children.length > 0 && sender === 'me') {
    doneBtn.style.display = 'inline-block';
    reportBtn.style.display = 'inline-block';
  }
}

// Unsend message function - stores data for modal
let pendingUnsendData = null;

function unsendMessage(messageId, messageElement) {
  console.log('Unsending message:', messageId); // Debug log
  
  // Store the data for when user confirms
  pendingUnsendData = { messageId, messageElement };
  
  // Show the modal
  const modal = document.getElementById('unsendModal');
  modal.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeUnsendModal() {
  const modal = document.getElementById('unsendModal');
  modal.classList.remove('active');
  document.body.style.overflow = '';
  pendingUnsendData = null;
}

function confirmUnsend() {
  if (!pendingUnsendData) return;
  
  const { messageId, messageElement } = pendingUnsendData;
  
  // Close modal first
  closeUnsendModal();
  
  // Proceed with deletion
  fetch('delete_message.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ message_id: messageId })
  })
  .then(res => res.json())
  .then(response => {
    if (response.status === 'success') {
      // Remove message from UI with animation
      messageElement.style.transition = 'all 0.3s ease-out';
      messageElement.style.opacity = '0';
      messageElement.style.transform = 'scale(0.8)';
      setTimeout(() => {
        messageElement.remove();
      }, 300);
      showToast('Message unsent successfully', 'success');
    } else {
      showToast('Failed to unsend message: ' + (response.error || 'Unknown error'), 'error');
    }
  })
  .catch(err => {
    showToast('Error unsending message. Please try again.', 'error');
  });
}

// Send message
function sendMessage() {
  if (!currentUserId) return;
  const input = document.getElementById("messageInput");
  const text = input.value.trim();
  const imageFile = document.getElementById("imageInput").files[0];

  if (!text && !imageFile) return;

  const formData = new FormData();
  formData.append("receiver_id", currentUserId);
  formData.append("message", text);
  if (imageFile) formData.append("image", imageFile);

  fetch("send_message.php", {
    method: "POST",
    body: formData
  })
  .then(res => res.json())
  .then (response => {
    if (response.status === "success") {
      // Use server time if returned, else local time
      let now = response.datetime || new Date().toLocaleString();
      let messageId = response.message_id || null;
      addMessage("me", text, imageFile ? URL.createObjectURL(imageFile) : null, now, messageId, currentUserProfileImage);
      // after successful send, reveal the Done Chat and Report buttons
      const doneBtn = document.getElementById('doneChatBtn');
      const reportBtn = document.getElementById('reportUserBtn');
      if (doneBtn) doneBtn.style.display = 'inline-block';
      if (reportBtn) reportBtn.style.display = 'inline-block';
  input.value = "";
  cancelImage(); // Use the cancel function to clear image
    } else {
      showToast("Message failed: " + (response.error || "Unknown error"), "error");
    }
  })
  .catch(err => {
    showToast("Error sending message. Please try again.", "error");
  });
}

// Enter to send
document.getElementById("messageInput").addEventListener("keydown", e => {
  if (e.key === "Enter" && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
});

// Click outside to deselect messages
document.addEventListener('click', function(e) {
  const chatBox = document.getElementById('chatBox');
  if (chatBox && !chatBox.contains(e.target)) {
    document.querySelectorAll('.message.selected').forEach(msg => {
      msg.classList.remove('selected');
    });
  }
});

// Image Modal Functions
let currentImageSrc = '';

function openImageModal(imageSrc) {
  currentImageSrc = imageSrc;
  const modal = document.getElementById('imageModal');
  const modalImage = document.getElementById('modalImage');
  
  modalImage.src = imageSrc;
  modal.classList.add('active');
  
  // Prevent body scroll when modal is open
  document.body.style.overflow = 'hidden';
}

function closeImageModal() {
  const modal = document.getElementById('imageModal');
  modal.classList.remove('active');
  currentImageSrc = '';
  
  // Restore body scroll
  document.body.style.overflow = '';
}

function downloadImage() {
  if (!currentImageSrc) return;
  
  // Create a temporary link element
  const link = document.createElement('a');
  link.href = currentImageSrc;
  link.download = 'image_' + Date.now() + '.jpg';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  
  showToast('Image download started', 'success');
}

// Close modal when clicking outside the image
document.addEventListener('DOMContentLoaded', function() {
  const modal = document.getElementById('imageModal');
  if (modal) {
    modal.addEventListener('click', function(e) {
      if (e.target === modal) {
        closeImageModal();
      }
    });
  }
  
  // Close modal with Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      const imageModal = document.getElementById('imageModal');
      const unsendModal = document.getElementById('unsendModal');
      
      if (imageModal && imageModal.classList.contains('active')) {
        closeImageModal();
      }
      if (unsendModal && unsendModal.classList.contains('active')) {
        closeUnsendModal();
      }
    }
  });
  
  // Close unsend modal when clicking outside
  const unsendModal = document.getElementById('unsendModal');
  if (unsendModal) {
    unsendModal.addEventListener('click', function(e) {
      if (e.target === unsendModal) {
        closeUnsendModal();
      }
    });
  }
});

// JavaScript timeAgo function
function timeAgo(datetime) {
  if (!datetime) return 'Never active';
  
  const now = new Date();
  const ago = new Date(datetime);
  const diffMs = now - ago;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);
  
  if (diffDays > 0) return diffDays + ' day' + (diffDays > 1 ? 's' : '') + ' ago';
  if (diffHours > 0) return diffHours + ' hour' + (diffHours > 1 ? 's' : '') + ' ago';
  if (diffMins > 0) return diffMins + ' min' + (diffMins > 1 ? 's' : '') + ' ago';
  return 'Just now';
}

// Load chat conversations for chat tab
function loadChatRoomList() {
  fetch("chat_list.php")
    .then(res => res.json())
    .then(data => {
      const list = document.getElementById("chatsList");
      list.innerHTML = "";
      if (!data || data.length === 0) {
        list.innerHTML = "<p style='padding:1rem;'>No conversations yet.</p>";
        return;
      }
      data.forEach(user => {
        const img = user.profile_image && user.profile_image.trim() !== ""
          ? user.profile_image
          : "https://cdn-icons-png.flaticon.com/512/149/149071.png";
        
        // Active status
        const isOnline = user.is_online == 1;
        const statusText = isOnline ? 'Active now' : timeAgo(user.last_active);
        const statusClass = isOnline ? 'online' : 'offline';
        const statusColor = isOnline ? '#28a745' : '#666';
        
        // Verification status
        const isVerified = user.jobfinder_verified == 1 || user.jobfinder_verified === '1';
        
        const div = document.createElement("div");
        div.classList.add("sidebar-card");
        div.innerHTML = `
          <div style="position:relative; display:inline-block;">
            <img src="${img}" width="44" style="border-radius:50%;">
            ${isVerified ? '<span title="Verified by Admin" style="position:absolute; bottom:-2px; right:-2px; display:flex; align-items:center; justify-content:center; background:#10b981; color:#fff; border-radius:50%; width:16px; height:16px; font-size:10px; font-weight:bold; border:2px solid #fff; box-shadow:0 2px 4px rgba(16,185,129,0.3);">✓</span>' : ''}
          </div>
          <div style="flex:1; margin-left:10px;">
            <div style="font-weight:600;">${user.name}</div>
            <div style="display:flex; align-items:center; gap:6px; margin-top:4px;">
              <span class="status-indicator ${statusClass}"></span>
              <span style="font-size:0.8rem; color:${statusColor}; font-weight:500;">${statusText}</span>
            </div>
          </div>
          <div style="display:flex; align-items:center; gap:8px;">
            ${user.unread > 0 ? `<span class="unread-badge">${user.unread}</span>` : ""}
            <button class="chat-btn" style="padding:6px 12px; font-size:0.85em;" onclick="openChat('${user.id}','${user.name.replace(/'/g, "\\'")}', ${isOnline}, '${user.last_active || ''}')">Open</button>
          </div>
        `;
        list.appendChild(div);
      });
    })
    .catch(() => {
      document.getElementById("chatsList").innerHTML = "<p>Could not load conversations.</p>";
    });
}

// Fetch unread chat count and update badge
function updateChatsBadge() {
  fetch('jobfinder.php?unread_count=1')
    .then(res => res.json())
    .then(data => {
      const badge = document.getElementById('chatsBadge');
      if (data.count > 0) {
        badge.textContent = data.count;
        badge.style.display = '';
      } else {
        badge.style.display = 'none';
      }
    });
} 
setInterval(updateChatsBadge, 2000);
updateChatsBadge();

  // Show profile card modal
function showProfileCard(userid, name, img, age, skills, skillDesc, avgRating = 0, ratingCount = 0, isVerified = 0) {
  document.getElementById('modalProfileImg').src = img;
  document.getElementById('modalProfileName').textContent = name;
  document.getElementById('modalProfileId').textContent = "User ID: " + userid;
  
  // Show/hide verified badge
  var verifiedBadge = document.getElementById('verifiedBadge');
  if (isVerified == 1 || isVerified === '1') {
    verifiedBadge.style.display = 'flex';
    verifiedBadge.title = 'Verified by Admin';
  } else {
    verifiedBadge.style.display = 'none';
  }
  document.getElementById('modalProfileAge').textContent = "Age: " + age;
  document.getElementById('modalProfileSkills').innerHTML = "Skills: " + (skills ? skills.replace(/[,;]/g, ', ') : "No skills listed");
  
  // Hide skill description if empty or show it with cleaner text
  const skillDescEl = document.getElementById('modalProfileSkillDesc');
  if (skillDesc && skillDesc.trim()) {
    skillDescEl.innerHTML = skillDesc;
    skillDescEl.style.display = 'block';
  } else {
    skillDescEl.style.display = 'none';
  }
  
  // Add average rating stars to profile modal
  let ratingHtml = '';
  if (avgRating > 0) {
    ratingHtml += '<div style="display:flex; align-items:center; justify-content:center; gap:4px; margin-bottom:12px;">';
    ratingHtml += '<div style="display:flex; gap:2px;">';
    for (let i = 1; i <= 5; i++) {
      ratingHtml += `<span style="color:${i <= Math.round(avgRating) ? '#FFD700' : '#e0e0e0'}; font-size:1.4em;">★</span>`;
    }
    ratingHtml += '</div>';
    ratingHtml += `<span style="color:#6c757d; font-size:0.95em; font-weight:500; margin-left:4px;">(${avgRating.toFixed(1)}`;
    if (ratingCount > 0) ratingHtml += `, ${ratingCount} rating${ratingCount>1?'s':''}`;
    ratingHtml += ")</span></div>";
  } else {
    ratingHtml = '<div style="color:#adb5bd; font-size:0.95em; margin-bottom:12px; text-align:center;">No ratings yet</div>';
  }
  let ratingDiv = document.getElementById('modalProfileRating');
  if (!ratingDiv) {
    ratingDiv = document.createElement('div');
    ratingDiv.id = 'modalProfileRating';
    ratingDiv.style.textAlign = 'center';
    document.getElementById('profileCard').insertBefore(ratingDiv, document.getElementById('modalProfileId').nextSibling);
  }
  ratingDiv.innerHTML = ratingHtml;

  // Fetch and display reviews with ratings
  let reviewsList = document.getElementById('modalReviewsList');
  reviewsList.innerHTML = '<div style="color:#adb5bd; text-align:center; padding:12px 0; font-size:0.9em;">Loading reviews...</div>';
  
  fetch('get_profile_rating.php?userid=' + encodeURIComponent(userid))
    .then(res => res.json())
    .then(ratingData => {
      fetch('get_profile_comments.php?userid=' + encodeURIComponent(userid))
        .then(res => res.json())
        .then(data => {
          if (data && data.length > 0) {
            reviewsList.innerHTML = data.map(c => {
              const rating = c.rating || 0;
              const stars = Array(5).fill(0).map((_, i) => 
                `<span style="color:${i < rating ? '#fbbf24' : '#d1d5db'}; font-size:14px;">★</span>`
              ).join('');
              
              return `<div style="margin-bottom:8px; padding:8px; background:#fff; border-radius:6px; border-left:3px solid #28a745; font-size:0.8em;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                  <span style="font-weight:600; color:#333; font-size:0.9em;">${c.name || 'Anonymous'}</span>
                  <div>${stars}</div>
                </div>
                <div style="color:#555; line-height:1.3; margin-bottom:3px;">${c.comment.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</div>
                <div style="color:#999; font-size:0.75em;">${c.date || ''}</div>
              </div>`;
            }).join('');
          } else {
            reviewsList.innerHTML = '<div style="color:#adb5bd; text-align:center; padding:16px 0; font-size:0.9em;">💬<br>No reviews yet</div>';
          }
        });
    });

  var modal = document.getElementById('profileModal');
  modal.classList.add('show');
  modal.style.display = 'flex';
  setTimeout(function(){ modal.style.opacity = 1; }, 10);
  document.getElementById('modalChatBtn').onclick = function() {
    closeProfileCard();
    openChat(userid, name);
  };
}

function closeProfileCard() {
  var modal = document.getElementById('profileModal');
  modal.classList.remove('show');
  modal.style.opacity = 0;
  setTimeout(function(){ modal.style.display = 'none'; }, 350);
}

// How to Use Modal logic
function showHowToUseCard() {
  var modal = document.getElementById('howToUseModal');
  modal.classList.add('show');
  modal.style.display = 'flex';
  setTimeout(function(){ modal.style.opacity = 1; }, 10);
}
function closeHowToUseCard() {
  var modal = document.getElementById('howToUseModal');
  modal.classList.remove('show');
  modal.style.opacity = 0;
  setTimeout(function(){ modal.style.display = 'none'; }, 350);
}
document.getElementById('howToUseModal').addEventListener('click', function(e) {
  if (e.target === this) closeHowToUseCard();
});

// Close modal on outside click
document.getElementById('profileModal').addEventListener('click', function(e) {
  if (e.target === this) closeProfileCard();
});

// Remove mobile chat mode when resizing to wider screens
window.addEventListener('resize', function() {
  if (!isSmallScreen()) {
    document.body.classList.remove('mobile-chat-active');
    // restore lists
    document.getElementById('residentsList').style.display = '';
    document.getElementById('residentsSearchBox').style.display = '';
    document.getElementById('chatsList').style.display = '';
  }
});
</script>


<script>
document.addEventListener('DOMContentLoaded', function() {
  const ratingModal = document.getElementById("ratingModal");
  let selectedRating = 0;

  // Star click logic (unified)
  function updateStars(rating) {
    document.querySelectorAll('#starContainer .star').forEach(star => {
      const val = parseInt(star.getAttribute('data-value'));
      star.textContent = val <= rating ? '★' : '☆';
      star.style.color = val <= rating ? '#FFD700' : '#ccc';
    });
  }

  document.querySelectorAll('#starContainer .star').forEach(star => {
    star.addEventListener('click', function() {
      selectedRating = parseInt(this.getAttribute('data-value'));
      updateStars(selectedRating);
    });
    // Reset color on load
    star.style.color = '#ccc';
  });

  // Show modal when Done Chat is clicked - attach to button directly
  window.showRatingModal = function() {
    const doneBtn = document.getElementById("doneChatBtn");
    if (ratingModal) {
      document.getElementById("ratingReceiverId").value = currentUserId || "";
      selectedRating = 0;
      updateStars(0);
      document.getElementById('ratingComment').value = '';
      
      // Load previous reviews
      const reviewsList = document.getElementById('ratingModalReviewsList');
      reviewsList.innerHTML = '<div style="color:#adb5bd; text-align:center; padding:12px 0; font-size:0.85em;">Loading reviews...</div>';
      
      fetch('get_profile_comments.php?userid=' + encodeURIComponent(currentUserId))
        .then(res => res.json())
        .then(data => {
          if (data && data.length > 0) {
            reviewsList.innerHTML = data.map(c => {
              const rating = c.rating || 0;
              const stars = Array(5).fill(0).map((_, i) => 
                `<span style="color:${i < rating ? '#fbbf24' : '#d1d5db'}; font-size:12px;">★</span>`
              ).join('');
              
              return `<div style="margin-bottom:6px; padding:6px 8px; background:#fff; border-radius:6px; border-left:3px solid #28a745; font-size:0.75em;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3px;">
                  <span style="font-weight:600; color:#333; font-size:0.9em;">${c.name || 'Anonymous'}</span>
                  <div>${stars}</div>
                </div>
                <div style="color:#555; line-height:1.3; margin-bottom:2px;">${c.comment.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</div>
                <div style="color:#999; font-size:0.7em;">${c.date || ''}</div>
              </div>`;
            }).join('');
          } else {
            reviewsList.innerHTML = '<div style="color:#adb5bd; text-align:center; padding:12px 0; font-size:0.85em;">💬<br>No reviews yet</div>';
          }
        })
        .catch(() => {
          reviewsList.innerHTML = '<div style="color:#adb5bd; text-align:center; padding:12px 0; font-size:0.85em;">Failed to load reviews</div>';
        });
      
      ratingModal.style.display = "flex";
    }
  }

  // Submit rating
  window.submitRating = function() {
    if (!selectedRating || selectedRating < 1 || selectedRating > 5) {
      showToast("Please select a rating.", "warning");
      return;
    }
    const comment = document.getElementById('ratingComment').value;
    const receiverId = document.getElementById('ratingReceiverId').value;

    fetch('save_rating.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        receiver_id: receiverId,
        rating: selectedRating,
        comment: comment
      })
    })
    .then(res => res.json())
    .then(data => {
      showToast(data.message || data.status || 'Rating submitted successfully!', "success");
      closeRatingModal();
    })
    .catch(err => {
      console.error(err);
      showToast("Error saving rating. Please try again.", "error");
      closeRatingModal();
    });
  }

  window.closeRatingModal = function() {
    ratingModal.style.display = "none";
    selectedRating = 0;
    updateStars(0);
    document.getElementById('ratingComment').value = '';
  }
});

// Modern Toast Notification System
window.showToast = function(message, type = 'info') {
  const toast = document.createElement('div');
  toast.className = 'toast-notification toast-' + type;
  
  // Icon based on type
  let icon = '';
  if (type === 'success') {
    icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  } else if (type === 'error') {
    icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6l12 12" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  } else if (type === 'warning') {
    icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  } else {
    icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="#fff" stroke-width="2"/><path d="M12 16v-4m0-4h.01" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>';
  }
  
  toast.innerHTML = `
    <div class="toast-icon">${icon}</div>
    <div class="toast-message">${message}</div>
  `;
  
  document.body.appendChild(toast);
  
  // Trigger animation
  setTimeout(() => toast.classList.add('toast-show'), 10);
  
  // Auto remove after 3 seconds
  setTimeout(() => {
    toast.classList.remove('toast-show');
    setTimeout(() => toast.remove(), 300);
  }, 3000);
};

// Report Modal Functions
window.showReportModal = function() {
  const reportModal = document.getElementById("reportModal");
  if (reportModal && currentUserId) {
    // Reset form
    document.getElementById("reportReason").value = "";
    document.getElementById("reportDetails").value = "";
    reportModal.style.display = "flex";
  } else {
    showToast("Please select a user to report", "warning");
  }
}

window.closeReportModal = function() {
  const reportModal = document.getElementById("reportModal");
  if (reportModal) {
    reportModal.style.display = "none";
    document.getElementById("reportReason").value = "";
    document.getElementById("reportDetails").value = "";
  }
}

window.submitReport = function() {
  const reason = document.getElementById("reportReason").value;
  const details = document.getElementById("reportDetails").value;
  
  if (!reason) {
    showToast("Please select a reason for reporting", "warning");
    return;
  }
  
  if (!currentUserId) {
    showToast("No user selected to report", "error");
    return;
  }
  
  // Submit report
  fetch('submit_chat_report.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
      reported_id: currentUserId,
      reason: reason,
      details: details
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      showToast(data.message || 'Report submitted successfully', "success");
      closeReportModal();
    } else {
      showToast(data.error || 'Failed to submit report', "error");
    }
  })
  .catch(err => {
    console.error(err);
    showToast("Error submitting report. Please try again.", "error");
  });
}
</script>

</body>
<!-- Image Modal -->
<div id="imageModal" class="image-modal">
  <div class="image-modal-content">
    <button class="image-modal-close" onclick="closeImageModal()">×</button>
    <button class="image-modal-download" onclick="downloadImage()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
        <polyline points="7 10 12 15 17 10"></polyline>
        <line x1="12" y1="15" x2="12" y2="3"></line>
      </svg>
      Download
    </button>
    <img id="modalImage" src="" alt="Full size image">
  </div>
</div>

<!-- Unsend Confirmation Modal -->
<div id="unsendModal" class="unsend-modal">
  <div class="unsend-modal-content">
    <div class="unsend-modal-icon">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 6h18"></path>
        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
        <line x1="10" y1="11" x2="10" y2="17"></line>
        <line x1="14" y1="11" x2="14" y2="17"></line>
      </svg>
    </div>
    <h3>Unsend Message?</h3>
    <p>This message will be permanently deleted. This action cannot be undone.</p>
    <div class="unsend-modal-buttons">
      <button class="unsend-modal-btn unsend-modal-btn-confirm" onclick="confirmUnsend()">Yes, Unsend</button>
      <button class="unsend-modal-btn unsend-modal-btn-cancel" onclick="closeUnsendModal()">Cancel</button>
    </div>
  </div>
</div>

<!-- Modern Rating Modal -->
<div id="ratingModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); backdrop-filter:blur(4px); z-index:3000; align-items:center; justify-content:center;">
  <div class="rating-modal-content" style="
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius:24px;
    padding:2.5rem 2rem;
    max-width:420px;
    width:90%;
    text-align:center;
    box-shadow:0 20px 60px rgba(0,0,0,0.3);
    position:relative;
    animation: modalSlideIn 0.3s ease-out;
  ">
    <!-- Close button -->
    <button onclick="closeRatingModal()" style="
      position:absolute;
      top:16px;
      right:16px;
      background:transparent;
      border:none;
      font-size:1.8em;
      color:#999;
      cursor:pointer;
      width:32px;
      height:32px;
      display:flex;
      align-items:center;
      justify-content:center;
      border-radius:50%;
      transition:all 0.2s;
    " onmouseover="this.style.background='#f0f0f0'; this.style.color='#333'" onmouseout="this.style.background='transparent'; this.style.color='#999'">&times;</button>
    
    <!-- Icon -->
    <div style="
      width:64px;
      height:64px;
      background:linear-gradient(135deg, #28a745 0%, #20c997 100%);
      border-radius:50%;
      display:flex;
      align-items:center;
      justify-content:center;
      margin:0 auto 1.5rem auto;
      box-shadow:0 8px 20px rgba(40,167,69,0.3);
    ">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" fill="#fff"/>
      </svg>
    </div>
    
    <h2 style="
      color:#1a1a1a;
      margin-bottom:0.5rem;
      font-size:1.75em;
      font-weight:700;
      letter-spacing:-0.5px;
    ">Rate Your Chat</h2>
    
    <p style="
      color:#6c757d;
      margin-bottom:1.8rem;
      font-size:0.95em;
    ">How was your experience?</p>
    
    <!-- Star Rating -->
    <div id="starContainer" style="
      font-size:2.5rem;
      margin-bottom:1.8rem;
      display:flex;
      justify-content:center;
      gap:8px;
    ">
      <span class="star" data-value="1" style="cursor:pointer; transition:all 0.2s; color:#ddd;">★</span>
      <span class="star" data-value="2" style="cursor:pointer; transition:all 0.2s; color:#ddd;">★</span>
      <span class="star" data-value="3" style="cursor:pointer; transition:all 0.2s; color:#ddd;">★</span>
      <span class="star" data-value="4" style="cursor:pointer; transition:all 0.2s; color:#ddd;">★</span>
      <span class="star" data-value="5" style="cursor:pointer; transition:all 0.2s; color:#ddd;">★</span>
    </div>
    
    <!-- Comment Textarea -->
    <textarea id="ratingComment" placeholder="Add a comment (optional)" style="
      width:100%;
      min-height:80px;
      resize:vertical;
      border-radius:14px;
      border:2px solid #e9ecef;
      margin-bottom:1rem;
      padding:12px 16px;
      font-size:0.95em;
      font-family:inherit;
      transition:border-color 0.2s;
      background:#fff;
      box-sizing:border-box;
    " onfocus="this.style.borderColor='#28a745'" onblur="this.style.borderColor='#e9ecef'"></textarea>
    
    <!-- Previous Reviews Section -->
    <div id="ratingModalReviewsSection" style="width: 100%; margin-bottom: 1rem; text-align: left;">
      <h4 style="color: #28a745; font-size: 0.9em; margin-bottom: 8px; font-weight: 600; display: flex; align-items: center; gap: 6px;">
        💬 Previous Reviews
      </h4>
      <div id="ratingModalReviewsList" style="max-height: 150px; overflow-y: auto; padding-right: 4px;"></div>
    </div>
    
    <!-- Buttons -->
    <div style="display:flex; gap:12px; justify-content:center;">
      <button onclick="submitRating()" style="
        background:linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color:#fff;
        border:none;
        padding:12px 32px;
        border-radius:25px;
        font-weight:700;
        cursor:pointer;
        font-size:1em;
        box-shadow:0 4px 12px rgba(40,167,69,0.3);
        transition:all 0.2s;
        letter-spacing:0.3px;
      " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(40,167,69,0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(40,167,69,0.3)'">Submit</button>
      
      <button onclick="closeRatingModal()" style="
        background:#e9ecef;
        color:#495057;
        border:none;
        padding:12px 32px;
        border-radius:25px;
        font-weight:600;
        cursor:pointer;
        font-size:1em;
        transition:all 0.2s;
      " onmouseover="this.style.background='#dee2e6'" onmouseout="this.style.background='#e9ecef'">Cancel</button>
    </div>
  </div>
</div>

<!-- Report User Modal -->
<div id="reportModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); backdrop-filter:blur(4px); z-index:3000; align-items:center; justify-content:center;">
  <div class="report-modal-content" style="
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius:24px;
    padding:2.5rem 2rem;
    max-width:420px;
    width:90%;
    text-align:center;
    box-shadow:0 20px 60px rgba(0,0,0,0.3);
    position:relative;
    animation: modalSlideIn 0.3s ease-out;
  ">
    <!-- Close button -->
    <button onclick="closeReportModal()" style="
      position:absolute;
      top:16px;
      right:16px;
      background:transparent;
      border:none;
      font-size:1.8em;
      color:#999;
      cursor:pointer;
      width:32px;
      height:32px;
      display:flex;
      align-items:center;
      justify-content:center;
      border-radius:50%;
      transition:all 0.2s;
    " onmouseover="this.style.background='#f0f0f0'; this.style.color='#333'" onmouseout="this.style.background='transparent'; this.style.color='#999'">&times;</button>
    
    <!-- Icon -->
    <div style="
      width:64px;
      height:64px;
      background:linear-gradient(135deg, #dc3545 0%, #c82333 100%);
      border-radius:50%;
      display:flex;
      align-items:center;
      justify-content:center;
      margin:0 auto 1.5rem auto;
      box-shadow:0 8px 20px rgba(220,53,69,0.3);
    ">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
        <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <line x1="12" y1="9" x2="12" y2="13" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
        <line x1="12" y1="17" x2="12.01" y2="17" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </div>
    
    <h2 style="
      color:#1a1a1a;
      margin-bottom:0.5rem;
      font-size:1.75em;
      font-weight:700;
      letter-spacing:-0.5px;
    ">Report User</h2>
    
    <p style="
      color:#6c757d;
      margin-bottom:1.8rem;
      font-size:0.95em;
    ">Help us keep the community safe</p>
    
    <!-- Report Reason Dropdown -->
    <select id="reportReason" style="
      width:100%;
      padding:12px 16px;
      border-radius:14px;
      border:2px solid #e9ecef;
      margin-bottom:1rem;
      font-size:0.95em;
      font-family:inherit;
      transition:border-color 0.2s;
      background:#fff;
      box-sizing:border-box;
      cursor:pointer;
    " onfocus="this.style.borderColor='#dc3545'" onblur="this.style.borderColor='#e9ecef'">
      <option value="">Select a reason...</option>
      <option value="Harassment">Harassment or bullying</option>
      <option value="Spam">Spam or scam</option>
      <option value="Inappropriate Content">Inappropriate content or behavior</option>
      <option value="Fake Profile">Fake profile or impersonation</option>
      <option value="Offensive Language">Offensive or abusive language</option>
      <option value="Fraudulent Activity">Fraudulent activity or scam</option>
      <option value="Threatening Behavior">Threatening or violent behavior</option>
      <option value="Sexual Harassment">Sexual harassment or misconduct</option>
      <option value="Hate Speech">Hate speech or discrimination</option>
      <option value="Privacy Violation">Privacy violation or doxxing</option>
      <option value="Misinformation">Spreading false information</option>
      <option value="Unprofessional Conduct">Unprofessional conduct</option>
      <option value="Other">Other (please specify in details)</option>
    </select>
    
    <!-- Details Textarea -->
    <textarea id="reportDetails" placeholder="Provide additional details (optional)" style="
      width:100%;
      min-height:100px;
      resize:vertical;
      border-radius:14px;
      border:2px solid #e9ecef;
      margin-bottom:1.5rem;
      padding:12px 16px;
      font-size:0.95em;
      font-family:inherit;
      transition:border-color 0.2s;
      background:#fff;
      box-sizing:border-box;
    " onfocus="this.style.borderColor='#dc3545'" onblur="this.style.borderColor='#e9ecef'"></textarea>
    
    <!-- Warning Message -->
    <div style="
      background:#fff3cd;
      border:1px solid #ffc107;
      border-radius:10px;
      padding:10px 12px;
      margin-bottom:1.5rem;
      text-align:left;
    ">
      <p style="margin:0; color:#856404; font-size:0.85em; line-height:1.4;">
        <strong>⚠️ Note:</strong> False reports may result in action against your account. All reports are reviewed by administrators.
      </p>
    </div>
    
    <!-- Buttons -->
    <div style="display:flex; gap:12px; justify-content:center;">
      <button onclick="submitReport()" style="
        background:linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color:#fff;
        border:none;
        padding:12px 32px;
        border-radius:25px;
        font-weight:700;
        cursor:pointer;
        font-size:1em;
        box-shadow:0 4px 12px rgba(220,53,69,0.3);
        transition:all 0.2s;
        letter-spacing:0.3px;
      " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(220,53,69,0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(220,53,69,0.3)'">Submit Report</button>
      
      <button onclick="closeReportModal()" style="
        background:#e9ecef;
        color:#495057;
        border:none;
        padding:12px 32px;
        border-radius:25px;
        font-weight:600;
        cursor:pointer;
        font-size:1em;
        transition:all 0.2s;
      " onmouseover="this.style.background='#dee2e6'" onmouseout="this.style.background='#e9ecef'">Cancel</button>
    </div>
  </div>
</div>

<style>
@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: scale(0.9) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.star:hover {
  transform: scale(1.15);
  filter: drop-shadow(0 2px 4px rgba(255,193,7,0.4));
}

@media (max-width: 480px) {
  .rating-modal-content {
    padding: 2rem 1.5rem !important;
  }
  
  #starContainer {
    font-size: 2rem !important;
    gap: 4px !important;
  }
}
</style>

<!-- Automatic logout script for logged-in users -->
<?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
<script src="auto_logout.js"></script>
<?php endif; ?>

</html>

