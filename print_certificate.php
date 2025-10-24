<?php
require_once __DIR__ . '/fpdf.php'; 
include 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$admin_username = $_SESSION['admin_username'] ?? 'Unknown';


$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die("Invalid certificate ID.");
}

// Support return URL for archiving after print
$returnUrl = isset($_GET['return']) ? $_GET['return'] : '';

// Try to fetch from certificate_requests first (join on unique_id)
$sql = "SELECT cr.id, cr.certificate_type, cr.purpose, cr.created_at, cr.status,
           r.surname, r.first_name, r.middle_name, r.address, r.age, r.sex, r.birthdate, r.citizenship, cr.resident_unique_id
    FROM certificate_requests cr
    LEFT JOIN residents r ON cr.resident_unique_id = r.unique_id
    WHERE cr.id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) die("SQL prepare failed: " . $conn->error);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    // Try archived_certificate_requests if not found (join on unique_id)
    $sql2 = "SELECT acr.id, acr.certificate_type, acr.purpose, acr.created_at, acr.status,
              r.surname, r.first_name, r.middle_name, r.address, r.age, r.sex, r.birthdate, r.citizenship, acr.resident_unique_id
          FROM archived_certificate_requests acr
          LEFT JOIN residents r ON acr.resident_unique_id = r.unique_id
          WHERE acr.id = ?";
    $stmt2 = $conn->prepare($sql2);
    if (!$stmt2) die("SQL prepare failed: " . $conn->error);
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    if ($result2->num_rows === 0) {
        $stmt2->close();
        die("Certificate not found (ID $id).");
    }
    $cert = $result2->fetch_assoc();
    $stmt2->close();
    $isArchived = true;
} else {
    $cert = $result->fetch_assoc();
    $stmt->close();
    $isArchived = false;
}


// === Sanitize fields ===
$surname    = htmlspecialchars($cert['surname'] ?? '');
$firstname  = htmlspecialchars($cert['first_name'] ?? '');
$middlename = htmlspecialchars($cert['middle_name'] ?? '');
$address    = htmlspecialchars($cert['address'] ?? '[No address found]');
$purpose    = htmlspecialchars($cert['purpose'] ?? '[No purpose given]');
$age        = isset($cert['age']) ? (int)$cert['age'] : '';
$sex        = htmlspecialchars($cert['sex'] ?? '');
$birthdate  = htmlspecialchars($cert['birthdate'] ?? '');
$citizenship= htmlspecialchars($cert['citizenship'] ?? '');

// === Build Resident Full Name ===
$resident_name = !empty($surname) && !empty($firstname)
    ? trim("$firstname $middlename $surname")
    : "[Resident not found for ID: " . $cert['resident_unique_id'] . "]";

// Barangay Info - Load from database
$contentResult = $conn->query("SELECT * FROM certificate_content WHERE id = 1");
if ($contentResult && $contentResult->num_rows > 0) {
    $contentData = $contentResult->fetch_assoc();
    $barangay_captain = $contentData['barangay_captain'];
    $barangay = $contentData['barangay_name'];
    $city = $contentData['city'];
} else {
    // Fallback to defaults if table doesn't exist or no data
    $barangay = 'Barangay Sabang';
    $city = 'Dasmariñas City, Cavite';
    $barangay_captain = 'Hon. Kenneth S. Saria';
}
$province = 'Cavite';
$region = 'Region IV-A (CALABARZON)';

// Certificate type
$cert_type = $cert['certificate_type'] ?? '';

// === Update status to Printed + Log admin action (only if not archived) ===
if (!$isArchived) {
    $update = $conn->prepare("UPDATE certificate_requests SET status = 'Printed', completed_at = NOW() WHERE id = ?");
    if ($update) {
        $update->bind_param("i", $id);
        if ($update->execute()) {
            $action_text = "Printed certificate ID: $id for resident $resident_name";
            $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
            if ($log) {
                $log->bind_param("ss", $admin_username, $action_text);
                $log->execute();
                $log->close();
            }
        }
        $update->close();
    }
}



// === Generate PDF ===
$pdf = new FPDF();
$pdf->AddPage();

// Format birthdate if available
$birthdate_formatted = '';
if (!empty($birthdate)) {
    $birthdate_formatted = date('F d, Y', strtotime($birthdate));
}

// Determine civil status text
$civil_status = !empty($cert['civil_status']) ? $cert['civil_status'] : '';

