<?php
require_once __DIR__ . '/db.php';

// Allow CORS from frontend dev server (adjust origin for production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
// allow headers commonly used by fetch() + multipart/form-data
header('Access-Control-Allow-Headers: Content-Type, Accept, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// routing helper: accept requests like /school/backend/api.php/schools
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = explode('/', trim($path, '/'));
// basic routing: last part is resource
$resource = end($parts);

// Determine the table name dynamically: try common names if 'schools' isn't present
$candidateTables = ['schools', 'school', 'all_schools', 'all_school'];
$table = null;
foreach ($candidateTables as $t) {
    $res = $mysqli->query("SHOW TABLES LIKE '" . $mysqli->real_escape_string($t) . "'");
    if ($res && $res->num_rows > 0) {
        $table = $t;
        break;
    }
}
if (!$table) {
    // fallback to 'schools' even if it doesn't exist to keep previous behavior
    $table = 'schools';
}

// accept both /.../schools and /.../school paths
if (in_array($resource, ['schools', 'school']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT id, name, email, address, city, ph_no as phone, link as website, description, image FROM `" . $mysqli->real_escape_string($table) . "` ORDER BY id DESC";
    $res = $mysqli->query($sql);
    if (!$res) send_json(['error' => $mysqli->error], 500);
    $rows = [];
    while ($row = $res->fetch_assoc()) {
        // convert image column to usable src (supports URL, stored path, or BLOB)
        // defensive normalization: if image stores an accidental table name like 'school' or 'schools', clear it
        if (isset($row['image'])) {
            $trimImg = is_string($row['image']) ? trim($row['image']) : '';
            if ($trimImg === 'school' || $trimImg === 'schools') {
                $row['image'] = '';
            }
        }

        if (!empty($row['image'])) {
            $img = $row['image'];

            // if it's already an absolute URL or data URL, keep it
            if (preg_match('#^(https?://|data:)#i', $img)) {
                $row['image'] = $img;
            } else {
                // check if value corresponds to an existing file under backend/ (relative path)
                $candidate1 = __DIR__ . '/' . ltrim($img, '/');
                $candidate2 = __DIR__ . '/uploads/' . ltrim($img, '/');
                if (file_exists($candidate1)) {
                    $scheme = (!empty($_SERVER['REQUEST_SCHEME'])) ? $_SERVER['REQUEST_SCHEME'] : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http');
                    $row['image'] = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/school/backend/' . ltrim($img, '/');
                } elseif (file_exists($candidate2)) {
                    $scheme = (!empty($_SERVER['REQUEST_SCHEME'])) ? $_SERVER['REQUEST_SCHEME'] : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http');
                    // use uploads path
                    $rel = 'uploads/' . ltrim($img, '/');
                    $row['image'] = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/school/backend/' . $rel;
                } else {
                    // possibility: DB stores raw binary (BLOB) - detect non-printable bytes
                    $isBinary = preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $img);
                    if ($isBinary) {
                        // attempt to detect mime type and return data URL
                        $mime = 'image/png';
                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $detected = $finfo->buffer($img);
                        if ($detected) $mime = $detected;
                        $b64 = base64_encode($img);
                        $row['image'] = 'data:' . $mime . ';base64,' . $b64;
                    } else {
                        // fallback: treat as filename in uploads folder or raw string; return as-is (may 404)
                        $scheme = (!empty($_SERVER['REQUEST_SCHEME'])) ? $_SERVER['REQUEST_SCHEME'] : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http');
                        $row['image'] = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/school/backend/' . ltrim($img, '/');
                    }
                }
            }
        }
        $rows[] = $row;
    }
    send_json($rows);
}

if (in_array($resource, ['schools', 'school']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // accept multipart/form-data for image upload
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $website = $_POST['website'] ?? '';
    $description = $_POST['description'] ?? '';

    // sanitize phone: keep digits only (remove formatting) and limit length
    $phone = preg_replace('/\D+/', '', $phone);
    $maxPhoneLen = 20; // reasonable max for international numbers
    if (strlen($phone) > $maxPhoneLen) {
        $phone = substr($phone, 0, $maxPhoneLen);
    }

    // check DB column type for ph_no; if integer type, return a helpful error
    $colRes = $mysqli->query("SHOW COLUMNS FROM `" . $mysqli->real_escape_string($table) . "` LIKE 'ph_no'");
    if ($colRes && $colRes->num_rows > 0) {
        $col = $colRes->fetch_assoc();
        $colType = $col['Type'] ?? '';
        if (preg_match('/^(tinyint|smallint|mediumint|int|bigint)\\b/i', $colType)) {
            send_json([
                'error' => "Database column 'ph_no' is type {$colType}. Phone numbers should be stored as strings. Please ALTER TABLE `{$table}` MODIFY COLUMN ph_no VARCHAR(20) NOT NULL;"
            ], 400);
        }
    }

    if (!$name || !$email || !$address) {
        send_json(['error' => 'Missing required fields'], 400);
    }

    // default to empty string to satisfy schemas where `image` is NOT NULL
    $imagePath = '';
    // quick check: guard against POST bodies larger than php.ini limits
    $contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;
    $postMax = ini_get('post_max_size');
    // convert shorthand like '8M' to bytes
    $postMaxBytes = null;
    if ($postMax) {
        $unit = strtoupper(substr($postMax, -1));
        $value = (int)$postMax;
        switch ($unit) {
            case 'G': $postMaxBytes = $value * 1024 * 1024 * 1024; break;
            case 'M': $postMaxBytes = $value * 1024 * 1024; break;
            case 'K': $postMaxBytes = $value * 1024; break;
            default: $postMaxBytes = $value; break;
        }
    }
    if ($postMaxBytes !== null && $contentLength > 0 && $contentLength > $postMaxBytes) {
        send_json(['error' => 'POST size exceeds post_max_size in php.ini (' . $postMax . ')'], 413);
    }

    if (!empty($_FILES['image'])) {
        // handle common PHP upload errors with clearer messages
        $err = $_FILES['image']['error'];
        if ($err !== UPLOAD_ERR_OK) {
            $map = [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            ];
            $msg = $map[$err] ?? ('Unknown upload error code: ' . $err);
            error_log('Image upload error: ' . $msg);
            // if no file, fall through to check for base64 in POST
        } else {
            // use an uploads directory inside backend and save absolute file on disk
            $uploadsDirRel = 'uploads';
            $uploadsDir = __DIR__ . '/' . $uploadsDirRel;
            if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
            $tmp = $_FILES['image']['tmp_name'];
            $orig = basename($_FILES['image']['name']);
            $ext = pathinfo($orig, PATHINFO_EXTENSION);
            $filename = uniqid('school_') . ($ext ? '.' . $ext : '');
            $dest = $uploadsDir . '/' . $filename;
            if (!move_uploaded_file($tmp, $dest)) {
                error_log('move_uploaded_file failed: tmp=' . $tmp . ' dest=' . $dest);
                send_json(['error' => 'Failed to save uploaded image on server'], 500);
            }
            // store relative path for usage in GET responses
            $imagePath = $uploadsDirRel . '/' . $filename;
        }
    } elseif (!empty($_POST['image'])) {
        // accept base64 image or image URL
        $maybe = $_POST['image'];
        if (strpos($maybe, 'data:image') === 0) {
            // base64 -> save file
            preg_match('/data:image\/(.*);base64,/', $maybe, $m);
            $ext = $m[1] ?? 'png';
            $data = preg_replace('/^data:image\/.+;base64,/', '', $maybe);
            $data = base64_decode($data);
            $uploadsDir = 'uploads';
            if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
            $filename = uniqid('school_') . '.' . $ext;
            $dest = $uploadsDir . '/' . $filename;
            if (file_put_contents($dest, $data) === false) send_json(['error' => 'Failed to save image'], 500);
            $imagePath = $dest;
        } else {
            // treat as URL and store as-is
            $imagePath = $maybe;
        }
    }

    $sql = "INSERT INTO `" . $mysqli->real_escape_string($table) . "` (name, email, address, city, ph_no, link, description, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    // bind all parameters as strings to avoid type mismatch for ph_no
    $stmt->bind_param('ssssssss', $name, $email, $address, $city, $phone, $website, $description, $imagePath);
    if (!$stmt->execute()) {
        send_json(['error' => $stmt->error], 500);
    }

    $insertedId = $stmt->insert_id;
    send_json(['success' => true, 'id' => $insertedId], 201);
}

// unknown route
send_json(['error' => 'Not found'], 404);
