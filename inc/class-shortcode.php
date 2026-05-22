<?php
if (!defined('ABSPATH')) {
    exit;
}

class BM_Shortcode {

    private $nav_manager;

    private $data_source;

    private $settings;

    public function __construct(BM_Nav_Manager $nav_manager, BM_Data_Source $data_source, BM_Settings $settings) {
        $this->nav_manager = $nav_manager;
        $this->data_source = $data_source;
        $this->settings = $settings;
    }

    public function register_shortcodes() {
        add_shortcode('bookmark_nav', array($this, 'render_nav'));
    }

    public function render_nav($atts) {
        $atts = shortcode_atts(array(
            'show_clock' => 'true',
            'show_dock' => 'true',
            'show_sidebar' => 'true',
            'default_group' => '',
        ), $atts, 'bookmark_nav');

        $page_id = get_the_ID();

        $data = $this->nav_manager->get_init_data($page_id);

        if (!empty($atts['show_clock']) && $atts['show_clock'] === 'false') {
            $data['settings']['clock.visible'] = 0;
        }
        if (!empty($atts['show_dock']) && $atts['show_dock'] === 'false') {
            $data['settings']['dock.visible'] = 0;
        }
        if (!empty($atts['show_sidebar']) && $atts['show_sidebar'] === 'false') {
            $data['settings']['sidebar.visible'] = 0;
        }

        ob_start();
        include BM_PLUGIN_DIR . 'templates/nav-page.php';
        return ob_get_clean();
    }
}
