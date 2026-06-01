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

			<!-- Section Started -->
			<div class="section started" id="section-started">
				<div class="centrize full-width">
					<div class="vertical-center">

						<!-- title -->
						<h1 class="h-title">
							<?php if($_SESSION['lang'] == 'en'){ echo "Our Services"; } ?>
							<?php if($_SESSION['lang'] == 'es'){ echo "Nuestros Servicios"; } ?>
						</h1>

						<!-- content started -->
						<div class="started-content">

							<!-- subtitle -->
							<div class="h-subtitles">
								<div class="h-subtitle typing-subtitle">
									<?php if($_SESSION['lang'] == 'en'): ?>
									<p>Player Placement</p>
									<p>College Workshop</p>
									<p>Video Recording</p>
									<p>Individual Training Plans</p>
									<?php endif; ?>
									<?php if($_SESSION['lang'] == 'es'): ?>
									<p>Colocacion de Jugadores</p>
									<p>Taller Universitario</p>
									<p>Grabacion de Video</p>
									<p>Planes de Entrenamiento Individual</p>
									<?php endif; ?>
								</div>
								<span class="typed-subtitle"></span>
							</div>

							<!-- text -->
							<div class="h-text">
								<?php if($_SESSION['lang'] == 'en'){ echo "Discover a wide array of services tailored to meet your unique needs. Below, you'll find a list of our offerings, accompanied by a short description of each. For additional information or to explore customization options, we encourage you to reach out to us."; } ?>
								<?php if($_SESSION['lang'] == 'es'){ echo "Descubra una amplia gama de servicios adaptados para satisfacer sus necesidades unicas. A continuacion encontrara una lista de nuestras ofertas, acompanada de una breve descripcion de cada una. Para obtener informacion adicional o explorar opciones de personalizacion, lo invitamos a ponerse en contacto con nosotros."; } ?>
							</div>

							<!-- mouse button -->
							<a href="#" class="btn mouse-btn" style="display: none;">
								<i class="icon fas fa-chevron-down"></i>
							</a>

						</div>

					</div>
				</div>
			</div>

			<!-- Section Service -->
			<div class="section service" id="section-services">
				<div class="content">

					<!-- title -->
					<div class="titles">
						<div class="title">
							<?php if($_SESSION['lang'] == 'en'){ echo "Our Services"; } ?>
							<?php if($_SESSION['lang'] == 'es'){ echo "Nuestros Servicios"; } ?>
						</div>
						<div class="subtitle">
							<?php if($_SESSION['lang'] == 'en'){ echo "What We Do"; } ?>
							<?php if($_SESSION['lang'] == 'es'){ echo "Lo Que Hacemos"; } ?>
						</div>
					</div>

					<!-- services items -->
					<div class="service-items">

						<div class="service-col">
							<div class="service-item">
								<div class="icon"><i class="fas fa-futbol"></i></div>
								<div class="name">
									<?php if($_SESSION['lang'] == 'en'){ echo "Player<br />Placement"; } ?>
									<?php if($_SESSION['lang'] == 'es'){ echo "Colocacion<br />de Jugadores"; } ?>
								</div>
								<div class="single-post-text">
									<p>
										<?php
										if($_SESSION['lang'] == 'en'){
											echo "Our services include contacting college coaches to boost your chances of being noticed. Building impressive player profiles that highlight your skills and achievements. We also offer expert match video editing to create captivating reels that showcase your talent. Get guidance through the admission process, ensuring a smooth journey to college soccer. Step up your recruitment game with our comprehensive services.";
										}
										if($_SESSION['lang'] == 'es'){
											echo "Nuestros servicios incluyen contactar a entrenadores universitarios para aumentar sus posibilidades de ser notado. Creamos perfiles de jugadores impresionantes que destacan sus habilidades y logros. Tambien ofrecemos edicion experta de videos de partidos para crear videos destacados que muestren su talento. Reciba orientacion a traves del proceso de admision, garantizando un camino sin contratiempos hacia el futbol universitario.";
										}
										?>
									</p>
								</div>
							</div>
						</div>

						<div class="service-col">
							<div class="service-item">
								<div class="icon"><i class="fas fa-graduation-cap"></i></div>
								<div class="name">
									<?php if($_SESSION['lang'] == 'en'){ echo "College<br />Workshop"; } ?>
									<?php if($_SESSION['lang'] == 'es'){ echo "Taller<br />Universitario"; } ?>
								</div>
								<div class="single-post-text">
									<p>
										<?php
										if($_SESSION['lang'] == 'en'){
											echo "Join our college recruiting workshop and gain valuable insights into the journey of finding the perfect fit. Discover the secrets of navigating the admissions process, understanding eligibility requirements, acing tryouts, and making memorable campus visits. Learn how to unlock financial aid and scholarship opportunities that can make your dreams a reality. Don't miss this opportunity to take control of your future. Our next workshop is Thursday, July 20, 2023 at 8:30PM via Zoom.  Sign up <a href=\"https://forms.gle/CUBasT8y6rvpCTWg6\" target='_blank'>here</a>!";
										}
										if($_SESSION['lang'] == 'es'){
											echo "Unase a nuestro taller de reclutamiento universitario y obtenga informacion valiosa sobre el camino para encontrar el lugar perfecto. Descubra los secretos para navegar el proceso de admision, comprender los requisitos de elegibilidad, destacar en las pruebas y realizar visitas memorables al campus. Aprenda como acceder a ayuda financiera y oportunidades de becas que pueden hacer realidad sus suenos. No pierda esta oportunidad de tomar el control de su futuro. Nuestro proximo taller es el jueves 20 de julio de 2023 a las 8:30 PM via Zoom. Registrese <a href=\"https://forms.gle/CUBasT8y6rvpCTWg6\" target='_blank'>aqui</a>!";
										}
										?>
									</p>
								</div>
							</div>
						</div>

						<div class="service-col">
							<div class="service-item">
								<div class="icon"><i class="fas fa-video"></i></div>
								<div class="name">
									<?php if($_SESSION['lang'] == 'en'){ echo "Video<br />Production"; } ?>
									<?php if($_SESSION['lang'] == 'es'){ echo "Produccion<br />de Video"; } ?>
								</div>
								<div class="single-post-text">
									<p>
										<?php
										if($_SESSION['lang'] == 'en'){
											echo "Capture your soccer skills like never before! Our services include full-length match video recording, ensuring every moment of your game is captured in high-quality footage. Relive your best plays, analyze your performance, and showcase your talent to college coaches. But that's not all! We also specialize in highlight video editing, where our experts transform your best moments into a captivating reel that grabs attention and highlights your skills. Take the spotlight and boost your chances of getting recruited with our professional video services. Contact us today to elevate your soccer journey!";
										}
										if($_SESSION['lang'] == 'es'){
											echo "Capture sus habilidades futbolisticas como nunca antes. Nuestros servicios incluyen grabacion completa de videos de partidos, asegurandonos de que cada momento de su juego quede capturado en material de alta calidad. Reviva sus mejores jugadas, analice su rendimiento y muestre su talento a los entrenadores universitarios. Tambien nos especializamos en la edicion de videos destacados, donde nuestros expertos transforman sus mejores momentos en un video cautivador que capta la atencion y resalta sus habilidades.";
										}
										?>
									</p>
								</div>
							</div>
						</div>

						<div class="service-col">
							<div class="service-item">
								<div class="icon"><i class="fas fa-running"></i></div>
								<div class="name">
									<?php if($_SESSION['lang'] == 'en'){ echo "Individual<br />Training"; } ?>
									<?php if($_SESSION['lang'] == 'es'){ echo "Entrenamiento<br />Individual"; } ?>
								</div>
								<div class="single-post-text">
									<p>
										<?php
										if($_SESSION['lang'] == 'en'){
											echo "Take your soccer game to new heights with our custom individualized training plans! Our comprehensive plans are tailored specifically for you, encompassing strength and conditioning exercises to boost your athleticism, technical drills to enhance your skills, and soccer IQ training to sharpen your game intelligence. With our personalized approach, you'll have a strategic roadmap to improve every aspect of your performance. Get ready to impress college coaches with your exceptional abilities. Start your journey to success today with our specialized training plans!";
										}
										if($_SESSION['lang'] == 'es'){
											echo "Lleve su juego de futbol a nuevas alturas con nuestros planes de entrenamiento individualizados. Nuestros planes integrales estan adaptados especificamente para usted, e incluyen ejercicios de fuerza y acondicionamiento para mejorar su atletismo, ejercicios tecnicos para mejorar sus habilidades y entrenamiento de IQ de futbol para agudizar su inteligencia de juego. Con nuestro enfoque personalizado, tendra una hoja de ruta estrategica para mejorar cada aspecto de su rendimiento.";
										}
										?>
									</p>
								</div>
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
