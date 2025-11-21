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
    <style>
        .review-card {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            padding: 24px;
            margin-bottom: 20px;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 16px;
        }
        .review-info {
            line-height: 1.3;
        }
        .review-name {
            font-weight: 600;
            color: #333;
            font-size: 1rem;
        }
        .review-service {
            font-size: 0.8rem;
            color: #888;
        }
        .review-meta {
            text-align: right;
        }
        .review-stars {
            color: #f39c12;
            font-size: 1rem;
        }
        .review-date {
            font-size: 0.75rem;
            color: #999;
            margin-top: 4px;
        }
        .review-text {
            color: #555;
            font-style: italic;
            line-height: 1.6;
        }
    </style>
</head>

<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php'); ?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php'); ?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="container">
                        <div class="page-header">
                            <h3 class="page-title">Patient Reviews</h3>
                        </div>

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