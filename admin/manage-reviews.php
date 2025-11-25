<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
} else {
    // Fetch all reviews from tblpatient
    $sql_reviews = "SELECT firstname, surname, Image, sex, rating, feedback, created_at FROM tblpatient WHERE feedback IS NOT NULL AND feedback != '' ORDER BY created_at DESC";
    $query_reviews = $dbh->prepare($sql_reviews);
    $query_reviews->execute();
    $all_reviews = $query_reviews->fetchAll(PDO::FETCH_OBJ);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Manage Reviews</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="./css/dashboard.css">
    <link rel="stylesheet" href="./css/sidebar.css">
</head>

<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php'); ?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php'); ?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="header">
                        <div class="header-text">
                            <h2>Patients Reviews</h2>
                            <p>Review patient feedback and ratings</p>
                        </div>
                        <br>
                        <?php if (!empty($all_reviews)): ?>
                            <?php foreach ($all_reviews as $review): ?>
                                <?php
                                $review_avatar = 'avatar.png'; // Default fallback
                                if (!empty($review->Image)) {
                                    $review_avatar = $review->Image;
                                } elseif ($review->sex === 'Male') {
                                    $review_avatar = 'man-icon.png';
                                } elseif ($review->sex === 'Female') {
                                    $review_avatar = 'woman-icon.jpg';
                                }
                                ?>
                                <div class="review-card">
                                    <div class="review-header">
                                        <div style="display: flex; gap: 16px; align-items: center;">
                                            <img class="avatar" src="../admin/images/<?php echo htmlentities($review_avatar); ?>" alt="Avatar">
                                            <div class="review-info">
                                                <div class="review-name"><?php echo htmlentities($review->firstname . ' ' . $review->surname); ?></div>
                                                <div class="review-service">Patient Review</div>
                                            </div>
                                        </div>
                                        <div class="review-meta">
                                            <div class="review-stars"><?php echo str_repeat('★', (int) $review->rating) . str_repeat('☆', 5 - (int) $review->rating); ?></div>
                                            <div class="review-date"><?php echo date("F j, Y", strtotime($review->created_at)); ?></div>
                                        </div>
                                    </div>
                                    <p class="review-text">"<?php echo htmlentities($review->feedback); ?>"</p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No reviews found.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php include_once('includes/footer.php'); ?>
            </div>
        </div>
    </div>
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
</body>
</html>
<?php } ?>