// Logo paths
$logoPath = __DIR__ . '/logo.jpg';
$brgyPath = __DIR__ . '/brgy1.png';

// Issue date
$issue_date = !empty($cert['created_at']) 
    ? date('jS \d\a\y \o\f F, Y', strtotime($cert['created_at'])) 
    : date('jS \d\a\y \o\f F, Y');

// ========================================
// BARANGAY CLEARANCE - Professional Format with Double Border
// ========================================
if ($cert_type === 'Barangay Clearance') {
    $pdf->SetMargins(20, 15, 20);
    $pdf->SetAutoPageBreak(true, 20);
    
    // Decorative double border
    $pdf->SetLineWidth(0.8);
    $pdf->Rect(10, 10, 190, 277);
    $pdf->SetLineWidth(0.3);
    $pdf->Rect(13, 13, 184, 271);
    
    // Add logos
    if (file_exists($logoPath)) {
        $imageInfo = @getimagesize($logoPath);
        if ($imageInfo !== false) {
            $imageType = $imageInfo[2];
            if ($imageType == IMAGETYPE_PNG) {
                $pdf->Image($logoPath, 25, 18, 28, 0, 'PNG');
            } elseif ($imageType == IMAGETYPE_JPEG) {
                $pdf->Image($logoPath, 25, 18, 28, 0, 'JPG');
            }
        }
    }
    if (file_exists($brgyPath)) {
        $pdf->Image($brgyPath, 157, 18, 28, 0, 'PNG');
    }
    
    $pdf->Ln(25);
    
    // Header
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(0,4,'Republic of the Philippines',0,1,'C');
    $pdf->SetFont('Arial','',9);
    $pdf->Cell(0,4,$region,0,1,'C');
    $pdf->Cell(0,4,'Province of ' . $province,0,1,'C');
    $pdf->Cell(0,4,$city,0,1,'C');
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(0,4,$barangay,0,1,'C');
    $pdf->Ln(2);
    $pdf->SetFont('Arial','BI',11);
    $pdf->Cell(0,5,'Office of the Punong Barangay',0,1,'C');
    $pdf->Ln(3);
    
    // Title with decorative line
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,8,'BARANGAY CLEARANCE',0,1,'C');
    $pdf->SetLineWidth(0.5);
    $pdf->Line(70, $pdf->GetY(), 140, $pdf->GetY());
    $pdf->Ln(8);
    
    // Body
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,6,'TO WHOM IT MAY CONCERN:',0,1);
    $pdf->Ln(3);
    $pdf->SetFont('Arial','',11);
    
    $text = "        This is to certify that ";
    $pdf->Write(6, $text);
    $pdf->SetFont('Arial','BU',11);
    $pdf->Write(6, strtoupper($resident_name));
    $pdf->SetFont('Arial','',11);
    $pdf->Write(6, ", $age years old, $sex, " . ($citizenship ? $citizenship : 'Filipino'));
    if (!empty($civil_status)) {
        $pdf->Write(6, ", $civil_status");
    }
    $pdf->Write(6, ", is a bona fide resident of $address, $barangay, $city.\n\n");
    
    $pdf->Write(6, "        This is to certify further that the bearer is personally known to me and to the residents of this barangay to be of ");
    $pdf->SetFont('Arial','B',11);
    $pdf->Write(6, "GOOD MORAL CHARACTER");
    $pdf->SetFont('Arial','',11);
    $pdf->Write(6, " and has ");
    $pdf->SetFont('Arial','B',11);
    $pdf->Write(6, "NO PENDING CASE");
    $pdf->SetFont('Arial','',11);
    $pdf->Write(6, " or derogatory record filed in this barangay.\n\n");
    
    $pdf->Write(6, "        This clearance is issued upon the request of the above-named individual for ");
    $pdf->SetFont('Arial','BU',11);
    $pdf->Write(6, strtoupper($purpose));
    $pdf->SetFont('Arial','',11);
    $pdf->Write(6, " and for whatever legal intent it may serve.\n");
    
    $pdf->Ln(5);
    $pdf->SetFont('Arial','',10);
    $pdf->MultiCell(0,5,"        Issued this $issue_date at the Office of the Punong Barangay, $barangay, $city.");
    
    $pdf->Ln(3);
    $pdf->SetFont('Arial','I',9);
    $pdf->Cell(0,5,'Not valid without official seal.',0,1);
    
    $pdf->Ln(12);
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,6,strtoupper($barangay_captain),0,1,'R');
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(0,5,'Punong Barangay',0,1,'R');
    $pdf->SetLineWidth(0.3);
    $pdf->Line(130, $pdf->GetY()-12, 185, $pdf->GetY()-12);

