<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

// Batch Excel upload logic
if (isset($_POST['upload_excel']) && isset($_FILES['excel_file'])) {
  $fileTmpPath = $_FILES['excel_file']['tmp_name'];
  $fileName = $_FILES['excel_file']['name'];
  $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

  // Validate file type
  if (!in_array($ext, ['xls', 'xlsx'])) {
    die("<div class='alert alert-danger'>Invalid file type. Please upload .xls or .xlsx only.</div>");
  }

  $spreadsheet = IOFactory::load($fileTmpPath);
  $sheet = $spreadsheet->getActiveSheet();
  $rows = $sheet->toArray();

  // Assuming first row is header
  $header = array_map('strtolower', $rows[0]);
  $key = array_search('unique_id', $header);
  if ($key !== false) {
    unset($header[$key]);
  }

  include 'config.php'; // Load DB config once
  $success = 0;
  $fail = 0;
  $failRows = [];

  $stmt = $conn->prepare("
      INSERT INTO residents (
        surname, first_name, middle_name, birthdate, age, email, sex, address,
        place_of_birth, civil_status, citizenship, occupation_skills, education,
        household_id, relationship, is_head, is_pwd
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ");

  for ($i = 1; $i < count($rows); $i++) {
    $row = $rows[$i];

    // Remove unique_id if header contained it
    if ($key !== false && count($row) > count($header)) {
        $unique_id = $row[$key];
        array_splice($row, $key, 1);
    } else {
        $unique_id = null;
    }

    $data = array_combine($header, $row);

    // Set occupation_skills and email to NULL if empty or missing
    $data['occupation_skills'] = (isset($data['occupation_skills']) && trim($data['occupation_skills']) !== '') ? $data['occupation_skills'] : '';
    $data['email'] = (isset($data['email']) && trim($data['email']) !== '') ? $data['email'] : '';

    // Default values for checkboxes
    $data['is_head'] = $data['is_head'] ?? 'No';
    $data['is_pwd'] = $data['is_pwd'] ?? 'No';

    // Check for duplicate resident by unique_id
    $duplicate = false;
    if ($unique_id) {
        $check = $conn->prepare("SELECT COUNT(*) FROM residents WHERE unique_id = ?");
        $check->bind_param("s", $unique_id);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->close();
        if ($count > 0) {
            $duplicate = true;
        }
    }

    if (!$duplicate) {
        $stmt->bind_param(
            "ssssissssssssssss",
            $data['surname'],
            $data['first_name'],
            $data['middle_name'],
            $data['birthdate'],
            $data['age'],
            $data['email'],
            $data['sex'],
            $data['address'],
            $data['place_of_birth'],
            $data['civil_status'],
            $data['citizenship'],
            $data['occupation_skills'],
            $data['education'],
            $data['household_id'],
            $data['relationship'],
            $data['is_head'],
            $data['is_pwd']
        );

        if ($stmt->execute()) {
            $success++;

                $new_resident_id = $conn->insert_id;

                
    // ✅ Use surname from $data, not an undefined $surname
    $surname = $data['surname']; 
    
    // ✅ Insert into useraccounts (password NULL for now)
    $acc = $conn->prepare("INSERT INTO useraccounts (userid, password, surname) VALUES (?, NULL, ?)");
    $acc->bind_param("is", $new_resident_id, $surname);
    $acc->execute();
    $acc->close();

            // --- Record admin action only ---
       
            $admin_username = $_SESSION['admin_username'] ?? 'unknown';
            $action = "Added resident: {$data['surname']}, {$data['first_name']} (ID: $unique_id)";
            $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
            $log->bind_param("ss", $admin_username, $action);
            $log->execute();
            $log->close();

        } else {
            $fail++;
            $failRows[] = "Row ".($i+1)." (".$stmt->error.")";
        }
    } else {
        $fail++;
        $failRows[] = "Row ".($i+1)." (Duplicate unique_id: $unique_id)";
    }
  }

  $stmt->close();
  $conn->close();

  // Show success message if at least one row was successful
  if ($success > 0) {
      echo "<div id='successMsg' class='alert alert-success text-center' style='position:fixed;top:30px;left:50%;transform:translateX(-50%);z-index:9999;min-width:300px;'>
              Successfully registered $success resident(s).
            </div>
            <script>
              setTimeout(function() {
                var msg = document.getElementById('successMsg');
                if(msg) msg.style.display = 'none';
              }, 3000);
            </script>";
  }

  // Display failure messages if any
  if ($fail > 0) {
      echo "<div id='failMsg' class='alert alert-danger text-center' style='position:fixed;top:30px;left:50%;transform:translateX(-50%);z-index:9999;min-width:300px;'>
              Failed to register $fail resident(s):<br>
              ".implode("<br>", $failRows)."
            </div>
            <script>
              setTimeout(function() {
                var msg = document.getElementById('failMsg');
                if(msg) msg.style.display = 'none';
              }, 5000);
            </script>";
  }

}
?>  


<!DOCTYPE html>
<html lang="en">
<head>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <meta charset="UTF-8">
  <a href="admin_dashboard.php?panel=residents" style="display:inline-block; margin-bottom:15px; color:#4CAF50; font-weight:bold; text-decoration:none; border:1px solid #4CAF50; border-radius:6px; padding:8px 16px; background:#f6fff6;">
    ← Back to Dashboard
  </a>
  <title>Register Profile</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap');
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #f1f8e9; /* Softer green background */
    }

    /* Top Navbar */
    .topnav {
      background-color: #2e7d32;
      color: white;
      padding: 15px 20px;
      text-align: center;
      font-size: 22px;
      font-weight: bold;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    /* Main Content */
    .main {
      padding: 30px 20px;
      max-width: 900px;
      margin: auto;
    }

    form {
      background: #ffffff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    h2, h3 {
      color: #2e7d32;
      margin-bottom: 10px;
      border-bottom: 2px solid #c8e6c9;
      padding-bottom: 5px;
    }

    label {
      display: block;
      margin-top: 15px;
      font-weight: bold;
      color: #333;
    }

    input[type="text"],
    input[type="number"],
    input[type="date"],
    select,
    textarea {
      width: 100%;
      padding: 10px;
      margin-top: 6px;
      border: 1px solid #bbb;
      border-radius: 5px;
      font-size: 14px;
    }

    textarea {
      resize: vertical;
    }

    /* Checkbox and inline fields */
    .checkbox-group {
      margin-top: 8px;
      margin-bottom: 10px;
    }

    .checkbox-group input {
      margin-right: 6px;
    }

    /* Submit button */
    input[type="submit"] {
      margin-top: 25px;
      padding: 12px 25px;
      background-color: #388e3c;
      color: white;
      font-size: 16px;
      font-weight: bold;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      transition: 0.3s;
    }

    input[type="submit"]:hover {
      background-color: #1b5e20;
      transform: scale(1.05);
    }

    .modern-input {
      width: 100%;
      padding: 12px 14px;
      margin-top: 6px;
      border: 1.5px solid #bfc9d1;
      border-radius: 8px;
      font-size: 15px;
      background: #f9fafb;
      transition: border-color 0.2s, box-shadow 0.2s;
      box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }
    .modern-input:focus {
      border-color: #007bff;
      outline: none;
      background: #fff;
      box-shadow: 0 2px 8px rgba(0,123,255,0.08);
    }

    .modern-label {
      font-family: 'Inter', Arial, sans-serif;
      font-weight: 500;
      color: #222;
      letter-spacing: 0.2px;
      font-size: 15px;
    }
  </style>
</head>
<body>

<div class="topnav">Barangay Management System</div>

<div class="main">
  <h2>Register Profile</h2>

  <!-- Batch Excel Upload Form -->
  <form action="rregister.php" method="POST" enctype="multipart/form-data" style="margin-bottom:30px;">
    <div class="mb-3">
      <a href="resident_template.php" class="btn btn-outline-success mb-2" style="float:right;">
        Download Excel Template
      </a>
      <label for="excel_file" class="form-label" style="color:#2e7d32;font-weight:600;">Batch Register via Excel:</label>
      <input type="file" name="excel_file" id="excel_file" class="form-control" accept=".xls,.xlsx" required>
    </div>
    <input type="submit" name="upload_excel" value="Upload & Register" class="btn btn-success">
  </form>

  <!-- Single Registration Form -->
  <form action="save_profile.php" method="POST">
    <div class="row g-3 align-items-end">
      <div class="col-lg-4 col-md-6">
        <label for="surname" class="form-label">Surname:</label>
        <input type="text" name="surname" class="form-control modern-input" required>
      </div>
      <div class="col-lg-4 col-md-6">
        <label for="first_name" class="form-label">First Name:</label>
        <input type="text" name="first_name" class="form-control modern-input" required>
      </div>
      <div class="col-lg-4 col-md-6">
        <label for="middle_name" class="form-label">Middle Name:</label>
        <input type="text" name="middle_name" class="form-control modern-input">
      </div>
      <div class="col-lg-4 col-md-6">
        <label for="birthdate" class="form-label">Birthdate:</label>
        <input type="date" name="birthdate" id="birthdate" class="form-control modern-input" required>
      </div>
      <div class="col-lg-2 col-md-6">
        <label for="age" class="form-label">Age:</label>
        <input type="number" name="age" id="age" class="form-control modern-input" readonly style="background:#f5f5f5;">
      </div>
      <div class="col-lg-8 col-md-8">
        <label for="email" class="form-label">Email:</label>
        <input type="email" name="email" id="email" class="form-control modern-input" placeholder="Enter your email">
      </div>
      <div class="col-lg-4 col-md-4">
        <label for="sex" class="form-label">Sex:</label>
        <select name="sex" class="form-control modern-input" required>
          <option value="">-- Select --</option>
          <option value="Male">Male</option>
          <option value="Female">Female</option>
        </select>
      </div>
      <div class="col-12">
        <label for="address" class="form-label">Address:</label>
        <input type="text" name="address" class="form-control modern-input" required>
      </div>
      <div class="col-lg-6 col-md-12">
        <label for="place_of_birth" class="form-label">Place of Birth:</label>
        <input type="text" name="place_of_birth" class="form-control modern-input">
      </div>
      <div class="col-lg-4 col-md-6">
        <label for="civil_status" class="form-label">Civil Status:</label>
        <select name="civil_status" class="form-control modern-input">
          <option value="">-- Select --</option>
          <option value="Single">Single</option>
          <option value="Married">Married</option>
          <option value="Widowed">Widowed</option>
          <option value="Divorced">Divorced</option>
        </select>
      </div>
      <div class="col-lg-3 col-md-6">
        <label for="citizenship" class="form-label">Citizenship:</label>
        <input type="text" name="citizenship" class="form-control modern-input">
      </div>
      <div class="col-lg-3 col-md-6">
        <label for="occupation_skills" class="form-label">Skills:</label>
        <input type="text" name="occupation_skills" class="form-control modern-input" placeholder="Enter your skills">
      </div>
      <div class="col-lg-4 col-md-6">
        <label for="education" class="form-label">Highest Educational Attainment:</label>
        <select name="education" class="form-control modern-input">
          <option value="">-- Select --</option>
          <option value="Elementary">Elementary</option>
          <option value="High School">High School</option>
          <option value="College">College</option>
          <option value="Vocational">Vocational</option>
          <option value="Undergrad">Undergrad</option>
          <option value="Graduate">Graduate</option>
        </select>
      </div>
    </div>

    <h3 style="font-family: 'Inter', Arial, sans-serif; font-weight:600; color:#2e7d32; margin-top:32px; margin-bottom:18px; letter-spacing:0.5px;">Household Information</h3>
    <div class="row g-3 align-items-end">
      <div class="col-lg-4 col-md-6">
        <label for="household_id" class="form-label modern-label">Household ID:</label>
        <input type="text" name="household_id" class="form-control modern-input" placeholder="e.g. H001">
      </div>
      <div class="col-lg-8 col-md-6">
        <label for="relationship" class="form-label modern-label">Relationship to Household Head:</label>
        <input type="text" name="relationship" class="form-control modern-input" placeholder="e.g. Head, Son, Daughter, Wife">
      </div>
      <div class="col-lg-6 col-md-6 d-flex align-items-center" style="gap:12px; margin-top:10px;">
        <input type="checkbox" name="is_head" value="Yes" style="width:18px; height:18px; margin:0;">
        <label class="form-label modern-label mb-0" style="font-weight:500;">Mark as Household Head</label>
      </div>
      <div class="col-lg-6 col-md-6 d-flex align-items-center" style="gap:12px; margin-top:10px;">
        <input type="checkbox" name="is_pwd" value="Yes" style="width:18px; height:18px; margin:0;">
        <label class="form-label modern-label mb-0" style="font-weight:500;">Person With Disability (PWD)</label>
      </div>
    </div>

    <input type="submit" value="Save">
  </form>
</div>

<script>
document.getElementById('birthdate').addEventListener('change', function() {
  const birthdate = this.value;
  if (birthdate) {
    const today = new Date();
    const birthDate = new Date(birthdate);
    // Check if birthdate is in the future
    if (birthDate > today) {
      this.setCustomValidity('Birthdate cannot be in the future.');
      this.reportValidity();
      document.getElementById('age').value = '';
      return;
    } else {
      this.setCustomValidity('');
    }
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
      age--;
    }
    document.getElementById('age').value = age >= 0 ? age : '';
  } else {
    document.getElementById('age').value = '';
    this.setCustomValidity('');
  }
});
</script>
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
        .then (data => {
            if (data.success) {
                modalVisible = false; // allow modal to show again
                if (callback) callback();
            }
        })
        .catch(err => console.error("Reset error:", err));
}

// Attach hidden listener once (X button or modal close)
modalEl.addEventListener('hidden.bs.modal', () => {
    resetNotifications();
});

// Fetch new reports every 3 seconds
setInterval(() => {
    if(modalVisible) return; // skip if modal is open

    fetch("check_new_reports.php?action=check")
        .then(res => res.json())
        .then(data => {
            const newReportsCount = Number(data.new_reports);
            if (newReportsCount > 0 && data.reports.length > 0) {
                let details = `<p style="font-weight:600; color:#b71c1c;">${newReportsCount} new report(s) submitted:</p><ul style="padding-left:18px;">`;
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
                        resetNotifications(() => modal.hide());
                    };
                }
            }
        })
        .catch(err => console.error("Fetch error:", err));
}, 3000);
</script>

</body>
</html>
