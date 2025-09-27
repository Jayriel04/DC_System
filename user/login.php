<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (isset($_POST['login'])) {
    $username = $_POST['username']; // Changed to username
    $password = md5($_POST['password']);

    // Updated SQL query to use tblpatient
    $sql = "SELECT number, firstname, surname FROM tblpatient WHERE (username = :username OR number = :username) AND password = :password";
    $query = $dbh->prepare($sql);
    $query->bindParam(':username', $username, PDO::PARAM_STR);
    $query->bindParam(':password', $password, PDO::PARAM_STR);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_OBJ);

    if ($query->rowCount() > 0) {
        foreach ($results as $result) {
            $_SESSION['sturecmsnumber'] = $result->number;
            $_SESSION['sturecmsfirstname'] = $result->firstname;
            $_SESSION['sturecmssurname'] = $result->surname;
        }

        if (!empty($_POST["remember"])) {
            // COOKIES for username
            setcookie("user_login", $_POST["username"], time() + (10 * 365 * 24 * 60 * 60));
            // COOKIES for password
            setcookie("userpassword", $_POST["password"], time() + (10 * 365 * 24 * 60 * 60));
        } else {
            if (isset($_COOKIE["user_login"])) {
                setcookie("user_login", "");
                if (isset($_COOKIE["userpassword"])) {
                    setcookie("userpassword", "");
                }
            }
        }
            $_SESSION['login'] = $_POST['username'];
            // Redirect user to the dashboard so the health modal can show if needed
            header('Location: dashboard.php');
            exit();
    } else {
        echo "<script>alert('Invalid Details');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login Page</title>
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
                                <h2>Login</h2>
                            </div>
                            <h6 class="font-weight-light">Sign in to continue.</h6>
                            <form class="pt-3" id="login" method="post" name="login">
                                <div class="form-group">
                                    <input type="text" class="form-control form-control-lg" placeholder="Enter your username or student number" required="true" name="username" value="<?php if(isset($_COOKIE["user_login"])) { echo $_COOKIE["user_login"]; } ?>">
                                </div>
                                <div class="form-group">
                                    <input type="password" class="form-control form-control-lg" placeholder="Enter your password" name="password" required="true" value="<?php if(isset($_COOKIE["userpassword"])) { echo $_COOKIE["userpassword"]; } ?>">
                                </div>
                                <div class="mt-3">
                                    <button class="btn btn-success btn-block loginbtn" name="login" type="submit">Login</button>
                                </div>
                                <div class="my-2 d-flex justify-content-between align-items-center">
                                    <div class="form-check">
                                        <label class="form-check-label text-muted">
                                            <input type="checkbox" id="remember" class="form-check-input" name="remember" <?php if(isset($_COOKIE["user_login"])) { ?> checked <?php } ?> /> Keep me signed in </label>
                                    </div>
                                    <a href="forgot-password.php" class="auth-link text-black">Forgot password?</a>
                                </div>
                                <div class="mb-2">
                                    <a href="../index.php" class="btn btn-block btn-facebook auth-form-btn">
                                        <i class="icon-social-home mr-2"></i>Back Home </a>
                                </div>
                                <div class="text-center">
                                    <a href="create_account.php" class="btn btn-primary btn-block">Create Account</a> <!-- New button for account creation -->
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