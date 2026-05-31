<?php include('dbConnect/dbConnect.inc.php'); ?>

<!doctype html>
<html lang="en-US">

<?php include('includes/siteHtmlHeader.inc.php'); ?>

<body class="home">

  <?php include('includes/sitePreloader.inc.php'); ?>
	
  <div class="container">
	<?php include('includes/siteHeader.inc.php'); ?>
	
    <div class="wrapper">
            <?php include('includes/siteBgImg.inc.php'); ?>

    <div class="section contacts" id="section-contacts">
      <div class="content">
        <div class="titles">
          <div class="title"><br>Contact</div>
          <div class="subtitle">Let's talk</div>
        </div>
		<div class="contact-form">
          <form id="cform" method="post">
            <div class="group-val">
              <div class="label">Full name <strong>*</strong></div>
              <input type="text" name="name" placeholder="eg. John Smith" />
            </div>
            <div class="group-val">
              <div class="label">Email address <strong>*</strong></div>
              <input type="email" name="email" placeholder="example@domain.com" />
            </div>
            <div class="group-val">
              <div class="label">Message <strong>*</strong></div>
              <textarea name="message" placeholder="Message"></textarea>
            </div>
            <div class="group-bts">
              <button type="submit" class="btn"><span class="animated-button"><span>Send Message</span></span><i class="icon fas fa-chevron-right"></i></button>
		    </div>
          </form>
          <div class="alert-success"><p>Thanks, your message is sent successfully.</p></div>
        </div>
        <div class="contact-info">
          <div class="name">Carlos Saenz</div>
          <div class="subname">Head Coach</div>
          <div class="info-list">
            <ul>
              <li><strong>Phone:</strong>(402) 508-0568</li>
              <li><strong>E-mail:</strong><a href='mailto:uruhighperformance@gmail.com'>uruhighperformance@gmail.com</a></li>
            </ul>
          </div>
          <div class="author">Carlos Saenz</div>
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