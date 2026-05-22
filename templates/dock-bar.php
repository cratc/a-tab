<?php if (!defined('ABSPATH')) exit; ?>
<div class="bm-dock-inner" id="bmDockInner">
    <?php if (!empty($data['dock_items'])): ?>
        <?php foreach ($data['dock_items'] as $item): ?>
        <div class="bm-dock-item" data-id="<?php echo esc_attr($item->id); ?>" data-url="<?php echo esc_url($item->url ?? ''); ?>" data-title="<?php echo esc_attr($item->title ?? ''); ?>">
            <div class="bm-dock-item-icon">
                <?php if (!empty($item->icon)): ?>
                    <img src="<?php echo esc_url($item->icon); ?>" alt="" loading="lazy">
                <?php elseif (!empty($item->text_icon)): ?>
                    <span class="bm-dock-text-icon" style="background:<?php echo !empty($item->bg_color) ? esc_attr($item->bg_color) : '#6366f1'; ?>">
                        <?php echo esc_html(mb_substr($item->text_icon, 0, 1)); ?>
                    </span>
                <?php else: ?>
                    <span class="bm-dock-text-icon" style="background:#94a3b8;">
                        <?php echo esc_html(mb_substr($item->title ?? '?', 0, 1)); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
