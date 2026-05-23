<?php if (!defined('ABSPATH')) exit; ?>

<?php
if (!isset($data) || !is_array($data)) {
    $data = ['page_id' => 0, 'settings' => [], 'groups' => [], 'nav_items' => [], 'pages' => [], 'active_page_id' => 0, 'current_user' => ['logged_in' => false, 'avatar_url' => '', 'display_name' => '', 'login_url' => wp_login_url(home_url($_SERVER['REQUEST_URI'] ?? '/')), 'logout_url' => wp_logout_url(home_url($_SERVER['REQUEST_URI'] ?? '/'))], 'dock_items' => [], 'user_logged_in' => false];
}

$s = $data['settings'];
$clock_visible = !empty($s['clock.visible']);
$dock_visible = !empty($s['dock.visible']);
$sidebar_mode = $s['sidebar.mode'] ?? 'always';
$active_page_id = $data['active_page_id'] ?? 0;
$wallpaper_type = $s['wallpaper.type'] ?? 'color';
$wallpaper_value = $s['wallpaper.value'] ?? '#1a1a2e';
$wallpaper_blur = $s['wallpaper.blur'] ?? 20;
$wallpaper_overlay = $s['wallpaper.overlay'] ?? 15;

$filter_str = 'blur(' . intval($wallpaper_blur) . 'px) brightness(' . (100 - intval($wallpaper_overlay)) . '%)';
$base_style = 'position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;pointer-events:none;background-size:cover;background-position:center;background-repeat:no-repeat;filter:' . $filter_str . ';';

$wallpaper_style = '';
switch ($wallpaper_type) {
    case 'color': $wallpaper_style = $base_style . 'background-color:' . esc_attr($wallpaper_value) . ';'; break;
    case 'gradient':
        $gf = $s['wallpaper.gradient_from'] ?? '#0c0c1d';
        $gt = $s['wallpaper.gradient_to'] ?? '#16213e';
        $wallpaper_style = $base_style . 'background:linear-gradient(135deg,' . esc_attr($gf) . ' 0%,' . esc_attr($gt) . ' 100%);';
        break;
    case 'image':
        $iu = $s['wallpaper.image_url'] ?? $wallpaper_value;
        $wallpaper_style = $base_style . 'background-image:url(' . esc_url($iu) . ');';
        break;
    case 'bing':
        $bing_url = function_exists('bm_get_bing_wallpaper_url') ? bm_get_bing_wallpaper_url() : '';
        $wallpaper_style = $bing_url ? $base_style . 'background-image:url(' . esc_url($bing_url) . ');' : $base_style . 'background-color:#1a1a2e;';
        break;
    default: $wallpaper_style = $base_style . 'background-color:#1a1a2e;';
}
?>

<div class="bm-nav-page"
     data-page-id="<?php echo esc_attr($data['page_id'] ?? 0); ?>"
     data-active-page-id="<?php echo esc_attr($active_page_id); ?>">

    <div class="bm-wallpaper" style="<?php echo $wallpaper_style; ?>"></div>

    <aside class="bm-sidebar<?php echo $sidebar_mode === 'autohide' ? ' bm-sidebar--autohide' : ''; ?>" id="bmSidebar">
        <?php require __DIR__ . '/sidebar.php'; ?>
    </aside>

    <main class="bm-main-content" id="bmMainContent">
        <?php if ($clock_visible): ?>
            <?php require __DIR__ . '/clock.php'; ?>
        <?php endif; ?>

        <?php require __DIR__ . '/search-bar.php'; ?>

        <div class="bm-canvas" id="bmCanvas">
            <?php if (!empty($data['groups'])): ?>
                <?php foreach ($data['groups'] as $group): ?>
                <section class="bm-group-section" data-group-id="<?php echo esc_attr($group->id); ?>" data-page-id="<?php echo esc_attr($group->page_id ?? 0); ?>">
                    <?php require __DIR__ . '/group-section.php'; ?>
                </section>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($data['nav_items']['ungrouped'])): ?>
                <?php
                $group = (object)['id' => 0, 'title' => '', 'icon' => '', 'page_id' => $active_page_id, 'columns' => null, 'text_color' => null, 'show_text' => null];
                $group_items = $data['nav_items']['ungrouped'];
                require __DIR__ . '/group-section.php';
                ?>
            <?php endif; ?>
        </div>
    </main>

    <?php if ($dock_visible): ?>
    <nav class="bm-dock-bar" id="bmDockBar">
        <?php require __DIR__ . '/dock-bar.php'; ?>
    </nav>
    <?php endif; ?>

    <div class="bm-context-menu-wrapper" id="bmContextMenuWrapper"></div>

    <div class="bm-settings-panel" id="bmSettingsPanel">
        <?php require __DIR__ . '/settings-panel.php'; ?>
    </div>

    <div class="bm-modal-overlay" id="bmPickerModal">
        <div class="bm-modal-content bm-picker-modal">
            <?php require __DIR__ . '/bookmark-picker.php'; ?>
        </div>
    </div>

    <div class="bm-modal-overlay" id="bmEditModal">
        <div class="bm-modal-content bm-edit-modal">
            <?php require __DIR__ . '/bookmark-edit.php'; ?>
        </div>
    </div>

    <div class="bm-modal-overlay" id="bmNewGroupModal">
        <div class="bm-modal-content bm-new-group-modal">
            <?php require __DIR__ . '/new-group-form.php'; ?>
        </div>
    </div>

    <div class="bm-modal-overlay" id="bmFolderModal">
        <div class="bm-modal-content bm-folder-expand-modal">
            <div class="bm-folder-expand-header">
                <button class="bm-mac-dot bm-mac-dot--close" data-close="bmFolderModal"></button>
                <h3 class="bm-folder-expand-title" id="bmFolderExpandTitle">文件夹</h3>
                <div style="width:12px"></div>
            </div>
            <div class="bm-folder-expand-grid" id="bmFolderExpandGrid"></div>
        </div>
    </div>

    <div class="bm-modal-overlay" id="bmMemoModal">
        <div class="bm-modal-content bm-memo-modal">
            <div id="bmMemoModalContent"></div>
        </div>
    </div>

    <button class="bm-mobile-toggle" id="bmMobileToggle" aria-label="菜单">
        <span></span><span></span><span></span>
    </button>
</div>
