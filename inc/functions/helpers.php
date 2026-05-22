<?php
if (!defined('ABSPATH')) {
    exit;
}

function bm_get_template_part($slug, $args = array()) {
    $template = BM_PLUGIN_DIR . 'templates/' . $slug . '.php';

    if (!file_exists($template)) {
        return '';
    }

    if (!empty($args) && is_array($args)) {
        extract($args, EXTR_SKIP);
    }

    ob_start();
    include $template;
    return ob_get_clean();
}

function bm_get_option($key, $default = null) {
    $option = get_option(BM_OPTION_PREFIX . $key);
    return $option !== false ? $option : $default;
}

function bm_update_option($key, $value) {
    return update_option(BM_OPTION_PREFIX . $key, $value);
}

function bm_delete_option($key) {
    return delete_option(BM_OPTION_PREFIX . $key);
}

function bm_is_onenav_active() {
    return post_type_exists(BM_ONENAV_POST_TYPE) && taxonomy_exists(BM_ONENAV_TAXONOMY_CATEGORY);
}

function bm_get_favicon_url($url, $size = 32) {
    $domain = bm_extract_domain($url);
    if (!$domain) {
        return '';
    }

    $api = bm_get_option('favicon_api', 'https://www.google.com/s2/favicons?domain=');
    return $api . $domain . '&sz=' . $size;
}

function bm_extract_domain($url) {
    $parsed = wp_parse_url($url);
    if (isset($parsed['host'])) {
        return $parsed['host'];
    }
    return '';
}

function bm_get_letter_icon($title, $saturation = 40, $brightness = 90) {
    $letter = mb_substr($title, 0, 1, 'UTF-8');
    $hash = md5($title);
    $hue = hexdec(substr($hash, 0, 6)) % 360;

    return array(
        'letter' => strtoupper($letter),
        'bg_color' => "hsl({$hue}, {$saturation}%, {$brightness}%)",
    );
}

function bm_sanitize_url($url) {
    $url = trim($url);
    if (empty($url)) {
        return '';
    }
    if (!preg_match('~^https?://~i', $url)) {
        $url = 'https://' . $url;
    }
    return esc_url_raw($url);
}

function bm_get_columns_class($columns = null) {
    if ($columns === null) {
        $settings = new BM_Settings();
        $columns = $settings->get('columns');
    }

    $classes = array('col-12');
    $breakpoints = array(
        'sm' => 'col-sm',
        'md' => 'col-md',
        'lg' => 'col-lg',
        'xl' => 'col-xl',
        'xxl' => 'col-xxl',
    );

    foreach ($breakpoints as $bp => $prefix) {
        if (isset($columns[$bp]) && $columns[$bp] > 0) {
            $col = floor(12 / $columns[$bp]);
            if ($col > 0 && $col <= 12) {
                $classes[] = $prefix . '-' . $col;
            }
        }
    }

    return implode(' ', $classes);
}

function bm_get_category_icon($term_id) {
    if (bm_is_onenav_active()) {
        $icon = get_term_meta($term_id, '_tag_ico', true);
        if (empty($icon)) {
            $icon = get_term_meta($term_id, '_bm_icon', true);
        }
    } else {
        $icon = get_term_meta($term_id, '_bm_icon', true);
        if (empty($icon)) {
            $icon = get_term_meta($term_id, '_tag_ico', true);
        }
    }
    return $icon ?: 'fas fa-folder';
}

function bm_get_category_order($term_id) {
    $order = get_term_meta($term_id, '_bm_order', true);
    if ($order === '') {
        $order = get_term_meta($term_id, '_term_order', true);
    }
    return $order !== '' ? absint($order) : 0;
}

function bm_format_card_style($style) {
    $allowed = array('min', 'max', 'big');
    return in_array($style, $allowed, true) ? $style : 'max';
}

function bm_enqueue_conditionally() {
    if (is_page()) {
        $page_template = get_page_template_slug();
        if ($page_template === 'templates/template-bookmark-nav.php') {
            return true;
        }
    }
    return false;
}

function bm_get_bing_wallpaper_url() {
    $cached = get_transient('bm_bing_wallpaper');
    if ($cached !== false) {
        return $cached;
    }

    $response = wp_remote_get('https://www.bing.com/HPImageArchive.aspx?format=js&idx=0&n=1&mkt=zh-CN');
    if (is_wp_error($response)) {
        return '';
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['images'][0]['url'])) {
        $url = 'https://www.bing.com' . $data['images'][0]['url'];
        set_transient('bm_bing_wallpaper', $url, DAY_IN_SECONDS);
        return $url;
    }

    return '';
}

function bm_generate_css_vars($settings) {
    $vars = $settings->get_css_vars();
    $dark_vars = $settings->get_dark_css_vars();

    $css = ':root {';
    foreach ($vars as $key => $value) {
        $css .= "{$key}: {$value};";
    }
    $css .= '}';

    $css .= '[data-theme="dark"] {';
    foreach ($dark_vars as $key => $value) {
        $css .= "{$key}: {$value};";
    }
    $css .= '}';

    return $css;
}
