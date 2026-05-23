<?php
if (!defined('ABSPATH')) {
    exit;
}

class BM_Nav_Manager {

    private $data_source;

    private $settings;

    private $defaults = array(
        'appearance.columns' => 8,
        'appearance.text_color' => '#374151',
        'appearance.icon_size' => 72,
        'appearance.card_radius' => 14,
        'appearance.card_gap' => 18,
        'wallpaper.type' => 'color',
        'wallpaper.value' => '#1a1a2e',
        'wallpaper.blur' => 20,
        'wallpaper.overlay' => 15,
        'wallpaper.gradient_from' => '#0c0c1d',
        'wallpaper.gradient_to' => '#16213e',
        'wallpaper.image_url' => '',
        'theme.primary_color' => '#4f46e5',
        'sidebar.visible' => 1,
        'sidebar.width_expanded' => 220,
        'sidebar.width_collapsed' => 72,
        'sidebar.position' => 'left',
        'sidebar.mode' => 'always',
        'dock.visible' => 1,
        'dock.height' => 68,
        'dock.position' => 'bottom',
        'clock.visible' => 1,
        'clock.format_24h' => 1,
        'search.default_engine' => 'baidu',
    );

    public function __construct(BM_Data_Source $data_source, BM_Settings $settings) {
        $this->data_source = $data_source;
        $this->settings = $settings;
    }

    public function get_nav_items($group_id = null, $user_id = 0) {
        global $wpdb;
        if ($user_id <= 0) {
            return array();
        }
        $table = BM_DB::get_instance()->get_table('nav_items');
        if ($group_id !== null) {
            $sql = $wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d AND group_id = %d ORDER BY sort_order ASC", $user_id, $group_id);
        } else {
            $sql = $wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d ORDER BY sort_order ASC", $user_id);
        }
        $results = $wpdb->get_results($sql);
        return $results ?: array();
    }

    public function get_all_nav_items_grouped($page_id = null, $user_id = 0) {
        global $wpdb;
        if ($user_id <= 0) {
            return array();
        }
        $table = BM_DB::get_instance()->get_table('nav_items');
        if ($page_id !== null) {
            $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d AND page_id = %d ORDER BY sort_order ASC", $user_id, $page_id));
        } else {
            $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d ORDER BY sort_order ASC", $user_id));
        }

        if (!$items) {
            return array();
        }

        $grouped = array();

        foreach ($items as $item) {
            $key = $item->group_id == 0 ? 'ungrouped' : $item->group_id;
            if (!isset($grouped[$key])) {
                $grouped[$key] = array();
            }
            $grouped[$key][] = $item;
        }

        return $grouped;
    }

    public function add_item($data) {
        global $wpdb;
        $table = BM_DB::get_instance()->get_table('nav_items');

        $page_id = isset($data['page_id']) ? absint($data['page_id']) : $this->get_active_page_id($data['user_id'] ?? 0);

        $insert_data = array(
            'user_id' => absint($data['user_id'] ?? 0),
            'page_id' => $page_id,
            'source_type' => $data['source_type'] ?? '',
            'source_id' => $data['source_id'] ?? 0,
            'title' => $data['title'] ?? '',
            'url' => $data['url'] ?? '',
            'icon' => $data['icon'] ?? '',
            'describe' => $data['describe'] ?? '',
            'group_id' => $data['group_id'] ?? 0,
            'layout' => $data['layout'] ?? 'grid',
            'bg_color' => $data['bg_color'] ?? '',
            'text_icon' => $data['text_icon'] ?? '',
            'open_in_iframe' => $data['open_in_iframe'] ?? 0,
            'component_id' => $data['component_id'] ?? '',
            'component_config' => $data['component_config'] ?? '',
        );

        $format = array('%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s');

        $result = $wpdb->insert($table, $insert_data, $format);

        if ($result === false) {
            return new WP_Error('bm_add_item_failed', __('添加导航项失败', 'bookmark-nav'));
        }

        return $wpdb->insert_id;
    }

