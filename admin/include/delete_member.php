<?php
ob_start();
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$memberId = isset($_POST['id']) ? trim($_POST['id']) : null;
if ($memberId === null || $memberId === '' || !ctype_digit((string) $memberId)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}
$memberId = (int) $memberId;

$sendJson = function($ok, $msg = '') {
    ob_end_clean();
    echo json_encode(['success' => $ok, 'message' => $msg]);
    exit();
};

try {
    include __DIR__ . '/conn.php';
    if (!isset($conn) || !($conn instanceof mysqli)) {
        $sendJson(false, 'Database not configured.');
    }
} catch (Throwable $e) {
    $sendJson(false, 'Database error.');
}

$baseDir = dirname(__DIR__, 2);

try {
    $stmt_select = $conn->prepare("SELECT id, photo, bank_slip FROM registrations WHERE id = ?");
    if (!$stmt_select) {
        $sendJson(false, 'Database error.');
    }
    $stmt_select->bind_param("i", $memberId);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    if ($result === false) {
        $stmt_select->close();
        $sendJson(false, 'Server configuration error (get_result not available).');
    }
    $row = $result->fetch_assoc();
    $stmt_select->close();

    if (!$row) {
        $sendJson(false, 'Member not found.');
    }

    $photo = $row['photo'] ?? null;
    $bank_slip = $row['bank_slip'] ?? null;

    // Delete dependent rows (ignore errors if tables missing)
    foreach (['member_access', 'password_reset_tokens'] as $table) {
        try {
            $del = $conn->prepare("DELETE FROM `{$table}` WHERE member_id = ?");
            if ($del) {
                $del->bind_param("i", $memberId);
                $del->execute();
                $del->close();
            }
        } catch (Throwable $e) {
            // continue
        }
    }

    $stmt_delete = $conn->prepare("DELETE FROM registrations WHERE id = ?");
    if (!$stmt_delete) {
        $sendJson(false, 'Database error.');
    }
    $stmt_delete->bind_param("i", $memberId);
    $stmt_delete->execute();
    $deleted = $stmt_delete->affected_rows;
    $stmt_delete->close();

    if ($deleted < 1) {
        $sendJson(false, 'Could not delete member.');
    }

    if (!empty($photo)) {
        $photoPath = $baseDir . '/' . $photo;
        if (file_exists($photoPath) && is_file($photoPath)) {
            @unlink($photoPath);
        }
    }
    if (!empty($bank_slip)) {
        $slipPath = $baseDir . '/' . $bank_slip;
        if (file_exists($slipPath) && is_file($slipPath)) {
            @unlink($slipPath);
        }
    }

    $conn->close();
    ob_end_clean();
    echo json_encode(['success' => true, 'message' => '']);
    exit();
} catch (Throwable $e) {
    if (isset($conn)) {
        $conn->close();
    }
    $sendJson(false, $e->getMessage());
} 