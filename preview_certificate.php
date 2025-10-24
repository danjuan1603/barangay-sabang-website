<?php
session_start();
include 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_username'])) {
    header("Location: login.php");
    exit;
}

$admin_username = $_SESSION['admin_username'];
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die("Invalid certificate ID.");
}

// Handle form submission for updating certificate data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_certificate'])) {
    $purpose = $_POST['purpose'] ?? '';
    $address = $_POST['address'] ?? '';
    $age = intval($_POST['age'] ?? 0);
    $sex = $_POST['sex'] ?? '';
    $citizenship = $_POST['citizenship'] ?? '';
    $civil_status = $_POST['civil_status'] ?? '';
    
    // Update the certificate request
    $updateSql = "UPDATE certificate_requests SET purpose = ? WHERE id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("si", $purpose, $id);
    $stmt->execute();
    $stmt->close();
    
    // Update resident information
    $cert = $conn->query("SELECT resident_unique_id FROM certificate_requests WHERE id = $id")->fetch_assoc();
    if ($cert) {
        $updateResident = "UPDATE residents SET address = ?, age = ?, sex = ?, citizenship = ?, civil_status = ? WHERE unique_id = ?";
        $stmt2 = $conn->prepare($updateResident);
        $stmt2->bind_param("sissss", $address, $age, $sex, $citizenship, $civil_status, $cert['resident_unique_id']);
        $stmt2->execute();
        $stmt2->close();
    }
    
    // Log the action
    $action_text = "Edited certificate ID: $id before printing";
    $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
    $log->bind_param("ss", $admin_username, $action_text);
    $log->execute();
    $log->close();
    
    // Redirect back to preview with success message
    header("Location: preview_certificate.php?id=$id&updated=1");
    exit;
}

// Fetch certificate data
$sql = "SELECT cr.id, cr.certificate_type, cr.purpose, cr.created_at, cr.status,
           r.surname, r.first_name, r.middle_name, r.address, r.age, r.sex, r.birthdate, r.citizenship, r.civil_status, cr.resident_unique_id
    FROM certificate_requests cr
    LEFT JOIN residents r ON cr.resident_unique_id = r.unique_id
    WHERE cr.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Certificate not found.");
}

$cert = $result->fetch_assoc();
$stmt->close();

// Sanitize fields
$surname = htmlspecialchars($cert['surname'] ?? '');
$firstname = htmlspecialchars($cert['first_name'] ?? '');
$middlename = htmlspecialchars($cert['middle_name'] ?? '');
$address = $cert['address'] ?? '[No address found]';
$purpose = $cert['purpose'] ?? '[No purpose given]';
$age = isset($cert['age']) ? (int)$cert['age'] : '';
$sex = $cert['sex'] ?? '';
$birthdate = $cert['birthdate'] ?? '';
$citizenship = $cert['citizenship'] ?? '';
$civil_status = $cert['civil_status'] ?? '';

// Build Resident Full Name
$resident_name = !empty($surname) && !empty($firstname)
    ? trim("$firstname $middlename $surname")
    : "[Resident not found]";

// Barangay Info
$contentResult = $conn->query("SELECT * FROM certificate_content WHERE id = 1");
if ($contentResult && $contentResult->num_rows > 0) {
    $contentData = $contentResult->fetch_assoc();
    $barangay_captain = $contentData['barangay_captain'];
    $barangay = $contentData['barangay_name'];
    $city = $contentData['city'];
} else {
    $barangay = 'Barangay Sabang';
    $city = 'Dasmariñas City, Cavite';
    $barangay_captain = 'Hon. Kenneth S. Saria';
}

$province = 'Cavite';
$region = 'Region IV-A (CALABARZON)';
$cert_type = $cert['certificate_type'] ?? '';
$issue_date = !empty($cert['created_at']) 
    ? date('jS \d\a\y \o\f F, Y', strtotime($cert['created_at'])) 
    : date('jS \d\a\y \o\f F, Y');

