<?php
session_start();
require_once "database.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Get the current user ID
$user_email = $_SESSION['user'];
$sql_user = "SELECT id FROM users WHERE email = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param('s', $user_email);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();
$user_id = $user['id'];

// Insert the message
if (isset($_POST['message'])) {
    $message = $_POST['message'];
    $sql_message = "INSERT INTO messages (user_id, message) VALUES (?, ?)";
    $stmt_message = $conn->prepare($sql_message);
    $stmt_message->bind_param('is', $user_id, $message);
    $stmt_message->execute();
    
    // Get the inserted message ID
    $message_id = $conn->insert_id;
    
    // Handle file uploads
    $allowed_types = array('pdf', 'doc', 'docx', 'jpg');
    $upload_dir = 'uploads/';
    
    foreach ($_FILES['files']['name'] as $key => $file_name) {
        $file_tmp = $_FILES['files']['tmp_name'][$key];
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        
        // Check if the file type is allowed
        if (in_array($file_ext, $allowed_types)) {
            $file_new_name = time() . '_' . $file_name;
            $file_path = $upload_dir . $file_new_name;
            
            // Move the uploaded file to the uploads directory
            if (move_uploaded_file($file_tmp, $file_path)) {
                // Insert file info into the database
                $sql_file = "INSERT INTO files (message_id, file_name) VALUES (?, ?)";
                $stmt_file = $conn->prepare($sql_file);
                $stmt_file->bind_param('is', $message_id, $file_new_name);
                $stmt_file->execute();
            }
        }
    }
    
    // Redirect back to the message board
    header("Location: index.php");
    exit();
}
?>
