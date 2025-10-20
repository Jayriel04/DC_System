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
            // Redirect user to the site index after successful login
            header('Location: ../index.php');
            exit();
    } else {
        echo "<script>alert('Invalid Details');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login User</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .close-btn {
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 35px;
            color: black; 
            background: transparent;
            border: none;
            cursor: pointer;
            outline: none;
        }
        .close-btn:hover {
            color: whitesmoke; 
        }
        .auth-form-light {
            border-radius: 15px; 
            padding: 20px;
            background-color: #f9f9f9; 
        }
                .input-group {
        position: relative;
    }
    .input-group .input-group-text {
        position: absolute;
        right: 10px; /* Adjust as necessary */
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        background: transparent; /* Make background transparent */
        border: none; /* Remove border */
    }
    .form-control {
        padding-right: 40px; /* Add padding to avoid overlap with the icon */
    }
    </style>
</head>
<body>
    <div class="container-scroller">
        <div class="container-fluid page-body-wrapper full-page-wrapper">
            <div class="content-wrapper d-flex align-items-center auth">
                <div class="row flex-grow">
                    <div class="col-lg-4 mx-auto">
                        <button onclick="window.location.href='../index.php'" class="close-btn">&times;</button> 
                        <div class="auth-form-light text-left p-5">
                            <div class="brand-logo" align="center" style="font-weight:bold">
    <img src="../images/Jf logo.png" alt="JF Dental Care Logo" class="logo-img" style="width:35px; margin-right:10px;">
    <h2 style="margin-top: 10px; margin-bottom: 5px;">Welcome to <br> JF Dental Care</h2>
</div>
                            <form class="pt-3" id="login" method="post" name="login">
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <input type="text" class="form-control form-control-lg" id="username" placeholder="Username or email" required="true" name="username" value="<?php if(isset($_COOKIE['user_login'])) { echo $_COOKIE['user_login']; } ?>" style="border-radius: 10px;">
                                </div>
                                <div class="form-group">
                                    <label for="password">Password</label>
                                    <div class="input-group">
                                        <input type="password" id="password" class="form-control form-control-lg" placeholder="Password" required name="password" style="border-radius: 10px;">
                                        <span class="input-group-text" onclick="togglePassword('password')">
                                            <i id="eye-password" class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button class="btn btn-success btn-block loginbtn" name="login" type="submit" style="border-radius: 10px;">Login</button>
                                </div>
                            </form>
                            <div class="my-2 d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <label class="form-check-label text-muted">
                                        <input type="checkbox" id="remember" class="form-check-input" name="remember" <?php if(isset($_COOKIE["user_login"])) { ?> checked <?php } ?> /> Keep me signed in </label>
                                </div>
                                <a href="forgot-password.php" class="auth-link text-black">Forgot password?</a>
                            </div>
                            
                            <div class="text-center">
                                <a href="create_account.php" class="btn btn-primary btn-block" style="border-radius:10px;">Create Account</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <script>
function togglePassword(id) {
    var input = document.getElementById(id);
    var eyeIcon = document.getElementById('eye-' + id);
    
    if (input.type === "password") {
        input.type = "text";
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
    } else {
        input.type = "password";
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
    }
}
</script>
</body>
</html>
