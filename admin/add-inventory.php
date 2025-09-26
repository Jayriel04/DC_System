<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
include('includes/dbconnection.php');
if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
    exit();
} else {
    if (isset($_POST['submit'])) {
        // Get form data
        $name = trim($_POST['name']);
        $brand = trim($_POST['brand']);
        $expiration_date = $_POST['expiration_date'];
        $quantity = intval($_POST['quantity']);
        $category = trim($_POST['category']);
        $status = trim($_POST['status']);

        // Insert into database
        $sql = "INSERT INTO tblinventory (name, brand, expiration_date, quantity, category, status) VALUES (:name, :brand, :expiration_date, :quantity, :category, :status)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':name', $name, PDO::PARAM_STR);
        $query->bindParam(':brand', $brand, PDO::PARAM_STR);
        $query->bindParam(':expiration_date', $expiration_date, PDO::PARAM_STR);
        $query->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $query->bindParam(':category', $category, PDO::PARAM_STR);
        $query->bindParam(':status', $status, PDO::PARAM_STR);

        if ($query->execute()) {
            echo "<script>
                alert('Inventory item added successfully');
                window.location.href='manage-inventory.php';
            </script>";
            exit();
        } else {
            print_r($query->errorInfo());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Inventory Item</title>
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container-scroller">
        <?php include_once('includes/header.php'); ?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php'); ?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="page-header">
                        <h3 class="page-title">Add Inventory Item</h3>
                    </div>
                    <div class="row">
                        <div class="col-md-8 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="form-group">
                                            <label for="name">Name</label>
                                            <input type="text" class="form-control" id="name" name="name" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="brand">Brand</label>
                                            <input type="text" class="form-control" id="brand" name="brand" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="expiration_date">Expiration Date</label>
                                            <input type="date" class="form-control" id="expiration_date" name="expiration_date" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="quantity">Quantity</label>
                                            <input type="number" class="form-control" id="quantity" name="quantity" min="0" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="category">Category</label>
                                            <select class="form-control" id="category" name="category" required>
                                                <option value="">Select Category</option>
                                                <option value="Supply">Supply</option>
                                                <option value="Medicine">Medicine</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="status">Status</label>
                                            <select class="form-control" id="status" name="status" required>
                                                <option value="">Select Status</option>
                                                <option value="Available">Available</option>
                                                <option value="Unavailable">Unavailable</option>
                                            </select>
                                        </div>
                                        <button type="submit" name="submit" class="btn btn-primary">Add Inventory Item</button>
                                        <a href="manage-inventory.php" class="btn btn-secondary">Cancel</a>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php include_once('includes/footer.php'); ?>
            </div>
        </div>
    </div>
    <script src="vendors/js/vendor.bundle.base.js"></script>
</body>
</html>