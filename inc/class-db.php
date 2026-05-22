<?php
if (!defined('ABSPATH')) {
    exit;
}

define('BM_DB_VERSION', '2.3.0');

class BM_DB {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function get_table($name) {
        global $wpdb;
        return $wpdb->prefix . 'bm_' . $name;
    }

    private function get_charset_collate() {
        global $wpdb;
        return $wpdb->get_charset_collate();
    }

    public function create_tables() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $this->get_charset_collate();

        $table_pages = $this->get_table('pages');
        $sql_pages = "CREATE TABLE $table_pages (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(200) NOT NULL DEFAULT '',
            icon VARCHAR(200) DEFAULT '',
            sort_order INT DEFAULT 0,
            is_default TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_sort (sort_order)
        ) $charset_collate;";
        dbDelta($sql_pages);

        $table_nav_items = $this->get_table('nav_items');
        $sql_nav_items = "CREATE TABLE $table_nav_items (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(200) NOT NULL DEFAULT '',
            url VARCHAR(500) NOT NULL DEFAULT '',
            icon VARCHAR(500) NOT NULL DEFAULT '',
            `describe` TEXT DEFAULT NULL,
            source_type ENUM('onenav','custom','local','card','memo') DEFAULT 'onenav',
            source_id BIGINT UNSIGNED DEFAULT 0,
            page_id BIGINT UNSIGNED DEFAULT 0,
            group_id BIGINT UNSIGNED DEFAULT 0,
            sort_order INT DEFAULT 0,
            layout VARCHAR(10) DEFAULT 'auto',
            in_dock TINYINT(1) DEFAULT 0,
            dock_sort INT DEFAULT 0,
            bg_color VARCHAR(20) DEFAULT '',
            text_icon VARCHAR(30) DEFAULT '',
            open_in_iframe TINYINT(1) DEFAULT 0,
            component_id VARCHAR(50) DEFAULT '',
            component_config TEXT DEFAULT NULL,
            status ENUM('active','inactive') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_page (page_id),
            KEY idx_group (group_id),
            KEY idx_status (status),
            KEY idx_source (source_type),
            KEY idx_sort (sort_order),
            KEY idx_dock (in_dock, dock_sort),
            KEY idx_component (component_id)
        ) $charset_collate;";
        dbDelta($sql_nav_items);

        $table_groups = $this->get_table('groups');
        $sql_groups = "CREATE TABLE $table_groups (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(200) NOT NULL DEFAULT '',
            icon VARCHAR(200) DEFAULT 'fas fa-folder',
            page_id BIGINT UNSIGNED DEFAULT 0,
            sort_order INT DEFAULT 0,
            is_system TINYINT(1) DEFAULT 0,
            is_folder TINYINT(1) DEFAULT 0,
            layout VARCHAR(10) DEFAULT NULL,
            columns INT DEFAULT NULL,
            icon_size ENUM('sm','md','lg') DEFAULT NULL,
            show_text TINYINT(1) DEFAULT NULL,
            text_color VARCHAR(20) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_page (page_id)
        ) $charset_collate;";
        dbDelta($sql_groups);

        $table_nav_config = $this->get_table('nav_config');
        $sql_nav_config = "CREATE TABLE $table_nav_config (
            id INT NOT NULL AUTO_INCREMENT,
            config_key VARCHAR(100) NOT NULL,
            config_value TEXT DEFAULT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY config_key (config_key)
        ) $charset_collate;";
        dbDelta($sql_nav_config);

        $table_card_components = $this->get_table('card_components');
        $sql_card_components = "CREATE TABLE $table_card_components (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            component_id VARCHAR(50) NOT NULL,
            title VARCHAR(100) NOT NULL DEFAULT '',
            description TEXT DEFAULT NULL,
            icon VARCHAR(200) DEFAULT '',
            preview_image VARCHAR(500) DEFAULT '',
            category VARCHAR(50) DEFAULT 'general',
            version VARCHAR(20) DEFAULT '1.0.0',
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY component_id (component_id)
        ) $charset_collate;";
        dbDelta($sql_card_components);

        $this->migrate_add_page_id();
        update_option('bm_db_version', BM_DB_VERSION);
    }

    private function migrate_add_page_id() {
        global $wpdb;

        $table_nav_items = $this->get_table('nav_items');
        $col = $wpdb->get_results("SHOW COLUMNS FROM {$table_nav_items} LIKE 'page_id'");
        if (empty($col)) {
            $wpdb->query("ALTER TABLE {$table_nav_items} ADD COLUMN page_id BIGINT UNSIGNED DEFAULT 0 AFTER source_id, ADD KEY idx_page (page_id)");
        }

        $table_groups = $this->get_table('groups');
        $col2 = $wpdb->get_results("SHOW COLUMNS FROM {$table_groups} LIKE 'page_id'");
        if (empty($col2)) {
            $wpdb->query("ALTER TABLE {$table_groups} ADD COLUMN page_id BIGINT UNSIGNED DEFAULT 0 AFTER icon, ADD KEY idx_page (page_id)");
        }

        $col3 = $wpdb->get_results("SHOW COLUMNS FROM {$table_groups} LIKE 'is_folder'");
        if (empty($col3)) {
            $wpdb->query("ALTER TABLE {$table_groups} ADD COLUMN is_folder TINYINT(1) DEFAULT 0 AFTER is_system");
        }

        $col4 = $wpdb->get_results("SHOW COLUMNS FROM {$table_groups} LIKE 'layout'");
        if (empty($col4)) {
            $wpdb->query("ALTER TABLE {$table_groups} ADD COLUMN layout VARCHAR(10) DEFAULT NULL AFTER is_folder");
        }
    }

    public function insert_default_data() {
        global $wpdb;

        $table_pages = $this->get_table('pages');
        $existing_pages = $wpdb->get_var("SELECT COUNT(*) FROM {$table_pages}");

        if (intval($existing_pages) === 0) {
            $wpdb->insert(
                $table_pages,
                array(
                    'title'      => '默认',
                    'icon'       => '🏠',
                    'sort_order' => 0,
                    'is_default' => 1,
                ),
                array('%s', '%s', '%d', '%d')
            );
        }

        $table_config = $this->get_table('nav_config');

        $config_defaults = array(
            'sidebar.mode'       => 'always',
            'sidebar.active_page' => '1',
            'dock.enabled'       => '1',
            'dock.max_items'     => '8',
        );

        foreach ($config_defaults as $key => $value) {
            $wpdb->query($wpdb->prepare(
                "INSERT INTO {$table_config} (config_key, config_value) VALUES (%s, %s)
                 ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)",
                $key, $value
            ));
        }
    }

    public static function check_db_version() {
        $stored_version = get_option('bm_db_version', '0.0.0');

        if (version_compare($stored_version, BM_DB_VERSION, '<')) {
            $instance = self::get_instance();
            $instance->create_tables();
            $instance->insert_default_data();
        }
    }

    public function drop_tables() {
        global $wpdb;

        $tables = array(
            $this->get_table('pages'),
            $this->get_table('nav_items'),
            $this->get_table('groups'),
            $this->get_table('nav_config'),
            $this->get_table('card_components'),
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        delete_option('bm_db_version');
    }
}
