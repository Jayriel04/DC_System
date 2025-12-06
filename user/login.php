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
    $username = $_POST['username']; // Changed to username
    $password_input = $_POST['password'];

    // Updated SQL query to use tblpatient
    $sql = "SELECT number, firstname, surname, password FROM tblpatient WHERE (username = :username OR number = :username)";
    $query = $dbh->prepare($sql);
    $query->bindParam(':username', $username, PDO::PARAM_STR);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_OBJ);

    // Verify password against the stored hash
    if ($query->rowCount() > 0 && password_verify($password_input, $result->password)) {
        // Check for legacy md5 passwords and update them to the new hash
        if (password_needs_rehash($result->password, PASSWORD_DEFAULT)) {
            $new_hash = password_hash($password_input, PASSWORD_DEFAULT);
            $rehash_sql = "UPDATE tblpatient SET password = :new_hash WHERE number = :number";
            $rehash_query = $dbh->prepare($rehash_sql);
            $rehash_query->execute([':new_hash' => $new_hash, ':number' => $result->number]);
        }

            $_SESSION['sturecmsnumber'] = $result->number;
            $_SESSION['sturecmsfirstname'] = $result->firstname;
            $_SESSION['sturecmssurname'] = $result->surname;

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
        setToast('Invalid username or password.', 'danger');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login User</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="../css/toast.css">
</head>
<body>
    <div class="container-login">
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
            <a href="../index.php" class="close-btn" title="Close">&times;</a>
            <div class="header">
                <a href="../index.php" class="logo">
                    <img src="../images/Jf logo.png" alt="JF Dental Care Logo">
                    <span style="color: #092c7a;">JF DENTAL CARE</span>
                </a>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="color: #999; font-size: 14px;">Don't have an account?</span>
                    <a href="create_account.php" class="create-account-link">SIGN UP</a>
                </div>
            </div>

            <div class="form-container">
                <h1>Welcome Back!</h1>
                <p class="subtitle">Sign in to your account</p>

                <div id="toast-container"></div>
                <script src="../js/toast.js"></script>
                <?php
                    if ($toast_message) {
                        echo "<script>showToast('{$toast_message['message']}', '{$toast_message['type']}');</script>";
                    }
                ?>

                <form id="login" method="post" name="login">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" id="username" placeholder="Username or email" required="true" name="username" value="<?php if(isset($_COOKIE['user_login'])) { echo $_COOKIE['user_login']; } ?>">
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" class="form-control" placeholder="Password" required name="password" value="<?php if(isset($_COOKIE['userpassword'])) { echo $_COOKIE['userpassword']; } ?>">
                            <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                        </div>
                    </div>

                    <div class="my-2 d-flex justify-content-between align-items-center" style="margin-top: 1rem; margin-bottom: 1rem;">
                        <div class="form-check">
                            <label class="form-check-label text-muted">
                                <input type="checkbox" id="remember" class="form-check-input" name="remember" <?php if(isset($_COOKIE["user_login"])) { ?> checked <?php } ?> /> Keep me signed in </label>
                        </div>
                        <a href="forgot-password.php" class="auth-link">Forgot password?</a>
                    </div>

                    <button type="submit" name="login" class="login-btn">Login</button>
                </form>

               
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password toggle functionality
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>
