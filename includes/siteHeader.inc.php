    <header class="header">
			<div class="logo">
				<a href="https://uru.soccer/">
					<img class="logo-img" src="images/logos/uru_logoOnly.png" style='vertical-align: middle' alt="" />
					<span class="logo-lnk" style='vertical-align: middle' >URU.soccer<br><span style="font-size: 15px"><i>
<?php
  if($_SESSION['lang'] == 'en'){echo "Education Through Soccer";}
  if($_SESSION['lang'] == 'es'){echo "Educacion a Traves del Futbol";}
?>
</i></span></span>
				</a>
			</div>
      <a href="#" class="menu-btn"><span></span></a>
	  <div class="header-sidebar">
        <div class="top-menu">
		  <div class="top-menu-nav">	
		    <div class="menu-topmenu-container">
			  <ul class="menu">
<?php
  if($_SESSION['lang'] == 'en'){
    echo "<li class='menu-item'><a href='index.php'><span class='animated-button'><span>Home</span></span></a></li>";
    echo "<li class='menu-item'><a href='aboutUs.php'><span class='animated-button'><span>About Us</span></span></a></li>";
    echo "<li class='menu-item'><a href='services.php'><span class='animated-button'><span>Services</span></span></a></li>";
    echo "<li class='menu-item'><a href='training.php'><span class='animated-button'><span>Training</span></span></a></li>";
    echo "<li class='menu-item'><a href='playerProfiles.php'><span class='animated-button'><span>Player Profiles</span></span></a></li>";                
    echo "<li class='menu-item'><a href='contactUs.php'><span class='animated-button'><span>Contact Us</span></span></a></li>";
  }
  if($_SESSION['lang'] == 'es'){
    echo "<li class='menu-item'><a href='index.php'><span class='animated-button'><span>Hogar</span></span></a></li>";
    echo "<li class='menu-item'><a href='aboutUs.php'><span class='animated-button'><span>Sobre</span></span></a></li>";
    echo "<li class='menu-item'><a href='services.php'><span class='animated-button'><span>Servicios</span></span></a></li>";
    echo "<li class='menu-item'><a href='training.php'><span class='animated-button'><span>Capacitacion</span></span></a></li>";
    echo "<li class='menu-item'><a href='playerProfiles.php'><span class='animated-button'><span>Perfiles de Jugador</span></span></a></li>";                
    echo "<li class='menu-item'><a href='contactUs.php'><span class='animated-button'><span>Contacta</span></span></a></li>";
  }
?>                
			  </ul>
			</div>
		  </div>
	    </div>
      </div>
	</header>
