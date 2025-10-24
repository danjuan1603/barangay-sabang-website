<?php
include 'config.php';
session_start();
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    echo '<div class="alert alert-danger text-center">⛔ Admin login required.</div>';
    exit;
}
$start = isset($_GET['start']) ? $_GET['start'] : '';
$end = isset($_GET['end']) ? $_GET['end'] : '';
$where = '';
if ($start && $end) {
    $where = "WHERE (login_time BETWEEN '" . $conn->real_escape_string($start) . "' AND '" . $conn->real_escape_string($end) . "' OR action_time BETWEEN '" . $conn->real_escape_string($start) . "' AND '" . $conn->real_escape_string($end) . "')";
}
$sql = "SELECT username, login_time, logout_time, action, action_time FROM admin_logs $where ORDER BY id DESC";
$result = $conn->query($sql);
?>
<table class="table table-bordered table-hover align-middle mb-0" style="background:#fff;">
  <thead style="background:#e8f5e9; color:#1b5e20; font-weight:600; letter-spacing:0.5px; position:sticky; top:0; z-index:1;">
    <tr>
      <th>Username</th>
      <th>Login Time</th>
      <th>Logout Time</th>
      <th>Action</th>
      <th>Action Time</th>
    </tr>
  </thead>
  <tbody>
  <?php if ($result && $result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($row['username']) ?></td>
        <td><?= !empty($row['login_time']) ? date("Y-m-d H:i:s", strtotime($row['login_time'])) : '-' ?></td>
        <td><?= !empty($row['logout_time']) ? date("Y-m-d H:i:s", strtotime($row['logout_time'])) : '-' ?></td>
        <td><?= !empty($row['action']) ? htmlspecialchars($row['action']) : '-' ?></td>
        <td><?= !empty($row['action_time']) ? date("Y-m-d H:i:s", strtotime($row['action_time'])) : '-' ?></td>
      </tr>
    <?php endwhile; ?>
  <?php else: ?>
    <tr><td colspan="5" style="text-align:center;">No logs found.</td></tr>
  <?php endif; ?>
  </tbody>
</table>