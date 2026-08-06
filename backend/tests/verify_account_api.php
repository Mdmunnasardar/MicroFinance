<?php
function req(string $url, string $method = 'GET', $body = null, $multipart = null, string $cookies = ''): array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    if ($cookies) curl_setopt($ch, CURLOPT_COOKIE, $cookies);
    $headers = ['Accept: application/json'];
    if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($multipart !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $multipart);
        } elseif ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $resp = curl_exec($ch);
    $info = curl_getinfo($ch);
    $hsize = $info['header_size'];
    $rawHeaders = substr($resp, 0, $hsize);
    $rawBody = substr($resp, $hsize);
    $jar = [];
    if (preg_match_all('/^Set-Cookie:\s*([^=]+)=([^;]*)/mi', $rawHeaders, $m, PREG_SET_ORDER)) {
        foreach ($m as $row) {
            $jar[$row[1]] = $row[2];
        }
    }
    $ckParts = [];
    foreach ($jar as $k => $v) $ckParts[] = "$k=$v";
    curl_close($ch);
    return ['code' => $info['http_code'], 'body' => $rawBody, 'cookies' => implode('; ', $ckParts)];
}

$base = 'http://localhost/MicroFinance/backend/public/api';
$results = [];
$check = function (string $name, bool $cond) use (&$results) {
    $results[] = ['name' => $name, 'pass' => $cond];
    echo ($cond ? '[PASS] ' : '[FAIL] ') . $name . "\n";
};

// --- login admin ---
$r = req($base . '/auth/login', 'POST', ['username' => 'admin', 'password' => '123456']);
echo "Login admin: HTTP " . $r['code'] . "\n";
$check('1. Login admin', $r['code'] === 200 && strpos($r['body'], '"success":true') !== false);
$adminCookie = $r['cookies'];

// --- session ---
$r = req($base . '/auth/session', 'GET', null, null, $adminCookie);
$check('2. Session authenticated', $r['code'] === 200 && strpos($r['body'], '"authenticated":true') !== false);

// --- GET /api/profile ---
$r = req($base . '/profile', 'GET', null, null, $adminCookie);
$check('3a. GET /api/profile 200', $r['code'] === 200);
$check('3b. profile contains expected fields', strpos($r['body'], '"full_name"') !== false && strpos($r['body'], '"branch_name"') !== false);
$check('3c. profile does NOT expose password_hash', strpos($r['body'], 'password_hash') === false);

// --- PUT /api/profile ---
$r = req($base . '/profile', 'PUT', ['full_name' => 'Md Munna Sardar', 'phone' => '+8801712345678', 'branch_id' => 1], null, $adminCookie);
$check('4. PUT /api/profile 200', $r['code'] === 200);
$check('4b. profile response has no password_hash', strpos($r['body'], 'password_hash') === false);

// --- silently ignore disallowed ---
$r = req($base . '/profile', 'PUT', ['full_name' => 'Md Munna Sardar', 'username' => 'hacker'], null, $adminCookie);
$check('5. PUT /api/profile with username -> 200 (ignored)', $r['code'] === 200);
$r2 = req($base . '/profile', 'GET', null, null, $adminCookie);
$check('5b. username unchanged after disallowed attempt', strpos($r2['body'], '"username":"admin"') !== false);

// --- password validation ---
$r = req($base . '/profile/password', 'PUT', ['current_password' => 'wrong', 'new_password' => 'abcdef', 'confirm_password' => 'abcdef'], null, $adminCookie);
$check('6. wrong current_password -> 422 with current_password=incorrect', $r['code'] === 422 && strpos($r['body'], '"current_password":"incorrect"') !== false);

$r = req($base . '/profile/password', 'PUT', ['current_password' => '123456', 'new_password' => 'abcdef', 'confirm_password' => 'xyz'], null, $adminCookie);
$check('7. mismatch -> 422 with confirm_password=mismatch', $r['code'] === 422 && strpos($r['body'], '"confirm_password":"mismatch"') !== false);

$r = req($base . '/profile/password', 'PUT', ['current_password' => '123456', 'new_password' => 'ab', 'confirm_password' => 'ab'], null, $adminCookie);
$check('8. min_length -> 422 with new_password=min_length', $r['code'] === 422 && strpos($r['body'], '"new_password":"min_length"') !== false);

// --- successful change (back to 123456) ---
$r = req($base . '/profile/password', 'PUT', ['current_password' => '123456', 'new_password' => '123456', 'confirm_password' => '123456'], null, $adminCookie);
$check('9. password change succeeded', $r['code'] === 200 && strpos($r['body'], '"changed":true') !== false);
$check('9b. password change response has no password_hash', strpos($r['body'], 'password_hash') === false);

