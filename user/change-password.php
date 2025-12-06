<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsnumber']) == 0) {
    header('location:logout.php');
    exit();
}

$patient_number = $_SESSION['sturecmsnumber'];
$toast_message = null;
function setToast($message, $type) {
    global $toast_message;
    $toast_message = ['message' => $message, 'type' => $type];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    // Basic validation
    if ($_POST['newpassword'] !== $_POST['confirmpassword']) {
        setToast('New Password and Confirm Password fields do not match.', 'danger');
    } else {
        $current_password_input = $_POST['currentpassword'];
        $new_password_input = $_POST['newpassword'];

        $sql = "SELECT password FROM tblpatient WHERE number=:sid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':sid', $patient_number, PDO::PARAM_INT);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_OBJ);

        if ($result && password_verify($current_password_input, $result->password)) {
            $new_hashed_password = password_hash($new_password_input, PASSWORD_DEFAULT);
            $con = "UPDATE tblpatient SET password=:newpassword WHERE number=:sid";
            $chngpwd1 = $dbh->prepare($con);
            $chngpwd1->bindParam(':sid', $patient_number, PDO::PARAM_INT);
            $chngpwd1->bindParam(':newpassword', $new_hashed_password, PDO::PARAM_STR);
            if ($chngpwd1->execute()) {
                $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'Your password was successfully changed.'];
                header('Location: profile.php');
                exit();
            } else {
                setToast('Something went wrong. Please try again.', 'danger');
            }
        } else {
            setToast('Your current password is wrong.', 'danger');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Change Password</title>
    <link rel="stylesheet" href="./css/profile.css">
    <link href="./css/header.css" rel="stylesheet">
    <link href="../css/toast.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script type="text/javascript">
        function checkpass() {
            if (document.changepassword.newpassword.value != document.changepassword.confirmpassword.value) {
                showToast('New Password and Confirm Password fields do not match.', 'danger');
                document.changepassword.confirmpassword.focus();
                return false;
            }
            return true;
        }
    </script>
</head>

<body>
    <?php include_once(__DIR__ . '/./includes/header.php'); ?>
    <div class="auth-container">
        <div class="left-panel">
            <h1 class="hero-title">
                Secure Your Account
            </h1>
            <p class="tagline" style="margin-bottom: 60px;">Keep your account safe by using a strong, unique password.
            </p>
            <div class="illustration">🔑</div>
        </div>

        <div class="right-panel">
            <a href="profile.php" class="close-btn" title="Back to Profile">&times;</a>

            <h2 class="form-title">Change Password</h2>

            <div id="toast-container"></div>

            <form name="changepassword" method="post" onsubmit="return checkpass();">
                <?php
                    if ($toast_message) {
                        echo "<script>document.addEventListener('DOMContentLoaded', function() { showToast('{$toast_message['message']}', '{$toast_message['type']}'); });</script>";
                    }
                ?>
                <div class="form-group">
                    <label for="currentpassword">Current Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="currentpassword" id="currentpassword" required="true">
                        <i class="password-toggle-icon fas fa-eye"
                            onclick="togglePasswordVisibility('currentpassword', this)"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label for="newpassword">New Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="newpassword" id="newpassword" required="true">
                        <i class="password-toggle-icon fas fa-eye"
                            onclick="togglePasswordVisibility('newpassword', this)"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirmpassword">Confirm New Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="confirmpassword" id="confirmpassword" required="true">
                        <i class="password-toggle-icon fas fa-eye"
                            onclick="togglePasswordVisibility('confirmpassword', this)"></i>
                    </div>
                </div>
                <button type="submit" class="auth-btn" name="submit">Update Password</button>
            </form>
        </div>
    </div>
    <script src="../js/toast.js"></script>
    <script>
        function togglePasswordVisibility(inputId, icon) {
            const passwordInput = document.getElementById(inputId);
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        }
    </script>
</body>

</html>