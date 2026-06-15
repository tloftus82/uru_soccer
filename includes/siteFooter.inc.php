  <footer class="footer">
    <div class="socials">
      <a target="_blank" href="https://www.facebook.com/profile.php?id=100081811810085"><i class="icon fab fa-facebook-f"></i></a>
      <a target="_blank" href="https://www.tiktok.com/@uruhighperformanc"><i class="icon fab fa-tiktok"></i></a>
    </div>
  </footer>

  <a href='<?= ($_SESSION['lang'] ?? 'en') === 'es' ? 'setLang.php?lang=en' : 'setLang.php?lang=es' ?>'
     style="position:fixed;bottom:24px;left:24px;z-index:999;background:rgba(255,255,255,0.15);backdrop-filter:blur(6px);border:1px solid rgba(255,255,255,0.25);color:#fff;padding:7px 16px;border-radius:20px;font-size:12px;font-weight:600;letter-spacing:.8px;text-decoration:none;box-shadow:0 2px 10px rgba(0,0,0,0.3);">
    <?= ($_SESSION['lang'] ?? 'en') === 'es' ? 'English' : 'Español' ?>
  </a>