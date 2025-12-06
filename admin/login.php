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
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <meta charset="utf-8">
  <title>Login Admin</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
  <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
  <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- Layout styles -->
  
  <link rel="stylesheet" href="css/login-v2.css">
  <link rel="stylesheet" href="css/toast.css">
  <link rel="stylesheet" href="css/responsive.css">
</head>
<body>
  <div class="container">
    <div class="left-section">
            <div class="floating-shape shape1"></div>
            <div class="floating-shape shape2"></div>
            <div class="floating-shape shape3"></div>
            
            <div class="illustration">
                <div class="tooth">
                    🦷
                    <div class="tooth-icon">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
                <div class="toothbrush-emoji">🪥</div>
                <div class="syringe-emoji">💉</div>
                <div class="wrench-emoji">🔧</div>
                <div class="magnifier-emoji">🔎</div>
            </div>
        </div>

    <div class="right-section">
        <div class="logo-section">
            <img src="../images/Jf logo.png" alt="JF Dental Care Logo" class="logo">
            <div class="logo-text">
                <h1 style="color: #092c7a;">JF DENTAL CARE</h1>
            </div>
        </div>

        <div class="welcome-text">
            <h2>Welcome Back!</h2>
            <p>Sign in to your admin account</p>
        </div>

        <div id="toast-container"></div>
        <?php
            if ($toast_message) {
                echo "<script>document.addEventListener('DOMContentLoaded', function() { showToast('{$toast_message['message']}', '{$toast_message['type']}'); });</script>";
            }
        ?>

        <form method="post" name="login">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required value="<?php if(isset($_COOKIE['user_login'])) { echo $_COOKIE['user_login']; } ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" required>
                    <button type="button" class="toggle-password" onclick="togglePassword()">
                      <i id="eye-icon" class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="options-wrapper">
              <div class="checkbox-wrapper">
                  <input type="checkbox" id="remember" name="remember" value="1" <?php if (isset($_COOKIE["user_login"])) { echo 'checked'; } ?>>
                  <label for="remember">Keep me signed in</label>
              </div>
              <a href="forgot-password.php" class="forgot-password">Forgot password?</a>
            </div>

            <button type="submit" name="login" class="login-btn">Login</button>
        </form>
    </div>
  </div>

  <script src="js/toast.js"></script>
  <script>
      function togglePassword() {
          const passwordInput = document.getElementById('password');
          const eyeIcon = document.getElementById('eye-icon');
          
          if (passwordInput.type === 'password') {
              passwordInput.type = 'text';
              eyeIcon.classList.remove('fa-eye');
              eyeIcon.classList.add('fa-eye-slash');
          } else {
              passwordInput.type = 'password';
              eyeIcon.classList.remove('fa-eye-slash');
              eyeIcon.classList.add('fa-eye');
          }
      }
  </script>
</body>

</html>