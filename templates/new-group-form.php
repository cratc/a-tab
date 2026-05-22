<?php if (!defined('ABSPATH')) exit; ?>
<div class="bm-modal-header">
    <button class="bm-mac-dot bm-mac-dot--close" data-close="bmNewGroupModal"></button>
    <h3 class="bm-modal-title">新建分组</h3>
    <div style="width:12px"></div>
</div>
<div class="bm-modal-body">
    <form class="bm-new-group-form" id="bmNewGroupForm">
        <div class="bm-form-group">
            <label class="bm-form-label">分组名称</label>
            <input type="text" class="bm-form-input" name="title" placeholder="输入分组名称" maxlength="200" required>
        </div>
        <div class="bm-form-group">
            <label class="bm-form-label">分组图标</label>
            <input type="text" class="bm-form-input" name="icon" placeholder="图标，如 fas fa-folder 或 📁" maxlength="200">
        </div>
    </form>
</div>
<div class="bm-modal-footer">
    <button class="bm-btn bm-btn--secondary" data-close="bmNewGroupModal">取消</button>
    <button class="bm-btn bm-btn--primary" id="bmNewGroupSaveBtn">创建</button>
</div>
