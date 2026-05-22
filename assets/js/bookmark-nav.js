const BM_App = (() => {
    const state = {
        pageId: null,
        activePageId: null,
        userLoggedIn: false,
        settings: {},
        groups: [],
        navItems: {},
        pages: [],
        dockItems: [],
        candidates: [],
        candidateMeta: {},
        localBookmarks: [],
        activeContextMenu: null,
        currentEditItem: null,
        pickerActiveTab: 'online',
        isDragging: false,
        dragItem: null
    };

    async function api(method, endpoint, body = null) {
        const opts = { method, headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': bmVars.ajax_nonce } };
        if (body) opts.body = JSON.stringify(body);
        return await fetch(bmVars.restUrl + endpoint, opts);
    }

    const Toast = {
        showToast(message, type = 'info') {
            const existing = document.querySelector('.bm-toast'); if (existing) existing.remove();
            const toast = document.createElement('div');
            toast.className = 'bm-toast' + (type === 'error' ? ' bm-toast--error' : '');
            toast.textContent = message;
            document.body.appendChild(toast);
            requestAnimationFrame(() => toast.classList.add('is-visible'));
            setTimeout(() => { toast.classList.remove('is-visible'); setTimeout(() => toast.remove(), 300); }, 3000);
        }
    };

    const Modal = {
        openModal(id) { const el = document.getElementById(id); if (el) el.classList.add('is-open'); },
        closeModal(id) { const el = document.getElementById(id); if (el) el.classList.remove('is-open'); },
        init() {
            document.querySelectorAll('.bm-modal-overlay').forEach(o => { o.addEventListener('click', e => { if (e.target === o) o.classList.remove('is-open'); }); });
            document.querySelectorAll('[data-close]').forEach(btn => { btn.addEventListener('click', () => Modal.closeModal(btn.getAttribute('data-close'))); });
        }
    };

    const LocalStorage = {
        KEY: 'bm_local_bookmarks',
        init() { try { const s = localStorage.getItem(this.KEY); if (s) state.localBookmarks = JSON.parse(s); } catch(e) { state.localBookmarks = []; } },
        addItem(b) { b.id = 'local_' + Date.now(); b.source_type = 'local'; state.localBookmarks.push(b); this.persist(); return b; },
        removeItem(id) { state.localBookmarks = state.localBookmarks.filter(b => b.id !== id); this.persist(); },
        updateItem(id, d) { const i = state.localBookmarks.findIndex(b => b.id === id); if (i >= 0) Object.assign(state.localBookmarks[i], d); this.persist(); },
        persist() { localStorage.setItem(this.KEY, JSON.stringify(state.localBookmarks)); },
        getAll() { return state.localBookmarks; }
    };

    const Clock = {
        init() { this.updateTime(); setInterval(() => this.updateTime(), 1000); },
        updateTime() {
            const now = new Date();
            const t = document.getElementById('bmClockTime');
            const d = document.getElementById('bmClockDate');
            if (t) t.textContent = String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0') + ':' + String(now.getSeconds()).padStart(2,'0');
            if (d) { const w = ['日','一','二','三','四','五','六']; d.textContent = now.getFullYear() + '年' + (now.getMonth()+1) + '月' + now.getDate() + '日 星期' + w[now.getDay()]; }
        }
    };

    const Search = {
        currentEngine: null, engines: [],
        init() {
            const form = document.getElementById('bmSearchForm');
            const engineBtn = document.getElementById('bmSearchEngineBtn');
            const engineDropdown = document.getElementById('bmSearchEngineDropdown');
            const engineItems = document.querySelectorAll('.bm-search-engine-item');
            if (!form) return;
            this.engines = [];
            engineItems.forEach(opt => { this.engines.push({ key: opt.getAttribute('data-key') || '', name: opt.getAttribute('data-name') || '', url: opt.getAttribute('data-url') || '' }); });
            if (this.engines.length > 0) this.currentEngine = this.engines[0];
            if (engineBtn && engineDropdown) { engineBtn.addEventListener('click', e => { e.stopPropagation(); engineDropdown.classList.toggle('is-open'); }); }
            engineItems.forEach((opt, idx) => { opt.addEventListener('click', () => { if (this.engines[idx]) { this.switchEngine(idx); engineItems.forEach(o => o.classList.remove('is-active')); opt.classList.add('is-active'); if (engineDropdown) engineDropdown.classList.remove('is-open'); } }); });
            document.addEventListener('click', e => { if (engineDropdown && !engineDropdown.contains(e.target) && e.target !== engineBtn && !(engineBtn && engineBtn.contains(e.target))) engineDropdown.classList.remove('is-open'); });
            form.addEventListener('submit', e => this.handleSubmit(e));
        },
        applySavedEngine() {
            const savedKey = state.settings['search.engine'] || localStorage.getItem('bm_search_engine') || 'baidu';
            const idx = this.engines.findIndex(e => e.key === savedKey);
            if (idx >= 0 && this.engines[idx]) {
                this.switchEngine(idx);
                const engineItems = document.querySelectorAll('.bm-search-engine-item');
                engineItems.forEach(o => o.classList.remove('is-active'));
                if (engineItems[idx]) engineItems[idx].classList.add('is-active');
            }
        },
        handleSubmit(e) { e.preventDefault(); if (!this.currentEngine) return; const i = document.getElementById('bmSearchInput'); if (!i || !i.value.trim()) return; window.open(this.currentEngine.url + encodeURIComponent(i.value.trim()), '_blank'); },
        switchEngine(idx) {
            this.currentEngine = this.engines[idx];
            const b = document.getElementById('bmSearchEngineBtn');
            if (b) {
                const icon = b.querySelector('.bm-search-engine-icon');
                if (icon) {
                    icon.className = 'bm-search-engine-icon bm-search-engine-icon--' + (this.currentEngine.key || 'baidu');
                    icon.textContent = (this.currentEngine.name || 'B').charAt(0);
                }
            }
            localStorage.setItem('bm_search_engine', this.currentEngine.key || 'baidu');
        }
    };

    const DragSort = {
        init() {
            document.addEventListener('dragstart', e => this.onDragStart(e));
            document.addEventListener('dragover', e => this.onDragOver(e));
            document.addEventListener('drop', e => this.onDrop(e));
            document.addEventListener('dragend', e => this.onDragEnd(e));
        },
        onDragStart(e) {
            const item = e.target.closest('.bm-item:not(.bm-item--folder)');
            if (!item) return;
            state.isDragging = true;
            state.dragItem = item;
            item.classList.add('bm-item--dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', item.getAttribute('data-id'));
        },
        onDragOver(e) {
            if (!state.isDragging) return;
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            const folderItem = e.target.closest('.bm-item--folder');
            if (folderItem) {
                folderItem.classList.add('bm-item--folder-hover');
                return;
            }
            const grid = e.target.closest('.bm-canvas-grid');
            if (!grid) return;
            const afterElement = this.getDragAfterElement(grid, e.clientY, e.clientX);
            if (state.dragItem) {
                if (afterElement == null) {
                    grid.appendChild(state.dragItem);
                } else {
                    grid.insertBefore(state.dragItem, afterElement);
                }
            }
        },
        onDrop(e) {
            e.preventDefault();
            if (!state.isDragging || !state.dragItem) return;
            document.querySelectorAll('.bm-item--folder-hover').forEach(el => el.classList.remove('bm-item--folder-hover'));
            const folderItem = e.target.closest('.bm-item--folder');
            if (folderItem) {
                const groupId = folderItem.getAttribute('data-group-id');
                const itemId = state.dragItem.getAttribute('data-id');
                if (groupId && itemId) {
                    CanvasManager.moveToGroup(itemId, groupId);
                    PageSwitcher.loadPageData(state.activePageId);
                }
            } else {
                this.saveOrder();
            }
        },
        onDragEnd(e) {
            if (state.dragItem) state.dragItem.classList.remove('bm-item--dragging');
            document.querySelectorAll('.bm-item--folder-hover').forEach(el => el.classList.remove('bm-item--folder-hover'));
            state.isDragging = false;
            state.dragItem = null;
        },
        getDragAfterElement(grid, y, x) {
            const items = [...grid.querySelectorAll('.bm-item:not(.bm-item--dragging)')];
            return items.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offsetY = y - box.top - box.height / 2;
                const offsetX = x - box.left - box.width / 2;
                if (offsetY < 0 && offsetY > closest.offsetY) return { offset: offsetY, element: child };
                if (offsetY === 0 && offsetX < 0 && offsetX > closest.offsetX) return { offset: offsetX, element: child };
                return closest;
            }, { offsetY: Number.NEGATIVE_INFINITY, offsetX: Number.NEGATIVE_INFINITY }).element;
        },
        async saveOrder() {
            const grids = document.querySelectorAll('.bm-canvas-grid');
            const apiItems = [];
            grids.forEach(grid => {
                const groupId = grid.closest('[data-group-id]')?.getAttribute('data-group-id') || 0;
                grid.querySelectorAll('.bm-item').forEach((el, idx) => {
                    const id = el.getAttribute('data-id');
                    const type = el.getAttribute('data-type');
                    if (type !== 'memo') {
                        apiItems.push({ id, sort_order: idx, group_id: groupId });
                    }
                });
            });
            if (state.userLoggedIn && apiItems.length > 0) {
                try { await api('PUT', '/nav-items/reorder', { items: apiItems }); } catch(e) {}
            }
        }
    };

    const PageSwitcher = {
        init() {
            const pages = document.querySelectorAll('.bm-sidebar-page[data-page-id]');
            pages.forEach(p => { p.addEventListener('click', () => this.switchTo(p.getAttribute('data-page-id'))); });
            const mainContent = document.getElementById('bmMainContent');
            if (mainContent) {
                let wheelTimer = null;
                mainContent.addEventListener('wheel', e => {
                    if (e.target.closest('.bm-settings-panel') || e.target.closest('.bm-modal-overlay') || e.target.closest('.bm-dock-bar')) return;
                    const dy = e.deltaY;
                    if (Math.abs(dy) < 30) return;
                    if (wheelTimer) return;
                    wheelTimer = setTimeout(() => { wheelTimer = null; }, 600);
                    e.preventDefault();
                    this.handleSwipe(dy > 0 ? 'next' : 'prev');
                }, { passive: false });
            }
            this.initTouchSwipe();
        },
        initTouchSwipe() {
            const mainContent = document.getElementById('bmMainContent');
            if (!mainContent) return;
            let startX = 0, startY = 0;
            mainContent.addEventListener('touchstart', e => {
                if (e.target.closest('.bm-settings-panel') || e.target.closest('.bm-modal-overlay') || e.target.closest('.bm-dock-bar')) return;
                startX = e.touches[0].clientX; startY = e.touches[0].clientY;
            }, { passive: true });
            mainContent.addEventListener('touchend', e => {
                if (e.target.closest('.bm-settings-panel') || e.target.closest('.bm-modal-overlay') || e.target.closest('.bm-dock-bar')) return;
                const dx = e.changedTouches[0].clientX - startX;
                const dy = e.changedTouches[0].clientY - startY;
                if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 60) {
                    this.handleSwipe(dx < 0 ? 'next' : 'prev');
                }
            }, { passive: true });
        },
        switchTo(pageId) {
            if (!pageId || pageId == state.activePageId) return;
            state.activePageId = pageId;
            document.querySelectorAll('.bm-sidebar-page').forEach(p => p.classList.toggle('is-active', p.getAttribute('data-page-id') === String(pageId)));
            const navPage = document.querySelector('.bm-nav-page');
            if (navPage) navPage.setAttribute('data-active-page-id', pageId);
            this.loadPageData(pageId);
        },
        async loadPageData(pageId) {
            try {
                const res = await api('GET', '/init-data?page_id=' + state.pageId + '&active_page=' + pageId);
                if (!res.ok) throw new Error('API error');
                const data = await res.json();
                state.groups = data.groups || [];
                state.navItems = data.nav_items || {};
                this.renderPage();
            } catch(e) { Toast.showToast('加载失败', 'error'); }
        },
        renderPage() {
            const canvas = document.getElementById('bmCanvas');
            if (!canvas) return;
            const folderGroups = state.groups.filter(g => g.is_folder);
            const regularGroups = state.groups.filter(g => !g.is_folder);
            const localItems = LocalStorage.getAll();
            const memoItems = MemoCard.getAll();
            const ungrouped = (state.navItems && state.navItems.ungrouped) || [];
            if (regularGroups.length === 0 && ungrouped.length === 0 && folderGroups.length === 0 && localItems.length === 0 && memoItems.length === 0) {
                canvas.innerHTML = '<div class="bm-group-empty"><p>暂无内容，右键添加标签</p></div>';
                return;
            }
            const frag = document.createDocumentFragment();
            regularGroups.forEach(group => {
                const section = document.createElement('section');
                section.className = 'bm-group-section';
                section.setAttribute('data-group-id', group.id);
                section.setAttribute('data-page-id', group.page_id || 0);
                const groupItems = (state.navItems && state.navItems[group.id]) || [];
                let html = '';
                if (group.id > 0 && group.title) {
                    html += '<div class="bm-group-header"><span class="bm-group-icon">' + (group.icon || '📁') + '</span><h3 class="bm-group-title">' + group.title + '</h3><span class="bm-group-count">' + groupItems.length + '</span></div>';
                }
                html += '<div class="bm-canvas-grid">';
                groupItems.forEach(item => { html += CanvasManager.renderBookmarkCard(item); });
                html += '</div>';
                section.innerHTML = html;
                frag.appendChild(section);
            });
            if (ungrouped.length > 0 || folderGroups.length > 0 || localItems.length > 0 || memoItems.length > 0) {
                const section = document.createElement('section');
                section.className = 'bm-group-section';
                section.setAttribute('data-group-id', '0');
                let html = '<div class="bm-canvas-grid">';
                folderGroups.forEach(group => {
                    html += CanvasManager.renderFolderCard({
                        id: group.id,
                        source_type: 'folder',
                        title: group.title || '新文件夹',
                        folder_group_id: group.id,
                        layout: group.layout || '1x1'
                    });
                });
                ungrouped.forEach(item => { html += CanvasManager.renderBookmarkCard(item); });
                localItems.forEach(item => { html += CanvasManager.renderBookmarkCard(item); });
                memoItems.forEach(item => { html += CanvasManager.renderBookmarkCard(item); });
                html += '</div>';
                section.innerHTML = html;
                frag.appendChild(section);
            }
            canvas.innerHTML = '';
            canvas.appendChild(frag);
        },
        handleSwipe(direction) {
            const idx = state.pages.findIndex(p => String(p.id) === String(state.activePageId));
            if (idx < 0) return;
            if (direction === 'next' && idx < state.pages.length - 1) this.switchTo(state.pages[idx + 1].id);
            if (direction === 'prev' && idx > 0) this.switchTo(state.pages[idx - 1].id);
        }
    };

    const ContextMenu = {
        init() {
            document.addEventListener('contextmenu', e => this.handle(e));
            document.addEventListener('click', e => { if (!e.target.closest('.bm-context-menu')) this.hide(); });
            document.addEventListener('keydown', e => { if (e.key === 'Escape') this.hide(); });
        },
        handle(e) {
            const folderEl = e.target.closest('.bm-item--folder');
            if (folderEl) { e.preventDefault(); this.showFolderMenu(e, folderEl); return; }
            const cardEl = e.target.closest('.bm-item:not(.bm-item--folder)');
            if (cardEl) { e.preventDefault(); this.showCardMenu(e, cardEl); }
            else if (e.target.closest('.bm-nav-page') && !e.target.closest('.bm-sidebar') && !e.target.closest('.bm-dock-bar') && !e.target.closest('.bm-settings-panel') && !e.target.closest('.bm-modal-overlay')) { e.preventDefault(); this.showBlankMenu(e); }
        },
        showBlankMenu(e) {
            const html = '<div class="bm-context-menu">' +
                '<div class="bm-context-item" data-action="open-picker"><span class="ctx-icon">📝</span><span>添加标签</span></div>' +
                '<div class="bm-context-item" data-action="add-memo"><span class="ctx-icon">📋</span><span>添加备忘录</span></div>' +
                '<div class="bm-context-item" data-action="new-group"><span class="ctx-icon">📁</span><span>新文件夹</span></div>' +
                '<div class="bm-context-item" data-action="new-page"><span class="ctx-icon">📄</span><span>新建分类页</span></div>' +
                '<div class="bm-context-item" data-action="change-wallpaper"><span class="ctx-icon">🖼️</span><span>更换壁纸</span></div>' +
                '<div class="bm-context-divider"></div>' +
                '<div class="bm-context-item" data-action="open-settings"><span class="ctx-icon">⚙️</span><span>设置</span></div></div>';
            this.render(html, e.clientX, e.clientY);
            const w = document.getElementById('bmContextMenuWrapper'); if (!w) return;
            const m = w.querySelector('.bm-context-menu'); if (!m) return;
            m.querySelector('[data-action="open-picker"]').addEventListener('click', ev => { ev.stopPropagation(); BookmarkPicker.open(); this.hide(); });
            m.querySelector('[data-action="add-memo"]').addEventListener('click', ev => { ev.stopPropagation(); MemoEditor.open(); this.hide(); });
            m.querySelector('[data-action="new-group"]').addEventListener('click', ev => { ev.stopPropagation(); CanvasManager.createFolder(); this.hide(); });
            m.querySelector('[data-action="new-page"]').addEventListener('click', ev => { ev.stopPropagation(); Sidebar.addPage(); this.hide(); });
            m.querySelector('[data-action="change-wallpaper"]').addEventListener('click', ev => { ev.stopPropagation(); Theme.cycleWallpaper(); this.hide(); });
            m.querySelector('[data-action="open-settings"]').addEventListener('click', ev => { ev.stopPropagation(); SettingsPanel.open(); this.hide(); });
        },
        showCardMenu(e, cardEl) {
            const itemId = cardEl.getAttribute('data-id');
            const itemType = cardEl.getAttribute('data-type');
            const itemUrl = cardEl.getAttribute('data-url') || '';
            const currentLayout = cardEl.getAttribute('data-layout') || 'auto';
            if (itemType === 'memo') {
                this.showMemoMenu(e, cardEl, itemId);
                return;
            }
            const groupsHtml = state.groups.map(g => '<div class="bm-context-subitem" data-group-id="' + g.id + '">' + (g.title || '') + '</div>').join('');
            const layouts = ['1x1','1x2','2x1','2x2','2x4'];
            const layoutLabels = {'1x1':'1×1','1x2':'1×2','2x1':'2×1','2x2':'2×2','2x4':'2×4'};
            const layoutBtns = layouts.map(l => '<button class="bm-layout-option' + (l === currentLayout ? ' is-active' : '') + '" data-layout="' + l + '">' + layoutLabels[l] + '</button>').join('');
            const html = '<div class="bm-context-menu" data-card-id="' + itemId + '">' +
                '<div class="bm-context-item" data-action="open-new-tab"><span class="ctx-icon">🔗</span><span>新标签打开</span></div>' +
                '<div class="bm-context-item" data-action="edit-bookmark"><span class="ctx-icon">✏️</span><span>编辑标签</span></div>' +
                '<div class="bm-context-item has-submenu" data-action="move-to-group"><span class="ctx-icon">➡️</span><span>移动至分类</span><span class="ctx-arrow">▶</span><div class="bm-context-submenu">' + groupsHtml + '</div></div>' +
                '<div class="bm-context-item" data-action="add-to-dock"><span class="ctx-icon">📱</span><span>加入Dock栏</span></div>' +
                '<div class="bm-context-divider"></div>' +
                '<div class="bm-context-item has-submenu" data-action="change-layout"><span class="ctx-icon">📐</span><span>布局</span><div class="bm-context-submenu bm-context-submenu--horizontal">' + layoutBtns + '</div></div>' +
                '<div class="bm-context-divider"></div>' +
                '<div class="bm-context-item bm-context-item--danger" data-action="delete-bookmark"><span class="ctx-icon">🗑️</span><span>删除标签</span></div></div>';
            this.render(html, e.clientX, e.clientY);
            const w = document.getElementById('bmContextMenuWrapper'); if (!w) return;
            const m = w.querySelector('.bm-context-menu'); if (!m) return;
            m.querySelector('[data-action="open-new-tab"]').addEventListener('click', ev => { ev.stopPropagation(); if (itemUrl) window.open(itemUrl, '_blank'); this.hide(); });
            m.querySelector('[data-action="edit-bookmark"]').addEventListener('click', ev => { ev.stopPropagation(); state.currentEditItem = itemId; this.populateEditForm(cardEl); Modal.openModal('bmEditModal'); this.hide(); });
            m.querySelector('[data-action="add-to-dock"]').addEventListener('click', ev => { ev.stopPropagation(); Dock.addToDock(itemId); this.hide(); });
            m.querySelector('[data-action="delete-bookmark"]').addEventListener('click', ev => { ev.stopPropagation(); if (confirm('确定要删除这个书签吗？')) CanvasManager.removeItem(itemId); this.hide(); });
            m.querySelectorAll('[data-group-id]').forEach(el => { el.addEventListener('click', ev => { ev.stopPropagation(); CanvasManager.moveToGroup(itemId, el.getAttribute('data-group-id')); this.hide(); }); });
            m.querySelectorAll('.bm-layout-option').forEach(btn => { btn.addEventListener('click', ev => { ev.stopPropagation(); CanvasManager.changeLayout(itemId, btn.getAttribute('data-layout')); this.hide(); }); });
        },
        showMemoMenu(e, cardEl, itemId) {
            const html = '<div class="bm-context-menu" data-card-id="' + itemId + '">' +
                '<div class="bm-context-item" data-action="open-memo"><span class="ctx-icon">📝</span><span>打开便签夹</span></div>' +
                '<div class="bm-context-divider"></div>' +
                '<div class="bm-context-item bm-context-item--danger" data-action="delete-memo"><span class="ctx-icon">🗑️</span><span>移除组件</span></div></div>';
            this.render(html, e.clientX, e.clientY);
            const w = document.getElementById('bmContextMenuWrapper'); if (!w) return;
            const m = w.querySelector('.bm-context-menu'); if (!m) return;
            m.querySelector('[data-action="open-memo"]').addEventListener('click', ev => { ev.stopPropagation(); MemoEditor.open(); this.hide(); });
            m.querySelector('[data-action="delete-memo"]').addEventListener('click', ev => { ev.stopPropagation();
                localStorage.removeItem(MemoCard.KEY);
                MemoCard.pad = null; MemoCard.activeNoteId = null;
                MemoCard.init();
                PageSwitcher.renderPage();
                Toast.showToast('已移除组件', 'success');
                this.hide();
            });
        },
        render(html, x, y) {
            let w = document.getElementById('bmContextMenuWrapper');
            if (!w) { w = document.createElement('div'); w.id = 'bmContextMenuWrapper'; w.className = 'bm-context-menu-wrapper'; document.body.appendChild(w); }
            w.innerHTML = html; w.style.display = 'block'; w.style.pointerEvents = 'auto';
            const m = w.querySelector('.bm-context-menu');
            if (m) { m.style.position = 'absolute'; m.style.left = x + 'px'; m.style.top = y + 'px';
                requestAnimationFrame(() => { let nx = x, ny = y; if (x + m.offsetWidth > window.innerWidth - 8) nx = window.innerWidth - m.offsetWidth - 8; if (y + m.offsetHeight > window.innerHeight - 8) ny = window.innerHeight - m.offsetHeight - 8; if (nx < 0) nx = 0; if (ny < 0) ny = 0; m.style.left = nx + 'px'; m.style.top = ny + 'px'; });
            }
            state.activeContextMenu = w;
        },
        hide() { const w = document.getElementById('bmContextMenuWrapper'); if (w) { w.innerHTML = ''; w.style.display = 'none'; } state.activeContextMenu = null; },
        populateEditForm(cardEl) {
            const form = document.getElementById('bmEditForm'); if (!form) return;
            const nameEl = cardEl.querySelector('.bm-item-name');
            const iconBox = cardEl.querySelector('.bm-item-icon-box');
            const title = nameEl ? nameEl.textContent.trim() : '';
            const url = cardEl.getAttribute('data-url') || '';
            const iconImg = iconBox ? iconBox.querySelector('img') : null;
            const icon = iconImg ? iconImg.getAttribute('src') : '';
            const letterEl = iconBox ? iconBox.querySelector('.bm-item-letter') : null;
            const textIcon = letterEl ? letterEl.textContent.trim() : '';
            const bgColor = letterEl ? (letterEl.style.background || letterEl.style.backgroundColor || '#6366f1') : '#6366f1';
            const setVal = (name, val) => { const el = form.querySelector('[name="' + name + '"]'); if (el) el.value = val; };
            setVal('url', url); setVal('title', title); setVal('icon', icon); setVal('text_icon', textIcon); setVal('bg_color', bgColor); setVal('bg_color_text', bgColor); setVal('describe', '');
            const idField = document.getElementById('bmEditItemId'); if (idField) idField.value = cardEl.getAttribute('data-id') || '';
        },
        showFolderMenu(e, folderEl) {
            const groupId = folderEl.getAttribute('data-group-id');
            const groupTitle = folderEl.querySelector('.bm-item-name')?.textContent?.trim() || '新文件夹';
            const currentLayout = folderEl.getAttribute('data-layout') || '1x1';
            const html = '<div class="bm-context-menu" data-folder-id="' + groupId + '">' +
                '<div class="bm-context-item" data-action="edit-folder"><span class="ctx-icon">✏️</span><span>编辑名称</span></div>' +
                '<div class="bm-context-item has-submenu" data-action="folder-layout"><span class="ctx-icon">📐</span><span>布局大小</span><div class="bm-context-submenu bm-context-submenu--horizontal">' +
                '<button class="bm-layout-option' + (currentLayout === '1x1' ? ' is-active' : '') + '" data-layout="1x1">1×1</button>' +
                '<button class="bm-layout-option' + (currentLayout === '2x2' ? ' is-active' : '') + '" data-layout="2x2">2×2</button>' +
                '</div></div>' +
                '<div class="bm-context-divider"></div>' +
                '<div class="bm-context-item bm-context-item--danger" data-action="delete-folder"><span class="ctx-icon">🗑️</span><span>删除文件夹</span></div></div>';
            this.render(html, e.clientX, e.clientY);
            const w = document.getElementById('bmContextMenuWrapper'); if (!w) return;
            const m = w.querySelector('.bm-context-menu'); if (!m) return;
            m.querySelector('[data-action="edit-folder"]').addEventListener('click', ev => {
                ev.stopPropagation(); this.hide();
                const newName = prompt('修改文件夹名称:', groupTitle);
                if (newName && newName !== groupTitle) {
                    CanvasManager.updateFolder(groupId, { title: newName });
                }
            });
            m.querySelectorAll('.bm-layout-option').forEach(btn => {
                btn.addEventListener('click', ev => {
                    ev.stopPropagation(); this.hide();
                    CanvasManager.updateFolder(groupId, { layout: btn.getAttribute('data-layout') });
                });
            });
            m.querySelector('[data-action="delete-folder"]').addEventListener('click', ev => {
                ev.stopPropagation(); this.hide();
                if (confirm('确定要删除这个文件夹吗？文件夹内的所有图标将同时删除。')) {
                    CanvasManager.deleteFolder(groupId);
                }
            });
        }
    };

    const CanvasManager = {
        init() {},
        async addItem(data) {
            data.page_id = data.page_id || state.activePageId;
            if (!state.userLoggedIn || (data.source_type && data.source_type === 'local')) {
                const item = LocalStorage.addItem(data);
                PageSwitcher.renderPage();
                Toast.showToast('已保存到本地', 'success');
                return item;
            }
            try {
                const res = await api('POST', '/nav-items', data);
                if (!res.ok) throw new Error('API error');
                const result = await res.json();
                PageSwitcher.loadPageData(state.activePageId);
                Toast.showToast('添加成功', 'success');
                return result.item || result;
            } catch(e) { Toast.showToast('添加失败', 'error'); }
        },
        async removeItem(id) {
            if (id && String(id).startsWith('local_')) { LocalStorage.removeItem(id); }
            else if (state.userLoggedIn) { try { const res = await api('DELETE', '/nav-items/' + id); if (!res.ok) throw new Error('删除失败'); } catch(e) { Toast.showToast('删除失败: ' + e.message, 'error'); return; } }
            const el = document.querySelector('.bm-canvas .bm-item[data-id="' + id + '"], .bm-canvas-grid .bm-item[data-id="' + id + '"]'); if (el) el.remove();
            Toast.showToast('已删除', 'success');
        },
        async editItem(id, data) {
            if (id && String(id).startsWith('local_')) { LocalStorage.updateItem(id, data); }
            else if (state.userLoggedIn) { try { await api('PUT', '/nav-items/' + id, data); } catch(e) {} }
        },
        async changeLayout(id, layout) {
            if (id && String(id).startsWith('local_')) { LocalStorage.updateItem(id, { layout }); }
            else if (state.userLoggedIn) { try { await api('PUT', '/nav-items/' + id, { layout }); } catch(e) {} }
            const el = document.querySelector('[data-id="' + id + '"]'); if (el) { el.className = el.className.replace(/layout-\S+/, 'layout-' + layout); el.setAttribute('data-layout', layout); }
        },
        async moveToGroup(itemId, groupId) {
            if (itemId && String(itemId).startsWith('local_')) { LocalStorage.updateItem(itemId, { group_id: groupId }); }
            else if (state.userLoggedIn) { try { await api('PUT', '/nav-items/' + itemId, { group_id: groupId }); } catch(e) {} }
            const el = document.querySelector('[data-id="' + itemId + '"]'); if (el) { const t = document.querySelector('[data-group-id="' + groupId + '"] .bm-canvas-grid'); if (t) t.appendChild(el); }
        },
        async createFolder() {
            try {
                const res = await api('POST', '/groups', { title: '新文件夹', page_id: state.activePageId, is_folder: true, layout: '1x1' });
                if (!res.ok) throw new Error('API error');
                Toast.showToast('文件夹创建成功', 'success');
                PageSwitcher.loadPageData(state.activePageId);
            } catch(e) { Toast.showToast('创建失败', 'error'); }
        },
        renderBookmarkCard(item) {
            if (item.source_type === 'memo') return MemoCard.renderCard(item);
            if (item.source_type === 'folder') return this.renderFolderCard(item);
            const iconHtml = item.icon ? '<img src="' + item.icon + '" alt="" loading="lazy">' : (item.text_icon ? '<span class="bm-item-letter" style="background:' + (item.bg_color || '#6366f1') + '">' + (item.text_icon || '').charAt(0) + '</span>' : '<span class="bm-item-letter" style="background:#94a3b8;">' + (item.title || '?').charAt(0) + '</span>');
            return '<div class="bm-item layout-' + (item.layout || 'auto') + '" data-id="' + (item.id || '') + '" data-type="bookmark" data-url="' + (item.url || '') + '" data-layout="' + (item.layout || 'auto') + '" draggable="true">' +
                '<div class="bm-item-icon-box"' + (item.bg_color ? ' style="background-color:' + item.bg_color + ';"' : '') + '>' + iconHtml + '</div>' +
                '<div class="bm-item-name">' + (item.title || '') + '</div></div>';
        },
        renderFolderCard(folderData) {
            const groupId = folderData.folder_group_id || folderData.id;
            const childItems = (state.navItems && state.navItems[groupId]) || [];
            const layout = folderData.layout || '1x1';
            let innerHtml = '';
            const displayItems = childItems.slice(0, 4);
            displayItems.forEach(item => {
                const iconHtml = item.icon
                    ? '<img src="' + item.icon + '" alt="" loading="lazy">'
                    : '<span class="bm-item-letter" style="background:' + (item.bg_color || '#6366f1') + '">' + (item.title||'?').charAt(0) + '</span>';
                innerHtml += '<div class="bm-folder-item" data-id="' + item.id + '">' + iconHtml + '</div>';
            });
            if (childItems.length === 0) innerHtml = '<div class="bm-folder-empty">拖入图标</div>';
            return '<div class="bm-item bm-item--folder layout-' + layout + '" data-id="folder_' + groupId + '" data-type="folder" data-group-id="' + groupId + '" data-layout="' + layout + '" draggable="false">' +
                '<div class="bm-item-icon-box bm-item-folder-box"><div class="bm-folder-grid">' + innerHtml + '</div></div>' +
                '<div class="bm-item-name">' + (folderData.title || '新文件夹') + '</div></div>';
        },
        async updateFolder(groupId, data) {
            try { await api('PUT', '/groups/' + groupId, data); Toast.showToast('已更新', 'success'); PageSwitcher.loadPageData(state.activePageId); } catch(e) { Toast.showToast('更新失败', 'error'); }
        },
        async deleteFolder(groupId) {
            try { await api('DELETE', '/groups/' + groupId); Toast.showToast('已删除', 'success'); PageSwitcher.loadPageData(state.activePageId); } catch(e) { Toast.showToast('删除失败', 'error'); }
        },
        openFolder(groupId) {
            const childItems = (state.navItems && state.navItems[groupId]) || [];
            const group = state.groups.find(g => g.id == groupId) || {};
            const title = group.title || '文件夹';
            const titleEl = document.getElementById('bmFolderExpandTitle');
            if (titleEl) titleEl.textContent = title;
            let html = '';
            if (childItems.length === 0) {
                html = '<div class="bm-folder-expand-empty">暂无内容</div>';
            } else {
                childItems.forEach(item => {
                    const iconHtml = item.icon
                        ? '<img src="' + item.icon + '" alt="" loading="lazy">'
                        : '<span class="bm-item-letter" style="background:' + (item.bg_color || '#6366f1') + '">' + (item.title || '?').charAt(0) + '</span>';
                    html += '<div class="bm-folder-expand-item" data-id="' + item.id + '" data-url="' + (item.url || '') + '" data-title="' + (item.title || '') + '">' +
                        '<div class="bm-folder-expand-icon">' + iconHtml + '</div>' +
                        '<div class="bm-folder-expand-name">' + (item.title || '') + '</div></div>';
                });
            }
            const gridEl = document.getElementById('bmFolderExpandGrid');
            if (gridEl) { gridEl.innerHTML = html; }
            Modal.openModal('bmFolderModal');
            if (gridEl) {
                gridEl.querySelectorAll('.bm-folder-expand-item').forEach(el => {
                    el.addEventListener('click', () => {
                        const url = el.getAttribute('data-url');
                        if (url) window.open(url, '_blank');
                    });
                    el.addEventListener('contextmenu', e => {
                        e.preventDefault(); e.stopPropagation();
                        this.showFolderItemMenu(e, el, groupId);
                    });
                });
            }
        },
        showFolderItemMenu(e, itemEl, groupId) {
            const itemId = itemEl.getAttribute('data-id');
            const itemUrl = itemEl.getAttribute('data-url') || '';
            const itemTitle = itemEl.getAttribute('data-title') || '';
            const html = '<div class="bm-context-menu">' +
                '<div class="bm-context-item" data-action="open-new-tab"><span class="ctx-icon">🔗</span><span>新标签打开</span></div>' +
                '<div class="bm-context-item" data-action="edit-item"><span class="ctx-icon">✏️</span><span>编辑</span></div>' +
                '<div class="bm-context-divider"></div>' +
                '<div class="bm-context-item bm-context-item--danger" data-action="remove-from-folder"><span class="ctx-icon">📤</span><span>移出文件夹</span></div></div>';
            ContextMenu.render(html, e.clientX, e.clientY);
            const w = document.getElementById('bmContextMenuWrapper'); if (!w) return;
            const m = w.querySelector('.bm-context-menu'); if (!m) return;
            m.querySelector('[data-action="open-new-tab"]').addEventListener('click', ev => { ev.stopPropagation(); if (itemUrl) window.open(itemUrl, '_blank'); ContextMenu.hide(); });
            m.querySelector('[data-action="edit-item"]').addEventListener('click', ev => {
                ev.stopPropagation(); ContextMenu.hide();
                state.currentEditItem = itemId;
                const cardEl = document.querySelector('.bm-item[data-id="' + itemId + '"]');
                if (cardEl) {
                    ContextMenu.populateEditForm(cardEl);
                } else {
                    const form = document.getElementById('bmEditForm'); if (!form) return;
                    const iconImg = itemEl.querySelector('img');
                    const icon = iconImg ? iconImg.getAttribute('src') : '';
                    const letterEl = itemEl.querySelector('.bm-item-letter');
                    const textIcon = letterEl ? letterEl.textContent.trim() : '';
                    const bgColor = letterEl ? (letterEl.style.background || letterEl.style.backgroundColor || '#6366f1') : '#6366f1';
                    const setVal = (name, val) => { const el = form.querySelector('[name="' + name + '"]'); if (el) el.value = val; };
                    setVal('url', itemUrl); setVal('title', itemTitle); setVal('icon', icon); setVal('text_icon', textIcon); setVal('bg_color', bgColor); setVal('bg_color_text', bgColor); setVal('describe', '');
                    const idField = document.getElementById('bmEditItemId'); if (idField) idField.value = itemId;
                }
                Modal.closeModal('bmFolderModal');
                Modal.openModal('bmEditModal');
            });
            m.querySelector('[data-action="remove-from-folder"]').addEventListener('click', ev => {
                ev.stopPropagation(); ContextMenu.hide();
                this.moveItemOutOfFolder(itemId, groupId);
            });
        },
        async moveItemOutOfFolder(itemId, groupId) {
            try {
                await api('PUT', '/nav-items/' + itemId, { group_id: 0 });
                Toast.showToast('已移出文件夹', 'success');
                this.openFolder(groupId);
                PageSwitcher.loadPageData(state.activePageId);
            } catch(e) { Toast.showToast('操作失败', 'error'); }
        },
    };

    const BookmarkPicker = {
        currentPage: 1, totalPages: 1, currentSearch: '', currentCategory: '', categories: [],
        init() {
            const modal = document.getElementById('bmPickerModal'); if (!modal) return;
            const closeBtn = modal.querySelector('[data-close="bmPickerModal"]'); if (closeBtn) closeBtn.addEventListener('click', () => this.close());
            modal.addEventListener('click', e => { if (e.target === modal) this.close(); });
            modal.querySelectorAll('.bm-picker-tab').forEach(tab => { tab.addEventListener('click', () => this.switchTab(tab.getAttribute('data-tab'))); });
            this.initManualForm(); this.initCandidateSearch(); this.initEditForm();
        },
        open() { Modal.openModal('bmPickerModal'); this.switchTab('online'); },
        close() { Modal.closeModal('bmPickerModal'); },
        switchTab(tab) {
            state.pickerActiveTab = tab;
            const modal = document.getElementById('bmPickerModal'); if (!modal) return;
            modal.querySelectorAll('.bm-picker-tab').forEach(t => t.classList.remove('is-active'));
            const tt = modal.querySelector('.bm-picker-tab[data-tab="' + tab + '"]'); if (tt) tt.classList.add('is-active');
            modal.querySelectorAll('.bm-picker-panel').forEach(p => p.classList.remove('is-active'));
            const tp = modal.querySelector('.bm-picker-panel[data-tab="' + tab + '"]'); if (tp) tp.classList.add('is-active');
            if (tab === 'online') { this.loadCategories(); this.loadOnlineCandidates(1); }
        },
        initCandidateSearch() { const s = document.getElementById('bmCandidateSearch'); if (s) { let t = null; s.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => { this.currentSearch = s.value.trim(); this.loadOnlineCandidates(1); }, 300); }); } },
        async loadCategories() {
            if (this.categories.length > 0) return;
            try { const res = await api('GET', '/categories'); if (!res.ok) return; this.categories = await res.json(); this.renderCategories(); } catch(e) {}
        },
        renderCategories() {
            const c = document.getElementById('bmCandidateCategories'); if (!c) return;
            let html = '<button class="bm-category-tag is-active" data-category="0">全部</button>';
            this.categories.forEach(cat => { html += '<button class="bm-category-tag" data-category="' + cat.id + '">' + cat.name + '</button>'; });
            c.innerHTML = html;
            c.querySelectorAll('.bm-category-tag').forEach(tag => { tag.addEventListener('click', () => { c.querySelectorAll('.bm-category-tag').forEach(t => t.classList.remove('is-active')); tag.classList.add('is-active'); this.currentCategory = tag.getAttribute('data-category'); this.loadOnlineCandidates(1); }); });
        },
        async loadOnlineCandidates(page) {
            page = page || this.currentPage; this.currentPage = page;
            const grid = document.getElementById('bmCandidateGrid'); if (!grid) return;
            grid.innerHTML = '<div class="bm-candidate-loading">加载中...</div>';
            try {
                const params = new URLSearchParams({ page: String(page), per_page: '20' });
                if (this.currentSearch) params.set('search', this.currentSearch);
                if (this.currentCategory) params.set('category', this.currentCategory);
                const res = await api('GET', '/candidates?' + params.toString());
                if (!res.ok) throw new Error('API error ' + res.status);
                const data = await res.json();
                state.candidates = data.items || data || [];
                state.candidateMeta = data.meta || {};
                this.totalPages = state.candidateMeta.total_pages || 1;
                this.renderCandidateGrid(state.candidates);
                this.renderPagination();
            } catch(e) { grid.innerHTML = '<div class="bm-candidate-loading">加载失败: ' + e.message + '</div>'; }
        },
        renderCandidateGrid(items) {
            const grid = document.getElementById('bmCandidateGrid'); if (!grid) return;
            if (!items || items.length === 0) { grid.innerHTML = '<div class="bm-candidate-loading">暂无内容</div>'; return; }
            grid.innerHTML = items.map(item => {
                const addBtn = '<button class="bm-btn-add" data-source-id="' + item.id + '">添加</button>';
                const iconHtml = item.icon ? '<img src="' + item.icon + '" alt="" loading="lazy">' : '<span class="bm-item-letter" style="background:#94a3b8;width:40px;height:40px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;color:white;font-weight:600;font-size:16px;">' + ((item.title || '?').charAt(0)) + '</span>';
                return '<div class="bm-candidate-item" data-source-id="' + item.id + '"><div class="bm-candidate-icon">' + iconHtml + '</div><div class="bm-candidate-title">' + (item.title || '') + '</div><div class="bm-candidate-desc">' + (item.describe || item.description || '') + '</div><div class="bm-candidate-actions">' + addBtn + '</div></div>';
            }).join('');
            grid.querySelectorAll('.bm-btn-add').forEach(btn => { btn.addEventListener('click', e => { e.stopPropagation(); this.addFromCandidate(btn.getAttribute('data-source-id'), state.groups.length > 0 ? state.groups[0].id : 0); }); });
        },
        renderPagination() {
            const c = document.getElementById('bmCandidatePagination'); if (!c) return;
            if (this.totalPages <= 1) { c.innerHTML = ''; return; }
            let html = '<button class="bm-page-btn' + (this.currentPage <= 1 ? ' is-disabled' : '') + '" data-page="' + (this.currentPage - 1) + '">上一页</button>';
            const sp = Math.max(1, this.currentPage - 2), ep = Math.min(this.totalPages, this.currentPage + 2);
            for (let i = sp; i <= ep; i++) html += '<button class="bm-page-btn' + (i === this.currentPage ? ' is-active' : '') + '" data-page="' + i + '">' + i + '</button>';
            html += '<button class="bm-page-btn' + (this.currentPage >= this.totalPages ? ' is-disabled' : '') + '" data-page="' + (this.currentPage + 1) + '">下一页</button>';
            c.innerHTML = html;
            c.querySelectorAll('.bm-page-btn:not(.is-disabled)').forEach(btn => { btn.addEventListener('click', () => { const p = parseInt(btn.getAttribute('data-page')); if (p >= 1 && p <= this.totalPages) this.loadOnlineCandidates(p); }); });
        },
        async addFromCandidate(sourceId, groupId) {
            try { const res = await api('POST', '/nav-items', { source_type: 'onenav', source_id: sourceId, group_id: groupId, page_id: state.activePageId }); if (!res.ok) throw new Error('API error'); PageSwitcher.loadPageData(state.activePageId); Toast.showToast('添加成功', 'success'); } catch(e) { Toast.showToast('添加失败', 'error'); }
        },
        initManualForm() {
            const form = document.getElementById('bmManualForm'); if (!form) return;
            const fetchIconBtn = document.getElementById('bmFetchIconBtn');
            if (fetchIconBtn) { fetchIconBtn.addEventListener('click', () => { const u = form.querySelector('[name="url"]'); const i = form.querySelector('[name="icon"]'); if (!u || !u.value.trim()) { Toast.showToast('请先填写网络地址', 'error'); return; } const d = u.value.trim().replace(/^https?:\/\//, '').split('/')[0]; if (i) i.value = 'https://faviconsnap.com/api/favicon?url=' + d + '&size=64'; }); }
            this.syncColorInputs(form);
            const saveBtn = document.getElementById('bmManualSaveBtn');
            const saveContinueBtn = document.getElementById('bmManualSaveContinueBtn');
            if (saveBtn) saveBtn.addEventListener('click', e => { e.preventDefault(); this.saveManual(false); });
            if (saveContinueBtn) saveContinueBtn.addEventListener('click', e => { e.preventDefault(); this.saveManual(true); });
        },
        initEditForm() {
            const form = document.getElementById('bmEditForm'); if (!form) return;
            const fetchIconBtn = document.getElementById('bmEditFetchIconBtn');
            if (fetchIconBtn) { fetchIconBtn.addEventListener('click', () => { const u = form.querySelector('[name="url"]'); const i = form.querySelector('[name="icon"]'); if (!u || !u.value.trim()) { Toast.showToast('请先填写网络地址', 'error'); return; } const d = u.value.trim().replace(/^https?:\/\//, '').split('/')[0]; if (i) i.value = 'https://faviconsnap.com/api/favicon?url=' + d + '&size=64'; }); }
            this.syncColorInputs(form);
        },
        syncColorInputs(form) {
            const colorPicker = form.querySelector('[name="bg_color"]');
            const colorText = form.querySelector('[name="bg_color_text"]');
            if (colorPicker && colorText) {
                colorPicker.addEventListener('input', () => { colorText.value = colorPicker.value; });
                colorText.addEventListener('input', () => { if (/^#[0-9a-fA-F]{6}$/.test(colorText.value)) colorPicker.value = colorText.value; });
            }
        },
        async saveManual(continueAdding) {
            const form = document.getElementById('bmManualForm'); if (!form) return;
            const title = form.querySelector('[name="title"]')?.value.trim();
            const url = form.querySelector('[name="url"]')?.value.trim();
            if (!title || !url) { Toast.showToast('请填写名称和网址', 'error'); return; }
            const data = { title, url, describe: form.querySelector('[name="describe"]')?.value.trim() || '', icon: form.querySelector('[name="icon"]')?.value.trim() || '', text_icon: form.querySelector('[name="text_icon"]')?.value.trim() || '', bg_color: form.querySelector('[name="bg_color"]')?.value || '#6366f1', open_in_iframe: form.querySelector('[name="open_in_iframe"]')?.checked ? 1 : 0, group_id: form.querySelector('[name="group_id"]')?.value || (state.groups.length > 0 ? state.groups[0].id : 0), page_id: state.activePageId, source_type: state.userLoggedIn ? 'custom' : 'local' };
            await CanvasManager.addItem(data);
            if (!continueAdding) this.close();
            else ['title','url','describe','text_icon','icon'].forEach(n => { const el = form.querySelector('[name="' + n + '"]'); if (el) el.value = ''; });
        }
    };

    const MemoEditor = {
        autoSaveTimer: null,

        open() {
            const contentEl = document.getElementById('bmMemoModalContent');
            if (contentEl) contentEl.innerHTML = MemoCard.renderPadView();
            Modal.openModal('bmMemoModal');
            this.bindPadEvents();
        },

        bindPadEvents() {
            this.bindNoteListClick();
            this.bindAddNote();
            this.bindDeleteNote();
            this.bindTitleInput();
            this.bindContentInput();
            this.bindSearch();
            this.bindColorPicker();
        },

        bindNoteListClick() {
            const list = document.getElementById('bmMemoNotesList');
            if (!list) return;
            list.addEventListener('click', e => {
                const noteItem = e.target.closest('.bm-memo-note-item[data-note-id]');
                if (!noteItem) return;
                const noteId = noteItem.getAttribute('data-note-id');
                this.switchToNote(noteId);
            });
        },

        switchToNote(noteId) {
            MemoCard.activeNoteId = noteId;
            const contentEl = document.getElementById('bmMemoModalContent');
            if (contentEl) contentEl.innerHTML = MemoCard.renderPadView();
            this.bindPadEvents();
        },

        bindAddNote() {
            const addBtn = document.getElementById('bmMemoAddBtn');
            if (!addBtn) return;
            addBtn.addEventListener('click', () => {
                MemoCard.createNote('新便签', '');
                this.refreshPadView();
                Toast.showToast('已新建便签', 'success');
                const titleInput = document.getElementById('bmMemoTitleInput');
                if (titleInput) { titleInput.focus(); titleInput.select(); }
            });
        },

        bindDeleteNote() {
            const delBtn = document.getElementById('bmMemoDeleteBtn');
            if (!delBtn) return;
            delBtn.addEventListener('click', () => {
                if (!MemoCard.activeNoteId) return;
                const activeNote = MemoCard.getActiveNote();
                if (!activeNote) return;
                if (confirm('确定要删除便签「' + activeNote.title + '」吗？')) {
                    MemoCard.deleteNote(MemoCard.activeNoteId);
                    this.refreshPadView();
                    Toast.showToast('已删除', 'success');
                }
            });
        },

        bindTitleInput() {
            const titleInput = document.getElementById('bmMemoTitleInput');
            if (!titleInput) return;
            let lastVal = titleInput.value;
            titleInput.addEventListener('input', () => {
                if (titleInput.value === lastVal) return;
                lastVal = titleInput.value;
                this.scheduleAutoSave({ title: titleInput.value.trim() || '无标题' });
            });
        },

        bindContentInput() {
            const textarea = document.getElementById('bmMemoEditorArea');
            if (!textarea) return;
            let lastVal = textarea.value;
            textarea.addEventListener('input', () => {
                if (textarea.value === lastVal) return;
                lastVal = textarea.value;
                this.scheduleAutoSave({ content: textarea.value });
            });
        },

        scheduleAutoSave(data) {
            if (this.autoSaveTimer) clearTimeout(this.autoSaveTimer);
            this.autoSaveTimer = setTimeout(() => {
                if (MemoCard.activeNoteId) {
                    MemoCard.updateNote(MemoCard.activeNoteId, data);
                }
            }, 400);
        },

        bindSearch() {
            const searchInput = document.getElementById('bmMemoSearchInput');
            const list = document.getElementById('bmMemoNotesList');
            if (!searchInput || !list) return;
            searchInput.addEventListener('input', () => {
                const kw = searchInput.value.trim();
                const results = MemoCard.searchNotes(kw);
                if (results.length === 0) {
                    list.innerHTML = '<div class="bm-memo-empty-list"><p>没有匹配的便签</p></div>';
                    return;
                }
                list.innerHTML = results.map(n => {
                    const isActive = n.id === MemoCard.activeNoteId;
                    return '<div class="bm-memo-note-item' + (isActive ? ' is-active' : '') + '" data-note-id="' + n.id + '">' +
                        '<div class="bm-memo-note-item-title">' + MemoCard.escapeHtml(n.title || '无标题') + '</div>' +
                        '<div class="bm-memo-note-item-time">' + MemoCard.formatDate(n.updated_at) + '</div></div>';
                }).join('');
            });
        },

        bindColorPicker() {
            const colorDot = document.getElementById('bmMemoColorDot');
            if (!colorDot) return;
            const colors = ['#f59e0b','#ef4444','#10b981','#3b82f6','#8b5cf6','#ec4899','#6366f1'];
            let idx = colors.indexOf((MemoCard.pad && MemoCard.pad.color) || '#f59e0b');
            if (idx < 0) idx = 0;
            colorDot.addEventListener('click', () => {
                idx = (idx + 1) % colors.length;
                const newColor = colors[idx];
                MemoCard.setColor(newColor);
                colorDot.style.background = newColor;
                const padView = document.querySelector('.bm-memo-pad-view');
                if (padView) padView.style.setProperty('--memo-accent', newColor);
                const cardEl = document.querySelector('.bm-memo-pad-card');
                if (cardEl) cardEl.style.setProperty('--memo-accent', newColor);
            });
        },

        refreshPadView() {
            const contentEl = document.getElementById('bmMemoModalContent');
            if (contentEl) contentEl.innerHTML = MemoCard.renderPadView();
            this.bindPadEvents();
        },
    };

    const SettingsPanel = {
        init() {
            const panel = document.getElementById('bmSettingsPanel'); if (!panel) return;
            const closeBtn = document.getElementById('bmSettingsClose'); if (closeBtn) closeBtn.addEventListener('click', () => this.close());
            panel.querySelectorAll('.bm-settings-tab').forEach(tab => { tab.addEventListener('click', () => { panel.querySelectorAll('.bm-settings-tab').forEach(t => t.classList.remove('is-active')); tab.classList.add('is-active'); const tn = tab.getAttribute('data-tab'); panel.querySelectorAll('.bm-settings-panel-tab').forEach(p => p.classList.remove('is-active')); const tp = panel.querySelector('.bm-settings-panel-tab[data-tab="' + tn + '"]'); if (tp) tp.classList.add('is-active'); }); });
            this.bindLivePreview();
            this.bindWallpaperControls();
            const saveBtn = document.getElementById('bmSettingsSaveBtn'); if (saveBtn) saveBtn.addEventListener('click', () => this.save());
        },
        open() { const p = document.getElementById('bmSettingsPanel'); if (p) p.classList.add('is-open'); },
        close() { const p = document.getElementById('bmSettingsPanel'); if (p) p.classList.remove('is-open'); },
        bindLivePreview() {
            const root = document.documentElement; const panel = document.getElementById('bmSettingsPanel'); if (!panel) return;
            const isr = panel.querySelector('[name="appearance.icon_size"]'); if (isr) { isr.addEventListener('input', () => { root.style.setProperty('--bm-card-icon-size', isr.value + 'px'); const h = document.getElementById('iconSizeValue'); if (h) h.textContent = isr.value + 'px'; }); }
            const cr = panel.querySelector('[name="appearance.columns"]'); if (cr) { cr.addEventListener('input', () => { root.style.setProperty('--bm-grid-columns', cr.value); const h = document.getElementById('columnsValue'); if (h) h.textContent = cr.value; }); }
            const tc = panel.querySelector('[name="appearance.text_color"]'); if (tc) { tc.addEventListener('input', () => { root.style.setProperty('--bm-card-text-color', tc.value); }); }
            const rr = panel.querySelector('[name="appearance.card_radius"]'); if (rr) { rr.addEventListener('input', () => { root.style.setProperty('--bm-card-border-radius', rr.value + 'px'); const h = document.getElementById('radiusValue'); if (h) h.textContent = rr.value + 'px'; }); }
            const gr = panel.querySelector('[name="appearance.card_gap"]'); if (gr) { gr.addEventListener('input', () => { root.style.setProperty('--bm-grid-gap', gr.value + 'px'); const h = document.getElementById('gapValue'); if (h) h.textContent = gr.value + 'px'; }); }
            const sm = panel.querySelector('[name="sidebar.mode"]'); if (sm) { sm.addEventListener('change', () => { const sb = document.getElementById('bmSidebar'); if (sb) { if (sm.value === 'autohide') sb.classList.add('bm-sidebar--autohide'); else sb.classList.remove('bm-sidebar--autohide'); } }); }
        },
        bindWallpaperControls() {
            const panel = document.getElementById('bmSettingsPanel'); if (!panel) return;
            const typeSelect = document.getElementById('bmWallpaperType');
            const colorGroup = document.getElementById('bmWallpaperColorGroup');
            const gradientGroup = document.getElementById('bmWallpaperGradientGroup');
            const imageGroup = document.getElementById('bmWallpaperImageGroup');
            const colorPicker = document.getElementById('bmWallpaperColor');
            const colorText = document.getElementById('bmWallpaperColorText');
            const gradFrom = document.getElementById('bmWallpaperGradFrom');
            const gradFromText = document.getElementById('bmWallpaperGradFromText');
            const gradTo = document.getElementById('bmWallpaperGradTo');
            const gradToText = document.getElementById('bmWallpaperGradToText');
            const blurRange = document.getElementById('bmWallpaperBlur');
            const overlayRange = document.getElementById('bmWallpaperOverlay');
            if (typeSelect) {
                const updateVisibility = () => {
                    const t = typeSelect.value;
                    if (colorGroup) colorGroup.style.display = (t === 'color') ? '' : 'none';
                    if (gradientGroup) gradientGroup.style.display = (t === 'gradient') ? '' : 'none';
                    if (imageGroup) imageGroup.style.display = (t === 'image') ? '' : 'none';
                };
                typeSelect.addEventListener('change', updateVisibility);
                updateVisibility();
            }
            if (colorPicker && colorText) {
                colorPicker.addEventListener('input', () => { colorText.value = colorPicker.value; this.applyWallpaperPreview(); });
                colorText.addEventListener('input', () => { if (/^#[0-9a-fA-F]{6}$/.test(colorText.value)) { colorPicker.value = colorText.value; this.applyWallpaperPreview(); } });
            }
            if (gradFrom && gradFromText) {
                gradFrom.addEventListener('input', () => { gradFromText.value = gradFrom.value; this.applyWallpaperPreview(); });
                gradFromText.addEventListener('input', () => { if (/^#[0-9a-fA-F]{6}$/.test(gradFromText.value)) { gradFrom.value = gradFromText.value; this.applyWallpaperPreview(); } });
            }
            if (gradTo && gradToText) {
                gradTo.addEventListener('input', () => { gradToText.value = gradTo.value; this.applyWallpaperPreview(); });
                gradToText.addEventListener('input', () => { if (/^#[0-9a-fA-F]{6}$/.test(gradToText.value)) { gradTo.value = gradToText.value; this.applyWallpaperPreview(); } });
            }
            if (blurRange) {
                blurRange.addEventListener('input', () => { const h = document.getElementById('wallpaperBlurValue'); if (h) h.textContent = blurRange.value + 'px'; this.applyWallpaperPreview(); });
            }
            if (overlayRange) {
                overlayRange.addEventListener('input', () => { const h = document.getElementById('wallpaperOverlayValue'); if (h) h.textContent = overlayRange.value + '%'; this.applyWallpaperPreview(); });
            }
        },
        applyWallpaperPreview() {
            const w = document.querySelector('.bm-wallpaper'); if (!w) return;
            const typeSelect = document.getElementById('bmWallpaperType');
            const blurRange = document.getElementById('bmWallpaperBlur');
            const overlayRange = document.getElementById('bmWallpaperOverlay');
            const t = typeSelect ? typeSelect.value : 'color';
            const blur = blurRange ? blurRange.value : 20;
            const overlay = overlayRange ? overlayRange.value : 15;
            w.style.filter = 'blur(' + blur + 'px) brightness(' + (100 - overlay) + '%)';
            switch(t) {
                case 'color': {
                    const c = document.getElementById('bmWallpaperColor');
                    w.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;background-size:cover;background-position:center;background-repeat:no-repeat;filter:blur(' + blur + 'px) brightness(' + (100 - overlay) + '%);background-color:' + (c ? c.value : '#1a1a2e');
                    break;
                }
                case 'gradient': {
                    const gf = document.getElementById('bmWallpaperGradFrom');
                    const gt = document.getElementById('bmWallpaperGradTo');
                    w.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;background-size:cover;background-position:center;background-repeat:no-repeat;filter:blur(' + blur + 'px) brightness(' + (100 - overlay) + '%);background:linear-gradient(135deg,' + (gf ? gf.value : '#0c0c1d') + ' 0%,' + (gt ? gt.value : '#16213e') + ' 100%)';
                    break;
                }
                case 'image': {
                    const iu = document.getElementById('bmWallpaperImageUrl');
                    w.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;background-size:cover;background-position:center;background-repeat:no-repeat;filter:blur(' + blur + 'px) brightness(' + (100 - overlay) + '%);background-image:url(' + (iu ? iu.value : '') + ')';
                    break;
                }
                case 'bing': {
                    w.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;background-size:cover;background-position:center;background-repeat:no-repeat;filter:blur(' + blur + 'px) brightness(' + (100 - overlay) + '%)';
                    break;
                }
            }
        },
        async save() {
            const panel = document.getElementById('bmSettingsPanel'); if (!panel) return;
            const values = {};
            panel.querySelectorAll('input[name], select[name]').forEach(el => {
                const n = el.getAttribute('name');
                if (n.endsWith('_text')) return;
                if (el.type === 'checkbox') values[n] = el.checked ? 1 : 0;
                else if (el.type === 'color') {
                    const textEl = panel.querySelector('[name="' + n + '_text"]');
                    values[n] = textEl ? textEl.value : el.value;
                }
                else values[n] = el.value;
            });
            try {
                const res = await api('PUT', '/settings', values);
                if (!res.ok) throw new Error('Save failed');
                Object.assign(state.settings, values);
                Theme.applyWallpaper();
                Search.applySavedEngine();
                Toast.showToast('设置已保存', 'success');
            } catch(e) { Toast.showToast('保存失败', 'error'); }
        }
    };

    const Sidebar = {
        init() {
            const avatar = document.getElementById('bmSidebarAvatar');
            if (avatar) { avatar.addEventListener('click', () => this.handleAvatarClick()); }
            const settingsBtn = document.getElementById('bmSidebarSettingsBtn');
            if (settingsBtn) { settingsBtn.addEventListener('click', () => SettingsPanel.open()); }
            const pagesContainer = document.getElementById('bmSidebarPages');
            if (pagesContainer) {
                pagesContainer.addEventListener('contextmenu', e => {
                    const pageEl = e.target.closest('.bm-sidebar-page[data-page-id]');
                    if (pageEl) { e.preventDefault(); e.stopPropagation(); this.showPageContextMenu(e, pageEl); }
                });
            }
            const addPageBtn = document.getElementById('bmSidebarAddPage');
            if (addPageBtn) { addPageBtn.addEventListener('click', () => this.addPage()); }
        },
        handleAvatarClick() {
            if (state.userLoggedIn) {
                if (confirm('确定要退出登录吗？')) {
                    const logoutUrl = state.currentUser?.logout_url || '/wp-login.php?action=logout';
                    const separator = logoutUrl.includes('?') ? '&' : '?';
                    window.location.href = logoutUrl + separator + 'redirect_to=' + encodeURIComponent(window.location.href);
                }
            } else {
                const currentUrl = encodeURIComponent(window.location.href);
                window.location.href = '/wp-login.php?redirect_to=' + currentUrl;
            }
        },
        async addPage() {
            const title = prompt('输入分类页名称:');
            if (!title) return;
            try {
                const res = await api('POST', '/pages', { title, icon: title.charAt(0) });
                if (!res.ok) throw new Error('API error');
                const data = await res.json();
                Toast.showToast('分类页创建成功', 'success');
                const pagesContainer = document.getElementById('bmSidebarPages');
                if (pagesContainer) {
                    const pageData = data.page || data;
                    const pageId = pageData.id || data.id || Date.now();
                    const pageEl = document.createElement('div');
                    pageEl.className = 'bm-sidebar-page';
                    pageEl.setAttribute('data-page-id', pageId);
                    pageEl.setAttribute('data-page-title', title);
                    pageEl.setAttribute('data-is-default', '0');
                    pageEl.innerHTML = '<span class="bm-sidebar-page-icon">' + (pageData.icon || title.charAt(0)) + '</span><span class="bm-sidebar-page-title">' + title + '</span>';
                    const addBtn = pagesContainer.querySelector('.bm-sidebar-add-page');
                    if (addBtn) pagesContainer.insertBefore(pageEl, addBtn);
                    else pagesContainer.appendChild(pageEl);
                    pageEl.addEventListener('click', () => PageSwitcher.switchTo(String(pageId)));
                    pageEl.addEventListener('contextmenu', e => { e.preventDefault(); e.stopPropagation(); this.showPageContextMenu(e, pageEl); });
                    state.pages.push({ id: pageId, title, icon: pageData.icon || title.charAt(0) });
                    pagesContainer.scrollTop = pagesContainer.scrollHeight;
                }
            } catch(e) { Toast.showToast('创建失败', 'error'); }
        },
        showPageContextMenu(e, pageEl) {
            const pageId = pageEl.getAttribute('data-page-id');
            const pageTitle = pageEl.getAttribute('data-page-title') || '';
            const isDefault = pageEl.getAttribute('data-is-default') === '1';
            const html = '<div class="bm-context-menu">' +
                '<div class="bm-context-item" data-action="edit-page"><span class="ctx-icon">✏️</span><span>编辑分类页</span></div>' +
                (isDefault ? '' : '<div class="bm-context-item bm-context-item--danger" data-action="delete-page"><span class="ctx-icon">🗑️</span><span>删除分类页</span></div>') +
                '</div>';
            ContextMenu.render(html, e.clientX, e.clientY);
            const w = document.getElementById('bmContextMenuWrapper'); if (!w) return;
            const m = w.querySelector('.bm-context-menu'); if (!m) return;
            m.querySelector('[data-action="edit-page"]').addEventListener('click', async ev => {
                ev.stopPropagation(); ContextMenu.hide();
                const newTitle = prompt('修改分类页名称:', pageTitle);
                if (newTitle && newTitle !== pageTitle) {
                    try { await api('PUT', '/pages/' + pageId, { title: newTitle }); Toast.showToast('已更新', 'success'); location.reload(); } catch(e) { Toast.showToast('更新失败', 'error'); }
                }
            });
            const delBtn = m.querySelector('[data-action="delete-page"]');
            if (delBtn) { delBtn.addEventListener('click', async ev => { ev.stopPropagation(); ContextMenu.hide(); if (confirm('删除分类页将同时删除其下所有内容，确定吗？')) { try { await api('DELETE', '/pages/' + pageId); Toast.showToast('已删除', 'success'); location.reload(); } catch(e) { Toast.showToast('删除失败', 'error'); } } }); }
        }
    };

    const Dock = {
        init() {
            const d = document.getElementById('bmDockBar'); if (!d) return;
            d.addEventListener('click', e => { const i = e.target.closest('.bm-dock-item'); if (i) { const u = i.getAttribute('data-url'); if (u) window.open(u, '_blank'); } });
            d.addEventListener('contextmenu', e => {
                const i = e.target.closest('.bm-dock-item');
                if (i) { e.preventDefault(); e.stopPropagation(); this.showDockMenu(e, i); }
            });
        },
        async addToDock(itemId) {
            try {
                const res = await api('PUT', '/dock/add/' + itemId);
                if (!res.ok) throw new Error('API error');
                const data = await res.json();
                const cardEl = document.querySelector('.bm-item[data-id="' + itemId + '"]');
                const title = cardEl?.querySelector('.bm-item-name')?.textContent || '';
                const url = cardEl?.getAttribute('data-url') || '';
                const iconImg = cardEl?.querySelector('.bm-item-icon-box img');
                const letterEl = cardEl?.querySelector('.bm-item-letter');
                let iconHtml = '';
                if (iconImg) {
                    iconHtml = '<img src="' + iconImg.src + '" alt="" loading="lazy">';
                } else if (letterEl) {
                    iconHtml = letterEl.outerHTML;
                } else {
                    iconHtml = '<span class="bm-dock-text-icon" style="background:#94a3b8;">' + (title || '?').charAt(0) + '</span>';
                }
                const dockInner = document.getElementById('bmDockInner');
                if (dockInner) {
                    const dockItem = document.createElement('div');
                    dockItem.className = 'bm-dock-item';
                    dockItem.setAttribute('data-id', data.id || itemId);
                    dockItem.setAttribute('data-url', url);
                    dockItem.setAttribute('data-title', title);
                    dockItem.innerHTML = '<div class="bm-dock-item-icon">' + iconHtml + '</div>';
                    dockInner.appendChild(dockItem);
                }
                Toast.showToast('已添加到Dock', 'success');
            } catch(e) { Toast.showToast('操作失败', 'error'); }
        },
        async removeFromDock(itemId) {
            try {
                const res = await api('PUT', '/dock/remove/' + itemId);
                if (!res.ok) throw new Error('API error');
                const el = document.querySelector('.bm-dock-item[data-id="' + itemId + '"]');
                if (el) el.remove();
                Toast.showToast('已从Dock移除', 'success');
            } catch(e) { Toast.showToast('操作失败', 'error'); }
        },
        showDockMenu(e, dockItem) {
            const itemId = dockItem.getAttribute('data-id');
            const itemUrl = dockItem.getAttribute('data-url') || '';
            const html = '<div class="bm-context-menu">' +
                '<div class="bm-context-item" data-action="open-new-tab"><span class="ctx-icon">🔗</span><span>新标签打开</span></div>' +
                '<div class="bm-context-item" data-action="edit-bookmark"><span class="ctx-icon">✏️</span><span>编辑标签</span></div>' +
                '<div class="bm-context-divider"></div>' +
                '<div class="bm-context-item bm-context-item--danger" data-action="remove-from-dock"><span class="ctx-icon">🗑️</span><span>从Dock移除</span></div></div>';
            ContextMenu.render(html, e.clientX, e.clientY);
            const w = document.getElementById('bmContextMenuWrapper'); if (!w) return;
            const m = w.querySelector('.bm-context-menu'); if (!m) return;
            m.querySelector('[data-action="open-new-tab"]').addEventListener('click', ev => { ev.stopPropagation(); if (itemUrl) window.open(itemUrl, '_blank'); ContextMenu.hide(); });
            m.querySelector('[data-action="edit-bookmark"]').addEventListener('click', ev => {
                ev.stopPropagation(); ContextMenu.hide();
                state.currentEditItem = itemId;
                const cardEl = document.querySelector('.bm-item[data-id="' + itemId + '"]');
                if (cardEl) {
                    ContextMenu.populateEditForm(cardEl);
                } else {
                    const form = document.getElementById('bmEditForm'); if (!form) return;
                    const title = dockItem.getAttribute('data-title') || '';
                    const iconImg = dockItem.querySelector('img');
                    const icon = iconImg ? iconImg.getAttribute('src') : '';
                    const letterEl = dockItem.querySelector('.bm-dock-text-icon');
                    const textIcon = letterEl ? letterEl.textContent.trim() : '';
                    const bgColor = letterEl ? (letterEl.style.background || letterEl.style.backgroundColor || '#6366f1') : '#6366f1';
                    const setVal = (name, val) => { const el = form.querySelector('[name="' + name + '"]'); if (el) el.value = val; };
                    setVal('url', itemUrl); setVal('title', title); setVal('icon', icon); setVal('text_icon', textIcon); setVal('bg_color', bgColor); setVal('bg_color_text', bgColor); setVal('describe', '');
                    const idField = document.getElementById('bmEditItemId'); if (idField) idField.value = itemId;
                }
                Modal.openModal('bmEditModal');
            });
            m.querySelector('[data-action="remove-from-dock"]').addEventListener('click', ev => { ev.stopPropagation(); ContextMenu.hide(); this.removeFromDock(itemId); });
        }
    };

    const Theme = {
        init() { this.applyWallpaper(); },
        applyWallpaper() {
            const w = document.querySelector('.bm-wallpaper'); if (!w) return;
            const wt = state.settings['wallpaper.type'] || 'color';
            const wv = state.settings['wallpaper.value'] || '#1a1a2e';
            const blur = state.settings['wallpaper.blur'] ?? 20;
            const overlay = state.settings['wallpaper.overlay'] ?? 15;
            const filterStr = 'blur(' + blur + 'px) brightness(' + (100 - overlay) + '%)';
            const base = 'position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;background-size:cover;background-position:center;background-repeat:no-repeat;filter:' + filterStr + ';';
            switch(wt) {
                case 'color': w.style.cssText = base + 'background-color:' + wv; break;
                case 'gradient': {
                    const gf = state.settings['wallpaper.gradient_from'] || '#0c0c1d';
                    const gt = state.settings['wallpaper.gradient_to'] || '#16213e';
                    w.style.cssText = base + 'background:linear-gradient(135deg,' + gf + ' 0%,' + gt + ' 100%)';
                    break;
                }
                case 'image': {
                    const iu = state.settings['wallpaper.image_url'] || wv;
                    w.style.cssText = base + 'background-image:url(' + iu + ')';
                    break;
                }
                case 'bing': {
                    w.style.cssText = base;
                    break;
                }
                default: w.style.cssText = base + 'background-color:#1a1a2e';
            }
        },
        cycleWallpaper() {
            const colors = ['#1a1a2e', '#0f172a', '#1e293b'];
            const gradients = [
                { from: '#0c0c1d', to: '#16213e' },
                { from: '#0f0c29', to: '#24243e' },
                { from: '#1a1a2e', to: '#302b63' }
            ];
            const w = document.querySelector('.bm-wallpaper'); if (!w) return;
            const currentType = state.settings['wallpaper.type'] || 'color';
            if (currentType === 'color') {
                const g = gradients[Math.floor(Math.random() * gradients.length)];
                state.settings['wallpaper.type'] = 'gradient';
                state.settings['wallpaper.gradient_from'] = g.from;
                state.settings['wallpaper.gradient_to'] = g.to;
            } else {
                state.settings['wallpaper.type'] = 'color';
                state.settings['wallpaper.value'] = colors[Math.floor(Math.random() * colors.length)];
            }
            this.applyWallpaper();
        }
    };

    function renderPage() {
        const root = document.documentElement; const s = state.settings;
        if (s['appearance.icon_size']) root.style.setProperty('--bm-card-icon-size', s['appearance.icon_size'] + 'px');
        if (s['appearance.columns']) root.style.setProperty('--bm-grid-columns', s['appearance.columns']);
        if (s['appearance.text_color']) root.style.setProperty('--bm-card-text-color', s['appearance.text_color']);
        if (s['appearance.card_radius']) root.style.setProperty('--bm-card-border-radius', s['appearance.card_radius'] + 'px');
        if (s['appearance.card_gap']) root.style.setProperty('--bm-grid-gap', s['appearance.card_gap'] + 'px');
    }

    function applyInitData(data) {
        state.settings = data.settings || {};
        state.groups = data.groups || [];
        state.navItems = data.nav_items || {};
        state.pages = data.pages || [];
        state.dockItems = data.dock_items || [];
        state.userLoggedIn = !!data.user_logged_in;
        state.activePageId = data.active_page_id || (state.pages.length > 0 ? state.pages[0].id : 0);
        state.currentUser = data.current_user || {};
        renderPage();
        PageSwitcher.renderPage();
        Theme.applyWallpaper();
        Search.applySavedEngine();
    }

    async function fetchInitialData() {
        try {
            const res = await api('GET', '/init-data?page_id=' + state.pageId + '&active_page=' + state.activePageId);
            if (!res.ok) throw new Error('API error ' + res.status);
            const data = await res.json();
            applyInitData(data);
        } catch(e) {
            console.warn('BM_App: REST API数据加载失败，使用PHP渲染数据', e);
            const pageEl = document.querySelector('.bm-nav-page');
            if (pageEl) {
                state.settings = {};
                state.activePageId = pageEl.getAttribute('data-active-page-id') || '0';
            }
            renderPage();
            LocalStorage.getAll().forEach(item => CanvasManager.renderItem(item));
            Search.applySavedEngine();
        }
    }

    function init() {
        const pageEl = document.querySelector('.bm-nav-page'); if (!pageEl) return;
        state.pageId = pageEl.getAttribute('data-page-id') || '0';
        state.activePageId = pageEl.getAttribute('data-active-page-id') || '0';
        state.userLoggedIn = bmVars.userLoggedIn || false;
        LocalStorage.init(); Modal.init(); Clock.init(); Search.init();
        ContextMenu.init(); DragSort.init(); PageSwitcher.init();
        SettingsPanel.init(); Sidebar.init(); Dock.init(); Theme.init();
        CanvasManager.init(); BookmarkPicker.init(); MemoCard.init();
        if (bmVars.initData) {
            applyInitData(bmVars.initData);
            delete bmVars.initData;
        } else {
            fetchInitialData();
        }
        pageEl.addEventListener('click', e => {
            const folder = e.target.closest('.bm-item--folder');
            if (folder && !e.target.closest('.bm-context-menu')) {
                e.stopPropagation();
                CanvasManager.openFolder(folder.getAttribute('data-group-id'));
                return;
            }
            const card = e.target.closest('.bm-item[data-type="bookmark"]');
            if (card && !e.target.closest('.bm-context-menu')) { const url = card.getAttribute('data-url'); if (url) window.open(url, '_blank'); }
        });
        const mt = document.getElementById('bmMobileToggle');
        if (mt) mt.addEventListener('click', () => { const sb = document.getElementById('bmSidebar'); if (sb) sb.classList.toggle('is-open'); });
        const ngsb = document.getElementById('bmNewGroupSaveBtn');
        if (ngsb) ngsb.addEventListener('click', async () => { const f = document.getElementById('bmNewGroupForm'); if (!f) return; const t = f.querySelector('[name="title"]')?.value.trim(); if (!t) { Toast.showToast('请输入分组名称', 'error'); return; } try { await api('POST', '/groups', { title: t, icon: f.querySelector('[name="icon"]')?.value.trim() || '', page_id: state.activePageId }); Toast.showToast('分组创建成功', 'success'); Modal.closeModal('bmNewGroupModal'); location.reload(); } catch(e) { Toast.showToast('创建失败', 'error'); } });
        const esb = document.getElementById('bmEditSaveBtn');
        if (esb) esb.addEventListener('click', async () => { const f = document.getElementById('bmEditForm'); if (!f || !state.currentEditItem) return; const d = { title: f.querySelector('[name="title"]')?.value.trim(), url: f.querySelector('[name="url"]')?.value.trim(), describe: f.querySelector('[name="describe"]')?.value.trim(), icon: f.querySelector('[name="icon"]')?.value.trim(), text_icon: f.querySelector('[name="text_icon"]')?.value.trim(), bg_color: f.querySelector('[name="bg_color"]')?.value, open_in_iframe: f.querySelector('[name="open_in_iframe"]')?.checked ? 1 : 0 }; await CanvasManager.editItem(state.currentEditItem, d); Toast.showToast('保存成功', 'success'); Modal.closeModal('bmEditModal'); });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();

    return { init, state };
})();
