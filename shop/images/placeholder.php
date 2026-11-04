<?php
// Ảnh tạm khi sản phẩm chưa có ảnh thật (tự đổi màu theo tên sản phẩm).
header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: public, max-age=86400');
$t = (string)($_GET['t'] ?? 'Sản phẩm');
preg_match('/^.{0,34}/u', $t, $m);
$t = $m[0] ?? '';
$hue = crc32($t) % 360;
$t = htmlspecialchars($t, ENT_QUOTES, 'UTF-8');
echo <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 750">
<rect width="600" height="750" fill="hsl($hue,22%,88%)"/>
<path d="M300 210c-14 0-24-9-24-21 0-13 10-19 20-24 8-4 12-9 12-17a15 15 0 0 0-30-1" fill="none" stroke="hsl($hue,25%,35%)" stroke-width="7" stroke-linecap="round"/>
<path d="M300 212 130 330c-14 10-6 30 10 30h320c16 0 24-20 10-30z" fill="none" stroke="hsl($hue,25%,35%)" stroke-width="7" stroke-linejoin="round"/>
<text x="300" y="470" text-anchor="middle" font-family="sans-serif" font-size="30" font-weight="600" fill="hsl($hue,25%,28%)">$t</text>
<text x="300" y="510" text-anchor="middle" font-family="sans-serif" font-size="20" fill="hsl($hue,20%,42%)">Ảnh sẽ cập nhật sau</text>
</svg>
SVG;
