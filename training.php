<?php
include('dbConnect/dbConnect.inc.php');
mysqli_query($cn, "CREATE TABLE IF NOT EXISTS URU_VARIABLES (VAR_KEY VARCHAR(100) PRIMARY KEY, VAR_VALUE TEXT NOT NULL, UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)");
$_uruVars = [];
$_vr = mysqli_query($cn, "SELECT VAR_KEY, VAR_VALUE FROM URU_VARIABLES");
while ($_vrow = mysqli_fetch_assoc($_vr)) $_uruVars[$_vrow['VAR_KEY']] = $_vrow['VAR_VALUE'];
$hpFlierImg      = $_uruVars['hp_flier_img']       ?? 'images/fliers/uruHighPerformance.jpg';
$hpFlierLink     = $_uruVars['hp_flier_link']      ?? 'https://forms.gle/TuvduKCEcqyuR9hF6';
$hpFlierExpiry   = $_uruVars['hp_flier1_expiry']   ?? '';
$hpFlierEnabled  = ($_uruVars['hp_flier1_enabled'] ?? '1') === '1'
                   && (empty($hpFlierExpiry) || strtotime($hpFlierExpiry) >= strtotime('today'));
$hpFlierBust     = @filemtime(__DIR__ . '/' . $hpFlierImg) ?: time();
$hpFlier2Img     = $_uruVars['hp_flier2_img']      ?? 'images/fliers/uruHighPerformance.jpg';
$hpFlier2Link    = $_uruVars['hp_flier2_link']     ?? 'https://forms.gle/TuvduKCEcqyuR9hF6';
$hpFlier2Expiry  = $_uruVars['hp_flier2_expiry']   ?? '';
$hpFlier2Enabled = ($_uruVars['hp_flier2_enabled'] ?? '1') === '1'
                   && (empty($hpFlier2Expiry) || strtotime($hpFlier2Expiry) >= strtotime('today'));
$hpFlier2Bust    = @filemtime(__DIR__ . '/' . $hpFlier2Img) ?: time();
?>

<!doctype html>
<html lang="en-US">

