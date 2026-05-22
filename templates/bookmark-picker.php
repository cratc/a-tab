<?php if (!defined('ABSPATH')) exit; ?>
<div class="bm-modal-header">
    <button class="bm-mac-dot bm-mac-dot--close" data-close="bmPickerModal"></button>
    <h3 class="bm-modal-title">标签商店</h3>
    <div style="width:12px"></div>
</div>

<div class="bm-picker-tabs">
    <button class="bm-picker-tab is-active" data-tab="online">🌐 在线添加</button>
    <button class="bm-picker-tab" data-tab="manual">✏️ 手动添加</button>
    <button class="bm-picker-tab" data-tab="component">🧩 卡片组件</button>
</div>

<div class="bm-modal-body">
    <div class="bm-picker-panel is-active" data-tab="online">
        <div class="bm-picker-search">
            <input type="text" class="bm-form-input" id="bmCandidateSearch" placeholder="快速搜索标签...">
        </div>
        <div class="bm-picker-categories" id="bmCandidateCategories">
            <button class="bm-category-tag is-active" data-category="0">全部</button>
        </div>
        <div class="bm-candidate-grid" id="bmCandidateGrid">
            <div class="bm-candidate-loading">加载中...</div>
        </div>
        <div class="bm-candidate-pagination" id="bmCandidatePagination"></div>
    </div>

    <div class="bm-picker-panel" data-tab="manual">
        <?php if (empty($data['user_logged_in'])): ?>
        <div class="bm-local-notice">
            <span>⚠️ 未登录状态，手动添加的书签将保存在本地浏览器中</span>
        </div>
        <?php endif; ?>
        <form class="bm-manual-form" id="bmManualForm">
            <div class="bm-form-group">
                <label class="bm-form-label">网络地址 <span class="bm-form-required">*</span></label>
                <input type="url" class="bm-form-input" name="url" placeholder="https://" maxlength="1000" required>
                <span class="bm-form-counter"><span class="current">0</span> / 1000</span>
            </div>
            <div class="bm-form-group">
                <label class="bm-form-label">链接名称 <span class="bm-form-required">*</span></label>
                <input type="text" class="bm-form-input" name="title" placeholder="输入名称" maxlength="100" required>
                <span class="bm-form-counter"><span class="current">0</span> / 100</span>
            </div>
            <div class="bm-form-group">
                <label class="bm-form-label">网址简介</label>
                <textarea class="bm-form-textarea" name="describe" placeholder="简短介绍" maxlength="200"></textarea>
                <span class="bm-form-counter"><span class="current">0</span> / 200</span>
            </div>
            <div class="bm-form-row">
                <div class="bm-form-group bm-form-group--half">
                    <label class="bm-form-label">文字图标</label>
                    <input type="text" class="bm-form-input" name="text_icon" placeholder="1个字符" maxlength="30">
                    <span class="bm-form-counter"><span class="current">0</span> / 30</span>
                </div>
                <div class="bm-form-group bm-form-group--half">
                    <label class="bm-form-label">图标背景色</label>
                    <div class="bm-color-input-row">
                        <input type="color" class="bm-form-color" name="bg_color" value="#6366f1">
                        <input type="text" class="bm-form-input bm-color-hex" name="bg_color_text" value="#6366f1" maxlength="7">
                    </div>
                </div>
            </div>
            <div class="bm-form-group">
                <label class="bm-form-label">图片图标</label>
                <div class="bm-icon-input-row">
                    <input type="text" class="bm-form-input" name="icon" placeholder="图标URL或上传">
                    <button type="button" class="bm-btn bm-btn--sm bm-btn--secondary" id="bmFetchIconBtn">获取图标</button>
                </div>
            </div>
            <div class="bm-form-group">
                <label class="bm-form-label">目标分组</label>
                <select class="bm-form-select" name="group_id" id="bmManualGroupId">
                    <option value="0">默认分组</option>
                </select>
            </div>
            <div class="bm-form-group">
                <label class="bm-form-toggle">
                    <input type="checkbox" name="open_in_iframe" value="1">
                    <span class="bm-form-toggle-slider"></span>
                    <span class="bm-form-toggle-text">内嵌窗口打开</span>
                </label>
            </div>
        </form>
        <div class="bm-modal-footer">
            <button class="bm-btn bm-btn--secondary" data-close="bmPickerModal">取消</button>
            <button class="bm-btn bm-btn--primary" id="bmManualSaveBtn">保存</button>
            <button class="bm-btn bm-btn--secondary" id="bmManualSaveContinueBtn">保存并继续</button>
        </div>
    </div>

    <div class="bm-picker-panel" data-tab="component">
        <div class="bm-components-empty">
            <div class="bm-components-empty-icon">🧩</div>
            <h3>卡片组件即将上线</h3>
            <p>我们正在开发天气、热搜、日历、AI助手等实用组件<br>敬请期待！</p>
        </div>
    </div>
</div>
