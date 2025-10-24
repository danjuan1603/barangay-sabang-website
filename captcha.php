<?php
// ✅ Start session early (no spaces or output before this)
session_start();

// ---------------------------------------------
// 1️⃣ Generate random CAPTCHA code
// ---------------------------------------------
$code = '';
$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // No 0 or O for clarity
for ($i = 0; $i < 5; $i++) {
    $code .= $chars[random_int(0, strlen($chars) - 1)]; // safer than rand()
}
$_SESSION['captcha_code'] = $code;

// ---------------------------------------------
// 2️⃣ Create image canvas
// ---------------------------------------------
$width = 90;
$height = 30;
$image = imagecreatetruecolor($width, $height);

// Colors
$bg   = imagecolorallocate($image, 245, 245, 245);
$text = imagecolorallocate($image, 40, 40, 40);
$line = imagecolorallocate($image, 200, 200, 200);

// Fill background
imagefilledrectangle($image, 0, 0, $width, $height, $bg);

// ---------------------------------------------
// 3️⃣ Add random noise lines
// ---------------------------------------------
for ($i = 0; $i < 5; $i++) {
    imageline($image, random_int(0, $width), random_int(0, $height),
                      random_int(0, $width), random_int(0, $height), $line);
}

// ---------------------------------------------
// 4️⃣ Add text (try TTF font, else fallback)
// ---------------------------------------------
$font_size = 14;
$font_file = __DIR__ . '/font/arial.ttf'; // adjust path if needed

if (function_exists('imagettftext') && file_exists($font_file)) {
    // Center text horizontally (approx)
    $bbox = imagettfbbox($font_size, 0, $font_file, $code);
    $text_width = $bbox[2] - $bbox[0];
    $x = ($width - $text_width) / 2;
    $y = 22;
    imagettftext($image, $font_size, random_int(-6, 6), $x, $y, $text, $font_file, $code);
} else {
    // Fallback if no TTF font support or file missing
    imagestring($image, 4, 20, 8, $code, $text);
}

// ---------------------------------------------
// 5️⃣ Output as PNG image
// ---------------------------------------------
if (!headers_sent()) {
    header('Content-Type: image/png');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}

imagepng($image);
imagedestroy($image);
exit;
