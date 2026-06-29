<?php
  include('dbConnect/dbConnect.inc.php');

  $sql  = "SELECT DISTINCT A.GENDER, A.GRAD_CLASS ";
  $sql .= "FROM PP_PLAYERS A ";
  $sql .= "WHERE A.IS_ACTIVE = 1 ";
  $sql .= "ORDER BY A.GENDER DESC, A.GRAD_CLASS ASC ";
  $result = mysqli_query($cn, $sql);
  $playerClassSections = mysqli_fetch_all($result, MYSQLI_ASSOC);

  $trackViewCode = 'cz51ts';

  $stateAbbr = [
    'Alabama'=>'AL','Alaska'=>'AK','Arizona'=>'AZ','Arkansas'=>'AR','California'=>'CA',
    'Colorado'=>'CO','Connecticut'=>'CT','Delaware'=>'DE','Florida'=>'FL','Georgia'=>'GA',
    'Hawaii'=>'HI','Idaho'=>'ID','Illinois'=>'IL','Indiana'=>'IN','Iowa'=>'IA',
    'Kansas'=>'KS','Kentucky'=>'KY','Louisiana'=>'LA','Maine'=>'ME','Maryland'=>'MD',
    'Massachusetts'=>'MA','Michigan'=>'MI','Minnesota'=>'MN','Mississippi'=>'MS',
    'Missouri'=>'MO','Montana'=>'MT','Nebraska'=>'NE','Nevada'=>'NV','New Hampshire'=>'NH',
    'New Jersey'=>'NJ','New Mexico'=>'NM','New York'=>'NY','North Carolina'=>'NC',
    'North Dakota'=>'ND','Ohio'=>'OH','Oklahoma'=>'OK','Oregon'=>'OR','Pennsylvania'=>'PA',
    'Rhode Island'=>'RI','South Carolina'=>'SC','South Dakota'=>'SD','Tennessee'=>'TN',
    'Texas'=>'TX','Utah'=>'UT','Vermont'=>'VT','Virginia'=>'VA','Washington'=>'WA',
    'West Virginia'=>'WV','Wisconsin'=>'WI','Wyoming'=>'WY','District of Columbia'=>'DC',
  ];
  function abbrevState($location, $map) {
    if (empty($location)) return $location;
    $parts = explode(', ', $location, 2);
    if (count($parts) === 2 && isset($map[trim($parts[1])])) {
      return $parts[0] . ', ' . $map[trim($parts[1])];
    }
    return $location;
  }
?>

<!doctype html>
<html lang="<?= (isset($_SESSION['lang']) && $_SESSION['lang'] === 'es') ? 'es' : 'en-US' ?>">

<?php
$pageTitle       = 'Player Profiles';
$metaDescription = 'URU Soccer player profiles — browse student-athletes seeking collegiate soccer opportunities. View stats, accolades, highlight videos, and contact information.';
?>
<?php include('includes/siteHtmlHeader.inc.php'); ?>
<?php $isEs = ($_SESSION['lang'] == 'es'); ?>

