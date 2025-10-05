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
  background: #fafbfc;
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
  <div class="services-cards-row">
    <!-- General Dentistry -->
    <div class="service-card">
      <img src="images/service-general.jpg" alt="General Dentistry" class="service-card-img">
      <div class="service-card-body">
        <span class="service-card-icon"><i class="ri-tooth-line"></i></span>
        <div class="service-card-title">General Dentistry</div>
        <div class="service-card-desc">
          Comprehensive oral health care including cleanings, fillings, and preventive treatments.
        </div>
      </div>
    </div>
    <!-- Cosmetic Dentistry -->
    <div class="service-card">
      <img src="images/service-cosmetic.jpg" alt="Cosmetic Dentistry" class="service-card-img">
      <div class="service-card-body">
        <span class="service-card-icon"><i class="ri-star-line"></i></span>
        <div class="service-card-title">Cosmetic Dentistry</div>
        <div class="service-card-desc">
          Enhance your smile with teeth whitening, veneers, and aesthetic dental procedures.
        </div>
      </div>
    </div>
    <!-- Restorative Care -->
    <div class="service-card">
      <img src="images/service-restorative.jpg" alt="Restorative Care" class="service-card-img">
      <div class="service-card-body">
        <span class="service-card-icon"><i class="ri-shield-check-line"></i></span>
        <div class="service-card-title">Restorative Care</div>
        <div class="service-card-desc">
          Restore damaged teeth with crowns, bridges, implants, and advanced restorative solutions.
        </div>
      </div>
    </div>
  </div>
</section>

<?php include_once('includes/footer.php');?>
</body>
</html>