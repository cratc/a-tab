<?php
if (!defined('ABSPATH')) {
    exit;
}

class BM_Admin_Page {

    private $settings;

    public function __construct(BM_Settings $settings) {
        $this->settings = $settings;
    }

    public function add_menu_pages() {
        add_menu_page(
            __('a-tab 设置', 'bookmark-nav'),
            __('a-tab', 'bookmark-nav'),
            'manage_options',
            'bookmark-nav-settings',
            array($this, 'render_settings_page'),
            'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iIzU5NUNmZiI+PHBhdGggZD0iTTE0IDJINmMtMS4xIDAtMiAuOS0yIDJ2MTZjMCAxLjEuOSAyIDIgMmgxMmMxLjEgMCAyLS45IDItMlY4bC02LTZ6bS0uNSA3aDJ2MWgtMnYtMXptMCAzaDJ2MWgtMnYtMXpNMTEgMTVIOXYtMWgydjF6bTQtNGMuNiAwIDEgLjQgMSAxcy0uNCAxLTEgMUgzYy0uNiAwLTEtLjQtMS0xcy40LTEgMS0xaDR6Ii8+PC9zdmc+',
            56
        );

        add_submenu_page(
            'bookmark-nav-settings',
            __('通用设置', 'bookmark-nav'),
            __('通用设置', 'bookmark-nav'),
            'manage_options',
            'bookmark-nav-settings',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            'bookmark-nav-settings',
            __('数据导入', 'bookmark-nav'),
            __('数据导入', 'bookmark-nav'),
            'manage_options',
            'bookmark-nav-import',
            array($this, 'render_import_page')
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['bm_save_settings']) && wp_verify_nonce($_POST['_bm_nonce'], 'bm_save_settings')) {
            $this->save_settings($_POST);
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('设置已保存', 'bookmark-nav') . '</p></div>';
        }

        $settings = $this->settings->get_all();
        $is_onenav = bm_is_onenav_active();
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';

        ?>
        <div class="wrap bm-admin-wrap">
            <h1><?php esc_html_e('a-tab 设置', 'bookmark-nav'); ?>
                <span class="bm-version">v<?php echo esc_html(BM_VERSION); ?></span>
                <?php if ($is_onenav) : ?>
                <span class="bm-badge bm-badge--onenav"><?php esc_html_e('OneNav 兼容模式', 'bookmark-nav'); ?></span>
                <?php endif; ?>
            </h1>

            <nav class="nav-tab-wrapper bm-nav-tabs">
                <a href="?page=bookmark-nav-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('通用设置', 'bookmark-nav'); ?></a>
                <a href="?page=bookmark-nav-settings&tab=store" class="nav-tab <?php echo $active_tab === 'store' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('商店分类', 'bookmark-nav'); ?></a>
            </nav>

            <form method="post" action="">
                <?php wp_nonce_field('bm_save_settings', '_bm_nonce'); ?>
                <input type="hidden" name="bm_save_settings" value="1" />

                <div class="bm-admin-content">
                    <?php
                    switch ($active_tab) {
                        case 'store':
                            $this->render_store_tab();
                            break;
                        default:
                            $this->render_general_tab($settings, $is_onenav);
                            break;
                    }
                    ?>
                </div>

