<?php
// incident_table_partial.php
// Can be included or called standalone via AJAX

// If called standalone (via AJAX), initialize database connection and fetch data
if (!isset($iresult)) {
    session_start();
    include 'config.php';
    
    // Default to active view if not set
    if (!isset($iview)) {
        $iview = isset($_GET['iview']) ? $_GET['iview'] : 'active';
    }
    
    // Fetch incident reports based on view
    if ($iview === 'archived') {
        $istmt = $conn->prepare("SELECT * FROM incident_reports WHERE status='Resolved' ORDER BY date_ended DESC");
    } else {
        $istmt = $conn->prepare("SELECT * FROM incident_reports WHERE status != 'Resolved' ORDER BY created_at DESC");
    }
    $istmt->execute();
    $iresult = $istmt->get_result();
}
?>
<?php if ($iresult->num_rows > 0): ?>
  <?php while ($row = $iresult->fetch_assoc()): ?>
  <?php
    // Fetch user block status for incidents
    $userid = $row['userid'];
    $canSubmitRes = $conn->query("SELECT can_submit_incidents FROM residents WHERE unique_id = '$userid'")->fetch_assoc();
    $canSubmitIncidents = $canSubmitRes ? $canSubmitRes['can_submit_incidents'] : 1;
    
    // Skip this incident if user is blocked
    if ($canSubmitIncidents == 0) {
        continue;
    }
    
    // Map incident_type (human-readable types) to a priority class for coloring.
    // Default lists here are reasonable guesses; adjust to your site's types as needed.
    $typeClass = '';
    $it = strtolower(trim($row['incident_type'] ?? ''));

    $urgent_types = [
  // English
  'fire', 'fire incident', 'explosion', 'gas leak', 'chemical spill', 'Accident/Injury',
  'damage to property', 'assault', 'armed assault', 'homicide', 'murder',
  'shooting', 'stabbing', 'violent', 'emergency', 'medical', 'heart attack',
  'stroke', 'unconscious person', 'car accident', 'serious injury', 
  'domestic violence', 'kidnapping', 'child abuse', 'sexual assault',
  'building collapse', 'natural disaster', 'earthquake', 'flood', 'electrocution',

  // Tagalog
  'sunog', 'pagsabog', 'tagas ng gas', 'pagtagas ng kemikal',
  'pinsala sa ari-arian', 'pananakit', 'armadong pananakit', 'pagpatay',
  'barilan', 'saksakan', 'karahasan', 'emerhensiya', 'medikal', 'atake sa puso',
  'stroke', 'walang malay', 'aksidente sa sasakyan', 'malubhang pinsala',
  'karahasan sa tahanan', 'pagdukot', 'pang-aabuso sa bata', 'panghahalay',
  'pagguho ng gusali', 'kalikasan', 'lindol', 'baha', 'kuryente'
];

$moderate_types = [
  // English
  'theft', 'vandalism', 'public disturbance', 'burglary', 'robbery',
  'damage', 'trespassing', 'hit and run', 'minor accident', 'property damage',
  'harassment', 'threat', 'missing person', 'fraud', 'illegal dumping',
  'shoplifting', 'verbal abuse', 'scam', 'identity theft', 'public intoxication',
  'illegal parking', 'reckless driving',

  // Tagalog
  'pagnanakaw', 'paninira', 'istorbo sa publiko', 'pagnanakaw sa bahay',
  'panghoholdap', 'pinsala', 'panggagambala', 'banggaan', 'nawalang tao',
  'panlilinlang', 'basurang itinatapon', 'pandurukot', 'pang-aasar', 'banta',
  'panloloko', 'pag-inom sa publiko', 'illegal na paradahan', 'pabaya sa pagmamaneho'
];

