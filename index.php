<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

// Check if a logged-in user already has health conditions on file
$user_has_health_conditions = false;
if (isset($_SESSION['sturecmsnumber'])) {
    $patient_number_check = $_SESSION['sturecmsnumber'];
    $sql_health_check = "SELECT health_conditions FROM tblpatient WHERE number = :patient_number";
    $query_health_check = $dbh->prepare($sql_health_check);
    $query_health_check->bindParam(':patient_number', $patient_number_check, PDO::PARAM_INT);
    $query_health_check->execute();
    $health_data = $query_health_check->fetch(PDO::FETCH_OBJ);

    if ($health_data && !empty($health_data->health_conditions) && $health_data->health_conditions !== 'null' && $health_data->health_conditions !== '[]') {
        $user_has_health_conditions = true;
    }
}

// AJAX endpoint: return calendar times for a given date
if (isset($_GET['get_calendar_times']) && !empty($_GET['date'])) {
    $reqDate = $_GET['date'];
    try {
        // Only return calendar slots for the date that are not already booked
        $stmt = $dbh->prepare("SELECT c.id, c.start_time, c.end_time FROM tblcalendar c LEFT JOIN tblappointment a ON a.date = c.date AND a.start_time = c.start_time WHERE c.date = :date AND a.id IS NULL ORDER BY c.start_time");
        $stmt->bindParam(':date', $reqDate, PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $start = $r['start_time'];
            $end = $r['end_time'];
            $label = date('g:i A', strtotime($start));
            if (!empty($end)) {
                $label .= ' - ' . date('g:i A', strtotime($end));
            }
            $out[] = ['id' => $r['id'], 'start' => $start, 'end' => $end, 'label' => $label];
        }
        header('Content-Type: application/json');
        echo json_encode($out);
    } catch (Exception $e) {
        header('Content-Type: application/json', true, 500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

if(isset($_POST['book_appointment'])) {
    // Use 'sturecmsnumber' as it seems to be the patient ID from your DB structure
    $patient_number = $_SESSION['sturecmsnumber'] ?? null;
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    
    if ($patient_number && !empty($appointment_date) && !empty($appointment_time)) {
        // Collect all health conditions into a structured array
        $health_conditions_structured = [];
        if (!empty($_POST['health_conditions']) && is_array($_POST['health_conditions'])) {
            foreach($_POST['health_conditions'] as $category => $conditions) {
                if (!empty($conditions)) {
                    $health_conditions_structured[$category] = is_array($conditions) ? $conditions : trim($conditions);
                }
            }
        }
        
        // Convert array to JSON for storage
        $conditions_json = json_encode($health_conditions_structured);
        
        try {
            $dbh->beginTransaction();

            // 1. Update the health conditions for the patient in tblpatient
            $sql_update_patient = "UPDATE tblpatient SET health_conditions = :conditions WHERE number = :patient_number";
            $query_update = $dbh->prepare($sql_update_patient);
            $query_update->bindParam(':conditions', $conditions_json, PDO::PARAM_STR);
            $query_update->bindParam(':patient_number', $patient_number, PDO::PARAM_INT);
            $query_update->execute();

            // 2. Get patient's name for the appointment record
            $sql_get_name = "SELECT firstname, surname FROM tblpatient WHERE number = :patient_number";
            $query_get_name = $dbh->prepare($sql_get_name);
            $query_get_name->bindParam(':patient_number', $patient_number, PDO::PARAM_INT);
            $query_get_name->execute();
            $patient_details = $query_get_name->fetch(PDO::FETCH_ASSOC);

            // 3. Create the appointment in tblappointment
            $sql_insert_appt = "INSERT INTO tblappointment (firstname, surname, date, start_time, patient_number, status) 
                                VALUES (:firstname, :surname, :appdate, :apptime, :patient_number, 'Pending')";
            $query_insert_appt = $dbh->prepare($sql_insert_appt);
            $query_insert_appt->bindParam(':firstname', $patient_details['firstname'], PDO::PARAM_STR);
            $query_insert_appt->bindParam(':surname', $patient_details['surname'], PDO::PARAM_STR);
            $query_insert_appt->bindParam(':appdate', $appointment_date, PDO::PARAM_STR);
            $query_insert_appt->bindParam(':apptime', $appointment_time, PDO::PARAM_STR);
            $query_insert_appt->bindParam(':patient_number', $patient_number, PDO::PARAM_INT);
            $query_insert_appt->execute();
            
            $dbh->commit();
            echo "<script>alert('Appointment booked successfully!'); window.location.href=window.location.href;</script>";
        } catch(PDOException $e) {
            $dbh->rollBack();
            // For debugging: error_log("Booking failed: " . $e->getMessage());
            echo "<script>alert('Something went wrong. Please try again.');</script>";
        }
    } else {
        echo "<script>alert('Please log in and fill out all required fields to book an appointment.');</script>";
    }
}

if (isset($_POST['submit_feedback'])) {
    $patient_number = $_SESSION['sturecmsnumber'] ?? null;
    $rating = $_POST['rating'] ?? null;
    $feedback = $_POST['feedback'] ?? null;

    if ($patient_number && $rating) {
        try {
            $sql = "UPDATE tblpatient SET rating = :rating, feedback = :feedback WHERE number = :patient_number";
            $query = $dbh->prepare($sql);
            $query->bindParam(':rating', $rating, PDO::PARAM_INT);
            $query->bindParam(':feedback', $feedback, PDO::PARAM_STR);
            $query->bindParam(':patient_number', $patient_number, PDO::PARAM_INT);
            $query->execute();
            echo "<script>alert('Thank you for your feedback!'); window.location.href=window.location.href;</script>";
        } catch (PDOException $e) {
            echo "<script>alert('Something went wrong. Please try again.');</script>";
        }
    }
}
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
  <link href="./css/style.css" rel="stylesheet" type="text/css" />
  <link href="./css/header.css" rel="stylesheet" type="text/css" />
  <link href="./css/footer.css" rel="stylesheet" type="text/css" />
  <link href="css/health-modal.css" rel="stylesheet" type="text/css" />

  <!--script-->
  <style>
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
  <div id="home"></div>
  <?php include_once('includes/header.php'); ?>
  <div class="banner" >
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
              <h2> Smiles come to life</h2>
              <p class="hero-description">
                Experience comprehensive dental care with our advanced patient management system.
                We provide personalized treatment plans and state of the art dental services
                for your optimal oral health. 
              </p>
              <div class="readmore">
                <?php 
                // Check for multiple possible session variables
                if(isset($_SESSION['sturecmsnumber'])) {
                    // Only show the button if the user does NOT have health conditions on file
                    if (!$user_has_health_conditions) { ?>
                        <a href="#" data-toggle="modal" data-target="#healthModal">
                            Book appointment <i class="ri-arrow-right-line"></i>
                        </a>
                <?php } } else { ?>
                  <a href="user/create_account.php">
                    Book appointment <i class="ri-arrow-right-line"></i>
                  </a>
                <?php } ?>
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

<section class="services-section" id="services">
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
        // Chunk services into groups of 4 for each row
        $service_chunks = array_chunk($services, 4);
        foreach ($service_chunks as $chunk) {
            echo '<div class="service-grid" style="margin-bottom: 20px;">'; // Add margin between rows
            foreach ($chunk as $svc) {
                $svcName = htmlspecialchars($svc->name ?? 'Service', ENT_QUOTES, 'UTF-8');
                $svcDesc = htmlspecialchars($svc->description ?? '', ENT_QUOTES, 'UTF-8');
                $svcImageRaw = $svc->image ?? '';
                $basename = trim(basename($svcImageRaw));
                $svcImage = !empty($basename) ? 'admin/images/services/' . rawurlencode($basename) : '';
                ?>
                <div class="service-card">
                    <?php if ($svcImage !== ''): ?><img src="<?php echo $svcImage; ?>" alt="<?php echo $svcName; ?>" class="service-card-img"><?php endif; ?>
                    <div class="service-card-body">
                        <div class="service-card-title"><?php echo $svcName; ?></div>
                        <div class="service-card-desc"><?php echo $svcDesc; ?></div>
                    </div>
                </div>
                <?php
            }
            echo '</div>'; // Close service-grid
        }
    } else {
        echo '<div class="no-services">No services available at the moment. Please check back later.</div>';
    }
    ?>
  </div>
</section>

  


<!--Contact-->

       <div class="contact-title" id="contact">Contact Us</div>
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
          <button type="submit" name="submit_feedback" style="background: #ffd966; color: #222; border: none; border-radius: 24px; padding: 10px 32px; font-size: 16px; font-weight: 500; box-shadow: 0 2px 8px #e0e7ff; cursor: pointer;">Send</button>
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

  <!-- Health Condition Modal -->
<div class="modal fade" id="healthModal" tabindex="-1" role="dialog" aria-labelledby="healthModalLabel" aria-hidden="true">
  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="healthModalLabel">Health Condition Form</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <!-- Appointment fields -->
          <div class="row mt-3">
            <div class="col-md-6">
              <div class="form-group">
                <label for="appointment_date">Preferred Appointment Date</label>
                <input type="date" class="form-control" name="appointment_date" id="appointment_date" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="appointment_time">Preferred Appointment Time</label>
                <select class="form-control" name="appointment_time" id="appointment_time">
                  <option value="">-- Select a time --</option>
                </select required>
              </div>
            </div>
          </div>

          <!-- Health Conditions -->
                        <p>Please check all conditions that apply to you.</p>
              <div class="row">
                <div class="col-md-6 section">
                  <h2 class="section-title">GENERAL</h2>
                  <div class="options">
                    <div class="option"><input type="checkbox" name="health_conditions[general][]" value="Marked weight change" id="hc_general_1"><label for="hc_general_1">Marked weight change</label></div>
                    <div class="option"><input type="checkbox" name="health_conditions[general][]" value="Increase frequency of urination" id="hc_general_2"><label for="hc_general_2">Increase frequency of urination</label></div>
                    <div class="option"><input type="checkbox" name="health_conditions[general][]" value="Burning sensation on urination" id="hc_general_3"><label for="hc_general_3">Burning sensation on urination</label></div>
                    <div class="option"><input type="checkbox" name="health_conditions[general][]" value="Loss of hearing, ringing of ears" id="hc_general_4"><label for="hc_general_4">Loss of hearing, ringing of ears</label></div>
                  </div>
                  
                  <h2 class="section-title mt-3">LIVER</h2>
                  <div class="options">
                    <div class="option"><input type="checkbox" name="health_conditions[liver][]" value="History of liver ailment" id="hc_liver_1"><label for="hc_liver_1">History of liver ailment</label></div>
                    <div class="option"><input type="checkbox" name="health_conditions[liver][]" value="Jaundice" id="hc_liver_2"><label for="hc_liver_2">Jaundice</label></div>
                  </div>
                  <div class="form-group mt-2"><label for="liver_specify">Specify:</label><input type="text" class="form-control" name="health_conditions[liver_specify]" id="liver_specify"></div>

                  <h2 class="section-title mt-3">DIABETES</h2>
                  <div class="options">
                    <div class="option"><input type="checkbox" name="health_conditions[diabetes][]" value="Delayed healing of wounds" id="hc_diab_1"><label for="hc_diab_1">Delayed healing of wounds</label></div>
                    <div class="option"><input type="checkbox" name="health_conditions[diabetes][]" value="Increase intake of food or water" id="hc_diab_2"><label for="hc_diab_2">Increase intake of food or water</label></div>
                    <div class="option"><input type="checkbox" name="health_conditions[diabetes][]" value="Family history of diabetes" id="hc_diab_3"><label for="hc_diab_3">Family history of diabetes</label></div>
                  </div>

                  <h2 class="section-title mt-3">THYROID</h2>
                  <div class="options">
                    <div class="option"><input type="checkbox" name="health_conditions[thyroid][]" value="Perspire easily" id="hc_thy_1"><label for="hc_thy_1">Perspire easily</label></div>
                    <div class="option"><input type="checkbox" name="health_conditions[thyroid][]" value="Apprehension" id="hc_thy_2"><label for="hc_thy_2">Apprehension</label></div>
                    <div class="option"><input type="checkbox" name="health_conditions[thyroid][]" value="Palpation/rapid heart beat" id="hc_thy_3"><label for="hc_thy_3">Palpation/rapid heart beat</label></div>
                    <div class="option"><input type="checkbox" name="health_conditions[thyroid][]" value="Goiter" id="hc_thy_4"><label for="hc_thy_4">Goiter</label></div>
                    <div class="option"><input type="checkbox" name="health_conditions[thyroid][]" value="Bulging of eyes" id="hc_thy_5"><label for="hc_thy_5">Bulging of eyes</label></div>
                  </div>
                </div>
                <div class="col-md-6 section">
                  <h2 class="section-title">URINARY</h2>
                  <div class="options">
                    <div class="option"><input type="checkbox" name="health_conditions[urinary][]" value="Increase frequency of urination" id="hc_ur_1"><label for="hc_ur_1">Increase frequency of urination</label></div>
                    <div class="option"><input type="checkbox" name="health_conditions[urinary][]" value="Burning sensation on urination" id="hc_ur_2"><label for="hc_ur_2">Burning sensation on urination</label></div>
                  </div>

                  <h2 class="section-title mt-3">NERVOUS SYSTEM</h2>
                  <div class="options">
                    <div class="option"><input type="checkbox" name="health_conditions[nervous][]" value="Headache" id="hc_nerv_1"><label for="hc_nerv_1">Headache</label></div>
                    <div class="option"><input type="checkbox" name="health_conditions[nervous][]" value="Convulsion/epilepsy" id="hc_nerv_2"><label for="hc_nerv_2">Convulsion/epilepsy</label></div>
                    <div class="option"><input type="checkbox" name="health_conditions[nervous][]" value="Numbness/Tingling" id="hc_nerv_3"><label for="hc_nerv_3">Numbness/Tingling</label></div>
                    <div class="option"><input type="checkbox" name="health_conditions[nervous][]" value="Dizziness/Fainting" id="hc_nerv_4"><label for="hc_nerv_4">Dizziness/Fainting</label></div>
                  </div>

                  <h2 class="section-title mt-3">BLOOD</h2>
                  <div class="options">
                    <div class="option"><input type="checkbox" name="health_conditions[blood][]" value="Bruise easily" id="hc_blood_1"><label for="hc_blood_1">Bruise easily</label></div>
                    <div class="option"><input type="checkbox" name="health_conditions[blood][]" value="Anemia" id="hc_blood_2"><label for="hc_blood_2">Anemia</label></div>
                  </div>

                  <h2 class="section-title mt-3">RESPIRATORY</h2>
                  <div class="options">
                    <div class="option"><input type="checkbox" name="health_conditions[respiratory][]" value="Persistent cough" id="hc_resp_1"><label for="hc_resp_1">Persistent cough</label></div>
                    <div class="option"><input type="checkbox" name="health_conditions[respiratory][]" value="Difficulty in breathing" id="hc_resp_2"><label for="hc_resp_2">Difficulty in breathing</label></div>
                    <div class="option"><input type="checkbox" name="health_conditions[respiratory][]" value="Asthma" id="hc_resp_3"><label for="hc_resp_3">Asthma</label></div>
                  </div>
                </div>
              </div>

            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Close</button>
              <button type="submit" name="book_appointment" class="btn btn-primary">Book Appointment</button>
            </div>
            </div>
          </div>
        </div>
        
      </form>
    </div>
  </div>
</div> 

<script>
    // Logic for the booking modal's time slots
    document.addEventListener('DOMContentLoaded', function () {
        const dateInputModal = document.getElementById('appointment_date');
        const timeSelectModal = document.getElementById('appointment_time');

        function populateTimes(date) {
            if (!timeSelectModal) return;
            timeSelectModal.innerHTML = '<option value="">-- Loading times --</option>';
            if (!date) {
                timeSelectModal.innerHTML = '<option value="">-- Select a date first --</option>';
                return;
            }
            
            const url = `index.php?get_calendar_times=1&date=${encodeURIComponent(date)}`;
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    timeSelectModal.innerHTML = '<option value="">-- Select a time --</option>';
                    if (data.length > 0) {
                        data.forEach(item => {
                            const option = document.createElement('option');
                            option.value = item.start;
                            option.textContent = item.label;
                            timeSelectModal.appendChild(option);
                        });
                    } else {
                        timeSelectModal.innerHTML = '<option value="">-- No available times --</option>';
                    }
                })
                .catch(err => {
                    timeSelectModal.innerHTML = '<option value="">-- Error loading times --</option>';
                });
        }

        if (dateInputModal) {
            dateInputModal.addEventListener('change', function () {
                populateTimes(this.value);
            });
        }
    });
</script>

  <?php include_once('includes/footer.php'); ?>
  <script src="js/health-modal.js"></script>
</body> 

</html>