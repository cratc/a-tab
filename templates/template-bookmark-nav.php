<?php
if (!defined('ABSPATH')) {
    exit;
}

try {
    $nav_manager = BM_Core::get_instance()->get_nav_manager();
    $wp_page_id = get_the_ID();
    $data = $nav_manager->get_init_data($wp_page_id);
} catch (Exception $e) {
    if (function_exists('error_log')) {
        error_log('BM Plugin template error: ' . $e->getMessage());
    }
    $data = array(
        'page_id' => 0,
        'settings' => array(),
        'groups' => array(),
        'nav_items' => array(),
        'pages' => array(),
        'active_page_id' => 0,
        'current_user' => array('logged_in' => false, 'avatar_url' => '', 'display_name' => '', 'login_url' => wp_login_url(home_url($_SERVER['REQUEST_URI'] ?? '/')), 'logout_url' => wp_logout_url(home_url($_SERVER['REQUEST_URI'] ?? '/'))),
        'dock_items' => array(),
        'user_logged_in' => false,
    );
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title('|', true, 'right'); bloginfo('name'); ?></title>
    <link rel="preload" href="<?php echo esc_url(BM_PLUGIN_URL . 'assets/css/bookmark-nav.css'); ?>" as="style">
    <link rel="preload" href="<?php echo esc_url(BM_PLUGIN_URL . 'assets/js/bookmark-nav.js'); ?>" as="script">
    <?php wp_head(); ?>
    <style>
        body, html { margin: 0; padding: 0; height: 100%; overflow: hidden; }
        body.admin-bar .bm-nav-page { top: 32px; height: calc(100vh - 32px); }
        @media (max-width: 782px) { body.admin-bar .bm-nav-page { top: 46px; height: calc(100vh - 46px); } }
        .bm-nav-page{width:100vw;min-height:100vh;overflow-x:hidden;position:relative}
        .bm-wallpaper{position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;background-size:cover;background-position:center;background-repeat:no-repeat}
        .bm-sidebar{position:fixed;top:0;left:0;width:72px;height:100vh;z-index:200;display:flex;flex-direction:column;padding:16px 0;overflow-y:auto;overflow-x:hidden;background:rgba(255,255,255,0.08);backdrop-filter:blur(24px) saturate(180%);-webkit-backdrop-filter:blur(24px) saturate(180%);border-right:1px solid rgba(255,255,255,0.12)}
        .bm-main-content{margin-left:72px;position:relative;z-index:1;min-height:100vh}
        .bm-dock-bar{position:fixed;bottom:16px;left:50%;transform:translateX(-50%);z-index:100}
        .bm-settings-panel{position:fixed;left:0;top:0;width:400px;height:100vh;z-index:4000;transform:translateX(-100%);display:flex;flex-direction:column}
        .bm-modal-overlay{position:fixed;inset:0;z-index:5000;display:none;align-items:center;justify-content:center}
        .bm-context-menu-wrapper{position:fixed;z-index:9999;pointer-events:none;display:none}
        @media(max-width:767px){.bm-sidebar{transform:translateX(-100%);width:220px}.bm-main-content{margin-left:0}.bm-mobile-toggle{display:flex!important}}
    </style>
</head>
<body <?php body_class(); ?>>
<?php
try {
    include BM_PLUGIN_DIR . 'templates/nav-page.php';
} catch (Exception $e) {
    if (function_exists('error_log')) {
        error_log('BM Plugin render error: ' . $e->getMessage());
    }
    echo '<div style="padding:40px;text-align:center"><h2>页面渲染出错</h2><p>请检查插件配置或联系管理员</p></div>';
}
?>
<?php wp_footer(); ?>
</body>
</html>