// ========================================
// CERTIFICATE OF INDIGENCY - Formal Format with Box Layout
// ========================================
} elseif ($cert_type === 'Certificate of Indigency') {
    $pdf->SetMargins(18, 12, 18);
    $pdf->SetAutoPageBreak(true, 18);
    
    // Single professional border
    $pdf->SetLineWidth(0.5);
    $pdf->Rect(12, 12, 186, 273);
    
    // Add logos
    if (file_exists($logoPath)) {
        $imageInfo = @getimagesize($logoPath);
        if ($imageInfo !== false) {
            $imageType = $imageInfo[2];
            if ($imageType == IMAGETYPE_PNG) {
                $pdf->Image($logoPath, 23, 16, 26, 0, 'PNG');
            } elseif ($imageType == IMAGETYPE_JPEG) {
                $pdf->Image($logoPath, 23, 16, 26, 0, 'JPG');
            }
        }
    }
    if (file_exists($brgyPath)) {
        $pdf->Image($brgyPath, 161, 16, 26, 0, 'PNG');
    }
    
    $pdf->Ln(22);
    
    // Header
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,5,'Republic of the Philippines',0,1,'C');
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(0,4,$region,0,1,'C');
    $pdf->Cell(0,4,'Province of ' . $province,0,1,'C');
    $pdf->Cell(0,4,$city,0,1,'C');
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(0,4,strtoupper($barangay),0,1,'C');
    $pdf->Ln(1);
    $pdf->SetFont('Arial','',9);
    $pdf->Cell(0,4,'OFFICE OF THE BARANGAY CAPTAIN',0,1,'C');
    $pdf->Ln(5);
    
    // Title box
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('Arial','B',15);
    $pdf->Cell(0,10,'CERTIFICATE OF INDIGENCY',1,1,'C',true);
    $pdf->Ln(6);
    
    // Body
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,6,'TO WHOM IT MAY CONCERN:',0,1);
    $pdf->Ln(3);
    $pdf->SetFont('Arial','',11);
    
    $text = "        This is to certify that ";
    $pdf->Write(6, $text);
    $pdf->SetFont('Arial','BU',11);
    $pdf->Write(6, strtoupper($resident_name));
    $pdf->SetFont('Arial','',11);
    $pdf->Write(6, ", $age years of age, $sex, " . ($citizenship ? $citizenship : 'Filipino'));
    if (!empty($civil_status)) {
        $pdf->Write(6, ", $civil_status");
    }
    if (!empty($birthdate_formatted)) {
        $pdf->Write(6, ", born on $birthdate_formatted");
    }
    $pdf->Write(6, ", is a resident of $address, $barangay, $city.\n\n");
    
    $pdf->Write(6, "        This is to certify further that the above-named person ");
    $pdf->SetFont('Arial','B',11);
    $pdf->Write(6, "BELONGS TO AN INDIGENT FAMILY");
    $pdf->SetFont('Arial','',11);
    $pdf->Write(6, " in this barangay and is ");
    $pdf->SetFont('Arial','B',11);
    $pdf->Write(6, "IN NEED OF FINANCIAL ASSISTANCE");
    $pdf->SetFont('Arial','',11);
    $pdf->Write(6, ". The bearer has limited means of income and requires support for basic necessities.\n\n");
    
    $pdf->Write(6, "        This certification is issued to support the request for ");
    $pdf->SetFont('Arial','BU',11);
    $pdf->Write(6, strtoupper($purpose));
    $pdf->SetFont('Arial','',11);
    $pdf->Write(6, " and to serve as proof of indigency status.\n");
    
    $pdf->Ln(5);
    $pdf->SetFont('Arial','',10);
    $pdf->MultiCell(0,5,"        Issued this $issue_date at $barangay, $city.");
    
    $pdf->Ln(2);
    $pdf->SetFont('Arial','I',9);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0,5,'Not valid without official seal and signature.',0,1);
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->Ln(15);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,6,strtoupper($barangay_captain),0,1,'R');
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(0,5,'Punong Barangay',0,1,'R');

