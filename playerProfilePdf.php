<?php
// Prevent search engines from indexing or following links on PDF responses
header('X-Robots-Tag: noindex, nofollow');

require('libraries/fpdf/fpdf.php');
include 'libraries/qr/qrlib.php';

include('dbConnect/dbConnect.inc.php');

  //get user's IP address
  $pageName = $_SERVER['REQUEST_URI'];
  $userIp = $_SERVER['REMOTE_ADDR'];
  $hostName = gethostbyaddr($userIp);
  $ipLocation = ''; $ipOrg = '';
  if (function_exists('curl_init')) {
    $ch = curl_init("https://ipapi.co/{$userIp}/json/");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3, CURLOPT_USERAGENT => 'URUSoccer/1.0']);
    $ipRaw = curl_exec($ch); curl_close($ch);
    $ipDetails = $ipRaw ? @json_decode($ipRaw) : null;
    if ($ipDetails && !empty($ipDetails->city)) {
      $ipLocation = $ipDetails->city.', '.$ipDetails->region.', '.$ipDetails->country_name;
      $ipOrg      = $ipDetails->org ?? '';
    }
  }

  //check to see if authorized viewer
  $viewCode = "NULL";
  $viewerId = "NULL";
  $playerId = "NULL";
  $authenticated = 0;

  $viewAuth = 0;
  if(isset($_GET['v']) == 1){
    $viewCode = $_GET['v'];
    $sql = "SELECT A.ID AS VIEWER_ID FROM PP_ALLOWED_VIEWERS A WHERE A.VIEW_CODE = '".$viewCode."';";
    $result = mysqli_query($cn, $sql);
    $viewerInfo = mysqli_fetch_array($result, MYSQLI_ASSOC);
    if(mysqli_num_rows($result) == 1){$viewAuth = 1; $viewerId = $viewerInfo['VIEWER_ID'];}
  }

  $playerAuth = 0;
  if(isset($_GET['p']) == 1){
    $playerId = $_GET['p'];
    $sql = "SELECT 'X' AS AUTH FROM PP_PLAYERS A WHERE A.ID = ".$playerId.";";
    $result = mysqli_query($cn, $sql);
    if(mysqli_num_rows($result) == 1){$playerAuth = 1;}
  }

  if($viewAuth == 1 and $playerAuth == 1){$authenticated = 1;}

  //insert view record into tracking table
  $sql = "";
  $sql .= "INSERT INTO PP_VIEW_LOG ( ";
  $sql .= "  PLAYER_ID, VIEWER_ID, VIEW_CODE, VIEW_DATE_TIME, AUTHENTICATED, IP_ADDRESS, HOST_NAME, IP_LOCATION, IP_ORG ";
  $sql .= ") VALUES ( ";
  $sql .= "  ".$playerId.", ".$viewerId.", '".$viewCode."', NOW(), ".$authenticated.", '".$userIp."', '".$hostName."', '".$ipLocation."', '".$ipOrg."' ";
  $sql .= ");";
  $result = mysqli_query($cn, $sql);

  //insert view record into tracking table
  $sql = "";
  $sql .= "INSERT INTO SITE_VIEW_LOG ( ";
  $sql .= "  PAGE, VIEW_DATE_TIME, IP_ADDRESS, HOST_NAME, IP_LOCATION, IP_ORG ";
  $sql .= ") VALUES ( ";
  $sql .= "  '".$pageName."', NOW(), '".$userIp."', '".$hostName."', '".$ipLocation."', '".$ipOrg."' ";
  $sql .= ");";
  $result = mysqli_query($cn, $sql);

  //kill page load if viewer or player isn't validated
  if($authenticated == 0){echo "Improperly formatted request."; die;}

  //get player information from database
  $sql = "";
  $sql .= "SELECT ";
  $sql .= "  A.FIRST_NAME, ";
  $sql .= "  A.LAST_NAME, ";
  $sql .= "  A.GENDER, ";
  $sql .= "  A.GRAD_CLASS, ";
  $sql .= "  IFNULL(A.GPA,'--') AS GPA, ";
  $sql .= "  IFNULL(A.ACT_SCORE,'--') AS ACT_SCORE, ";
  $sql .= "  IFNULL(A.SAT_SCORE,'--') AS SAT_SCORE, ";
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
  $sql .= "  A.TXT_GOALS ";
  $sql .= "FROM PP_PLAYERS A ";
  $sql .= "LEFT OUTER JOIN PP_POSITIONS B ON B.ID = A.POSITION_PRI ";
  $sql .= "LEFT OUTER JOIN PP_POSITIONS C ON C.ID = A.POSITION_SEC ";
  $sql .= "LEFT OUTER JOIN PP_LOCATIONS D ON D.ID = A.LOCATION ";
  $sql .= "LEFT OUTER JOIN PP_ORGANIZATIONS E ON E.ID = A.HIGH_SCHOOL ";
  $sql .= "LEFT OUTER JOIN PP_LOCATIONS F ON F.ID = E.LOCATION_ID ";
  $sql .= "WHERE ";
  $sql .= "  A.ID = ".$playerId.";";

  $result = mysqli_query($cn, $sql);
  $playerInfo = mysqli_fetch_array($result, MYSQLI_ASSOC);

  $interval = date_diff(date_create(), date_create($playerInfo['DATE_OF_BIRTH']));
  $playerInfo['AGE'] = $interval->format("%Y Years, %m Months");

  if($playerInfo['HEIGHT_IN'] == 0){
    $playerInfo['HEIGHT'] = '--';
  } else {
    $heightFt = floor($playerInfo['HEIGHT_IN'] / 12);
    $heightIn = $playerInfo['HEIGHT_IN'] % 12;
    $playerInfo['HEIGHT'] = $heightFt.'\' '.$heightIn.'"';
  }

  $tdy = new DateTime();
  $grad = new DateTime($playerInfo['GRAD_CLASS'].'-06-01');
  $diff = $grad->diff($tdy);
  if($diff->invert == 0){
    $diffYrs = 0 - ceil($diff->days/365.25);
  } else {
    $diffYrs = ceil($diff->days/365.25);
  }

  if($diffYrs > 7){$playerInfo['GRADE'] = 'Elementary School';}
  if($diffYrs == 7){$playerInfo['GRADE'] = '6th Grade';}
  if($diffYrs == 6){$playerInfo['GRADE'] = '7th Grade';}
  if($diffYrs == 5){$playerInfo['GRADE'] = '8th Grade';}
  if($diffYrs == 4){$playerInfo['GRADE'] = 'Freshman';}
  if($diffYrs == 3){$playerInfo['GRADE'] = 'Sophomore';}
  if($diffYrs == 2){$playerInfo['GRADE'] = 'Junior';}
  if($diffYrs <= 1){$playerInfo['GRADE'] = 'Senior';}

  $displayRankPct = 0;
  if($playerInfo['CLASS_RANK'] !== '--'){
    $rankSplit = explode('/',$playerInfo['CLASS_RANK']);
    $rankPercent = (intval($rankSplit[0]) / intval($rankSplit[1]));
    if($rankPercent > 0){$playerInfo['RANK_PERCENT'] = ceil($rankPercent * 100);}
    if($rankPercent == 0){$playerInfo['RANK_PERCENT'] = 1;}
    if($playerInfo['RANK_PERCENT'] < 30){$displayRankPct = 1;}
  }

  $imgHeadshot = "";
  if(strlen($playerInfo['IMG_HEADSHOT']) > 0){
    $imgHeadshot = $playerInfo['IMG_HEADSHOT'];
  } else {
    if($playerInfo['GENDER'] == "M"){$imgHeadshot = 'images/headshots/nophotomale.jpg';}
    if($playerInfo['GENDER'] == "F"){$imgHeadshot = 'images/headshots/nophotofemale.jpg';}    
  };

