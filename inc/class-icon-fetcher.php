<?php
if (!defined('ABSPATH')) {
    exit;
}

class BM_Icon_Fetcher {

    private $api_url;

    public function __construct() {
        $this->api_url = bm_get_option('favicon_api', 'https://www.google.com/s2/favicons?domain=');
    }

    /**
     * 获取网站图标URL
     */
    public function get_icon($url, $size = 64) {
        $domain = bm_extract_domain($url);
        if (!$domain) {
            return '';
        }

        $cached = $this->get_cached_icon($domain);
        if ($cached) {
            return $cached;
        }

        $icon_url = $this->fetch_from_api($domain, $size);
        if ($icon_url) {
            $this->cache_icon($domain, $icon_url);
            return $icon_url;
        }

        return '';
    }

    /**
     * 从缓存中获取图标
     */
    private function get_cached_icon($domain) {
        $cache_key = 'bm_icon_' . md5($domain);
        $cached = get_transient($cache_key);
        return $cached !== false ? $cached : '';
    }

    /**
     * 缓存图标URL
     */
    private function cache_icon($domain, $icon_url) {
        $cache_key = 'bm_icon_' . md5($domain);
        set_transient($cache_key, $icon_url, WEEK_IN_SECONDS);
    }

    /**
     * 从API获取图标
     */
    private function fetch_from_api($domain, $size = 64) {
        if (strpos($this->api_url, 'google.com') !== false) {
            return $this->api_url . $domain . '&sz=' . $size;
        }

        return $this->api_url . $domain;
    }

    /**
     * 获取字母图标数据（用于无图标时的降级显示）
     */
    public function get_letter_icon_data($title) {
        $letter = mb_substr($title, 0, 1, 'UTF-8');
        $hash = md5($title);
        $hue = hexdec(substr($hash, 0, 6)) % 360;
        $saturation = bm_get_option('letter_ico_saturation', 40);
        $brightness = bm_get_option('letter_ico_brightness', 90);

        return array(
            'letter' => strtoupper($letter),
            'bg_color' => "hsl({$hue}, {$saturation}%, {$brightness}%)",
            'text_color' => $brightness > 50 ? '#ffffff' : '#000000',
        );
    }

    /**
     * 清除图标缓存
     */
    public function clear_cache() {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_bm_icon_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_bm_icon_%'");
    }
}