// ========================================
// CERTIFICATE OF RESIDENCY - Modern Format with Header Bar
// ========================================
} elseif ($cert_type === 'Certificate of Residency') {
    $pdf->SetMargins(15, 10, 15);
    $pdf->SetAutoPageBreak(true, 15);
    
    // Outer border
    $pdf->SetLineWidth(0.6);
    $pdf->Rect(8, 8, 194, 281);
    
    // Add logos
    if (file_exists($logoPath)) {
        $imageInfo = @getimagesize($logoPath);
        if ($imageInfo !== false) {
            $imageType = $imageInfo[2];
            if ($imageType == IMAGETYPE_PNG) {
                $pdf->Image($logoPath, 20, 13, 30, 0, 'PNG');
            } elseif ($imageType == IMAGETYPE_JPEG) {
                $pdf->Image($logoPath, 20, 13, 30, 0, 'JPG');
            }
        }
    }
    if (file_exists($brgyPath)) {
        $pdf->Image($brgyPath, 160, 13, 30, 0, 'PNG');
    }
    
    $pdf->Ln(24);
    
    // Header
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(0,4,'Republic of the Philippines',0,1,'C');
    $pdf->SetFont('Arial','',9);
    $pdf->Cell(0,4,$region,0,1,'C');
    $pdf->Cell(0,4,'Province of ' . $province,0,1,'C');
    $pdf->Cell(0,4,$city,0,1,'C');
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(0,4,$barangay,0,1,'C');
    $pdf->Ln(1);
    $pdf->SetFont('Arial','I',9);
    $pdf->Cell(0,4,'Office of the Barangay Captain',0,1,'C');
    $pdf->Ln(4);
    
    // Title with underline
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,7,'CERTIFICATE OF RESIDENCY',0,1,'C');
    $pdf->SetLineWidth(0.8);
    $pdf->Line(60, $pdf->GetY(), 150, $pdf->GetY());
    $pdf->SetLineWidth(0.3);
    $pdf->Line(60, $pdf->GetY()+1, 150, $pdf->GetY()+1);
    $pdf->Ln(7);
    
    // Body
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,6,'TO WHOM IT MAY CONCERN:',0,1);
    $pdf->Ln(3);
    $pdf->SetFont('Arial','',11);
    
    $text = "        This is to certify that ";
    $pdf->Write(6, $text);
    $pdf->SetFont('Arial','BU',11);
    $pdf->Write(6, strtoupper($resident_name));
    $pdf->SetFont('Arial','',11);
    $pdf->Write(6, ", $age years of age, $sex, " . ($citizenship ? $citizenship : 'Filipino'));
    if (!empty($civil_status)) {
        $pdf->Write(6, ", $civil_status");
    }
    if (!empty($birthdate_formatted)) {
        $pdf->Write(6, ", born on $birthdate_formatted");
    }
    $pdf->Write(6, ", is a ");
    $pdf->SetFont('Arial','B',11);
    $pdf->Write(6, "BONA FIDE RESIDENT");
    $pdf->SetFont('Arial','',11);
    $pdf->Write(6, " of $address, $barangay, $city.\n\n");
    
    $pdf->Write(6, "        This is to certify further that the above-named individual has ");
    $pdf->SetFont('Arial','B',11);
    $pdf->Write(6, "ESTABLISHED RESIDENCY");
    $pdf->SetFont('Arial','',11);
    $pdf->Write(6, " in this barangay and is a ");
    $pdf->SetFont('Arial','B',11);
    $pdf->Write(6, "LAW-ABIDING CITIZEN");
    $pdf->SetFont('Arial','',11);
    $pdf->Write(6, " of good moral character and standing in the community.\n\n");
    
    $pdf->Write(6, "        This certificate is issued upon request for ");
    $pdf->SetFont('Arial','BU',11);
    $pdf->Write(6, strtoupper($purpose));
    $pdf->SetFont('Arial','',11);
    $pdf->Write(6, " and to verify residency status.\n");
    
    $pdf->Ln(5);
    $pdf->SetFont('Arial','',10);
    $pdf->MultiCell(0,5,"        Issued this $issue_date at the Office of the Barangay Captain, $barangay, $city.");
    
    $pdf->Ln(2);
    $pdf->SetFont('Arial','I',9);
    $pdf->Cell(0,5,'Not valid without official seal.',0,1);
    
    $pdf->Ln(15);
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,6,strtoupper($barangay_captain),0,1,'R');
    $pdf->SetLineWidth(0.4);
    $pdf->Line(135, $pdf->GetY(), 185, $pdf->GetY());
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(0,5,'Punong Barangay',0,1,'R');

