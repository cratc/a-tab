<?php
if (!defined('ABSPATH')) {
    exit;
}

class BM_Settings {

    private $options = array();

    private $defaults = array();

    public function __construct() {
        $this->defaults = array(
            'data_source' => 'auto',
            'card_style' => 'max',
            'columns' => array(
                'sm' => 2,
                'md' => 3,
                'lg' => 4,
                'xl' => 5,
                'xxl' => 6,
            ),
            'icon_source' => 'api',
            'favicon_api' => 'https://www.google.com/s2/favicons?domain=',
            'global_goto' => false,
            'show_sidebar' => true,
            'sidebar_width' => 200,
            'content_width' => 1400,
            'card_border_radius' => 12,
            'card_gap' => 16,
            'primary_color' => '#4f46e5',
            'theme_mode' => 'auto-system',
            'dark_bg_color' => '#111827',
            'dark_card_bg' => '#1f2937',
            'dark_text_color' => '#f9fafb',
            'dark_text_muted' => '#9ca3af',
            'light_bg_color' => '#f5f5f5',
            'light_card_bg' => '#ffffff',
            'light_text_color' => '#1f2937',
            'light_text_muted' => '#6b7280',
            'bg_type' => 'color',
            'bg_color' => '#f5f5f5',
            'bg_image' => '',
            'bg_gradient_from' => '#667eea',
            'bg_gradient_to' => '#764ba2',
            'bg_gradient_direction' => 'to bottom right',
            'bing_wallpaper' => false,
            'search_style' => 'big',
            'search_engines' => array(
                array('id' => 1, 'name' => '百度', 'url' => 'https://www.baidu.com/s?wd=', 'icon' => ''),
                array('id' => 2, 'name' => 'Google', 'url' => 'https://www.google.com/search?q=', 'icon' => ''),
                array('id' => 3, 'name' => '必应', 'url' => 'https://www.bing.com/search?q=', 'icon' => ''),
            ),
            'default_search_engine' => 1,
            'search_suggestion' => true,
            'layout_modules' => array(
                array('type' => 'search', 'visible' => true),
                array('type' => 'content', 'visible' => true),
            ),
            'tab_ajax' => true,
            'lazy_load' => true,
            'link_check_enabled' => false,
            'link_check_frequency' => 72,
            'letter_ico' => false,
            'letter_ico_saturation' => 40,
            'letter_ico_brightness' => 90,
            'show_card_tags' => true,
            'togo_btn' => true,
            'custom_css' => '',
            'custom_js' => '',
        );
    }

    /**
     * 获取单个设置项
     *
     * @param string $key 设置键名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get($key, $default = null) {
        if (isset($this->options[$key])) {
            return $this->options[$key];
        }

        $option = get_option(BM_OPTION_PREFIX . $key);

        if ($option !== false) {
            $this->options[$key] = $option;
            return $option;
        }

        if (isset($this->defaults[$key])) {
            return $this->defaults[$key];
        }

        return $default;
    }

    /**
     * 设置单个设置项
     *
     * @param string $key 设置键名
     * @param mixed $value 设置值
     */
    public function set($key, $value) {
        $this->options[$key] = $value;
        update_option(BM_OPTION_PREFIX . $key, $value);
    }

    /**
     * 删除单个设置项
     *
     * @param string $key 设置键名
     */
    public function delete($key) {
        unset($this->options[$key]);
        delete_option(BM_OPTION_PREFIX . $key);
    }

    /**
     * 获取所有设置项（合并默认值）
     *
     * @return array
     */
    public function get_all() {
        $all = $this->defaults;
        foreach ($this->defaults as $key => $default) {
            $all[$key] = $this->get($key, $default);
        }
        return $all;
    }

    /**
     * 初始化默认设置项到数据库
     */
    public function set_defaults() {
        foreach ($this->defaults as $key => $value) {
            if (get_option(BM_OPTION_PREFIX . $key) === false) {
                add_option(BM_OPTION_PREFIX . $key, $value);
            }
        }
    }

    /**
     * 删除所有设置项
     */
    public function delete_all() {
        foreach ($this->defaults as $key => $value) {
            $this->delete($key);
        }
        delete_option('bm_plugin_version');
    }

    /**
     * 获取亮色模式 CSS 变量
     *
     * @return array
     */
    public function get_css_vars() {
        $settings = $this->get_all();
        $theme = $settings['theme_mode'];

        $vars = array(
            '--bm-primary' => $settings['primary_color'],
            '--bm-border-radius' => $settings['card_border_radius'] . 'px',
            '--bm-sidebar-width' => $settings['sidebar_width'] . 'px',
            '--bm-content-width' => $settings['content_width'] . 'px',
            '--bm-gap' => $settings['card_gap'] . 'px',
            '--bm-bg' => $settings['light_bg_color'],
            '--bm-card-bg' => $settings['light_card_bg'],
            '--bm-text' => $settings['light_text_color'],
            '--bm-text-muted' => $settings['light_text_muted'],
            '--bm-card-shadow' => '0 1px 3px rgba(0,0,0,0.1)',
        );

        return $vars;
    }

