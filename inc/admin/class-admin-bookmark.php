<?php
if (!defined('ABSPATH')) {
    exit;
}

class BM_Admin_Bookmark {

    private $data_source;

    public function __construct(BM_Data_Source $data_source) {
        $this->data_source = $data_source;
    }

    /**
     * 添加书签管理子菜单
     */
    public function add_menu_page() {
        add_submenu_page(
            'bookmark-nav-settings',
            __('书签管理', 'bookmark-nav'),
            __('书签管理', 'bookmark-nav'),
            'manage_options',
            'bookmark-nav-bookmarks',
            array($this, 'render_page')
        );
    }

    /**
     * 渲染书签管理页面
     */
    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $categories = $this->data_source->get_categories(0, false);
        $filter_cat = isset($_GET['cat']) ? absint($_GET['cat']) : 0;

        $args = array('posts_per_page' => 20);
        if ($filter_cat) {
            $bookmarks = $this->data_source->get_bookmarks_by_term($filter_cat);
        } else {
            $bookmarks = $this->data_source->get_bookmarks($args);
        }

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('书签管理', 'bookmark-nav'); ?></h1>
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=' . BM_POST_TYPE)); ?>" class="page-title-action"><?php esc_html_e('添加书签', 'bookmark-nav'); ?></a>

            <div class="bm-bookmark-filters">
                <select onchange="location.href='<?php echo esc_url(admin_url('admin.php?page=bookmark-nav-bookmarks&cat=')); ?>' + this.value;">
                    <option value="0"><?php esc_html_e('全部分类', 'bookmark-nav'); ?></option>
                    <?php foreach ($categories as $cat) : ?>
                    <option value="<?php echo esc_attr($cat->term_id); ?>" <?php selected($filter_cat, $cat->term_id); ?>><?php echo esc_html($cat->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('图标', 'bookmark-nav'); ?></th>
                        <th><?php esc_html_e('名称', 'bookmark-nav'); ?></th>
                        <th><?php esc_html_e('链接', 'bookmark-nav'); ?></th>
                        <th><?php esc_html_e('分类', 'bookmark-nav'); ?></th>
                        <th><?php esc_html_e('状态', 'bookmark-nav'); ?></th>
                        <th><?php esc_html_e('操作', 'bookmark-nav'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookmarks)) : ?>
                    <tr><td colspan="6"><?php esc_html_e('暂无书签', 'bookmark-nav'); ?></td></tr>
                    <?php else : ?>
                    <?php foreach ($bookmarks as $bookmark) : ?>
                    <tr>
                        <td>
                            <?php if ($bookmark['icon']) : ?>
                            <img src="<?php echo esc_url($bookmark['icon']); ?>" alt="" width="24" height="24" />
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo esc_html($bookmark['title']); ?></strong></td>
                        <td><a href="<?php echo esc_url($bookmark['link']); ?>" target="_blank" rel="nofollow"><?php echo esc_html(mb_substr($bookmark['link'], 0, 50)); ?></a></td>
                        <td>
                            <?php foreach ($bookmark['categories'] as $cat) : ?>
                            <?php echo esc_html($cat->name); ?>
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <?php if ($bookmark['is_dead']) : ?>
                            <span class="bm-status bm-status--dead"><?php esc_html_e('死链', 'bookmark-nav'); ?></span>
                            <?php else : ?>
                            <span class="bm-status bm-status--ok"><?php esc_html_e('正常', 'bookmark-nav'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url(get_edit_post_link($bookmark['id'])); ?>"><?php esc_html_e('编辑', 'bookmark-nav'); ?></a> |
                            <a href="<?php echo esc_url(get_delete_post_link($bookmark['id'])); ?>" class="bm-delete-link" onclick="return confirm('<?php esc_attr_e('确定删除？', 'bookmark-nav'); ?>');"><?php esc_html_e('删除', 'bookmark-nav'); ?></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