$birthdate_formatted = '';
if (!empty($birthdate)) {
    $birthdate_formatted = date('F d, Y', strtotime($birthdate));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Certificate - <?= htmlspecialchars($cert_type) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .preview-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .certificate-preview {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            min-height: 800px;
        }
        .edit-panel {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .header-section {
            text-align: center;
            margin-bottom: 30px;
        }
        .header-section img {
            width: 80px;
            height: auto;
        }
        .certificate-title {
            font-size: 24px;
            font-weight: bold;
            text-decoration: underline;
            margin: 20px 0;
        }
        .certificate-body {
            text-align: justify;
            line-height: 1.8;
            font-size: 14px;
        }
        .signature-section {
            margin-top: 60px;
            text-align: right;
        }
        .btn-action {
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .alert-custom {
            border-radius: 8px;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .form-label {
            font-weight: 600;
            color: #495057;
        }
        .border-decorative {
            border: 3px double #000;
            padding: 20px;
            position: relative;
        }
        .border-decorative::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            bottom: 10px;
            border: 1px solid #000;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success alert-custom alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong>Success!</strong> Certificate details have been updated.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Certificate Preview -->
            <div class="col-lg-7">
                <div class="certificate-preview border-decorative" id="certificate-preview">
                    <div class="header-section">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <?php if (file_exists(__DIR__ . '/logo.jpg')): ?>
                            <img src="logo.jpg" alt="Logo">
                            <?php endif; ?>
                            <div class="flex-grow-1 mx-3">
                                <div style="font-size: 11px; font-weight: bold;">Republic of the Philippines</div>
                                <div style="font-size: 10px;"><?= htmlspecialchars($region) ?></div>
                                <div style="font-size: 10px;">Province of <?= htmlspecialchars($province) ?></div>
                                <div style="font-size: 10px;"><?= htmlspecialchars($city) ?></div>
                                <div style="font-size: 11px; font-weight: bold;"><?= htmlspecialchars($barangay) ?></div>
                                <div style="font-size: 9px; font-style: italic;">Office of the Punong Barangay</div>
                            </div>
                            <?php if (file_exists(__DIR__ . '/brgy1.png')): ?>
                            <img src="brgy1.png" alt="Barangay Logo">
                            <?php endif; ?>
                        </div>
                        <div class="certificate-title"><?= strtoupper(htmlspecialchars($cert_type)) ?></div>
                    </div>

                    <div class="certificate-body">
                        <p style="font-weight: bold; font-size: 13px;">TO WHOM IT MAY CONCERN:</p>
                        
                        <?php if ($cert_type === 'Barangay Clearance'): ?>
                        <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;This is to certify that <strong><u><?= strtoupper($resident_name) ?></u></strong>, <?= $age ?> years old, <?= htmlspecialchars($sex) ?>, <?= $citizenship ? htmlspecialchars($citizenship) : 'Filipino' ?><?= !empty($civil_status) ? ', ' . htmlspecialchars($civil_status) : '' ?>, is a bona fide resident of <?= htmlspecialchars($address) ?>, <?= htmlspecialchars($barangay) ?>, <?= htmlspecialchars($city) ?>.</p>
                        
                        <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;This is to certify further that the bearer is personally known to me and to the residents of this barangay to be of <strong>GOOD MORAL CHARACTER</strong> and has <strong>NO PENDING CASE</strong> or derogatory record filed in this barangay.</p>
                        
                        <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;This clearance is issued upon the request of the above-named individual for <strong><u><?= strtoupper(htmlspecialchars($purpose)) ?></u></strong> and for whatever legal intent it may serve.</p>
                        
                        <?php elseif ($cert_type === 'Certificate of Indigency'): ?>
                        <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;This is to certify that <strong><u><?= strtoupper($resident_name) ?></u></strong>, <?= $age ?> years of age, <?= htmlspecialchars($sex) ?>, <?= $citizenship ? htmlspecialchars($citizenship) : 'Filipino' ?><?= !empty($civil_status) ? ', ' . htmlspecialchars($civil_status) : '' ?><?= !empty($birthdate_formatted) ? ', born on ' . $birthdate_formatted : '' ?>, is a resident of <?= htmlspecialchars($address) ?>, <?= htmlspecialchars($barangay) ?>, <?= htmlspecialchars($city) ?>.</p>
                        
                        <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;This is to certify further that the above-named person <strong>BELONGS TO AN INDIGENT FAMILY</strong> in this barangay and is <strong>IN NEED OF FINANCIAL ASSISTANCE</strong>. The bearer has limited means of income and requires support for basic necessities.</p>
                        
                        <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;This certification is issued to support the request for <strong><u><?= strtoupper(htmlspecialchars($purpose)) ?></u></strong> and to serve as proof of indigency status.</p>
                        
                        <?php elseif ($cert_type === 'Certificate of Residency'): ?>
                        <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;This is to certify that <strong><u><?= strtoupper($resident_name) ?></u></strong>, <?= $age ?> years of age, <?= htmlspecialchars($sex) ?>, <?= $citizenship ? htmlspecialchars($citizenship) : 'Filipino' ?><?= !empty($civil_status) ? ', ' . htmlspecialchars($civil_status) : '' ?><?= !empty($birthdate_formatted) ? ', born on ' . $birthdate_formatted : '' ?>, is a <strong>BONA FIDE RESIDENT</strong> of <?= htmlspecialchars($address) ?>, <?= htmlspecialchars($barangay) ?>, <?= htmlspecialchars($city) ?>.</p>
                        
                        <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;This is to certify further that the above-named individual has <strong>ESTABLISHED RESIDENCY</strong> in this barangay and is a <strong>LAW-ABIDING CITIZEN</strong> of good moral character and standing in the community.</p>
                        
                        <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;This certificate is issued upon request for <strong><u><?= strtoupper(htmlspecialchars($purpose)) ?></u></strong> and to verify residency status.</p>
                        
                        <?php else: ?>
                        <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;This is to certify that <strong><u><?= strtoupper($resident_name) ?></u></strong>, <?= $age ?> years of age, <?= htmlspecialchars($sex) ?>, <?= $citizenship ? htmlspecialchars($citizenship) : 'Filipino' ?><?= !empty($civil_status) ? ', ' . htmlspecialchars($civil_status) : '' ?>, residing at <?= htmlspecialchars($address) ?>, <?= htmlspecialchars($barangay) ?>, <?= htmlspecialchars($city) ?>, is <strong>PERSONALLY KNOWN</strong> to me and to the residents of this barangay.</p>
                        
                        <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;This is to certify further that the above-named individual is a <strong>MEMBER IN GOOD STANDING</strong> of this community, possesses good moral character, and is a law-abiding citizen.</p>
                        
                        <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;This certification is issued upon the request of the above-named individual for <strong><u><?= strtoupper(htmlspecialchars($purpose)) ?></u></strong> and for whatever legal purpose it may serve.</p>
                        <?php endif; ?>
                        
                        <p style="margin-top: 20px; font-size: 12px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Issued this <?= $issue_date ?> at the Office of the Punong Barangay, <?= htmlspecialchars($barangay) ?>, <?= htmlspecialchars($city) ?>.</p>
                        
                        <p style="font-size: 11px; font-style: italic; color: #666;">Not valid without official seal.</p>
                    </div>

                    <div class="signature-section">
                        <div style="border-top: 2px solid #000; display: inline-block; min-width: 250px; padding-top: 5px;">
                            <strong><?= strtoupper(htmlspecialchars($barangay_captain)) ?></strong><br>
                            <span style="font-size: 12px;">Punong Barangay</span>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-3 justify-content-center">
                    <a href="admin_dashboard.php?panel=certificates" class="btn btn-secondary btn-action">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                    <button onclick="printCertificate()" class="btn btn-success btn-action">
                        <i class="bi bi-printer"></i> Print Certificate
                    </button>
                </div>
            </div>

            <!-- Edit Panel -->
            <div class="col-lg-5">
                <div class="edit-panel">
                    <h4 class="mb-4">
                        <i class="bi bi-pencil-square text-primary"></i> Edit Certificate Details
                    </h4>
                    
                    <form method="POST" id="editForm">
                        <input type="hidden" name="update_certificate" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label">Resident Name</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($resident_name) ?>" readonly>
                            <small class="text-muted">Name cannot be changed here</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Certificate Type</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($cert_type) ?>" readonly>
                            <small class="text-muted">Type cannot be changed</small>
                        </div>

                        <div class="mb-3">
                            <label for="purpose" class="form-label">Purpose <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="purpose" name="purpose" rows="3" required><?= htmlspecialchars($purpose) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="address" name="address" rows="2" required><?= htmlspecialchars($address) ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="age" class="form-label">Age <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="age" name="age" value="<?= $age ?>" required min="1" max="150">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="sex" class="form-label">Sex <span class="text-danger">*</span></label>
                                <select class="form-select" id="sex" name="sex" required>
                                    <option value="Male" <?= $sex === 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= $sex === 'Female' ? 'selected' : '' ?>>Female</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="citizenship" class="form-label">Citizenship</label>
                            <input type="text" class="form-control" id="citizenship" name="citizenship" value="<?= htmlspecialchars($citizenship) ?>">
                        </div>

                        <div class="mb-3">
                            <label for="civil_status" class="form-label">Civil Status</label>
                            <select class="form-select" id="civil_status" name="civil_status">
                                <option value="">Not Specified</option>
                                <option value="Single" <?= $civil_status === 'Single' ? 'selected' : '' ?>>Single</option>
                                <option value="Married" <?= $civil_status === 'Married' ? 'selected' : '' ?>>Married</option>
                                <option value="Widowed" <?= $civil_status === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                                <option value="Separated" <?= $civil_status === 'Separated' ? 'selected' : '' ?>>Separated</option>
                            </select>
                        </div>

                        <div class="alert alert-info alert-custom">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            <small>Changes will update the certificate preview in real-time after saving.</small>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-action">
                                <i class="bi bi-save"></i> Save Changes
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function printCertificate() {
            const certId = <?= $id ?>;
            // Open print page in new tab
            window.open('print_certificate.php?id=' + certId, '_blank');
            
            // Optionally redirect back to dashboard after a delay
            setTimeout(() => {
                if (confirm('Certificate opened for printing. Return to dashboard?')) {
                    window.location.href = 'admin_dashboard.php?panel=certificates';
                }
            }, 1000);
        }

        function resetForm() {
            if (confirm('Reset all changes? This will reload the page.')) {
                window.location.href = 'preview_certificate.php?id=<?= $id ?>';
            }
        }

        // Auto-dismiss alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>
