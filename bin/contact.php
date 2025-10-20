<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback'])) {
  // You can process/store feedback here if needed
  header('Location: index.php');
  exit();
}
?>
<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
?>
<!doctype html>
<html>
<head>
  <title>Contact Us</title>
  <link href="css/bootstrap.css" rel="stylesheet" type="text/css" media="all">
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
    <div style="display: flex; justify-content: center; align-items: center; min-height: 350px;">
  <form method="post" action="" class="feedback-form" style="max-width: 500px; width: 100%; margin: auto;">
        <div style="margin-top: 20px; background: #f5f8ff; border-radius: 16px; box-shadow: 0 2px 8px #e0e7ff; padding: 24px; text-align: center;">
          <div style="font-size: 22px; font-weight: 600; color: #222; margin-bottom: 8px;">Rate your experience</div>
          <div style="color: #6c7a89; font-size: 15px; margin-bottom: 18px;">We highly value your feedback! Kindly take a moment to rate your experience and provide us with your valuable feedback.</div>
          <div class="star-rating" style="margin-bottom: 18px;">
            <!-- ...existing star rating code... -->
            <input type="radio" id="star5" name="rating" value="5"><label for="star5" title="5 stars">&#9733;</label>
            <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="4 stars">&#9733;</label>
            <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="3 stars">&#9733;</label>
            <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="2 stars">&#9733;</label>
            <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="1 star">&#9733;</label>
          </div>
          <textarea name="feedback" rows="3" style="width: 90%; max-width: 400px; border-radius: 8px; border: 1px solid #dbeafe; padding: 12px; font-size: 15px; margin-bottom: 18px; resize: none;" placeholder="Tell us about your experience!"></textarea>
          <br>
          <button type="submit" style="background: #ffd966; color: #222; border: none; border-radius: 24px; padding: 10px 32px; font-size: 16px; font-weight: 500; box-shadow: 0 2px 8px #e0e7ff; cursor: pointer;">Send</button>
        </div>
      </form>
    </div>
  </div>
  <!-- Google Map -->
  <div class="map-container" style="max-width: 1200px; margin: 40px auto;">
    <iframe
      src="https://www.google.com/maps?q=383+L.Jayme+St.,+Bakilid+2,+Hi-way+Mandaue+City,+6014+Cebu,+Philippines&output=embed"
      width="100%" height="320" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
  </div>
  <?php include_once('includes/footer.php');?>
</body>
</html>