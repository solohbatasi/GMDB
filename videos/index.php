<?php
require_once 'inc/videos_data.php';
?>

<section class="videos-page">
    <div class="container">
        <?php include_once 'inc/breadcrumbs.php'; ?>

        <div class="videos-page-header">
            <div>
                <span class="videos-eyebrow">Video Library</span>
                <h1>Preachings, Teachings, and Ministry Events</h1>
                <p>Watch recent messages and event moments from Global Ministries Daily Bread. Select any video to play it without leaving the page.</p>
            </div>
            <a href="https://www.youtube.com/@globalministries-dailybread" target="_blank" rel="noopener" class="btn btn-primary">
                <i class="ion-social-youtube"></i> YouTube Channel
            </a>
        </div>

        <ul class="video-list video-catalog-list" data-youtube='"carousel":false'>
            <?php foreach ($videos as $video): ?>
                <li>
                    <a data-youtube-id="<?php echo htmlspecialchars($video['id']); ?>" data-action="magnific"></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
