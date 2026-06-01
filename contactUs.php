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
		<div class="contact-form">
          <form id="cform" method="post">
            <div class="group-val">
              <div class="label">
                <?php if($_SESSION['lang'] == 'en'){ echo "Full name"; } ?>
                <?php if($_SESSION['lang'] == 'es'){ echo "Nombre completo"; } ?>
                <strong>*</strong>
              </div>
              <input type="text" name="name" placeholder="<?php if($_SESSION['lang'] == 'en'){ echo 'eg. John Smith'; } if($_SESSION['lang'] == 'es'){ echo 'ej. Juan Garcia'; } ?>" />
            </div>
            <div class="group-val">
              <div class="label">
                <?php if($_SESSION['lang'] == 'en'){ echo "Email address"; } ?>
                <?php if($_SESSION['lang'] == 'es'){ echo "Correo electronico"; } ?>
                <strong>*</strong>
              </div>
              <input type="email" name="email" placeholder="example@domain.com" />
            </div>
            <div class="group-val">
              <div class="label">
                <?php if($_SESSION['lang'] == 'en'){ echo "Message"; } ?>
                <?php if($_SESSION['lang'] == 'es'){ echo "Mensaje"; } ?>
                <strong>*</strong>
              </div>
              <textarea name="message" placeholder="<?php if($_SESSION['lang'] == 'en'){ echo 'Message'; } if($_SESSION['lang'] == 'es'){ echo 'Mensaje'; } ?>"></textarea>
            </div>
            <div class="group-bts">
              <button type="submit" class="btn">
                <span class="animated-button">
                  <span>
                    <?php if($_SESSION['lang'] == 'en'){ echo "Send Message"; } ?>
                    <?php if($_SESSION['lang'] == 'es'){ echo "Enviar Mensaje"; } ?>
                  </span>
                </span>
                <i class="icon fas fa-chevron-right"></i>
              </button>
		    </div>
          </form>
          <div class="alert-success">
            <p>
              <?php if($_SESSION['lang'] == 'en'){ echo "Thanks, your message is sent successfully."; } ?>
              <?php if($_SESSION['lang'] == 'es'){ echo "Gracias, su mensaje ha sido enviado exitosamente."; } ?>
            </p>
          </div>
        </div>
        <div class="contact-info">
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
