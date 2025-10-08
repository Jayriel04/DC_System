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

  <!-- Ensure jQuery and Bootstrap JS are available for the carousel (matches other pages) -->
  <script src="js/jquery-1.11.0.min.js"></script>
  <script src="js/bootstrap.js"></script>

<?php include_once('includes/footer.php');?>
</body>
</html>