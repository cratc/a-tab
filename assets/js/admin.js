(function () {
  "use strict";

  var adminAjaxUrl = (window.bmVars && bmVars.ajaxUrl) || "";
  var adminNonce = (window.bmVars && bmVars.nonce) || "";

  function initMediaUpload() {
    var uploadBtns = document.querySelectorAll(".bm-media-upload-btn");

    uploadBtns.forEach(function (btn) {
      btn.addEventListener("click", function (e) {
        e.preventDefault();

        var frame = wp.media({
          title: btn.getAttribute("data-title") || "选择媒体",
          button: { text: btn.getAttribute("data-btn-text") || "选择" },
          multiple: false
        });

        frame.on("select", function () {
          var attachment = frame.state().get("selection").first().toJSON();
          var targetId = btn.getAttribute("data-target");
          var targetInput = targetId ? document.querySelector(targetId) : null;

          if (targetInput) {
            targetInput.value = attachment.url;
            targetInput.dispatchEvent(new Event("change"));
          }

          var previewTarget = btn.getAttribute("data-preview");
          var preview = previewTarget ? document.querySelector(previewTarget) : null;
          if (preview) {
            var img = preview.querySelector("img");
            if (img) {
              img.src = attachment.url;
              img.style.display = "";
            } else {
              preview.innerHTML = '<img src="' + escapeHtml(attachment.url) + '" alt="preview">';
            }
          }
        });

        frame.open();
      });
    });

    var removeBtns = document.querySelectorAll(".bm-media-remove-btn");
    removeBtns.forEach(function (btn) {
      btn.addEventListener("click", function (e) {
        e.preventDefault();

        var targetId = btn.getAttribute("data-target");
        var targetInput = targetId ? document.querySelector(targetId) : null;
        if (targetInput) {
          targetInput.value = "";
          targetInput.dispatchEvent(new Event("change"));
        }

        var previewTarget = btn.getAttribute("data-preview");
        var preview = previewTarget ? document.querySelector(previewTarget) : null;
        if (preview) {
          preview.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>';
        }
      });
    });
  }

  function initColorPicker() {
    var pickers = document.querySelectorAll(".bm-color-picker");

    pickers.forEach(function (picker) {
      var inputId = picker.getAttribute("data-input");
      var input = inputId ? document.querySelector(inputId) : null;

      if (input) {
        picker.value = input.value || "#4f6ef7";

        picker.addEventListener("input", function () {
          input.value = picker.value;
        });

        input.addEventListener("input", function () {
          if (/^#[0-9a-fA-F]{6}$/.test(input.value)) {
            picker.value = input.value;
          }
        });
      }
    });
  }

  function initEngineManager() {
    var engineList = document.querySelector(".bm-engine-list");
    if (!engineList) return;

    var addBtn = document.querySelector(".bm-engine-add-btn");
    var modal = document.querySelector(".bm-modal-overlay");
    var modalForm = modal ? modal.querySelector("form") : null;

    initSortable(engineList);

    if (addBtn && modal) {
      addBtn.addEventListener("click", function () {
        if (modalForm) modalForm.reset();
        var editId = modalForm ? modalForm.querySelector('input[name="engine_id"]') : null;
        if (editId) editId.value = "";
        modal.classList.add("show");
      });
    }

    if (modal) {
      var cancelBtn = modal.querySelector(".bm-modal-btn--cancel");
      if (cancelBtn) {
        cancelBtn.addEventListener("click", function () {
          modal.classList.remove("show");
        });
      }

      modal.addEventListener("click", function (e) {
        if (e.target === modal) {
          modal.classList.remove("show");
        }
      });
    }

    if (modalForm) {
      modalForm.addEventListener("submit", function (e) {
        e.preventDefault();
        var formData = new FormData(modalForm);
        formData.append("action", "bm_save_engine");
        formData.append("nonce", adminNonce);

        fetch(adminAjaxUrl, {
          method: "POST",
          credentials: "same-origin",
          body: formData
        })
          .then(function (res) { return res.json(); })
          .then(function (data) {
            if (data.success) {
              location.reload();
            } else {
              showNotice("error", data.data || "保存失败");
            }
          })
          .catch(function () {
            showNotice("error", "请求失败，请重试");
          });
      });
    }

    engineList.addEventListener("click", function (e) {
      var editBtn = e.target.closest(".bm-btn-edit");
      var deleteBtn = e.target.closest(".bm-btn-delete");

      if (editBtn) {
        var item = editBtn.closest(".bm-engine-item");
        if (item && modal && modalForm) {
          var editId = modalForm.querySelector('input[name="engine_id"]');
          if (editId) editId.value = item.getAttribute("data-id") || "";

          var nameInput = modalForm.querySelector('input[name="engine_name"]');
          if (nameInput) nameInput.value = item.getAttribute("data-name") || "";

          var urlInput = modalForm.querySelector('input[name="engine_url"]');
          if (urlInput) urlInput.value = item.getAttribute("data-url") || "";

          var paramInput = modalForm.querySelector('input[name="engine_param"]');
          if (paramInput) paramInput.value = item.getAttribute("data-param") || "q";

          var suggestInput = modalForm.querySelector('input[name="engine_suggest"]');
          if (suggestInput) suggestInput.value = item.getAttribute("data-suggest") || "";

          var iconInput = modalForm.querySelector('input[name="engine_icon"]');
          if (iconInput) iconInput.value = item.getAttribute("data-icon") || "";

          modal.classList.add("show");
        }
      }

      if (deleteBtn) {
        var delItem = deleteBtn.closest(".bm-engine-item");
        if (delItem && confirm("确定删除该搜索引擎？")) {
          var engineId = delItem.getAttribute("data-id");
          var formData = new FormData();
          formData.append("action", "bm_delete_engine");
          formData.append("nonce", adminNonce);
          formData.append("engine_id", engineId);

          fetch(adminAjaxUrl, {
            method: "POST",
            credentials: "same-origin",
            body: formData
          })
            .then(function (res) { return res.json(); })
            .then(function (data) {
              if (data.success) {
                delItem.remove();
                showNotice("success", "已删除");
              } else {
                showNotice("error", data.data || "删除失败");
              }
            })
            .catch(function () {
              showNotice("error", "请求失败，请重试");
            });
        }
      }
    });
  }

  function initSortable(container) {
    var dragItem = null;
    var placeholder = document.createElement("div");
    placeholder.className = "bm-sortable-placeholder";
    placeholder.style.cssText = "height:4px;background:var(--bm-primary);border-radius:2px;margin:4px 0;";

    var handles = container.querySelectorAll(".bm-engine-item-drag");

    handles.forEach(function (handle) {
      handle.addEventListener("mousedown", function (e) {
        e.preventDefault();
        dragItem = handle.closest(".bm-engine-item");
        if (!dragItem) return;

        dragItem.style.opacity = "0.5";
        document.addEventListener("mousemove", onDragMove);
        document.addEventListener("mouseup", onDragEnd);
      });
    });

    function onDragMove(e) {
      if (!dragItem) return;

      var items = Array.from(container.querySelectorAll(".bm-engine-item:not([style*='opacity: 0.5'])"));
      var closestItem = null;
      var closestOffset = Number.POSITIVE_INFINITY;

      items.forEach(function (item) {
        var box = item.getBoundingClientRect();
        var offset = e.clientY - box.top - box.height / 2;
        if (offset < 0 && offset > -closestOffset) {
          closestOffset = -offset;
          closestItem = item;
        }
      });

      if (placeholder.parentNode) placeholder.remove();

      if (closestItem) {
        container.insertBefore(placeholder, closestItem);
      } else {
        container.appendChild(placeholder);
      }
    }

    function onDragEnd() {
      if (!dragItem) return;

      dragItem.style.opacity = "";
      if (placeholder.parentNode) {
        container.insertBefore(dragItem, placeholder);
        placeholder.remove();
      }

      saveEngineOrder();

      dragItem = null;
      document.removeEventListener("mousemove", onDragMove);
      document.removeEventListener("mouseup", onDragEnd);
    }

    function saveEngineOrder() {
      var items = container.querySelectorAll(".bm-engine-item");
      var order = [];
      items.forEach(function (item, index) {
        order.push({
          id: item.getAttribute("data-id"),
          sort: index
        });
      });

      var formData = new FormData();
      formData.append("action", "bm_sort_engines");
      formData.append("nonce", adminNonce);
      formData.append("order", JSON.stringify(order));

      fetch(adminAjaxUrl, {
        method: "POST",
        credentials: "same-origin",
        body: formData
      }).catch(function () {});
    }
  }

  function initFormSave() {
    var saveBtns = document.querySelectorAll(".bm-admin-save-btn");

    saveBtns.forEach(function (btn) {
      btn.addEventListener("click", function () {
        var form = btn.closest("form") || document.querySelector(".bm-admin-form");
        if (!form) return;

        var formData = new FormData(form);
        formData.append("action", "bm_save_settings");
        formData.append("nonce", adminNonce);

        btn.disabled = true;
        btn.textContent = "保存中...";

        fetch(adminAjaxUrl, {
          method: "POST",
          credentials: "same-origin",
          body: formData
        })
          .then(function (res) { return res.json(); })
          .then(function (data) {
            if (data.success) {
              showNotice("success", "设置已保存");
            } else {
              showNotice("error", data.data || "保存失败");
            }
          })
          .catch(function () {
            showNotice("error", "请求失败，请重试");
          })
          .finally(function () {
            btn.disabled = false;
            btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> 保存设置';
          });
      });
    });
  }

  function initImport() {
    var importArea = document.querySelector(".bm-import-area");
    var importInput = document.querySelector(".bm-import-input");

    if (!importArea || !importInput) return;

    importArea.addEventListener("click", function () {
      importInput.click();
    });

    importArea.addEventListener("dragover", function (e) {
      e.preventDefault();
      importArea.classList.add("bm-dragover");
    });

    importArea.addEventListener("dragleave", function () {
      importArea.classList.remove("bm-dragover");
    });

    importArea.addEventListener("drop", function (e) {
      e.preventDefault();
      importArea.classList.remove("bm-dragover");

      var file = e.dataTransfer.files[0];
      if (file) {
        processImportFile(file);
      }
    });

    importInput.addEventListener("change", function () {
      var file = importInput.files[0];
      if (file) {
        processImportFile(file);
      }
      importInput.value = "";
    });
  }

  function processImportFile(file) {
    if (!file.name.endsWith(".json") && !file.name.endsWith(".html")) {
      showNotice("error", "仅支持 JSON 或 HTML 格式的书签文件");
      return;
    }

    var reader = new FileReader();
    reader.onload = function (e) {
      var content = e.target.result;
      var formData = new FormData();
      formData.append("action", "bm_import_bookmarks");
      formData.append("nonce", adminNonce);
      formData.append("file_content", content);
      formData.append("file_name", file.name);

      showNotice("info", "正在导入，请稍候...");

      fetch(adminAjaxUrl, {
        method: "POST",
        credentials: "same-origin",
        body: formData
      })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (data.success) {
            showNotice("success", data.data || "导入成功");
            setTimeout(function () { location.reload(); }, 1500);
          } else {
            showNotice("error", data.data || "导入失败");
          }
        })
        .catch(function () {
          showNotice("error", "导入请求失败，请重试");
        });
    };
    reader.readAsText(file);
  }

  function showNotice(type, message) {
    var existing = document.querySelector(".bm-admin-notice");
    if (existing) existing.remove();

    var notice = document.createElement("div");
    notice.className = "bm-admin-notice bm-admin-notice--" + type;
    notice.innerHTML =
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">' +
      (type === "success"
        ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'
        : type === "error"
          ? '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>'
          : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>') +
      "</svg>" +
      "<span>" + escapeHtml(message) + "</span>";

    var header = document.querySelector(".bm-admin-header");
    if (header) {
      header.parentNode.insertBefore(notice, header.nextSibling);
    } else {
      document.body.prepend(notice);
    }

    setTimeout(function () {
      notice.style.opacity = "0";
      notice.style.transform = "translateY(-8px)";
      notice.style.transition = "all 0.3s ease";
      setTimeout(function () { notice.remove(); }, 300);
    }, 3000);
  }

  function escapeHtml(str) {
    var div = document.createElement("div");
    div.textContent = str;
    return div.innerHTML;
  }

  function init() {
    initMediaUpload();
    initColorPicker();
    initEngineManager();
    initFormSave();
    initImport();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
