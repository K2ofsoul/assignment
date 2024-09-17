<?php
session_start();
$response = array('active' => false);

if (isset($_SESSION['login_time'])) {
    if (time() - $_SESSION['login_time'] = 900) {  // 900 seconds = 15 minutes
        $response['active'] = true;
    } else {
        session_unset();
        session_destroy();
    }
}

echo json_encode($response);
?>
