<?php
require_once 'inc/books_data.php';
?>

<section class="books-page">
    <div class="container">
        <?php include_once 'inc/breadcrumbs.php'; ?>

        <div class="books-page-header">
            <div>
                <span class="books-eyebrow">Bookshelf</span>
                <h1>Explore Books by Duke Fitz-Theodore Randolph</h1>
                <p>Browse the full collection, read more about each title, or go straight to purchase from the available store link.</p>
            </div>
            <a href="./" class="btn btn-primary"><i class="ion-ios-home"></i> Back Home</a>
        </div>

        <div class="books-grid">
            <?php foreach ($books as $book): ?>
                <article class="book-card">
                    <a class="book-card-cover" href="./?p=books/<?php echo htmlspecialchars($book['slug']); ?>">
                        <img src="<?php echo htmlspecialchars($book['cover']); ?>" loading="lazy" alt="<?php echo htmlspecialchars($book['title']); ?>">
                    </a>
                    <div class="book-card-body">
                        <div class="book-meta">
                            <span><?php echo htmlspecialchars($book['tag']); ?></span>
                            <span><?php echo htmlspecialchars($book['date']); ?></span>
                        </div>
                        <h2><a href="./?p=books/<?php echo htmlspecialchars($book['slug']); ?>"><?php echo htmlspecialchars($book['title']); ?></a></h2>
                        <p><?php echo htmlspecialchars($book['summary']); ?></p>
                    </div>
                    <div class="book-card-actions">
                        <a href="./?p=books/<?php echo htmlspecialchars($book['slug']); ?>" class="book-btn book-btn-outline">
                            <i class="ion-ios-book-outline"></i> Details
                        </a>
                        <a href="<?php echo htmlspecialchars($book['purchase_url']); ?>" target="_blank" rel="noopener" class="book-btn book-btn-solid">
                            <i class="fas fa-shopping-cart"></i> Purchase
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
