<?php
session_start();
// DEBUG: show all errors while debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

  // Check if health conditions are not empty, null, or an empty JSON array/object
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

if (isset($_GET['get_month_availability']) && !empty($_GET['month']) && !empty($_GET['year'])) {
  $month = $_GET['month'];
  $year = $_GET['year'];

  try {
    // Subquery to get all slots in the month from tblcalendar
    $all_slots_sql = "SELECT `date`, `start_time` FROM `tblcalendar` WHERE YEAR(`date`) = :year1 AND MONTH(`date`) = :month1";

    // Subquery to get all booked slots in the month from tblappointment
    $booked_slots_sql = "SELECT `date`, `start_time` FROM `tblappointment` WHERE YEAR(`date`) = :year2 AND MONTH(`date`) = :month2 AND `status` != 'Declined' AND `status` != 'Cancelled'";

    // Main query to find dates with at least one available slot
    $sql = "SELECT DISTINCT T1.`date`
                FROM ($all_slots_sql) AS T1
                LEFT JOIN ($booked_slots_sql) AS T2 
                ON T1.`date` = T2.`date` AND T1.`start_time` = T2.`start_time`
                WHERE T2.`start_time` IS NULL";

    $query = $dbh->prepare($sql);
    $query->execute([':year1' => $year, ':month1' => $month, ':year2' => $year, ':month2' => $month]);
    $available_dates = $query->fetchAll(PDO::FETCH_COLUMN, 0);

    header('Content-Type: application/json');
    echo json_encode(['available' => $available_dates]);

  } catch (Exception $e) {
    header('Content-Type: application/json', true, 500);
    echo json_encode(['error' => $e->getMessage()]);
  }
  exit();
}


