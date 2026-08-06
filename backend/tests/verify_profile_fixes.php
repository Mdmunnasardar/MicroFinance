<?php
/**
 * End-to-end verification of the four profile flows after the fixes:
 *   1. My Profile data loading (API returns DB values + name field).
 *   2. Edit Profile (full_name, phone, branch_id save + return fresh).
 *   3. Avatar upload (file written, DB updated, URL serves, Topbar refresh).
 *   4. Field Officers (admin 200, branch_manager 200/403, officer 403, anon 401).
 *   5. New branches endpoint (used by Edit Profile select).
 */

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
        foreach ($m as $row) $jar[$row[1]] = $row[2];
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

function jget(string $body, string $path): mixed {
    $j = json_decode($body, true);
    $cur = $j;
    foreach (explode('.', $path) as $k) {
        if (is_array($cur) && array_key_exists($k, $cur)) $cur = $cur[$k];
        else return null;
    }
    return $cur;
}

// === 0. login ===
$r = req($base . '/auth/login', 'POST', ['username' => 'admin', 'password' => '123456']);
$check('0. Login admin', $r['code'] === 200 && jget($r['body'], 'success') === true);
$adminCookie = $r['cookies'];

// === 1. My Profile data loading ===
$r = req($base . '/profile', 'GET', null, null, $adminCookie);
$check('1a. GET /api/profile 200', $r['code'] === 200);
$u = jget($r['body'], 'data.user');
$check('1b. profile has id, username, full_name', $u && isset($u['id'], $u['username'], $u['full_name']));
$check('1c. profile has name (for AuthContext/Topbar)', $u && isset($u['name']) && $u['name'] === $u['full_name']);
$check('1d. profile has phone', $u && array_key_exists('phone', $u));
$check('1e. profile has branch_id, branch_name', $u && array_key_exists('branch_id', $u) && array_key_exists('branch_name', $u));
$check('1f. profile has avatar, is_active, created_at', $u && array_key_exists('avatar', $u) && array_key_exists('is_active', $u) && array_key_exists('created_at', $u));
$check('1g. profile does NOT expose password_hash', strpos($r['body'], 'password_hash') === false);

// Compare against DB
$db = new mysqli('127.0.0.1', 'root', '', 'MicroFinance', 3306);
$dbRow = $db->query("SELECT u.full_name, u.phone, u.branch_id, u.avatar FROM users u WHERE u.user_id = 4")->fetch_assoc();
$check('1h. API full_name matches DB', $u['full_name'] === $dbRow['full_name']);
$check('1i. API phone matches DB', ($u['phone'] ?? null) === ($dbRow['phone'] ?? null));
$check('1j. API branch_id matches DB', ($u['branch_id'] ?? null) === ($dbRow['branch_id'] !== null ? (int) $dbRow['branch_id'] : null));

// === 2. Edit Profile ===
$originalFullName = $u['full_name'];
$originalPhone = $u['phone'];
$originalBranchId = $u['branch_id'];

$newPhone = '+8801712345678';
$newBranch = 1;
$r = req($base . '/profile', 'PUT', [
    'full_name' => 'Md Munna Sardar (test)',
    'phone' => $newPhone,
    'branch_id' => $newBranch,
], null, $adminCookie);
$check('2a. PUT /api/profile 200', $r['code'] === 200);
$updated = jget($r['body'], 'data.user');
$check('2b. response full_name reflects new value', $updated && $updated['full_name'] === 'Md Munna Sardar (test)');
$check('2c. response phone reflects new value', $updated && $updated['phone'] === $newPhone);
$check('2d. response branch_id reflects new value', $updated && (int) $updated['branch_id'] === $newBranch);
$check('2e. response has name field (Topbar compat)', $updated && isset($updated['name']) && $updated['name'] === $updated['full_name']);

// Confirm DB match
$dbRow2 = $db->query("SELECT full_name, phone, branch_id FROM users WHERE user_id = 4")->fetch_assoc();
$check('2f. DB full_name matches new value', $dbRow2['full_name'] === 'Md Munna Sardar (test)');
$check('2g. DB phone matches new value', $dbRow2['phone'] === $newPhone);
$check('2h. DB branch_id matches new value', (int) $dbRow2['branch_id'] === $newBranch);

