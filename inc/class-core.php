<?php
if (!defined('ABSPATH')) {
    exit;
}

class BM_Core {

    private static $instance = null;

    private $data_source = null;

    private $settings = null;

    private $nav_manager = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->settings = new BM_Settings();
        $this->data_source = new BM_Data_Source($this->settings);
        $this->nav_manager = new BM_Nav_Manager($this->data_source, $this->settings);
    }

    public function init() {
        BM_DB::check_db_version();

        add_action('init', array($this, 'load_textdomain'));
        add_action('init', array($this, 'setup_post_type'), 99);
        add_filter('theme_page_templates', array($this, 'register_page_template'));
        add_filter('template_include', array($this, 'load_page_template'));
        add_filter('login_redirect', array($this, 'handle_login_redirect'), 10, 3);
        add_filter('logout_redirect', array($this, 'handle_logout_redirect'), 10, 3);

        $enqueue = new BM_Enqueue($this->settings);
        add_action('wp_enqueue_scripts', array($enqueue, 'enqueue_frontend'));
        add_action('admin_enqueue_scripts', array($enqueue, 'enqueue_admin'));

        if (class_exists('BM_REST_API')) {
            $rest_api = new BM_REST_API($this->nav_manager);
            add_action('rest_api_init', array($rest_api, 'register_routes'));
        }

        if ($this->settings->get('link_check_enabled', false)) {
            $link_checker = new BM_Link_Checker($this->data_source);
            add_action('bm_link_check_event', array($link_checker, 'run_check'));
        }

        add_action('wp_ajax_bm_load_tab', array($this, 'ajax_load_tab'));
        add_action('wp_ajax_nopriv_bm_load_tab', array($this, 'ajax_load_tab'));

        if (is_admin()) {
            $admin_page = new BM_Admin_Page($this->settings);
            add_action('admin_menu', array($admin_page, 'add_menu_pages'));

            $admin_bookmark = new BM_Admin_Bookmark($this->data_source);
            add_action('admin_menu', array($admin_bookmark, 'add_menu_page'));

            $admin_search = new BM_Admin_Search($this->settings);
            $admin_theme = new BM_Admin_Theme($this->settings);
        }
    }

    public function setup_post_type() {
        if (!$this->data_source->is_onenav_mode()) {
            $post_type = new BM_Bookmark_Post_Type();
            $post_type->register_post_type();
            $post_type->register_taxonomies();
            add_action('add_meta_boxes', array($post_type, 'add_meta_boxes'));
            add_action('save_post', array($post_type, 'save_meta_data'));
        }
    }

    public function activate() {
        $this->settings->set_defaults();

        $db = BM_DB::get_instance();
        $db->create_tables();
        $db->insert_default_data();

        if (!wp_next_scheduled('bm_link_check_event')) {
            wp_schedule_event(time(), 'daily', 'bm_link_check_event');
        }

        update_option('bm_plugin_version', BM_VERSION);
        update_option('bm_db_version', BM_DB_VERSION);
    }

    public function deactivate() {
        wp_clear_scheduled_hook('bm_link_check_event');
    }

    public function load_textdomain() {
        load_plugin_textdomain('bookmark-nav', false, dirname(BM_PLUGIN_BASENAME) . '/languages');
    }

    public function is_onenav_active() {
        $source = $this->settings->get('data_source', 'auto');
        if ($source === 'standalone') {
            return false;
        }
        return post_type_exists(BM_ONENAV_POST_TYPE) && taxonomy_exists(BM_ONENAV_TAXONOMY_CATEGORY);
    }

    public function get_data_source() {
        return $this->data_source;
    }

    public function get_settings() {
        return $this->settings;
    }

    public function get_nav_manager() {
        return $this->nav_manager;
    }

    public function ajax_load_tab() {
        check_ajax_referer('bm_ajax_nonce', 'nonce');

        $taxonomy = sanitize_key($_POST['taxonomy'] ?? '');
        $term_id = absint($_POST['term_id'] ?? 0);
        $style = sanitize_key($_POST['style'] ?? 'max');
        $columns = sanitize_key($_POST['columns'] ?? '');

        if (!$taxonomy || !$term_id) {
            wp_send_json_error(array('message' => __('参数错误', 'bookmark-nav')));
        }

        $bookmarks = $this->data_source->get_bookmarks_by_term($term_id, $taxonomy);
        $settings = $this->settings;

        ob_start();
        foreach ($bookmarks as $bookmark) {
            bm_get_template_part('card-' . $style, compact('bookmark', 'columns', 'settings'));
        }
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }

    public function register_page_template($templates) {
        $templates['templates/template-bookmark-nav.php'] = __('书签导航页', 'bookmark-nav');
        return $templates;
    }

    public function load_page_template($template) {
        if (is_page()) {
            $page_template = get_page_template_slug();
            if ($page_template === 'templates/template-bookmark-nav.php') {
                $plugin_template = BM_PLUGIN_DIR . 'templates/template-bookmark-nav.php';
                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
            }
        }
        return $template;
    }

    public function handle_login_redirect($redirect_to, $requested_redirect_to, $user) {
        if (!is_wp_error($user) && $requested_redirect_to && $requested_redirect_to !== admin_url() && $requested_redirect_to !== '') {
            return $requested_redirect_to;
        }
        if (!is_wp_error($user) && $redirect_to && $redirect_to !== admin_url() && $redirect_to !== '') {
            return $redirect_to;
        }
        return home_url();
    }

    public function handle_logout_redirect($redirect_to, $requested_redirect_to, $user) {
        if ($requested_redirect_to && $requested_redirect_to !== admin_url() && $requested_redirect_to !== '') {
            return $requested_redirect_to;
        }
        return home_url();
    }
}