// Create instance of PDF
$pdf = new FPDF('P', 'in', 'Letter'); // 'P' for Portrait, 'in' for inches, 'Letter' for 8.5x11
$pdf->AddPage();

// Add a PNG logo to the top left corner
$pdf->Image('images/fliers/playerProfileBg_white.png', 0, 0, 8.5, 11); // Adjust x, y, width, height as needed

$pdf->Image($imgHeadshot, 6.5, 2, 1.5, 1.5); // Image path, x, y, width, height

// Set font and text color to white
$pdf->AddFont('LeagueGothic', '', 'LeagueGothic.php');
$pdf->SetFont('LeagueGothic', '', 60);

$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(0.05, .55);
$pdf->Cell(8, 0.5, $playerInfo['FIRST_NAME'], 0, 1, 'R');
$pdf->SetXY(0.05, 1.20);
$pdf->Cell(8, 0.5, $playerInfo['LAST_NAME'], 0, 1, 'R');

$pdf->SetTextColor(56, 182, 255);
$pdf->SetXY(0, .5);
$pdf->Cell(8, 0.5, $playerInfo['FIRST_NAME'], 0, 1, 'R');
$pdf->SetXY(0, 1.15);
$pdf->Cell(8, 0.5, $playerInfo['LAST_NAME'], 0, 1, 'R');

