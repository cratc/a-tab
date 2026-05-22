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
?>

<article class="bm-card bm-card--min">
    <a href="<?php echo esc_url($link); ?>" target="<?php echo esc_attr($target); ?>"
       class="bm-card-link" title="<?php echo esc_attr($bookmark['title']); ?>"
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
        </div>
    </a>
</article>
