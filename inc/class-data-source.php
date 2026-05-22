<?php
if (!defined('ABSPATH')) {
    exit;
}

class BM_Data_Source {

    private $settings;

    private $is_onenav = false;

    private $onenav_detected = false;

    private $post_type;

    private $taxonomy_category;

    private $taxonomy_tag;

    public function __construct(BM_Settings $settings) {
        $this->settings = $settings;
        $this->post_type = BM_POST_TYPE;
        $this->taxonomy_category = BM_TAXONOMY_CATEGORY;
        $this->taxonomy_tag = BM_TAXONOMY_TAG;
    }

    private function ensure_detected() {
        if ($this->onenav_detected) {
            return;
        }
        $this->onenav_detected = true;
        $this->is_onenav = $this->detect_onenav();

        if ($this->is_onenav) {
            $this->post_type = BM_ONENAV_POST_TYPE;
            $this->taxonomy_category = BM_ONENAV_TAXONOMY_CATEGORY;
            $this->taxonomy_tag = BM_ONENAV_TAXONOMY_TAG;
        } else {
            $this->post_type = BM_POST_TYPE;
            $this->taxonomy_category = BM_TAXONOMY_CATEGORY;
            $this->taxonomy_tag = BM_TAXONOMY_TAG;
        }
    }

    private function detect_onenav() {
        $source = $this->settings->get('data_source', 'auto');
        if ($source === 'standalone') {
            return false;
        }
        if ($source === 'onenav') {
            return post_type_exists(BM_ONENAV_POST_TYPE);
        }
        return post_type_exists(BM_ONENAV_POST_TYPE) && taxonomy_exists(BM_ONENAV_TAXONOMY_CATEGORY);
    }

    public function is_onenav_mode() {
        $this->ensure_detected();
        return $this->is_onenav;
    }

    public function get_post_type() {
        $this->ensure_detected();
        return $this->post_type;
    }

    public function get_taxonomy_category() {
        $this->ensure_detected();
        return $this->taxonomy_category;
    }

    public function get_taxonomy_tag() {
        $this->ensure_detected();
        return $this->taxonomy_tag;
    }

    public function get_categories($parent = 0, $hide_empty = false) {
        $this->ensure_detected();

        $order_key = $this->is_onenav ? '_term_order' : '_bm_order';

        $terms = get_terms(array(
            'taxonomy' => $this->taxonomy_category,
            'parent' => $parent,
            'hide_empty' => $hide_empty,
            'orderby' => 'bm_order_clause',
            'order' => 'ASC',
            'meta_query' => array(
                'relation' => 'OR',
                'bm_order_clause' => array(
                    'key' => $order_key,
                    'compare' => 'EXISTS',
                ),
                array(
                    'key' => $order_key,
                    'compare' => 'NOT EXISTS',
                ),
            ),
        ));

        if (is_wp_error($terms)) {
            $terms = get_terms(array(
                'taxonomy' => $this->taxonomy_category,
                'parent' => $parent,
                'hide_empty' => $hide_empty,
                'orderby' => 'name',
                'order' => 'ASC',
            ));
        }

        if (is_wp_error($terms)) {
            return array();
        }

        return $terms;
    }

    public function get_category_tree($parent = 0) {
        $terms = $this->get_categories($parent);
        $tree = array();

        foreach ($terms as $term) {
            $children = $this->get_category_tree($term->term_id);
            $tree[] = array(
                'term' => $term,
                'children' => $children,
                'has_children' => !empty($children),
            );
        }

        return $tree;
    }

