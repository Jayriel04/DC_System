<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
include('includes/dbconnection.php');

$toast_message = null;
function setToast($message, $type) {
    global $toast_message;
    $toast_message = ['message' => $message, 'type' => $type];
}

$auth = new \Delight\Auth\Auth($dbh);

if (isset($_POST['login'])) {
    try {
        $identifier = $_POST['username'];
        $password = $_POST['password'];

        // The auth library needs an email to log in.
        // We fetch the email corresponding to the user-provided identifier (username or patient number).
        $sql = "SELECT email FROM tblpatient WHERE username = ?";
        $query = $dbh->prepare($sql);
        $query->execute([$identifier]);
        $user_email = $query->fetchColumn();

        if (!$user_email) {
            // If no email is found, we can treat it as an invalid user.
            throw new \Delight\Auth\InvalidEmailException();
        }

        $rememberDuration = !empty($_POST["remember"]) ? (int) (60 * 60 * 24 * 365) : null;
        $auth->login($user_email, $password, $rememberDuration);

        // Login successful. Fetch patient data for the session.
        $patient_query = $dbh->prepare("SELECT number, firstname, surname FROM tblpatient WHERE number = ?");
        $patient_query->execute([$auth->getUserId()]);
        $patient = $patient_query->fetch(PDO::FETCH_OBJ);

        if ($patient) {
            $_SESSION['sturecmsnumber'] = $patient->number;
            $_SESSION['sturecmsfirstname'] = $patient->firstname;
            $_SESSION['sturecmssurname'] = $patient->surname;

            header('Location: ../index.php');
            exit();
        } else {
            // Inconsistent state: user exists in `users` but not `tblpatient`.
            $auth->logOut();
            setToast('Associated patient record not found. Please contact support.', 'danger');
        }
    }
    catch (\Delight\Auth\InvalidEmailException $e) {
        setToast('Invalid username or password.', 'danger');
    }
    catch (\Delight\Auth\InvalidPasswordException $e) {
        setToast('Invalid username or password.', 'danger');
    }
    catch (\Delight\Auth\EmailNotVerifiedException $e) {
        setToast('Please verify your email address.', 'warning');
    }
    catch (\Delight\Auth\TooManyRequestsException $e) {
        setToast('Too many login attempts. Please try again later.', 'danger');
    }
    catch (\Exception $e) {
        error_log($e->getMessage()); // Log the real error for debugging
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
                        <input type="text" class="form-control" id="username" placeholder="Username or Patient Number" required="true" name="username" value="">
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" class="form-control" placeholder="Password" required name="password" value="">
                            <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                        </div>
                    </div>

                    <div class="my-2 d-flex justify-content-between align-items-center" style="margin-top: 1rem; margin-bottom: 1rem;">
                        <div class="form-check">
                            <label class="form-check-label text-muted">
                                <input type="checkbox" id="remember" class="form-check-input" name="remember" value="1" /> Keep me signed in </label>
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
