<?php if (!defined('ABSPATH')) exit; ?>
<div class="bm-settings-header">
    <div class="bm-mac-traffic-lights">
        <button class="bm-mac-dot bm-mac-dot--close" id="bmSettingsClose"></button>
        <button class="bm-mac-dot bm-mac-dot--minimize"></button>
        <button class="bm-mac-dot bm-mac-dot--maximize"></button>
    </div>
    <h3 class="bm-settings-title">设置</h3>
    <div class="bm-settings-header-spacer"></div>
</div>

<div class="bm-settings-tabs">
    <button class="bm-settings-tab is-active" data-tab="general">常规</button>
    <button class="bm-settings-tab" data-tab="appearance">外观</button>
    <button class="bm-settings-tab" data-tab="wallpaper">壁纸</button>
    <button class="bm-settings-tab" data-tab="sidebar">侧栏</button>
    <button class="bm-settings-tab" data-tab="dock">Dock</button>
    <button class="bm-settings-tab" data-tab="about">关于</button>
</div>

<div class="bm-settings-body">
    <div class="bm-settings-panel-tab is-active" data-tab="general">
        <div class="bm-form-group">
            <label class="bm-form-label">显示时钟</label>
            <label class="bm-form-toggle">
                <input type="checkbox" name="clock.visible" value="1" <?php checked(!empty($data['settings']['clock.visible'])); ?>>
                <span class="bm-form-toggle-slider"></span>
            </label>
        </div>
        <div class="bm-form-group">
            <label class="bm-form-label">24小时制</label>
            <label class="bm-form-toggle">
                <input type="checkbox" name="clock.format_24h" value="1" <?php checked(!empty($data['settings']['clock.format_24h'])); ?>>
                <span class="bm-form-toggle-slider"></span>
            </label>
        </div>
        <div class="bm-form-group">
            <label class="bm-form-label">默认搜索引擎</label>
            <select class="bm-form-select" name="search.engine">
                <option value="baidu" <?php selected(($data['settings']['search.engine'] ?? 'baidu') === 'baidu'); ?>>百度</option>
                <option value="google" <?php selected(($data['settings']['search.engine'] ?? 'baidu') === 'google'); ?>>Google</option>
                <option value="bing" <?php selected(($data['settings']['search.engine'] ?? 'baidu') === 'bing'); ?>>必应</option>
                <option value="sogou" <?php selected(($data['settings']['search.engine'] ?? 'baidu') === 'sogou'); ?>>搜狗</option>
            </select>
        </div>
    </div>

    <div class="bm-settings-panel-tab" data-tab="appearance">
        <div class="bm-form-group">
            <label class="bm-form-label">图标大小 <span class="bm-form-hint" id="iconSizeValue"><?php echo esc_html($data['settings']['appearance.icon_size'] ?? 72); ?>px</span></label>
            <input type="range" class="bm-form-range" name="appearance.icon_size" id="bmIconSizeRange" min="40" max="120" value="<?php echo esc_attr($data['settings']['appearance.icon_size'] ?? 72); ?>">
        </div>
        <div class="bm-form-group">
            <label class="bm-form-label">最大列数 <span class="bm-form-hint" id="columnsValue"><?php echo esc_html($data['settings']['appearance.columns'] ?? 8); ?></span></label>
            <input type="range" class="bm-form-range" name="appearance.columns" min="3" max="15" value="<?php echo esc_attr($data['settings']['appearance.columns'] ?? 8); ?>">
        </div>
        <div class="bm-form-group">
            <label class="bm-form-label">图标下方文字颜色</label>
            <input type="color" class="bm-form-color" name="appearance.text_color" value="<?php echo esc_attr($data['settings']['appearance.text_color'] ?? '#374151'); ?>">
        </div>
        <div class="bm-form-group">
            <label class="bm-form-label">卡片圆角 <span class="bm-form-hint" id="radiusValue"><?php echo esc_html($data['settings']['appearance.card_radius'] ?? 14); ?>px</span></label>
            <input type="range" class="bm-form-range" name="appearance.card_radius" min="0" max="100" value="<?php echo esc_attr($data['settings']['appearance.card_radius'] ?? 14); ?>">
        </div>
        <div class="bm-form-group">
            <label class="bm-form-label">卡片间距 <span class="bm-form-hint" id="gapValue"><?php echo esc_html($data['settings']['appearance.card_gap'] ?? 18); ?>px</span></label>
            <input type="range" class="bm-form-range" name="appearance.card_gap" min="4" max="32" value="<?php echo esc_attr($data['settings']['appearance.card_gap'] ?? 18); ?>">
        </div>
    </div>

    <div class="bm-settings-panel-tab" data-tab="wallpaper">
        <div class="bm-form-group">
            <label class="bm-form-label">壁纸类型</label>
            <select class="bm-form-select" name="wallpaper.type" id="bmWallpaperType">
                <option value="color" <?php selected(($data['settings']['wallpaper.type'] ?? 'color') === 'color'); ?>>纯色</option>
                <option value="gradient" <?php selected(($data['settings']['wallpaper.type'] ?? '') === 'gradient'); ?>>渐变</option>
                <option value="image" <?php selected(($data['settings']['wallpaper.type'] ?? '') === 'image'); ?>>图片</option>
                <option value="bing" <?php selected(($data['settings']['wallpaper.type'] ?? '') === 'bing'); ?>>必应每日</option>
            </select>
        </div>
        <div class="bm-form-group bm-wallpaper-color-group" id="bmWallpaperColorGroup">
            <label class="bm-form-label">背景颜色</label>
            <div class="bm-color-input-row">
                <input type="color" class="bm-form-color" name="wallpaper.value" id="bmWallpaperColor" value="<?php echo esc_attr($data['settings']['wallpaper.value'] ?? '#1a1a2e'); ?>">
                <input type="text" class="bm-form-input bm-color-hex" name="wallpaper.value_text" id="bmWallpaperColorText" value="<?php echo esc_attr($data['settings']['wallpaper.value'] ?? '#1a1a2e'); ?>" maxlength="30">
            </div>
        </div>
        <div class="bm-form-group bm-wallpaper-gradient-group" id="bmWallpaperGradientGroup" style="display:none;">
            <label class="bm-form-label">渐变颜色</label>
            <div class="bm-gradient-colors">
                <div class="bm-color-input-row">
                    <input type="color" class="bm-form-color" name="wallpaper.gradient_from" id="bmWallpaperGradFrom" value="<?php echo esc_attr($data['settings']['wallpaper.gradient_from'] ?? '#0c0c1d'); ?>">
                    <input type="text" class="bm-form-input bm-color-hex" name="wallpaper.gradient_from_text" id="bmWallpaperGradFromText" value="<?php echo esc_attr($data['settings']['wallpaper.gradient_from'] ?? '#0c0c1d'); ?>" maxlength="7">
                </div>
                <span class="bm-gradient-arrow">→</span>
                <div class="bm-color-input-row">
                    <input type="color" class="bm-form-color" name="wallpaper.gradient_to" id="bmWallpaperGradTo" value="<?php echo esc_attr($data['settings']['wallpaper.gradient_to'] ?? '#16213e'); ?>">
                    <input type="text" class="bm-form-input bm-color-hex" name="wallpaper.gradient_to_text" id="bmWallpaperGradToText" value="<?php echo esc_attr($data['settings']['wallpaper.gradient_to'] ?? '#16213e'); ?>" maxlength="7">
                </div>
            </div>
        </div>
        <div class="bm-form-group bm-wallpaper-image-group" id="bmWallpaperImageGroup" style="display:none;">
            <label class="bm-form-label">图片地址</label>
            <input type="text" class="bm-form-input" name="wallpaper.image_url" id="bmWallpaperImageUrl" placeholder="输入壁纸图片URL" value="<?php echo esc_attr($data['settings']['wallpaper.image_url'] ?? ''); ?>">
        </div>
        <div class="bm-form-group">
            <label class="bm-form-label">模糊程度 <span class="bm-form-hint" id="wallpaperBlurValue"><?php echo esc_html($data['settings']['wallpaper.blur'] ?? 20); ?>px</span></label>
            <input type="range" class="bm-form-range" name="wallpaper.blur" id="bmWallpaperBlur" min="0" max="40" value="<?php echo esc_attr($data['settings']['wallpaper.blur'] ?? 20); ?>">
        </div>
        <div class="bm-form-group">
            <label class="bm-form-label">遮罩透明度 <span class="bm-form-hint" id="wallpaperOverlayValue"><?php echo esc_html(intval(($data['settings']['wallpaper.overlay'] ?? 15))); ?>%</span></label>
            <input type="range" class="bm-form-range" name="wallpaper.overlay" id="bmWallpaperOverlay" min="0" max="80" value="<?php echo esc_attr($data['settings']['wallpaper.overlay'] ?? 15); ?>">
        </div>
    </div>

    <div class="bm-settings-panel-tab" data-tab="sidebar">
        <div class="bm-form-group">
            <label class="bm-form-label">侧栏模式</label>
            <select class="bm-form-select" name="sidebar.mode">
                <option value="always" <?php selected(($data['settings']['sidebar.mode'] ?? 'always') === 'always'); ?>>常开</option>
                <option value="autohide" <?php selected(($data['settings']['sidebar.mode'] ?? '') === 'autohide'); ?>>贴边隐藏</option>
            </select>
        </div>
    </div>

    <div class="bm-settings-panel-tab" data-tab="dock">
        <div class="bm-form-group">
            <label class="bm-form-label">显示Dock栏</label>
            <label class="bm-form-toggle">
                <input type="checkbox" name="dock.visible" value="1" <?php checked(!empty($data['settings']['dock.visible'])); ?>>
                <span class="bm-form-toggle-slider"></span>
            </label>
        </div>
        <div class="bm-form-group">
            <label class="bm-form-label">Dock高度 <span class="bm-form-hint"><?php echo esc_html($data['settings']['dock.height'] ?? 68); ?>px</span></label>
            <input type="range" class="bm-form-range" name="dock.height" min="48" max="96" value="<?php echo esc_attr($data['settings']['dock.height'] ?? 68); ?>">
        </div>
    </div>

    <div class="bm-settings-panel-tab" data-tab="about">
        <div class="bm-about-info">
            <h4>书签导航页</h4>
            <p>版本 1.0.0</p>
            <p>基于 OneNav 网址数据的 mtab 风格书签导航页插件</p>
        </div>
    </div>
</div>

<div class="bm-settings-footer">
    <button class="bm-btn bm-btn--primary bm-btn--block" id="bmSettingsSaveBtn">保存设置</button>
</div>
