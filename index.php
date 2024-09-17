<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

// Check if session has expired (15 minutes)
if (isset($_SESSION['login_time'])) {
    if (time() - $_SESSION['login_time'] > 900) {
        session_unset();
        session_destroy();
        header("Location: login.php?message=Session expired, please login again");
        exit();
    } else {
        $_SESSION['login_time'] = time();
    }
}

require_once "database.php";

$user_id = $_SESSION['user_id'];

function uploadFiles($message_id, $conn) {
    $allowed_types = ['pdf', 'doc', 'docx', 'jpg'];
    $upload_dir = 'uploads/';

    if (!is_dir($upload_dir)) {
        echo "<div class='alert alert-danger'>Upload directory does not exist. Please create a folder named 'uploads' and give it the right permissions.</div>";
        return;
    }

    foreach ($_FILES['files']['name'] as $key => $file_name) {
        $file_tmp = $_FILES['files']['tmp_name'][$key];
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $file_error = $_FILES['files']['error'][$key];
        $file_size = $_FILES['files']['size'][$key];

        if ($file_error !== UPLOAD_ERR_OK) {
            echo "<div class='alert alert-danger'>Error uploading file: $file_name. Error code: $file_error</div>";
            continue;
        }

        if (!in_array($file_ext, $allowed_types)) {
            echo "<div class='alert alert-danger'>Invalid file type: $file_name</div>";
            continue;
        }
        if ($file_size > 2 * 1024 * 1024) {
            echo "<div class='alert alert-danger'>File too large: $file_name</div>";
            continue;
        }
        $file_new_name = time() . '_' . $file_name;
        $file_path = $upload_dir . $file_new_name;

        if (move_uploaded_file($file_tmp, $file_path)) {
            $sql = "INSERT INTO files (message_id, file_name, file_path) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iss', $message_id, $file_new_name, $file_path);
            $stmt->execute();
        } else {
            echo "<div class='alert alert-danger'>Failed to move file: $file_name</div>";
        }
    }
}

// Add a new message
if (isset($_POST['add_message'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $sql = "INSERT INTO messages (user_id, message) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('is', $user_id, $message);
        if ($stmt->execute()) {
            $message_id = $conn->insert_id;
            if (!empty($_FILES['files']['name'][0])) {
                uploadFiles($message_id, $conn); 
            }
            header("Location: index.php");
            exit();
        }
    } else {
        echo "<div class='alert alert-danger'>Message cannot be empty</div>";
    }
}
if (isset($_POST['delete_message'])) {
    $message_id = $_POST['message_id'];
    
    $sql_files = "SELECT file_path FROM files WHERE message_id = ?";
    $stmt_files = $conn->prepare($sql_files);
    $stmt_files->bind_param('i', $message_id);
    $stmt_files->execute();
    $result_files = $stmt_files->get_result();
    
    while ($file = $result_files->fetch_assoc()) {
        unlink($file['file_path']); 
    }
    $sql_delete_files = "DELETE FROM files WHERE message_id = ?";
    $stmt_delete_files = $conn->prepare($sql_delete_files);
    $stmt_delete_files->bind_param('i', $message_id);
    $stmt_delete_files->execute();

    $sql = "DELETE FROM messages WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $message_id, $user_id);
    $stmt->execute();
    
    header("Location: index.php");
    exit();
}
if (isset($_POST['edit_message'])) {
    $message_id = $_POST['message_id'];
    $new_message = trim($_POST['new_message']);
    
    if (!empty($new_message)) {
        $sql = "UPDATE messages SET message = ? WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sii', $new_message, $message_id, $user_id);
        $stmt->execute();

        if (!empty($_FILES['files']['name'][0])) {
            uploadFiles($message_id, $conn);
        }
        header("Location: index.php");
        exit();
    } else {
        echo "<div class='alert alert-danger'>Message cannot be empty</div>";
    }
}
$sql_messages = "SELECT messages.id, messages.message, messages.created_at, users.email 
                 FROM messages 
                 JOIN users ON messages.user_id = users.id 
                 ORDER BY created_at DESC";
$result_messages = $conn->query($sql_messages);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Board with File Uploads</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1>Welcome</h1>
        <a href="logout.php" class="btn btn-warning">Logout</a>
        <hr>
        <h2>Messages</h2>

        <?php while ($message = $result_messages->fetch_assoc()): ?>
            <div class="message-box mb-4">
                <p><strong><?= $message['email'] ?>:</strong> <?= $message['message'] ?></p>
                <small>Posted on: <?= $message['created_at'] ?></small>
                <?php
                $message_id = $message['id'];
                $sql_files = "SELECT * FROM files WHERE message_id = ?";
                $stmt_files = $conn->prepare($sql_files);
                $stmt_files->bind_param('i', $message_id);
                $stmt_files->execute();
                $result_files = $stmt_files->get_result();
                if ($result_files->num_rows > 0): ?>
                    <ul>
                    <?php while ($file = $result_files->fetch_assoc()): ?>
                        <li><a href="<?= $file['file_path'] ?>" download><?= $file['file_name'] ?></a></li>
                    <?php endwhile; ?>
                    </ul>
                <?php endif; ?>

                <?php if ($message['email'] === $_SESSION['user']): ?>
                    <form action="index.php" method="post" enctype="multipart/form-data" style="display:inline;">
                    <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                    <input type="text" name="new_message" value="<?= $message['message'] ?>" required>
                    <input type="file" name="files[]" multiple>
                    <button type="submit" name="edit_message" class="btn btn-primary btn-sm">Edit</button>
</form>

                    <form action="index.php" method="post" style="display:inline;">
                        <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                        <button type="submit" name="delete_message" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                <?php endif; ?>
            </div>
            <hr>
        <?php endwhile; ?>

        <h2>Add a New Message</h2>
        <form action="index.php" method="post" enctype="multipart/form-data">
            <textarea name="message" class="form-control" placeholder="Write your message here..." required></textarea>
            <input type="file" name="files[]" multiple class="form-control mt-2">
            <button type="submit" name="add_message" class="btn btn-success mt-2">Add Message</button>
        </form>
    </div>
</body>
</html>