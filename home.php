		<section class="home">
			<div class="container">
				<div class="row">
					<div class="col-md-8 col-sm-12 col-xs-12">
					
						<?php
							// Pick a random verse reference (you can expand this list)
							$references = [
								"john 3:16",
								"Philippians 4:13",
								"psalm 23:1",
								"romans 8:28",
								"jeremiah 29:11",
								"proverbs 3:5",
								"isaiah 41:10",
								"Matthew 5:14"
							];
							$randomRef = $references[array_rand($references)];

							// Use Bible-API to fetch the verse text

							// Get random image from local folder
							$images = glob("images/verse/*.{jpg,png,jpeg}", GLOB_BRACE);
							$image = $images ? $images[array_rand($images)] : "images/default.jpg"; // fallback image

							$randomRef = $references[array_rand($references)];
							$apiUrl = "https://bible-api.com/" . urlencode($randomRef);

							$ch = curl_init();
							curl_setopt($ch, CURLOPT_URL, $apiUrl);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
							$response = curl_exec($ch);
							curl_close($ch);

							$verseData = json_decode($response, true);

							$verseText = isset($verseData['text']) ? $verseData['text'] : "Verse unavailable at the moment.";
							$verseReference = isset($verseData['reference']) ? $verseData['reference'] : $randomRef;
							// Replace Yahweh or all-uppercase LORD with "the Lord"
							$verseText = str_ireplace(['Yahweh', 'YHWH', 'LORD'], 'Lord', $verseText);
						?>


						<div class="owl-carousel owl-theme slide" id="featured">
							<!-- Event 1 -->
							<div class="item">
								<article class="featured">
									<div class="overlay"></div>
									<figure>
										<img src="<?= $image ?>" loading="lazy" alt="Bible Verse of the Day">
									</figure>
								
								</article>
							</div>
                          
						</div>

						
					</div>
					<div class="col-xs-6 col-md-4 sidebar" id="sidebar">
						<div class="sidebar-title for-tablet">Sidebar</div>
						<aside>
							<div class="aside-body">
								<div class="featured-author">
									<div class="featured-author-inner">
										<div class="featured-author-cover" style="background-image: url('images/18.jpg');">
											<div class="badges">
												<div class="badge-item"><i class="ion-star"></i></div>
											</div>
											<div class="featured-author-center">
												<figure class="featured-author-picture">
													<img src="images/IMG_0785.jpg" loading="lazy" alt="Sample Article">
												</figure>
												<div class="featured-author-info">
													<div class="desc"></div>
												</div>
											</div>
										</div>
										<div class="featured-author-body">
											<div class="featured-author-count">
												<div class="item">
													<a href="#">
														<div class="icon">
															<div>More</div>
															<i class="ion-chevron-right"></i>
														</div>														
													</a>
												</div>
											</div>
											<div class="featured-author-quote">
												<h6>
													"We seek to create innovative ways of engagement that bring people and communities together in creative and interactive ways"
												</h6>
										    </div>
											<div class="block">
												<h2 class="block-title">Our Photos</h2>
												<div class="block-body">
													<ul class="item-list-round" data-magnific="gallery">
														<?php
														$imageFiles = glob("images/gallery/*.{jpg,jpeg,png}", GLOB_BRACE);
														$visibleCount = 6; // Number of initially visible images
														$count = 0;

														foreach ($imageFiles as $index => $image) {
															$isHidden = $index >= $visibleCount ? 'hidden' : '';
															$moreText = $index == $visibleCount - 1 && count($imageFiles) > $visibleCount ? "<div class='more'>+" . (count($imageFiles) - $visibleCount) . "</div>" : "";
															echo "<li class='{$isHidden}'><a href='{$image}' style='background-image: url(\"{$image}\");'>{$moreText}</a></li>";
														}
														?>
													</ul>
												</div>
											</div>

											<div class="featured-author-footer">
												<a href="#">Explore Our Gallery</a>
											</div>
										</div>
									</div>
								</div>
							</div>
						</aside>
					
					</div>
				</div>
			</div>
		</section>

		<?php require_once 'inc/books_data.php'; ?>
		<section class="books-showcase">
			<div class="container">
				<div class="books-showcase-header">
					<div>
						<span class="books-eyebrow">Featured Books</span>
						<h1>Books for Faith, Ministry, and Everyday Formation</h1>
						<p>Explore practical resources written to equip believers, leaders, families, and communities for deeper Christian service.</p>
					</div>
					<div class="books-header-actions">
						<a href="./?p=books" class="book-link">View all books</a>
						<div class="carousel-nav" id="books-carousel-nav">
							<div class="prev"><i class="ion-ios-arrow-left"></i></div>
							<div class="next"><i class="ion-ios-arrow-right"></i></div>
						</div>
					</div>
				</div>

				<div class="owl-carousel owl-theme books-carousel" id="books-carousel">
					<?php foreach ($books as $book): ?>
						<article class="book-slide">
							<a class="book-slide-cover" href="./?p=books/<?php echo htmlspecialchars($book['slug']); ?>">
								<img src="<?php echo htmlspecialchars($book['cover']); ?>" loading="lazy" alt="<?php echo htmlspecialchars($book['title']); ?>">
							</a>
							<div class="book-slide-body">
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

		<section class="best-of-the-week">
			<div class="container">
				<h1><div class="text-center">Best Of The Week</div>
					<div class="carousel-nav" id="best-of-the-week-nav">
						<div class="prev" style="color:white; background-color:black;" >
							<i class="ion-ios-arrow-left"></i>
						</div>
						<div class="next" style="color:white; background-color:black;" >
							<i class="ion-ios-arrow-right"></i>
						</div>
					</div>
				</h1>
				<div class="owl-carousel owl-theme carousel-1">
					<article class="article">
						<div class="inner">
							<figure>
								<a href="./?p=blogs/baptism">
									<img src="images/bible/5.jpg" loading="lazy" alt="Sermon of the Week">
								</a>
							</figure>
							<div class="padding">
								<div class="detail">
									<div class="time">January 17, 2025</div>
									<div class="category"><a href="#">Sermons</a></div>
								</div>
								<h2><a href="./?p=blogs/baptism">Saved & Condemned</a></h2>
								<p>Mark 16:16 speaks about faith, baptism, and salvation, emphasizing the importance of believing in Jesus Christ....</p>
							</div>
						</div>
					</article>	
					<article class="article">
						<div class="inner">
							<figure>
								<a href="./?p=blogs/shepherd">
									<img src="images/blogs/shepherd.png" loading="lazy" alt="Sermon of the Week">
								</a>
							</figure>
							<div class="padding">
								<div class="detail">
									<div class="time">January 16, 2025</div>
									<div class="category"><a href="#">Sermons</a></div>
								</div>
								<h2><a href="./?p=blogs/shepherd">The lord is my good shepherd</a></h2>
								<p>Psalm 23 is one of the most cherished biblical declarations, reflecting God’s care for His people. God is portrayed as the...</p>
							</div>
						</div>
					</article>	
					<article class="article">
						<div class="inner">
							<figure>
								<a href="./?p=blogs/faith">
									<img loading="lazy" src="images/4.jpg" alt="Sermon of the Week">
								</a>
							</figure>
							<div class="padding">
								<div class="detail">
									<div class="time">January 14, 2025</div>
									<div class="category"><a href="#">Sermons</a></div>
								</div>
								<h2><a href="./?p=blogs/faith">“The Power of Faith” – A Message of Breakthrough</a></h2>
								<p>This week’s sermon reminds us to trust God through trials. Read the full message here...</p>
							</div>
						</div>
					</article>
					<article class="article">
						<div class="inner">
							<figure>
								<a href="./?p=503">
									<img src="images/13.jpg" loading="lazy" alt="Devotional Highlight">
								</a>
							</figure>
							<div class="padding">
								<div class="detail">
									<div class="time">April 04, 2025</div>
									<div class="category"><a href="./?p=503">Devotional</a></div>
								</div>
								<h2><a href="./?p=503">Morning Grace: Starting Your Day With God</a></h2>
								<p>A refreshing devotional reminding us to seek God’s presence.</p>
							</div>
						</div>
					</article>
					<!-- Article 3: Scripture of the Week -->
					<article class="article">
						<div class="inner">
							<figure>
								<a href="./?p=503">
									<img src="images/15.jpg" loading="lazy" alt="Scripture of the Week">
								</a>
							</figure>
							<div class="padding">
								<div class="detail">
									<div class="time">April 03, 2025</div>
									<div class="category"><a href="./?p=503">Scripture</a></div>
								</div>
								<h2><a href="./?p=503">Philippians 4:6 – Do Not Be Anxious</a></h2>
								<p>"Do not be anxious about anything..." A gentle reminder to bring all our worries to God in prayer.</p>
							</div>
						</div>
					</article>
					<!-- Article 4: Weekly Prayer Focus -->
					<article class="article">
						<div class="inner">
							<figure>
								<a href="./?p=503">
									<img src="images/16.jpg" loading="lazy" alt="Prayer Focus">
								</a>
							</figure>
							<div class="padding">
								<div class="detail">
									<div class="time">April 02, 2025</div>
									<div class="category"><a href="./?p=503">Prayer</a></div>
								</div>
								<h2><a href="./?p=503">Prayer Focus: Peace in Our Hearts and Homes</a></h2>
								<p>This week we pray for God’s peace to reign within families, communities, and personal lives.</p>
							</div>
						</div>
					</article>
					<!-- Article 5: Worship Moment -->
					<article class="article">
						<div class="inner">
							<figure>
								<a href="./?p=503">
									<img src="images/17.jpg" loading="lazy" alt="Worship Moment">
								</a>
							</figure>
							<div class="padding">
								<div class="detail">
									<div class="time">April 01, 2025</div>
									<div class="category"><a href="./?p=503">Worship</a></div>
								</div>
								<h2><a href="./?p=503">Heaven Touched Earth: Praise Night Recap</a></h2>
								<p>An evening of pure worship where hearts were lifted and God’s presence filled the room.</p>
							</div>
						</div>
					</article>
					<!-- Article 6: Quote from Pastor -->
					<article class="article">
						<div class="inner">
							<figure>
								<a href="./?p=503">
									<img src="images/18.jpg" loading="lazy" alt="Pastor Quote">
								</a>
							</figure>
							<div class="padding">
								<div class="detail">
									<div class="time">March 31, 2025</div>
									<div class="category"><a href="./?p=503">Quote</a></div>
								</div>
								<h2><a href="./?p=503">"Faith is Trusting God's Silence" – Pastor David</a></h2>
								<p>This week’s quote reminds us that even in silence, God is working behind the scenes.</p>
							</div>
						</div>
	     			</article>
					<article class="article">
						<div class="inner">
							<figure>
								<a href="./?p=503">
									<img src="images/19.jpg" loading="lazy" alt="Pastor Quote">
								</a>
							</figure>
							<div class="padding">
								<div class="detail">
									<div class="time">March 31, 2025</div>
									<div class="category"><a href="./?p=503">Quote</a></div>
								</div>
								<h2><a href="./?p=503">"Faith is Trusting God's Silence" – Pastor David</a></h2>
								<p>This week’s quote reminds us that even in silence, God is working behind the scenes.</p>
							</div>
						</div>
					</article>
					<article class="article">
						<div class="inner">
							<figure>
								<a href="./?p=503">
									<img src="images/20.png" loading="lazy" alt="Worship Moment">
								</a>
							</figure>
							<div class="padding">
								<div class="detail">
									<div class="time">April 01, 2025</div>
									<div class="category"><a href="./?p=503">Worship</a></div>
								</div>
								<h2><a href="./?p=503">Heaven Touched Earth: Praise Night Recap</a></h2>
								<p>An evening of pure worship where hearts were lifted and God’s presence filled the room.</p>
							</div>
						</div>
					</article>		
				</div>
			</div>
		</section>
		<section class="home">
			<div class="container">
				<div class="row">
					<div class="col-md-8 col-sm-12 col-xs-12">
						<div class="banner">
							<a href="#">
								<img src="images/kk.jpg" alt="Sample Article">
							</a>
						</div>
						<div class="line transparent little"></div>
						<div class="row">
							<div class="col-md-6 col-sm-6 trending-tags">
								<h1 class="title-col">Trending Tags</h1>
								<div class="body-col">
									<ol class="tags-list">
										<li><a href="./?p=503">Faith</a></li>
										<li><a href="./?p=503">Community Outreach</a></li>
										<li><a href="./?p=503">Youth Ministry</a></li>
										<li><a href="./?p=503">Charity Events</a></li>
										<li><a href="./?p=503">Volunteering</a></li>
										<li><a href="./?p=503">Global Mission</a></li>
										<li><a href="./?p=503">Health Initiatives</a></li>
										<li><a href="./?p=503">Religious Studies</a></li>
										<li><a href="./?p=503">Humanitarian Aid</a></li>
										<li><a href="./?p=503">Spiritual Growth</a></li>
									</ol>
								</div>

							</div>
							<div class="col-md-6 col-sm-6">
								<h1 class="title-col">
									Hot News
									<div class="carousel-nav" id="hot-news-nav">
										<div class="prev" style="background-color:black;">
											<i class="ion-ios-arrow-left"></i>
										</div>
										<div class="next" style="background-color:black;">
											<i class="ion-ios-arrow-right"></i>
										</div>
									</div>
								</h1>
								<div class="body-col vertical-slider" data-max="4" data-nav="#hot-news-nav" data-item="article">
									<article class="article-mini">
										<div class="inner">
										<figure>
											<a href="#">
											<img src="images/16.jpg" alt="Sample Article">
											</a>
										</figure>
										<div class="padding">
											<h1><a href="./?p=503">Missionary Work in Kenya: A Heartfelt Journey</a></h1>
											<div class="detail">
											<div class="category"><a href="#">Global Mission</a></div>
											<div class="time">April 7, 2025</div>
											</div>
										</div>
										</div>
									</article>
									 <article class="article-mini">
										<div class="inner">
										<figure>
											<a href="#">
											<img src="images/IMG_0798.jpg" alt="Sample Article">
											</a>
										</figure>
										<div class="padding">
											<h1><a href="./?p=503">Spreading Faith Through Education: A Global Effort</a></h1>
											<div class="detail">
											<div class="category"><a href="#">Education</a></div>
											<div class="time">April 6, 2025</div>
											</div>
										</div>
										</div>
									</article>
									<article class="article-mini">
										<div class="inner">
										<figure>
											<a href="#">
											<img src="images/18.jpg" alt="Sample Article">
											</a>
										</figure>
										<div class="padding">
											<h1><a href="./?p=503">New Charity Event for Underprivileged Communities</a></h1>
											<div class="detail">
											<div class="category"><a href="#">Charity</a></div>
											<div class="time">April 5, 2025</div>
											</div>
										</div>
										</div>
									</article>
									<article class="article-mini">
										<div class="inner">
											<figure>
											<a href="#">
												<img src="images/18.jpg" alt="Sample Article">
											</a>
											</figure>
											<div class="padding">
											<h1><a href="./?p=503">Building Stronger Communities Through Faith</a></h1>
											<div class="detail">
												<div class="category"><a href="#">Community</a></div>
												<div class="time">April 4, 2025</div>
											</div>
											</div>
										</div>
									</article>
									<article class="article-mini">
										<div class="inner">
											<figure>
											<a href="#">
												<img src="images/19.jpg" alt="Sample Article">
											</a>
											</figure>
											<div class="padding">
											<h1><a href="./?p=503">New Volunteer Program to Support Families in Need</a></h1>
											<div class="detail">
												<div class="category"><a href="#">Volunteer</a></div>
												<div class="time">April 3, 2025</div>
											</div>
											</div>
										</div>
									</article>
									<article class="article-mini">
										<div class="inner">
											<figure>
											<a href="#">
												<img src="images/bible/1.jpg" alt="Sample Article">
											</a>
											</figure>
											<div class="padding">
											<h1><a href="./?p=503">Empowering Youth Through Education and Mentorship</a></h1>
											<div class="detail">
												<div class="category"><a href="#">Youth Ministry</a></div>
												<div class="time">April 2, 2025</div>
											</div>
											</div>
										</div>
									</article>
								</div>
							</div>
						</div>
					</div>
					<div class="col-xs-6 col-md-4 sidebar" id="sidebar">
					<div class="sidebar-title for-tablet">Sidebar</div>
					<aside>
						<div class="aside-body">
							<form class="newsletter"  style="background-color:#252e38;">
								<div class="icon">
									<i class="ion-ios-email-outline"></i>
									<h1>Newsletter</h1>
								</div>
								<div class="input-group">
									<input type="email" class="form-control email" placeholder="Your mail">
									<div class="input-group-btn">
										<button class="btn btn-primary"><i class="ion-paper-airplane"></i></button>
									</div>
								</div>
								<p>By subscribing you will receive new articles in your email.</p>
							</form>
						</div>
					</aside>
					<aside>
						<h1 class="aside-title">Videos
							<div class="carousel-nav" id="video-list-nav">
								<div class="prev" style="background-color:black;"><i class="ion-ios-arrow-left"></i></div>
								<div class="next" style="background-color:black;"><i class="ion-ios-arrow-right"></i></div>
							</div>
						</h1>
						<div class="aside-body">
							<ul class="video-list" data-youtube='"carousel":true, "nav": "#video-list-nav"'>
								<li><a data-youtube-id="PPnsCJh3g0E" data-action="magnific"></a></li>
								<li><a data-youtube-id="wA1cMMe9ccA" data-action="magnific"></a></li>
								<li><a data-youtube-id="ZeLy2z29w5E" data-action="magnific"></a></li>
							    <li><a data-youtube-id="sG9XELyani4" data-action="magnific"></a></li>
							    <li><a data-youtube-id="JzpfX9Bgfwc" data-action="magnific"></a></li>
							    <li><a data-youtube-id="vR1xPpRi3r0" data-action="magnific"></a></li>
							    <li><a data-youtube-id="OcOn2z0aCog&t=187s" data-action="magnific"></a></li>

							</ul>
							<div class="sharing">
								<ul class="social">
									<li>
										<a href="https://www.youtube.com/@globalministries-dailybread" target="_blank" class="youtube">
											<i class="ion-social-youtube"></i> For More Videos, Subscribe
										</a>
									</li>
								</ul>
							</div>
						</div>
					</aside>
					</div>
				</div>
			</div>
		</section>
