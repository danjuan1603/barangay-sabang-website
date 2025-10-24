<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    echo "⛔ You must be logged in to change your password. <a href='login.html'>Login</a>";
    exit;
}

$username = $_SESSION['admin_username'];
$file = 'admins.json';
$admins = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

$feedback = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($new !== $confirm) {
        $feedback = "<div class='error'>❌ New passwords do not match.</div>";
    } elseif ($username === 'admin') {
        if ($current !== '1234') {
            $feedback = "<div class='error'>❌ Current password is incorrect.</div>";
        } else {
            $feedback = "<div class='success'>✅ Main admin password is hardcoded and cannot be changed here.</div>";
        }
    } else {
        $found = false;
        foreach ($admins as &$admin) {
            if ($admin['username'] === $username) {
                if ($admin['password'] !== $current) {
                    $feedback = "<div class='error'>❌ Current password is incorrect.</div>";
                } else {
                    $admin['password'] = $new;
                    $found = true;
                }
                break;
            }
        }

        if ($found) {
            file_put_contents($file, json_encode($admins, JSON_PRETTY_PRINT));
            $feedback = "<div class='success'>✅ Password changed successfully.</div>";
        } else {
            $feedback = "<div class='error'>❌ Admin not found.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Change Password</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f4f4;
            padding: 40px;
        }
        .container {
            background: #ffffff;
            padding: 25px 30px;
            max-width: 400px;
            margin: auto;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 128, 0, 0.2);
        }
        h2 {
            color: #008000;
            text-align: center;
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }
        input[type="password"],
        input[type="submit"],
        .back-btn {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            border-radius: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background-color: #008000;
            color: white;
            border: none;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        input[type="submit"]:hover {
            background-color: #006400;
        }
        .back-btn {
            margin-top: 15px;
            background-color: #ccc;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            color: #000;
            font-weight: bold;
        }
        .back-btn:hover {
            background-color: #aaa;
        }
        .success {
            background-color: #d4edda;
            padding: 10px;
            border-left: 5px solid #28a745;
            margin-top: 15px;
            border-radius: 4px;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            padding: 10px;
            border-left: 5px solid #dc3545;
            margin-top: 15px;
            border-radius: 4px;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Change Password<br><small>(<?= htmlspecialchars($username) ?>)</small></h2>

        <?= $feedback ?>

        <form method="POST">
            <label>Current Password:</label>
            <input type="password" name="current_password" required>

            <label>New Password:</label>
            <input type="password" name="new_password" required>

            <label>Confirm New Password:</label>
            <input type="password" name="confirm_password" required>

            <input type="submit" value="Change Password">
        </form>

        <a class="back-btn" href="dashboard.php">⬅ Back to Dashboard</a>
    </div>
</body>
</html>