    public function add_item_from_onenav($source_id, $params = array(), $user_id = 0) {
        $bookmark = $this->data_source->get_bookmark($source_id);

        if (!$bookmark) {
            return new WP_Error('bm_bookmark_not_found', __('书签不存在', 'bookmark-nav'));
        }

        $data = array(
            'user_id' => $user_id,
            'source_type' => 'onenav',
            'source_id' => $source_id,
            'title' => $bookmark['title'] ?? '',
            'url' => $bookmark['link'] ?? '',
            'icon' => $bookmark['icon'] ?? '',
            'describe' => $bookmark['describe'] ?? '',
            'group_id' => $params['group_id'] ?? 0,
            'page_id' => absint($params['page_id'] ?? 0),
        );

        return $this->add_item($data);
    }

    public function update_item($id, $data, $user_id = 0) {
        global $wpdb;
        $table = BM_DB::get_instance()->get_table('nav_items');

        if ($user_id > 0) {
            $owner = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$table} WHERE id = %d", $id));
            if ($owner === null || intval($owner) !== $user_id) {
                return false;
            }
        }

        $allowed = array('title', 'url', 'icon', 'describe', 'group_id', 'layout', 'in_dock', 'dock_sort', 'bg_color', 'text_icon', 'open_in_iframe', 'sort_order', 'component_config', 'page_id');
        $update_data = array();
        $format = array();

