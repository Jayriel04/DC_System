<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
?>
<!doctype html>
<html>

<head>
  <title>JF Dental Care</title>
  <script
    type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); function hideURLbar(){ window.scrollTo(0,1); } </script>
  <!--bootstrap-->
  <link href="css/bootstrap.css" rel="stylesheet" type="text/css" media="all">
  <!--coustom css-->
  <link href="css/style.css" rel="stylesheet" type="text/css" />
  <!--script-->
  <script src="js/jquery-1.11.0.min.js"></script>
  <!-- js -->
  <script src="js/bootstrap.js"></script>
  <!-- /js -->
  <!--fonts-->
  <link
    href='//fonts.googleapis.com/css?family=Open+Sans:300,300italic,400italic,400,600,600italic,700,700italic,800,800italic'
    rel='stylesheet' type='text/css'>
  <!--/fonts-->
  <!--hover-girds-->
  <link rel="stylesheet" type="text/css" href="css/default.css" />
  <link rel="stylesheet" type="text/css" href="css/component.css" />
  <script src="js/modernizr.custom.js"></script>
  <!--/hover-grids-->
  <script type="text/javascript" src="js/move-top.js"></script>
  <script type="text/javascript" src="js/easing.js"></script>
  <!--script-->
  <script type="text/javascript">
    jQuery(document).ready(function ($) {
      $(".scroll").click(function (event) {
        event.preventDefault();
        $('html,body').animate({ scrollTop: $(this.hash).offset().top }, 900);
      });
    });
  </script>
  <!--/script-->
  <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>

<body>
  <?php include_once('includes/header.php'); ?>
  <div class="banner">
    <div class="container">
      <script src="js/responsiveslides.min.js"></script>
      <script>
        $(function () {
          $("#slider").responsiveSlides({
            auto: true,
            nav: true,
            speed: 500,
            namespace: "callbacks",
            pager: true,
          });
        });
      </script>
      <div class="slider">
        <div class="callbacks_container">
          <ul class="rslides" id="slider">
            <li>
              <h2>Where beautiful</h2>
              <h2> smiles come to life</h2>
              <p class="hero-description">
                Experience comprehensive dental care with our advanced patient management system.
                We provide personalized treatment plans and state-of-the-art dental services
                for your optimal oral health.
              </p>
              <div class="readmore">
                <a href="user/create_account.php">
                  Book appointment <i class="ri-arrow-right-line"></i>
                </a>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
  
  <!--About -->

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
<!--sevices-->

<section class="services-section">
  <h1>Our Services</h1>
  <div class="subtitle">
    We offer comprehensive dental services using the latest technology and techniques to<br>
    ensure your optimal oral health and beautiful smile.
  </div>
  <div id="services-carousel" class="services-cards-row">
    <?php
    // Fetch services from the database (show all so carousel can slide through them)
    try {
        $sql = "SELECT number, name, description, image FROM tblservice ORDER BY number ASC";
        $query = $dbh->prepare($sql);
        $query->execute();
        $services = $query->fetchAll(PDO::FETCH_OBJ);
    } catch (Exception $e) {
        $services = [];
    }

    if (!empty($services)) {
        // Start carousel wrapper (Bootstrap carousel - v3 style 'item')
        $count = count($services);
        ?>

        <div id="servicesCarousel" class="carousel slide services-carousel" data-ride="carousel" data-interval="5000">
          <ol class="carousel-indicators">
            <?php
            // Number of slides when showing 3 cards per slide
            $perSlide = 3;
            $slideCount = (int) ceil($count / $perSlide);
            for ($si = 0; $si < $slideCount; $si++) : ?>
              <li data-target="#servicesCarousel" data-slide-to="<?php echo $si;?>"<?php echo $si === 0 ? ' class="active"' : ''; ?>></li>
            <?php endfor; ?>
          </ol>

          <div class="carousel-inner" role="listbox">
            <?php
      // Render slides with up to $perSlide cards each
      $index = 0;
      for ($slide = 0; $slide < $slideCount; $slide++) {
        echo '<div class="item' . ($slide === 0 ? ' active' : '') . '">';
        echo '<div class="service-slide">';

        // Count how many cards will be in this slide
        $cardsInThisSlide = min($perSlide, $count - $index);
        $emptyCards = $perSlide - $cardsInThisSlide;
        $leftPad = (int)floor($emptyCards / 2);
        $rightPad = $emptyCards - $leftPad;

        // Pad left if needed (to center single or double card)
        for ($p = 0; $p < $leftPad; $p++) {
          echo '<div class="service-card" style="visibility:hidden;"></div>';
        }

        for ($j = 0; $j < $cardsInThisSlide; $j++) {
          $svc = $services[$index];
          $svcName = htmlspecialchars($svc->name ?? 'Service', ENT_QUOTES, 'UTF-8');
          $svcDesc = htmlspecialchars($svc->description ?? '', ENT_QUOTES, 'UTF-8');
          $svcImageRaw = $svc->image ?? '';
          $basename = trim(basename($svcImageRaw));
          $svcImage = '';
          if ($basename !== '') {
            $svcImage = 'admin/images/services/' . rawurlencode($basename);
          }

          echo '<div class="service-card">';
          if ($svcImage !== '') {
            echo '<img src="' . $svcImage . '" alt="' . $svcName . '" class="service-card-img">';
          }
          echo '<div class="service-card-body">';
          echo '<span class="service-card-icon"><i class="ri-file-list-3-line"></i></span>';
          echo '<div class="service-card-title">' . $svcName . '</div>';
          echo '<div class="service-card-desc">' . $svcDesc . '</div>';
          echo '</div>';
          echo '</div>';

          $index++;
        }

        // Pad right if needed
        for ($p = 0; $p < $rightPad; $p++) {
          echo '<div class="service-card" style="visibility:hidden;"></div>';
        }

        echo '</div>'; // .service-slide
        echo '</div>'; // .item
      }
            ?>
          </div>

          <?php if ($count > 1) : ?>
            <a class="left carousel-control" href="#servicesCarousel" role="button" data-slide="prev">
              <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
              <span class="sr-only">Previous</span>
            </a>
            <a class="right carousel-control" href="#servicesCarousel" role="button" data-slide="next">
              <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
              <span class="sr-only">Next</span>
            </a>
          <?php endif; ?>

        </div>

    <?php
    } else {
        echo '<div class="no-services">No services available at the moment. Please check back later.</div>';
    }
    ?>
  </div>
