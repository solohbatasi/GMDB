<?php 
    date_default_timezone_set('Africa/Nairobi');

    // require_once 'initialize.php';
    // require_once 'essentials.php';
    require_once 'inc/router.php';
    $currentRoute = gdmb_normalize_route($_GET['p'] ?? 'home');
    if ($currentRoute === 'store-diagnostics') {
        require 'store-diagnostics.php';
        exit;
    }
    if (in_array($currentRoute, ['cart', 'checkout'], true)) {
        require_once 'inc/cart.php';
        if ($currentRoute === 'cart' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            gdmb_cart_handle_request();
        }
        if ($currentRoute === 'checkout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            gdmb_checkout_handle_request();
        }
        gdmb_cart_boot();
    }
    require_once 'inc/header.php';
    $page = gdmb_resolve_public_route($_GET['p'] ?? 'home');
?>
<body class="skin-orange">
		  
     <!-- preloader area start -->
   
        <?php
        //  require_once 'inc/sidebar.php';
         require_once 'inc/topnavbar.php';
         if ($page === null) {
            include '404.html';
         } else {
            include $page;
         }
         require_once 'inc/footer.php';
        ?>
    <?php
        // require_once 'inc/modal.php';  
        require_once 'inc/script.php'; 

    ?>
    
  </body>
</html>
