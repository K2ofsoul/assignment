<?php
session_start();
if (isset($_SESSION["user"]))
{
   header("Location: index.php");
   exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Design</title>
    <link rel="stylesheet" type="text/css" href="css/mystyle.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body>
<div class="container">
    <?php
    if (isset($_POST["submit"]))
    {
        $fullname = $_POST["fullname"];
        $email = $_POST["email"];
        $password = $_POST["password"];
        $passwordRepeat = $_POST["repeat_password"];
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $errors = array();
        
        // Kiểm tra các trường bắt buộc
        if (empty($fullname) || empty($email) || empty($password) || empty($passwordRepeat))
        {
            array_push($errors, "All fields are required");
        }
        // Kiểm tra email hợp lệ
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            array_push($errors, "Email is not valid");
        }
        // Kiểm tra độ dài mật khẩu
        if (strlen($password) < 8)
        {
            array_push($errors, "Password must be at least 8 characters long");
        }
        // Kiểm tra mật khẩu nhập lại
        if ($password !== $passwordRepeat)
        {
            array_push($errors, "Passwords do not match");
        }

        // Kết nối cơ sở dữ liệu
        require_once "database.php";

        // Kiểm tra email đã tồn tại chưa
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = mysqli_stmt_init($conn);
        if (mysqli_stmt_prepare($stmt, $sql))
        {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $rowCount = mysqli_num_rows($result);
            if ($rowCount > 0)
            {
                array_push($errors, "Email already exists!");
            }
        }

        // Hiển thị lỗi nếu có
        if (count($errors) > 0)
        {
            foreach ($errors as $error)
            {
                echo "<div class='alert alert-danger'>$error</div>";
            }
        }
        else
        {
            // Thêm người dùng vào cơ sở dữ liệu
            $sql = "INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)";
            $stmt = mysqli_stmt_init($conn);
            if (mysqli_stmt_prepare($stmt, $sql))
            {
                mysqli_stmt_bind_param($stmt, "sss", $fullname, $email, $password_hash);
                mysqli_stmt_execute($stmt);
                echo "<div class='alert alert-success'>You are registered successfully</div>";
            }
            else
            {
                die("Something went wrong");
            }
        }
    }
    ?>
    <form action="Register.php" method="post">
        <div class="form-group">
            <input type="text" class="form-control" name="fullname" placeholder="Full Name:">
        </div>
        <div class="form-group">
            <input type="email" class="form-control" name="email" placeholder="Email:">
        </div>
        <div class="form-group">
            <input type="password" class="form-control" name="password" placeholder="Password:">
        </div>
        <div class="form-group">
            <input type="password" class="form-control" name="repeat_password" placeholder="Repeat Password:">
        </div>
        <div class="form_btn">
            <input type="submit" class="btn btn-primary" value="Register" name="submit">
        </div>
    </form>
    <div><p>Already Registered? <a href="login.php">Login Here</a></p></div>
</div>
</body>
</html>