</section>

  


<!--Contact-->

     <div class="contact-title">Contact Us</div>
  <div class="contact-desc">
     Get in touch with us today and take the first step towards better oral health.
  </div>
  <div class="contact-main">
    <!-- Left: Contact Info -->
    <div class="contact-info">
      <h3>Get in Touch</h3>
      <ul class="contact-list">  
        <li>
          <i class="ri-map-pin-line"></i>
          <div>
            <span class="label">Address</span><br>
            383 L.Jayme St., Bakilid 2, Hi-way Mandaue City<br>
            6014 Cebu, Philippines
          </div>
        </li>
        <li>
          <i class="ri-phone-line"></i>
          <div>
            <span class="label">Phone</span><br>
            0925-519-1328
          </div>
        </li>
        <li>
          <i class="ri-mail-line"></i>
          <div>
            <span class="label">Email</span><br>
            <a href="mailto:info@jfdentalcare.com">info@jfdentalcare.com</a>
          </div>
        </li>
        <li>
          <i class="ri-time-line"></i>
          <div>
            <span class="label">Office Hours</span><br>
            Monday & Thursday : 2:00 PM - 7:00 PM<br>
            Tuesday & Saturday : 9:00 AM - 6:00 PM<br>
            Wednesday & Friday : 4:30 PM - 7:00 PM<br>
			Sunday: Closed
          </div>
        </li>
        <li>
          <i class="ri-facebook-circle-fill"></i>
          <div>
            <span class="label">Facebook</span><br>
            <a href="https://facebook.com/jfdentalcare" target="_blank">facebook.com/jfdentalcare</a>
          </div>
        </li>
      </ul>
    </div>
    <!-- Right: Feedback Star Rating -->
    <div class="contact-form-section">
      <h3>Feedback</h3>
      <form class="contact-form" method="post" action="#">
        <label for="star-rating">Your Rating</label>
        <div class="star-rating">
          <input type="radio" id="star5" name="rating" value="5"><label for="star5" title="5 stars">&#9733;</label>
          <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="4 stars">&#9733;</label>
          <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="3 stars">&#9733;</label>
          <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="2 stars">&#9733;</label>
          <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="1 star">&#9733;</label>
        </div>
        <button type="submit">Submit Feedback</button>
      </form>
    </div>
  </div>
  <!-- Google Map -->
  <div class="map-container" style="max-width: 1200px; margin: 40px auto;">
    <iframe
      src="https://www.google.com/maps?q=383+L.Jayme+St.,+Bakilid+2,+Hi-way+Mandaue+City,+6014+Cebu,+Philippines&output=embed"
      width="100%" height="320" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
  </div>

  <?php include_once('includes/footer.php'); ?>
</body> 

</html>