// --- avatar upload ---
$jpegPath = sys_get_temp_dir() . '/test_avatar_' . getmypid() . '.jpg';
$jpeg = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAr/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFAEBAAAAAAAAAAAAAAAAAAAAAP/EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAMAwEAAhEDEQA/AL+AB//Z');
file_put_contents($jpegPath, $jpeg);
$r = req($base . '/profile/avatar', 'POST', null, ['avatar' => new CURLFile($jpegPath, 'image/jpeg', 'avatar.jpg')], $adminCookie);
echo "Avatar upload: HTTP " . $r['code'] . "\n";
echo $r['body'] . "\n";
$check('10. avatar upload returns 200', $r['code'] === 200);
$check('10b. avatar upload response has no password_hash', strpos($r['body'], 'password_hash') === false);
preg_match('/"avatar":"([^"]+)"/', $r['body'], $m);
$newAvatar = $m[1] ?? null;
$expectedPath = realpath(__DIR__ . '/../../uploads/avatars') . DIRECTORY_SEPARATOR . $newAvatar;
$check('10c. avatar file exists on disk', $newAvatar !== null && file_exists($expectedPath));

// Revert avatar filename on disk and DB.
$c = new mysqli('127.0.0.1', 'root', '', 'MicroFinance', 3306);
$orig = '1783254055_IMG_20240426_231310.jpg';
$stmt = $c->prepare('UPDATE users SET avatar = ? WHERE user_id = 4');
$stmt->bind_param('s', $orig);
$stmt->execute();
$stmt->close();
if ($newAvatar && file_exists($expectedPath)) {
    @unlink($expectedPath);
}
echo "Reverted admin avatar to: $orig\n";

// --- oversized ---
$bigPath = sys_get_temp_dir() . '/big_' . getmypid() . '.jpg';
$big = str_repeat("\xFF\xD8\xFF\xE0", 5 * 1024 * 1024);
file_put_contents($bigPath, $big);
$r = req($base . '/profile/avatar', 'POST', null, ['avatar' => new CURLFile($bigPath, 'image/jpeg', 'big.jpg')], $adminCookie);
$check('11. oversized avatar -> 422', $r['code'] === 422 && strpos($r['body'], '"avatar":"file_too_large"') !== false);
@unlink($bigPath);

// --- invalid type ---
$svgPath = sys_get_temp_dir() . '/test_' . getmypid() . '.svg';
file_put_contents($svgPath, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
$r = req($base . '/profile/avatar', 'POST', null, ['avatar' => new CURLFile($svgPath, 'image/svg+xml', 'test.svg')], $adminCookie);
$check('12. svg-mime avatar -> 422', $r['code'] === 422);
@unlink($svgPath);

// --- field officers as admin ---
$r = req($base . '/field-officers', 'GET', null, null, $adminCookie);
echo "field-officers (admin): HTTP " . $r['code'] . "\n";
echo $r['body'] . "\n";
$check('13. field-officers as admin -> 200', $r['code'] === 200);
$check('13b. contains officer1', strpos($r['body'], '"officer1"') !== false);

// --- logout admin ---
$r = req($base . '/auth/logout', 'POST', null, null, $adminCookie);
$check('14. logout -> 200', $r['code'] === 200);

// --- officer1 cannot see field officers ---
$r = req($base . '/auth/login', 'POST', ['username' => 'officer1', 'password' => '123456']);
echo "Login officer1: HTTP " . $r['code'] . "\n";
$officerCookie = $r['cookies'];

$r = req($base . '/field-officers', 'GET', null, null, $officerCookie);
echo "field-officers (officer1): HTTP " . $r['code'] . "\n";
echo $r['body'] . "\n";
$check('16. field-officers as field_officer -> 403', $r['code'] === 403);

// --- unauthenticated ---
$r = req($base . '/profile', 'GET');
$check('17. /api/profile no cookie -> 401', $r['code'] === 401);

$r = req($base . '/field-officers', 'GET');
$check('18. /api/field-officers no cookie -> 401', $r['code'] === 401);

// --- logout clears session ---
$r = req($base . '/auth/logout', 'POST', null, null, $officerCookie);
$r = req($base . '/auth/session', 'GET', null, null, $officerCookie);
$check('19. session cleared after logout', $r['code'] === 200 && strpos($r['body'], '"authenticated":false') !== false);

// --- revert admin phone/branch to original state ---
$stmt = $c->prepare("UPDATE users SET phone='+8801712345678', branch_id=NULL WHERE user_id=4");
$stmt->execute();
$stmt->close();
echo "Reverted admin phone/branch to original baseline\n";

echo "\n=== Summary ===\n";
$pass = 0; $fail = 0;
foreach ($results as $rr) { $rr['pass'] ? $pass++ : $fail++; }
echo "PASS: $pass, FAIL: $fail\n";
$c->close();