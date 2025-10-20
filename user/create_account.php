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

                header('Location: dashboard.php');
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
    <title>Create Account</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css">
    
</head>
<body>
    <style>
        
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
        right: 10px; 
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        background: transparent; 
        border: none; 
    }
    .form-control {
        padding-right: 40px; 
    }
    </style>
       <div class="container-scroller">
        <div class="container-fluid page-body-wrapper full-page-wrapper">
            <div class="content-wrapper d-flex align-items-center auth">
                <div class="row flex-grow">
                    <div class="col-lg-4 mx-auto">
                         
                        <div class="auth-form-light text-left p-5">
                            <div class="brand-logo" align="center" style="font-weight:bold">
    <img src="../images/Jf logo.png" alt="JF Dental Care Logo" class="logo-img" style="width:35px; margin-right:10px;">
    <h2 style="margin-top: 10px; margin-bottom: 5px;">Welcome to <br> JF Dental Care</h2>
</div>           
       <form class="pt-3" method="post" name="register">
                                <div class="form-group">
                                    <label for="firstname">Firstname</label>
                                    <input type="text" id="firstname" class="form-control form-control-lg" placeholder="Firstname" required name="firstname" style="border-radius: 10px;">
                                </div>
                                <div class="form-group">
                                    <label for="surname">Surname</label>
                                    <input type="text" id="surname" class="form-control form-control-lg" placeholder="Surname" required name="surname" style="border-radius: 10px;">
                                </div>
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" class="form-control form-control-lg" placeholder="Email" required name="email" style="border-radius: 10px;">
                                </div>
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <input type="text" id="username" class="form-control form-control-lg" placeholder="Username" required name="username" style="border-radius: 10px;">
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
                                <div class="form-group">
                                    <label for="confirm_password">Confirm Password</label>
                                    <div class="input-group">
                                        <input type="password" id="confirm_password" class="form-control form-control-lg" placeholder="Confirm Password" required name="confirm_password" style="border-radius: 10px;">
                                        <span class="input-group-text" onclick="togglePassword('confirm_password')">
                                            <i id="eye-confirm-password" class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                <div class="mt-3">
                                    <button class="btn btn-primary btn-block" name="register" type="submit" style="border-radius: 10px;">Create Account</button>
                                </div>
                                            <div class="text-center mt-4 font-weight-light">
              Already a member? <a href="login.php" class="text-black" style="font-weight:bold">Login</a>
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