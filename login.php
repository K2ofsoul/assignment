<?php
session_start();
if (isset($_SESSION["user"])) {
   header("Location: index.php");
   exit(); // Always use exit() after header redirection
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" type="text/css" href="css/mystyle.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body>
    <?php
    if(isset($_POST["login"])){
        $email = $_POST["email"];
        $password = $_POST["password"];
        require_once "database.php";

        // Prepared statement to avoid SQL injection
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if($user) {
            // Verify password
            if(password_verify($password, $user["password"])){
                $_SESSION['user'] = $user['email'];  // Store email in session
                $_SESSION['user_id'] = $user['id'];  // Store user ID in session
                $_SESSION['login_time'] = time(); // Store login time for session expiration

                // Set cookie (optional)
                setcookie("user", $user['email'], time() + (86400 * 30), "/"); // Cookie valid for 30 days

                header("Location: index.php");
                exit(); // Always use exit after redirection
            } else {
                echo "<div class='alert alert-danger'>Password doesn't match</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Email doesn't match</div>";
        }
    }
    ?>
    <div class="container">
        <form action="login.php" method="post">
            <div class="form-group">
               <input type="email" placeholder="Enter Email:" name="email" class="form-control" required> 
            </div>
            <div class="form-group">
               <input type="password" placeholder="Enter Password:" name="password" class="form-control" required> 
            </div>
            <div class="form-btn">
                <input type="submit" value="Login" name="login" class="btn btn-primary">
            </div>
        </form>
        <div><p>Not registered yet? <a href="Register.php">Register Here</a></p></div>
    </div>
</body>
</html>
