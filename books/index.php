<?php
require_once 'inc/store_catalog.php';

$search = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$category = isset($_GET['category']) ? trim((string) $_GET['category']) : '';
$books = gdmb_store_books([
    'search' => $search,
    'category' => $category,
    'limit' => 50,
]);
$categories = [];

foreach (gdmb_store_books(['limit' => 50]) as $bookForCategory) {
    if (! empty($bookForCategory['category'])) {
        $categories[$bookForCategory['category_slug'] ?: $bookForCategory['category']] = $bookForCategory['category'];
    }
}
?>

<section class="books-page">
    <div class="container">
        <?php include_once 'inc/breadcrumbs.php'; ?>

        <div class="books-page-header">
            <div>
                <span class="books-eyebrow">Bookshelf</span>
                <h1>Explore Books by Duke Fitz-Theodore Randolph</h1>
                <p>Browse the full collection, read more about each title, or click Purchase to order through Amazon and have the book delivered to your preferred location.</p>
            </div>
            <a href="./" class="btn btn-primary"><i class="ion-ios-home"></i> Back Home</a>
        </div>

        <form method="get" class="books-filter" style="margin: 0 0 25px; display:flex; gap:10px; flex-wrap:wrap;">
            <input type="hidden" name="p" value="books">
            <input type="search" name="q" value="<?php echo gdmb_e($search); ?>" placeholder="Search books" class="form-control" style="max-width:280px;">
            <select name="category" class="form-control" style="max-width:240px;">
                <option value="">All categories</option>
                <?php foreach ($categories as $slug => $name): ?>
                    <option value="<?php echo gdmb_e($slug); ?>" <?php echo $category === (string) $slug ? 'selected' : ''; ?>>
                        <?php echo gdmb_e($name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>

        <div class="books-grid">
            <?php foreach ($books as $book): ?>
                <article class="book-card">
                    <a class="book-card-cover" href="./?p=book&amp;slug=<?php echo rawurlencode($book['slug']); ?>">
                        <img src="<?php echo gdmb_e($book['cover']); ?>" loading="lazy" alt="<?php echo gdmb_e($book['title']); ?>">
                    </a>
                    <div class="book-card-body">
                        <div class="book-meta">
                            <span><?php echo gdmb_e($book['category']); ?></span>
                            <span><?php echo gdmb_e($book['published_at']); ?></span>
                        </div>
                        <h2><a href="./?p=book&amp;slug=<?php echo rawurlencode($book['slug']); ?>"><?php echo gdmb_e($book['title']); ?></a></h2>
                        <?php if ($price = gdmb_format_price($book['price'], $book['currency'])): ?>
                            <p><strong><?php echo gdmb_e($price); ?></strong></p>
                        <?php endif; ?>
                        <p><?php echo gdmb_e($book['summary']); ?></p>
                    </div>
                    <div class="book-card-actions">
                        <a href="./?p=book&amp;slug=<?php echo rawurlencode($book['slug']); ?>" class="book-btn book-btn-outline">
                            <i class="ion-ios-book-outline"></i> Details
                        </a>
                        <?php if (! empty($book['available']) && is_numeric($book['price']) && (float) $book['price'] > 0): ?>
                            <form method="post" action="./?p=cart" style="display:inline;">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="slug" value="<?php echo gdmb_e($book['slug']); ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button class="book-btn book-btn-solid" type="submit"><i class="fas fa-shopping-cart"></i> Add to Cart</button>
                            </form>
                        <?php endif; ?>
                        <?php if (! empty($book['purchase_url'])): ?>
                            <a href="<?php echo gdmb_e($book['purchase_url']); ?>" target="_blank" rel="noopener" class="book-btn book-btn-solid">
                                <i class="fas fa-shopping-cart"></i> Purchase
                            </a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
