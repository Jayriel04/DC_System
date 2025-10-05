<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
?>
<!doctype html>
<html>
<head>
  <title>Contact Us</title>
  <link rel="stylesheet" href="css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>
  <?php include_once('includes/header.php'); ?>
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