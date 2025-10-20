<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
?>
<!doctype html>
<html>
<head>
<title>Service</title>
<link href="css/bootstrap.css" rel="stylesheet" type="text/css" media="all">
<link rel="stylesheet" href="css/style.css">
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
<style>
body {
  background: #ffffffff;
}
.service-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  justify-content: center;
}
.service-card {
  flex: 1 1 250px; /* Flex properties for responsiveness */
  max-width: 280px;
  transition: transform 0.3s ease, box-shadow 0.3s ease; /* Add transition for smooth effect */
}
.service-card:hover {
  transform: scale(1.05); /* Make it slightly larger on hover */
  box-shadow: 0 8px 25px rgba(0,0,0,0.15); /* Add a subtle shadow for depth */
}

</style>
</head>
<body>
<?php include_once('includes/header.php');?>

<section class="services-section">
  <h1>Our Services</h1>
  <div class="subtitle">
    We offer comprehensive dental services using the latest technology and techniques to<br>
    ensure your optimal oral health and beautiful smile.
  </div>
  <div id="services-carousel" class="services-cards-row">
    <?php
    // Fetch all services from the database
    try {
        $sql = "SELECT number, name, description, image FROM tblservice ORDER BY number ASC";
        $query = $dbh->prepare($sql);
        $query->execute();
        $services = $query->fetchAll(PDO::FETCH_OBJ);
    } catch (Exception $e) {
        $services = [];
    }
    
    if (!empty($services)) {
    ?>
        <div class="service-grid">
            <?php foreach ($services as $svc) :
                $svcName = htmlspecialchars($svc->name ?? 'Service', ENT_QUOTES, 'UTF-8');
                $svcDesc = htmlspecialchars($svc->description ?? '', ENT_QUOTES, 'UTF-8');
                $svcImageRaw = $svc->image ?? '';
                $basename = trim(basename($svcImageRaw));
                $svcImage = '';
                if ($basename !== '') {
                    $svcImage = 'admin/images/services/' . rawurlencode($basename);
                }
            ?>
                <div class="service-card">
                    <?php if ($svcImage !== '') : ?>
                        <img src="<?php echo $svcImage; ?>" alt="<?php echo $svcName; ?>" class="service-card-img">
                    <?php endif; ?>
                    <div class="service-card-body">
                        <div class="service-card-title"><?php echo $svcName; ?></div>
                        <div class="service-card-desc"><?php echo $svcDesc; ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php
    } else {
        echo '<div class="no-services">No services available at the moment. Please check back later.</div>';
    }
    ?>
  </div>
</section>

  <!-- Ensure jQuery and Bootstrap JS are available for the carousel (matches other pages) -->
  <script src="js/jquery-1.11.0.min.js"></script>
  <script src="js/bootstrap.js"></script>

<?php include_once('includes/footer.php');?>
</body>
</html>