<?php
require_once 'inc/store_catalog.php';

$book = gdmb_store_book_by_slug($_GET['slug'] ?? '');
$priceOptions = $book ? gdmb_book_price_options($book) : [];
?>

<?php if (! $book): ?>
    <?php include '404.html'; ?>
<?php else: ?>
<section class="single">
    <div class="container">
        <div class="row">
            <div class="col-md-4 sidebar" id="sidebar">
                <aside>
                    <div class="aside-body">
                        <figure class="ads">
                            <img loading="lazy" src="<?php echo gdmb_e($book['cover']); ?>" alt="<?php echo gdmb_e($book['title']); ?>">
                            <figcaption><?php echo gdmb_e($book['author']); ?></figcaption>
                        </figure>
                    </div>
                </aside>
            </div>
            <div class="col-md-8">
                <?php include_once 'inc/breadcrumbs.php'; ?>
                <article class="article main-article">
                    <header>
                        <h1><?php echo gdmb_e($book['title']); ?></h1>
                        <div class="book-detail-prices">
                            <?php foreach ($priceOptions as $priceOption): ?>
                                <span><strong><?php echo gdmb_e($priceOption['label']); ?>:</strong> <?php echo gdmb_e($priceOption['formatted']); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <ul class="details">
                            <?php if (! empty($book['published_at'])): ?>
                                <li>Published <?php echo gdmb_e($book['published_at']); ?></li>
                            <?php endif; ?>
                            <?php if (! empty($book['category'])): ?>
                                <li><a><?php echo gdmb_e($book['category']); ?></a></li>
                            <?php endif; ?>
                            <li>By <a href="#"><?php echo gdmb_e($book['author']); ?></a></li>
                        </ul>
                    </header>
                    <div class="main">
                        <p><strong>Availability:</strong> <?php echo gdmb_e(str_replace('_', ' ', ucfirst($book['availability']))); ?></p>
                        <?php foreach (preg_split('/\R+/', trim((string) $book['description'])) as $paragraph): ?>
                            <?php if (trim($paragraph) !== ''): ?>
                                <p><?php echo nl2br(gdmb_e($paragraph)); ?></p>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </article>

                <?php if (! empty($book['available']) && $priceOptions): ?>
                    <div class="sharing">
                        <div class="title"><i class="fas fa-shopping-cart"></i> Order the Book</div>
                        <ul class="social">
                            <li>
                                <button type="button" class="book-btn book-btn-solid book-purchase-trigger" data-book-title="<?php echo gdmb_e($book['title']); ?>" data-book-slug="<?php echo gdmb_e($book['slug']); ?>" data-book-cover="<?php echo gdmb_e($book['cover']); ?>" data-currency="<?php echo gdmb_e($book['currency']); ?>" data-primary-price="<?php echo gdmb_e($book['price'] ?? ''); ?>" data-secondary-price="<?php echo gdmb_e($book['compare_price'] ?? ''); ?>"><i class="fas fa-shopping-cart"></i> Purchase</button>
                            </li>
                        </ul>
                    </div>
                <?php elseif (! empty($book['purchase_url'])): ?>
                    <div class="sharing">
                        <div class="title">
                            <i class="fas fa-shopping-cart"></i>
                            Purchase the Book
                        </div>
                        <ul class="social">
                            <li>
                                <a href="<?php echo gdmb_e($book['purchase_url']); ?>" target="_blank" rel="noopener" style="background-color: #FF9900; color:white;">
                                    <i class="fas fa-external-link-alt"></i> Buy on Amazon
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>

                <br>
                <div class="sharing">
                    <div class="title"><i class="ion-android-share-alt"></i> Sharing is caring</div>
                    <ul class="social">
                        <li><a href="#" class="facebook"><i class="ion-social-facebook"></i> Facebook</a></li>
                        <li><a href="#" class="twitter" style="background-color:black;"><i class="fab fa-x-twitter"></i> X (Twitter)</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
<?php require_once 'inc/book_purchase_modal.php'; ?>