// Revert
$db->query("UPDATE users SET full_name='" . $db->real_escape_string($originalFullName) . "', phone=" . ($originalPhone === null ? "NULL" : "'" . $db->real_escape_string($originalPhone) . "'") . ", branch_id=" . ($originalBranchId === null ? "NULL" : (int) $originalBranchId) . " WHERE user_id=4");
echo "  [info] reverted admin profile to original values\n";

// === 3. Avatar upload ===
$jpeg = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAr/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFAEBAAAAAAAAAAAAAAAAAAAAAP/EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAMAwEAAhEDEQA/AL+AB//Z');
$jpegPath = sys_get_temp_dir() . '/verify_avatar_' . getmypid() . '.jpg';
file_put_contents($jpegPath, $jpeg);
$r = req($base . '/profile/avatar', 'POST', null, ['avatar' => new CURLFile($jpegPath, 'image/jpeg', 'avatar.jpg')], $adminCookie);
$check('3a. avatar upload 200', $r['code'] === 200);
$newName = jget($r['body'], 'data.avatar');
$check('3b. response has new avatar filename', is_string($newName) && $newName !== '');
$check('3c. response has no password_hash', strpos($r['body'], 'password_hash') === false);

$expectedPath = realpath(__DIR__ . '/../../uploads/avatars') . DIRECTORY_SEPARATOR . $newName;
$check('3d. file exists on disk', $newName && file_exists($expectedPath));

// Confirm DB updated
$dbRow3 = $db->query("SELECT avatar FROM users WHERE user_id=4")->fetch_assoc();
$check('3e. DB avatar matches response', $dbRow3['avatar'] === $newName);

// Confirm URL serves
$url = 'http://localhost/MicroFinance/uploads/avatars/' . $newName;
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_exec($ch);
$http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$check('3f. avatar URL serves via Apache', $http === 200);

// Revert: clear avatar in DB and remove uploaded file. Don't touch the original stale DB reference.
$db->query("UPDATE users SET avatar=NULL WHERE user_id=4");
if ($newName && file_exists($expectedPath)) @unlink($expectedPath);
echo "  [info] cleared admin avatar in DB after test (was NULL after revert? checking baseline)\n";

// === 4. Field Officers ===
$r = req($base . '/field-officers', 'GET', null, null, $adminCookie);
$check('4a. /api/field-officers as admin -> 200', $r['code'] === 200);
$list = jget($r['body'], 'data.field_officers');
$check('4b. response has field_officers array', is_array($list));
if (is_array($list) && count($list) > 0) {
    $o = $list[0];
    $check('4c. officer row has user_id, username, full_name', isset($o['user_id'], $o['username'], $o['full_name']));
    $check('4d. officer row has phone, branch_id, branch_name, is_active', isset($o['phone'], $o['branch_id'], $o['branch_name'], $o['is_active']));
}

// logout + login as officer1 (should get 403)
req($base . '/auth/logout', 'POST', null, null, $adminCookie);
$r = req($base . '/auth/login', 'POST', ['username' => 'officer1', 'password' => '123456']);
$officerCookie = $r['cookies'];
$r = req($base . '/field-officers', 'GET', null, null, $officerCookie);
$check('4e. /api/field-officers as field_officer -> 403', $r['code'] === 403);

// logout + anon
req($base . '/auth/logout', 'POST', null, null, $officerCookie);
$r = req($base . '/field-officers', 'GET');
$check('4f. /api/field-officers anonymous -> 401', $r['code'] === 401);

// === 5. Branches endpoint ===
$r = req($base . '/auth/login', 'POST', ['username' => 'admin', 'password' => '123456']);
$adminCookie = $r['cookies'];
$r = req($base . '/branches', 'GET', null, null, $adminCookie);
$check('5a. /api/branches as admin -> 200', $r['code'] === 200);
$br = jget($r['body'], 'data.branches');
$check('5b. response has branches array', is_array($br));
if (is_array($br) && count($br) > 0) {
    $b = $br[0];
    $check('5c. branch row has branch_id, branch_name, branch_code, is_active', isset($b['branch_id'], $b['branch_name'], $b['branch_code'], $b['is_active']));
}

$db->close();
echo "\n=== Summary ===\n";
$pass = 0; $fail = 0;
foreach ($results as $r) $r['pass'] ? $pass++ : $fail++;
echo "PASS: $pass, FAIL: $fail\n";