// ========================================
// DEFAULT BARANGAY CERTIFICATION - Classic Format
// ========================================
} else {
    $pdf->SetMargins(16, 12, 16);
    $pdf->SetAutoPageBreak(true, 16);
    
    // Triple line border
    $pdf->SetLineWidth(0.5);
    $pdf->Rect(10, 10, 190, 277);
    $pdf->SetLineWidth(0.2);
    $pdf->Rect(11.5, 11.5, 187, 274);
    $pdf->Rect(13, 13, 184, 271);
    
    // Add logos
    if (file_exists($logoPath)) {
        $imageInfo = @getimagesize($logoPath);
        if ($imageInfo !== false) {
            $imageType = $imageInfo[2];
            if ($imageType == IMAGETYPE_PNG) {
                $pdf->Image($logoPath, 22, 17, 27, 0, 'PNG');
            } elseif ($imageType == IMAGETYPE_JPEG) {
                $pdf->Image($logoPath, 22, 17, 27, 0, 'JPG');
            }
        }
    }
    if (file_exists($brgyPath)) {
        $pdf->Image($brgyPath, 161, 17, 27, 0, 'PNG');
    }
    
    $pdf->Ln(23);
    
    // Header
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,5,'Republic of the Philippines',0,1,'C');
    $pdf->SetFont('Arial','',9);
    $pdf->Cell(0,4,$region,0,1,'C');
    $pdf->Cell(0,4,'Province of ' . $province,0,1,'C');
    $pdf->Cell(0,4,$city,0,1,'C');
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(0,4,$barangay,0,1,'C');
    $pdf->Ln(2);
    $pdf->SetFont('Arial','',9);
    $pdf->Cell(0,4,'Office of the Barangay Captain',0,1,'C');
    $pdf->Ln(5);
    
    // Title
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,8,'BARANGAY CERTIFICATION',0,1,'C');
    $pdf->Ln(5);
    
    // Body
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,6,'TO WHOM IT MAY CONCERN:',0,1);
    $pdf->Ln(3);
    $pdf->SetFont('Arial','',11);
    
    $text = "        This is to certify that ";
    $pdf->Write(6, $text);
    $pdf->SetFont('Arial','BU',11);
    $pdf->Write(6, strtoupper($resident_name));
    $pdf->SetFont('Arial','',11);
    $pdf->Write(6, ", $age years of age, $sex, " . ($citizenship ? $citizenship : 'Filipino'));
    if (!empty($civil_status)) {
        $pdf->Write(6, ", $civil_status");
    }
    $pdf->Write(6, ", residing at $address, $barangay, $city, is ");
    $pdf->SetFont('Arial','B',11);
    $pdf->Write(6, "PERSONALLY KNOWN");
    $pdf->SetFont('Arial','',11);
    $pdf->Write(6, " to me and to the residents of this barangay.\n\n");
    
    $pdf->Write(6, "        This is to certify further that the above-named individual is a ");
    $pdf->SetFont('Arial','B',11);
    $pdf->Write(6, "MEMBER IN GOOD STANDING");
    $pdf->SetFont('Arial','',11);
    $pdf->Write(6, " of this community, possesses good moral character, and is a law-abiding citizen.\n\n");
    
    $pdf->Write(6, "        This certification is issued upon the request of the above-named individual for ");
    $pdf->SetFont('Arial','BU',11);
    $pdf->Write(6, strtoupper($purpose));
    $pdf->SetFont('Arial','',11);
    $pdf->Write(6, " and for whatever legal purpose it may serve.\n");
    
    $pdf->Ln(6);
    $pdf->SetFont('Arial','',10);
    $pdf->MultiCell(0,5,"        Issued this $issue_date at the Office of the Barangay Captain, $barangay, $city.");
    
    $pdf->Ln(2);
    $pdf->SetFont('Arial','I',9);
    $pdf->Cell(0,5,'Not valid without official seal.',0,1);
    
    $pdf->Ln(14);
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,6,strtoupper($barangay_captain),0,1,'R');
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(0,5,'Punong Barangay',0,1,'R');
}

// Output PDF in browser
$pdf->Output("I", "certificate.pdf");

// If return URL is set, show a button to continue (archive)
if ($returnUrl && !$isArchived) {
    echo '<div style="text-align:center;margin-top:30px;">';
    echo '<a href="' . htmlspecialchars($returnUrl) . '" class="btn btn-success" style="font-size:1.2em;">Archive This Certificate</a>';
    echo '<br><small>After printing, click to archive and remove from active requests.</small>';
    echo '</div>';
}
?>
