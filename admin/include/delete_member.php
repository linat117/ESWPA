<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include 'conn.php';

if (isset($_POST['id'])) {
    $memberId = $_POST['id'];

    $conn->begin_transaction();

    try {
        // First, get the file names to delete them from the server
        $stmt_select = $conn->prepare("SELECT photo, bank_slip FROM registrations WHERE id = ?");
        $stmt_select->bind_param("i", $memberId);
        $stmt_select->execute();
        $result = $stmt_select->get_result();
        $row = $result->fetch_assoc();
        $stmt_select->close();

        if ($row) {
            $photo = $row['photo'];
            $bank_slip = $row['bank_slip'];

            // Now, delete the record from the database
            $stmt_delete = $conn->prepare("DELETE FROM registrations WHERE id = ?");
            $stmt_delete->bind_param("i", $memberId);
            $stmt_delete->execute();
            $stmt_delete->close();

            // Delete the files if they exist after successful DB deletion
            if (!empty($photo) && file_exists('../../uploads/members/' . $photo)) {
                unlink('../../uploads/members/' . $photo);
            }
            if (!empty($bank_slip) && file_exists('../../uploads/bankslip/' . $bank_slip)) {
                unlink('../../uploads/bankslip/' . $bank_slip);
            }

            $conn->commit();
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('Member not found.');
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?> 