$pdf->SetFont('LeagueGothic', '', 20);

$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(0.75, 3.0);
$pdf->Cell(8, 0.5, 'Birthdate:  '.date('M j, Y', strtotime($playerInfo['DATE_OF_BIRTH'])).'', 0, 1, 'L');
$pdf->SetXY(0.75, 3.3);
$pdf->Cell(8, 0.5, 'Age:  '.$playerInfo['AGE'].'', 0, 1, 'L');
$pdf->SetXY(0.75, 3.6);
$pdf->Cell(8, 0.5, 'Height:  '.$playerInfo['HEIGHT'].'', 0, 1, 'L');

$pdf->SetXY(0.75, 4.2);
$pdf->Cell(8, 0.5, 'Position:  '.$playerInfo['POSITION_PRI'].'', 0, 1, 'L');
$pdf->SetXY(0.75, 4.5);
$pdf->Cell(8, 0.5, 'Secondary:  '.$playerInfo['POSITION_SEC'].'', 0, 1, 'L');
$pdf->SetXY(0.75, 4.8);
$pdf->Cell(8, 0.5, 'Footed:  '.$playerInfo['DOMINATE_FOOT'].'', 0, 1, 'L');

$pdf->SetXY(0.75, 5.4);
$pdf->Cell(8, 0.5, 'School:  '.$playerInfo['HIGH_SCHOOL_NAME'].'', 0, 1, 'L');
$pdf->SetXY(0.75, 5.7);
$pdf->Cell(8, 0.5, 'Grade:  '.$playerInfo['GRADE'].'', 0, 1, 'L');
$pdf->SetXY(0.75, 6.0);
$pdf->Cell(8, 0.5, 'City:  '.$playerInfo['HS_FULL_LOCATION'].'', 0, 1, 'L');

$pdf->SetXY(0.75, 6.6);
$pdf->Cell(8, 0.5, 'GPA:  '.$playerInfo['GPA'].'', 0, 1, 'L');
$pdf->SetXY(0.75, 6.9);
$pdf->Cell(8, 0.5, 'ACT / SAT:  '.$playerInfo['ACT_SCORE'].'/'.$playerInfo['SAT_SCORE'].'', 0, 1, 'L');
$pdf->SetXY(0.75, 7.2);
$dispRank = $playerInfo['CLASS_RANK'];
if($displayRankPct == 1){$dispRank .= ' � Top '.$playerInfo['RANK_PERCENT'].'%';} 
$pdf->Cell(8, 0.5, 'Rank:  '.$dispRank.'', 0, 1, 'L');

$data = 'https://uru.soccer/playerProfile.php?p='.$playerId.'&v=56ed5e';
$outputFile = 'qrcode.png';
$errorCorrectionLevel = 'L';
$matrixPointSize = 4;
QRcode::png($data, $outputFile, $errorCorrectionLevel, $matrixPointSize, 0);
$pdf->Image($outputFile, 0.75, 8.0, 1.5, 1.5); // Adjust x, y, width, and height as needed
$pdf->SetXY(2.3, 8.5);
$pdf->Cell(8, 0.6, 'Full Profile Available at uru.soccer!', 0, 1, 'L');

// Output the PDF
$pdf->Output('I', $playerInfo['LAST_NAME'].', '.$playerInfo['FIRST_NAME'].'.pdf'); // 'I' for inline browser output, 'D' for download, 'F' to save to a file
?>
