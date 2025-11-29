<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

$toast_message = null;
function setToast($message, $type) {
    global $toast_message;
    $toast_message = ['message' => $message, 'type' => $type];
}

if (isset($_POST['login'])) {
  $username = $_POST['username'];
  $password = md5($_POST['password']);
  $sql = "SELECT ID FROM tbladmin WHERE UserName=:username and Password=:password";
  $query = $dbh->prepare($sql);
  $query->bindParam(':username', $username, PDO::PARAM_STR);
  $query->bindParam(':password', $password, PDO::PARAM_STR);
  $query->execute();
  $results = $query->fetchAll(PDO::FETCH_OBJ);
  if ($query->rowCount() > 0) {
    foreach ($results as $result) {
      $_SESSION['sturecmsaid'] = $result->ID;
    }

    if (!empty($_POST["remember"])) {
      // Set cookies with proper path and secure parameters
      setcookie(
        "user_login",
        $_POST["username"],
        [
          'expires' => time() + (10 * 365 * 24 * 60 * 60),
          'path' => '/',
          'secure' => true,
          'httponly' => true,
          'samesite' => 'Strict'
        ]
      );
      // Store hashed password in cookie
      setcookie(
        "userpassword",
        $password, // Using the hashed password
        [
          'expires' => time() + (10 * 365 * 24 * 60 * 60),
          'path' => '/',
          'secure' => true,
          'httponly' => true,
          'samesite' => 'Strict'
        ]
      );
    } else {
      // Clear cookies if remember is not checked
      if (isset($_COOKIE["user_login"])) {
        setcookie("user_login", "", [
          'expires' => time() - 3600,
          'path' => '/',
        ]);
      }
      if (isset($_COOKIE["userpassword"])) {
        setcookie("userpassword", "", [
          'expires' => time() - 3600,
          'path' => '/',
        ]);
      }
    }
    $_SESSION['login'] = $_POST['username'];
    echo "<script type='text/javascript'> document.location ='dashboard.php'; </script>";
  } else {
    setToast('Invalid Details', 'danger');
  }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

  <title>Login Admin</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
  <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
  <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- Layout styles -->
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/toast.css">
    <style>
        
        .auth-form-light {
            border-radius: 15px; 
            padding: 20px;
            background-color: #f9f9f9; 
        }
        .form-group label {
            text-align: left;
            font-weight: normal;
            display: block;
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
        padding-right: 50px; 
    }
    </style>
</head>
<body>
    <div class="container-scroller">
        <div class="container-fluid page-body-wrapper full-page-wrapper">
            <div class="content-wrapper d-flex align-items-center auth">
                <div class="row flex-grow">
                      <div class="col-lg-4 mx-auto"> 
                          <div class="auth-form-light text-left p-5">
                              <div class="brand-logo" align="center" style="font-weight:bold">
                        <img src="../images/Jf logo.png" alt="JF Dental Care Logo" class="logo-img" style="width:35px; margin-right:10px;">
                        <h2 style="margin-top: 10px; margin-bottom: 5px;">Welcome to <br> JF Dental Care Admin</h2>     
                          <br>
                          <div id="toast-container"></div>
                            <?php
                                if ($toast_message) {
                                    echo "<script>document.addEventListener('DOMContentLoaded', function() { showToast('{$toast_message['message']}', '{$toast_message['type']}'); });</script>";
                                }
                            ?>
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
                <div class="my-2 d-flex justify-content-between align-items-center">
                  <div class="form-check">
                    <label class="form-check-label text-muted">
                      <input type="checkbox" id="remember" class="form-check-input" name="remember" value="1" <?php if (isset($_COOKIE["user_login"])) { echo 'checked'; } ?>/> Keep me signed in </label>
                  </div>
                  <a href="forgot-password.php" class="auth-link text-black">Forgot password?</a>
                </div>
                

              </form>
            </div>
          </div>  
        </div>
      </div>
      <!-- content-wrapper ends -->
    </div>
    <!-- page-body-wrapper ends -->
  </div>
  <!-- container-scroller -->
  <!-- plugins:js -->
  <script src="vendors/js/vendor.bundle.base.js"></script>
  <!-- endinject -->
  <!-- Plugin js for this page -->
  <!-- End plugin js for this page -->
  <!-- inject:js -->
  <script src="js/off-canvas.js"></script>
  <script src="js/misc.js"></script>
  <script src="js/toast.js"></script>
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
  <!-- endinject -->
</body>

</html>