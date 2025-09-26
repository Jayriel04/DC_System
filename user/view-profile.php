<?php
session_start();
// error_reporting(0);
include('includes/dbconnection.php');

// Updated session check for tblpatient
if (strlen($_SESSION['sturecmsnumber']) == 0) {
    header('location:logout.php');
} else {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Management System || View Profile</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/profile.css" />
    <style>
        .profile-container {
            max-width: 800px;
            margin: auto;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .profile-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .profile-header img {
            border-radius: 50%;
            width: 150px;
            height: 150px;
            object-fit: cover;
        }
        .btn-update {
            display: block;
            width: 100%;
            margin: 20px 0;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            text-align: center;
            font-size: 16px;
            text-decoration: none;
        }
        .btn-update:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container-scroller">
        <!-- Navbar -->
        <?php include_once('includes/header.php'); ?>
        <!-- Sidebar -->
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php'); ?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="profile-container">
                        <div class="profile-header">
                            <h3>Patient Profile</h3>
                            <?php
                            $number = $_SESSION['sturecmsnumber'];
                            $sql = "SELECT firstname, surname, date_of_birth, age, contact_number, address, username, image FROM tblpatient WHERE number = :number";
                            $query = $dbh->prepare($sql);
                            $query->bindParam(':number', $number, PDO::PARAM_STR);
                            $query->execute();
                            $results = $query->fetchAll(PDO::FETCH_OBJ);

                            if ($query->rowCount() > 0) {
                                foreach ($results as $row) { ?>
                                    <img src="../admin/images/<?php echo htmlentities($row->image); ?>" alt="Profile Picture" class="img-fluid">
                                    <h4><?php echo htmlentities($row->firstname . ' ' . $row->surname); ?></h4>
                                <?php }
                            } ?>
                        </div>
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th>Date of Birth</th>
                                    <td><?php echo htmlentities($row->date_of_birth); ?></td>
                                </tr>
                                <tr>
                                    <th>Age</th>
                                    <td><?php echo htmlentities($row->age); ?></td>
                                </tr>
                                <tr>
                                    <th>Contact Number</th>
                                    <td><?php echo htmlentities($row->contact_number); ?></td>
                                </tr>
                                <tr>
                                    <th>Address</th>
                                    <td><?php echo htmlentities($row->address); ?></td>
                                </tr>
                                <tr>
                                    <th>Username</th>
                                    <td><?php echo htmlentities($row->username); ?></td>
                                </tr>
                            </tbody>
                        </table>
                        <a href="update_profile.php" class="btn-update">Update Profile</a>
                    </div>
                </div>
                <!-- Footer -->
                <?php include_once('includes/footer.php'); ?>
            </div>
        </div>
    </div>
    <script src="vendors/js/vendor.bundle.base.js"></script>
</body>
</html>
<?php } ?>