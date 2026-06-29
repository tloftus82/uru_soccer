<?php include('dbConnect/dbConnect.inc.php'); ?>

<!doctype html>
<html lang="<?= (isset($_SESSION['lang']) && $_SESSION['lang'] === 'es') ? 'es' : 'en-US' ?>">

<?php
$pageTitle       = 'Contact Us';
$metaDescription = 'Contact URU Soccer — reach out to learn more about our college recruiting services, training programs, and how we can help your student-athlete.';
?>
<?php include('includes/siteHtmlHeader.inc.php'); ?>

<body class="home">

  <?php include('includes/sitePreloader.inc.php'); ?>

  <div class="container">
	<?php include('includes/siteHeader.inc.php'); ?>

    <div class="wrapper">
            <?php include('includes/siteBgImg.inc.php'); ?>

    <div class="section contacts" id="section-contacts">
      <div class="content">
        <div class="titles">
          <div class="title"><br>
            <?php if($_SESSION['lang'] == 'en'){ echo "Contact"; } ?>
            <?php if($_SESSION['lang'] == 'es'){ echo "Contacto"; } ?>
          </div>
          <div class="subtitle">
            <?php if($_SESSION['lang'] == 'en'){ echo "Let's talk"; } ?>
            <?php if($_SESSION['lang'] == 'es'){ echo "Hablemos"; } ?>
          </div>
        </div>
        <div style="display:flex;align-items:flex-start;gap:40px;flex-wrap:wrap;">
          <div class="contact-info" style="flex:1;min-width:220px;">
            <div class="name">Carlos Saenz</div>
            <div class="subname">
              <?php if($_SESSION['lang'] == 'en'){ echo "Head Coach"; } ?>
              <?php if($_SESSION['lang'] == 'es'){ echo "Entrenador Principal"; } ?>
            </div>
            <div class="info-list">
              <ul>
                <li><strong><?php if($_SESSION['lang'] == 'en'){ echo "Phone:"; } if($_SESSION['lang'] == 'es'){ echo "Telefono:"; } ?></strong><a href="tel:+14025080568" style="color:inherit;">(402) 508-0568</a></li>
                <li><strong><?php if($_SESSION['lang'] == 'en'){ echo "E-mail:"; } if($_SESSION['lang'] == 'es'){ echo "Correo:"; } ?></strong><a href='mailto:uruhighperformance@gmail.com'>uruhighperformance@gmail.com</a></li>
              </ul>
            </div>
            <div class="author">Carlos Saenz</div>
          </div>
          <div style="flex-shrink:0;">
            <img src="images/headshots/carlos.jpg" alt="Carlos Saenz"
                 style="width:220px;height:auto;object-fit:contain;border-radius:10px;display:block;box-shadow:0 4px 18px rgba(0,0,0,0.25);">
          </div>
        </div>
        <div class="clear"></div>
      </div>
    </div>


		</div>
	  </div>

<?php include('includes/siteFooter.inc.php'); ?>
<?php include('includes/extScripts.inc.php'); ?>

</body>
</html>
