<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$option_prefix = 'bm_';
$post_type = 'bm_bookmark';
$taxonomy_category = 'bm_category';
$taxonomy_tag = 'bm_tag';

$keep_data = get_option($option_prefix . 'keep_data_on_uninstall', false);

$options_to_delete = $wpdb->get_col(
    $wpdb->prepare(
        "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
        $option_prefix . '%'
    )
);

foreach ($options_to_delete as $option_name) {
    delete_option($option_name);
}

if (!$keep_data) {
    $posts = get_posts(array(
        'post_type'   => $post_type,
        'numberposts' => -1,
        'post_status' => 'any',
    ));

    foreach ($posts as $post) {
        wp_delete_post($post->ID, true);
    }

    $taxonomies = array($taxonomy_category, $taxonomy_tag);
    foreach ($taxonomies as $taxonomy) {
        $terms = get_terms(array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
        ));

        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                wp_delete_term($term->term_id, $taxonomy);
            }
        }
    }
}

$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_bm_icon_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_bm_icon_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_bm_bing_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_bm_bing_%'");

wp_clear_scheduled_hook('bm_link_check_event');

delete_option('bm_plugin_version');
delete_option('bm_keep_data_on_uninstall');
