<?php
session_start();
require_once "database.php";

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Fetch user details
$user_email = $_SESSION['user'];
$user_id = $_SESSION['user_id'];

// Add a new message
if (isset($_POST['add_message'])) {
    $message = $_POST['message'];
    $sql = "INSERT INTO messages (user_id, message) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $user_id, $message);
    $stmt->execute();
    header("Location: message_board.php");
    exit();
}

// Update a message
if (isset($_POST['edit_message'])) {
    $message_id = $_POST['message_id'];
    $new_message = $_POST['new_message'];

    // Check if the message belongs to the logged-in user
    $sql = "UPDATE messages SET message = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sii', $new_message, $message_id, $user_id);
    $stmt->execute();
    header("Location: message_board.php");
    exit();
}

// Delete a message
if (isset($_POST['delete_message'])) {
    $message_id = $_POST['message_id'];

    // Check if the message belongs to the logged-in user
    $sql = "DELETE FROM messages WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $message_id, $user_id);
    $stmt->execute();
    header("Location: message_board.php");
    exit();
}

// Fetch all messages
$sql_messages = "SELECT messages.id, messages.message, messages.created_at, users.email FROM messages JOIN users ON messages.user_id = users.id ORDER BY created_at DESC";
$result_messages = $conn->query($sql_messages);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Board</title>
</head>
<body>
    <h1>Welcome, <?= $_SESSION['user'] ?>!</h1>

    <!-- Display all messages -->
    <h2>All Messages</h2>
    <?php while ($message = $result_messages->fetch_assoc()): ?>
        <div class="message-box">
            <p><strong><?= $message['email'] ?>:</strong> <?= $message['message'] ?></p>
            <small>Posted on: <?= $message['created_at'] ?></small>

            <!-- Edit/Delete options for the owner of the message -->
            <?php if ($message['email'] === $_SESSION['user']): ?>
                <!-- Edit form -->
                <form action="message_board.php" method="post" style="display:inline;">
                    <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                    <input type="text" name="new_message" value="<?= $message['message'] ?>" required>
                    <button type="submit" name="edit_message">Edit</button>
                </form>

                <!-- Delete form -->
                <form action="message_board.php" method="post" style="display:inline;">
                    <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                    <button type="submit" name="delete_message">Delete</button>
                </form>
            <?php endif; ?>
        </div>
        <hr>
    <?php endwhile; ?>

    <!-- Add a new message -->
    <h2>Add a Message</h2>
    <form action="message_board.php" method="post">
        <textarea name="message" placeholder="Write your message" required></textarea><br>
        <button type="submit" name="add_message">Add Message</button>
    </form>

    <a href="logout.php">Logout</a>
</body>
</html>