                <div class="bm-admin-footer">
                    <?php submit_button(__('保存设置', 'bookmark-nav'), 'primary large', 'submit', false); ?>
                </div>
            </form>
        </div>
        <?php
    }

    private function render_general_tab($settings, $is_onenav) {
        ?>
        <div class="bm-admin-section">
            <h2><?php esc_html_e('通用设置', 'bookmark-nav'); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('数据源', 'bookmark-nav'); ?></th>
                    <td>
                        <select name="data_source" class="regular-text">
                            <option value="auto" <?php selected($settings['data_source'], 'auto'); ?>><?php esc_html_e('自动检测', 'bookmark-nav'); ?></option>
                            <?php if ($is_onenav) : ?>
                            <option value="onenav" <?php selected($settings['data_source'], 'onenav'); ?>><?php esc_html_e('OneNav 主题', 'bookmark-nav'); ?></option>
                            <?php endif; ?>
                            <option value="standalone" <?php selected($settings['data_source'], 'standalone'); ?>><?php esc_html_e('独立模式', 'bookmark-nav'); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e('选择书签数据来源。自动检测会优先使用 OneNav 主题数据。', 'bookmark-nav'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('链接健康检查', 'bookmark-nav'); ?></th>
                    <td>
                        <label><input type="checkbox" name="link_check_enabled" value="1" <?php checked($settings['link_check_enabled']); ?> /> <?php esc_html_e('启用自动链接健康检查', 'bookmark-nav'); ?></label>
                        <p class="description"><?php esc_html_e('定期检查书签链接是否有效，标记死链。', 'bookmark-nav'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="bm-admin-section">
            <h2><?php esc_html_e('使用说明', 'bookmark-nav'); ?></h2>
            <div class="bm-usage-info">
                <p><?php esc_html_e('新建页面并选择"书签导航页"模板即可使用', 'bookmark-nav'); ?></p>
                <p><strong><?php esc_html_e('REST API', 'bookmark-nav'); ?></strong></p>
                <ul>
                    <li><code>/wp-json/bm/v1/bookmarks</code> — <?php esc_html_e('书签列表', 'bookmark-nav'); ?></li>
                    <li><code>/wp-json/bm/v1/categories</code> — <?php esc_html_e('分类列表', 'bookmark-nav'); ?></li>
                    <li><code>/wp-json/bm/v1/search?q=关键词</code> — <?php esc_html_e('搜索书签', 'bookmark-nav'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }

    private function render_store_tab() {
        $core = BM_Core::get_instance();
        $data_source = $core->get_data_source();
        $nav_manager = $core->get_nav_manager();

        $enabled_ids = array();
        $store_categories_raw = $nav_manager->get_config('store_categories', '');
        if (!empty($store_categories_raw)) {
            $enabled_ids = array_map('absint', explode(',', $store_categories_raw));
        }

        $categories = array();
        if (bm_is_onenav_active()) {
            $taxonomy = $data_source->get_taxonomy_category();
            $terms = get_terms(array(
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ));
            if (!is_wp_error($terms)) {
                $categories = $terms;
            }
        }

        ?>
        <div class="bm-admin-section">
            <h2><?php esc_html_e('商店分类', 'bookmark-nav'); ?></h2>
            <?php if (!bm_is_onenav_active()) : ?>
                <p class="description"><?php esc_html_e('需要启用 OneNav 数据源后才能管理商店分类。', 'bookmark-nav'); ?></p>
            <?php elseif (empty($categories)) : ?>
                <p class="description"><?php esc_html_e('暂无分类数据。', 'bookmark-nav'); ?></p>
            <?php else : ?>
                <p class="description"><?php esc_html_e('勾选要在前端商店中展示的分类。', 'bookmark-nav'); ?></p>
                <table class="form-table">
                    <?php foreach ($categories as $term) : ?>
                    <tr>
                        <th scope="row"><?php echo esc_html($term->name); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="store_categories[]" value="<?php echo esc_attr($term->term_id); ?>" <?php checked(in_array($term->term_id, $enabled_ids)); ?> />
                                <?php
                                printf(
                                    esc_html__('启用 %1$s（共 %2$d 个书签）', 'bookmark-nav'),
                                    esc_html($term->name),
                                    absint($term->count)
                                );
                                ?>
                            </label>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    private function save_settings($post_data) {
        $fields = array(
            'data_source' => array('sanitize' => 'sanitize_key'),
        );

        foreach ($fields as $field => $config) {
            if (isset($post_data[$field])) {
                $value = call_user_func($config['sanitize'], $post_data[$field]);
                $this->settings->set($field, $value);
            }
        }

        $checkboxes = array('link_check_enabled');
        foreach ($checkboxes as $field) {
            $this->settings->set($field, isset($post_data[$field]) && $post_data[$field] === '1');
        }

        $nav_manager = BM_Core::get_instance()->get_nav_manager();

        if (isset($post_data['store_categories']) && is_array($post_data['store_categories'])) {
            $ids = array_map('absint', $post_data['store_categories']);
            $ids = array_filter($ids);
            $nav_manager->set_config('store_categories', implode(',', $ids));
        } else {
            $nav_manager->set_config('store_categories', '');
        }
    }

    public function render_import_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['bm_import_submit']) && wp_verify_nonce($_POST['_bm_nonce'], 'bm_import')) {
            $this->handle_import();
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('数据导入', 'bookmark-nav'); ?></h1>

            <div class="bm-import-section">
                <h2><?php esc_html_e('从浏览器书签导入', 'bookmark-nav'); ?></h2>
                <p><?php esc_html_e('支持 Chrome、Firefox、Edge 等浏览器导出的 HTML 书签文件。', 'bookmark-nav'); ?></p>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('bm_import', '_bm_nonce'); ?>
                    <input type="hidden" name="bm_import_submit" value="1" />
                    <input type="hidden" name="import_type" value="browser" />
                    <p><input type="file" name="import_file" accept=".html,.htm" class="regular-text" /></p>
                    <?php submit_button(__('导入', 'bookmark-nav'), 'secondary', 'import_browser', false); ?>
                </form>
            </div>

            <div class="bm-import-section">
                <h2><?php esc_html_e('从 CSV 导入', 'bookmark-nav'); ?></h2>
                <p><?php esc_html_e('CSV 文件需包含表头：title, url, description, category', 'bookmark-nav'); ?></p>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('bm_import', '_bm_nonce'); ?>
                    <input type="hidden" name="bm_import_submit" value="1" />
                    <input type="hidden" name="import_type" value="csv" />
                    <p><input type="file" name="import_file" accept=".csv" class="regular-text" /></p>
                    <?php submit_button(__('导入', 'bookmark-nav'), 'secondary', 'import_csv', false); ?>
                </form>
            </div>

            <div class="bm-import-section">
                <h2><?php esc_html_e('从 JSON 导入', 'bookmark-nav'); ?></h2>
                <p><?php esc_html_e('支持 mtab 格式的 JSON 数据。', 'bookmark-nav'); ?></p>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('bm_import', '_bm_nonce'); ?>
                    <input type="hidden" name="bm_import_submit" value="1" />
                    <input type="hidden" name="import_type" value="json" />
                    <p><input type="file" name="import_file" accept=".json" class="regular-text" /></p>
                    <?php submit_button(__('导入', 'bookmark-nav'), 'secondary', 'import_json', false); ?>
                </form>
            </div>
        </div>
        <?php
    }

    private function handle_import() {
        if (empty($_FILES['import_file']['tmp_name'])) {
            echo '<div class="notice notice-error"><p>' . esc_html__('请选择文件', 'bookmark-nav') . '</p></div>';
            return;
        }

        $import_type = sanitize_key($_POST['import_type'] ?? '');
        $file_path = $_FILES['import_file']['tmp_name'];
        $data_source = new BM_Data_Source($this->settings);
        $importer = new BM_Importer($data_source);

        $result = false;
        switch ($import_type) {
            case 'browser':
                $result = $importer->import_browser_bookmarks($file_path);
                break;
            case 'csv':
                $result = $importer->import_csv($file_path);
                break;
            case 'json':
                $result = $importer->import_json($file_path);
                break;
        }

        if (is_wp_error($result)) {
            echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
        } elseif ($result !== false) {
            echo '<div class="notice notice-success"><p>' . sprintf(esc_html__('成功导入 %d 个书签', 'bookmark-nav'), absint($result)) . '</p></div>';
        }
    }
}
