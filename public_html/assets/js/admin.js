/* JSD Construction - admin portal interactions (vanilla JS).
   Tabs, drag-drop upload, caption edit, visibility toggle, delete, feature
   toggle and drag reorder. All POSTs send the CSRF token. */
(function () {
  'use strict';

  var CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

  function post(url, data) {
    var body;
    if (data instanceof FormData) {
      body = data;
      body.append('csrf_token', CSRF);
    } else {
      body = new URLSearchParams(data);
      body.append('csrf_token', CSRF);
    }
    return fetch(url, {
      method: 'POST',
      body: body,
      headers: { 'X-CSRF-Token': CSRF, 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (r) {
      return r.json().catch(function () { return { ok: false, message: 'Unexpected server response.' }; });
    });
  }

  /* ---------- Tabs ---------- */
  var tabs = document.querySelectorAll('.admin-tab');
  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      var key = tab.getAttribute('data-tab');
      tabs.forEach(function (t) {
        var on = t === tab;
        t.classList.toggle('is-active', on);
        t.setAttribute('aria-selected', String(on));
      });
      document.querySelectorAll('.admin-panel').forEach(function (p) {
        var on = p.id === 'panel-' + key;
        p.classList.toggle('is-active', on);
        p.hidden = !on;
      });
    });
  });

  /* ---------- Upload ---------- */
  document.querySelectorAll('[data-dropzone]').forEach(function (zone) {
    var category = zone.getAttribute('data-category');
    var input = zone.querySelector('[data-file-input]');
    var panel = zone.closest('.admin-panel');
    var feedback = panel.querySelector('[data-upload-feedback]');

    function show(msg, type) {
      feedback.hidden = false;
      feedback.textContent = msg;
      feedback.className = 'upload-feedback is-' + type;
    }

    function upload(fileList) {
      if (!fileList || !fileList.length) return;
      var fd = new FormData();
      fd.append('category', category);
      for (var i = 0; i < fileList.length; i++) {
        fd.append('files[]', fileList[i]);
      }
      show('Uploading ' + fileList.length + ' file(s)...', 'busy');
      post('upload.php', fd).then(function (res) {
        if (res.ok) {
          var n = (res.added || []).length;
          show(n + ' uploaded. Refreshing...', 'success');
          if (res.errors && res.errors.length) {
            show(n + ' uploaded. Some files were skipped: ' + res.errors.join(' '), 'error');
          }
          setTimeout(function () { window.location.reload(); }, 700);
        } else {
          show(res.message || 'Upload failed.', 'error');
        }
      }).catch(function () { show('Network error during upload.', 'error'); });
    }

    input.addEventListener('change', function () { upload(input.files); });
    ['dragenter', 'dragover'].forEach(function (ev) {
      zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.add('is-drag'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
      zone.addEventListener(ev, function (e) { e.preventDefault(); if (ev !== 'dragleave' || e.target === zone) zone.classList.remove('is-drag'); });
    });
    zone.addEventListener('drop', function (e) {
      if (e.dataTransfer && e.dataTransfer.files) upload(e.dataTransfer.files);
    });
  });

  /* ---------- Card actions (event delegation) ---------- */
  var main = document.querySelector('.admin-main');
  if (!main) return;

  function cardId(el) { var c = el.closest('.media-card'); return c ? c.getAttribute('data-id') : null; }

  main.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-action]');
    if (!btn) return;
    var action = btn.getAttribute('data-action');
    var card = btn.closest('.media-card');
    var id = card && card.getAttribute('data-id');
    if (!id) return;

    if (action === 'toggle') {
      post('media-crud.php', { action: 'toggle_active', id: id }).then(function (res) {
        if (res.ok) {
          var on = res.is_active === 1;
          btn.setAttribute('aria-pressed', String(on));
          btn.textContent = on ? 'Visible' : 'Hidden';
          card.classList.toggle('is-hidden', !on);
        } else { alert(res.message || 'Could not update.'); }
      });
    } else if (action === 'delete') {
      if (!confirm('Delete this image permanently? This cannot be undone.')) return;
      post('media-crud.php', { action: 'delete', id: id }).then(function (res) {
        if (res.ok) { card.remove(); } else { alert(res.message || 'Could not delete.'); }
      });
    } else if (action === 'feature') {
      post('media-crud.php', { action: 'toggle_featured', id: id }).then(function (res) {
        if (res.ok) {
          var on = res.is_featured === 1;
          btn.classList.toggle('is-on', on);
          btn.setAttribute('aria-pressed', String(on));
        } else { alert(res.message || 'Could not update.'); }
      });
    }
  });

  // Caption save on blur / Enter.
  main.addEventListener('blur', function (e) {
    if (!e.target.matches('[data-action="caption"]')) return;
    var id = cardId(e.target);
    if (!id) return;
    post('media-crud.php', { action: 'caption', id: id, caption: e.target.value }).then(function (res) {
      if (!res.ok) alert(res.message || 'Could not save caption.');
    });
  }, true);
  main.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && e.target.matches('[data-action="caption"]')) { e.preventDefault(); e.target.blur(); }
  });

  /* ---------- Drag reorder ---------- */
  document.querySelectorAll('[data-media-grid]').forEach(function (grid) {
    var dragEl = null;
    grid.addEventListener('dragstart', function (e) {
      var card = e.target.closest('.media-card');
      if (!card) return;
      dragEl = card;
      card.classList.add('dragging');
      e.dataTransfer.effectAllowed = 'move';
    });
    grid.addEventListener('dragend', function () {
      if (dragEl) dragEl.classList.remove('dragging');
      grid.querySelectorAll('.drag-over').forEach(function (c) { c.classList.remove('drag-over'); });
      dragEl = null;
    });
    grid.addEventListener('dragover', function (e) {
      e.preventDefault();
      var target = e.target.closest('.media-card');
      if (!target || target === dragEl) return;
      var rect = target.getBoundingClientRect();
      var after = (e.clientY - rect.top) > rect.height / 2;
      grid.insertBefore(dragEl, after ? target.nextSibling : target);
    });
    grid.addEventListener('drop', function (e) {
      e.preventDefault();
      var ids = Array.prototype.map.call(grid.querySelectorAll('.media-card'), function (c) { return c.getAttribute('data-id'); });
      if (!ids.length) return;
      post('media-crud.php', { action: 'reorder', category: grid.getAttribute('data-category'), order: ids.join(',') }).then(function (res) {
        if (!res.ok) alert(res.message || 'Could not save order.');
      });
    });
  });
})();
