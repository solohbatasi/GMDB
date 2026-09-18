<?php 
    date_default_timezone_set('Africa/Nairobi');

    // require_once 'initialize.php';
    // require_once 'essentials.php';
    require_once 'inc/router.php';
    if (gdmb_normalize_route($_GET['p'] ?? 'home') === 'cart' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once 'inc/cart.php';
        gdmb_cart_handle_request();
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
