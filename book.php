<?php
require_once 'inc/store_catalog.php';

$book = gdmb_store_book_by_slug($_GET['slug'] ?? '');
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
                        <?php if ($price = gdmb_format_price($book['price'], $book['currency'])): ?>
                            <p><strong><?php echo gdmb_e($price); ?></strong></p>
                        <?php endif; ?>
                        <p><strong>Availability:</strong> <?php echo gdmb_e(str_replace('_', ' ', ucfirst($book['availability']))); ?></p>
                        <?php foreach (preg_split('/\R+/', trim((string) $book['description'])) as $paragraph): ?>
                            <?php if (trim($paragraph) !== ''): ?>
                                <p><?php echo nl2br(gdmb_e($paragraph)); ?></p>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </article>

                <?php if (! empty($book['available']) && is_numeric($book['price']) && (float) $book['price'] > 0): ?>
                    <div class="sharing">
                        <div class="title"><i class="fas fa-shopping-cart"></i> Order the Book</div>
                        <ul class="social">
                            <li>
                                <form method="post" action="./?p=cart">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="slug" value="<?php echo gdmb_e($book['slug']); ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button class="book-btn book-btn-solid" type="submit"><i class="fas fa-shopping-cart"></i> Add to Cart</button>
                                </form>
                            </li>
                            <li>
                                <form method="post" action="./?p=cart">
                                    <input type="hidden" name="action" value="buy_now">
                                    <input type="hidden" name="slug" value="<?php echo gdmb_e($book['slug']); ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button class="book-btn book-btn-outline" type="submit">Buy Now</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (! empty($book['purchase_url'])): ?>
                    <div class="sharing">
                        <div class="title">
                            <i class="fas fa-shopping-cart"></i>
                            Purchase the Book
                        </div>
                        <ul class="social">
                            <li>
                                <a href="<?php echo gdmb_e($book['purchase_url']); ?>" target="_blank" rel="noopener" style="background-color: #FF9900; color:white;">
                                    <i class="fas fa-shopping-cart"></i> Purchase
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
