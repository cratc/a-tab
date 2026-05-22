<?php if (!defined('ABSPATH')) exit; ?>
<div class="bm-modal-header">
    <button class="bm-mac-dot bm-mac-dot--close" data-close="bmEditModal"></button>
    <h3 class="bm-modal-title">编辑标签</h3>
    <div style="width:12px"></div>
</div>
<div class="bm-modal-body">
    <form class="bm-edit-form" id="bmEditForm">
        <div class="bm-form-group">
            <label class="bm-form-label">网络地址</label>
            <input type="url" class="bm-form-input" name="url" placeholder="https://" maxlength="1000">
            <span class="bm-form-counter"><span class="current">0</span> / 1000</span>
        </div>
        <div class="bm-form-group">
            <label class="bm-form-label">链接名称</label>
            <input type="text" class="bm-form-input" name="title" placeholder="输入名称" maxlength="100">
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
                <input type="text" class="bm-form-input" name="icon" placeholder="图标URL">
                <button type="button" class="bm-btn bm-btn--sm bm-btn--secondary" id="bmEditFetchIconBtn">获取图标</button>
            </div>
        </div>
        <div class="bm-form-group">
            <label class="bm-form-toggle">
                <input type="checkbox" name="open_in_iframe" value="1">
                <span class="bm-form-toggle-slider"></span>
                <span class="bm-form-toggle-text">内嵌窗口打开</span>
            </label>
        </div>
        <input type="hidden" name="id" id="bmEditItemId">
    </form>
</div>
<div class="bm-modal-footer">
    <button class="bm-btn bm-btn--secondary" data-close="bmEditModal">取消</button>
    <button class="bm-btn bm-btn--primary" id="bmEditSaveBtn">保存</button>
</div>
