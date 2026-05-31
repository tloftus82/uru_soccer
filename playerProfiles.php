<?php
  //ini_set('display_errors', 1);
  //ini_set('display_startup_errors', 1);
  //error_reporting(E_ALL);

  include('dbConnect/dbConnect.inc.php');

  //get required genders / sections that need displayed
  $sql = "";
  $sql .= "SELECT DISTINCT A.GENDER, A.GRAD_CLASS ";
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

<body class="home">

  <?php include('includes/sitePreloader.inc.php'); ?>
	
  <div class="container">
	<?php include('includes/siteHeader.inc.php'); ?>

    <div class="wrapper">
      <?php include('includes/siteBgImg.inc.php'); ?>
      <div class="section started" id="section-started">
        <div class="centrize full-width">
          <div class="vertical-center">
            <h1 class="h-title">Player Profiles</h1>
            <div class="started-content">
              <div class="h-text">
                Below you will find our featured player profiles, which have been carefully curated to meet your team's specific needs, so please take your time to thoroughly review them and don't hesitate to contact us if you require any further information or assistance!
              </div>
              <a href="#" class="btn mouse-btn" style="display: none;"><i class="icon fas fa-chevron-down"></i></a>
            </div>
          </div>
        </div>
      </div>


<?php
  foreach ($playerClassSections as $playerClassSection){
    $dispSectionName = "";
    if($playerClassSection['GENDER'] == 'M'){$dispSectionName = "Men's Team";}
    if($playerClassSection['GENDER'] == 'F'){$dispSectionName = "Women's Team";}

    $dispClassName = "";
    $tdy = new DateTime();
    $grad = new DateTime($playerClassSection['GRAD_CLASS'].'-06-01');
    $diff = $grad->diff($tdy);
    if($diff->invert == 0){
      $diffYrs = 0 - ceil($diff->days/365.25);
    } else {
      $diffYrs = ceil($diff->days/365.25);
    }

    if($diffYrs > 7){$dispClassName = 'Elementary Schoolers &#8226; Class of '.$playerClassSection['GRAD_CLASS'];}
    if($diffYrs == 7){$dispClassName = '6th Graders &#8226; Class of '.$playerClassSection['GRAD_CLASS'];}
    if($diffYrs == 6){$dispClassName = '7th Graders &#8226; Class of '.$playerClassSection['GRAD_CLASS'];}
    if($diffYrs == 5){$dispClassName = '8th Graders &#8226; Class of  '.$playerClassSection['GRAD_CLASS'];}
    if($diffYrs == 4){$dispClassName = 'Freshmen &#8226; Class of  '.$playerClassSection['GRAD_CLASS'];}
    if($diffYrs == 3){$dispClassName = 'Sophomores &#8226; Class of  '.$playerClassSection['GRAD_CLASS'];}
    if($diffYrs == 2){$dispClassName = 'Juniors &#8226; Class of  '.$playerClassSection['GRAD_CLASS'];}
    if($diffYrs <= 1){$dispClassName = 'Seniors &#8226; Class of  '.$playerClassSection['GRAD_CLASS'];}

    echo "<div class=\"section pricing\" id=\"section-pricing\">";
    echo "  <div class=\"content\">";
    echo "    <div class=\"titles\">";
    echo "      <div class=\"title\">".$dispSectionName."</div>";
    echo "      <div class=\"subtitle\">".$dispClassName."</div>";
    echo "    </div>";
    echo "    <div class=\"content-carousel\">";
    echo "      <div class=\"owl-carousel\" data-slidesview=\"3\" data-slidesview_mobile=\"1\">";
    
    //get players in this gender / class group
    $sql = "";
    $sql .= "SELECT ";
    $sql .= "  A.ID, ";
    $sql .= "  A.FIRST_NAME, ";
    $sql .= "  A.LAST_NAME, ";
    $sql .= "  A.GENDER, ";
    $sql .= "  A.GRAD_CLASS, ";
    $sql .= "  A.GPA, ";
    $sql .= "  A.ACT_SCORE, ";
    $sql .= "  A.SAT_SCORE, ";
    $sql .= "  IFNULL(A.CLASS_RANK,'--') AS CLASS_RANK, ";
    $sql .= "  B.POSITION AS POSITION_PRI, ";
    $sql .= "  IFNULL(C.POSITION,'--') AS POSITION_SEC, ";
    $sql .= "  CONCAT(D.CITY,', ',D.STATE) AS FULL_LOCATION, ";
    $sql .= "  A.DATE_OF_BIRTH, ";
    $sql .= "  IFNULL(A.HEIGHT_IN,0) AS HEIGHT_IN, ";
    $sql .= "  IFNULL(A.DOMINATE_FOOT,'--') AS DOMINATE_FOOT, ";
    $sql .= "  IFNULL(E.ORG_NAME,'--') AS HIGH_SCHOOL_NAME, ";
    $sql .= "  IFNULL(CONCAT(F.CITY,', ',F.STATE),'--') AS HS_FULL_LOCATION, ";
    $sql .= "  IFNULL(A.PHONE_NUMBER,'--') AS PHONE_NUMBER, ";
    $sql .= "  IFNULL(A.EMAIL_ADDRESS,'--') AS EMAIL_ADDRESS, ";
    $sql .= "  A.IMG_HEADSHOT, ";
    $sql .= "  A.IMG_ACTION, ";
    $sql .= "  A.PDF_TRANSCRIPT, ";
    $sql .= "  A.SOC_FACEBOOK, ";
    $sql .= "  A.SOC_TWITTER, ";
    $sql .= "  A.SOC_INSTAGRAM, ";
    $sql .= "  A.TXT_WHOAMI, ";
    $sql .= "  A.TXT_GOALS, ";
    $sql .= "  A.COMMITTED_FLAG ";
    $sql .= "FROM PP_PLAYERS A ";
    $sql .= "LEFT OUTER JOIN PP_POSITIONS B ON B.ID = A.POSITION_PRI ";
    $sql .= "LEFT OUTER JOIN PP_POSITIONS C ON C.ID = A.POSITION_SEC ";
    $sql .= "LEFT OUTER JOIN PP_LOCATIONS D ON D.ID = A.LOCATION ";
    $sql .= "LEFT OUTER JOIN PP_ORGANIZATIONS E ON E.ID = A.HIGH_SCHOOL ";
    $sql .= "LEFT OUTER JOIN PP_LOCATIONS F ON F.ID = E.LOCATION_ID ";
    $sql .= "WHERE ";
    $sql .= "  A.IS_ACTIVE = 1 ";
    $sql .= "  AND A.GENDER = '".$playerClassSection['GENDER']."' ";
    $sql .= "  AND A.GRAD_CLASS = '".$playerClassSection['GRAD_CLASS']."' ";
    $sql .= "ORDER BY ";
    $sql .= "  A.LAST_NAME ASC, ";
    $sql .= "  A.FIRST_NAME ASC ";
    $result = mysqli_query($cn, $sql);
    $players = mysqli_fetch_all($result, MYSQLI_ASSOC); 

    foreach($players as $player){
      $imgHeadshot = "";
      if(strlen($player['IMG_HEADSHOT']) > 0){
        $imgHeadshot = $player['IMG_HEADSHOT'];
      } else {
        if($player['GENDER'] == "M"){$imgHeadshot = 'images/headshots/nophotomale.jpg';}
        if($player['GENDER'] == "F"){$imgHeadshot = 'images/headshots/nophotofemale.jpg';}    
      };

      echo "        <div class=\"item\">";
      echo "          <div class=\"pricing-item\">";
      echo "            <div class=\"icons\">";
      echo "              <div style='position:relative;display:inline-block;'>";
      echo "                <a href=\"playerProfile.php?p=".$player['ID']."&v=".$trackViewCode."\"><img src='".$imgHeadshot."' style='width: 120px; text-align: center;vertical-align: middle;border-radius: 50%; display:inline-block;'></a>";
      if($player['COMMITTED_FLAG'] == 1){
        echo "                <div style='position:absolute;bottom:6px;left:50%;transform:translateX(-50%);background:#27ae60;color:#fff;font-size:9px;font-weight:700;padding:2px 8px;border-radius:10px;white-space:nowrap;letter-spacing:1px;'>COMMITTED</div>";
      }
      echo "              </div>";
      echo "            </div><br><br><br>";
      echo "            <div class=\"name\"><a href=\"playerProfile.php?p=".$player['ID']."&v=".$trackViewCode."\" style='text-decoration: none;'>".$player['FIRST_NAME']." ".$player['LAST_NAME']."</a></div>";
      echo "            <div class=\"feature-list\">";
      echo "              <ul>";
      echo "                <li>".$player['POSITION_PRI']."</li>";
      if(strlen($player['GPA']) > 0){echo "<li>".$player['GPA']." GPA</li>";}
      if(strlen($player['ACT_SCORE']) > 0){echo "<li>".$player['ACT_SCORE']." ACT</li>";}
      if(strlen($player['SAT_SCORE']) > 0){echo "<li>".$player['SAT_SCORE']." SAT</li>";}
      echo "              </ul>";
      echo "            </div>";
      echo "            <a href=\"playerProfile.php?p=".$player['ID']."&v=".$trackViewCode."\" class=\"btn\"><span class=\"animated-button\"><span>More Info</span></span><i class=\"icon fas fa-chevron-right\"></i></a>";
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
