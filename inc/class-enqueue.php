<?php
if (!defined('ABSPATH')) {
    exit;
}

class BM_Enqueue {

    private $settings;

    public function __construct(BM_Settings $settings) {
        $this->settings = $settings;
    }

    public function enqueue_frontend() {
        if (is_admin()) {
            return;
        }

        if (!bm_enqueue_conditionally()) {
            return;
        }

        wp_enqueue_style(
            'bookmark-nav',
            BM_PLUGIN_URL . 'assets/css/bookmark-nav.css',
            array(),
            BM_VERSION
        );

        wp_enqueue_script(
            'bookmark-nav-memo',
            BM_PLUGIN_URL . 'assets/js/components/memo-card.js',
            array(),
            BM_VERSION,
            true
        );

        wp_enqueue_script(
            'bookmark-nav',
            BM_PLUGIN_URL . 'assets/js/bookmark-nav.js',
            array('bookmark-nav-memo'),
            BM_VERSION,
            true
        );

        $js_vars = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'ajax_nonce' => wp_create_nonce('wp_rest'),
            'homeUrl' => home_url(),
            'restUrl' => rest_url('bm/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'isOnenav' => bm_is_onenav_active(),
            'userLoggedIn' => is_user_logged_in(),
            'userId' => get_current_user_id(),
        );

        $nav_manager = BM_Core::get_instance()->get_nav_manager();
        $wp_page_id = get_the_ID();
        $user_id = get_current_user_id();
        if ($wp_page_id) {
            $init_data = $nav_manager->get_init_data($wp_page_id, null, $user_id);
            $js_vars['initData'] = $init_data;
        }

        wp_localize_script('bookmark-nav', 'bmVars', $js_vars);
    }

    public function enqueue_admin($hook) {
        if (!is_admin()) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen) {
            return;
        }

        $is_plugin_page = strpos($screen->id, 'bookmark-nav') !== false
            || strpos($screen->id, 'bm_') !== false
            || ($screen->post_type === BM_POST_TYPE)
            || ($screen->taxonomy === BM_TAXONOMY_CATEGORY)
            || ($screen->taxonomy === BM_TAXONOMY_TAG);

        if (!$is_plugin_page) {
            return;
        }

        wp_enqueue_style(
            'bookmark-nav-admin',
            BM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            BM_VERSION
        );

        wp_enqueue_script(
            'bookmark-nav-admin',
            BM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            BM_VERSION,
            true
        );

        wp_enqueue_style('wp-color-picker');

        if ($screen->post_type === BM_POST_TYPE) {
            wp_enqueue_media();
        }

        wp_localize_script('bookmark-nav-admin', 'bmAdminVars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'ajax_nonce' => wp_create_nonce('bm_admin_nonce'),
        ));
    }
}
