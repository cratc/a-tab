<?php
if (!defined('ABSPATH')) {
    exit;
}

class BM_Link_Checker {

    private $data_source;

    public function __construct(BM_Data_Source $data_source) {
        $this->data_source = $data_source;
    }

    /**
     * 批量检查链接健康状态
     */
    public function run_check() {
        $bookmarks = $this->data_source->get_bookmarks(array(
            'posts_per_page' => 50,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_last_check',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => '_last_check',
                    'value' => date('Y-m-d H:i:s', strtotime('-72 hours')),
                    'compare' => '<',
                    'type' => 'DATETIME',
                ),
            ),
        ));

        foreach ($bookmarks as $bookmark) {
            $this->check_single_link($bookmark['id'], $bookmark['link']);
        }
    }

    /**
     * 检查单个链接的健康状态
     */
    public function check_single_link($post_id, $url) {
        if (empty($url)) {
            return;
        }

        $response = wp_remote_head($url, array(
            'timeout' => 30,
            'redirection' => 3,
            'sslverify' => false,
        ));

        $is_dead = false;
        $redirect_url = '';
        $status_code = 0;

        if (is_wp_error($response)) {
            $is_dead = true;
        } else {
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code >= 400) {
                $is_dead = true;
            }
            $redirect_url = wp_remote_retrieve_header($response, 'location');
        }

        update_post_meta($post_id, '_dead_link', $is_dead ? '1' : '0');
        update_post_meta($post_id, '_last_check', current_time('mysql'));
        update_post_meta($post_id, '_check_status', $status_code);

        if (!empty($redirect_url)) {
            update_post_meta($post_id, '_redirect_url', esc_url_raw($redirect_url));
        }

        $check_count = (int) get_post_meta($post_id, '_check_count', true);
        update_post_meta($post_id, '_check_count', $check_count + 1);
    }

    /**
     * 获取失效链接列表
     */
    public function get_dead_links() {
        return $this->data_source->get_bookmarks(array(
            'meta_query' => array(
                array(
                    'key' => '_dead_link',
                    'value' => '1',
                ),
            ),
        ));
    }
}
