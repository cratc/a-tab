<?php
if (!defined('ABSPATH')) exit;

$group_items = $data['nav_items'][$group->id] ?? ($data['nav_items']['ungrouped'] ?? []);
if (empty($group_items)) $group_items = [];

$global_settings = $data['settings'] ?? [];
$group_text_color = $group->text_color ?? ($global_settings['appearance.text_color'] ?? '#374151');
$show_text = $group->show_text ?? 1;
$is_folder = !empty($group->is_folder);
$folder_layout = $group->layout ?? '1x1';
?>

<?php if ($is_folder): ?>
<div class="bm-item bm-item--folder layout-<?php echo esc_attr($folder_layout); ?>" data-id="folder_<?php echo esc_attr($group->id); ?>" data-type="folder" data-group-id="<?php echo esc_attr($group->id); ?>" data-layout="<?php echo esc_attr($folder_layout); ?>" draggable="false">
    <div class="bm-item-icon-box bm-item-folder-box">
        <div class="bm-folder-grid">
            <?php
            $display_items = array_slice($group_items, 0, 4);
            foreach ($display_items as $item):
                if (!empty($item->icon)):
            ?>
                <div class="bm-folder-item" data-id="<?php echo esc_attr($item->id); ?>">
                    <img src="<?php echo esc_url($item->icon); ?>" alt="" loading="lazy">
                </div>
            <?php elseif (!empty($item->text_icon)): ?>
                <div class="bm-folder-item" data-id="<?php echo esc_attr($item->id); ?>">
                    <span class="bm-item-letter" style="background:<?php echo !empty($item->bg_color) ? esc_attr($item->bg_color) : '#6366f1'; ?>"><?php echo esc_html(mb_substr($item->text_icon, 0, 1)); ?></span>
                </div>
            <?php else: ?>
                <div class="bm-folder-item" data-id="<?php echo esc_attr($item->id); ?>">
                    <span class="bm-item-letter" style="background:#94a3b8;"><?php echo esc_html(mb_substr($item->title ?? '?', 0, 1)); ?></span>
                </div>
            <?php endif; endforeach;
            if (empty($group_items)): ?>
                <div class="bm-folder-empty">拖入图标</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="bm-item-name"><?php echo esc_html($group->title ?? '新文件夹'); ?></div>
</div>
<?php elseif (!empty($group->id) && $group->id > 0 && !empty($group->title)): ?>
<div class="bm-group-header">
    <span class="bm-group-icon"><?php echo !empty($group->icon) ? esc_html($group->icon) : '📁'; ?></span>
    <h3 class="bm-group-title"><?php echo esc_html($group->title); ?></h3>
    <span class="bm-group-count"><?php echo count($group_items); ?></span>
</div>
<?php endif; ?>

<?php if (!$is_folder): ?>
<div class="bm-canvas-grid">
    <?php foreach ($group_items as $item): ?>
        <?php if (isset($item->source_type) && $item->source_type === 'card'): ?>
        <div class="bm-component-card layout-<?php echo esc_attr($item->layout ?? 'auto'); ?>"
             data-id="<?php echo esc_attr($item->id); ?>"
             data-type="component"
             data-component-id="<?php echo esc_attr($item->component_id ?? ''); ?>">
            <div class="bm-component-placeholder">
                <span class="bm-component-icon"><?php echo !empty($item->icon) ? esc_html($item->icon) : '🧩'; ?></span>
                <span class="bm-component-title"><?php echo esc_html($item->title ?? ''); ?></span>
                <small>组件即将上线</small>
            </div>
        </div>
        <?php else: ?>
        <div class="bm-item layout-<?php echo esc_attr($item->layout ?? 'auto'); ?>"
             data-id="<?php echo esc_attr($item->id ?? ''); ?>"
             data-type="bookmark"
             data-url="<?php echo esc_url($item->url ?? ''); ?>"
             data-layout="<?php echo esc_attr($item->layout ?? 'auto'); ?>"
             draggable="true">
            <div class="bm-item-icon-box"<?php if (!empty($item->bg_color)): ?> style="background-color: <?php echo esc_attr($item->bg_color); ?>;"<?php endif; ?>>
                <?php if (!empty($item->icon)): ?>
                    <img src="<?php echo esc_url($item->icon); ?>" alt="" loading="lazy">
                <?php elseif (!empty($item->text_icon)): ?>
                    <span class="bm-item-letter" style="background:<?php echo !empty($item->bg_color) ? esc_attr($item->bg_color) : '#6366f1'; ?>">
                        <?php echo esc_html(mb_substr($item->text_icon, 0, 1)); ?>
                    </span>
                <?php else: ?>
                    <span class="bm-item-letter" style="background:#94a3b8;">
                        <?php echo esc_html(mb_substr($item->title ?? '?', 0, 1)); ?>
                    </span>
                <?php endif; ?>
            </div>
            <?php if ($show_text): ?>
            <div class="bm-item-name" style="color: <?php echo esc_attr($group_text_color); ?>">
                <?php echo esc_html($item->title ?? ''); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<?php if (empty($group_items)): ?>
<div class="bm-group-empty">
    <p>暂无内容，右键添加标签</p>
</div>
<?php endif; ?>
<?php endif; ?>
