<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsnumber']) == 0) {
    header('location:logout.php');
    exit();
}

$patient_number = $_SESSION['sturecmsnumber'];
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    // Basic validation
    if ($_POST['newpassword'] !== $_POST['confirmpassword']) {
        $message = 'New Password and Confirm Password fields do not match.';
        $message_type = 'danger';
    } else {
        $cpassword = md5($_POST['currentpassword']); // Using md5 for consistency with login
        $newpassword = md5($_POST['newpassword']);

        $sql = "SELECT number FROM tblpatient WHERE number=:sid AND password=:cpassword";
        $query = $dbh->prepare($sql);
        $query->bindParam(':sid', $patient_number, PDO::PARAM_INT);
        $query->bindParam(':cpassword', $cpassword, PDO::PARAM_STR);
        $query->execute();

        if ($query->rowCount() > 0) {
            $con = "UPDATE tblpatient SET password=:newpassword WHERE number=:sid";
            $chngpwd1 = $dbh->prepare($con);
            $chngpwd1->bindParam(':sid', $patient_number, PDO::PARAM_INT);
            $chngpwd1->bindParam(':newpassword', $newpassword, PDO::PARAM_STR);
            if ($chngpwd1->execute()) {
                $_SESSION['profile_message'] = 'Your password was successfully changed.';
                header('Location: profile.php');
                exit();
            } else {
                $message = 'Something went wrong. Please try again.';
                $message_type = 'danger';
            }
        } else {
            $message = 'Your current password is wrong.';
            $message_type = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Change Password</title>
    <link rel="stylesheet" href="./css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script type="text/javascript">
        function checkpass() {
            if (document.changepassword.newpassword.value != document.changepassword.confirmpassword.value) {
                alert('New Password and Confirm Password fields do not match.');
                document.changepassword.confirmpassword.focus();
                return false;
            }
            return true;
        }
    </script>
</head>

<body>
    <?php include_once(__DIR__ . '/../includes/header.php'); ?>
    <div class="auth-container">
        <div class="left-panel">
            <h1 class="hero-title">
                Secure Your Account
            </h1>
            <p class="tagline" style="margin-bottom: 60px;">Keep your account safe by using a strong, unique password.</p>
            <div class="illustration">🔑</div>
        </div>

        <div class="right-panel">
            <a href="profile.php" class="close-btn" title="Back to Profile">&times;</a>

            <h2 class="form-title">Change Password</h2>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form name="changepassword" method="post" onsubmit="return checkpass();">
                <div class="form-group">
                    <label for="currentpassword">Current Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="currentpassword" id="currentpassword" required="true">
                        <i class="password-toggle-icon fas fa-eye" onclick="togglePasswordVisibility('currentpassword', this)"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label for="newpassword">New Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="newpassword" id="newpassword" required="true">
                        <i class="password-toggle-icon fas fa-eye" onclick="togglePasswordVisibility('newpassword', this)"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirmpassword">Confirm New Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="confirmpassword" id="confirmpassword" required="true">
                        <i class="password-toggle-icon fas fa-eye" onclick="togglePasswordVisibility('confirmpassword', this)"></i>
                    </div>
                </div>
                <button type="submit" class="auth-btn" name="submit">Update Password</button>
            </form>
        </div>
    </div>
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