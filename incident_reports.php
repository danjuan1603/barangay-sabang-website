<?php
session_start();
include 'config.php'; // DB connection
$admin_username = $_SESSION['admin_username'] ?? 'Unknown';

$view = $_GET['view'] ?? 'active';

// --- Handle Search & Filter ---
$search = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';

// --- Update Status (with archive on Resolved) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = intval($_POST['id']);
    $status = $_POST['status'];
    // Normalize legacy 'Priority' status to 'In Review'
    if ($status === 'Priority') $status = 'In Review';

    $comment = trim($_POST['comment'] ?? '');
    if ($status === "Resolved") {
        // Check if the incident exists
        $check = $conn->prepare("SELECT * FROM incident_reports WHERE id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $incident = $check->get_result()->fetch_assoc();
        $check->close();

        if ($incident) {
            $conn->begin_transaction();
                try {
                    // Check for admin_comment column
                    $hasCommentCol = false;
                    $dbName = $conn->query('select database()')->fetch_row()[0];
                    $colCheck = $conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'archived_incident_reports' AND COLUMN_NAME = 'admin_comment' LIMIT 1");
                    $colCheck->bind_param('s', $dbName);
                    $colCheck->execute();
                    $colRes = $colCheck->get_result();
                    if ($colRes && $colRes->num_rows > 0) $hasCommentCol = true;
                    $colCheck->close();

                    if ($hasCommentCol) {
                        $insert = $conn->prepare("\
                            INSERT INTO archived_incident_reports \
                                (userid, incident_type, contact_number, incident_description, incident_image, created_at, status, date_ended, seen, admin_comment)\
                            VALUES (?, ?, ?, ?, ?, ?, 'Resolved', NOW(), ?, ?)\
                        ");
                        if (!$insert) throw new Exception("Prepare failed: " . $conn->error);
                        // userid (i), incident_type (s), contact_number (s), incident_description (s), incident_image (s), created_at (s), seen (i), comment (s)
                        $insert->bind_param(
                            "isssssis",
                            $incident['userid'],
                            $incident['incident_type'],
                            $incident['contact_number'],
                            $incident['incident_description'],
                            $incident['incident_image'],
                            $incident['created_at'],
                            $incident['seen'],
                            $comment
                        );
                    } else {
                        $insert = $conn->prepare("\
                            INSERT INTO archived_incident_reports \
                                (userid, incident_type, contact_number, incident_description, incident_image, created_at, status, date_ended, seen)\
                            VALUES (?, ?, ?, ?, ?, ?, 'Resolved', NOW(), ?)\
                        ");
                        if (!$insert) throw new Exception("Prepare failed: " . $conn->error);
                        $insert->bind_param(
                            "isssssi",
                            $incident['userid'],
                            $incident['incident_type'],
                            $incident['contact_number'],
                            $incident['incident_description'],
                            $incident['incident_image'],
                            $incident['created_at'],
                            $incident['seen']
                        );
                    }
                    if (!$insert->execute()) {
                        throw new Exception("Insert failed: " . $insert->error);
                    }
                    $insert->close();

                // Delete from active table
                $delete = $conn->prepare("DELETE FROM incident_reports WHERE id = ?");
                $delete->bind_param("i", $id);
                $delete->execute();
                $delete->close();

                // Log action
                $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
                $logText = "Archived resolved incident report ID $id";
                $log->bind_param("ss", $admin_username, $logText);
                $log->execute();
                $log->close();

                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
                die("❌ Archiving failed: " . $e->getMessage());
            }
        } else {
            die("❌ Incident not found. Cannot archive.");
        }
    } else {
    // Update status for non-archived reports. Validate allowed values defensively.
    $allowed = ['Pending','In Review','Resolved'];
    if (!in_array($status, $allowed)) $status = 'Pending';
    $update = $conn->prepare("UPDATE incident_reports SET status = ? WHERE id = ?");
    $update->bind_param("si", $status, $id);
    $update->execute();
    $update->close();
    }

    $redirectUrl = basename($_SERVER['PHP_SELF']) . '?' . http_build_query($_GET);
    header("Location: $redirectUrl");
    exit;
}

