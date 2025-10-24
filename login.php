<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    /* ==================================================
       UNIFIED LOGIN (try admin first, then resident/user)
    ================================================== */
    $userid   = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $captcha  = strtoupper(trim($_POST['captcha'] ?? ''));

    // If both username and password provided, attempt unified authentication
    if (!empty($userid) && !empty($password)) {
        // CAPTCHA check for resident/user logins only (admins bypass captcha when using this form)
        if (!isset($_SESSION['captcha_code']) || $captcha !== $_SESSION['captcha_code']) {
            // If captcha was not submitted (empty) and admin credentials might be used, allow admin attempt first
            // But keep strict captcha requirement for resident logins
            // We'll only redirect on captcha mismatch when resident login path is taken below
        }

        // 1) Try main_admin table (super admin)
        $sql = "SELECT * FROM main_admin WHERE username=? AND password=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $userid, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            // Set admin session flags
            $_SESSION['admin_loggedin'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['role'] = $admin['role'] ?? 'super';
            $_SESSION['admin_username'] = $admin['username'];

            // update online status
            $update_status = $conn->prepare("UPDATE main_admin SET is_online = 1, last_active = NOW() WHERE id = ?");
            $update_status->bind_param("i", $admin['id']);
            $update_status->execute();
            $update_status->close();

            // insert login log
            $log = $conn->prepare("INSERT INTO admin_logs (username, login_time) VALUES (?, NOW())");
            $log->bind_param("s", $admin['username']);
            $log->execute();
            $_SESSION['log_id'] = $conn->insert_id;
            $log->close();

            header("Location: admin_dashboard.php?login=success");
            exit();
        }

        // 2) Try admin_accounts (regular admin)
        $sql = "SELECT * FROM admin_accounts WHERE admin_id=? AND admin_password=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $userid, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $_SESSION['admin_loggedin'] = true;
            $_SESSION['admin_id'] = $userid;
            $_SESSION['role'] = 'regular';
            $_SESSION['admin_username'] = $userid;

            $update_status = $conn->prepare("UPDATE admin_accounts SET is_online = 1, last_active = NOW() WHERE admin_id = ?");
            $update_status->bind_param("s", $userid);
            $update_status->execute();
            $update_status->close();

            $log = $conn->prepare("INSERT INTO admin_logs (username, login_time) VALUES (?, NOW())");
            $log->bind_param("s", $userid);
            $log->execute();
            $_SESSION['log_id'] = $conn->insert_id;
            $log->close();

            header("Location: admin_dashboard.php?login=success");
            exit();
        }

        // 3) Not admin — proceed to resident/user login path
        // CAPTCHA must match for resident login
        if (!isset($_SESSION['captcha_code']) || $captcha !== $_SESSION['captcha_code']) {
            header("Location: index.php?login=fail&reason=captcha");
            exit();
        }

        $sql = "SELECT ua.*, r.surname FROM useraccounts ua LEFT JOIN residents r ON ua.userid = r.unique_id WHERE ua.userid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $userid);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (is_null($user['password'])) {
                header("Location: index.php?set_password=required&userid=" . urlencode($userid));
                exit();
            }

            if ($user['password'] === $password) {
                $_SESSION['loggedin'] = true;
                $_SESSION['userid'] = $userid;
                $_SESSION['surname'] = $user['surname'] ?? '';

                $update_status = $conn->prepare("UPDATE useraccounts SET is_online = 1, last_active = NOW() WHERE userid = ?");
                $update_status->bind_param("s", $userid);
                $update_status->execute();
                $update_status->close();

                header("Location: index.php?login=success");
                exit();
            } else {
                header("Location: index.php?login=fail");
                exit();
            }
        } else {
            header("Location: index.php?login=notfound");
            exit();
        }
    }

    /* ==================================================
       NEW USER REGISTRATION (set password for existing NULL accounts)
    ================================================== */
    $new_user     = trim($_POST['new_username'] ?? '');
    $new_pass     = trim($_POST['new_password'] ?? '');
    $confirm_pass = trim($_POST['confirm_password'] ?? '');

    if (!empty($new_user) && !empty($new_pass) && !empty($confirm_pass)) {
        if ($new_pass !== $confirm_pass) {
            header("Location: index.php?register=nomatch");
            exit();
        }

        $sql = "SELECT ua.*, r.surname 
                FROM useraccounts ua
                LEFT JOIN residents r ON ua.userid = r.unique_id
                WHERE ua.userid=? AND ua.password IS NULL";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $new_user);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $update = $conn->prepare("UPDATE useraccounts SET password=? WHERE userid=?");
            $update->bind_param("ss", $new_pass, $new_user);
            $update->execute();

            $user = $result->fetch_assoc();

            $_SESSION['loggedin'] = true;
            $_SESSION['userid']   = $new_user;
            $_SESSION['surname']  = $user['surname'] ?? '';
            
            // ✅ Set user as online and update last_active
            $update_status = $conn->prepare("UPDATE useraccounts SET is_online = 1, last_active = NOW() WHERE userid = ?");
            $update_status->bind_param("s", $new_user);
            $update_status->execute();
            $update_status->close();
            
            header("Location: index.php?register=success");
            exit();
        } else {
            header("Location: index.php?register=fail");
            exit();
        }
    }

    /* ==================================================
   ADMIN LOGIN
================================================== */
$admin_user = trim($_POST['admin_username'] ?? '');
$admin_pass = trim($_POST['admin_password'] ?? '');

