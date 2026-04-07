<!-- Article card -->
<article class="card star51-card star51-card-fixed h-100">
  <!-- Image + Badge Overlay -->
  <div class="position-relative">
    <img src="file_db_med/<?= $article['image_1'] ? $article['image_1'] : 'nova-01-med.jpg' ?>"
         class="card-img-top"
         alt="<?= htmlspecialchars($article['article_title']) ?>"
         loading="lazy" decoding="async" />
    <div class="card-badge-overlay">
      <?php if (!empty($article['item_collection'])): ?>
        <span class="badge badge-overlay badge-overlay-collection">
          <?= htmlspecialchars($article['item_collection']) ?>
        </span>
      <?php endif; ?>
      <?php if (!empty($article['item_year'])): ?>
        <span class="badge badge-overlay badge-overlay-highlight">
          <?= htmlspecialchars($article['item_year']) ?>
        </span>
      <?php endif; ?>
    </div>
  </div>
  <!-- Card Body -->
  <div class="card-body d-flex flex-column">
    <h3 class="card-title h5 mb-3 text-center">
      <?= htmlspecialchars($article['article_title']) ?>
    </h3>
    <p class="card-text flex-grow-1 text-start">
      <?php
      $summary = !empty($article['article_summary']) ? $article['article_summary'] : __front('articles.fallback_summary');
      if (mb_strlen($summary) > 120) {
        echo htmlspecialchars(mb_substr($summary, 0, 117)) . '...';
      } else {
        echo htmlspecialchars($summary);
      }
      ?>
    </p>
    <div class="d-flex justify-content-center mt-auto">
      <a href="articles-detail.php?id=<?= $article['id_article'] ?>"
         class="btn btn-star51 btn-pill"
         aria-label="<?= __front('buttons.go') ?>: <?= htmlspecialchars($article['article_title']) ?>">
        <span class="btn-text"><?= __front('buttons.go') ?></span>
        <i class="bi bi-arrow-right btn-icon"></i>
      </a>
    </div>
  </div>
</article>
