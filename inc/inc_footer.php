<?php
/**
 * Star51 - Common FOOTER Section
 * Include file for common footer across all pages
 *
 * Requires: inc_star51_lang.php loaded (via inc_head.php)
 */
?>

  <!-- ========== FOOTER ========== -->
  <!-- Brand info + Social links (Solo Edition) -->
  <footer class="star51-footer">
    <div class="container">
      <div class="row g-4">

        <!-- Brand section -->
        <div class="col-lg-6">
          <h5 class="footer-title">
            <i class="bi bi-star-fill me-2"></i>Star51
          </h5>
          <p><?= __front('footer.description') ?></p>
        </div>

        <!-- Social & contact section -->
        <div class="col-lg-6 text-lg-end">
          <h6 class="footer-title"><?= __front('footer.follow_us') ?></h6>
          <div class="d-flex gap-2 mt-3 justify-content-lg-end">
            <a href="#" class="btn btn-sm social-btn" aria-label="Facebook">
              <i class="bi bi-facebook" aria-hidden="true"></i>
            </a>
            <a href="#" class="btn btn-sm social-btn" aria-label="X (Twitter)">
              <i class="bi bi-twitter-x" aria-hidden="true"></i>
            </a>
            <a href="#" class="btn btn-sm social-btn" aria-label="Instagram">
              <i class="bi bi-instagram" aria-hidden="true"></i>
            </a>
            <a href="#" class="btn btn-sm social-btn" aria-label="LinkedIn">
              <i class="bi bi-linkedin" aria-hidden="true"></i>
            </a>
            <a href="#" class="btn btn-sm social-btn" aria-label="TikTok">
              <i class="bi bi-tiktok" aria-hidden="true"></i>
            </a>
          </div>
          <a href="contact.php" class="footer-link mt-3 d-inline-block">
            <i class="bi bi-envelope me-1"></i><?= __front('footer.contact_us') ?>
          </a>
        </div>
      </div>

      <hr class="my-4 opacity-25" />

      <!-- Copyright & legal links -->
      <div class="row align-items-center">
        <div class="col-md-6">
          <p class="small mb-0">&copy;&nbsp;<?= date('Y') ?> Star51. <?= __front('footer.copyright') ?></p>
        </div>
        <div class="col-md-6 text-md-end">
          <a href="policy.php" class="footer-link small me-3"><i class="bi bi-shield-lock me-1"></i><?= __front('footer.privacy_policy') ?></a>
          <a href="policy.php" class="footer-link small"><i class="bi bi-cookie me-1"></i><?= __front('footer.cookie_policy') ?></a>
        </div>
      </div>
    </div>
  </footer>