if (isset($_POST['book_appointment'])) {
  // Use 'sturecmsnumber' as it seems to be the patient ID from your DB structure
  $patient_number = $_SESSION['sturecmsnumber'] ?? null;
  $appointment_date = $_POST['appointment_date'];
  $appointment_time = $_POST['appointment_time'];

  if ($patient_number && !empty($appointment_date) && !empty($appointment_time)) {
    // Collect all health conditions into a structured array
    $health_conditions_structured = [];
    if (!empty($_POST['health_conditions']) && is_array($_POST['health_conditions'])) {
      foreach ($_POST['health_conditions'] as $category => $conditions) {
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
    } catch (PDOException $e) {
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

      // Insert notification for admin
      $admin_id = 1; // Assuming admin ID is 1
      $patient_name_sql = "SELECT firstname, surname FROM tblpatient WHERE number = :pnum";
      $patient_name_query = $dbh->prepare($patient_name_sql);
      $patient_name_query->execute([':pnum' => $patient_number]);
      $patient_info = $patient_name_query->fetch(PDO::FETCH_ASSOC);
      $notif_message = "New feedback received from " . htmlentities($patient_info['firstname'] . ' ' . $patient_info['surname']) . ".";
      $notif_url = "manage-reviews.php";
      $sql_notif = "INSERT INTO tblnotif (recipient_id, recipient_type, message, url) VALUES (:rid, 'admin', :msg, :url)";
      $query_notif = $dbh->prepare($sql_notif);
      $query_notif->execute([':rid' => $admin_id, ':msg' => $notif_message, ':url' => $notif_url]);
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
  <?php
  // Compute base href to always point to DC_System root (e.g. "/DC_System/")
  // This works for scripts in any subfolder
  $scriptPath = $_SERVER['SCRIPT_NAME']; // e.g. "/DC_System/index.php"
  $appRoot = dirname($scriptPath); // Go up one level to /DC_System
  $baseHref = rtrim($appRoot, '/\\') . '/';
  ?>
  <base href="<?php echo htmlspecialchars($baseHref, ENT_QUOTES, 'UTF-8'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <meta charset="utf-8">
  <title>JF Dental Care</title>
  <script
    type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); function hideURLbar(){ window.scrollTo(0,1); } </script>
  <!--bootstrap-->
  <link href="css/bootstrap.css" rel="stylesheet" type="text/css" media="all">
  <!--coustom css-->
  <link href="./css/style.css" rel="stylesheet" type="text/css" />
  <link href="./css/header.css" rel="stylesheet" type="text/css" />
  <link href="./css/style.v2.css" rel="stylesheet" type="text/css" />

  <!--script-->
  <style>
    * {
      -webkit-text-size-adjust: 100%;
      -ms-text-size-adjust: 100%;
      text-size-adjust: 100%;
    }

    html, body {
      width: 100%;
      height: 100%;
      margin: 0;
      padding: 0;
      overflow-x: hidden;
    }

    .service-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      justify-content: center;
    }

    .service-card {
      flex: 0 0 calc(25% - 15px); /* Forces 4 cards per row with 20px gap */
      max-width: 280px;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      /* Add transition for smooth effect */
    }

    .service-card:hover {
      transform: scale(1.05);
      /* Make it slightly larger on hover */
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
      /* Add a subtle shadow for depth */
    }

    @media (max-width: 1024px) {
      .service-card {
        flex: 0 0 calc(33.333% - 15px);
        max-width: 240px;
      }
    }

    @media (max-width: 768px) {
      .service-card {
        flex: 0 0 calc(50% - 10px);
        max-width: 200px;
      }
    }

    @media (max-width: 480px) {
      .service-card {
        flex: 0 0 calc(100% - 10px);
        max-width: 87%;
      }
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
  <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>

<body>
  <div id="home"></div>
  <?php include_once('includes/header.php'); ?>
  <div class="banner">
    <div class="slider">
      <div class="callbacks_container">
        <ul class="rslides" id="slider">
          <li>
            <h2>Where beautiful smiles come to life</h2>
            <p class="hero-description">
              Experience comprehensive dental care with our advanced patient management system.
              We provide personalized treatment plans and state of the art dental services
              for your optimal oral health.
            </p>
            <div class="readmore">
              <?php
              // Check for multiple possible session variables
              if (isset($_SESSION['sturecmsnumber'])) {
                // Only show the button if the user does NOT have health conditions on file
                if (!$user_has_health_conditions) { ?>
                  <a href="#" data-toggle="modal" data-target="#healthModal">
                    Book appointment <i class="ri-arrow-right-line"></i>
                  </a>
                <?php }
              } else { ?>
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
                <p class="feature-description">Experienced dental professionals with advanced training and
                  certifications.</p>
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
          <img src="images/doctor.png" alt="JF Dental Care Team" class="about-image" />
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
    <h1 style="color: #092c7a;">Our Services</h1>
    <div class="subtitle">
      We offer comprehensive dental services using the latest technology and techniques to<br>
      ensure your optimal oral health and beautiful smile.
    </div>
    <div id="services-carousel" class="services-cards-row">
      <?php
      // Fetch all services from the database
      try {
        // Fetch all categories to display as cards
        $sql_cat = "SELECT id, name, description, image FROM tblcategory ORDER BY id ASC";
        $query_cat = $dbh->prepare($sql_cat);
        $query_cat->execute();
        $categories = $query_cat->fetchAll(PDO::FETCH_OBJ);

        if ($query_cat->rowCount() > 0) {
          echo '<div class="service-grid">';
          foreach ($categories as $category) {
            $catName = htmlspecialchars($category->name ?? 'Category', ENT_QUOTES, 'UTF-8');
            $catDesc = htmlspecialchars($category->description ?? '', ENT_QUOTES, 'UTF-8');
            // Assuming category images are stored similarly to service images
            $catImage = !empty($category->image) ? 'admin/' . htmlspecialchars($category->image, ENT_QUOTES, 'UTF-8') : 'images/default-service.jpg';
      ?>
            <div class="service-card">
              <img src="<?php echo $catImage; ?>" alt="<?php echo $catName; ?>" class="service-card-img">
              <div class="service-card-body">
                <div class="service-card-title"><?php echo $catName; ?></div>
                <div class="service-card-desc"><?php echo $catDesc; ?></div>
              </div>
            </div>
      <?php
          }
          echo '</div>'; // Close service-grid
        }
      } catch (Exception $e) {
        echo '<div class="no-services">Could not load services due to a database error.</div>';
      }
      ?>
    </div>
  </section>




  <!--Contact-->

  <div class="contact-title" id="contact" style="color: #092c7a;">Contact Us</div>
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
        <div
          style="margin-top: 20px; background: #f5f8ff; border-radius: 16px; box-shadow: 0 2px 8px #e0e7ff; padding: 24px; text-align: center;">
          <div style="font-size: 22px; font-weight: 600; color: #222; margin-bottom: 8px;">Rate your experience</div>
          <div style="color: #6c7a89; font-size: 15px; margin-bottom: 18px;">We highly value your feedback! Kindly take
            a moment to rate your experience and provide us with your valuable feedback.</div>
          <div class="star-rating" style="margin-bottom: 18px;">
            <!-- ...existing star rating code... -->
            <input type="radio" id="star5" name="rating" value="5"><label for="star5" title="5 stars">&#9733;</label>
            <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="4 stars">&#9733;</label>
            <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="3 stars">&#9733;</label>
            <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="2 stars">&#9733;</label>
            <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="1 star">&#9733;</label>
          </div>
          <textarea name="feedback" rows="3"
            style="width: 90%; max-width: 400px; border-radius: 8px; border: 1px solid #dbeafe; padding: 12px; font-size: 15px; margin-bottom: 18px; resize: none;"
            placeholder="Tell us about your experience!"></textarea>
          <br>
          <button type="submit" name="submit_feedback"
            style="background: #ffd966; color: #222; border: none; border-radius: 24px; padding: 10px 32px; font-size: 16px; font-weight: 500; box-shadow: 0 2px 8px #e0e7ff; cursor: pointer;">Send</button>
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

  <!-- Health Condition Modal (cleaned up structure) -->
  <div class="modal fade health-questionnaire-modal" id="healthModal" tabindex="-1" role="dialog"
    aria-labelledby="healthModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content" >
        <form method="post">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"
              style="font-size: 1.5rem; background: none; border: none;">
              <span aria-hidden="true">X</span>
            </button>
            <h3 class="modal-title" id="healthModalLabel"><b>Health Condition Form</b></h3>
          </div>
          <div class="modal-body">
            <!-- Appointment fields -->
            <div class="appointment-fields">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="appointment_date">Preferred Date</label>
                    <!-- This input is now the trigger and will be populated by the calendar -->
                    <input type="text" class="form-control" name="appointment_date" id="appointment_date"
                      placeholder="Select a date" required readonly>
                    <!-- Interactive Calendar HTML -->
                    <div class="calendar-wrapper" id="calendarWrapper">
                      <div class="calendar-header">
                        <button type="button" class="nav-btn" id="prevBtn">‹</button>
                        <h2 id="monthYear"></h2>
                        <button type="button" class="nav-btn" id="nextBtn">›</button>
                      </div>
                      <div class="weekdays">
                        <div class="weekday">Mon</div>
                        <div class="weekday">Tue</div>
                        <div class="weekday">Wed</div>
                        <div class="weekday">Thu</div>
                        <div class="weekday">Fri</div>
                        <div class="weekday">Sat</div>
                        <div class="weekday">Sun</div>
                      </div>
                      <div class="days" id="daysContainer"></div>
                    </div>
                    <!-- End Interactive Calendar -->
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="appointment_time">Preferred Time</label>
                    <select class="form-control" name="appointment_time" id="appointment_time" required>
                      <option value="">Select a date first</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <p>Please check all conditions that apply to you.</p>
            <div class="two-column">
              <div>
                <div class="section">
                  <div class="section-title">General</div>
                  <div class="form-group"><label>Marked weight change</label><input type="checkbox"
                      name="health_conditions[general][]" value="Marked weight change"></div>
                </div>
                <div class="section">
                  <div class="section-title">Ear</div>
                  <div class="form-group"><label>Loss of hearing, ringing of ears</label><input type="checkbox"
                      name="health_conditions[ear][]" value="Loss of hearing, ringing of ears"></div>
                </div>
                <div class="section">
                  <div class="section-title">Nervous System</div>
                  <div class="form-group"><label>Headache</label><input type="checkbox"
                      name="health_conditions[nervous][]" value="Headache"></div>
                  <div class="form-group"><label>Convulsion / epilepsy</label><input type="checkbox"
                      name="health_conditions[nervous][]" value="Convulsion/epilepsy"></div>
                  <div class="form-group"><label>Numbness / Tingling</label><input type="checkbox"
                      name="health_conditions[nervous][]" value="Numbness/Tingling"></div>
                  <div class="form-group"><label>Dizziness / Fainting</label><input type="checkbox"
                      name="health_conditions[nervous][]" value="Dizziness/Fainting"></div>
                </div>
                <div class="section">
                  <div class="section-title">Blood</div>
                  <div class="form-group"><label>Bruise easily</label><input type="checkbox"
                      name="health_conditions[blood][]" value="Bruise easily"></div>
                  <div class="form-group"><label>Anemia</label><input type="checkbox" name="health_conditions[blood][]"
                      value="Anemia"></div>
                </div>
                <div class="section">
                  <div class="section-title">Respiratory</div>
                  <div class="form-group"><label>Persistent cough</label><input type="checkbox"
                      name="health_conditions[respiratory][]" value="Persistent cough"></div>
                  <div class="form-group"><label>Difficulty in breathing</label><input type="checkbox"
                      name="health_conditions[respiratory][]" value="Difficulty in breathing"></div>
                  <div class="form-group"><label>Asthma</label><input type="checkbox"
                      name="health_conditions[respiratory][]" value="Asthma"></div>
                </div>
                <div class="section">
                  <div class="section-title">Heart</div>
                  <div class="form-group"><label>Chest pain/discomfort</label><input type="checkbox"
                      name="health_conditions[heart][]" value="Chest pain/discomfort"></div>
                  <div class="form-group"><label>Shortness of breath</label><input type="checkbox"
                      name="health_conditions[heart][]" value="Shortness of breath"></div>
                  <div class="form-group"><label>Hypertension</label><input type="checkbox"
                      name="health_conditions[heart][]" value="Hypertension"></div>
                  <div class="form-group"><label>Ankle edema</label><input type="checkbox"
                      name="health_conditions[heart][]" value="Ankle edema"></div>
                  <div class="form-group"><label>Rheumatic fever (age)</label><input type="checkbox"
                      name="health_conditions[heart][]" value="Rheumatic fever"></div>
                  <div class="input-group"><input type="text" placeholder="Specify age"
                      name="health_conditions[rheumatic_age]"></div>
                  <div class="form-group"><label>History of stroke (When)</label><input type="checkbox"
                      name="health_conditions[heart][]" value="History of stroke"></div>
                  <div class="input-group"><input type="text" placeholder="When" name="health_conditions[stroke_when]">
                  </div>
                </div>
              </div>
              <div>
                <div class="section">
                  <div class="section-title">Urinary</div>
                  <div class="form-group"><label>Increase frequency of urination</label><input type="checkbox"
                      name="health_conditions[urinary][]" value="Increase frequency of urination"></div>
                  <div class="form-group"><label>Burning sensation on urination</label><input type="checkbox"
                      name="health_conditions[urinary][]" value="Burning sensation on urination"></div>
                </div>
                <div class="section">
                  <div class="section-title">Liver</div>
                  <div class="form-group"><label>History of liver ailment</label><input type="checkbox"
                      name="health_conditions[liver][]" value="History of liver ailment"></div>
                  <div class="input-group"><input type="text" placeholder="Specify"
                      name="health_conditions[liver_specify]"></div>
                  <div class="form-group"><label>Jaundice</label><input type="checkbox"
                      name="health_conditions[liver][]" value="Jaundice"></div>
                </div>
                <div class="section">
                  <div class="section-title">Diabetes</div>
                  <div class="form-group"><label>Delayed healing of wounds</label><input type="checkbox"
                      name="health_conditions[diabetes][]" value="Delayed healing of wounds"></div>
                  <div class="form-group"><label>Increase intake of food or water</label><input type="checkbox"
                      name="health_conditions[diabetes][]" value="Increase intake of food or water"></div>
                  <div class="form-group"><label>Family history of diabetes</label><input type="checkbox"
                      name="health_conditions[diabetes][]" value="Family history of diabetes"></div>
                </div>
                <div class="section">
                  <div class="section-title">Thyroid</div>
                  <div class="form-group"><label>Perspire easily</label><input type="checkbox"
                      name="health_conditions[thyroid][]" value="Perspire easily"></div>
                  <div class="form-group"><label>Apprehension</label><input type="checkbox"
                      name="health_conditions[thyroid][]" value="Apprehension"></div>
                  <div class="form-group"><label>Palpitation/rapid heart beat</label><input type="checkbox"
                      name="health_conditions[thyroid][]" value="Palpation/rapid heart beat"></div>
                  <div class="form-group"><label>Goiter</label><input type="checkbox"
                      name="health_conditions[thyroid][]" value="Goiter"></div>
                  <div class="form-group"><label>Bulging of eyes</label><input type="checkbox"
                      name="health_conditions[thyroid][]" value="Bulging of eyes"></div>
                </div>
                <div class="section">
                  <div class="section-title">Arthritis</div>
                  <div class="form-group"><label>Joint pain</label><input type="checkbox"
                      name="health_conditions[arthritis][]" value="Joint pain"></div>
                  <div class="form-group"><label>Joint Swelling</label><input type="checkbox"
                      name="health_conditions[arthritis][]" value="Joint Swelling"></div>
                </div>
                <div class="section">
                  <div class="section-title">Radiograph</div>
                  <div class="form-group"><label>Undergo radiation therapy</label><input type="checkbox"
                      name="health_conditions[radiograph][]" value="Undergo radiation therapy"></div>
                </div>
                <div class="section">
                  <div class="section-title">Women</div>
                  <div class="form-group"><label>Pregnancy (No. of month)</label><input type="checkbox"
                      name="health_conditions[women][]" value="Pregnancy"></div>
                  <div class="input-group"><input type="number" placeholder="No."
                      name="health_conditions[pregnancy_months]" min="1" max="9"></div>
                  <div class="form-group"><label>Breast feed</label><input type="checkbox"
                      name="health_conditions[women][]" value="Breast feed"></div>
                </div>
              </div>
            </div>

            <div class="section">
              <div class="section-title">Hospitalization</div>
              <div class="inline-group"><label>Have you been hospitalized</label><input type="checkbox"
                  name="health_conditions[hospitalization][]" value="Hospitalized"></div>
              <div class="input-group"><label>Date:</label><input type="date"
                  name="health_conditions[hospitalization_date]"></div>
              <div class="input-group"><label>Specify:</label><input type="text"
                  name="health_conditions[hospitalization_specify]" placeholder="Please specify reason"></div>
            </div>

            <div class="allergy-section">
              <div class="allergy-title">Are you allergic or have ever experienced any reaction to the ff?</div>
              <div class="inline-group">
                <label>Sleeping pills</label><input type="checkbox" name="health_conditions[allergies][]"
                  value="Sleeping pills">
                <label>Aspirin</label><input type="checkbox" name="health_conditions[allergies][]" value="Aspirin">
                <label>Food</label><input type="checkbox" name="health_conditions[allergies][]" value="Food">
              </div>
              <div class="inline-group">
                <label>Penicillin/other antibiotics</label><input type="checkbox" name="health_conditions[allergies][]"
                  value="Penicillin/other antibiotics">
                <label>Sulfa Drugs</label><input type="checkbox" name="health_conditions[allergies][]"
                  value="Sulfa Drugs">
                <label>Others</label><input type="checkbox" name="health_conditions[allergies][]" value="Others">
              </div>
              <div class="input-group"><label>Specify:</label><input type="text"
                  name="health_conditions[allergy_specify]" placeholder="Please specify allergies"></div>
            </div><br>

            <div class="section">
              <div class="section-title">Previous Extraction History</div>
              <div class="form-group"><label>Have you had any previous extraction</label><input type="checkbox"
                  name="health_conditions[extraction][]" value="Previous extraction"></div>
              <div class="input-group"><label>Date of last extraction:</label><input type="date"
                  name="health_conditions[extraction_date]"></div>
              <div class="input-group"><label>Specify:</label><textarea name="health_conditions[extraction_specify]"
                  rows="2" placeholder="Please provide details"></textarea></div>
              <div class="form-group"><label>Untoward reaction to extraction</label><input type="checkbox"
                  name="health_conditions[extraction][]" value="Untoward reaction to extraction"></div>
              <div class="input-group"><label>Specify:</label><input type="text"
                  name="health_conditions[extraction_reaction_specify]" placeholder="Please specify reaction"></div>
              <div class="form-group"><label>Were you under local anesthesia</label><input type="checkbox"
                  name="health_conditions[extraction][]" value="Under local anesthesia"></div>
              <div class="form-group"><label>Allergic reaction to local anesthesia</label><input type="checkbox"
                  name="health_conditions[extraction][]" value="Allergic reaction to local anesthesia"></div>
            </div>
            <div style="text-align: center; margin-top: 16px;">
              <button type="submit" name="book_appointment" class="submit-btn">Submit Form</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php include_once('includes/footer.php'); ?>
  <script src="js/responsiveslides.min.js"></script>
  <script src="js/health-modal.js"></script>
  <script src="js/health-questionnaire.js"></script>
  <script src="js/interactive-calendar.js"></script>
  <script src="js/script.js"></script>
</body>

</html>