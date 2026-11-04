<?php
require __DIR__ . '/includes/bootstrap.php';

if (isset($_GET['refresh'])) createLoginCaptcha();
$code = (string)($_SESSION['login_captcha'] ?? '');
$expires = (int)($_SESSION['login_captcha_expires'] ?? 0);
if ($code === '' || $expires < time()) {
    createLoginCaptcha();
    $code = (string)$_SESSION['login_captcha'];
}

$escape = static fn($value) => htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
$width = 190;
$height = 56;
$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">'
    . '<rect width="100%" height="100%" rx="7" fill="#edf1f6"/>';
for ($i = 0; $i < 7; $i++) {
    $svg .= '<path d="M' . random_int(0, $width) . ' ' . random_int(0, $height)
        . ' Q' . random_int(0, $width) . ' ' . random_int(0, $height)
        . ' ' . random_int(0, $width) . ' ' . random_int(0, $height)
        . '" fill="none" stroke="#8ca1bc" stroke-width="' . random_int(1, 2) . '" opacity=".65"/>';
}
for ($i = 0; $i < strlen($code); $i++) {
    $x = 22 + $i * 28;
    $y = random_int(35, 43);
    $rotation = random_int(-15, 15);
    $svg .= '<text x="' . $x . '" y="' . $y . '" transform="rotate(' . $rotation . ' ' . $x . ' ' . $y . ')"'
        . ' font-family="Arial,sans-serif" font-size="27" font-weight="700" fill="#243b5a">'
        . $escape($code[$i]) . '</text>';
}
$svg .= '</svg>';

header('Content-Type: image/svg+xml; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'");
echo $svg;
