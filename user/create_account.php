<?php
session_start();
include('includes/dbconnection.php');

// Only use columns that exist in tblpatient:
// firstname, surname, date_of_birth, sex, status, occupation, age,
// contact_number, address, username, password, Image, health_conditions

if (isset($_POST['register'])) {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password_raw = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // Required fields from form
    $firstname = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
    $surname = isset($_POST['surname']) ? trim($_POST['surname']) : '';
    $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null; // YYYY-MM-DD or null
    $sex = !empty($_POST['sex']) ? $_POST['sex'] : null;
    $contact_number = !empty($_POST['contact_number']) ? trim($_POST['contact_number']) : null;
    $address = !empty($_POST['address']) ? trim($_POST['address']) : null;

    // Basic validation
    if ($password_raw === '' || $username === '' || $firstname === '' || $surname === '' || $email === '') {
        echo "<script>alert('Please fill in required fields.');</script>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Please provide a valid email address.');</script>";
    } elseif ($password_raw !== $confirm_password) {
        echo "<script>alert('Passwords do not match.');</script>";
    } else {
        // Check if username or email already exist in tblpatient
        $checkSql = "SELECT number FROM tblpatient WHERE username = :username OR email = :email";
        $checkQuery = $dbh->prepare($checkSql);
        $checkQuery->bindValue(':username', $username, PDO::PARAM_STR);
        $checkQuery->bindValue(':email', $email, PDO::PARAM_STR);
        $checkQuery->execute();

        if ($checkQuery->rowCount() > 0) {
            echo "<script>alert('Username or email already exists. Please choose another one.');</script>";
        } else {
            // compute age from date_of_birth if provided
            $age = null;
            if (!empty($date_of_birth)) {
                $dob_dt = DateTime::createFromFormat('Y-m-d', $date_of_birth);
                if ($dob_dt !== false) {
                    $today = new DateTime('today');
                    $age = $dob_dt->diff($today)->y;
                } else {
                    // invalid date format, set to null
                    $date_of_birth = null;
                }
            }

            // keep existing hashing method to stay compatible with current login flow
            $password = md5($password_raw);

            // Prepare INSERT using only tblpatient columns (email added)
            $sql = "INSERT INTO tblpatient (firstname, surname, date_of_birth, sex, status, occupation, age, contact_number, address, email, username, password, Image, health_conditions) VALUES (:firstname, :surname, :date_of_birth, :sex, NULL, NULL, :age, :contact_number, :address, :email, :username, :password, NULL, NULL)";
            $query = $dbh->prepare($sql);

            // Bind values, use PARAM_NULL when appropriate to avoid type errors
            $query->bindValue(':firstname', $firstname, PDO::PARAM_STR);
            $query->bindValue(':surname', $surname, PDO::PARAM_STR);

            if ($date_of_birth !== null) {
                $query->bindValue(':date_of_birth', $date_of_birth, PDO::PARAM_STR);
            } else {
                $query->bindValue(':date_of_birth', null, PDO::PARAM_NULL);
            }

            if ($sex !== null) {
                $query->bindValue(':sex', $sex, PDO::PARAM_STR);
            } else {
                $query->bindValue(':sex', null, PDO::PARAM_NULL);
            }

            if ($age !== null) {
                $query->bindValue(':age', $age, PDO::PARAM_INT);
            } else {
                $query->bindValue(':age', null, PDO::PARAM_NULL);
            }

            if ($contact_number !== null) {
                $query->bindValue(':contact_number', $contact_number, PDO::PARAM_STR);
            } else {
                $query->bindValue(':contact_number', null, PDO::PARAM_NULL);
            }

            if ($address !== null) {
                $query->bindValue(':address', $address, PDO::PARAM_STR);
            } else {
                $query->bindValue(':address', null, PDO::PARAM_NULL);
            }

            // bind email
            if ($email !== '') {
                $query->bindValue(':email', $email, PDO::PARAM_STR);
            } else {
                $query->bindValue(':email', null, PDO::PARAM_NULL);
            }

            $query->bindValue(':username', $username, PDO::PARAM_STR);
            $query->bindValue(':password', $password, PDO::PARAM_STR);

            if ($query->execute()) {
                // Auto-login the newly created patient: set same session vars as login.php and redirect to dashboard
                $patientId = $dbh->lastInsertId();
                // Ensure integer
                $patientId = $patientId ? (int)$patientId : null;
                $_SESSION['sturecmsnumber'] = $patientId;
                $_SESSION['sturecmsfirstname'] = $firstname;
                $_SESSION['sturecmssurname'] = $surname;
                $_SESSION['login'] = $username;

                header('Location: ../index.php');
                exit();
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/login.css">
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
                    <span>JF DENTAL CARE</span>
                </a>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="color: #999; font-size: 14px;">Already have an account?</span>
                    <a href="login.php" class="create-account-link" style="background: #3498db; color: white;">SIGN IN</a>
                </div>
            </div>

            <div class="form-container">
                <h1>Create an Account</h1>
                <p class="subtitle">Join us and manage your dental health!</p>

                <form class="pt-3" method="post" name="register">
                    <div style="display: flex; gap: 20px;">
                        <div class="form-group" style="flex: 1;">
                            <label for="firstname">Firstname</label>
                            <input type="text" id="firstname" class="form-control" placeholder="Firstname" required name="firstname">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="surname">Surname</label>
                            <input type="text" id="surname" class="form-control" placeholder="Surname" required name="surname">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" class="form-control" placeholder="your.email@example.com" required name="email">
                    </div>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" class="form-control" placeholder="Choose a username" required name="username">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" class="form-control" placeholder="Create a password" required name="password">
                            <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="confirm_password" class="form-control" placeholder="Confirm your password" required name="confirm_password">
                            <i class="fas fa-eye toggle-password" id="toggleConfirmPassword"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button class="login-btn" name="register" type="submit">Create Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function setupPasswordToggle(toggleId, inputId) {
            const toggle = document.getElementById(toggleId);
            const input = document.getElementById(inputId);

            if (toggle && input) {
                toggle.addEventListener('click', function() {
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    this.classList.toggle('fa-eye');
                    this.classList.toggle('fa-eye-slash');
                });
            }
        }

        setupPasswordToggle('togglePassword', 'password');
        setupPasswordToggle('toggleConfirmPassword', 'confirm_password');
    </script>
</body>
</html>