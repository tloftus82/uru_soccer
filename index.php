<?php include('dbConnect/dbConnect.inc.php'); ?>

<!doctype html>
<!-- GitHub auto-deploy test: OK -->
<html lang="en-US">

<?php include('includes/siteHtmlHeader.inc.php'); ?>

<body class="home">

  <?php include('includes/sitePreloader.inc.php'); ?>
	
  <div class="container">
	<?php include('includes/siteHeader.inc.php'); ?>
	
    <div class="wrapper">
      <?php include('includes/siteBgImg.inc.php'); ?>

	  <div class="section started" id="section-started">
	    <div class="centrize full-width">
		  <div class="vertical-center">
            <div class="started-content">
              <h1 class="h-title">
                <?php if($_SESSION['lang'] == 'en'){echo "Welcome!";} ?>
                <?php if($_SESSION['lang'] == 'es'){echo "Bienvenido!";} ?>
			  </h1>

              <div class="h-text">
                <?php
                  if($_SESSION['lang'] == 'en'){
			        echo "<i>Welcome to URU.soccer!  Our mission is to facilitate the progression of regional soccer talent by offering them opportunities to pursue their educational and soccer endeavors at the collegiate level. We aim to equip student-athletes with the necessary tools and resources to effectively navigate the recruitment process and successfully complete the admission procedure.</i>";
                    echo "<br /><br />";
                    echo "<i>We encourage you to utilize the convenient navigation bar located at the top of the page, which will effortlessly guide you through our site, allowing you to explore all of our offerings and discover the many benefits that await you!</i>";
                    echo "<br /><br />";
                    echo "<a href='setLang.php?lang=es'>Ver este sitio en Espanol.</a>";
                  }
                  if($_SESSION['lang'] == 'es'){
			        echo "<i>Bienvenidos a URU.soccer!  Nuestra mision es facilitar la progresion del talento futbolistico regional ofreciendoles oportunidades para continuar con sus esfuerzos educativos y futbolisticos a nivel universitario. Nuestro objetivo es equipar a los estudiantes-atletas con las herramientas y recursos necesarios para navegar de manera efectiva el proceso de reclutamiento y completar con exito el procedimiento de admision.</i>";
                    echo "<br /><br />";
                    echo "<i>Le recomendamos que utilice la conveniente barra de navegacion ubicada en la parte superior de la pagina, que lo guiara sin esfuerzo a traves de nuestro sitio, permitiendole explorar todas nuestras ofertas y descubrir los muchos beneficios que le esperan.</i>";
                    echo "<br /><br />";
                    echo "<a href='setLang.php?lang=en'>View our site in English.</a>";
                  }
                ?>
			  </div>


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
