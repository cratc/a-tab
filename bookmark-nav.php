<?php
/**
 * Plugin Name: a-tab
 * Plugin URI: https://github.com/cratc/a-tab
 * Description: 基于 OneNav 网址数据，参考 mtab 设计的高度可自定义书签导航页插件。新建页面并选择"书签导航页"模板即可使用。
 * Version: 1.0.1
 * Author: craved
 * Author URI: https://jovz.cn
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bookmark-nav
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BM_VERSION', '1.0.1');
define('BM_PLUGIN_FILE', __FILE__);
define('BM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BM_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('BM_PLUGIN_SLUG', 'bookmark-nav');
define('BM_OPTION_PREFIX', 'bm_');
define('BM_POST_TYPE', 'bm_bookmark');
define('BM_TAXONOMY_CATEGORY', 'bm_category');
define('BM_TAXONOMY_TAG', 'bm_tag');
define('BM_ONENAV_POST_TYPE', 'sites');
define('BM_ONENAV_TAXONOMY_CATEGORY', 'favorites');
define('BM_ONENAV_TAXONOMY_TAG', 'sitetag');

require_once BM_PLUGIN_DIR . 'inc/functions/helpers.php';
require_once BM_PLUGIN_DIR . 'inc/class-db.php';
require_once BM_PLUGIN_DIR . 'inc/class-settings.php';
require_once BM_PLUGIN_DIR . 'inc/class-data-source.php';
require_once BM_PLUGIN_DIR . 'inc/class-nav-manager.php';
require_once BM_PLUGIN_DIR . 'inc/class-core.php';
require_once BM_PLUGIN_DIR . 'inc/class-bookmark-post-type.php';
require_once BM_PLUGIN_DIR . 'inc/class-enqueue.php';
require_once BM_PLUGIN_DIR . 'inc/class-icon-fetcher.php';
require_once BM_PLUGIN_DIR . 'inc/class-rest-api.php';
require_once BM_PLUGIN_DIR . 'inc/class-link-checker.php';
require_once BM_PLUGIN_DIR . 'inc/class-importer.php';

if (is_admin()) {
    require_once BM_PLUGIN_DIR . 'inc/admin/class-admin-page.php';
    require_once BM_PLUGIN_DIR . 'inc/admin/class-admin-bookmark.php';
    require_once BM_PLUGIN_DIR . 'inc/admin/class-admin-search.php';
    require_once BM_PLUGIN_DIR . 'inc/admin/class-admin-theme.php';
}

register_activation_hook(__FILE__, 'bm_activate_plugin');
register_deactivation_hook(__FILE__, 'bm_deactivate_plugin');

function bm_activate_plugin() {
    $core = BM_Core::get_instance();
    $core->activate();
}

function bm_deactivate_plugin() {
    $core = BM_Core::get_instance();
    $core->deactivate();
}

function bm_init_plugin() {
    $instance = BM_Core::get_instance();
    $instance->init();
    return $instance;
}

add_action('plugins_loaded', 'bm_init_plugin');
