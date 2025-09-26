<?php
session_start();
include('includes/dbconnection.php');

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = md5($_POST['password']); // Hash the password

    // Check if username already exists
    $checkSql = "SELECT * FROM tblpatient WHERE username = :username";
    $checkQuery = $dbh->prepare($checkSql);
    $checkQuery->bindParam(':username', $username, PDO::PARAM_STR);
    $checkQuery->execute();

    if ($checkQuery->rowCount() > 0) {
        echo "<script>alert('Username already exists. Please choose another one.');</script>";
    } else {
        // Insert new user into the tblpatient
        $sql = "INSERT INTO tblpatient (username, password) VALUES (:username, :password)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':username', $username, PDO::PARAM_STR);
        $query->bindParam(':password', $password, PDO::PARAM_STR);
        
        if ($query->execute()) {
            echo "<script>alert('Account created successfully. You can now log in.');</script>";
            echo "<script>window.location.href='login.php';</script>";
        } else {
            echo "<script>alert('Error creating account. Please try again.');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Create Account - Student Management System</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container-scroller">
        <div class="container-fluid page-body-wrapper full-page-wrapper">
            <div class="content-wrapper d-flex align-items-center auth">
                <div class="row flex-grow">
                    <div class="col-lg-4 mx-auto">
                        <div class="auth-form-light text-left p-5">
                            <div class="brand-logo" align="center" style="font-weight:bold">
                                Student Management System
                            </div>
                            <h6 class="font-weight-light">Create your account.</h6>
                            <form class="pt-3" method="post" name="register">
                                <div class="form-group">
                                    <input type="text" class="form-control form-control-lg" placeholder="Firstname" required="true" name="firstname">
                                </div>
                                    <div class="form-group">
                                    <input type="text" class="form-control form-control-lg" placeholder="Surname" required="true" name="surname">
                                </div>
                                <div class="form-group">
                                    <label for="date_of_birth">Date of Birth</label>
                                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                                </div>   
                                <div class="form-group">
                                    <input type="text" class="form-control form-control-lg" placeholder="Username" required="true" name="username">
                                </div>
                                <div class="form-group">
                                    <input type="password" class="form-control form-control-lg" placeholder="Password" required="true" name="password">
                                </div>
                                <div class="mt-3">
                                    <button class="btn btn-success btn-block" name="register" type="submit">Create Account</button>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="login.php" class="auth-link text-black">Already have an account? Login here</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
</body>
</html>v