<style>
  /* ── Grid ── */
  .player-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:18px;margin-top:20px;}

  /* ── Flip wrapper ── */
  .pc-flip-wrap{perspective:1000px;aspect-ratio:3/4;cursor:pointer;}
  .pc-flip-inner{position:relative;width:100%;height:100%;transform-style:preserve-3d;transition:transform .55s cubic-bezier(.4,.2,.2,1);}
  .pc-flip-wrap:hover .pc-flip-inner,
  .pc-flip-wrap.flipped .pc-flip-inner{transform:rotateY(180deg);}

  /* ── Both faces ── */
  .pc-front,.pc-back{position:absolute;inset:0;backface-visibility:hidden;-webkit-backface-visibility:hidden;border-radius:12px;overflow:hidden;}

  /* ── Front ── */
  .pc-front{display:block;text-decoration:none;color:inherit;}
  .pc-front .pc-photo{width:100%;height:100%;object-fit:cover;object-position:top;display:block;}
  .pc-front-overlay{position:absolute;bottom:0;left:0;right:0;padding:14px;background:linear-gradient(to top,rgba(0,0,0,.88) 0%,rgba(0,0,0,.45) 55%,transparent 100%);}
  .pc-front-overlay .pc-badges{display:flex;gap:5px;flex-wrap:wrap;margin-bottom:6px;}
  .pc-front-overlay .pc-name{font-size:14px;font-weight:700;color:#fff;line-height:1.2;margin-bottom:2px;}
  .pc-front-overlay .pc-pos{font-size:10px;text-transform:uppercase;letter-spacing:1.2px;color:rgba(255,255,255,.6);}

  /* ── Back ── */
  .pc-back{transform:rotateY(180deg);background:linear-gradient(145deg,#1a1e24 0%,#252d38 100%);border:1px solid rgba(56,182,255,.2);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:20px 14px;text-align:center;gap:0;}
  .pc-back-accent{width:32px;height:3px;background:#38B6FF;border-radius:2px;margin-bottom:12px;flex-shrink:0;}
  .pc-back .pc-name{font-size:15px;font-weight:700;color:#fff;line-height:1.2;margin-bottom:4px;}
  .pc-back .pc-pos{font-size:10px;text-transform:uppercase;letter-spacing:1.2px;color:#38B6FF;margin-bottom:4px;}
  .pc-back .pc-location{font-size:11px;color:rgba(255,255,255,.45);margin-bottom:12px;}
  .pc-back .pc-stats{display:flex;flex-wrap:wrap;gap:5px;justify-content:center;margin-bottom:10px;}
  .pc-back .pc-stat{background:rgba(56,182,255,.12);border:1px solid rgba(56,182,255,.25);border-radius:6px;padding:3px 9px;font-size:11px;font-weight:600;color:#fff;}
  .pc-back .pc-badges{display:flex;gap:5px;flex-wrap:wrap;justify-content:center;margin-bottom:14px;}
  .pc-view-btn{display:inline-block;background:#38B6FF;color:#fff;font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;padding:7px 18px;border-radius:20px;text-decoration:none;transition:background .2s;flex-shrink:0;}
  .pc-view-btn:hover{background:#1fa3f0;color:#fff;text-decoration:none;}

  /* ── Shared badges ── */
  .pc-committed,.pc-video-badge{font-size:9px;font-weight:700;padding:3px 10px;border-radius:10px;letter-spacing:1px;line-height:1.4;display:inline-flex;align-items:center;gap:4px;}
  .pc-committed{background:#27ae60;color:#fff;}
  .pc-video-badge{background:rgba(231,76,60,.85);color:#fff;}

  @media(max-width:540px){.player-grid{grid-template-columns:repeat(2,1fr);gap:12px;}}
</style>

<body class="home">

  <?php include('includes/sitePreloader.inc.php'); ?>

  <div class="container">
	<?php include('includes/siteHeader.inc.php'); ?>

    <div class="wrapper">
      <?php include('includes/siteBgImg.inc.php'); ?>
      <div class="section started" id="section-started">
        <div class="centrize full-width">
          <div class="vertical-center">
            <h1 class="h-title">
              <?php if(!$isEs){ echo "Player Profiles"; } ?>
              <?php if($isEs){  echo "Perfiles de Jugadores"; } ?>
            </h1>
            <div class="started-content">
              <div class="h-text">
                <?php
                if(!$isEs){
                  echo "Below you will find our featured player profiles, which have been carefully curated to meet your team's specific needs, so please take your time to thoroughly review them and don't hesitate to contact us if you require any further information or assistance!";
                } else {
                  echo "A continuacion encontrara nuestros perfiles de jugadores destacados, cuidadosamente seleccionados para satisfacer las necesidades especificas de su equipo. Tómese el tiempo necesario para revisarlos detalladamente y no dude en contactarnos si requiere informacion adicional o asistencia.";
                }
                ?>
              </div>
              <a href="#" class="btn mouse-btn" style="display: none;"><i class="icon fas fa-chevron-down"></i></a>
            </div>
          </div>
        </div>
      </div>

<?php
  foreach ($playerClassSections as $playerClassSection) {

    if($isEs) {
      $dispSectionName = ($playerClassSection['GENDER'] == 'M') ? "Equipo Masculino" : "Equipo Femenino";
    } else {
      $dispSectionName = ($playerClassSection['GENDER'] == 'M') ? "Men's Team" : "Women's Team";
    }

    $tdy  = new DateTime();
    $grad = new DateTime($playerClassSection['GRAD_CLASS'].'-08-01');
    $diff = $grad->diff($tdy);
    $diffYrs = $diff->invert == 0 ? (0 - ceil($diff->days/365.25)) : ceil($diff->days/365.25);

    $classOf = ' &#8226; ' . ($isEs ? 'Clase de ' : 'Class of ') . $playerClassSection['GRAD_CLASS'];

    if($isEs) {
      if($diffYrs > 7)    { $dispClassName = 'Escuela Primaria'  . $classOf; }
      elseif($diffYrs==7) { $dispClassName = '6to Grado'         . $classOf; }
      elseif($diffYrs==6) { $dispClassName = '7mo Grado'         . $classOf; }
      elseif($diffYrs==5) { $dispClassName = '8vo Grado'         . $classOf; }
      elseif($diffYrs==4) { $dispClassName = 'Primer Ano'        . $classOf; }
      elseif($diffYrs==3) { $dispClassName = 'Segundo Ano'       . $classOf; }
      elseif($diffYrs==2) { $dispClassName = 'Tercer Ano'        . $classOf; }
      else                { $dispClassName = 'Cuarto Ano'        . $classOf; }
    } else {
      if($diffYrs > 7)    { $dispClassName = 'Elementary Schoolers' . $classOf; }
      elseif($diffYrs==7) { $dispClassName = '6th Graders'          . $classOf; }
      elseif($diffYrs==6) { $dispClassName = '7th Graders'          . $classOf; }
      elseif($diffYrs==5) { $dispClassName = '8th Graders'          . $classOf; }
      elseif($diffYrs==4) { $dispClassName = 'Freshmen'             . $classOf; }
      elseif($diffYrs==3) { $dispClassName = 'Sophomores'           . $classOf; }
      elseif($diffYrs==2) { $dispClassName = 'Juniors'              . $classOf; }
      else                { $dispClassName = 'Seniors'              . $classOf; }
    }

    $sql  = "SELECT A.ID, A.FIRST_NAME, A.LAST_NAME, A.GENDER, A.GPA, A.ACT_SCORE, A.SAT_SCORE, ";
    $sql .= "  B.POSITION AS POSITION_PRI, A.IMG_HEADSHOT, A.COMMITTED_FLAG, ";
    $sql .= "  CONCAT(D.CITY,', ',D.STATE) AS FULL_LOCATION, ";
    $sql .= "  (SELECT COUNT(*) FROM PP_VIDEOS V WHERE V.PLAYER_ID = A.ID) AS VIDEO_COUNT ";
    $sql .= "FROM PP_PLAYERS A ";
    $sql .= "LEFT OUTER JOIN PP_POSITIONS B ON B.ID = A.POSITION_PRI ";
    $sql .= "LEFT OUTER JOIN PP_LOCATIONS D ON D.ID = A.LOCATION ";
    $sql .= "WHERE A.IS_ACTIVE = 1 AND A.GENDER = '".$playerClassSection['GENDER']."' AND A.GRAD_CLASS = '".$playerClassSection['GRAD_CLASS']."' ";
    $sql .= "ORDER BY A.LAST_NAME ASC, A.FIRST_NAME ASC ";
    $result  = mysqli_query($cn, $sql);
    $players = mysqli_fetch_all($result, MYSQLI_ASSOC);

    $committedLabel = $isEs ? 'COMPROMETIDO' : 'COMMITTED';
    $moreInfoLabel  = $isEs ? 'Mas Info'     : 'More Info';
?>
      <div class="section about" id="section-about">
        <div class="content">
          <div class="titles">
            <div class="title"><?= $dispSectionName ?></div>
            <div class="subtitle"><?= $dispClassName ?></div>
          </div>
          <div class="player-grid">
            <?php foreach($players as $player):
              $imgHeadshot = strlen($player['IMG_HEADSHOT']) > 0
                ? $player['IMG_HEADSHOT']
                : ($player['GENDER'] == 'M' ? 'images/headshots/nophotomale.jpg' : 'images/headshots/nophotofemale.jpg');
            ?>
            <div class="pc-flip-wrap">
              <div class="pc-flip-inner">
                <!-- FRONT: full photo with name overlay -->
                <a href="playerProfile.php?p=<?= $player['ID'] ?>&v=<?= $trackViewCode ?>" class="pc-front">
                  <img class="pc-photo" src="<?= htmlspecialchars($imgHeadshot) ?>" alt="<?= htmlspecialchars($player['FIRST_NAME'].' '.$player['LAST_NAME']) ?>" loading="lazy">
                  <div class="pc-front-overlay">
                    <?php if($player['COMMITTED_FLAG'] == 1 || $player['VIDEO_COUNT'] > 0): ?>
                    <div class="pc-badges">
                      <?php if($player['COMMITTED_FLAG'] == 1): ?><span class="pc-committed"><?= $committedLabel ?></span><?php endif; ?>
                      <?php if($player['VIDEO_COUNT'] > 0): ?><span class="pc-video-badge"><i class="fas fa-play"></i> Video</span><?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <div class="pc-name"><?= htmlspecialchars($player['FIRST_NAME'].' '.$player['LAST_NAME']) ?></div>
                    <div class="pc-pos"><?= htmlspecialchars($player['POSITION_PRI'] ?? '') ?></div>
                  </div>
                </a>
                <!-- BACK: stats + link -->
                <div class="pc-back">
                  <div class="pc-back-accent"></div>
                  <div class="pc-name"><?= htmlspecialchars($player['FIRST_NAME'].' '.$player['LAST_NAME']) ?></div>
                  <div class="pc-pos"><?= htmlspecialchars($player['POSITION_PRI'] ?? '') ?></div>
                  <?php if(!empty($player['FULL_LOCATION'])): ?>
                  <div class="pc-location"><i class="fas fa-map-marker-alt" style="font-size:9px;margin-right:3px;opacity:.5;"></i><?= htmlspecialchars(abbrevState($player['FULL_LOCATION'], $stateAbbr)) ?></div>
                  <?php endif; ?>
                  <div class="pc-stats">
                    <?php if(strlen($player['GPA'])       > 0): ?><span class="pc-stat"><?= htmlspecialchars($player['GPA']) ?> GPA</span><?php endif; ?>
                    <?php if(strlen($player['ACT_SCORE']) > 0): ?><span class="pc-stat"><?= htmlspecialchars($player['ACT_SCORE']) ?> ACT</span><?php endif; ?>
                    <?php if(strlen($player['SAT_SCORE']) > 0): ?><span class="pc-stat"><?= htmlspecialchars($player['SAT_SCORE']) ?> SAT</span><?php endif; ?>
                  </div>
                  <?php if($player['COMMITTED_FLAG'] == 1 || $player['VIDEO_COUNT'] > 0): ?>
                  <div class="pc-badges">
                    <?php if($player['COMMITTED_FLAG'] == 1): ?><span class="pc-committed"><?= $committedLabel ?></span><?php endif; ?>
                    <?php if($player['VIDEO_COUNT'] > 0): ?><span class="pc-video-badge"><i class="fas fa-play"></i> Video</span><?php endif; ?>
                  </div>
                  <?php endif; ?>
                  <a href="playerProfile.php?p=<?= $player['ID'] ?>&v=<?= $trackViewCode ?>" class="pc-view-btn"><?= $isEs ? 'Ver Perfil' : 'View Profile' ?> &rsaquo;</a>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
<?php } ?>

    </div>

  </div>

<?php include('includes/siteFooter.inc.php'); ?>
<?php include('includes/extScripts.inc.php'); ?>
<script>
(function(){
  // On touch devices: tap front = flip, tap back "View Profile" = navigate
  if (!window.matchMedia('(hover: none)').matches) return;
  var wraps = document.querySelectorAll('.pc-flip-wrap');
  wraps.forEach(function(wrap){
    wrap.querySelector('.pc-front').addEventListener('click', function(e){
      if (!wrap.classList.contains('flipped')) {
        e.preventDefault();
        wraps.forEach(function(w){ w.classList.remove('flipped'); });
        wrap.classList.add('flipped');
      }
    });
  });
  // Tap anywhere outside a flipped card to unflip
  document.addEventListener('click', function(e){
    if (!e.target.closest('.pc-flip-wrap')) {
      wraps.forEach(function(w){ w.classList.remove('flipped'); });
    }
  });
})();
</script>
</body>
</html>
