<?php
if (!defined('ABSPATH')) {
    exit;
}

class BM_Importer {

    private $data_source;

    public function __construct(BM_Data_Source $data_source) {
        $this->data_source = $data_source;
    }

    /**
     * 导入浏览器书签文件（Netscape格式）
     */
    public function import_browser_bookmarks($file_path) {
        if (!file_exists($file_path)) {
            return new WP_Error('bm_file_not_found', __('文件不存在', 'bookmark-nav'));
        }

        $content = file_get_contents($file_path);
        $bookmarks = $this->parse_netscape_bookmarks($content);

        $imported = 0;
        foreach ($bookmarks as $bookmark) {
            $result = $this->import_single_bookmark($bookmark);
            if (!is_wp_error($result)) {
                $imported++;
            }
        }

        return $imported;
    }

    /**
     * 解析Netscape格式书签文件
     */
    private function parse_netscape_bookmarks($content) {
        $bookmarks = array();
        $current_folder = '';

        if (preg_match_all('/<DT><H3[^>]*>(.*?)<\/H3>/i', $content, $folders, PREG_SET_ORDER)) {
        }

        if (preg_match_all('/<DT><A[^>]*HREF="([^"]*)"[^>]*>(.*?)<\/A>/i', $content, $links, PREG_SET_ORDER)) {
            foreach ($links as $link) {
                $url = $link[1];
                $title = $link[2];

                $description = '';
                if (preg_match('/<DD>(.*?)(?=<DT>|<\/DL>|$)/i', $content, $desc_match)) {
                    $description = trim(strip_tags($desc_match[1]));
                }

                $bookmarks[] = array(
                    'title' => html_entity_decode($title, ENT_QUOTES, 'UTF-8'),
                    'url' => $url,
                    'description' => $description,
                );
            }
        }

        return $bookmarks;
    }

    /**
     * 导入CSV格式书签
     */
    public function import_csv($file_path) {
        if (!file_exists($file_path)) {
            return new WP_Error('bm_file_not_found', __('文件不存在', 'bookmark-nav'));
        }

        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return new WP_Error('bm_file_open_failed', __('无法打开文件', 'bookmark-nav'));
        }

        $header = fgetcsv($handle);
        $imported = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            if (!$data) {
                continue;
            }

            $bookmark = array(
                'title' => $data['title'] ?? $data['name'] ?? '',
                'url' => $data['url'] ?? $data['link'] ?? '',
                'description' => $data['description'] ?? $data['describe'] ?? '',
                'category' => $data['category'] ?? $data['group'] ?? '',
            );

            $result = $this->import_single_bookmark($bookmark);
            if (!is_wp_error($result)) {
                $imported++;
            }
        }

        fclose($handle);
        return $imported;
    }

    /**
     * 导入JSON格式书签
     */
    public function import_json($file_path) {
        if (!file_exists($file_path)) {
            return new WP_Error('bm_file_not_found', __('文件不存在', 'bookmark-nav'));
        }

        $content = file_get_contents($file_path);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('bm_json_error', __('JSON格式错误', 'bookmark-nav'));
        }

        $imported = 0;

        if (isset($data['bookmarks'])) {
            foreach ($data['bookmarks'] as $bookmark) {
                $result = $this->import_single_bookmark($bookmark);
                if (!is_wp_error($result)) {
                    $imported++;
                }
            }
        } elseif (isset($data['groups'])) {
            foreach ($data['groups'] as $group) {
                $category_id = $this->ensure_category($group['title'] ?? '');
                if (isset($group['apps'])) {
                    foreach ($group['apps'] as $app) {
                        $app['category_id'] = $category_id;
                        $result = $this->import_single_bookmark($app);
                        if (!is_wp_error($result)) {
                            $imported++;
                        }
                    }
                }
            }
        }

        return $imported;
    }

    /**
     * 导入单个书签
     */
    private function import_single_bookmark($data) {
        $title = sanitize_text_field($data['title'] ?? $data['name'] ?? '');
        $url = bm_sanitize_url($data['url'] ?? $data['link'] ?? '');
        $description = sanitize_textarea_field($data['description'] ?? $data['describe'] ?? '');

        if (empty($title) || empty($url)) {
            return new WP_Error('bm_missing_data', __('标题或链接不能为空', 'bookmark-nav'));
        }

        $post_id = wp_insert_post(array(
            'post_title' => $title,
            'post_type' => $this->data_source->get_post_type(),
            'post_status' => 'publish',
        ));

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        $is_onenav = $this->data_source->is_onenav_mode();
        $link_key = $is_onenav ? '_sites_link' : '_bm_link';
        $desc_key = $is_onenav ? '_sites_sescribe' : '_bm_describe';

        update_post_meta($post_id, $link_key, $url);
        if ($description) {
            update_post_meta($post_id, $desc_key, $description);
        }

        if (!empty($data['category_id'])) {
            wp_set_object_terms($post_id, absint($data['category_id']), $this->data_source->get_taxonomy_category());
        } elseif (!empty($data['category'])) {
            $term = get_term_by('name', $data['category'], $this->data_source->get_taxonomy_category());
            if (!$term) {
                $term_result = wp_insert_term($data['category'], $this->data_source->get_taxonomy_category());
                if (!is_wp_error($term_result)) {
                    $term = get_term($term_result['term_id']);
                }
            }
            if ($term) {
                wp_set_object_terms($post_id, $term->term_id, $this->data_source->get_taxonomy_category());
            }
        }

        return $post_id;
    }

    /**
     * 确保分类存在，不存在则创建
     */
    private function ensure_category($name) {
        $name = sanitize_text_field($name);
        if (empty($name)) {
            return 0;
        }

        $term = get_term_by('name', $name, $this->data_source->get_taxonomy_category());
        if ($term) {
            return $term->term_id;
        }

        $result = wp_insert_term($name, $this->data_source->get_taxonomy_category());
        if (!is_wp_error($result)) {
            return $result['term_id'];
        }

        return 0;
    }
}
