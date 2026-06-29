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
  .player-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:18px;margin-top:20px;}
  .player-card{background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.13);border-radius:12px;overflow:hidden;display:flex;flex-direction:column;transition:background .2s,transform .2s;text-decoration:none;color:inherit;}
  .player-card:hover{background:rgba(255,255,255,0.14);transform:translateY(-4px);color:inherit;text-decoration:none;}
  .player-card .pc-photo{width:100%;aspect-ratio:1/1;object-fit:cover;object-position:top;display:block;}
  .player-card .pc-body{padding:14px 14px 16px;display:flex;flex-direction:column;flex:1;}
  .player-card .pc-name{font-size:15px;font-weight:700;line-height:1.2;margin-bottom:4px;}
  .player-card .pc-pos{font-size:11px;text-transform:uppercase;letter-spacing:1.2px;opacity:.55;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
  .player-card .pc-stats{display:flex;flex-wrap:wrap;gap:6px;margin-top:auto;}
  .player-card .pc-stat{background:rgba(255,255,255,0.1);border-radius:6px;padding:3px 9px;font-size:11px;font-weight:600;}
  .player-card .pc-badges{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px;}
  .player-card .pc-committed,
  .player-card .pc-video-badge{font-size:9px;font-weight:700;padding:3px 10px;border-radius:10px;letter-spacing:1px;line-height:1.4;display:inline-flex;align-items:center;gap:4px;}
  .player-card .pc-committed{background:#27ae60;color:#fff;}
  .player-card .pc-video-badge{background:rgba(231,76,60,0.85);color:#fff;}
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
                : ($player['GENDER'] == 'M' ? 'images/headshots/nophotomale.svg' : 'images/headshots/nophotofemale.svg');
            ?>
            <a href="playerProfile.php?p=<?= $player['ID'] ?>&v=<?= $trackViewCode ?>" class="player-card">
              <img class="pc-photo" src="<?= htmlspecialchars($imgHeadshot) ?>" alt="<?= htmlspecialchars($player['FIRST_NAME'].' '.$player['LAST_NAME']) ?>" loading="lazy">
              <div class="pc-body">
                <?php if($player['COMMITTED_FLAG'] == 1 || $player['VIDEO_COUNT'] > 0): ?>
                <div class="pc-badges">
                  <?php if($player['COMMITTED_FLAG'] == 1): ?><span class="pc-committed"><?= $committedLabel ?></span><?php endif; ?>
                  <?php if($player['VIDEO_COUNT'] > 0): ?><span class="pc-video-badge"><i class="fas fa-play"></i> <?= $isEs ? 'Video' : 'Video' ?></span><?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="pc-name"><?= htmlspecialchars($player['FIRST_NAME'].' '.$player['LAST_NAME']) ?></div>
                <div class="pc-pos"><?= htmlspecialchars($player['POSITION_PRI'] ?? '') ?></div>
                <?php if(!empty($player['FULL_LOCATION'])): ?><div class="pc-pos"><i class="fas fa-map-marker-alt" style="opacity:.5;font-size:9px;margin-right:3px;"></i><?= htmlspecialchars(abbrevState($player['FULL_LOCATION'], $stateAbbr)) ?></div><?php endif; ?>
                <div class="pc-stats">
                  <?php if(strlen($player['GPA'])       > 0): ?><span class="pc-stat"><?= htmlspecialchars($player['GPA']) ?> GPA</span><?php endif; ?>
                  <?php if(strlen($player['ACT_SCORE']) > 0): ?><span class="pc-stat"><?= htmlspecialchars($player['ACT_SCORE']) ?> ACT</span><?php endif; ?>
                  <?php if(strlen($player['SAT_SCORE']) > 0): ?><span class="pc-stat"><?= htmlspecialchars($player['SAT_SCORE']) ?> SAT</span><?php endif; ?>
                </div>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
<?php } ?>

    </div>

  </div>

<?php include('includes/siteFooter.inc.php'); ?>
<?php include('includes/extScripts.inc.php'); ?>

</body>
</html>
