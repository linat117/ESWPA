<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'conn.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $partner_id = (int)$_GET['id'];
    
    // Get partner info to delete logo file if needed
    $stmt = $conn->prepare("SELECT logo_url FROM partners WHERE id = ?");
    $stmt->bind_param("i", $partner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $partner = $result->fetch_assoc();
        
        // Delete partner from database
        $delete_stmt = $conn->prepare("DELETE FROM partners WHERE id = ?");
        $delete_stmt->bind_param("i", $partner_id);
        
        if ($delete_stmt->execute()) {
            // Delete logo file if it's a local file
            if (!empty($partner['logo_url']) && strpos($partner['logo_url'], 'http') !== 0) {
                $logo_path = '../' . $partner['logo_url'];
                if (file_exists($logo_path)) {
                    unlink($logo_path);
                }
            }
            
            header("Location: ../partners_list.php?success=Partner deleted successfully");
            exit();
        } else {
            header("Location: ../partners_list.php?error=Error deleting partner: " . urlencode($delete_stmt->error));
            exit();
        }
        $delete_stmt->close();
    } else {
        header("Location: ../partners_list.php?error=Partner not found");
        exit();
    }
    $stmt->close();
} else {
    header("Location: ../partners_list.php?error=Invalid partner ID");
    exit();
}

$conn->close();
?>
