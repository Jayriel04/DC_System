<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
?>
<!doctype html>
<html>
<head>
<title>About Us </title>
<script type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); function hideURLbar(){ window.scrollTo(0,1); } </script>
<link href="css/bootstrap.css" rel="stylesheet" type="text/css" media="all">
<link href="css/style.css" rel="stylesheet" type="text/css"/>
<script src="js/jquery-1.11.0.min.js"></script>
<script src="js/bootstrap.js"></script>
<!-- /js -->
<!--fonts-->
<link href='//fonts.googleapis.com/css?family=Open+Sans:300,300italic,400italic,400,600,600italic,700,700italic,800,800italic' rel='stylesheet' type='text/css'>
<!--/fonts-->
<script type="text/javascript" src="js/move-top.js"></script>
<script type="text/javascript" src="js/easing.js"></script>
<!--script-->
<script type="text/javascript">
			jQuery(document).ready(function($) {
				$(".scroll").click(function(event){		
					event.preventDefault();
					$('html,body').animate({scrollTop:$(this.hash).offset().top},900);
				});
			});
</script>
<!--/script-->
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
	<body>
<!--header-->
<?php include_once('includes/header.php');?>
<!-- Top Navigation -->
	
	</div>
</div>
<!--header-->
<!-- About -->
<section id="about" class="about-section">
  <div class="about-container">
    <div class="about-grid">
      <div class="about-content">
        <h2 class="about-title">About JF Dental Care</h2>
        <p class="about-paragraph">
          At JF Dental Care, we are committed to providing exceptional dental services
          in a comfortable and modern environment. Our experienced team uses the latest
          technology and techniques to ensure you receive the best possible care for
          your oral health needs.
        </p>
        <p class="about-paragraph">
          We believe in building long-term relationships with our patients through
          personalized care, education, and a gentle approach to dentistry. Your
          comfort and satisfaction are our top priorities.
        </p>
        <div class="features-grid">
  <div class="feature-item">
    <div class="feature-icon-container">
      <i class="ri-award-line feature-icon"></i>
    </div>
    <div class="feature-content">
      <h3 class="feature-title">Expert Team</h3>
      <p class="feature-description">Experienced dental professionals with advanced training and certifications.</p>
    </div>
  </div>
  <div class="feature-item">
    <div class="feature-icon-container">
      <i class="ri-user-heart-line feature-icon"></i>
    </div>
    <div class="feature-content">
      <h3 class="feature-title">Patient Centered</h3>
      <p class="feature-description">We focus on your comfort and satisfaction at every visit.</p>
    </div>
  </div>
  <div class="feature-item">
    <div class="feature-icon-container">
      <i class="ri-macbook-line feature-icon"></i>
    </div>
    <div class="feature-content">
      <h3 class="feature-title">Modern Technology</h3>
      <p class="feature-description">State-of-the-art equipment for precise and gentle care.</p>
    </div>
  </div>
  <div class="feature-item">
    <div class="feature-icon-container">
      <i class="ri-calendar-check-line feature-icon"></i>
    </div>
    <div class="feature-content">
      <h3 class="feature-title">Flexible Scheduling</h3>
      <p class="feature-description">Convenient appointment times to fit your busy life.</p>
    </div>
  </div>
</div>
      </div>
          <div class="about-image-container">
  <img
    src="images/doctor.png"
    alt="JF Dental Care Team"
    class="about-image"
  />
  <div class="experience-badge">
    <div class="experience-number">5+</div>
    <div class="experience-text">Years of Experience</div>
  </div>
</div>
    </div>
  </div>
</section>
			 	
<!-- /About -->
<?php include_once('includes/footer.php');?>
<!--/copy-rights-->
	</body>
</html>
