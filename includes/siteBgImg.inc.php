<?php
  $dir = "images/siteBg";
  $files = glob($dir . '/home*.*');
  $file = array_rand($files);
  $randomPic = $files[$file];
?>

      <div class="background-bg">
	    <div class="background-filter circle" style="background-image: url('<?php echo $randomPic; ?>');"></div>
        <!--<div class="background-filter circle" style="background-image: url('images/action/home006.png');"></div>-->
	  </div>