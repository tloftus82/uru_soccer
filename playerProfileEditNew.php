<?php
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);

  include('dbConnect/dbConnect.inc.php');

  // Simple authentication check
  $authenticated = 0;
  if(isset($_GET['v']) && $_GET['v'] == '36315'){
    $authenticated = 1;
  }

  if($authenticated == 0){
    echo "Access Denied";
    die;
  }

  // Handle form submissions
  $message = "";
  $messageType = "";

  if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(isset($_POST['action'])){
      
      // SAVE PLAYER INFO
      if($_POST['action'] == 'save_player'){
        $playerId = $_POST['player_id'];
        
        if($playerId == 'new'){
          // INSERT NEW PLAYER
          $sql = "INSERT INTO PP_PLAYERS (
            FIRST_NAME, LAST_NAME, GENDER, DATE_OF_BIRTH, PHONE_NUMBER, EMAIL_ADDRESS,
            LOCATION, POSITION_PRI, POSITION_SEC, HEIGHT_IN, HIGH_SCHOOL, GRAD_CLASS,
            CLUB, DOMINATE_FOOT, GPA, ACT_SCORE, SAT_SCORE, CLASS_RANK,
            IMG_HEADSHOT, IMG_ACTION, PDF_TRANSCRIPT,
            SOC_FACEBOOK, SOC_TWITTER, SOC_INSTAGRAM,
            TXT_WHOAMI, TXT_GOALS, IS_ACTIVE
          ) VALUES (
            '".$_POST['first_name']."',
            '".$_POST['last_name']."',
            '".$_POST['gender']."',
            '".$_POST['date_of_birth']."',
            '".$_POST['phone_number']."',
            '".$_POST['email_address']."',
            ".$_POST['location'].",
            ".$_POST['position_pri'].",
            ".($_POST['position_sec'] != '' ? $_POST['position_sec'] : 'NULL').",
            ".($_POST['height_in'] != '' ? $_POST['height_in'] : 'NULL').",
            ".($_POST['high_school'] != '' ? $_POST['high_school'] : 'NULL').",
            ".$_POST['grad_class'].",
            ".($_POST['club'] != '' ? $_POST['club'] : 'NULL').",
            '".$_POST['dominate_foot']."',
            ".($_POST['gpa'] != '' ? $_POST['gpa'] : 'NULL').",
            ".($_POST['act_score'] != '' ? $_POST['act_score'] : 'NULL').",
            ".($_POST['sat_score'] != '' ? $_POST['sat_score'] : 'NULL').",
            '".$_POST['class_rank']."',
            '".$_POST['img_headshot']."',
            '".$_POST['img_action']."',
            '".$_POST['pdf_transcript']."',
            '".$_POST['soc_facebook']."',
            '".$_POST['soc_twitter']."',
            '".$_POST['soc_instagram']."',
            '".$_POST['txt_whoami']."',
            '".$_POST['txt_goals']."',
            ".$_POST['is_active']."
          )";
          
          if(mysqli_query($cn, $sql)){
            $playerId = mysqli_insert_id($cn);
            $message = "Player created successfully! Player ID: ".$playerId;
            $messageType = "success";
          } else {
            $message = "Error creating player: ".mysqli_error($cn);
            $messageType = "error";
          }
          
        } else {
          // UPDATE EXISTING PLAYER
          $sql = "UPDATE PP_PLAYERS SET
            FIRST_NAME = '".$_POST['first_name']."',
            LAST_NAME = '".$_POST['last_name']."',
            GENDER = '".$_POST['gender']."',
            DATE_OF_BIRTH = '".$_POST['date_of_birth']."',
            PHONE_NUMBER = '".$_POST['phone_number']."',
            EMAIL_ADDRESS = '".$_POST['email_address']."',
            LOCATION = ".$_POST['location'].",
            POSITION_PRI = ".$_POST['position_pri'].",
            POSITION_SEC = ".($_POST['position_sec'] != '' ? $_POST['position_sec'] : 'NULL').",
            HEIGHT_IN = ".($_POST['height_in'] != '' ? $_POST['height_in'] : 'NULL').",
            HIGH_SCHOOL = ".($_POST['high_school'] != '' ? $_POST['high_school'] : 'NULL').",
            GRAD_CLASS = ".$_POST['grad_class'].",
            CLUB = ".($_POST['club'] != '' ? $_POST['club'] : 'NULL').",
            DOMINATE_FOOT = '".$_POST['dominate_foot']."',
            GPA = ".($_POST['gpa'] != '' ? $_POST['gpa'] : 'NULL').",
            ACT_SCORE = ".($_POST['act_score'] != '' ? $_POST['act_score'] : 'NULL').",
            SAT_SCORE = ".($_POST['sat_score'] != '' ? $_POST['sat_score'] : 'NULL').",
            CLASS_RANK = '".$_POST['class_rank']."',
            IMG_HEADSHOT = '".$_POST['img_headshot']."',
            IMG_ACTION = '".$_POST['img_action']."',
            PDF_TRANSCRIPT = '".$_POST['pdf_transcript']."',
            SOC_FACEBOOK = '".$_POST['soc_facebook']."',
            SOC_TWITTER = '".$_POST['soc_twitter']."',
            SOC_INSTAGRAM = '".$_POST['soc_instagram']."',
            TXT_WHOAMI = '".$_POST['txt_whoami']."',
            TXT_GOALS = '".$_POST['txt_goals']."',
            IS_ACTIVE = ".$_POST['is_active']."
          WHERE ID = ".$playerId;
          
          if(mysqli_query($cn, $sql)){
            $message = "Player updated successfully!";
            $messageType = "success";
          } else {
            $message = "Error updating player: ".mysqli_error($cn);
            $messageType = "error";
          }
        }
      }
      
      // DELETE PLAYER
      if($_POST['action'] == 'delete_player'){
        $playerId = $_POST['player_id'];
        $sql = "DELETE FROM PP_PLAYERS WHERE ID = ".$playerId;
        if(mysqli_query($cn, $sql)){
          $message = "Player deleted successfully!";
          $messageType = "success";
          $playerId = 'new';
        } else {
          $message = "Error deleting player: ".mysqli_error($cn);
          $messageType = "error";
        }
      }
      
      // SAVE ACCOLADE
      if($_POST['action'] == 'save_accolade'){
        $accoladeId = $_POST['accolade_id'];
        $playerId = $_POST['player_id'];
        
        if($accoladeId == 'new'){
          $sql = "INSERT INTO PP_ACCOLADES (PLAYER_ID, TIME_PERIOD_ID, ORG_ID, ACCOLADES_TEXT, SORT_ORDER)
                  VALUES (".$playerId.", ".$_POST['time_period_id'].", ".$_POST['org_id'].", '".$_POST['accolades_text']."', ".$_POST['sort_order'].")";
        } else {
          $sql = "UPDATE PP_ACCOLADES SET
                  TIME_PERIOD_ID = ".$_POST['time_period_id'].",
                  ORG_ID = ".$_POST['org_id'].",
                  ACCOLADES_TEXT = '".$_POST['accolades_text']."',
                  SORT_ORDER = ".$_POST['sort_order']."
                  WHERE ID = ".$accoladeId;
        }
        
        if(mysqli_query($cn, $sql)){
          $message = "Accolade saved successfully!";
          $messageType = "success";
        } else {
          $message = "Error saving accolade: ".mysqli_error($cn);
          $messageType = "error";
        }
      }
      
      // DELETE ACCOLADE
      if($_POST['action'] == 'delete_accolade'){
        $sql = "DELETE FROM PP_ACCOLADES WHERE ID = ".$_POST['accolade_id'];
        if(mysqli_query($cn, $sql)){
          $message = "Accolade deleted successfully!";
          $messageType = "success";
        }
      }
      
      // SAVE VIDEO
      if($_POST['action'] == 'save_video'){
        $videoId = $_POST['video_id'];
        $playerId = $_POST['player_id'];
        
        if($videoId == 'new'){
          $sql = "INSERT INTO PP_VIDEOS (PLAYER_ID, ORG_ID, TIME_PER_ID, VIDEO_TYPE_ID, VIDEO_LENGTH_M, IMG_THUMBNAIL, VIDEO_URL, SORT_ORDER)
                  VALUES (".$playerId.", ".$_POST['org_id'].", ".$_POST['time_per_id'].", ".$_POST['video_type_id'].", ".$_POST['video_length_m'].", '".$_POST['img_thumbnail']."', '".$_POST['video_url']."', ".$_POST['sort_order'].")";
        } else {
          $sql = "UPDATE PP_VIDEOS SET
                  ORG_ID = ".$_POST['org_id'].",
                  TIME_PER_ID = ".$_POST['time_per_id'].",
                  VIDEO_TYPE_ID = ".$_POST['video_type_id'].",
                  VIDEO_LENGTH_M = ".$_POST['video_length_m'].",
                  IMG_THUMBNAIL = '".$_POST['img_thumbnail']."',
                  VIDEO_URL = '".$_POST['video_url']."',
                  SORT_ORDER = ".$_POST['sort_order']."
                  WHERE ID = ".$videoId;
        }
        
        if(mysqli_query($cn, $sql)){
          $message = "Video saved successfully!";
          $messageType = "success";
        } else {
          $message = "Error saving video: ".mysqli_error($cn);
          $messageType = "error";
        }
      }
      
      // DELETE VIDEO
      if($_POST['action'] == 'delete_video'){
        $sql = "DELETE FROM PP_VIDEOS WHERE ID = ".$_POST['video_id'];
        if(mysqli_query($cn, $sql)){
          $message = "Video deleted successfully!";
          $messageType = "success";
        }
      }
      
      // SAVE REFERENCE
      if($_POST['action'] == 'save_reference'){
        $refId = $_POST['reference_id'];
        $playerId = $_POST['player_id'];
        
        if($refId == 'new'){
          $sql = "INSERT INTO PP_REFERENCES (PLAYER_ID, REF_TYPE_ID, REF_CONTACT_ID, IS_ACTIVE, SORT_ORDER)
                  VALUES (".$playerId.", ".$_POST['ref_type_id'].", ".$_POST['ref_contact_id'].", ".$_POST['is_active'].", ".$_POST['sort_order'].")";
        } else {
          $sql = "UPDATE PP_REFERENCES SET
                  REF_TYPE_ID = ".$_POST['ref_type_id'].",
                  REF_CONTACT_ID = ".$_POST['ref_contact_id'].",
                  IS_ACTIVE = ".$_POST['is_active'].",
                  SORT_ORDER = ".$_POST['sort_order']."
                  WHERE ID = ".$refId;
        }
        
        if(mysqli_query($cn, $sql)){
          $message = "Reference saved successfully!";
          $messageType = "success";
        } else {
          $message = "Error saving reference: ".mysqli_error($cn);
          $messageType = "error";
        }
      }
      
      // DELETE REFERENCE
      if($_POST['action'] == 'delete_reference'){
        $sql = "DELETE FROM PP_REFERENCES WHERE ID = ".$_POST['reference_id'];
        if(mysqli_query($cn, $sql)){
          $message = "Reference deleted successfully!";
          $messageType = "success";
        }
      }
    }
  }

  // Get player ID from URL or set to 'new'
  $playerId = isset($_GET['p']) ? $_GET['p'] : 'new';

  // Load player data if editing existing player
  $playerInfo = array();
  if($playerId != 'new'){
    $sql = "SELECT * FROM PP_PLAYERS WHERE ID = ".$playerId;
    $result = mysqli_query($cn, $sql);
    if(mysqli_num_rows($result) > 0){
      $playerInfo = mysqli_fetch_array($result, MYSQLI_ASSOC);
    } else {
      $message = "Player not found!";
      $messageType = "error";
      $playerId = 'new';
    }
  }

  // Get dropdown data
  $sql = "SELECT * FROM PP_LOCATIONS ORDER BY STATE, CITY";
  $locationsResult = mysqli_query($cn, $sql);
  $locations = mysqli_fetch_all($locationsResult, MYSQLI_ASSOC);

  $sql = "SELECT * FROM PP_POSITIONS ORDER BY POSITION";
  $positionsResult = mysqli_query($cn, $sql);
  $positions = mysqli_fetch_all($positionsResult, MYSQLI_ASSOC);

  $sql = "SELECT * FROM PP_ORGANIZATIONS WHERE ORG_TYPE = 1 ORDER BY ORG_NAME";
  $schoolsResult = mysqli_query($cn, $sql);
  $schools = mysqli_fetch_all($schoolsResult, MYSQLI_ASSOC);

  $sql = "SELECT * FROM PP_ORGANIZATIONS WHERE ORG_TYPE = 2 ORDER BY ORG_NAME";
  $clubsResult = mysqli_query($cn, $sql);
  $clubs = mysqli_fetch_all($clubsResult, MYSQLI_ASSOC);

  $sql = "SELECT * FROM PP_ORGANIZATIONS ORDER BY ORG_NAME";
  $orgsResult = mysqli_query($cn, $sql);
  $orgs = mysqli_fetch_all($orgsResult, MYSQLI_ASSOC);

  $sql = "SELECT * FROM PP_TIME_PERIODS WHERE IS_ACTIVE = 1 ORDER BY SORT_ORDER";
  $timePeriodsResult = mysqli_query($cn, $sql);
  $timePeriods = mysqli_fetch_all($timePeriodsResult, MYSQLI_ASSOC);

  $sql = "SELECT * FROM PP_VIDEO_TYPES ORDER BY VIDEO_TYPE_DESC";
  $videoTypesResult = mysqli_query($cn, $sql);
  $videoTypes = mysqli_fetch_all($videoTypesResult, MYSQLI_ASSOC);

  $sql = "SELECT * FROM PP_REF_TYPES ORDER BY REF_TYPE";
  $refTypesResult = mysqli_query($cn, $sql);
  $refTypes = mysqli_fetch_all($refTypesResult, MYSQLI_ASSOC);

  $sql = "SELECT * FROM PP_CONTACTS ORDER BY LAST_NAME, FIRST_NAME";
  $contactsResult = mysqli_query($cn, $sql);
  $contacts = mysqli_fetch_all($contactsResult, MYSQLI_ASSOC);

  // Get all players for dropdown
  $sql = "SELECT ID, FIRST_NAME, LAST_NAME, GRAD_CLASS, IS_ACTIVE FROM PP_PLAYERS ORDER BY LAST_NAME, FIRST_NAME";
  $playersResult = mysqli_query($cn, $sql);
  $allPlayers = mysqli_fetch_all($playersResult, MYSQLI_ASSOC);

  // Get player's accolades
  $accolades = array();
  if($playerId != 'new'){
    $sql = "SELECT A.*, B.TIME_PER_DESC, C.ORG_NAME 
            FROM PP_ACCOLADES A
            LEFT JOIN PP_TIME_PERIODS B ON B.ID = A.TIME_PERIOD_ID
            LEFT JOIN PP_ORGANIZATIONS C ON C.ID = A.ORG_ID
            WHERE A.PLAYER_ID = ".$playerId."
            ORDER BY A.SORT_ORDER";
    $accoladesResult = mysqli_query($cn, $sql);
    $accolades = mysqli_fetch_all($accoladesResult, MYSQLI_ASSOC);
  }

  // Get player's videos
  $videos = array();
  if($playerId != 'new'){
    $sql = "SELECT A.*, B.ORG_NAME, C.TIME_PER_DESC, D.VIDEO_TYPE_DESC
            FROM PP_VIDEOS A
            LEFT JOIN PP_ORGANIZATIONS B ON B.ID = A.ORG_ID
            LEFT JOIN PP_TIME_PERIODS C ON C.ID = A.TIME_PER_ID
            LEFT JOIN PP_VIDEO_TYPES D ON D.ID = A.VIDEO_TYPE_ID
            WHERE A.PLAYER_ID = ".$playerId."
            ORDER BY A.SORT_ORDER";
    $videosResult = mysqli_query($cn, $sql);
    $videos = mysqli_fetch_all($videosResult, MYSQLI_ASSOC);
  }

  // Get player's references
  $references = array();
  if($playerId != 'new'){
    $sql = "SELECT A.*, B.REF_TYPE, C.FIRST_NAME, C.LAST_NAME, C.EMAIL_ADDRESS, C.PHONE_NUMBER, D.ORG_NAME
            FROM PP_REFERENCES A
            LEFT JOIN PP_REF_TYPES B ON B.ID = A.REF_TYPE_ID
            LEFT JOIN PP_CONTACTS C ON C.ID = A.REF_CONTACT_ID
            LEFT JOIN PP_ORGANIZATIONS D ON D.ID = C.ORG_ID
            WHERE A.PLAYER_ID = ".$playerId."
            ORDER BY A.SORT_ORDER";
    $referencesResult = mysqli_query($cn, $sql);
    $references = mysqli_fetch_all($referencesResult, MYSQLI_ASSOC);
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Player Profile Admin</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
    .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h1 { color: #333; margin-bottom: 10px; }
    h2 { color: #555; margin: 30px 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #007bff; }
    h3 { color: #666; margin: 20px 0 10px 0; }
    .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #ddd; }
    .player-selector { margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 5px; }
    .player-selector select { padding: 8px; font-size: 14px; width: 100%; max-width: 400px; }
    .message { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
    .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .form-group { margin-bottom: 20px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
    label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
    input[type="text"], input[type="email"], input[type="date"], input[type="number"], select, textarea {
      width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;
    }
    textarea { min-height: 100px; resize: vertical; font-family: Arial, sans-serif; }
    .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; transition: all 0.3s; }
    .btn-primary { background: #007bff; color: white; }
    .btn-primary:hover { background: #0056b3; }
    .btn-success { background: #28a745; color: white; }
    .btn-success:hover { background: #218838; }
    .btn-danger { background: #dc3545; color: white; }
    .btn-danger:hover { background: #c82333; }
    .btn-secondary { background: #6c757d; color: white; }
    .btn-secondary:hover { background: #5a6268; }
    .btn-group { display: flex; gap: 10px; margin-top: 20px; }
    .table-container { margin-top: 20px; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
    table th { background: #f8f9fa; font-weight: bold; color: #333; }
    table tr:hover { background: #f8f9fa; }
    .action-btns { display: flex; gap: 5px; }
    .action-btns button { padding: 5px 10px; font-size: 12px; }
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
    .modal-content { background-color: #fefefe; margin: 5% auto; padding: 30px; border: 1px solid #888; border-radius: 8px; width: 90%; max-width: 600px; }
    .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
    .close:hover { color: black; }
    .required { color: red; }
    .view-profile { margin-left: 10px; font-size: 14px; }
  </style>
</head>
<body>

<div class="container">
  <div class="header">
    <div>
      <h1>Player Profile Admin</h1>
      <p style="color: #777; margin-top: 5px;">
        <?php 
          if($playerId == 'new'){
            echo "Create New Player Profile";
          } else {
            echo "Editing: ".$playerInfo['FIRST_NAME']." ".$playerInfo['LAST_NAME'];
            echo " <a href='playerProfile.php?p=".$playerId."&v=YOURVIEWCODE' target='_blank' class='view-profile'>[View Profile]</a>";
          }
        ?>
      </p>
    </div>
    <div>
      <a href="playerProfileEditNew.php?v=36315" class="btn btn-success">+ New Player</a>
    </div>
  </div>

  <?php if($message != ''): ?>
    <div class="message <?php echo $messageType; ?>">
      <?php echo $message; ?>
    </div>
  <?php endif; ?>

  <div class="player-selector">
    <label>Select Player to Edit:</label>
    <select onchange="if(this.value != '') window.location.href='playerProfileEditNew.php?v=36315&p=' + this.value;">
      <option value="">-- Select Player --</option>
      <?php foreach($allPlayers as $p): ?>
        <option value="<?php echo $p['ID']; ?>" <?php if($playerId == $p['ID']) echo 'selected'; ?>>
          <?php echo $p['LAST_NAME'].", ".$p['FIRST_NAME']." (Class of ".$p['GRAD_CLASS'].")"; ?>
          <?php if($p['IS_ACTIVE'] == 0) echo " - INACTIVE"; ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <!-- PLAYER BASIC INFO FORM -->
  <form method="POST" id="playerForm">
    <input type="hidden" name="action" value="save_player">
    <input type="hidden" name="player_id" value="<?php echo $playerId; ?>">
    
    <h2>Basic Information</h2>
    
    <div class="form-row">
      <div class="form-group">
        <label>First Name <span class="required">*</span></label>
        <input type="text" name="first_name" value="<?php echo $playerInfo['FIRST_NAME'] ?? ''; ?>" required>
      </div>
      <div class="form-group">
        <label>Last Name <span class="required">*</span></label>
        <input type="text" name="last_name" value="<?php echo $playerInfo['LAST_NAME'] ?? ''; ?>" required>
      </div>
    </div>

    <div class="form-row-3">
      <div class="form-group">
        <label>Gender</label>
        <select name="gender">
          <option value="">--</option>
          <option value="M" <?php if(($playerInfo['GENDER'] ?? '') == 'M') echo 'selected'; ?>>Male</option>
          <option value="F" <?php if(($playerInfo['GENDER'] ?? '') == 'F') echo 'selected'; ?>>Female</option>
        </select>
      </div>
      <div class="form-group">
        <label>Date of Birth <span class="required">*</span></label>
        <input type="date" name="date_of_birth" value="<?php echo $playerInfo['DATE_OF_BIRTH'] ?? ''; ?>" required>
      </div>
      <div class="form-group">
        <label>Graduation Class <span class="required">*</span></label>
        <input type="number" name="grad_class" value="<?php echo $playerInfo['GRAD_CLASS'] ?? ''; ?>" required>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Phone Number</label>
        <input type="text" name="phone_number" value="<?php echo $playerInfo['PHONE_NUMBER'] ?? ''; ?>">
      </div>
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email_address" value="<?php echo $playerInfo['EMAIL_ADDRESS'] ?? ''; ?>">
      </div>
    </div>

    <h2>Position & Physical Info</h2>

    <div class="form-row-3">
      <div class="form-group">
        <label>Primary Position <span class="required">*</span></label>
        <select name="position_pri" required>
          <option value="">-- Select --</option>
          <?php foreach($positions as $pos): ?>
            <option value="<?php echo $pos['ID']; ?>" <?php if(($playerInfo['POSITION_PRI'] ?? '') == $pos['ID']) echo 'selected'; ?>>
              <?php echo $pos['POSITION']; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Secondary Position</label>
        <select name="position_sec">
          <option value="">-- None --</option>
          <?php foreach($positions as $pos): ?>
            <option value="<?php echo $pos['ID']; ?>" <?php if(($playerInfo['POSITION_SEC'] ?? '') == $pos['ID']) echo 'selected'; ?>>
              <?php echo $pos['POSITION']; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Height (inches)</label>
        <input type="number" name="height_in" value="<?php echo $playerInfo['HEIGHT_IN'] ?? ''; ?>">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Dominant Foot</label>
        <select name="dominate_foot">
          <option value="">--</option>
          <option value="Right" <?php if(($playerInfo['DOMINATE_FOOT'] ?? '') == 'Right') echo 'selected'; ?>>Right</option>
          <option value="Left" <?php if(($playerInfo['DOMINATE_FOOT'] ?? '') == 'Left') echo 'selected'; ?>>Left</option>
          <option value="Both" <?php if(($playerInfo['DOMINATE_FOOT'] ?? '') == 'Both') echo 'selected'; ?>>Both</option>
        </select>
      </div>
      <div class="form-group">
        <label>Location <span class="required">*</span></label>
        <select name="location" required>
          <option value="">-- Select --</option>
          <?php foreach($locations as $loc): ?>
            <option value="<?php echo $loc['ID']; ?>" <?php if(($playerInfo['LOCATION'] ?? '') == $loc['ID']) echo 'selected'; ?>>
              <?php echo $loc['CITY'].", ".$loc['STATE']; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <h2>School & Club</h2>

    <div class="form-row">
      <div class="form-group">
        <label>High School</label>
        <select name="high_school">
          <option value="">-- None --</option>
          <?php foreach($schools as $school): ?>
            <option value="<?php echo $school['ID']; ?>" <?php if(($playerInfo['HIGH_SCHOOL'] ?? '') == $school['ID']) echo 'selected'; ?>>
              <?php echo $school['ORG_NAME']; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Club Team</label>
        <select name="club">
          <option value="">-- None --</option>
          <?php foreach($clubs as $club): ?>
            <option value="<?php echo $club['ID']; ?>" <?php if(($playerInfo['CLUB'] ?? '') == $club['ID']) echo 'selected'; ?>>
              <?php echo $club['ORG_NAME']; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <h2>Academic Info</h2>

    <div class="form-row-3">
      <div class="form-group">
        <label>GPA</label>
        <input type="number" step="0.01" name="gpa" value="<?php echo $playerInfo['GPA'] ?? ''; ?>">
      </div>
      <div class="form-group">
        <label>ACT Score</label>
        <input type="number" name="act_score" value="<?php echo $playerInfo['ACT_SCORE'] ?? ''; ?>">
      </div>
      <div class="form-group">
        <label>SAT Score</label>
        <input type="number" name="sat_score" value="<?php echo $playerInfo['SAT_SCORE'] ?? ''; ?>">
      </div>
    </div>

    <div class="form-group">
      <label>Class Rank (format: 5/200)</label>
      <input type="text" name="class_rank" value="<?php echo $playerInfo['CLASS_RANK'] ?? ''; ?>">
    </div>

    <h2>Media & Documents</h2>

    <div class="form-row">
      <div class="form-group">
        <label>Headshot Image Path</label>
        <input type="text" name="img_headshot" value="<?php echo $playerInfo['IMG_HEADSHOT'] ?? ''; ?>" placeholder="images/headshots/filename.jpg">
      </div>
      <div class="form-group">
        <label>Action Image Path</label>
        <input type="text" name="img_action" value="<?php echo $playerInfo['IMG_ACTION'] ?? ''; ?>" placeholder="images/action/filename.jpg">
      </div>
    </div>

    <div class="form-group">
      <label>Transcript PDF Path</label>
      <input type="text" name="pdf_transcript" value="<?php echo $playerInfo['PDF_TRANSCRIPT'] ?? ''; ?>" placeholder="documents/transcript.pdf">
    </div>

    <h2>Social Media</h2>

    <div class="form-row-3">
      <div class="form-group">
        <label>Facebook Username</label>
        <input type="text" name="soc_facebook" value="<?php echo $playerInfo['SOC_FACEBOOK'] ?? ''; ?>" placeholder="username">
      </div>
      <div class="form-group">
        <label>Twitter Handle</label>
        <input type="text" name="soc_twitter" value="<?php echo $playerInfo['SOC_TWITTER'] ?? ''; ?>" placeholder="@handle">
      </div>
      <div class="form-group">
        <label>Instagram Username</label>
        <input type="text" name="soc_instagram" value="<?php echo $playerInfo['SOC_INSTAGRAM'] ?? ''; ?>" placeholder="@username">
      </div>
    </div>

    <h2>About Me Text</h2>

    <div class="form-group">
      <label>Who Am I</label>
      <textarea name="txt_whoami"><?php echo $playerInfo['TXT_WHOAMI'] ?? ''; ?></textarea>
    </div>

    <div class="form-group">
      <label>Goals & Aspirations</label>
      <textarea name="txt_goals"><?php echo $playerInfo['TXT_GOALS'] ?? ''; ?></textarea>
    </div>

    <h2>Status</h2>

    <div class="form-group">
      <label>Profile Status</label>
      <select name="is_active">
        <option value="1" <?php if(($playerInfo['IS_ACTIVE'] ?? 1) == 1) echo 'selected'; ?>>Active</option>
        <option value="0" <?php if(($playerInfo['IS_ACTIVE'] ?? 1) == 0) echo 'selected'; ?>>Inactive</option>
      </select>
    </div>

    <div class="btn-group">
      <button type="submit" class="btn btn-primary">Save Player Info</button>
      <?php if($playerId != 'new'): ?>
        <button type="button" class="btn btn-danger" onclick="if(confirm('Are you sure you want to delete this player?')) { document.getElementById('deleteForm').submit(); }">Delete Player</button>
      <?php endif; ?>
    </div>
  </form>

  <?php if($playerId != 'new'): ?>
    <!-- DELETE FORM -->
    <form method="POST" id="deleteForm" style="display:none;">
      <input type="hidden" name="action" value="delete_player">
      <input type="hidden" name="player_id" value="<?php echo $playerId; ?>">
    </form>

    <!-- ACCOLADES SECTION -->
    <h2>Accolades & Awards</h2>
    <button type="button" class="btn btn-success" onclick="openAccoladeModal('new')">+ Add Accolade</button>
    
    <?php if(count($accolades) > 0): ?>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Time Period</th>
              <th>Organization</th>
              <th>Accolades Text</th>
              <th>Sort Order</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($accolades as $acc): ?>
              <tr>
                <td><?php echo $acc['TIME_PER_DESC']; ?></td>
                <td><?php echo $acc['ORG_NAME']; ?></td>
                <td><?php echo substr($acc['ACCOLADES_TEXT'], 0, 100); ?>...</td>
                <td><?php echo $acc['SORT_ORDER']; ?></td>
                <td class="action-btns">
                  <button class="btn btn-secondary" onclick="openAccoladeModal(<?php echo $acc['ID']; ?>)">Edit</button>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="delete_accolade">
                    <input type="hidden" name="accolade_id" value="<?php echo $acc['ID']; ?>">
                    <input type="hidden" name="player_id" value="<?php echo $playerId; ?>">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this accolade?')">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <!-- VIDEOS SECTION -->
    <h2>Videos</h2>
    <button type="button" class="btn btn-success" onclick="openVideoModal('new')">+ Add Video</button>
    
    <?php if(count($videos) > 0): ?>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Time Period</th>
              <th>Organization</th>
              <th>Type</th>
              <th>Length (min)</th>
              <th>URL</th>
              <th>Sort Order</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($videos as $vid): ?>
              <tr>
                <td><?php echo $vid['TIME_PER_DESC']; ?></td>
                <td><?php echo $vid['ORG_NAME']; ?></td>
                <td><?php echo $vid['VIDEO_TYPE_DESC']; ?></td>
                <td><?php echo $vid['VIDEO_LENGTH_M']; ?></td>
                <td><a href="<?php echo $vid['VIDEO_URL']; ?>" target="_blank">View</a></td>
                <td><?php echo $vid['SORT_ORDER']; ?></td>
                <td class="action-btns">
                  <button class="btn btn-secondary" onclick="openVideoModal(<?php echo $vid['ID']; ?>)">Edit</button>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="delete_video">
                    <input type="hidden" name="video_id" value="<?php echo $vid['ID']; ?>">
                    <input type="hidden" name="player_id" value="<?php echo $playerId; ?>">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this video?')">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <!-- REFERENCES SECTION -->
    <h2>References</h2>
    <button type="button" class="btn btn-success" onclick="openReferenceModal('new')">+ Add Reference</button>
    
    <?php if(count($references) > 0): ?>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Type</th>
              <th>Contact</th>
              <th>Organization</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Status</th>
              <th>Sort Order</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($references as $ref): ?>
              <tr>
                <td><?php echo $ref['REF_TYPE']; ?></td>
                <td><?php echo $ref['FIRST_NAME'].' '.$ref['LAST_NAME']; ?></td>
                <td><?php echo $ref['ORG_NAME']; ?></td>
                <td><?php echo $ref['EMAIL_ADDRESS']; ?></td>
                <td><?php echo $ref['PHONE_NUMBER']; ?></td>
                <td><?php echo $ref['IS_ACTIVE'] == 1 ? 'Active' : 'Inactive'; ?></td>
                <td><?php echo $ref['SORT_ORDER']; ?></td>
                <td class="action-btns">
                  <button class="btn btn-secondary" onclick="openReferenceModal(<?php echo $ref['ID']; ?>)">Edit</button>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="delete_reference">
                    <input type="hidden" name="reference_id" value="<?php echo $ref['ID']; ?>">
                    <input type="hidden" name="player_id" value="<?php echo $playerId; ?>">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this reference?')">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

  <?php endif; ?>
</div>

<!-- ACCOLADE MODAL -->
<div id="accoladeModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeAccoladeModal()">&times;</span>
    <h2 id="accoladeModalTitle">Add Accolade</h2>
    <form method="POST">
      <input type="hidden" name="action" value="save_accolade">
      <input type="hidden" name="player_id" value="<?php echo $playerId; ?>">
      <input type="hidden" name="accolade_id" id="accolade_id" value="new">
      
      <div class="form-group">
        <label>Time Period <span class="required">*</span></label>
        <select name="time_period_id" id="acc_time_period_id" required>
          <option value="">-- Select --</option>
          <?php foreach($timePeriods as $tp): ?>
            <option value="<?php echo $tp['ID']; ?>"><?php echo $tp['TIME_PER_DESC']; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="form-group">
        <label>Organization <span class="required">*</span></label>
        <select name="org_id" id="acc_org_id" required>
          <option value="">-- Select --</option>
          <?php foreach($orgs as $org): ?>
            <option value="<?php echo $org['ID']; ?>"><?php echo $org['ORG_NAME']; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="form-group">
        <label>Accolades Text <span class="required">*</span></label>
        <textarea name="accolades_text" id="acc_text" required></textarea>
      </div>
      
      <div class="form-group">
        <label>Sort Order <span class="required">*</span></label>
        <input type="number" name="sort_order" id="acc_sort_order" value="1" required>
      </div>
      
      <div class="btn-group">
        <button type="submit" class="btn btn-primary">Save Accolade</button>
        <button type="button" class="btn btn-secondary" onclick="closeAccoladeModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- VIDEO MODAL -->
<div id="videoModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeVideoModal()">&times;</span>
    <h2 id="videoModalTitle">Add Video</h2>
    <form method="POST">
      <input type="hidden" name="action" value="save_video">
      <input type="hidden" name="player_id" value="<?php echo $playerId; ?>">
      <input type="hidden" name="video_id" id="video_id" value="new">
      
      <div class="form-group">
        <label>Organization <span class="required">*</span></label>
        <select name="org_id" id="vid_org_id" required>
          <option value="">-- Select --</option>
          <?php foreach($orgs as $org): ?>
            <option value="<?php echo $org['ID']; ?>"><?php echo $org['ORG_NAME']; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="form-group">
        <label>Time Period <span class="required">*</span></label>
        <select name="time_per_id" id="vid_time_per_id" required>
          <option value="">-- Select --</option>
          <?php foreach($timePeriods as $tp): ?>
            <option value="<?php echo $tp['ID']; ?>"><?php echo $tp['TIME_PER_DESC']; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="form-group">
        <label>Video Type <span class="required">*</span></label>
        <select name="video_type_id" id="vid_type_id" required>
          <option value="">-- Select --</option>
          <?php foreach($videoTypes as $vt): ?>
            <option value="<?php echo $vt['ID']; ?>"><?php echo $vt['VIDEO_TYPE_DESC']; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="form-group">
        <label>Video URL <span class="required">*</span></label>
        <input type="text" name="video_url" id="vid_url" required>
      </div>
      
      <div class="form-group">
        <label>Video Length (minutes) <span class="required">*</span></label>
        <input type="number" name="video_length_m" id="vid_length" required>
      </div>
      
      <div class="form-group">
        <label>Thumbnail Image Path</label>
        <input type="text" name="img_thumbnail" id="vid_thumbnail">
      </div>
      
      <div class="form-group">
        <label>Sort Order <span class="required">*</span></label>
        <input type="number" name="sort_order" id="vid_sort_order" value="1" required>
      </div>
      
      <div class="btn-group">
        <button type="submit" class="btn btn-primary">Save Video</button>
        <button type="button" class="btn btn-secondary" onclick="closeVideoModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- REFERENCE MODAL -->
<div id="referenceModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeReferenceModal()">&times;</span>
    <h2 id="referenceModalTitle">Add Reference</h2>
    <form method="POST">
      <input type="hidden" name="action" value="save_reference">
      <input type="hidden" name="player_id" value="<?php echo $playerId; ?>">
      <input type="hidden" name="reference_id" id="reference_id" value="new">
      
      <div class="form-group">
        <label>Reference Type <span class="required">*</span></label>
        <select name="ref_type_id" id="ref_type_id" required>
          <option value="">-- Select --</option>
          <?php foreach($refTypes as $rt): ?>
            <option value="<?php echo $rt['ID']; ?>"><?php echo $rt['REF_TYPE']; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="form-group">
        <label>Contact <span class="required">*</span></label>
        <select name="ref_contact_id" id="ref_contact_id" required>
          <option value="">-- Select --</option>
          <?php foreach($contacts as $con): ?>
            <option value="<?php echo $con['ID']; ?>"><?php echo $con['LAST_NAME'].", ".$con['FIRST_NAME']; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="form-group">
        <label>Status <span class="required">*</span></label>
        <select name="is_active" id="ref_is_active" required>
          <option value="1">Active</option>
          <option value="0">Inactive</option>
        </select>
      </div>
      
      <div class="form-group">
        <label>Sort Order <span class="required">*</span></label>
        <input type="number" name="sort_order" id="ref_sort_order" value="1" required>
      </div>
      
      <div class="btn-group">
        <button type="submit" class="btn btn-primary">Save Reference</button>
        <button type="button" class="btn btn-secondary" onclick="closeReferenceModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
  // Accolade Modal Functions
  function openAccoladeModal(id) {
    <?php if($playerId == 'new'): ?>
      alert('Please save the player first before adding accolades.');
      return;
    <?php endif; ?>
    
    document.getElementById('accoladeModal').style.display = 'block';
    document.getElementById('accolade_id').value = id;
    
    if(id === 'new') {
      document.getElementById('accoladeModalTitle').textContent = 'Add Accolade';
      document.getElementById('acc_time_period_id').value = '';
      document.getElementById('acc_org_id').value = '';
      document.getElementById('acc_text').value = '';
      document.getElementById('acc_sort_order').value = '1';
    } else {
      document.getElementById('accoladeModalTitle').textContent = 'Edit Accolade';
      // Load data via AJAX or PHP array
      <?php foreach($accolades as $acc): ?>
        if(id == <?php echo $acc['ID']; ?>) {
          document.getElementById('acc_time_period_id').value = '<?php echo $acc['TIME_PERIOD_ID']; ?>';
          document.getElementById('acc_org_id').value = '<?php echo $acc['ORG_ID']; ?>';
          document.getElementById('acc_text').value = <?php echo json_encode($acc['ACCOLADES_TEXT']); ?>;
          document.getElementById('acc_sort_order').value = '<?php echo $acc['SORT_ORDER']; ?>';
        }
      <?php endforeach; ?>
    }
  }
  
  function closeAccoladeModal() {
    document.getElementById('accoladeModal').style.display = 'none';
  }
  
  // Video Modal Functions
  function openVideoModal(id) {
    <?php if($playerId == 'new'): ?>
      alert('Please save the player first before adding videos.');
      return;
    <?php endif; ?>
    
    document.getElementById('videoModal').style.display = 'block';
    document.getElementById('video_id').value = id;
    
    if(id === 'new') {
      document.getElementById('videoModalTitle').textContent = 'Add Video';
      document.getElementById('vid_org_id').value = '';
      document.getElementById('vid_time_per_id').value = '';
      document.getElementById('vid_type_id').value = '';
      document.getElementById('vid_url').value = '';
      document.getElementById('vid_length').value = '';
      document.getElementById('vid_thumbnail').value = '';
      document.getElementById('vid_sort_order').value = '1';
    } else {
      document.getElementById('videoModalTitle').textContent = 'Edit Video';
      <?php foreach($videos as $vid): ?>
        if(id == <?php echo $vid['ID']; ?>) {
          document.getElementById('vid_org_id').value = '<?php echo $vid['ORG_ID']; ?>';
          document.getElementById('vid_time_per_id').value = '<?php echo $vid['TIME_PER_ID']; ?>';
          document.getElementById('vid_type_id').value = '<?php echo $vid['VIDEO_TYPE_ID']; ?>';
          document.getElementById('vid_url').value = <?php echo json_encode($vid['VIDEO_URL']); ?>;
          document.getElementById('vid_length').value = '<?php echo $vid['VIDEO_LENGTH_M']; ?>';
          document.getElementById('vid_thumbnail').value = <?php echo json_encode($vid['IMG_THUMBNAIL']); ?>;
          document.getElementById('vid_sort_order').value = '<?php echo $vid['SORT_ORDER']; ?>';
        }
      <?php endforeach; ?>
    }
  }
  
  function closeVideoModal() {
    document.getElementById('videoModal').style.display = 'none';
  }
  
  // Reference Modal Functions
  function openReferenceModal(id) {
    <?php if($playerId == 'new'): ?>
      alert('Please save the player first before adding references.');
      return;
    <?php endif; ?>
    
    document.getElementById('referenceModal').style.display = 'block';
    document.getElementById('reference_id').value = id;
    
    if(id === 'new') {
      document.getElementById('referenceModalTitle').textContent = 'Add Reference';
      document.getElementById('ref_type_id').value = '';
      document.getElementById('ref_contact_id').value = '';
      document.getElementById('ref_is_active').value = '1';
      document.getElementById('ref_sort_order').value = '1';
    } else {
      document.getElementById('referenceModalTitle').textContent = 'Edit Reference';
      <?php foreach($references as $ref): ?>
        if(id == <?php echo $ref['ID']; ?>) {
          document.getElementById('ref_type_id').value = '<?php echo $ref['REF_TYPE_ID']; ?>';
          document.getElementById('ref_contact_id').value = '<?php echo $ref['REF_CONTACT_ID']; ?>';
          document.getElementById('ref_is_active').value = '<?php echo $ref['IS_ACTIVE']; ?>';
          document.getElementById('ref_sort_order').value = '<?php echo $ref['SORT_ORDER']; ?>';
        }
      <?php endforeach; ?>
    }
  }
  
  function closeReferenceModal() {
    document.getElementById('referenceModal').style.display = 'none';
  }
  
  // Close modal when clicking outside
  window.onclick = function(event) {
    if(event.target.className === 'modal') {
      event.target.style.display = 'none';
    }
  }
</script>

</body>
</html>