    /**
     * 获取暗色模式 CSS 变量
     *
     * @return array
     */
    public function get_dark_css_vars() {
        $settings = $this->get_all();
        return array(
            '--bm-bg' => $settings['dark_bg_color'],
            '--bm-card-bg' => $settings['dark_card_bg'],
            '--bm-text' => $settings['dark_text_color'],
            '--bm-text-muted' => $settings['dark_text_muted'],
            '--bm-card-shadow' => '0 1px 3px rgba(0,0,0,0.3)',
        );
    }

    /**
     * 根据列数设置生成响应式列类名
     *
     * @param array|null $columns 列数配置
     * @return string
     */
    public function get_columns_class($columns = null) {
        if ($columns === null) {
            $columns = $this->get('columns', $this->defaults['columns']);
        }

        $breakpoints = array(
            'sm' => 'col-sm',
            'md' => 'col-md',
            'lg' => 'col-lg',
            'xl' => 'col-xl',
            'xxl' => 'col-xxl',
        );

        $classes = array('col-12');
        foreach ($breakpoints as $bp => $prefix) {
            if (isset($columns[$bp]) && $columns[$bp] > 0) {
                $classes[] = $prefix . '-' . floor(12 / $columns[$bp]);
            }
        }

        return implode(' ', $classes);
    }

    /**
     * 清洗和验证设置输入
     *
     * @param array $input 原始输入数据
     * @return array 清洗后的数据
     */
    public function sanitize_settings($input) {
        $sanitized = array();

        if (isset($input['primary_color'])) {
            $sanitized['primary_color'] = sanitize_hex_color($input['primary_color']);
        }
        if (isset($input['card_style'])) {
            $sanitized['card_style'] = in_array($input['card_style'], array('min', 'max', 'big'), true) ? $input['card_style'] : 'max';
        }
        if (isset($input['card_border_radius'])) {
            $sanitized['card_border_radius'] = absint($input['card_border_radius']);
        }
        if (isset($input['card_gap'])) {
            $sanitized['card_gap'] = absint($input['card_gap']);
        }
        if (isset($input['content_width'])) {
            $sanitized['content_width'] = absint($input['content_width']);
        }
        if (isset($input['sidebar_width'])) {
            $sanitized['sidebar_width'] = absint($input['sidebar_width']);
        }
        if (isset($input['theme_mode'])) {
            $sanitized['theme_mode'] = in_array($input['theme_mode'], array('manual-light', 'manual-dark', 'auto-system', 'time-auto'), true) ? $input['theme_mode'] : 'auto-system';
        }
        if (isset($input['bg_type'])) {
            $sanitized['bg_type'] = in_array($input['bg_type'], array('color', 'gradient', 'image', 'bing'), true) ? $input['bg_type'] : 'color';
        }
        if (isset($input['bg_image'])) {
            $sanitized['bg_image'] = esc_url_raw($input['bg_image']);
        }
        if (isset($input['search_style'])) {
            $sanitized['search_style'] = in_array($input['search_style'], array('big', 'simple'), true) ? $input['search_style'] : 'big';
        }
        if (isset($input['global_goto'])) {
            $sanitized['global_goto'] = (bool) $input['global_goto'];
        }
        if (isset($input['show_sidebar'])) {
            $sanitized['show_sidebar'] = (bool) $input['show_sidebar'];
        }
        if (isset($input['tab_ajax'])) {
            $sanitized['tab_ajax'] = (bool) $input['tab_ajax'];
        }
        if (isset($input['lazy_load'])) {
            $sanitized['lazy_load'] = (bool) $input['lazy_load'];
        }
        if (isset($input['search_suggestion'])) {
            $sanitized['search_suggestion'] = (bool) $input['search_suggestion'];
        }
        if (isset($input['letter_ico'])) {
            $sanitized['letter_ico'] = (bool) $input['letter_ico'];
        }
        if (isset($input['show_card_tags'])) {
            $sanitized['show_card_tags'] = (bool) $input['show_card_tags'];
        }
        if (isset($input['togo_btn'])) {
            $sanitized['togo_btn'] = (bool) $input['togo_btn'];
        }
        if (isset($input['columns']) && is_array($input['columns'])) {
            $sanitized['columns'] = array();
            foreach (array('sm', 'md', 'lg', 'xl', 'xxl') as $bp) {
                $sanitized['columns'][$bp] = isset($input['columns'][$bp]) ? absint($input['columns'][$bp]) : 2;
            }
        }
        if (isset($input['search_engines']) && is_array($input['search_engines'])) {
            $sanitized['search_engines'] = array();
            foreach ($input['search_engines'] as $engine) {
                $sanitized['search_engines'][] = array(
                    'id' => absint($engine['id'] ?? 0),
                    'name' => sanitize_text_field($engine['name'] ?? ''),
                    'url' => esc_url_raw($engine['url'] ?? ''),
                    'icon' => esc_url_raw($engine['icon'] ?? ''),
                );
            }
        }
        if (isset($input['custom_css'])) {
            $sanitized['custom_css'] = wp_strip_all_tags($input['custom_css']);
        }

        return $sanitized;
    }
}