if (!empty($admin_user) && !empty($admin_pass)) {
    // ✅ First check main_admin (super admin)
    $sql = "SELECT * FROM main_admin WHERE username=? AND password=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $admin_user, $admin_pass);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $admin = $result->fetch_assoc();

        $_SESSION['admin_loggedin'] = true;
        $_SESSION['admin_id']       = $admin['id'];
        $_SESSION['role']           = $admin['role'];
        $_SESSION['admin_username'] = $admin['username'];

        // ✅ Set admin as online and update last_active
        $update_status = $conn->prepare("UPDATE main_admin SET is_online = 1, last_active = NOW() WHERE id = ?");
        $update_status->bind_param("i", $admin['id']);
        $update_status->execute();
        $update_status->close();

        // ✅ Insert login log (username + login_time only)
        $log = $conn->prepare("
            INSERT INTO admin_logs (username, login_time) 
            VALUES (?, NOW())
        ");
        $log->bind_param("s", $admin['username']);
        $log->execute();
        $_SESSION['log_id'] = $conn->insert_id;
        $log->close();

        header("Location: admin_dashboard.php?login=success");
        exit();
    }

    // ✅ Else check regular admin_accounts
    $sql = "SELECT * FROM admin_accounts WHERE admin_id=? AND admin_password=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $admin_user, $admin_pass);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $_SESSION['admin_loggedin'] = true;
        $_SESSION['admin_id']       = $admin_user;
        $_SESSION['role']           = "regular";
        $_SESSION['admin_username'] = $admin_user;

        // ✅ Set admin as online and update last_active
        $update_status = $conn->prepare("UPDATE admin_accounts SET is_online = 1, last_active = NOW() WHERE admin_id = ?");
        $update_status->bind_param("s", $admin_user);
        $update_status->execute();
        $update_status->close();

        // ✅ Insert login log (username + login_time only)
        $log = $conn->prepare("
            INSERT INTO admin_logs (username, login_time) 
            VALUES (?, NOW())
        ");
        $log->bind_param("s", $admin_user);
        $log->execute();
        $_SESSION['log_id'] = $conn->insert_id;
        $log->close();


            header("Location: admin_dashboard.php?login=success");
            exit();
        } else {
            header("Location: index.php?admin_login=fail");
            exit();
        }
    }
}
?>