$minor_types = [
  // English
  'noise', 'noise complaint', 'minor', 'loitering', 'littering',
  'public nuisance', 'lost item', 'animal complaint', 'barking dog',
  'illegal posting', 'curfew violation', 'jaywalking', 'unauthorized selling',
  'other', 'graffiti', 'disorderly conduct', 'drunk in public',
  'trespassing (non-violent)', 'neighborhood dispute', 'unauthorized entry (non-violent)',

  // Tagalog
  'ingay', 'reklamo sa ingay', 'maliit', 'paglalaboy', 'pagtatapon ng basura',
  'istorbo', 'nawalang gamit', 'reklamo sa hayop', 'tahol ng aso',
  'illegal na poster', 'labag sa curfew', 'tumawid sa maling daan',
  'walang permit na tindero', 'iba pa', 'graffiti', 'gulo sa publiko',
  'lasing sa publiko', 'panggugulo', 'alitan sa kapitbahay', 'hindi awtorisadong pagpasok'
];

 
    // Helper: check if any keyword exists in the incident_type string
    foreach ($urgent_types as $kw) {
      if ($kw !== '' && strpos($it, $kw) !== false) { $typeClass = 'incident-urgent'; break; }
    }
    if (!$typeClass) {
      foreach ($moderate_types as $kw) {
        if ($kw !== '' && strpos($it, $kw) !== false) { $typeClass = 'incident-moderate'; break; }
      }
    }
    if (!$typeClass) {
      foreach ($minor_types as $kw) {
        if ($kw !== '' && strpos($it, $kw) !== false) { $typeClass = 'incident-minor'; break; }
      }
    }

    // If still empty, you can optionally default to a class or leave plain.

    // Compute inline style fallback so rows get colored even if external CSS isn't loaded
    $trStyle = '';
    if ($typeClass === 'incident-urgent') {
      $trStyle = 'background-color:#ffcccc !important; border-left:4px solid #e53935 !important;';
    } elseif ($typeClass === 'incident-moderate') {
      $trStyle = 'background-color:#ffe5b4 !important; border-left:4px solid #fb8c00 !important;';
    } elseif ($typeClass === 'incident-minor') {
      $trStyle = 'background-color:#ffffcc !important; border-left:4px solid #fbc02d !important;';
    }
  ?>
  <tr<?= $typeClass ? ' class="' . $typeClass . '"' : '' ?><?= $trStyle ? ' style="' . $trStyle . '"' : '' ?>>
    <td<?= $trStyle ? ' style="' . $trStyle . '"' : '' ?>><?= $row['userid'] ?></td>
    <td<?= $trStyle ? ' style="' . $trStyle . '"' : '' ?>><?= htmlspecialchars($row['incident_type']) ?></td>
    <td<?= $trStyle ? ' style="' . $trStyle . '"' : '' ?>><?= htmlspecialchars($row['contact_number']) ?></td>
    <td<?= $trStyle ? ' style="' . $trStyle . '"' : '' ?>>
        <?php if (!empty($row['incident_image'])): $src = htmlspecialchars($row['incident_image']); ?>
          <a href="#" data-bs-toggle="modal" data-bs-target="#iphotoModal<?= $row['id'] ?>">
            <img src="<?= $src ?>" alt="Incident Photo" style="width:50px;height:50px;object-fit:cover;border-radius:5px;">
          </a>
        <?php else: ?>
          <span class="text-muted">No photo</span>
        <?php endif; ?>
  </td>
  <td<?= $trStyle ? ' style="' . $trStyle . '"' : '' ?>>
        <?php 
          $desc = htmlspecialchars($row['incident_description']); 
          $shortDesc = (strlen($desc) > 50) ? substr($desc, 0, 50) . "…" : $desc; 
        ?>
        <?= $shortDesc ?>
        <?php if (strlen($desc) > 50): ?>
          <a href="#" data-bs-toggle="modal" data-bs-target="#idescModal<?= $row['id'] ?>">
            <small class="text-primary">View</small>
          </a>
        <?php endif; ?>
  </td>
  <?php if (isset($iview) && $iview === 'archived'): ?>
  <td<?= $trStyle ? ' style="' . $trStyle . '"' : '' ?>>
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
        <?php if ($adminComment): ?>
          <?php
            // Truncate admin comment for inline display and show a View link for archived items
            $maxCommentInline = 200; // chars
            $isLongComment = mb_strlen($adminComment) > $maxCommentInline;
            $inlineComment = $isLongComment ? mb_substr($adminComment, 0, $maxCommentInline) . '…' : $adminComment;
            $inlineCommentEsc = nl2br(htmlspecialchars($inlineComment));
          ?>
          <div class="text-muted" style="max-width:320px; white-space:pre-wrap;"><?= $inlineCommentEsc ?>
            <?php if ($isLongComment): ?>
              <a href="#" data-bs-toggle="modal" data-bs-target="#acommentModal<?= $row['id'] ?>" class="ms-2"><small class="text-primary">View</small></a>
            <?php endif; ?>
          </div>

          <?php if ($isLongComment): ?>
            <!-- Modal for full admin comment -->
            <div class="modal fade" id="acommentModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="acommentModalLabel<?= $row['id'] ?>" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="acommentModalLabel<?= $row['id'] ?>">Resolution Comment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <p><?= nl2br(htmlspecialchars($adminComment)) ?></p>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  </div>
                </div>
              </div>
            </div>
          <?php endif; ?>
        <?php endif; ?>
  </td>
  <?php endif; ?>
  <td<?= $trStyle ? ' style="' . $trStyle . '"' : '' ?>><?= date("M d, Y h:i A", strtotime($row['created_at'])) ?></td>
  <td<?= $trStyle ? ' style="' . $trStyle . '"' : '' ?>>
        <?php
          // Normalize legacy "Priority" status to behave as "In Review" for display and selection
          $status = $row['status'] ?? '';
          $statusNormalized = ($status === 'Priority') ? 'In Review' : $status;
        ?>
        <?php if ($iview === 'active'): ?>
          <select class="form-select form-select-sm incident-status-select" data-id="<?= $row['id'] ?>">
            <option value="Pending" <?= ($statusNormalized === "Pending") ? "selected" : "" ?>>Pending</option>
            <option value="In Review" <?= ($statusNormalized === "In Review") ? "selected" : "" ?>>In Review</option>
            <option value="Resolved" <?= ($statusNormalized === "Resolved") ? "selected" : "" ?>>Resolved</option>
          </select>
        <?php else: ?>
          <span class="badge bg-success"><?= htmlspecialchars($statusNormalized) ?></span>
        <?php endif; ?>
      </td>
    <?php if ($iview === 'archived'): ?>
    <td<?= $trStyle ? ' style="' . $trStyle . '"' : '' ?>><?= $row['date_ended'] ? date("M d, Y h:i A", strtotime($row['date_ended'])) : '-' ?></td>
      <?php endif; ?>
    <td class="text-center"<?= $trStyle ? ' style="' . $trStyle . '"' : '' ?>>
      <div class="btn-group-vertical gap-1" style="width: 100%;">
        <button type="button" class="btn btn-warning btn-sm incident-block-btn" data-userid="<?= $userid ?>">
          <i class="bi bi-person-x"></i> Block
        </button>
        <button class="btn btn-sm btn-danger incident-delete-btn" data-id="<?= $row['id'] ?>" data-archived="<?= $iview === 'archived' ? '1' : '0' ?>">
          <i class="bi bi-trash"></i> Delete
        </button>
      </div>
      </td>
    </tr>

    <!-- 🔹 Modal for Description -->
    <div class="modal fade" id="idescModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="idescModalLabel<?= $row['id'] ?>" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="idescModalLabel<?= $row['id'] ?>">Incident Description</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p><?= nl2br($desc) ?></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- 🔹 Modal for Photo -->
    <?php if (!empty($row['incident_image'])): ?>
    <div class="modal fade" id="iphotoModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="iphotoModalLabel<?= $row['id'] ?>" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="iphotoModalLabel<?= $row['id'] ?>">Incident Photo</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center">
            <img src="<?= $src ?>" alt="Incident Photo" class="img-fluid rounded">
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

  <?php endwhile; ?>
<?php else: ?>
    <?php $noCols = ($iview === 'active') ? 8 : 9; ?>
    <tr><td colspan="<?= $noCols ?>" class="text-center text-muted">No incident reports found.</td></tr>
<?php endif; ?>
