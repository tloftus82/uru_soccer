<?php include('dbConnect/dbConnect.inc.php'); ?>

<!doctype html>
<html lang="en-US">

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
        <div class="contact-info">
          <img src="images/headshots/carlos.jpg" alt="Carlos Saenz" style="width:160px;height:160px;object-fit:cover;border-radius:50%;display:block;margin:0 auto 20px;">
          <div class="name">Carlos Saenz</div>
          <div class="subname">
            <?php if($_SESSION['lang'] == 'en'){ echo "Head Coach"; } ?>
            <?php if($_SESSION['lang'] == 'es'){ echo "Entrenador Principal"; } ?>
          </div>
          <div class="info-list">
            <ul>
              <li><strong><?php if($_SESSION['lang'] == 'en'){ echo "Phone:"; } if($_SESSION['lang'] == 'es'){ echo "Telefono:"; } ?></strong>(402) 508-0568</li>
              <li><strong><?php if($_SESSION['lang'] == 'en'){ echo "E-mail:"; } if($_SESSION['lang'] == 'es'){ echo "Correo:"; } ?></strong><a href='mailto:uruhighperformance@gmail.com'>uruhighperformance@gmail.com</a></li>
            </ul>
          </div>
          <div class="author">Carlos Saenz</div>
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
