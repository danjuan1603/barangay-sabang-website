<?php
use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0) {
    $filePath = $_FILES['excel_file']['tmp_name'];
    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    $success = 0;
    $fail = 0;
    $failRows = [];

    // Start from row 2 if row 1 is header
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        // Map columns: adjust indexes as needed
        $unique_id = $row[0];
        $surname = $row[1];
        $first_name = $row[2];
        $middle_name = $row[3];
        $age = $row[4];
        $sex = $row[5];
        $birthdate = $row[6];
        $place_of_birth = $row[7];
        $civil_status = $row[8];
        $citizenship = $row[9];
        $occupation_skills = $row[10];
        $education = $row[11];
        $is_pwd = $row[12];
        $address = $row[13];
        $household_id = $row[14];
        $is_head = $row[15];

        // Insert into DB
        $stmt = $conn->prepare("INSERT INTO residents (unique_id, surname, first_name, middle_name, age, sex, birthdate, place_of_birth, civil_status, citizenship, occupation_skills, education, is_pwd, address, household_id, is_head) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssisssssssssss", $unique_id, $surname, $first_name, $middle_name, $age, $sex, $birthdate, $place_of_birth, $civil_status, $citizenship, $occupation_skills, $education, $is_pwd, $address, $household_id, $is_head);
        if ($stmt->execute()) {
            $success++;
        } else {
            $fail++;
            $failRows[] = $i + 1; // Excel row number
        }
        $stmt->close();
    }

    echo "<div class='alert alert-info'>Batch upload complete. Success: $success, Failed: $fail";
    if ($fail > 0) echo ". Failed rows: " . implode(", ", $failRows);
    echo "</div>";
}