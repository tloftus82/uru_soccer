<?php
include('dbConnect/dbConnect.inc.php');
mysqli_query($cn, "CREATE TABLE IF NOT EXISTS URU_VARIABLES (VAR_KEY VARCHAR(100) PRIMARY KEY, VAR_VALUE TEXT NOT NULL, UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)");
$_uruVars = [];
$_vr = mysqli_query($cn, "SELECT VAR_KEY, VAR_VALUE FROM URU_VARIABLES");
while ($_vrow = mysqli_fetch_assoc($_vr)) $_uruVars[$_vrow['VAR_KEY']] = $_vrow['VAR_VALUE'];

$hpFlierImg      = $_uruVars['hp_flier_img']       ?? 'images/fliers/uruHighPerformance.jpg';
$hpFlierLink     = $_uruVars['hp_flier_link']       ?? '';
$hpFlierExpiry   = $_uruVars['hp_flier1_expiry']    ?? '';
$hpFlierEnabled  = ($_uruVars['hp_flier1_enabled']  ?? '1') === '1'
                   && (empty($hpFlierExpiry) || strtotime($hpFlierExpiry) >= strtotime('today'));
$hpFlierBust     = @filemtime(__DIR__ . '/' . $hpFlierImg) ?: time();

$hpFlier2Img     = $_uruVars['hp_flier2_img']       ?? 'images/fliers/uruHighPerformance.jpg';
$hpFlier2Link    = $_uruVars['hp_flier2_link']       ?? '';
$hpFlier2Expiry  = $_uruVars['hp_flier2_expiry']    ?? '';
$hpFlier2Enabled = ($_uruVars['hp_flier2_enabled']  ?? '1') === '1'
                   && (empty($hpFlier2Expiry) || strtotime($hpFlier2Expiry) >= strtotime('today'));
$hpFlier2Bust    = @filemtime(__DIR__ . '/' . $hpFlier2Img) ?: time();

$showFliers = $hpFlierEnabled || $hpFlier2Enabled;
?>

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
            <div class="started-content" style="padding-top:60px;">
              <h1 class="h-title">
                <?php if($_SESSION['lang'] == 'en'){echo "Welcome!";} ?>
                <?php if($_SESSION['lang'] == 'es'){echo "Bienvenido!";} ?>
              </h1>

              <div class="h-text">
                <?php if($_SESSION['lang'] == 'en'): ?>
                  <i>Welcome to URU.soccer!  Our mission is to facilitate the progression of regional soccer talent by offering them opportunities to pursue their educational and soccer endeavors at the collegiate level. We aim to equip student-athletes with the necessary tools and resources to effectively navigate the recruitment process and successfully complete the admission procedure.</i>
                <?php endif; ?>
                <?php if($_SESSION['lang'] == 'es'): ?>
                  <i>Bienvenidos a URU.soccer!  Nuestra mision es facilitar la progresion del talento futbolistico regional ofreciendoles oportunidades para continuar con sus esfuerzos educativos y futbolisticos a nivel universitario. Nuestro objetivo es equipar a los estudiantes-atletas con las herramientas y recursos necesarios para navegar de manera efectiva el proceso de reclutamiento y completar con exito el procedimiento de admision.</i>
                <?php endif; ?>

                <?php if ($showFliers):
                  $flierMaxW = 'width:calc(50% - 8px);flex:none;';
                ?>
                <div style="margin:18px 0;">
                  <div style="font-size:12px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;opacity:.55;margin-bottom:10px;">
                    <?= $_SESSION['lang'] == 'es' ? 'Oportunidades de Entrenamiento' : 'Current Training Opportunities' ?>
                  </div>
                  <div style="display:flex;gap:16px;flex-wrap:wrap;">
                    <?php if ($hpFlierEnabled): ?>
                    <div style="flex:1;min-width:200px;<?= $flierMaxW ?>text-align:center;">
                      <a href='<?= htmlspecialchars($hpFlierImg) ?>?v=<?= $hpFlierBust ?>' class="flier-popup" data-link='<?= htmlspecialchars($hpFlierLink) ?>'><img src='<?= htmlspecialchars($hpFlierImg) ?>?v=<?= $hpFlierBust ?>' style='width:100%;border-radius:8px;cursor:zoom-in;'></a>
                      <?php if (!empty($hpFlierLink)): ?><div style="margin-top:5px;font-size:11px;opacity:.7;"><a href='<?= htmlspecialchars($hpFlierLink) ?>' target='_BLANK' style="color:inherit;"><?= $_SESSION['lang']=='es' ? 'Haz clic para más información' : 'Click for more information' ?></a></div><?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($hpFlier2Enabled): ?>
                    <div style="flex:1;min-width:200px;<?= $flierMaxW ?>text-align:center;">
                      <a href='<?= htmlspecialchars($hpFlier2Img) ?>?v=<?= $hpFlier2Bust ?>' class="flier-popup" data-link='<?= htmlspecialchars($hpFlier2Link) ?>'><img src='<?= htmlspecialchars($hpFlier2Img) ?>?v=<?= $hpFlier2Bust ?>' style='width:100%;border-radius:8px;cursor:zoom-in;'></a>
                      <?php if (!empty($hpFlier2Link)): ?><div style="margin-top:5px;font-size:11px;opacity:.7;"><a href='<?= htmlspecialchars($hpFlier2Link) ?>' target='_BLANK' style="color:inherit;"><?= $_SESSION['lang']=='es' ? 'Haz clic para más información' : 'Click for more information' ?></a></div><?php endif; ?>
                    </div>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endif; ?>
              </div>

            </div>

            <style>.footer:before { display: none !important; }</style>
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
