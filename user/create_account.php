<?php
session_start();
include('includes/dbconnection.php');

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = md5($_POST['password']); // Hash the password

    // New fields
    $firstname = $_POST['firstname'];
    $surname = $_POST['surname'];
    $dob = $_POST['dob'];
    $address = $_POST['address'];
    $confirm_password = $_POST['confirm_password'];

    // Password confirmation check
    if ($_POST['password'] !== $confirm_password) {
        echo "<script>alert('Passwords do not match.');</script>";
    } else {
        // Check if username or email already exists
        $checkSql = "SELECT * FROM tblpatient WHERE username = :username OR email = :email";
        $checkQuery = $dbh->prepare($checkSql);
        $checkQuery->bindParam(':username', $username, PDO::PARAM_STR);
        $checkQuery->bindParam(':email', $email, PDO::PARAM_STR);
        $checkQuery->execute();

        if ($checkQuery->rowCount() > 0) {
            echo "<script>alert('Username or Email already exists. Please choose another one.');</script>";
        } else {
            // Insert new user into the tblpatient
            $sql = "INSERT INTO tblpatient (username, email, password, firstname, surname, dob, address) VALUES (:username, :email, :password, :firstname, :surname, :dob, :address)";
            $query = $dbh->prepare($sql);
            $query->bindParam(':username', $username, PDO::PARAM_STR);
            $query->bindParam(':email', $email, PDO::PARAM_STR);
            $query->bindParam(':password', $password, PDO::PARAM_STR);
            $query->bindParam(':firstname', $firstname, PDO::PARAM_STR);
            $query->bindParam(':surname', $surname, PDO::PARAM_STR);
            $query->bindParam(':dob', $dob, PDO::PARAM_STR);
            $query->bindParam(':address', $address, PDO::PARAM_STR);

            if ($query->execute()) {
                echo "<script>alert('Account created successfully. You can now log in.');</script>";
                echo "<script>window.location.href='login.php';</script>";
            } else {
                echo "<script>alert('Error creating account. Please try again.');</script>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Create Account</title>
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
                                CREATE ACCOUNT
                            </div>
                            <form class="pt-3" method="post" name="register"> 
                                <div class="form-group">
                                    <label for="firstname">Firstname</label>
                                    <input type="text" id="firstname" class="form-control form-control-lg" placeholder="Firstname" required="true" name="firstname">
                                </div>
                                <div class="form-group">
                                    <label for="surname">Surname</label>
                                    <input type="text" id="surname" class="form-control form-control-lg" placeholder="Surname" required="true" name="surname">
                                </div>
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" class="form-control form-control-lg" placeholder="Email" required="true" name="email">
                                </div>
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <input type="text" id="username" class="form-control form-control-lg" placeholder="Username" required="true" name="username">
                                </div>
                                <div class="form-group">
                                    <label for="password">Password</label>
                                    <input type="password" id="password" class="form-control form-control-lg" placeholder="Password" required="true" name="password">
                                </div>
                                <div class="form-group">
                                    <label for="confirm_password">Confirm Password</label>
                                    <input type="password" id="confirm_password" class="form-control form-control-lg" placeholder="Confirm Password" required="true" name="confirm_password">
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
</html>