<?php
// Nova Navigation Include - Solo Edition
// Single admin, no level gating

// Nova base path for navigation links
if (!defined('NOVA_BASE_PATH')) {
    $nova_base = '/nova';
} else {
    $nova_base = NOVA_BASE_PATH;
}
?>
<!-- Nova Main Navigation -->
<nav class="navbar navbar-expand-lg nova-navbar"
     role="navigation"
     aria-label="<?= __admin('nav.main_nav') ?>">
  <div class="container-nova d-flex justify-content-between align-items-center w-100">
    <!-- Nova Admin Home - Mobile only -->
    <a class="navbar-brand d-lg-none"
       href="<?= $nova_base ?>/home.php"
       aria-label="Nova Home">
      <i class="bi bi-house-heart-fill"></i>
    </a>

    <!-- Mobile direct access icons -->
    <div class="d-flex d-lg-none gap-2 mobile-nav-icons">
      <a href="<?= $nova_base ?>/admins/admins_list.php"
         class="mobile-nav-icon"
         aria-label="<?= __admin('nav.administrators') ?>"
         title="<?= __admin('nav.administrators') ?>">
        <i class="bi bi-people"></i>
      </a>
      <a href="#"
         class="mobile-nav-icon"
         data-bs-toggle="offcanvas"
         data-bs-target="#mobileGestioneMenu"
         aria-label="<?= __admin('nav.management') ?>"
         title="<?= __admin('nav.management') ?>">
        <i class="bi bi-gear"></i>
      </a>
      <a href="<?= $nova_base ?>/requests/requests_list.php"
         class="mobile-nav-icon"
         aria-label="<?= __admin('nav.requests') ?>"
         title="<?= __admin('nav.requests') ?>">
        <i class="bi bi-envelope"></i>
      </a>
      <a href="<?= $nova_base ?>/home.php"
         class="mobile-nav-icon"
         aria-label="<?= __admin('nav.search') ?>"
         title="<?= __admin('nav.search') ?>">
        <i class="bi bi-search"></i>
      </a>
      <!-- Language Switcher Mobile -->
      <a href="#"
         class="mobile-nav-icon"
         data-bs-toggle="offcanvas"
         data-bs-target="#mobileLangMenu"
         aria-label="<?= __admin('nav.language') ?>"
         title="<?= __admin('nav.language') ?>">
        <i class="bi bi-translate"></i>
      </a>
    </div>

    <!-- Desktop navigation menu -->
    <div class="d-none d-lg-block navbar-collapse" id="navbarNav">
      <!-- Left Menu: Navigation Icons -->
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'home.php' ? 'active' : '' ?>"
             href="<?= $nova_base ?>/home.php"
             aria-label="<?= __admin('nav.go_to_dashboard') ?>"
             title="<?= __admin('nav.home') ?>">
            <i class="bi bi-house-heart-fill" aria-hidden="true"></i>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link"
             href="<?= $nova_base ?>/admins/admins_list.php"
             aria-label="<?= __admin('nav.administrators') ?>"
             title="<?= __admin('nav.administrators') ?>">
            <i class="bi bi-people" aria-hidden="true"></i>
            <span class="d-lg-inline d-none ms-1"><?= __admin('nav.administrators') ?></span>
          </a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle"
             href="#"
             role="button"
             data-bs-toggle="dropdown"
             aria-expanded="false"
             aria-haspopup="true"
             aria-label="<?= __admin('nav.management') ?>"
             title="<?= __admin('nav.management') ?>">
            <i class="bi bi-gear" aria-hidden="true"></i>
            <span class="d-lg-inline d-none ms-1"><?= __admin('nav.management') ?></span>
          </a>
          <ul class="dropdown-menu nova-dropdown"
              aria-label="<?= __admin('nav.management') ?>">
            <li>
              <a class="dropdown-item"
                 href="<?= $nova_base ?>/cat/cat_list.php"
                 aria-label="<?= __admin('nav.categories') ?>">
                <i class="bi bi-tag me-2" aria-hidden="true"></i>
                <span class="dropdown-item-text"><?= __admin('nav.categories') ?></span>
              </a>
            </li>
            <li>
              <a class="dropdown-item"
                 href="<?= $nova_base ?>/subcat/subcat_list.php"
                 aria-label="<?= __admin('nav.subcategories') ?>">
                <i class="bi bi-tags me-2" aria-hidden="true"></i>
                <span class="dropdown-item-text"><?= __admin('nav.subcategories') ?></span>
              </a>
            </li>
            <li>
              <a class="dropdown-item"
                 href="<?= $nova_base ?>/articles/articles_list.php"
                 aria-label="<?= __admin('nav.articles') ?>">
                <i class="bi bi-collection me-2" aria-hidden="true"></i>
                <span class="dropdown-item-text"><?= __admin('nav.articles') ?></span>
              </a>
            </li>
          </ul>
        </li>
        <li class="nav-item">
          <a class="nav-link"
             href="<?= $nova_base ?>/requests/requests_list.php"
             aria-label="<?= __admin('nav.requests') ?>"
             title="<?= __admin('nav.requests') ?>">
            <i class="bi bi-envelope" aria-hidden="true"></i>
            <span class="d-lg-inline d-none ms-1"><?= __admin('nav.requests') ?></span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link"
             href="<?= $nova_base ?>/home.php"
             aria-label="<?= __admin('nav.search') ?>"
             title="<?= __admin('nav.search') ?>">
            <i class="bi bi-search" aria-hidden="true"></i>
            <span class="d-lg-inline d-none ms-1"><?= __admin('nav.search') ?></span>
          </a>
        </li>
      </ul>

      <!-- Right Menu: Language + Admin Name -->
      <ul class="navbar-nav align-items-center">
        <!-- Language Switcher -->
        <li class="nav-item dropdown me-2">
          <a class="nav-link dropdown-toggle"
             href="#"
             role="button"
             data-bs-toggle="dropdown"
             aria-expanded="false"
             aria-haspopup="true"
             aria-label="<?= __admin('nav.language') ?>">
            <i class="bi bi-translate me-1" aria-hidden="true"></i>
            <?= strtoupper($nova_lang_code) ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end nova-dropdown"
              aria-label="<?= __admin('nav.language') ?>">
            <?php foreach (nova_get_available_languages() as $lang): ?>
            <li>
              <a class="dropdown-item <?= $nova_lang_code === $lang['code'] ? 'active' : '' ?>"
                 href="<?= $nova_base ?>/admins/admins_lang_switch.php?lang=<?= $lang['code'] ?>&redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>">
                <?= strtoupper($lang['code']) ?> - <?= $lang['native'] ?>
                <?php if ($nova_lang_code === $lang['code']): ?>
                  <i class="bi bi-check2 ms-2" aria-hidden="true"></i>
                <?php endif; ?>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
        </li>

        <!-- User Profile -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle"
             href="#"
             role="button"
             data-bs-toggle="dropdown"
             aria-expanded="false"
             aria-haspopup="true"
             aria-label="<?= __admin('nav.profile') ?>: <?= htmlspecialchars($admin_full_name) ?>">
            <i class="bi bi-person-circle me-1" aria-hidden="true"></i>
            <?= htmlspecialchars($admin_full_name) ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end nova-dropdown"
              aria-label="<?= __admin('nav.profile_menu') ?>">
            <li>
              <a class="dropdown-item"
                 href="<?= $nova_base ?>/admins/admins_edit.php"
                 aria-label="<?= __admin('admins.page.title_edit_self') ?>">
                <i class="bi bi-pencil-square me-2" aria-hidden="true"></i>
                <span class="dropdown-item-text"><?= __admin('admins.page.title_edit_self') ?></span>
              </a>
            </li>
            <li><hr class="dropdown-divider" role="separator"></li>
            <li>
              <a class="dropdown-item"
                 href="<?= $nova_base ?>/logout.php"
                 aria-label="<?= __admin('nav.logout') ?>">
                <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>
                <span class="dropdown-item-text"><?= __admin('nav.logout') ?></span>
              </a>
            </li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Mobile Offcanvas Menu - Gestione -->