<?php $metaDescription = 'URU High Performance Soccer Training — skill development sessions and camps for youth and high school players in the region.'; ?>
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
              <?php if($_SESSION['lang'] == 'en'){ echo "In-Person Training"; } ?>
              <?php if($_SESSION['lang'] == 'es'){ echo "Entrenamiento Presencial"; } ?>
			</h1>

						<div class="h-subtitles">
							<div class="h-subtitle typing-subtitle">
								<?php if($_SESSION['lang'] == 'en'): ?>
								<p>URU High Performance</p>
								<p>Club Team Training</p>
								<p>School Team Training</p>
								<p>Individual Training</p>
								<?php endif; ?>
								<?php if($_SESSION['lang'] == 'es'): ?>
								<p>URU Alto Rendimiento</p>
								<p>Entrenamiento de Club</p>
								<p>Entrenamiento Escolar</p>
								<p>Entrenamiento Individual</p>
								<?php endif; ?>
							</div>
							<span class="typed-subtitle"></span>
						</div>

              <div class="h-text">
				<?php
				if($_SESSION['lang'] == 'en'){
					echo "<i>At our organization, we take pride in providing an extensive range of in-person training opportunities tailored to soccer players of all ages and skill levels. From small group conditioning sessions and technical skill development to comprehensive club and school team trainings, we are dedicated to working closely with you or your organization to create a customized and ideal solution that meets your unique training needs. Our experienced team of trainers and coaches is committed to ensuring that every participant receives top-notch guidance and support to enhance their soccer abilities and reach their full potential. With our diverse and flexible training programs, you can rest assured that we have the expertise and resources to help you excel on the field.</i>";
				}
				if($_SESSION['lang'] == 'es'){
					echo "<i>En nuestra organizacion, nos enorgullece ofrecer una amplia gama de oportunidades de entrenamiento presencial adaptadas a jugadores de futbol de todas las edades y niveles de habilidad. Desde sesiones de acondicionamiento en grupos pequenos y desarrollo de habilidades tecnicas hasta entrenamientos completos de equipos de club y escuela, estamos dedicados a trabajar estrechamente con usted o su organizacion para crear una solucion personalizada e ideal que satisfaga sus necesidades unicas de entrenamiento.</i>";
				}
				?>
			  </div>
              <a href="#" class="btn mouse-btn" style="display: none;"><i class="icon fas fa-chevron-down"></i></a>
            </div>
          </div>
          <div class="clear"></div>
		</div>
	  </div>

			<!-- URU High Performance -->
			<div class="section about" id="section-about">
				<div class="content">

					<div class="titles">
						<div class="title">
							<?php if($_SESSION['lang'] == 'en'){ echo "URU High Performance"; } ?>
							<?php if($_SESSION['lang'] == 'es'){ echo "URU Alto Rendimiento"; } ?>
						</div>
						<div class="subtitle">
							<?php if($_SESSION['lang'] == 'en'){ echo "Small Group Training"; } ?>
							<?php if($_SESSION['lang'] == 'es'){ echo "Entrenamiento en Grupos Pequenos"; } ?>
						</div>
					</div>

					<div class="cols">
						<div class="col col-full">
							<div class="single-post-text">
								<p>
									<?php
									if($_SESSION['lang'] == 'en'){
										echo "Come and experience URU High Performance! We are dedicated to maintaining high coach-to-athlete ratios to ensure that you receive individual attention during our small group sessions. URU High Performance training is built upon an integrated methodology that combines conditioning and technical drills, designed to enhance players' individual skills while fostering a high-intensity environment.";
									}
									if($_SESSION['lang'] == 'es'){
										echo "Venga y experimente URU Alto Rendimiento! Estamos dedicados a mantener altas proporciones de entrenador por atleta para garantizar que reciba atencion individual durante nuestras sesiones en grupos pequenos. El entrenamiento URU Alto Rendimiento se basa en una metodologia integrada que combina ejercicios de acondicionamiento y tecnicos, disenados para mejorar las habilidades individuales de los jugadores en un entorno de alta intensidad.";
									}
									?>
								</p>
								<?php if ($hpFlierEnabled || $hpFlier2Enabled):
								  $flierMaxW = 'width:calc(50% - 8px);flex:none;';
								?>
								<div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:12px;">
								  <?php if ($hpFlierEnabled): ?>
								  <div style="flex:1;min-width:200px;<?= $flierMaxW ?>text-align:center;">
								    <a href='<?= htmlspecialchars($hpFlierImg) ?>?v=<?= $hpFlierBust ?>' class="flier-popup" data-link='<?= htmlspecialchars($hpFlierLink) ?>'><img src='<?= htmlspecialchars($hpFlierImg) ?>?v=<?= $hpFlierBust ?>' style='width:100%;cursor:zoom-in;'></a>
								    <?php if (!empty($hpFlierLink)): ?><div style="margin-top:6px;font-size:12px;opacity:.7;"><a href='<?= htmlspecialchars($hpFlierLink) ?>' target='_BLANK' style="color:inherit;"><?= $_SESSION['lang']=='es' ? 'Haz clic para más información' : 'Click for more information' ?></a></div><?php endif; ?>
								  </div>
								  <?php endif; ?>
								  <?php if ($hpFlier2Enabled): ?>
								  <div style="flex:1;min-width:200px;<?= $flierMaxW ?>text-align:center;">
								    <a href='<?= htmlspecialchars($hpFlier2Img) ?>?v=<?= $hpFlier2Bust ?>' class="flier-popup" data-link='<?= htmlspecialchars($hpFlier2Link) ?>'><img src='<?= htmlspecialchars($hpFlier2Img) ?>?v=<?= $hpFlier2Bust ?>' style='width:100%;cursor:zoom-in;'></a>
								    <?php if (!empty($hpFlier2Link)): ?><div style="margin-top:6px;font-size:12px;opacity:.7;"><a href='<?= htmlspecialchars($hpFlier2Link) ?>' target='_BLANK' style="color:inherit;"><?= $_SESSION['lang']=='es' ? 'Haz clic para más información' : 'Click for more information' ?></a></div><?php endif; ?>
								  </div>
								  <?php endif; ?>
								</div>
								<?php endif; ?>
							</div>
						</div>
					</div>

					<div class="clear"></div>
				</div>
			</div>

			<!-- Team Training -->
			<div class="section about" id="section-about">
				<div class="content">

					<div class="titles">
						<div class="title">
							<?php if($_SESSION['lang'] == 'en'){ echo "Team Training"; } ?>
							<?php if($_SESSION['lang'] == 'es'){ echo "Entrenamiento de Equipo"; } ?>
						</div>
						<div class="subtitle">
							<?php if($_SESSION['lang'] == 'en'){ echo "Club / School Teams"; } ?>
							<?php if($_SESSION['lang'] == 'es'){ echo "Equipos de Club / Escuela"; } ?>
						</div>
					</div>

					<div class="cols">
						<div class="col col-full">
							<div class="single-post-text">
								<p>
									<?php
									if($_SESSION['lang'] == 'en'){
										echo "At our organization, we take pride in providing a wide array of training options, offering custom training sessions meticulously crafted to accommodate the age, skill level, and developmental needs of your school or club team; get in touch with us today, and let us demonstrate how our expertise can be harnessed to develop a personalized session package that best suits the specific requirements of your team, ensuring optimal growth and progress.";
									}
									if($_SESSION['lang'] == 'es'){
										echo "En nuestra organizacion, nos enorgullece ofrecer una amplia variedad de opciones de entrenamiento, con sesiones personalizadas meticulosamente disenadas para adaptarse a la edad, nivel de habilidad y necesidades de desarrollo de su equipo escolar o de club. Contactenos hoy y dejenos demostrarle como nuestra experiencia puede aprovecharse para desarrollar un paquete de sesiones personalizado que mejor se adapte a los requisitos especificos de su equipo, garantizando un crecimiento y progreso optimos.";
									}
									?>
								</p>
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
<script>
$(function(){
  $('.flier-popup').magnificPopup({
    type: 'image',
    closeOnContentClick: true,
    image: { verticalFit: true },
    callbacks: {
      open: function() {
        var link = this.st.el.data('link');
        if (link) {
          setTimeout(function(){
            $('.mfp-img').css('cursor','pointer').off('click').on('click', function(){
              window.open(link, '_blank');
            });
          }, 100);
        }
      }
    }
  });
});
</script>
</body>
</html>
