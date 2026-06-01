<?php
  include('dbConnect/dbConnect.inc.php');

  $sql  = "SELECT DISTINCT A.GENDER, A.GRAD_CLASS ";
  $sql .= "FROM PP_PLAYERS A ";
  $sql .= "WHERE A.IS_ACTIVE = 1 ";
  $sql .= "ORDER BY A.GENDER DESC, A.GRAD_CLASS ASC ";
  $result = mysqli_query($cn, $sql);
  $playerClassSections = mysqli_fetch_all($result, MYSQLI_ASSOC);

  $trackViewCode = 'cz51ts';
?>

<!doctype html>
<html lang="en-US">

<?php include('includes/siteHtmlHeader.inc.php'); ?>
<?php $isEs = ($_SESSION['lang'] == 'es'); ?>

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

    echo "<div class=\"section pricing\" id=\"section-pricing\">";
    echo "  <div class=\"content\">";
    echo "    <div class=\"titles\">";
    echo "      <div class=\"title\">".$dispSectionName."</div>";
    echo "      <div class=\"subtitle\">".$dispClassName."</div>";
    echo "    </div>";
    echo "    <div class=\"content-carousel\">";
    echo "      <div class=\"owl-carousel\" data-slidesview=\"3\" data-slidesview_mobile=\"1\">";

    $sql  = "SELECT A.ID, A.FIRST_NAME, A.LAST_NAME, A.GENDER, A.GRAD_CLASS, A.GPA, A.ACT_SCORE, A.SAT_SCORE, ";
    $sql .= "  IFNULL(A.CLASS_RANK,'--') AS CLASS_RANK, B.POSITION AS POSITION_PRI, ";
    $sql .= "  IFNULL(C.POSITION,'--') AS POSITION_SEC, CONCAT(D.CITY,', ',D.STATE) AS FULL_LOCATION, ";
    $sql .= "  A.DATE_OF_BIRTH, IFNULL(A.HEIGHT_IN,0) AS HEIGHT_IN, IFNULL(A.DOMINATE_FOOT,'--') AS DOMINATE_FOOT, ";
    $sql .= "  IFNULL(E.ORG_NAME,'--') AS HIGH_SCHOOL_NAME, IFNULL(CONCAT(F.CITY,', ',F.STATE),'--') AS HS_FULL_LOCATION, ";
    $sql .= "  IFNULL(A.PHONE_NUMBER,'--') AS PHONE_NUMBER, IFNULL(A.EMAIL_ADDRESS,'--') AS EMAIL_ADDRESS, ";
    $sql .= "  A.IMG_HEADSHOT, A.IMG_ACTION, A.PDF_TRANSCRIPT, A.SOC_FACEBOOK, A.SOC_TWITTER, A.SOC_INSTAGRAM, ";
    $sql .= "  A.TXT_WHOAMI, A.TXT_GOALS, A.COMMITTED_FLAG ";
    $sql .= "FROM PP_PLAYERS A ";
    $sql .= "LEFT OUTER JOIN PP_POSITIONS B ON B.ID = A.POSITION_PRI ";
    $sql .= "LEFT OUTER JOIN PP_POSITIONS C ON C.ID = A.POSITION_SEC ";
    $sql .= "LEFT OUTER JOIN PP_LOCATIONS D ON D.ID = A.LOCATION ";
    $sql .= "LEFT OUTER JOIN PP_ORGANIZATIONS E ON E.ID = A.HIGH_SCHOOL ";
    $sql .= "LEFT OUTER JOIN PP_LOCATIONS F ON F.ID = E.LOCATION_ID ";
    $sql .= "WHERE A.IS_ACTIVE = 1 AND A.GENDER = '".$playerClassSection['GENDER']."' AND A.GRAD_CLASS = '".$playerClassSection['GRAD_CLASS']."' ";
    $sql .= "ORDER BY A.LAST_NAME ASC, A.FIRST_NAME ASC ";
    $result  = mysqli_query($cn, $sql);
    $players = mysqli_fetch_all($result, MYSQLI_ASSOC);

    foreach($players as $player) {
      if(strlen($player['IMG_HEADSHOT']) > 0) {
        $imgHeadshot = $player['IMG_HEADSHOT'];
      } else {
        $imgHeadshot = ($player['GENDER'] == 'M') ? 'images/headshots/nophotomale.jpg' : 'images/headshots/nophotofemale.jpg';
      }

      $committedLabel = $isEs ? 'COMPROMETIDO' : 'COMMITTED';
      $moreInfoLabel  = $isEs ? 'Mas Info'     : 'More Info';

      echo "        <div class=\"item\">";
      echo "          <div class=\"pricing-item\">";
      echo "            <div class=\"icons\">";
      echo "              <div style='position:relative;display:inline-block;'>";
      echo "                <a href=\"playerProfile.php?p=".$player['ID']."&v=".$trackViewCode."\"><img src='".$imgHeadshot."' style='width:120px;text-align:center;vertical-align:middle;border-radius:50%;display:inline-block;'></a>";
      if($player['COMMITTED_FLAG'] == 1) {
        echo "                <div style='position:absolute;bottom:6px;left:50%;transform:translateX(-50%);background:#27ae60;color:#fff;font-size:9px;font-weight:700;padding:2px 8px;border-radius:10px;white-space:nowrap;letter-spacing:1px;'>".$committedLabel."</div>";
      }
      echo "              </div>";
      echo "            </div><br><br><br>";
      echo "            <div class=\"name\"><a href=\"playerProfile.php?p=".$player['ID']."&v=".$trackViewCode."\" style='text-decoration:none;'>".$player['FIRST_NAME']." ".$player['LAST_NAME']."</a></div>";
      echo "            <div class=\"feature-list\"><ul>";
      echo "              <li>".$player['POSITION_PRI']."</li>";
      if(strlen($player['GPA'])       > 0){ echo "<li>".$player['GPA']."  GPA</li>"; }
      if(strlen($player['ACT_SCORE']) > 0){ echo "<li>".$player['ACT_SCORE']." ACT</li>"; }
      if(strlen($player['SAT_SCORE']) > 0){ echo "<li>".$player['SAT_SCORE']." SAT</li>"; }
      echo "            </ul></div>";
      echo "            <a href=\"playerProfile.php?p=".$player['ID']."&v=".$trackViewCode."\" class=\"btn\"><span class=\"animated-button\"><span>".$moreInfoLabel."</span></span><i class=\"icon fas fa-chevron-right\"></i></a>";
      echo "          </div>";
      echo "        </div>";
    }

    echo "      </div>";
    echo "      <div class=\"navs\"><span class=\"prev fas fa-chevron-left\"></span><span class=\"next fas fa-chevron-right\"></span></div>";
    echo "    </div>";
    echo "  </div>";
    echo "</div>";
  }
?>

    </div>

  </div>

<?php include('includes/siteFooter.inc.php'); ?>
<?php include('includes/extScripts.inc.php'); ?>

</body>
</html>
