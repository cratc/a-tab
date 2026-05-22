<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!isset($bookmark)) return;

$link = ($bookmark['goto'] || $settings->get('global_goto', false)) ? $bookmark['link'] : $bookmark['permalink'];
$target = ($bookmark['goto'] || $settings->get('global_goto', false)) ? '_blank' : '_self';
$icon = $bookmark['icon'];
$letter_icon = '';

if (empty($icon) && $settings->get('letter_ico', false)) {
    $icon_fetcher = new BM_Icon_Fetcher();
    $letter_data = $icon_fetcher->get_letter_icon_data($bookmark['title']);
    $letter_icon = $letter_data['letter'];
    $letter_bg = $letter_data['bg_color'];
    $letter_color = $letter_data['text_color'];
}

$show_tags = $settings->get('show_card_tags', true);
$togo_btn = $settings->get('togo_btn', true);
?>

<article class="bm-card bm-card--max<?php echo $bookmark['is_dead'] ? ' bm-card--dead' : ''; ?>">
    <a href="<?php echo esc_url($link); ?>" target="<?php echo esc_attr($target); ?>"
       class="bm-card-link" title="<?php echo esc_attr($bookmark['describe'] ?: $bookmark['link']); ?>"
       <?php if ($bookmark['nofollow']) echo 'rel="nofollow"'; ?>>
        <div class="bm-card-icon">
            <?php if ($icon) : ?>
            <img src="<?php echo esc_url($icon); ?>" alt="<?php echo esc_attr($bookmark['title']); ?>" loading="lazy" />
            <?php elseif ($letter_icon) : ?>
            <span class="bm-card-letter" style="background-color: <?php echo esc_attr($letter_bg ?? '#667eea'); ?>; color: <?php echo esc_attr($letter_color ?? '#fff'); ?>;">
                <?php echo esc_html($letter_icon); ?>
            </span>
            <?php else : ?>
            <span class="bm-card-letter bm-card-letter--default">
                <?php echo esc_html(mb_substr($bookmark['title'], 0, 1, 'UTF-8')); ?>
            </span>
            <?php endif; ?>
        </div>
        <div class="bm-card-body">
            <h3 class="bm-card-title"><?php echo esc_html($bookmark['title']); ?></h3>
            <?php if ($bookmark['describe']) : ?>
            <p class="bm-card-desc"><?php echo esc_html($bookmark['describe']); ?></p>
            <?php endif; ?>
        </div>
    </a>
    <?php if ($show_tags || $togo_btn) : ?>
    <div class="bm-card-footer">
        <?php if ($show_tags && !empty($bookmark['tags'])) : ?>
        <div class="bm-card-tags">
            <?php foreach (array_slice($bookmark['tags'], 0, 2) as $tag) : ?>
            <span class="bm-card-tag"><?php echo esc_html($tag->name); ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if ($togo_btn && !$bookmark['goto'] && !$settings->get('global_goto', false)) : ?>
        <a href="<?php echo esc_url($bookmark['link']); ?>" target="_blank" class="bm-card-togo" title="<?php esc_attr_e('直达', 'bookmark-nav'); ?>" rel="nofollow">
            <i class="fas fa-external-link-alt"></i>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</article>
