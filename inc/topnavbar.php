<?php
require_once __DIR__ . '/store_catalog.php';

$gdmbBackendBase = rtrim(getenv('GDMB_BACKEND_BASE') ?: './backend', '/');
$gdmbPickupResponse = gdmb_store_api_get('pickup-locations');
$gdmbPickupLocations = is_array($gdmbPickupResponse['data'] ?? null) ? $gdmbPickupResponse['data'] : [];
$gdmbHeaderPickup = $gdmbPickupLocations[0] ?? null;
$gdmbHeaderPickupMapUrl = is_array($gdmbHeaderPickup) ? gdmb_pickup_map_url($gdmbHeaderPickup) : '';
$gdmbNavBooks = gdmb_store_books(['limit' => 15]);
$gdmbNavBookColumns = array_chunk($gdmbNavBooks, 5);
$gdmbNavBookColumnTitles = ['Featured Books', 'More Titles', 'Explore More'];
?>
<header class="primary">
			<div class="firstbar" style="background-color:#112243;">
				<div class="container">
					<div class="row">
						<div class="col-md-3 col-sm-12">
							<div class="brand">
								<a href="./">
									<img loading="lazy"src="images/1720010940_church-removebg-preview.png" alt="Magz Logo">
								</a>
							</div>						
						</div>
						<div class="col-md-6 col-sm-12">
							<form class="search" autocomplete="off">
								<div class="block">
									<div class="block-body">
										<?php if (is_array($gdmbHeaderPickup)): ?>
											<div style="color:white; line-height:1.45;">
												<strong><?php echo gdmb_e($gdmbHeaderPickup['name'] ?? 'Pickup point'); ?></strong>
												<div><?php echo gdmb_e(trim(($gdmbHeaderPickup['address'] ?? '') . ', ' . ($gdmbHeaderPickup['city'] ?? '') . ', ' . ($gdmbHeaderPickup['county'] ?? ''), ', ')); ?></div>
												<?php if (! empty($gdmbHeaderPickup['instructions'])): ?><div style="opacity:.86;"><?php echo gdmb_e($gdmbHeaderPickup['instructions']); ?></div><?php endif; ?>
												<div style="margin-top:6px; opacity:.92;">
													<div><strong>Opening Hours</strong></div>
													<div>Monday - Friday: 9am - 5pm</div>
													<div>Saturday: 10am - 2pm</div>
													<div>Sunday: 8am - 8pm (Church Services)</div>
												</div>
											</div>
										<?php else: ?>
											<div style="color:white;">Pickup details will be available before checkout.</div>
										<?php endif; ?>
									</div>
								</div>
								<div class="help-block">
									<div>Popular:</div>
									<ul>
										<li><a href="./?p=books/book1" style="color:white;">A Theory of Lay Ministry Praxis</a></li>
										<li><a href="#" style="color:white;">Duke Randolph</a></li>
										<li><a href="#" style="color:white;">Empowerment missions</a></li>
									</ul>
								</div>
							</form>								
						</div>
						<div class="col-md-3 col-sm-12 text-right">
							<div class="block">
								<div class="block-body" style="padding-top:14px;">
									<ul class="social trp">
										<li><a href="https://web.facebook.com/people/Global-Ministries-Daily-Bread/61570234694399/?mibextid=rS40aB7S9Ucbxw6v" target="_blank" class="facebook"><svg><rect width="0" height="0"/></svg><i class="fab fa-facebook"></i></a></li>
										<li><a href="https://www.tiktok.com/@global.ministries4?_t=ZG-8tK8ue6DcEj&_r=1" target="_blank" class="tumblr"><svg><rect width="0" height="0"/></svg><i class="fab fa-tiktok"></i></a></li>
										<li><a href="https://www.youtube.com/@globalministriesdailybread" target="_blank" class="youtube"><svg><rect width="0" height="0"/></svg><i class="fab fa-youtube"></i></a></li>
										<li><a href="#" class="twitter"><svg><rect width="0" height="0"/></svg><i class="fab fa-x-twitter"></i></a></li>
									</ul>
									<div style="color:white; margin-top:10px; line-height:1.7; font-size:12px; text-align:left; display:inline-block; font-family:'Raleway', sans-serif; font-weight:700; letter-spacing:.5px;">
										<div><i class="ion-ios-telephone-outline" style="display:inline-block; width:18px;"></i>+254722780410</div>
										<div><i class="ion-ios-telephone-outline" style="display:inline-block; width:18px;"></i>+254724207817</div>
										<div><i class="ion-ios-email-outline" style="display:inline-block; width:18px;"></i>info@globalministriesdailybread.org</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Start nav -->
			<nav class="menu">
				<div class="container">
					
					<div class="mobile-toggle">
						<a href="#" data-toggle="menu" data-target="#menu-list"><i class="ion-navicon-round"></i></a>
					</div>
					<div class="mobile-toggle">
						<a href="#" data-toggle="sidebar" data-target="#sidebar"><i class="ion-ios-arrow-left"></i></a>
					</div>
					<div id="menu-list">
						<ul class="nav-list">
							<li class="for-tablet nav-title"><a  >Menu</a></li>
							<li class="for-tablet"><a href="<?php echo gdmb_e($gdmbBackendBase . '/login'); ?>"  >Login</a></li>
							<li><a href="./" >Home</a></li>
							<li class="dropdown magz-dropdown">
								<a href="./?p=about" >About <i class="ion-ios-arrow-right"></i></a>
								<ul class="dropdown-menu">
									<li><a href="./?p=mission">Our Mission & Vision</a></li>
									<li><a href="./?p=core_values">Core Values</a></li>
									<li><a href="./?p=team">Our Team</a></li>
									<li><a href="./?p=503">History</a></li>
									
								</ul>
							</li>
							<li class="dropdown magz-dropdown"><a href="#" >Bible Blogs<i class="ion-ios-arrow-right"></i></a>
								<ul class="dropdown-menu">
									<li><a href="./?p=blogs/shepherd">Lord is my Shepherd</a></li>
									<li><a href="./?p=blogs/faith">The power of faith</a></li>
									<li><a href="./?p=blogs/baptism">Saved & Condemned</a></li>
								</ul>
							</li>
							<li class="dropdown magz-dropdown magz-dropdown-megamenu"><a href="#">Events<i class="ion-ios-arrow-right"></i> <div class="badge">Upcoming</div></a>
								<div class="dropdown-menu megamenu">
									<div class="megamenu-inner">
										<div class="row">
											<div class="col-md-3">
												<div class="row">
													<div class="col-md-12">
														<h2 class="megamenu-title">Upcoming</h2>
													</div>
												</div>
												<ul class="vertical-menu">
													<li><a href="./?p=events/empowerment-conference"><i class="ion-ios-circle-outline"></i> Your Time of Empowerment Conference</a></li>
													<li><a href="./?p=503"><i class="ion-ios-circle-outline"></i> Revival Crusade</a></li>
													<li><a href="./?p=503"><i class="ion-ios-circle-outline"></i> Youth Worship Night</a></li>
													<li><a href="./?p=503"><i class="ion-ios-circle-outline"></i> Prayer & Fasting Week</a></li>
													<li><a href="./?p=503"><i class="ion-ios-circle-outline"></i> Annual Church Conference</a></li>
													<li><a href="./?p=503"><i class="ion-ios-circle-outline"></i> Sunday School Fun Day</a></li>
												</ul>

											</div>
											<div class="col-md-9">
												<div class="row">
													<div class="col-md-12">
														<h2 class="megamenu-title">Featured Posts</h2>
													</div>
												</div>
												<div class="row">
													<article class="article col-md-4 mini">
														<div class="inner">
															<figure>
																<a href="./?p=events/empowerment-conference">
																	<img src="images/events/empowerment-conference-main.jpeg" loading="lazy" alt="Your Time of Empowerment Conference">
																</a>
															</figure>
															<div class="padding">
																<div class="detail">
																	<div class="time">August 14-15, 2026</div>
																	<div class="category"><a href="./?p=events/empowerment-conference">Conference</a></div>
																</div>
																<h2><a href="./?p=events/empowerment-conference">Your Time of Empowerment Conference</a></h2>
															</div>
														</div>
													</article>
												</div>
											</div>
										</div>								
									</div>
								</div>
							</li>
							<li class="dropdown magz-dropdown magz-dropdown-megamenu"><a href="./?p=books" >Books<i class="ion-ios-arrow-right"></i></a>
								<div class="dropdown-menu megamenu">
									<div class="megamenu-inner">
										<div class="row">
											<?php foreach ($gdmbNavBookColumns as $columnIndex => $bookColumn): ?>
												<div class="col-md-3">
													<h2 class="megamenu-title"><?php echo gdmb_e($gdmbNavBookColumnTitles[$columnIndex] ?? 'Books'); ?></h2>
													<ul class="vertical-menu">
														<?php if ($columnIndex === 0): ?>
															<li><a href="./?p=books"><strong>View All Books</strong></a></li>
														<?php endif; ?>
														<?php foreach ($bookColumn as $book): ?>
															<?php if (! empty($book['title']) && ! empty($book['slug'])): ?>
																<li><a href="./?p=book&amp;slug=<?php echo rawurlencode($book['slug']); ?>"><?php echo gdmb_e($book['title']); ?></a></li>
															<?php endif; ?>
														<?php endforeach; ?>
													</ul>
												</div>
											<?php endforeach; ?>
											<!-- <div class="col-md-3">
												<h2 class="megamenu-title">Column 4</h2>
												<ul class="vertical-menu">
													<li><a href="./?p=503">Book 16</a></li>
													<li><a href="./?p=503">Book 17</a></li>
													<li><a href="./?p=503">Book 18</a></li>
													<li><a href="./?p=503">Book 19</a></li>
													<li><a href="./?p=503">Book 20</a></li>
												</ul>
											</div> -->
										</div>
									</div>
								</div>
							</li>
							<li class="dropdown magz-dropdown"><a href="#">Appointments <i class="ion-ios-arrow-right"></i></a>
								<ul class="dropdown-menu">
								
								</ul>
							</li>
							<li class="dropdown magz-dropdown"><a href="#">Donations <i class="ion-ios-arrow-right"></i></a>
								<ul class="dropdown-menu">
								
								</ul>
							</li>
							<li><a href="./?p=videos">Videos</a></li>
							<li><a href="./?p=contact">Contact us</a></li>
							<li><a href="https://cs2.rcnoc.com:2096/logout/?locale=en" target="_blank">Email</a></li>
							<li><a href="<?php echo gdmb_e($gdmbBackendBase . '/login'); ?>">Login</a></li>

						</ul>
					</div>
				</div>
			</nav>
			<!-- End nav -->
		</header>
