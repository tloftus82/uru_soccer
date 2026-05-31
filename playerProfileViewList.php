<?php
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);

  include('dbConnect/dbConnect.inc.php');

  $sql = "";
  $sql .= "SELECT ";
  $sql .= "  A.VIEW_DATE_TIME, ";
  $sql .= "  CONCAT(C.FIRST_NAME, ' ', C.LAST_NAME) AS PLAYER, ";
  $sql .= "  CONCAT(B.FIRST_NAME, ' ', B.LAST_NAME) AS VIEWER, ";
  $sql .= "  A.IP_ADDRESS, ";
  $sql .= "  A.HOST_NAME, ";
  $sql .= "  A.IP_LOCATION, ";
  $sql .= "  A.IP_ORG ";
  $sql .= "FROM PP_VIEW_LOG A ";
  $sql .= "INNER JOIN PP_ALLOWED_VIEWERS B ON B.ID = A.VIEWER_ID "; 
  $sql .= "INNER JOIN PP_PLAYERS C ON C.ID = A.PLAYER_ID ";
  $sql .= "WHERE ";
  $sql .= "  A.VIEW_DATE_TIME >= DATE_SUB(CURDATE(), INTERVAL 12000 DAY) ";
  $sql .= "  AND B.ID NOT IN(2) ";
  $sql .= "  AND A.IP_ORG NOT LIKE '%Google%' ";
  //$sql .= "  AND CONCAT(C.FIRST_NAME, ' ', C.LAST_NAME) = 'Alexandra Loftus' ";
  $sql .= "ORDER BY ";
  $sql .= "  A.VIEW_DATE_TIME DESC ";
  $result = mysqli_query($cn, $sql);
  $viewsArray = mysqli_fetch_all($result, MYSQLI_ASSOC);

  echo "<table border=2>";
  echo "<tr><th>Date/Time</th><th>Player</th><th>Viewer</th><th>IP Address</th><th>Host Name</th><th>IP Location</th><th>IP Org</th></tr>";
  foreach($viewsArray as $view){
    echo "<tr><td>".$view['VIEW_DATE_TIME']."</td><td>".$view['PLAYER']."</td><td>".$view['VIEWER']."</td><td>".$view['IP_ADDRESS']."</td><td>".$view['HOST_NAME']."</td><td>".$view['IP_LOCATION']."</td><td>".$view['IP_ORG']."</td></tr>";
  }
  echo "</table>";



?>
