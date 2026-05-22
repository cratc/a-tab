<?php
if (!defined('ABSPATH')) {
    exit;
}

class BM_Bookmark_Post_Type {

    public function register_post_type() {
        $labels = array(
            'name' => __('书签', 'bookmark-nav'),
            'singular_name' => __('书签', 'bookmark-nav'),
            'menu_name' => __('书签管理', 'bookmark-nav'),
            'add_new' => __('添加书签', 'bookmark-nav'),
            'add_new_item' => __('添加新书签', 'bookmark-nav'),
            'edit_item' => __('编辑书签', 'bookmark-nav'),
            'new_item' => __('新书签', 'bookmark-nav'),
            'view_item' => __('查看书签', 'bookmark-nav'),
            'search_items' => __('搜索书签', 'bookmark-nav'),
            'not_found' => __('未找到书签', 'bookmark-nav'),
            'not_found_in_trash' => __('回收站中没有书签', 'bookmark-nav'),
            'all_items' => __('所有书签', 'bookmark-nav'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'bookmark'),
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-bookmark',
            'show_in_rest' => true,
            'supports' => array('title', 'author', 'custom-fields'),
        );

        register_post_type(BM_POST_TYPE, $args);
    }

    public function register_taxonomies() {
        $category_labels = array(
            'name' => __('书签分类', 'bookmark-nav'),
            'singular_name' => __('书签分类', 'bookmark-nav'),
            'search_items' => __('搜索分类', 'bookmark-nav'),
            'all_items' => __('所有分类', 'bookmark-nav'),
            'parent_item' => __('父级分类', 'bookmark-nav'),
            'parent_item_colon' => __('父级分类：', 'bookmark-nav'),
            'edit_item' => __('编辑分类', 'bookmark-nav'),
            'update_item' => __('更新分类', 'bookmark-nav'),
            'add_new_item' => __('添加新分类', 'bookmark-nav'),
            'new_item_name' => __('新分类名称', 'bookmark-nav'),
            'menu_name' => __('书签分类', 'bookmark-nav'),
        );

        $category_args = array(
            'hierarchical' => true,
            'labels' => $category_labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'bookmark-category'),
        );

        register_taxonomy(BM_TAXONOMY_CATEGORY, array(BM_POST_TYPE), $category_args);

        $tag_labels = array(
            'name' => __('书签标签', 'bookmark-nav'),
            'singular_name' => __('书签标签', 'bookmark-nav'),
            'search_items' => __('搜索标签', 'bookmark-nav'),
            'all_items' => __('所有标签', 'bookmark-nav'),
            'edit_item' => __('编辑标签', 'bookmark-nav'),
            'update_item' => __('更新标签', 'bookmark-nav'),
            'add_new_item' => __('添加新标签', 'bookmark-nav'),
            'new_item_name' => __('新标签名称', 'bookmark-nav'),
            'menu_name' => __('书签标签', 'bookmark-nav'),
        );

        $tag_args = array(
            'hierarchical' => false,
            'labels' => $tag_labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'bookmark-tag'),
        );

        register_taxonomy(BM_TAXONOMY_TAG, array(BM_POST_TYPE), $tag_args);
    }

    public function add_meta_boxes() {
        add_meta_box(
            'bm_bookmark_details',
            __('书签信息', 'bookmark-nav'),
            array($this, 'render_meta_box'),
            BM_POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('bm_save_bookmark', 'bm_bookmark_nonce');

        $link = get_post_meta($post->ID, '_bm_link', true);
        $describe = get_post_meta($post->ID, '_bm_describe', true);
        $thumbnail = get_post_meta($post->ID, '_bm_thumbnail', true);
        $preview = get_post_meta($post->ID, '_bm_preview', true);
        $order = get_post_meta($post->ID, '_bm_order', true);
        $goto = get_post_meta($post->ID, '_bm_goto', true);
        $nofollow = get_post_meta($post->ID, '_bm_nofollow', true);

        ?>
        <div class="bm-meta-box">
            <p>
                <label for="bm_link"><strong><?php esc_html_e('链接地址', 'bookmark-nav'); ?></strong></label><br>
                <input type="url" id="bm_link" name="bm_link" value="<?php echo esc_attr($link); ?>" class="widefat" placeholder="https://" />
            </p>
            <p>
                <label for="bm_describe"><strong><?php esc_html_e('网站描述', 'bookmark-nav'); ?></strong></label><br>
                <textarea id="bm_describe" name="bm_describe" class="widefat" rows="2" maxlength="80"><?php echo esc_textarea($describe); ?></textarea>
            </p>
            <p>
                <label for="bm_thumbnail"><strong><?php esc_html_e('网站图标', 'bookmark-nav'); ?></strong></label><br>
                <input type="text" id="bm_thumbnail" name="bm_thumbnail" value="<?php echo esc_attr($thumbnail); ?>" class="widefat" />
                <button type="button" class="button bm-upload-btn" data-target="bm_thumbnail"><?php esc_html_e('选择图片', 'bookmark-nav'); ?></button>
            </p>
            <p>
                <label for="bm_preview"><strong><?php esc_html_e('网站预览图', 'bookmark-nav'); ?></strong></label><br>
                <input type="text" id="bm_preview" name="bm_preview" value="<?php echo esc_attr($preview); ?>" class="widefat" />
                <button type="button" class="button bm-upload-btn" data-target="bm_preview"><?php esc_html_e('选择图片', 'bookmark-nav'); ?></button>
            </p>
            <p>
                <label for="bm_order"><strong><?php esc_html_e('排序', 'bookmark-nav'); ?></strong></label><br>
                <input type="number" id="bm_order" name="bm_order" value="<?php echo esc_attr($order ?: 0); ?>" class="small-text" />
            </p>
            <p>
                <label>
                    <input type="checkbox" name="bm_goto" value="1" <?php checked($goto, '1'); ?> />
                    <?php esc_html_e('直达目标站', 'bookmark-nav'); ?>
                </label>
            </p>
            <p>
                <label>
                    <input type="checkbox" name="bm_nofollow" value="1" <?php checked($nofollow, '1'); ?> />
                    <?php esc_html_e('Nofollow', 'bookmark-nav'); ?>
                </label>
            </p>
        </div>
        <?php
    }

    public function save_meta_data($post_id) {
        if (!isset($_POST['bm_bookmark_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['bm_bookmark_nonce'], 'bm_save_bookmark')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $fields = array(
            'bm_link' => '_bm_link',
            'bm_describe' => '_bm_describe',
            'bm_thumbnail' => '_bm_thumbnail',
            'bm_preview' => '_bm_preview',
            'bm_order' => '_bm_order',
        );

        foreach ($fields as $post_key => $meta_key) {
            if (isset($_POST[$post_key])) {
                if ($post_key === 'bm_link') {
                    $value = bm_sanitize_url($_POST[$post_key]);
                } elseif ($post_key === 'bm_describe') {
                    $value = sanitize_textarea_field($_POST[$post_key]);
                } elseif ($post_key === 'bm_order') {
                    $value = absint($_POST[$post_key]);
                } else {
                    $value = sanitize_text_field($_POST[$post_key]);
                }
                update_post_meta($post_id, $meta_key, $value);
            }
        }

        $goto = isset($_POST['bm_goto']) ? '1' : '0';
        update_post_meta($post_id, '_bm_goto', $goto);

        $nofollow = isset($_POST['bm_nofollow']) ? '1' : '0';
        update_post_meta($post_id, '_bm_nofollow', $nofollow);
    }
}