        $string_fields = array('title', 'url', 'icon', 'describe', 'layout', 'bg_color', 'text_icon', 'component_config');
        $int_fields = array('group_id', 'in_dock', 'dock_sort', 'open_in_iframe', 'sort_order', 'page_id');

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $update_data[$field] = $data[$field];
                if (in_array($field, $string_fields, true)) {
                    $format[] = '%s';
                } else {
                    $format[] = '%d';
                }
            }
        }

        if (empty($update_data)) {
            return false;
        }

        $result = $wpdb->update($table, $update_data, array('id' => $id), $format, array('%d'));

        return $result !== false;
    }

    public function remove_item($id, $user_id = 0) {
        global $wpdb;
        $table = BM_DB::get_instance()->get_table('nav_items');

        if ($user_id > 0) {
            $result = $wpdb->delete($table, array('id' => $id, 'user_id' => $user_id), array('%d', '%d'));
        } else {
            $result = $wpdb->delete($table, array('id' => $id), array('%d'));
        }

        return $result !== false;
    }

    public function reorder_items($items, $user_id = 0) {
        global $wpdb;
        $table = BM_DB::get_instance()->get_table('nav_items');

        foreach ($items as $item) {
            if ($user_id > 0) {
                $wpdb->update(
                    $table,
                    array('sort_order' => $item['sort_order']),
                    array('id' => $item['id'], 'user_id' => $user_id),
                    array('%d'),
                    array('%d', '%d')
                );
            } else {
                $wpdb->update(
                    $table,
                    array('sort_order' => $item['sort_order']),
                    array('id' => $item['id']),
                    array('%d'),
                    array('%d')
                );
            }
        }

        return true;
    }

    public function add_to_dock($id, $user_id = 0) {
        global $wpdb;
        $table = BM_DB::get_instance()->get_table('nav_items');

        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
        if (!$item) {
            return new WP_Error('bm_not_found', '项目不存在');
        }

        if ($user_id > 0) {
            $max_dock = $wpdb->get_var($wpdb->prepare("SELECT COALESCE(MAX(dock_sort), 0) FROM {$table} WHERE in_dock = 1 AND user_id = %d", $user_id));
        } else {
            $max_dock = $wpdb->get_var("SELECT COALESCE(MAX(dock_sort), 0) FROM {$table} WHERE in_dock = 1");
        }

        $wpdb->insert(
            $table,
            array(
                'user_id'      => $user_id > 0 ? $user_id : 0,
                'title'        => $item->title,
                'url'          => $item->url,
                'icon'         => $item->icon,
                'describe'     => $item->describe,
                'source_type'  => 'custom',
                'source_id'    => 0,
                'page_id'      => 0,
                'group_id'     => 0,
                'sort_order'   => 0,
                'layout'       => $item->layout,
                'in_dock'      => 1,
                'dock_sort'    => $max_dock + 1,
                'bg_color'     => $item->bg_color,
                'text_icon'    => $item->text_icon,
                'open_in_iframe' => $item->open_in_iframe,
                'status'       => 'active',
            ),
            array('%d','%s','%s','%s','%s','%s','%d','%d','%d','%d','%s','%d','%d','%s','%s','%d','%s')
        );

        return array('id' => $wpdb->insert_id, 'added' => true);
    }

    public function remove_from_dock($id, $user_id = 0) {
        global $wpdb;
        $table = BM_DB::get_instance()->get_table('nav_items');

        $item = $wpdb->get_row($wpdb->prepare("SELECT in_dock, user_id FROM {$table} WHERE id = %d", $id));
        if (!$item) {
            return new WP_Error('bm_not_found', '项目不存在');
        }

        if ($user_id > 0 && intval($item->user_id) !== $user_id) {
            return new WP_Error('bm_forbidden', '无权操作');
        }

        if (!empty($item->in_dock)) {
            $wpdb->delete($table, array('id' => $id), array('%d'));
        } else {
            $wpdb->update(
                $table,
                array('in_dock' => 0, 'dock_sort' => 0),
                array('id' => $id),
                array('%d', '%d'),
                array('%d')
            );
        }

        return array('removed' => true);
    }

    public function get_dock_items($user_id = 0) {
        global $wpdb;
        if ($user_id <= 0) {
            return array();
        }
        $table = BM_DB::get_instance()->get_table('nav_items');
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE in_dock = 1 AND user_id = %d ORDER BY dock_sort ASC", $user_id));
        return $results ?: array();
    }

    public function reorder_dock($items, $user_id = 0) {
        global $wpdb;
        $table = BM_DB::get_instance()->get_table('nav_items');

        foreach ($items as $item) {
            if ($user_id > 0) {
                $wpdb->update(
                    $table,
                    array('dock_sort' => $item['sort_order']),
                    array('id' => $item['id'], 'user_id' => $user_id),
                    array('%d'),
                    array('%d', '%d')
                );
            } else {
                $wpdb->update(
                    $table,
                    array('dock_sort' => $item['sort_order']),
                    array('id' => $item['id']),
                    array('%d'),
                    array('%d')
                );
            }
        }

        return true;
    }

    public function get_groups($page_id = null, $user_id = 0) {
        global $wpdb;
        if ($user_id <= 0) {
            return array();
        }
        $table = BM_DB::get_instance()->get_table('groups');
        if ($page_id !== null) {
            $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d AND page_id = %d ORDER BY sort_order ASC", $user_id, $page_id));
        } else {
            $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d ORDER BY sort_order ASC", $user_id));
        }
        return $results ?: array();
    }

    public function add_group($data) {
        global $wpdb;
        $table = BM_DB::get_instance()->get_table('groups');

        $insert_data = array(
            'user_id' => absint($data['user_id'] ?? 0),
            'title' => $data['title'] ?? '',
            'icon' => $data['icon'] ?? '',
            'page_id' => absint($data['page_id'] ?? 0),
            'sort_order' => $data['sort_order'] ?? 0,
            'is_system' => $data['is_system'] ?? 0,
            'is_folder' => $data['is_folder'] ?? 0,
            'layout' => $data['layout'] ?? null,
            'columns' => $data['columns'] ?? 5,
            'icon_size' => $data['icon_size'] ?? 'md',
            'show_text' => $data['show_text'] ?? 1,
            'text_color' => $data['text_color'] ?? '',
        );

        $format = array('%d', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%d', '%s', '%d', '%s');

        $result = $wpdb->insert($table, $insert_data, $format);

        if ($result === false) {
            return new WP_Error('bm_add_group_failed', __('添加分组失败', 'bookmark-nav'));
        }

        return $wpdb->insert_id;
    }

    public function update_group($id, $data, $user_id = 0) {
        global $wpdb;
        $table = BM_DB::get_instance()->get_table('groups');

        if ($user_id > 0) {
            $owner = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$table} WHERE id = %d", $id));
            if ($owner === null || intval($owner) !== $user_id) {
                return false;
            }
        }

        $allowed = array('title', 'icon', 'page_id', 'sort_order', 'is_system', 'is_folder', 'columns', 'icon_size', 'show_text', 'text_color', 'layout');
        $update_data = array();
        $format = array();

        $string_fields = array('title', 'icon', 'icon_size', 'text_color', 'layout');
        $int_fields = array('sort_order', 'is_system', 'is_folder', 'columns', 'show_text');

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $update_data[$field] = $data[$field];
                if (in_array($field, $string_fields, true)) {
                    $format[] = '%s';
                } else {
                    $format[] = '%d';
                }
            }
        }

        if (empty($update_data)) {
            return false;
        }

        $result = $wpdb->update($table, $update_data, array('id' => $id), $format, array('%d'));

        return $result !== false;
    }

    public function delete_group($id, $user_id = 0) {
        global $wpdb;
        $groups_table = BM_DB::get_instance()->get_table('groups');
        $items_table = BM_DB::get_instance()->get_table('nav_items');

        $group = $wpdb->get_row($wpdb->prepare("SELECT is_system, user_id FROM {$groups_table} WHERE id = %d", $id));

        if (!$group) {
            return false;
        }

        if ($user_id > 0 && intval($group->user_id) !== $user_id) {
            return false;
        }

        if ($group->is_system == 1) {
            return false;
        }

        if ($user_id > 0) {
            $wpdb->delete($items_table, array('group_id' => $id, 'user_id' => $user_id), array('%d', '%d'));
        } else {
            $wpdb->delete($items_table, array('group_id' => $id), array('%d'));
        }

        $result = $wpdb->delete($groups_table, array('id' => $id), array('%d'));

        return $result !== false;
    }

    public function get_pages($user_id = 0) {
        global $wpdb;
        if ($user_id <= 0) {
            return array();
        }
        $table = BM_DB::get_instance()->get_table('pages');
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d ORDER BY sort_order ASC", $user_id));
        return $results ?: array();
    }

    public function add_page($data) {
        global $wpdb;
        $table = BM_DB::get_instance()->get_table('pages');
        $wpdb->insert($table, array(
            'user_id' => absint($data['user_id'] ?? 0),
            'title' => sanitize_text_field($data['title'] ?? ''),
            'icon' => sanitize_text_field($data['icon'] ?? '📁'),
            'sort_order' => absint($data['sort_order'] ?? 0),
            'is_default' => 0,
        ), array('%d', '%s', '%s', '%d', '%d'));
        return $wpdb->insert_id;
    }

    public function update_page($id, $data, $user_id = 0) {
        global $wpdb;
        $table = BM_DB::get_instance()->get_table('pages');

        if ($user_id > 0) {
            $owner = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$table} WHERE id = %d", $id));
            if ($owner === null || intval($owner) !== $user_id) {
                return false;
            }
        }

        $update = array();
        $formats = array();
        if (isset($data['title'])) { $update['title'] = sanitize_text_field($data['title']); $formats[] = '%s'; }
        if (isset($data['icon'])) { $update['icon'] = sanitize_text_field($data['icon']); $formats[] = '%s'; }
        if (isset($data['sort_order'])) { $update['sort_order'] = absint($data['sort_order']); $formats[] = '%d'; }
        if (!empty($update)) {
            $wpdb->update($table, $update, array('id' => $id), $formats, array('%d'));
        }
        return true;
    }

    public function delete_page($id, $user_id = 0) {
        global $wpdb;
        $page_table = BM_DB::get_instance()->get_table('pages');
        $page = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$page_table} WHERE id = %d", $id));
        if (!$page) return false;
        if ($page->is_default) return false;
        if ($user_id > 0 && intval($page->user_id) !== $user_id) return false;
        $groups_table = BM_DB::get_instance()->get_table('groups');
        $nav_table = BM_DB::get_instance()->get_table('nav_items');
        if ($user_id > 0) {
            $wpdb->delete($nav_table, array('page_id' => $id, 'user_id' => $user_id), array('%d', '%d'));
            $wpdb->delete($groups_table, array('page_id' => $id, 'user_id' => $user_id), array('%d', '%d'));
        } else {
            $wpdb->delete($nav_table, array('page_id' => $id), array('%d'));
            $wpdb->delete($groups_table, array('page_id' => $id), array('%d'));
        }
        $wpdb->delete($page_table, array('id' => $id), array('%d'));
        return true;
    }

    public function reorder_pages($items, $user_id = 0) {
        global $wpdb;
        $table = BM_DB::get_instance()->get_table('pages');
        foreach ($items as $item) {
            if ($user_id > 0) {
                $wpdb->update($table, array('sort_order' => absint($item['sort_order'])), array('id' => absint($item['id']), 'user_id' => $user_id), array('%d'), array('%d', '%d'));
            } else {
                $wpdb->update($table, array('sort_order' => absint($item['sort_order'])), array('id' => absint($item['id'])), array('%d'), array('%d'));
            }
        }
        return true;
    }

    public function get_config($key, $user_id = 0, $default = null) {
        global $wpdb;
        $table = BM_DB::get_instance()->get_table('nav_config');

        if ($user_id > 0) {
            $value = $wpdb->get_var($wpdb->prepare(
                "SELECT config_value FROM {$table} WHERE config_key = %s AND user_id = %d",
                $key, $user_id
            ));
            if ($value === null) {
                $value = $wpdb->get_var($wpdb->prepare(
                    "SELECT config_value FROM {$table} WHERE config_key = %s AND user_id = 0",
                    $key
                ));
            }
        } else {
            $value = $wpdb->get_var($wpdb->prepare(
                "SELECT config_value FROM {$table} WHERE config_key = %s AND user_id = 0",
                $key
            ));
        }

        if ($value === null) {
            if ($default !== null) {
                return $default;
            }
            if (isset($this->defaults[$key])) {
                return $this->defaults[$key];
            }
            return null;
        }

        return maybe_unserialize($value);
    }

    public function set_config($key, $value, $user_id = 0) {
        global $wpdb;
        $table = BM_DB::get_instance()->get_table('nav_config');

        $serialized = maybe_serialize($value);

        $result = $wpdb->query($wpdb->prepare(
            "INSERT INTO {$table} (config_key, config_value, user_id) VALUES (%s, %s, %d) ON DUPLICATE KEY UPDATE config_value = %s",
            $key,
            $serialized,
            $user_id,
            $serialized
        ));

        return $result !== false;
    }

    public function get_all_config($user_id = 0) {
        global $wpdb;
        $table = BM_DB::get_instance()->get_table('nav_config');

        $global_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT config_key, config_value FROM {$table} WHERE user_id = %d",
            0
        ));

        $config = array();
        if ($global_rows && is_array($global_rows)) {
            foreach ($global_rows as $row) {
                $config[$row->config_key] = maybe_unserialize($row->config_value);
            }
        }

        if ($user_id > 0) {
            $user_rows = $wpdb->get_results($wpdb->prepare(
                "SELECT config_key, config_value FROM {$table} WHERE user_id = %d",
                $user_id
            ));
            if ($user_rows && is_array($user_rows)) {
                foreach ($user_rows as $row) {
                    $config[$row->config_key] = maybe_unserialize($row->config_value);
                }
            }
        }

        foreach ($this->defaults as $key => $value) {
            if (!isset($config[$key])) {
                $config[$key] = $value;
            }
        }

        return $config;
    }

    public function save_settings($settings_data, $user_id = 0) {
        foreach ($settings_data as $section => $options) {
            if (!is_array($options)) {
                $this->set_config($section, $options, $user_id);
                continue;
            }
            foreach ($options as $key => $value) {
                $this->set_config($section . '.' . $key, $value, $user_id);
            }
        }

        return true;
    }

    public function get_candidates($args = array(), $user_id = 0) {
        global $wpdb;

        $page = absint($args['page'] ?? 1);
        $per_page = absint($args['per_page'] ?? 20);
        $search = $args['search'] ?? '';
        $category = $args['category'] ?? 0;

        $query_args = array(
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => 'publish',
        );

        if (!empty($search)) {
            $query_args['s'] = sanitize_text_field($search);
        }

        if (!empty($category)) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => $this->data_source->get_taxonomy_category(),
                    'field' => 'term_id',
                    'terms' => absint($category),
                ),
            );
        }

        $bookmarks = $this->data_source->get_bookmarks($query_args);

        $items_table = BM_DB::get_instance()->get_table('nav_items');
        if ($user_id > 0) {
            $added_sources = $wpdb->get_col($wpdb->prepare(
                "SELECT source_id FROM {$items_table} WHERE source_type = 'onenav' AND source_id > 0 AND user_id = %d",
                $user_id
            ));
        } else {
            $added_sources = array();
        }

        $items = array();
        foreach ($bookmarks as $bookmark) {
            $bookmark['is_added'] = in_array($bookmark['id'], $added_sources);
            $items[] = $bookmark;
        }

        $total = $this->data_source->get_bookmark_count(!empty($category) ? absint($category) : null);
        if (empty($total)) {
            $total = count($items);
        }

        $total_pages = $per_page > 0 ? ceil($total / $per_page) : 1;

        return array(
            'items' => $items,
            'meta' => array(
                'total' => $total,
                'total_pages' => $total_pages,
                'page' => $page,
                'per_page' => $per_page,
            ),
        );
    }

    private function get_active_page_id($user_id = 0) {
        $active_page_id = $this->get_config('sidebar.active_page', $user_id);
        if ($active_page_id) {
            return absint($active_page_id);
        }
        $pages = $this->get_pages($user_id);
        if (!empty($pages)) {
            return absint($pages[0]->id);
        }
        return 0;
    }

    public function get_init_data($wp_page_id = 0, $active_page = null, $user_id = 0) {
        $pages = $this->get_pages($user_id);

        if ($active_page !== null && $active_page > 0) {
            $active_page_id = absint($active_page);
        } else {
            $active_page_id = $this->get_active_page_id($user_id);
        }

        $redirect_url = $wp_page_id ? get_permalink($wp_page_id) : '';
        if (!$redirect_url) {
            $redirect_url = home_url($_SERVER['REQUEST_URI'] ?? '/');
        }

        $current_user = array(
            'logged_in' => is_user_logged_in(),
            'user_id' => get_current_user_id(),
            'avatar_url' => is_user_logged_in() ? get_avatar_url(get_current_user_id()) : '',
            'display_name' => is_user_logged_in() ? wp_get_current_user()->display_name : '',
            'login_url' => wp_login_url($redirect_url),
            'logout_url' => wp_logout_url($redirect_url),
        );

        $nav_items_grouped = $this->get_all_nav_items_grouped($active_page_id, $user_id);

        $has_data = !empty($pages) || !empty($nav_items_grouped);

        return array(
            'settings' => $this->get_all_config($user_id),
            'pages' => $pages,
            'active_page_id' => $active_page_id,
            'groups' => $this->get_groups($active_page_id, $user_id),
            'nav_items' => $nav_items_grouped,
            'dock_items' => $this->get_dock_items($user_id),
            'current_user' => $current_user,
            'user_logged_in' => is_user_logged_in(),
            'has_data' => $has_data,
        );
    }

    public function has_user_data($user_id) {
        global $wpdb;

        $pages_table = BM_DB::get_instance()->get_table('pages');
        $pages_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$pages_table} WHERE user_id = %d", $user_id));

        $items_table = BM_DB::get_instance()->get_table('nav_items');
        $items_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$items_table} WHERE user_id = %d", $user_id));

        return (intval($pages_count) > 0 || intval($items_count) > 0);
    }

    public function init_default_data($user_id) {
        global $wpdb;

        $table_pages = BM_DB::get_instance()->get_table('pages');
        $wpdb->insert($table_pages, array(
            'user_id' => $user_id,
            'title' => '首页',
            'icon' => '🏠',
            'sort_order' => 0,
            'is_default' => 1,
        ), array('%d', '%s', '%s', '%d', '%d'));
        $page_id = $wpdb->insert_id;

        $table_groups = BM_DB::get_instance()->get_table('groups');
        $wpdb->insert($table_groups, array(
            'user_id' => $user_id,
            'title' => '常用',
            'icon' => '⭐',
            'page_id' => $page_id,
            'sort_order' => 0,
        ), array('%d', '%s', '%s', '%d', '%d'));

        $this->set_config('sidebar.active_page', $page_id, $user_id);

        return $page_id;
    }

    public function sync_local_data($user_id, $bookmarks = array(), $memo = null, $settings = array()) {
        $synced_bookmarks = 0;

        foreach ($bookmarks as $bookmark) {
            $data = array(
                'user_id' => $user_id,
                'source_type' => 'custom',
                'title' => $bookmark['title'] ?? '',
                'url' => $bookmark['url'] ?? '',
                'icon' => $bookmark['icon'] ?? '',
                'describe' => $bookmark['describe'] ?? '',
                'group_id' => 0,
                'page_id' => 0,
                'bg_color' => $bookmark['bg_color'] ?? '',
                'text_icon' => $bookmark['text_icon'] ?? '',
            );
            $result = $this->add_item($data);
            if (!is_wp_error($result)) {
                $synced_bookmarks++;
            }
        }

        $synced_memo = false;
        if ($memo !== null) {
            $synced_memo = $this->save_memo($user_id, $memo);
        }

        $synced_settings = false;
        if (!empty($settings)) {
            $synced_settings = $this->save_settings($settings, $user_id);
        }

        return array(
            'synced_bookmarks' => $synced_bookmarks,
            'synced_memo' => $synced_memo,
            'synced_settings' => $synced_settings,
        );
    }

    public function get_memo($user_id) {
        global $wpdb;
        $table = BM_DB::get_instance()->get_table('memos');

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d", $user_id));

        if (!$row) {
            return null;
        }

        return array(
            'color' => $row->color,
            'notes' => maybe_unserialize($row->notes),
        );
    }

    public function save_memo($user_id, $data) {
        global $wpdb;
        $table = BM_DB::get_instance()->get_table('memos');

        $color = $data['color'] ?? '#f59e0b';
        $notes = maybe_serialize($data['notes'] ?? array());

        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE user_id = %d", $user_id));

        if ($existing) {
            $result = $wpdb->update(
                $table,
                array('color' => $color, 'notes' => $notes),
                array('user_id' => $user_id),
                array('%s', '%s'),
                array('%d')
            );
        } else {
            $result = $wpdb->insert(
                $table,
                array('user_id' => $user_id, 'color' => $color, 'notes' => $notes),
                array('%d', '%s', '%s')
            );
        }

        return $result !== false;
    }
}
