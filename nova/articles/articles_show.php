<?php
// Nova Articles Show - Display article details
// Session management and database connection
include '../inc/inc_nova_session.php';

// Get article ID from URL
$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$article_id) {
  $_SESSION['nova_errors'] = [__admin('articles.err.invalid_id')];
  header('Location: articles_list.php');
  exit();
}

// Fetch article data with related information
$query = 'SELECT
    a.id_article,
    a.article_title,
    a.article_content,
    a.article_summary,
    a.item_collection,
    a.item_year,
    a.youtube_video,
    a.image_1,
    a.image_2,
    a.publish_date,
    a.show_publish_date,
    a.is_active,
    a.created_at,
    a.updated_at,
    s.subcategory_name,
    c.category_name,
    c.id_category
  FROM ns_articles a
  LEFT JOIN ns_subcategories s ON a.id_subcategory = s.id_subcategory
  LEFT JOIN ns_categories c ON s.id_category = c.id_category
  WHERE a.id_article = ?';

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $article_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
  $_SESSION['nova_errors'] = [__admin('articles.err.not_found')];
  header('Location: articles_list.php');
  exit();
}

$article = mysqli_fetch_assoc($result);

// Page configuration
$page_title = __admin('articles.page.title_show') . ': ' . $article['article_title'] . ' | ' . $nova_settings['admin_name'];
$page_description = __admin('articles.page.desc_show');
?>
<!DOCTYPE html>
<html lang="<?= $nova_lang_code ?>">
<head>
    <!-- Quill Editor CSS (read-only display) - CDN -->
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">

    <?php include '../inc/inc_nova_head.php'; ?>
</head>