    public function get_bookmarks($args = array()) {
        $this->ensure_detected();

        $order_key = $this->is_onenav ? '_sites_order' : '_bm_order';

        $defaults = array(
            'post_type' => $this->post_type,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => array('bm_order_clause' => 'DESC', 'date' => 'DESC'),
            'meta_query' => array(
                'relation' => 'OR',
                'bm_order_clause' => array(
                    'key' => $order_key,
                    'compare' => 'EXISTS',
                ),
                array(
                    'key' => $order_key,
                    'compare' => 'NOT EXISTS',
                ),
            ),
            'suppress_filters' => false,
        );

        $args = wp_parse_args($args, $defaults);

        if (isset($args['tax_query'])) {
            $args['meta_query'] = array(
                'relation' => 'AND',
                $defaults['meta_query'],
                array(),
            );
        }

        $query = new WP_Query($args);
        $bookmarks = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $bookmarks[] = $this->build_bookmark_data(get_the_ID());
            }
        }

        wp_reset_postdata();

        return $bookmarks;
    }

    public function get_bookmarks_by_term($term_id, $taxonomy = '') {
        $this->ensure_detected();
        if (empty($taxonomy)) {
            $taxonomy = $this->taxonomy_category;
        }

        return $this->get_bookmarks(array(
            'tax_query' => array(
                array(
                    'taxonomy' => $taxonomy,
                    'field' => 'term_id',
                    'terms' => $term_id,
                ),
            ),
        ));
    }

    public function get_bookmark($post_id) {
        $this->ensure_detected();
        $post = get_post($post_id);
        if (!$post || $post->post_type !== $this->post_type) {
            return null;
        }

        return $this->build_bookmark_data($post_id);
    }

    private function build_bookmark_data($post_id) {
        $this->ensure_detected();

        $link_meta_key = $this->is_onenav ? '_sites_link' : '_bm_link';
        $desc_meta_key = $this->is_onenav ? '_sites_sescribe' : '_bm_describe';
        $icon_meta_key = $this->is_onenav ? '_thumbnail' : '_bm_thumbnail';
        $preview_meta_key = $this->is_onenav ? '_sites_preview' : '_bm_preview';
        $order_meta_key = $this->is_onenav ? '_sites_order' : '_bm_order';
        $type_meta_key = $this->is_onenav ? '_sites_type' : '_bm_type';
        $goto_meta_key = $this->is_onenav ? '_goto' : '_bm_goto';
        $nofollow_meta_key = $this->is_onenav ? '_nofollow' : '_bm_nofollow';

        $icon = get_post_meta($post_id, $icon_meta_key, true);
        $link = get_post_meta($post_id, $link_meta_key, true);
        $describe = get_post_meta($post_id, $desc_meta_key, true);
        $preview = get_post_meta($post_id, $preview_meta_key, true);
        $order = get_post_meta($post_id, $order_meta_key, true);
        $type = get_post_meta($post_id, $type_meta_key, true);
        $goto = get_post_meta($post_id, $goto_meta_key, true);
        $nofollow = get_post_meta($post_id, $nofollow_meta_key, true);

        $categories = get_the_terms($post_id, $this->taxonomy_category);
        $tags = get_the_terms($post_id, $this->taxonomy_tag);

        if (empty($icon) && !empty($link)) {
            $icon_fetcher = new BM_Icon_Fetcher();
            $icon = $icon_fetcher->get_icon($link);
        }

        return array(
            'id' => $post_id,
            'title' => get_the_title($post_id),
            'link' => $link,
            'describe' => $describe,
            'icon' => $icon,
            'preview' => $preview,
            'order' => $order ?: 0,
            'type' => $type ?: 'sites',
            'goto' => (bool) $goto,
            'nofollow' => (bool) $nofollow,
            'permalink' => get_permalink($post_id),
            'categories' => $categories ?: array(),
            'tags' => $tags ?: array(),
            'is_dead' => (bool) get_post_meta($post_id, '_dead_link', true),
        );
    }

    public function search_bookmarks($keyword) {
        return $this->get_bookmarks(array(
            's' => sanitize_text_field($keyword),
            'posts_per_page' => 20,
        ));
    }

    public function get_bookmark_count($term_id = null) {
        $this->ensure_detected();
        $args = array(
            'post_type' => $this->post_type,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids',
        );

        if ($term_id) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => $this->taxonomy_category,
                    'field' => 'term_id',
                    'terms' => $term_id,
                ),
            );
        }

        $query = new WP_Query($args);
        return $query->found_posts;
    }

    public function get_candidates_for_picker($args = []) {
        $this->ensure_detected();

        $defaults = array(
            'page' => 1,
            'per_page' => 20,
            'search' => '',
            'category' => 0,
        );

        $args = wp_parse_args($args, $defaults);
        $page = max(1, absint($args['page']));
        $per_page = max(1, min(100, absint($args['per_page'])));
        $search = sanitize_text_field($args['search']);
        $category = absint($args['category']);

        $query_args = array(
            'post_type' => $this->post_type,
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => array('date' => 'DESC'),
        );

        if (!empty($search)) {
            $query_args['s'] = $search;
        }

        if ($category > 0) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => $this->taxonomy_category,
                    'field' => 'term_id',
                    'terms' => $category,
                ),
            );
        }

        $query = new WP_Query($query_args);
        $items = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $items[] = $this->build_bookmark_data(get_the_ID());
            }
        }

        wp_reset_postdata();

        return array(
            'items' => $items,
            'meta' => array(
                'total' => (int) $query->found_posts,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => (int) $query->max_num_pages,
            ),
        );
    }
}
