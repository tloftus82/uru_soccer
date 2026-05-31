<style>
  input[type="text"] {
    width: 300px; /* Set the desired width */
  }
</style>
<?php
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);

  include('dbConnect/dbConnect.inc.php');

  $pageName = $_SERVER['REQUEST_URI'];
  $userIp = $_SERVER['REMOTE_ADDR'];
  $hostName = gethostbyaddr($userIp);
  $ipDetails = json_decode(file_get_contents("http://ipinfo.io/".$userIp."/json"));
  $ipLocation = $ipDetails->city.", ".$ipDetails->region.", ".$ipDetails->country;
  $ipOrg = $ipDetails->org;

  //insert view record into tracking table
  $sql = "";
  $sql .= "INSERT INTO SITE_VIEW_LOG ( ";
  $sql .= "  PAGE, VIEW_DATE_TIME, IP_ADDRESS, HOST_NAME, IP_LOCATION, IP_ORG ";
  $sql .= ") VALUES ( ";
  $sql .= "  '".$pageName."', NOW(), '".$userIp."', '".$hostName."', '".$ipLocation."', '".$ipOrg."' ";
  $sql .= ");";
  $result = mysqli_query($cn, $sql);

  $passCodeChk = "36315";
  
  if(isset($_GET['p']) == 1){
    $playerId = $_GET['p'];
  }

  if(isset($_GET['v']) == 1){
    $viewCode = $_GET['v'];
  }

  if($viewCode <> $passCodeChk){
    echo "Not authorized.";
    die;
  }

  if(isset($_POST['UPDATE_TYPE']) == 1){
    if($_POST['UPDATE_TYPE'] == 'PP_PLAYERS'){
      $updateArray = $_POST;
      $i = 0;

      $sql = "";
      $sql .= "UPDATE PP_PLAYERS A SET "; 
      foreach($updateArray as $key => $value){
        if($key <> "UPDATE_TYPE" AND $key <> "PP_PLAYERS"){
          if($i > 0){$sql .= ",";}
          if(strlen($value) == 0){$sql .= "  A.".$key." = NULL ";}
          elseif(is_numeric($value) == 1){$sql .= "  A.".$key." = ".$value." ";}
          else{$sql .= "  A.".$key." = '".addslashes($value)."' ";}
          $i++;
        }
      }
      $sql .= "WHERE A.ID = ".$playerId.";";
      $result = mysqli_query($cn, $sql);

      echo mysqli_affected_rows($cn)." record(s) updated!";
    }

    if($_POST['UPDATE_TYPE'] == 'PP_REFERENCES'){
      $updateArray = $_POST;
      $i = 0;

      do {
        $sql = "UPDATE PP_REFERENCES A SET A.IS_ACTIVE = ".$updateArray['IS_ACTIVE'][$i].", A.SORT_ORDER = ".$updateArray['SORT_ORDER'][$i]." WHERE A.ID = ".$updateArray['REF_ID'][$i].";";
        $result = mysqli_query($cn, $sql);
        if($i > 0){echo "<br>";}        
        echo mysqli_affected_rows($cn)." record(s) updated!";        
        $i++;
      } while ($i < sizeof($updateArray['REF_ID']));
    }

    if($_POST['UPDATE_TYPE'] == 'PP_REFERENCES_ADD'){
      $updateArray = $_POST;
      $i = 0;

      do {
        //get ref type id
        $sql = "SELECT B.ORG_TYPE FROM PP_CONTACTS A INNER JOIN PP_ORGANIZATIONS B ON B.ID = A.ORG_ID WHERE A.ID = ".$updateArray['ADD_ID'][$i].";";
        $result = mysqli_query($cn, $sql);
        $refTypeArray = mysqli_fetch_all($result, MYSQLI_ASSOC);

        $sql = "INSERT INTO PP_REFERENCES (PLAYER_ID, REF_TYPE_ID, REF_CONTACT_ID, IS_ACTIVE, SORT_ORDER) VALUES (".$playerId.", ".$refTypeArray[0]['ORG_TYPE'].", ".$updateArray['ADD_ID'][$i].", 1, 0);";
        $result = mysqli_query($cn, $sql);
        if($i > 0){echo "<br>";}        
        echo mysqli_affected_rows($cn)." record(s) updated!";        
        $i++;
      } while ($i < sizeof($updateArray['ADD_ID']));
    }
  }

  $sql = "";
  $sql .= "SELECT A.* FROM PP_PLAYERS A WHERE A.ID = ".$playerId.";";
  $result = mysqli_query($cn, $sql);
  $playerInfo = mysqli_fetch_array($result, MYSQLI_ASSOC);
  if(mysqli_num_rows($result) == 0){echo "Player not found."; die();}

  $gradYearOptions = "";
  $i = date("Y") - 1;
  while($i <= date("Y") + 5){
    $selFlag = "";
    if($i == $playerInfo['GRAD_CLASS']){$selFlag = "SELECTED";}
    $gradYearOptions .= "<option value=".$i." ".$selFlag.">".$i."</option>";
    $i++;
  }

  $feet = array("Left","Right","Ambidextrous");
  $domFootOptions = "";
  foreach($feet as $foot){
    $selFlag = "";
    if($foot == $playerInfo['DOMINATE_FOOT']){$selFlag = "SELECTED";}
    $domFootOptions .= "<option value='".$foot."' ".$selFlag.">".$foot."</option>";
  }

  $sql = "";
  $sql .= "SELECT A.* FROM PP_LOCATIONS A ORDER BY A.CITY ASC, A.STATE ASC ";
  $result = mysqli_query($cn, $sql);
  $locationArray = mysqli_fetch_all($result, MYSQLI_ASSOC);
  $locationOptions = "";
  foreach($locationArray as $location){
    $selFlag = "";
    if($location['ID'] == $playerInfo['LOCATION']){$selFlag = "SELECTED";}
    $locationOptions .= "<option value='".$location['ID']."' ".$selFlag.">".$location['CITY'].", ".$location['STATE']."</option>";
  }

  $sql = "";
  $sql .= "SELECT A.* FROM PP_POSITIONS A ORDER BY A.POSITION ASC";
  $result = mysqli_query($cn, $sql);
  $positionArray = mysqli_fetch_all($result, MYSQLI_ASSOC);
  
  $posPriOptions = "<option value=''>N/A</option>";
  foreach($positionArray as $position){
    $selFlag = "";
    if($position['ID'] == $playerInfo['POSITION_PRI']){$selFlag = "SELECTED";}
    $posPriOptions .= "<option value='".$position['ID']."' ".$selFlag.">".$position['POSITION']."</option>";
  }

  $posSecOptions = "<option value=''>N/A</option>";
  foreach($positionArray as $position){
    $selFlag = "";
    if($position['ID'] == $playerInfo['POSITION_SEC']){$selFlag = "SELECTED";}     
    $posSecOptions .= "<option value='".$position['ID']."' ".$selFlag.">".$position['POSITION']."</option>";
  }

  $sql = "";
  $sql .= "SELECT A.* FROM PP_ORGANIZATIONS A WHERE A.ORG_TYPE = 2 ORDER BY A.ORG_NAME ASC";
  $result = mysqli_query($cn, $sql);
  $highSchoolArray = mysqli_fetch_all($result, MYSQLI_ASSOC);
  $highSchoolOptions = "";
  foreach($highSchoolArray as $highSchool){
    $selFlag = "";
    if($highSchool['ID'] == $playerInfo['HIGH_SCHOOL']){$selFlag = "SELECTED";}     
    $highSchoolOptions .= "<option value='".$highSchool['ID']."' ".$selFlag.">".$highSchool['ORG_NAME']."</option>";
  }

  $sql = "";
  $sql .= "SELECT A.* FROM PP_ORGANIZATIONS A WHERE A.ORG_TYPE = 1 ORDER BY A.ORG_NAME ASC";
  $result = mysqli_query($cn, $sql);
  $clubArray = mysqli_fetch_all($result, MYSQLI_ASSOC);
  $clubOptions = "";
  $clubOptions = "<option value=''>N/A</option>";
  foreach($clubArray as $club){
    $selFlag = "";
    if($club['ID'] == $playerInfo['CLUB']){$selFlag = "SELECTED";}     
    $clubOptions .= "<option value='".$club['ID']."' ".$selFlag.">".$club['ORG_NAME']."</option>";
  }

  $activeOptions = "";
  $selFlagNo = "";
  $selFlagYes = "";
  if($playerInfo['IS_ACTIVE'] == 0){$selFlagNo = "SELECTED";} else {$selFlagYes = "SELECTED";}
  $activeOptions .= "<option value='0' ".$selFlagNo.">No</option>";
  $activeOptions .= "<option value='1' ".$selFlagYes.">Yes</option>";

  echo "<form action='playerProfileEdit.php?p=".$playerId."&v=".$viewCode."' method='POST' id='PP_PLAYERS'>";
  echo "  <input type='hidden' name='UPDATE_TYPE' value='PP_PLAYERS'>";
  echo "  <table border=2>";
  echo "    <tr><th>ID</th><td><input type='text' id='ID' name='ID' value='".$playerInfo['ID']."' DISABLED></td></tr>";
  echo "    <tr><th>First Name</th><td><input type='text' id='FIRST_NAME' name='FIRST_NAME' value='".$playerInfo['FIRST_NAME']."' onKeyUp=\"validate('PP_PLAYERS')\"></td></tr>";
  echo "    <tr><th>Last Name</th><td><input type='text' id='LAST_NAME' name='LAST_NAME' value='".$playerInfo['LAST_NAME']."' onKeyUp=\"validate('PP_PLAYERS')\"></td></tr>";
  echo "    <tr><th>Date of Birth</th><td><input type='date' id='DATE_OF_BIRTH' name='DATE_OF_BIRTH' value='".$playerInfo['DATE_OF_BIRTH']."'></td></tr>";
  echo "    <tr><th>Location</th><td><select id='LOCATION' name='LOCATION'>".$locationOptions."</select></td></tr>";
  echo "    <tr><th>Position (Primary)</th><td><select id='POSITION_PRI' name='POSITION_PRI'>".$posPriOptions."</select></td></tr>";
  echo "    <tr><th>Position (Secondary)</th><td><select id='POSITION_SEC' name='POSITION_SEC'>".$posSecOptions."</select></td></tr>";
  echo "    <tr><th>Phone Number</th><td><input type='text' id='PHONE_NUMBER' name='PHONE_NUMBER' value='".$playerInfo['PHONE_NUMBER']."'></td></tr>";
  echo "    <tr><th>E-Mail Address</th><td><input type='text' id='EMAIL_ADDRESS' name='EMAIL_ADDRESS' value='".$playerInfo['EMAIL_ADDRESS']."'></td></tr>";
  echo "    <tr><th>Height (Inches)</th><td><input type='text' id='HEIGHT_IN' name='HEIGHT_IN' value='".$playerInfo['HEIGHT_IN']."'></td></tr>";
  echo "    <tr><th>High School</th><td><select id='HIGH_SCHOOL' name='HIGH_SCHOOL'>".$highSchoolOptions."</select></td></tr>";  
  echo "    <tr><th>Graduation Year</th><td><select id='GRAD_CLASS' name='GRAD_CLASS'>".$gradYearOptions."</select></td></tr>";
  echo "    <tr><th>Club</td><td><select id='CLUB' name='CLUB'>".$clubOptions."</select></td></tr>";
  echo "    <tr><th>Dominate Foot</th><td><select id='DOMINATE_FOOT' name='DOMINATE_FOOT'>".$domFootOptions."</select></td></tr>";
  echo "    <tr><th>GPA (x.xx)</th><td><input type='text' id='GPA' name='GPA' value='".$playerInfo['GPA']."'></td></tr>";
  echo "    <tr><th>ACT Score</th><td><input type='text' id='ACT_SCORE' name='ACT_SCORE' value='".$playerInfo['ACT_SCORE']."'></td></tr>";
  echo "    <tr><th>SAT Score</th><td><input type='text' id='SAT_SCORE' name='SAT_SCORE' value='".$playerInfo['SAT_SCORE']."'></td></tr>";
  echo "    <tr><th>Class Rank (xxx / xxx)</th><td><input type='text' id='CLASS_RANK' name='CLASS_RANK' value='".$playerInfo['CLASS_RANK']."'></td></tr>";
  echo "    <tr><th>Headshot Image</th><td><input type='text' id='IMG_HEADSHOT' name='IMG_HEADSHOT' value='".$playerInfo['IMG_HEADSHOT']."'>&nbsp;&nbsp;images/headshots/[xxxx].jpg</td></tr>";
  echo "    <tr><th>Action Image</th><td><input type='text' id='IMG_ACTION' name='IMG_ACTION' value='".$playerInfo['IMG_ACTION']."'>&nbsp;&nbsp;images/action/[xxxx].jpg</td></tr>";
  echo "    <tr><th>Transcript PDF</th><td><input type='text' id='PDF_TRANSCRIPT' name='PDF_TRANSCRIPT' value='".$playerInfo['PDF_TRANSCRIPT']."'></td></tr>";
  echo "    <tr><th>Facebook</th><td><input type='text' id='SOC_FACEBOOK' name='SOC_FACEBOOK' value='".$playerInfo['SOC_FACEBOOK']."'></td></tr>";
  echo "    <tr><th>Twitter</th><td><input type='text' id='SOC_TWITTER' name='SOC_TWITTER' value='".$playerInfo['SOC_TWITTER']."'></td></tr>";
  echo "    <tr><th>Instagram</th><td><input type='text' id='SOC_INSTAGRAM' name='SOC_INSTAGRAM' value='".$playerInfo['SOC_INSTAGRAM']."'></td></tr>";
  echo "    <tr><th>Who Am I?</th><td><textarea id='TXT_WHOAMI' name='TXT_WHOAMI' style='width:500px; height:200px;'>".$playerInfo['TXT_WHOAMI']."</textarea></td></tr>";
  echo "    <tr><th>Goals</th><td><textarea id='TXT_GOALS' name='TXT_GOALS' style='width:500px; height:200px;'>".$playerInfo['TXT_GOALS']."</textarea></td></tr>";
  echo "    <tr><th>Active</th><td><select id='IS_ACTIVE' name='IS_ACTIVE'>".$activeOptions."</select></td></tr>";  
  echo "    <tr><td colspan=2><input type='submit' value='Update' id='PP_PLAYERS' name='PP_PLAYERS'></td></tr>";    
  echo "  </table>";
  echo "</form>";

  echo "<hr>";

  $sql = "";
  $sql .= "SELECT ";
  $sql .= "  A.ID AS REF_ID, ";
  $sql .= "  C.ID AS CONTACT_ID, ";
  $sql .= "  CONCAT(C.LAST_NAME, ', ', C.FIRST_NAME) AS REF_NAME, ";
  $sql .= "  B.ID AS REF_TYPE_ID, ";
  $sql .= "  B.REF_TYPE, ";
  $sql .= "  D.ID AS ORG_ID, ";
  $sql .= "  D.ORG_NAME, ";
  $sql .= "  C.EMAIL_ADDRESS, ";
  $sql .= "  C.PHONE_NUMBER, ";
  $sql .= "  A.SORT_ORDER, ";
  $sql .= "  A.IS_ACTIVE ";
  $sql .= "FROM PP_REFERENCES A ";
  $sql .= "INNER JOIN PP_REF_TYPES B ON B.ID = A.REF_TYPE_ID ";
  $sql .= "INNER JOIN PP_CONTACTS C ON C.ID = A.REF_CONTACT_ID ";
  $sql .= "INNER JOIN PP_ORGANIZATIONS D ON D.ID = C.ORG_ID ";
  $sql .= "WHERE ";
  $sql .= "A.PLAYER_ID = ".$playerInfo['ID']." ";
  $sql .= "ORDER BY ";
  $sql .= "A.SORT_ORDER ASC; ";
  $result = mysqli_query($cn, $sql);
  $refArray = mysqli_fetch_all($result, MYSQLI_ASSOC);

  echo "<form action='playerProfileEdit.php?p=".$playerId."&v=".$viewCode."' method='POST' id='PP_REFERENCES'>";
  echo "  <input type='hidden' name='UPDATE_TYPE' value='PP_REFERENCES'>";
  echo "  <table border=2>";
  echo "    <tr><th colspan=7>Update References</th></tr>";
  echo "    <tr><th>Type</th><th>Name</th><th>Organization</th><th>E-Mail Address</th><th>Phone Number</th><th>Sort Order</th><th>Active</th></tr>";

  foreach($refArray as $ref){
    $activeOptions = "";
    $selFlagNo = "";
    $selFlagYes = "";
    if($ref['IS_ACTIVE'] == 0){$selFlagNo = "SELECTED";} else {$selFlagYes = "SELECTED";}
    $activeOptions .= "<option value='0' ".$selFlagNo.">No</option>";
    $activeOptions .= "<option value='1' ".$selFlagYes.">Yes</option>";

    echo "  <tr>";
    echo "    <td>".$ref['REF_TYPE']."</td>";
    echo "    <td>".$ref['REF_NAME']."</td>";
    echo "    <td>".$ref['ORG_NAME']."</td>";
    echo "    <td>".$ref['EMAIL_ADDRESS']."</td>";
    echo "    <td>".$ref['PHONE_NUMBER']."</td>";
    echo "    <input type='hidden' id='REF_ID[]' name='REF_ID[]' value='".$ref['REF_ID']."'>";
    echo "    <td><input type='text' id='SORT_ORDER[]' name='SORT_ORDER[]' value='".$ref['SORT_ORDER']."' style='width:40px'></td>";
    echo "    <td><select id='IS_ACTIVE[]' name='IS_ACTIVE[]'>".$activeOptions."</select></td>";
    echo "  </tr>";
  }

  echo "    <tr><td colspan=7><input type='submit' value='Update' id='PP_REFERENCES' name='PP_REFERENCES'></td></tr>"; 
  echo "  </table>";
  echo "</form>";

  echo "<hr>";

  echo "<form action='playerProfileEdit.php?p=".$playerId."&v=".$viewCode."' method='POST' id='PP_REFERENCES_ADD'>";
  echo "  <input type='hidden' name='UPDATE_TYPE' value='PP_REFERENCES_ADD'>";
  echo "  <table border=2>";
  echo "    <tr><th colspan=6>Add References</th></tr>";
  echo "    <tr><th>Type</th><th>Name</th><th>Organization</th><th>E-Mail Address</th><th>Phone Number</th><th>Add</th></tr>";

  $sql = "";
  $sql .= "SELECT ";
  $sql .= "  A.ID AS CONTACT_ID, ";
  $sql .= "  CONCAT(A.LAST_NAME, ', ', A.FIRST_NAME) AS REF_NAME, ";
  $sql .= "  A.EMAIL_ADDRESS, ";
  $sql .= "  A.PHONE_NUMBER, ";
  $sql .= "  B.ORG_NAME, ";
  $sql .= "  C.REF_TYPE ";
  $sql .= "FROM PP_CONTACTS A ";
  $sql .= "INNER JOIN PP_ORGANIZATIONS B ON B.ID = A.ORG_ID ";
  $sql .= "INNER JOIN PP_REF_TYPES C ON C.ID = B.ORG_TYPE ";
  $sql .= "WHERE ";
  $sql .= "  A.ID NOT IN(SELECT DISTINCT X.REF_CONTACT_ID FROM PP_REFERENCES X WHERE X.PLAYER_ID = ".$playerId.") ";
  $sql .= "ORDER BY ";
  $sql .= "  CONCAT(A.LAST_NAME, ', ', A.FIRST_NAME) ASC; ";
  $result = mysqli_query($cn, $sql);
  $contArray = mysqli_fetch_all($result, MYSQLI_ASSOC);

  foreach($contArray as $cont){
    echo "  <tr>";
    echo "    <td>".$cont['REF_TYPE']."</td>";
    echo "    <td>".$cont['REF_NAME']."</td>";
    echo "    <td>".$cont['ORG_NAME']."</td>";
    echo "    <td>".$cont['EMAIL_ADDRESS']."</td>";
    echo "    <td>".$cont['PHONE_NUMBER']."</td>";
    echo "    <td><input type='checkbox' id='ADD_ID[]' name='ADD_ID[]' value='".$cont['CONTACT_ID']."'></input></td>";
    echo "  </tr>";
  }

  echo "    <tr><td colspan=7><input type='submit' value='Update' id='PP_REFERENCES_ADD' name='PP_REFERENCES_ADD'></td></tr>"; 
  echo "  </table>";
  echo "</form>";
?>

<script>
  function validate(formId){
    errStr = "Please correct the following validation errors:";
    if(formId == "PP_PLAYERS"){
      var pass = 1;

      chkFld = document.getElementById("FIRST_NAME");
      chkFld.style.background = "white";
      if(chkFld.value.length <= 0){pass = 0; chkFld.style.background = "yellow"; errStr = errStr + "\n  - First Name value is not valid.";}

      chkFld = document.getElementById("LAST_NAME");
      chkFld.style.background = "white";
      if(chkFld.value.length <= 0){pass = 0; chkFld.style.background = "yellow"; errStr = errStr + "\n  - Last Name value is not valid.";}      
    }
    if(pass == 0){return false;} else {return true;}
  }
</script>
