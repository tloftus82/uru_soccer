<?php include('dbConnect/dbConnect.inc.php'); ?>

<!doctype html>
<html lang="<?= (isset($_SESSION['lang']) && $_SESSION['lang'] === 'es') ? 'es' : 'en-US' ?>">

<?php
$pageTitle       = 'About Us';
$metaDescription = 'Learn about URU Soccer — our mission, vision, values, and the team behind our college recruiting and player development programs.';
?>
<?php include('includes/siteHtmlHeader.inc.php'); ?>

<body class="home">

  <?php include('includes/sitePreloader.inc.php'); ?>

  <div class="container">
    <?php include('includes/siteHeader.inc.php'); ?>

    <div class="wrapper">
      <div class="background-bg">
        <div class="background-filter circle" style="background-image: url('images/headshots/carlos.jpg');"></div>
      </div>

      <div class="section started" id="section-started">
        <div class="centrize full-width">
          <div class="vertical-center">
            <div class="started-content">

              <h1 class="h-title">
                <?php if($_SESSION['lang'] == 'en'){ echo "About Us"; } ?>
                <?php if($_SESSION['lang'] == 'es'){ echo "Sobre"; } ?>
              </h1>

              <div class="h-text">
                <?php if($_SESSION['lang'] == 'en'): ?>
                  <b>Carlos Saenz, Owner</b>:&nbsp;&nbsp;<i>Carlos Saenz has experience working with players at different levels. A native from Lima, Peru has helped develop kids at a young age all the way to preparing players to perform at the College Division I and Semi-Pro Soccer level.
                  Saenz also works with teams competing at the High School, Youth Soccer Clubs, College, WPSL and UPSL level where he led them to multiple Championships.
                  Saenz has a bachelors in Exercise Science, a Master's in Healthcare Administration as well as several Soccer Licenses through US Soccer.</i>
                  <br /><br />
                  <b>Mission</b>:&nbsp;&nbsp;<i>Our mission is to facilitate the progression of regional soccer talent by offering them opportunities to pursue their educational and soccer endeavors at the collegiate level. We aim to equip student-athletes with the necessary tools and resources to effectively navigate the recruitment process and successfully complete the admission procedure.</i>
                  <br /><br />
                  <b>Vision</b>:&nbsp;&nbsp;<i>Our vision is to develop a cutting-edge online platform that seamlessly bridges the gap between nationwide coaches and regional talent. This innovative platform will empower student-athletes to effortlessly discover educational institutions that perfectly align with their academic pursuits and athletic ambitions, ensuring an optimal fit for their holistic development.</i>
                  <br /><br />
                  <b>Values</b>:&nbsp;&nbsp;<i>Community, Integrity, Commitment, and Responsibility</i>
                <?php endif; ?>
                <?php if($_SESSION['lang'] == 'es'): ?>
                  <b>Carlos Saenz, Dueno</b>:&nbsp;&nbsp;<i>Carlos Saenz tiene experiencia trabajando con jugadores de diferentes niveles. Originario de Lima, Peru, ha ayudado a desarrollar a ninos desde una edad temprana hasta preparar jugadores para desempenarse en la Division Universitaria I y en el nivel de futbol semiprofesional.
                  Saenz tambien trabaja con equipos que compiten a nivel de escuela secundaria, clubes de futbol juvenil, universidad, WPSL y UPSL, donde los llevo a multiples campeonatos.
                  Saenz tiene una licenciatura en Ciencias del Ejercicio, una Maestria en Administracion de Atencion Medica y varias Licencias de Futbol a traves de US Soccer.</i>
                  <br /><br />
                  <b>Mision</b>:&nbsp;&nbsp;<i>Nuestra mision es facilitar la progresion del talento futbolistico regional ofreciendoles oportunidades para continuar con sus esfuerzos educativos y futbolisticos a nivel universitario. Nuestro objetivo es equipar a los estudiantes-atletas con las herramientas y recursos necesarios para navegar de manera efectiva el proceso de reclutamiento y completar con exito el procedimiento de admision.</i>
                  <br /><br />
                  <b>Vision</b>:&nbsp;&nbsp;<i>Nuestra vision es desarrollar una plataforma en linea de vanguardia que cierre la brecha entre los entrenadores a nivel nacional y el talento regional. Esta innovadora plataforma permitira a los estudiantes-atletas descubrir sin esfuerzo instituciones educativas que se alineen perfectamente con sus actividades academicas y ambiciones atleticas, garantizando una adaptacion optima para su desarrollo integral.</i>
                  <br /><br />
                  <b>Valores</b>:&nbsp;&nbsp;<i>Comunidad, Integridad, Compromiso y Responsabilidad</i>
                <?php endif; ?>
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