<div class="offcanvas offcanvas-bottom"
     tabindex="-1"
     id="mobileGestioneMenu"
     aria-labelledby="mobileGestioneMenuLabel">
  <div class="offcanvas-header nova-bg-offcanvas-header">
    <h5 class="offcanvas-title" id="mobileGestioneMenuLabel">
      <i class="bi bi-gear me-2"></i><?= __admin('nav.management') ?>
    </h5>
    <button type="button"
            class="btn-close"
            data-bs-dismiss="offcanvas"
            aria-label="<?= __admin('buttons.close') ?>"></button>
  </div>
  <div class="offcanvas-body">
    <div class="list-group list-group-flush">
      <a href="<?= $nova_base ?>/cat/cat_list.php"
         class="list-group-item list-group-item-action">
        <i class="bi bi-tag me-2"></i><?= __admin('nav.categories') ?>
      </a>
      <a href="<?= $nova_base ?>/subcat/subcat_list.php"
         class="list-group-item list-group-item-action">
        <i class="bi bi-tags me-2"></i><?= __admin('nav.subcategories') ?>
      </a>
      <a href="<?= $nova_base ?>/articles/articles_list.php"
         class="list-group-item list-group-item-action">
        <i class="bi bi-collection me-2"></i><?= __admin('nav.articles') ?>
      </a>
    </div>
  </div>
</div>

<!-- Mobile Offcanvas Menu - Language -->
<div class="offcanvas offcanvas-bottom"
     tabindex="-1"
     id="mobileLangMenu"
     aria-labelledby="mobileLangMenuLabel">
  <div class="offcanvas-header nova-bg-offcanvas-header">
    <h5 class="offcanvas-title" id="mobileLangMenuLabel">
      <i class="bi bi-translate me-2"></i><?= __admin('nav.language') ?>
    </h5>
    <button type="button"
            class="btn-close"
            data-bs-dismiss="offcanvas"
            aria-label="<?= __admin('buttons.close') ?>"></button>
  </div>
  <div class="offcanvas-body">
    <div class="list-group list-group-flush">
      <?php foreach (nova_get_available_languages() as $lang): ?>
      <a href="<?= $nova_base ?>/admins/admins_lang_switch.php?lang=<?= $lang['code'] ?>&redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
         class="list-group-item list-group-item-action <?= $nova_lang_code === $lang['code'] ? 'active' : '' ?>">
        <?= strtoupper($lang['code']) ?> - <?= $lang['native'] ?>
        <?php if ($nova_lang_code === $lang['code']): ?>
          <i class="bi bi-check2 ms-2"></i>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