<body class="nova-layout">
    <!-- Navigation -->
    <?php include '../inc/inc_nova_navigation.php'; ?>

    <!-- MAIN CONTENT WRAPPER -->
    <main class="nova-main-content" role="main" id="main-content">
        <div class="container-nova py-4">

            <!-- PAGE HEADER SECTION -->
            <header class="page-header mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="page-title">
                            <i class="bi bi-eye me-2"></i>
                            <?= __admin('articles.show.title') ?>
                        </h1>
                    </div>
                    <div>
                        <a href="articles_list.php" class="btn btn-primary nova-btn-action">
                            <i class="bi bi-arrow-left"></i><?= __admin('articles.show.back_to_list') ?>
                        </a>
                    </div>
                </div>
            </header>
            <!-- END: PAGE HEADER SECTION -->

            <!-- ARTICLE DETAILS SECTION -->
            <section class="content-section">
                <div class="row">
                    <!-- Main Article Content -->
                    <div class="col-12">
                        <!-- Single Article Card with mint accent -->
                        <div class="card nova-card nova-card-mint-accent mb-4">
                            <div class="card-body">
                                <!-- Article Title -->
                                <h2 class="card-title mb-4"><?= htmlspecialchars($article['article_title']) ?></h2>

                                <!-- Metadata Grid 5x2 -->
                                <div class="row mb-4">
                                    <!-- Col 1: Status Badges (Informativo solo) -->
                                    <div class="col">
                                        <small class="text-muted d-block mb-1"><?= __admin('articles.show.status') ?></small>
                                        <!-- Active/Inactive Status (read-only) -->
                                        <span class="badge nova-badge-status mb-1 <?= $article['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                            <i class="bi bi-<?= $article['is_active'] ? 'check-circle' : 'x-circle' ?>"></i>
                                            <?= $article['is_active'] ? __admin('articles.status.active') : __admin('articles.status.inactive') ?>
                                        </span>
                                    </div>

                                    <!-- Col 2: Dates -->
                                    <div class="col">
                                        <small class="text-muted d-block mb-1"><?= __admin('articles.show.created_at') ?></small>
                                        <strong class="d-block mb-2">
                                            <i class="bi bi-clock me-1"></i>
                                            <?= date(NOVA_DATE_FORMAT . ', H:i:s', strtotime($article['created_at'])) ?>
                                        </strong>
                                        <small class="text-muted d-block mb-1"><?= __admin('articles.show.updated_at') ?></small>
                                        <strong class="d-block">
                                            <i class="bi bi-clock-history me-1"></i>
                                            <?= date(NOVA_DATE_FORMAT . ', H:i:s', strtotime($article['updated_at'])) ?>
                                        </strong>
                                    </div>

                                    <!-- Col 3: Category & Type -->
                                    <div class="col">
                                        <small class="text-muted d-block mb-1"><?= __admin('articles.show.category_subcategory') ?></small>
                                        <strong class="d-block mb-2">
                                            <i class="bi bi-folder me-1"></i>
                                            <?= htmlspecialchars($article['category_name'] ?? 'N/A') ?> -
                                            <?= htmlspecialchars($article['subcategory_name'] ?? 'N/A') ?>
                                        </strong>
                                        <small class="text-muted d-block mb-1"><?= __admin('articles.show.collection_type') ?></small>
                                        <strong class="d-block"><?= !empty($article['item_collection']) ? htmlspecialchars($article['item_collection']) : 'N/A' ?></strong>
                                    </div>

                                    <!-- Col 4: Year & Publish Date + Data ON/OFF Toggle -->
                                    <div class="col">
                                        <small class="text-muted d-block mb-1"><?= __admin('articles.show.year') ?></small>
                                        <strong class="d-block mb-2"><?= !empty($article['item_year']) ? htmlspecialchars($article['item_year']) : 'N/A' ?></strong>
                                        <small class="text-muted d-block mb-1"><?= __admin('articles.show.publish_date') ?></small>
                                        <strong class="d-block mb-2">
                                          <?= $article['publish_date'] ? date(NOVA_DATE_FORMAT, strtotime($article['publish_date'])) : 'N/A' ?>
                                        </strong>
                                        <!-- Show Publish Date Status (read-only) -->
                                        <span class="badge nova-badge-status <?= $article['show_publish_date'] ? 'bg-info text-white' : 'badge-outline-muted' ?>">
                                            <i class="bi bi-calendar-<?= $article['show_publish_date'] ? 'check' : 'x' ?>"></i>
                                            <?= $article['show_publish_date'] ? __admin('articles.status.date_on') : __admin('articles.status.date_off') ?>
                                        </span>
                                    </div>

                                    <!-- Col 5: ID & Creator -->
                                    <div class="col">
                                        <small class="text-muted d-block mb-1"><?= __admin('articles.show.article_id') ?></small>
                                        <strong class="d-block mb-2"><?= $article['id_article'] ?></strong>
                                        <small class="text-muted d-block mb-1"><?= __admin('articles.show.created_by') ?></small>
                                        <strong class="d-block">
                                            <i class="bi bi-person-fill me-1"></i>
                                            <?= htmlspecialchars($admin_full_name) ?>
                                        </strong>
                                    </div>
                                </div>

                                <!-- Visual Separator -->
                                <hr class="my-4">

                                <!-- Article Summary -->
                                <div class="mb-4">
                                    <h6 class="content-box-title">
                                        <i class="bi bi-card-text me-1"></i><?= __admin('articles.show.summary') ?>
                                    </h6>
                                    <?php if (!empty($article['article_summary'])): ?>
                                        <div class="content-filled border-left">
                                            <p class="mb-0"><?= nl2br(htmlspecialchars($article['article_summary'])) ?></p>
                                        </div>
                                    <?php else: ?>
                                        <div class="content-placeholder">
                                            <i class="bi bi-card-text"></i>
                                            <p><?= __admin('articles.show.no_summary') ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Article Content -->
                                <div class="mb-4">
                                    <h6 class="content-box-title">
                                        <i class="bi bi-journal-text me-1"></i><?= __admin('articles.show.extended_text') ?>
                                    </h6>
                                    <?php if (!empty($article['article_content'])): ?>
                                        <!-- Quill read-only content container -->
                                        <div class="ql-container ql-snow nova-quill-display">
                                            <div class="ql-editor">
                                                <?= $article['article_content'] ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="content-placeholder">
                                            <i class="bi bi-journal-text"></i>
                                            <p><?= __admin('articles.show.no_extended_text') ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Visual Separator -->
                                <hr class="my-4">

                                <!-- YouTube Video -->
                                <?php $has_video = !empty($article['youtube_video']); ?>
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h6 class="text-muted mb-2">
                                            <i class="bi bi-youtube me-1"></i><?= __admin('articles.show.youtube_video') ?>
                                        </h6>
                                        <?php if ($has_video):
                                          // Extract YouTube video ID inline (procedural)
                                          $video_id = null;
                                          if (!empty($article['youtube_video'])) {
                                            preg_match(
                                              '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/',
                                              $article['youtube_video'],
                                              $youtube_matches
                                            );
                                            $video_id = isset($youtube_matches[1]) ? $youtube_matches[1] : null;
                                          }
                                          ?>
                                            <?php if ($video_id): ?>
                                                <div class="ratio ratio-16x9">
                                                    <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($video_id) ?>"
                                                            title="YouTube video" allowfullscreen></iframe>
                                                </div>
                                            <?php else: ?>
                                                <a href="<?= htmlspecialchars($article['youtube_video']) ?>" target="_blank" class="btn btn-outline-danger nova-btn-action">
                                                    <i class="bi bi-youtube"></i><?= __admin('articles.show.open_video') ?>
                                                </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="content-placeholder nova-min-h-180">
                                                <i class="bi bi-youtube"></i>
                                                <p><?= __admin('articles.show.no_video') ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Visual Separator -->
                                <hr class="my-4">

                                <!-- Gallery Images - 2 images -->
                                <div class="mb-4">
                                    <h6 class="text-muted mb-2">
                                        <i class="bi bi-images me-1"></i><?= __admin('articles.show.image_gallery') ?>
                                    </h6>
                                    <div class="row">
                                        <!-- IMAGE 1 -->
                                        <div class="col-md-6">
                                            <small class="text-muted d-block mb-2">
                                                <i class="bi bi-image me-1"></i><?= __admin('articles.show.image_1') ?>
                                            </small>
                                            <?php if (!empty($article['image_1'])): ?>
                                                <img src="../../file_db_max/<?= $article['image_1'] ?>"
                                                     class="img-fluid rounded border"
                                                     alt="<?= htmlspecialchars($article['article_title']) ?> - Gallery 1">
                                                <small class="text-muted d-block mt-1">
                                                    <?= htmlspecialchars($article['image_1']) ?>
                                                    <?php
                                                    $image1_path = '../../file_db_max/' . $article['image_1'];
                                                    if (file_exists($image1_path)):
                                                      // Format image size
                                                      $img1_size = filesize($image1_path);
                                                      $img1_units = ['B', 'KB', 'MB', 'GB'];
                                                      $img1_power = $img1_size > 0 ? floor(log($img1_size, 1024)) : 0;
                                                      $img1_size_formatted = number_format($img1_size / pow(1024, $img1_power), 2, ',', '.') . ' ' . $img1_units[$img1_power];
                                                      ?>
                                                      - <?= $img1_size_formatted ?>
                                                    <?php endif; ?>
                                                </small>
                                            <?php else: ?>
                                                <div class="content-placeholder nova-min-h-240">
                                                    <i class="bi bi-image"></i>
                                                    <p><?= __admin('articles.show.no_image') ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- IMAGE 2 -->
                                        <div class="col-md-6">
                                            <small class="text-muted d-block mb-2">
                                                <i class="bi bi-image me-1"></i><?= __admin('articles.show.image_2') ?>
                                            </small>
                                            <?php if (!empty($article['image_2'])): ?>
                                                <img src="../../file_db_max/<?= $article['image_2'] ?>"
                                                     class="img-fluid rounded border"
                                                     alt="<?= htmlspecialchars($article['article_title']) ?> - Gallery 2">
                                                <small class="text-muted d-block mt-1">
                                                    <?= htmlspecialchars($article['image_2']) ?>
                                                    <?php
                                                    $image2_path = '../../file_db_max/' . $article['image_2'];
                                                    if (file_exists($image2_path)):
                                                      // Format image size
                                                      $img2_size = filesize($image2_path);
                                                      $img2_units = ['B', 'KB', 'MB', 'GB'];
                                                      $img2_power = $img2_size > 0 ? floor(log($img2_size, 1024)) : 0;
                                                      $img2_size_formatted = number_format($img2_size / pow(1024, $img2_power), 2, ',', '.') . ' ' . $img2_units[$img2_power];
                                                      ?>
                                                      - <?= $img2_size_formatted ?>
                                                    <?php endif; ?>
                                                </small>
                                            <?php else: ?>
                                                <div class="content-placeholder nova-min-h-240">
                                                    <i class="bi bi-image"></i>
                                                    <p><?= __admin('articles.show.no_image') ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- END: ARTICLE DETAILS SECTION -->

        </div>
    </main>
    <!-- END: MAIN CONTENT WRAPPER -->

    <!-- FOOTER -->
    <?php include '../inc/inc_nova_footer.php'; ?>

</body>
</html>
