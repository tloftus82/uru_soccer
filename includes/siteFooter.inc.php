  <footer class="footer">
    <div class="socials">
      <a target="_blank" href="https://www.facebook.com/profile.php?id=100081811810085"><i class="icon fab fa-facebook-f"></i></a>
      <a target="_blank" href="https://www.tiktok.com/@uruhighperformanc">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px;vertical-align:middle;"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.69a8.17 8.17 0 0 0 4.78 1.52V6.75a4.85 4.85 0 0 1-1.01-.06z"/></svg>
      </a>
    </div>
  </footer>

  <a href='<?= ($_SESSION['lang'] ?? 'en') === 'es' ? 'setLang.php?lang=en' : 'setLang.php?lang=es' ?>'
     style="position:fixed;bottom:24px;left:24px;z-index:999;background:rgba(255,255,255,0.15);backdrop-filter:blur(6px);border:1px solid rgba(255,255,255,0.25);color:#fff;padding:7px 16px;border-radius:20px;font-size:12px;font-weight:600;letter-spacing:.8px;text-decoration:none;box-shadow:0 2px 10px rgba(0,0,0,0.3);">
    <?= ($_SESSION['lang'] ?? 'en') === 'es' ? 'English' : 'Español' ?>
  </a>