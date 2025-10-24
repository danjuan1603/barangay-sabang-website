<?php
// Files
$residentsFile = 'rresidents.json';
$deletedFile   = 'rdeleted_residents.json';

// Function to repair fullnames
function repairFile($file) {
    if (!file_exists($file)) return;

    $data = json_decode(file_get_contents($file), true) ?? [];
    $changed = false;

    foreach ($data as &$resident) {
        // If fullname missing or blank, rebuild it
        if (!isset($resident['fullname']) || trim($resident['fullname']) === '') {
            $surname     = $resident['surname'] ?? '';
            $first_name  = $resident['first_name'] ?? '';
            $middle_name = $resident['middle_name'] ?? '';

            $resident['fullname'] = trim($surname . ', ' . $first_name . ' ' . $middle_name);
            $changed = true;
        }
    }

    if ($changed) {
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
        echo "✅ Fixed names in $file<br>";
    } else {
        echo "ℹ No changes needed in $file<br>";
    }
}

// Run repair
repairFile($residentsFile);
repairFile($deletedFile);

echo "<br>Done repairing names!";
?>