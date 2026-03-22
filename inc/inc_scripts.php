<?php
/**
 * Star51 - Common SCRIPTS Section
 * Include file for common JavaScript across all pages
 */
?>

  <!-- ========== SCRIPTS ========== -->
  <!-- Bootstrap JS bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
          integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
          crossorigin="anonymous"></script>

  <!-- GLightbox JS (conditional) -->
  <?php if (!empty($use_glightbox)): ?>
    <script src="https://cdn.jsdelivr.net/npm/glightbox@3.3.0/dist/js/glightbox.min.js"></script>
  <?php endif; ?>

  <!-- Custom JavaScript -->
  <script src="js/star51.js"></script>
</body>

</html>