// --- Delete Report (from active table only) ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($view === 'archived') {
        $stmt = $conn->prepare("SELECT incident_type FROM archived_incident_reports WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $stmtDel = $conn->prepare("DELETE FROM archived_incident_reports WHERE id=?");
        $stmtDel->bind_param("i", $id);
        if ($stmtDel->execute()) {
            // --- Log delete action ---
            $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
            $logText = "Deleted archived_incident_report ID $id (Type: {$row['incident_type']})";
            $log->bind_param("ss", $admin_username, $logText);
            $log->execute();
            $log->close();

            echo "
            <div id='popup' style='
                position: fixed; 
                top: 0; left: 0; 
                width: 100%; height: 100%; 
                background: rgba(0,0,0,0.5); 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                z-index: 9999;
            '>
              <div style='
                  background: #fff; 
                  padding: 20px 30px; 
                  border-radius: 10px; 
                  text-align: center; 
                  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                  font-family: Arial, sans-serif;
              '>
                <h3 style='margin-bottom: 10px;'>🗑️ Archived Report Deleted</h3>
                <p>The archived incident report has been successfully removed.</p>
                <button onclick=\"window.location.href='incident_reports.php?view=archived'\" 
                    style='
                        margin-top: 15px; 
                        padding: 8px 16px; 
                        background: #2196f3; 
                        color: white; 
                        border: none; 
                        border-radius: 5px; 
                        cursor: pointer;
                    '>
                  OK
                </button>
              </div>
            </div>";
            exit;
        }
        $stmtDel->close();
    } else {
        $stmt = $conn->prepare("SELECT incident_type FROM incident_reports WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $stmtDel = $conn->prepare("DELETE FROM incident_reports WHERE id=?");
        $stmtDel->bind_param("i", $id);
        if ($stmtDel->execute()) {
            // --- Log delete action ---
            $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
            $logText = "Deleted incident_report ID $id (Type: {$row['incident_type']})";
            $log->bind_param("ss", $admin_username, $logText);
            $log->execute();
            $log->close();

            echo "
            <div id='popup' style='
                position: fixed; 
                top: 0; left: 0; 
                width: 100%; height: 100%; 
                background: rgba(0,0,0,0.5); 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                z-index: 9999;
            '>
              <div style='
                  background: #fff; 
                  padding: 20px 30px; 
                  border-radius: 10px; 
                  text-align: center; 
                  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                  font-family: Arial, sans-serif;
              '>
                <h3 style='margin-bottom: 10px;'>🗑️ Report Deleted</h3>
                <p>The incident report has been successfully removed.</p>
                <button onclick=\"window.location.href='incident_reports.php'\" 
                    style='
                        margin-top: 15px; 
                        padding: 8px 16px; 
                        background: #2196f3; 
                        color: white; 
                        border: none; 
                        border-radius: 5px; 
                        cursor: pointer;
                    '>
                  OK
                </button>
              </div>
            </div>";
            exit;
        }
        $stmtDel->close();
    }
}

// --- Build SQL depending on view (active or archived) ---
if ($view === 'archived') {
    $sql = "SELECT * FROM archived_incident_reports WHERE 1=1";
} else {
    $sql = "SELECT * FROM incident_reports WHERE 1=1";
}
$params = [];
$types = "";

// Search
if (!empty($search)) {
    $sql .= " AND (id LIKE ? OR incident_type LIKE ? OR incident_description LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like]);
    $types .= "sss";
}

