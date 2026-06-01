<?php
include('dbConnect/dbConnect.inc.php');
mysqli_query($cn, "CREATE TABLE IF NOT EXISTS URU_VARIABLES (VAR_KEY VARCHAR(100) PRIMARY KEY, VAR_VALUE TEXT NOT NULL, UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)");
$_uruVars = [];
$_vr = mysqli_query($cn, "SELECT VAR_KEY, VAR_VALUE FROM URU_VARIABLES");
while ($_vrow = mysqli_fetch_assoc($_vr)) $_uruVars[$_vrow['VAR_KEY']] = $_vrow['VAR_VALUE'];
$hpFlierImg  = $_uruVars['hp_flier_img']  ?? 'images/fliers/uruHighPerformance.jpg';
$hpFlierLink = $_uruVars['hp_flier_link'] ?? 'https://forms.gle/TuvduKCEcqyuR9hF6';
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

            <div class="started-content">
            <h1 class="h-title">
              In-Person Training 
			</h1>

							<div class="h-subtitles">
								<div class="h-subtitle typing-subtitle">
									<p>URU High Performance</p>
									<p>Club Team Training</p>
									<p>School Team Training</p>
                                    <p>Individual Training</p>
								</div>
								<span class="typed-subtitle"></span>
							</div>

              <div class="h-text">
			    <i>At our organization, we take pride in providing an extensive range of in-person training opportunities tailored to soccer players of all ages and skill levels. From small group conditioning sessions and technical skill development to comprehensive club and school team trainings, we are dedicated to working closely with you or your organization to create a customized and ideal solution that meets your unique training needs. Our experienced team of trainers and coaches is committed to ensuring that every participant receives top-notch guidance and support to enhance their soccer abilities and reach their full potential. With our diverse and flexible training programs, you can rest assured that we have the expertise and resources to help you excel on the field.</i>
			  </div>
              <!--<a href="#section-contacts" class="btn"><span class="animated-button"><span>Contact Us</span></span><i class="icon fas fa-chevron-right"></i></a>-->
              <a href="#" class="btn mouse-btn" style="display: none;"><i class="icon fas fa-chevron-down"></i></a>
            </div>
          </div>
          <div class="clear"></div>
		</div>
	  </div>

<!-- Section About -->
			<div class="section about" id="section-about">
				<div class="content">

					<!-- title -->
					<div class="titles">
						<div class="title">URU High Performance</div>
						<div class="subtitle">Small Group Training</div>
					</div>

					<!-- text -->
					<div class="cols">

						<div class="col col-full">
							<div class="single-post-text">
								<p>Come and experience URU High Performance! We are dedicated to maintaining high coach-to-athlete ratios to ensure that you receive individual attention during our small group sessions. URU High Performance training is built upon an integrated methodology that combines conditioning and technical drills, designed to enhance players' individual skills while fostering a high-intensity environment.  Information for our next session is below!  Register <a href='https://forms.gle/TuvduKCEcqyuR9hF6' target='_BLANK'>HERE</a>!</p>
<a href='<?= htmlspecialchars($hpFlierLink) ?>' target='_BLANK'><img src='<?= htmlspecialchars($hpFlierImg) ?>' style='width: 50%'></a>
							</div>
						</div>
					</div>

					<div class="clear"></div>
				</div>
			</div>

			<div class="section about" id="section-about">
				<div class="content">

					<!-- title -->
					<div class="titles">
						<div class="title">Team Training</div>
						<div class="subtitle">Club / School Teams</div>
					</div>

					<!-- text -->
					<div class="cols">

						<div class="col col-full">
							<div class="single-post-text">
								<p>At our organization, we take pride in providing a wide array of training options, offering custom training sessions meticulously crafted to accommodate the age, skill level, and developmental needs of your school or club team; get in touch with us today, and let us demonstrate how our expertise can be harnessed to develop a personalized session package that best suits the specific requirements of your team, ensuring optimal growth and progress.</p>

							</div>
						</div>
					</div>

					<div class="clear"></div>
				</div>
			</div>


        
		</div>
	  </div>

<?php include('includes/siteFooter.inc.php'); ?>
<?php include('includes/extScripts.inc.php'); ?> 

</body>
</html>
