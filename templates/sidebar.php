<?php if (!defined('ABSPATH')) exit; ?>

<div class="bm-sidebar-inner">
    <div class="bm-sidebar-avatar" id="bmSidebarAvatar">
        <?php if (!empty($data['current_user']['logged_in'])): ?>
            <img src="<?php echo esc_url($data['current_user']['avatar_url'] ?? ''); ?>" alt="" class="bm-avatar-img">
            <span class="bm-avatar-name"><?php echo esc_html($data['current_user']['display_name'] ?? ''); ?></span>
        <?php else: ?>
            <div class="bm-avatar-default">👤</div>
            <span class="bm-avatar-name">未登录</span>
        <?php endif; ?>
    </div>

    <div class="bm-sidebar-divider"></div>

    <div class="bm-sidebar-pages" id="bmSidebarPages">
        <?php if (!empty($data['pages'])): ?>
            <?php foreach ($data['pages'] as $page): ?>
            <div class="bm-sidebar-page<?php echo !empty($data['active_page_id']) && $data['active_page_id'] == $page->id ? ' is-active' : ''; ?>"
                 data-page-id="<?php echo esc_attr($page->id); ?>"
                 data-page-title="<?php echo esc_attr($page->title); ?>"
                 data-is-default="<?php echo !empty($page->is_default) ? '1' : '0'; ?>">
                <span class="bm-sidebar-page-icon"><?php echo esc_html($page->icon ?? '📁'); ?></span>
                <span class="bm-sidebar-page-title"><?php echo esc_html($page->title ?? ''); ?></span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <div class="bm-sidebar-page bm-sidebar-add-page" id="bmSidebarAddPage">
            <span class="bm-sidebar-page-icon">➕</span>
            <span class="bm-sidebar-page-title">添加</span>
        </div>
    </div>

    <div class="bm-sidebar-spacer"></div>

    <div class="bm-sidebar-bottom">
        <a class="bm-sidebar-page bm-sidebar-home-btn" href="<?php echo esc_url(home_url()); ?>" target="_blank" rel="noopener">
            <span class="bm-sidebar-page-icon">🏠</span>
            <span class="bm-sidebar-page-title">首页</span>
        </a>
        <div class="bm-sidebar-page bm-sidebar-settings-btn" id="bmSidebarSettingsBtn">
            <span class="bm-sidebar-page-icon">⚙️</span>
            <span class="bm-sidebar-page-title">设置</span>
        </div>
    </div>
</div>