// Status
if (!empty($filter_status)) {
    $sql .= " AND status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

// Date range
if (!empty($start_date) && !empty($end_date)) {
    $sql .= " AND DATE(created_at) BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
} elseif (!empty($start_date)) {
    $sql .= " AND DATE(created_at) >= ?";
    $params[] = $start_date;
    $types .= "s";
} elseif (!empty($end_date)) {
    $sql .= " AND DATE(created_at) <= ?";
    $params[] = $end_date;
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) die("❌ SQL Prepare failed (Select): " . $conn->error);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Incident Reports</title>
    <!-- ✅ Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- ✅ Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card { border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .table thead { background: #2c3e50; color: white; }
        .badge { font-size: 0.9em; }
    </style>
</head>
<body>
<div class="container py-4">
    <a href="admin_dashboard.php" class="btn btn-secondary mb-3">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>

    <div class="card p-4">
        <h2 class="mb-4 text-center text-primary">📋 Manage Incident Reports</h2>

        <!-- ✅ Toggle view -->
        <div class="mb-3 text-center">
            <a href="incident_reports.php?view=active" class="btn btn-outline-primary <?= $view === 'active' ? 'active' : '' ?>">Active</a>
            <a href="incident_reports.php?view=archived" class="btn btn-outline-secondary <?= $view === 'archived' ? 'active' : '' ?>">Archived</a>
        </div>

        <!-- 🔍 Filters -->
        <form method="GET" class="row g-2 mb-4">
            <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>"> <!-- Add this line -->
            <div class="col-md-3">
                <input type="text" class="form-control" name="search" placeholder="Search ID, type, or description"
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="Pending" <?= ($filter_status=="Pending") ? "selected" : "" ?>>Pending</option>
                    <option value="In Review" <?= ($filter_status=="In Review") ? "selected" : "" ?>>In Review</option>
                    <option value="Resolved" <?= ($filter_status=="Resolved") ? "selected" : "" ?>>Resolved</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
            </div>
            <div class="col-md-3 d-grid gap-2 d-md-flex">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Apply</button>
                <a href="<?= basename($_SERVER['PHP_SELF']) ?>?view=<?= htmlspecialchars($view) ?>" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>

<!-- 📑 Table -->
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
        <tr>
            <th>ID</th>
            <th>User ID</th>
            <th>Type</th>
            <th>Contact Number</th>
            <th>Photo</th>
            <th>Description</th>
            <th>Comment</th>
            <th>Date Submitted</th>
            <th>Status</th>
            <?php if ($view === 'archived'): ?>
                <th>Date Ended</th>
                <th class="text-center">Actions</th>
            <?php else: ?>
                <th class="text-center">Actions</th>
            <?php endif; ?>
        </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= $row['userid'] ?></td> 
                    <td><?= htmlspecialchars($row['incident_type']) ?></td>
                    <td><?= htmlspecialchars($row['contact_number']) ?></td> <!-- ✅ Show number -->

                    <td>
                        <?php if (!empty($row['incident_image'])): 
                            $src = htmlspecialchars($row['incident_image']);
                        ?>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#photoModal<?= $row['id'] ?>">
                                <img src="<?= $src ?>" alt="Incident Photo" 
                                     style="width:50px;height:50px;object-fit:cover;border-radius:5px;">
                            </a>

                            <!-- Modal -->
                            <div class="modal fade" id="photoModal<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Incident Photo (ID: <?= $row['id'] ?>)</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body text-center">
                                            <img src="<?= $src ?>" alt="Incident Photo" 
                                                 style="max-width: 100%; height: auto; border-radius:8px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <span class="text-muted">No photo</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php 
                        $desc = htmlspecialchars($row['incident_description']);
                        $shortDesc = (strlen($desc) > 50) ? substr($desc, 0, 50) . "…" : $desc;
                        ?>
                        <?= $shortDesc ?>
                        <?php if (strlen($desc) > 50): ?>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#descModal<?= $row['id'] ?>">
                                <small class="text-primary">View</small>
                            </a>

                            <!-- Modal -->
                            <div class="modal fade" id="descModal<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Incident Description (ID: <?= $row['id'] ?>)</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p><?= nl2br($desc) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                                        </td>
                                        <td>
                                                <?php
                                                $adminWho = isset($row['admin_who_resolved']) ? trim($row['admin_who_resolved']) : '';
                                                $adminComment = isset($row['admin_comment']) ? trim($row['admin_comment']) : '';
                                                $adminWhoEsc = $adminWho ? htmlspecialchars($adminWho) : '';
                                                $adminCommentEsc = $adminComment ? nl2br(htmlspecialchars($adminComment)) : '';
                                                ?>
                                                <?php if ($adminWhoEsc): ?>
                                                    <div><strong><?= $adminWhoEsc ?></strong></div>
                                                <?php else: ?>
                                                    <div class="text-muted">-</div>
                                                <?php endif; ?>
                                                <?php if ($adminCommentEsc): ?>
                                                    <div class="text-muted" style="max-width:320px; white-space:pre-wrap;"><?= $adminCommentEsc ?></div>
                                                <?php endif; ?>
                                        </td>
                                        <td><?= date("M d, Y h:i A", strtotime($row['created_at'])) ?></td>
                    <td>
                        <?php if ($view === 'active'): ?>
        <form method="POST" action="<?= basename($_SERVER['PHP_SELF']) . '?' . http_build_query($_GET) ?>" onsubmit="return handleResolveForm(this);">
            <input type="hidden" name="id" value="<?= $row['id'] ?>">
            <input type="hidden" name="update_status" value="1">
            <input type="hidden" name="comment" value="" />
            <select class="form-select form-select-sm" name="status" onchange="
                if (this.value === 'Resolved') {
                    var c = prompt('Please enter a short resolution comment (required):');
                    if (c === null) { this.value = '<?= htmlspecialchars($row['status']) ?>'; return false; }
                    if (c.trim() === '') { alert('Resolution comment cannot be empty.'); this.value = '<?= htmlspecialchars($row['status']) ?>'; return false; }
                    this.form.comment.value = c;
                    this.form.submit();
                } else {
                    this.form.submit();
                }
            ">
                <option value="Pending" <?= ($row['status']=="Pending") ? "selected" : "" ?>>Pending</option>
                <option value="In Review" <?= ($row['status']=="In Review") ? "selected" : "" ?>>In Review</option>
                <option value="Resolved">Resolved</option>
            </select>
        </form>
    <?php else: ?>
        <span class="badge bg-success"><?= htmlspecialchars($row['status']) ?></span>
    <?php endif; ?>
                    </td>
                    <td><?= $row['date_ended'] ? date("M d, Y h:i A", strtotime($row['date_ended'])) : '-' ?></td>
                    <td class="text-center">
                        <a href="<?= basename($_SERVER['PHP_SELF']) . '?delete=' . $row['id'] . '&view=' . $view . '&' . http_build_query($_GET) ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Delete this report?')">
                            <i class="bi bi-trash"></i> Delete
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php else: ?>
            <tr><td colspan="11" class="text-center text-muted">No incident reports found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>


<!-- ✅ Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


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


</script>
<!-- Resolve modal for incident_reports.php -->
<div class="modal fade" id="resolveModalForm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add resolution comment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Please explain briefly how the incident was resolved. (required)</p>
                <textarea id="resolveFormComment" class="form-control" rows="4" maxlength="1000"></textarea>
                <div class="form-text text-end"><small id="resolveFormCharCount">0/1000</small></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="resolveFormSave" class="btn btn-primary">Save & Resolve</button>
            </div>
        </div>
    </div>
</div>

<script>
// Hook select change for form-based resolves
document.querySelectorAll('form input[name="update_status"]').forEach(function(f){
    // nothing; we will attach to selects directly
});
document.querySelectorAll('select[name="status"]').forEach(function(sel){
    sel.dataset.prev = sel.value;
    sel.addEventListener('change', function(e){
        if (this.value === 'Resolved') {
            // show modal, store form reference
            window.__resolveFormPending = { form: this.form, select: this };
            var modalNode = document.getElementById('resolveModalForm');
            if (modalNode && modalNode.parentElement !== document.body) document.body.appendChild(modalNode);
            document.getElementById('resolveFormComment').value = '';
            document.getElementById('resolveFormCharCount').textContent = '0/1000';
                        var m = new bootstrap.Modal(modalNode);
                                    m.show();
                                    setTimeout(function(){
                                            const backs = Array.from(document.querySelectorAll('.modal-backdrop'));
                                            if (backs.length > 1) {
                                                for (let i = 0; i < backs.length - 1; i++) try{ backs[i].parentNode.removeChild(backs[i]); }catch(e){}
                                            }
                                            const back = document.querySelector('.modal-backdrop.show');
                                            if (back) back.style.zIndex = '20040';
                                            try { document.getElementById('resolveFormComment').focus(); } catch(e){}
                                            try {
                                                const rect = document.getElementById('resolveModalForm').getBoundingClientRect();
                                                const cx = rect.left + rect.width/2, cy = rect.top + rect.height/2;
                                                const els = document.elementsFromPoint(cx, cy);
                                                window.__disabledOverlaysForm = [];
                                                els.forEach(function(el){
                                                    if (el !== document.getElementById('resolveModalForm') && el.closest('.modal') == null && window.getComputedStyle(el).pointerEvents !== 'none') {
                                                        const z = parseInt(window.getComputedStyle(el).zIndex) || 0;
                                                        if (z > 1000) { el.__oldPointer = el.style.pointerEvents; el.style.pointerEvents = 'none'; window.__disabledOverlaysForm.push(el); }
                                                    }
                                                });
                                            } catch(e){}
                                    }, 120);
        } else {
            // submit immediately
            this.form.submit();
        }
    });
});
// char count
var rtxt = document.getElementById('resolveFormComment');
if (rtxt) rtxt.addEventListener('input', function(){ document.getElementById('resolveFormCharCount').textContent = this.value.length + '/1000'; });
document.getElementById('resolveFormSave').addEventListener('click', function(){
    var pending = window.__resolveFormPending;
    if (!pending) return;
    var comment = document.getElementById('resolveFormComment').value.trim();
    if (!comment) { alert('Resolution comment cannot be empty.'); return; }
    // put comment into hidden input and submit
    if (!pending.form.comment) {
        var inp = document.createElement('input'); inp.type='hidden'; inp.name='comment'; pending.form.appendChild(inp);
    }
    pending.form.comment.value = comment;
    pending.form.submit();
});
// If modal closed without saving, revert select
document.getElementById('resolveModalForm').addEventListener('hidden.bs.modal', function(){
        var pending = window.__resolveFormPending;
        if (pending && pending.select) pending.select.value = pending.select.dataset.prev;
        window.__resolveFormPending = null;
        try {
            if (window.__disabledOverlaysForm && window.__disabledOverlaysForm.length) {
                window.__disabledOverlaysForm.forEach(function(el){ try{ el.style.pointerEvents = el.__oldPointer || ''; delete el.__oldPointer; }catch(e){} });
                window.__disabledOverlaysForm = null;
            }
        } catch(e){}
});
</script>
</body>
</html>
</html></body><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


