<?php

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Define the columns (headers) for the template (removed unique_id)
$headers = [
    'surname', 'first_name', 'middle_name', 'birthdate', 'age', 'email', 'sex', 'address',
    'place_of_birth', 'civil_status', 'citizenship', 'occupation_skills', 'education',
    'household_id', 'relationship', 'is_head', 'is_pwd'
];

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->fromArray($headers, NULL, 'A1');

// Optionally, add a sample row
$sample = [
    'Ronario', 'Juan', 'Santos', '2000-01-01', '33', ' ', 'Male', '123 Main St',
    'Cebu', 'Single', 'Filipino', 'Carpenter', 'College', 'H001', 'Head', 'Yes', 'No'
];
$sheet->fromArray($sample, NULL, 'A2');

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="resident_template.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;