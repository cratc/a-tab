const MemoCard = {
    KEY: 'bm_memo_pad',
    pad: null,
    activeNoteId: null,

    init() {
        this.loadFromStorage();
        if (!this.pad) {
            this.pad = { color: '#f59e0b', notes: [] };
            this.persist();
        }
        this.activeNoteId = (this.pad.notes.length > 0) ? this.pad.notes[0].id : null;
    },

    loadFromStorage() {
        try {
            const raw = localStorage.getItem(this.KEY);
            this.pad = raw ? JSON.parse(raw) : null;
        } catch (e) {
            this.pad = null;
        }
    },

    persist() {
        localStorage.setItem(this.KEY, JSON.stringify(this.pad));
    },

    getLatestNote() {
        if (!this.pad || !this.pad.notes || this.pad.notes.length === 0) return null;
        let latest = this.pad.notes[0];
        for (let i = 1; i < this.pad.notes.length; i++) {
            if (this.pad.notes[i].updated_at > latest.updated_at) latest = this.pad.notes[i];
        }
        return latest;
    },

    getActiveNote() {
        if (!this.activeNoteId || !this.pad || !this.pad.notes) return null;
        return this.pad.notes.find(n => n.id === this.activeNoteId);
    },

    createNote(title, content) {
        if (!this.pad) this.pad = { color: '#f59e0b', notes: [] };
        const note = {
            id: 'note_' + Date.now(),
            title: title || '新便签',
            content: content || '',
            created_at: Date.now(),
            updated_at: Date.now(),
        };
        this.pad.notes.unshift(note);
        this.activeNoteId = note.id;
        this.persist();
        return note;
    },

    getAll() {
        return [];
    },

    updateNote(id, data) {
        if (!this.pad || !this.pad.notes) return false;
        const idx = this.pad.notes.findIndex(n => n.id === id);
        if (idx === -1) return false;
        Object.assign(this.pad.notes[idx], data, { updated_at: Date.now() });
        this.persist();
        return true;
    },

    deleteNote(id) {
        if (!this.pad || !this.pad.notes) return;
        this.pad.notes = this.pad.notes.filter(n => n.id !== id);
        if (this.activeNoteId === id) {
            this.activeNoteId = (this.pad.notes.length > 0) ? this.pad.notes[0].id : null;
        }
        this.persist();
    },

    setColor(color) {
        if (!this.pad) this.pad = { color: color, notes: [] };
        else this.pad.color = color;
        this.persist();
    },

    searchNotes(keyword) {
        if (!this.pad || !this.pad.notes) return [];
        if (!keyword) return this.pad.notes;
        const kw = keyword.toLowerCase();
        return this.pad.notes.filter(n =>
            (n.title && n.title.toLowerCase().includes(kw)) ||
            (n.content && n.content.toLowerCase().includes(kw))
        );
    },

    renderCard() {
        const latest = this.getLatestNote();
        const previewTitle = latest ? this.escapeHtml(latest.title) : '';
        const previewContent = latest && latest.content
            ? this.escapeHtml(latest.content.substring(0, 80)) + (latest.content.length > 80 ? '...' : '')
            : '<span style="opacity:0.5">点击打开便签夹</span>';
        const noteCount = (this.pad && this.pad.notes) ? this.pad.notes.length : 0;
        return `<div class="bm-item bm-item--memo layout-2x2"
             data-id="memopad"
             data-type="memo"
             data-layout="2x2"
             draggable="true">
            <div class="bm-memo-pad-card" style="--memo-accent:${(this.pad && this.pad.color) || '#f59e0b'}">
                <div class="bm-memo-pad-header">
                    <span class="bm-memo-pad-title">备忘录</span>
                    ${noteCount > 0 ? '<span class="bm-memo-pad-count">' + noteCount + '</span>' : ''}
                </div>
                <div class="bm-memo-pad-body">
                    ${previewTitle ? '<div class="bm-memo-pad-preview-title">' + previewTitle + '</div>' : ''}
                    <div class="bm-memo-pad-preview-content">${previewContent}</div>
                </div>
                <div class="bm-memo-pad-footer">
                    <span>备忘录</span>
                </div>
            </div>
        </div>`;
    },

    renderPadView() {
        const padColor = (this.pad && this.pad.color) || '#f59e0b';
        const notes = (this.pad && this.pad.notes) || [];
        const activeNote = this.getActiveNote();
        const listItems = notes.map(n => {
            const isActive = n.id === this.activeNoteId;
            const dateStr = this.formatDate(n.updated_at);
            return '<div class="bm-memo-note-item' + (isActive ? ' is-active' : '') + '" data-note-id="' + n.id + '">' +
                '<div class="bm-memo-note-item-title">' + this.escapeHtml(n.title || '无标题') + '</div>' +
                '<div class="bm-memo-note-item-time">' + dateStr + '</div></div>';
        }).join('');
        const noNotesMsg = notes.length === 0
            ? '<div class="bm-memo-empty-list"><p>暂无便签</p><p class="bm-memo-empty-hint">点击下方 + 新建便签</p></div>'
            : '';
        const editorHtml = activeNote
            ? '<div class="bm-memo-editor-area"><textarea class="bm-memo-editor-textarea" id="bmMemoEditorArea" placeholder="输入内容...">' + this.escapeHtml(activeNote.content) + '</textarea></div>'
            : '<div class="bm-memo-editor-placeholder"><p>选择或新建一个便签开始记录</p></div>';
        const bottomInfo = activeNote
            ? '最后编辑：' + this.formatDateTime(activeNote.updated_at) + '，创建：' + this.formatDateTime(activeNote.created_at)
            : '';
        return `<div class="bm-memo-pad-view" style="--memo-accent:${padColor}">
            <div class="bm-memo-pad-sidebar">
                <div class="bm-memo-pad-sidebar-header">
                    <h3 class="bm-memo-pad-sidebar-title">备忘录</h3>
                    <button class="bm-memo-color-dot" id="bmMemoColorDot" style="background:${padColor}" title="更换主题色"></button>
                </div>
                <div class="bm-memo-pad-search">
                    <input type="text" class="bm-memo-search-input" id="bmMemoSearchInput" placeholder="search">
                </div>
                <div class="bm-memo-notes-list" id="bmMemoNotesList">${listItems}${noNotesMsg}</div>
                <div class="bm-memo-pad-actions">
                    <button class="bm-memo-add-btn" id="bmMemoAddBtn" title="新建便签">+</button>
                </div>
            </div>
            <div class="bm-memo-pad-main">
                <div class="bm-memo-pad-main-header">
                    <input type="text" class="bm-memo-title-input" id="bmMemoTitleInput"
                        value="' + this.escapeHtml(activeNote ? activeNote.title : '') + '"
                        placeholder="标题" maxlength="50"' + (activeNote ? '' : ' disabled') + '>
                    <div class="bm-memo-pad-toolbar">
                        <button class="bm-memo-tool-btn" id="bmMemoDeleteBtn" title="删除便签"' + (activeNote ? '' : ' disabled') + '>🗑️</button>
                    </div>
                </div>
                <div class="bm-memo-pad-main-body">${editorHtml}</div>
                <div class="bm-memo-pad-main-footer">${bottomInfo}</div>
            </div>
        </div>`;
    },

    escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    },

    formatDate(ts) {
        const d = new Date(ts);
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        const h = String(d.getHours()).padStart(2, '0');
        const min = String(d.getMinutes()).padStart(2, '0');
        return m + '/' + day + ' ' + h + ':' + min;
    },

    formatDateTime(ts) {
        const d = new Date(ts);
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        const h = String(d.getHours()).padStart(2, '0');
        const min = String(d.getMinutes()).padStart(2, '0');
        const sec = String(d.getSeconds()).padStart(2, '0');
        return y + '-' + m + '-' + day + ' ' + h + ':' + min + ':' + sec;
    },
};