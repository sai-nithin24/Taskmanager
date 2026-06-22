/**
 * TaskBoard · app.js
 * ============================================================
 * Modules (IIFE pattern, no bundler needed):
 *   Api      — fetch wrapper for /src/api/tasks.php
 *   Validate — client-side rules (mirrors server-side)
 *   Toast    — notification system
 *   Clock    — live header clock
 *   Stats    — SVG ring + counters
 *   Tasks    — render task list, handle interactions
 *   Form     — add-task form with live validation
 *   App      — bootstrap & global state
 * ============================================================
 */

'use strict';

/* ── Helpers ──────────────────────────────────────────────── */
function escHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

/* ── Api ──────────────────────────────────────────────────── */
const Api = (() => {
  const BASE = '/taskboard/src/api/tasks.php';

  async function request(method, body, params) {
    params = params || '';
    const opts = { method, headers: { 'Content-Type': 'application/json' } };
    if (body !== null && body !== undefined) {
      opts.body = JSON.stringify(body);
    }

    let res;
    try {
      res = await fetch(BASE + params, opts);
    } catch (networkErr) {
      throw new Error('Network error — check your connection.');
    }

    let json;
    try {
      json = await res.json();
    } catch (_) {
      throw new Error('Server returned invalid JSON (HTTP ' + res.status + ').');
    }

    if (!json.success) {
      throw new Error(json.message || 'Request failed.');
    }
    return json.data;
  }

  return {
    getTasks : function(filter) { return request('GET',  null, '?filter=' + (filter || 'all')); },
    getStats : function()       { return request('GET',  null, '?stats=1'); },
    create   : function(title, priority) { return request('POST',   { title: title, priority: priority }); },
    setStatus: function(id, status)      { return request('PATCH',  { id: id, status: status }); },
    remove   : function(id)              { return request('DELETE', { id: id }); },
  };
})();

/* ── Validate ─────────────────────────────────────────────── */
const Validate = (() => {
  var MIN = 3;
  var MAX = 120;

  function task(title) {
    var t = title.trim();
    if (!t)            return 'Task name is required.';
    if (t.length < MIN) return 'At least ' + MIN + ' characters needed.';
    if (t.length > MAX) return 'Keep it under ' + MAX + ' characters.';
    if (/^[^a-zA-Z0-9]/.test(t)) return 'Start with a letter or number.';
    return null;
  }

  return { task: task, MIN: MIN, MAX: MAX };
})();

/* ── Toast ────────────────────────────────────────────────── */
const Toast = (() => {
  var timer = null;

  function getEl() {
    var el = document.getElementById('toast');
    if (!el) {
      el = document.createElement('div');
      el.id = 'toast';
      document.body.appendChild(el);
    }
    return el;
  }

  function hide() {
    var el = document.getElementById('toast');
    if (el) el.classList.add('hidden');
  }

  function show(msg, type) {
    type = type || 'ok';
    var el = getEl();
    clearTimeout(timer);
    el.className = 'toast ' + type;

    // Build DOM safely — never use innerHTML with user/server content
    el.textContent = '';

    var dot = document.createElement('span');
    dot.className = 'toast__dot';

    var text = document.createElement('span');
    text.textContent = msg;               // safe — no innerHTML

    var close = document.createElement('button');
    close.className = 'toast__close';
    close.setAttribute('aria-label', 'Close');
    close.textContent = '×';
    close.addEventListener('click', hide);

    el.appendChild(dot);
    el.appendChild(text);
    el.appendChild(close);

    timer = setTimeout(hide, 3200);
  }

  return { show: show };
})();

/* ── Clock ────────────────────────────────────────────────── */
const Clock = (() => {
  function start(el) {
    function tick() {
      el.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    tick();
    setInterval(tick, 1000);
  }
  return { start: start };
})();

/* ── Stats ────────────────────────────────────────────────── */
const Stats = (() => {
  var CIRC = 2 * Math.PI * 22;  // circumference for r=22

  function setText(id, val) {
    var el = document.getElementById(id);
    if (el) el.textContent = val;
  }

  function update(data) {
    var total     = parseInt(data.total,     10) || 0;
    var pending   = parseInt(data.pending,   10) || 0;
    var completed = parseInt(data.completed, 10) || 0;
    var pct       = parseInt(data.pct,       10) || 0;

    // Stat bar
    setText('stat-total',     total);
    setText('stat-pending',   pending);
    setText('stat-completed', completed);
    setText('stat-pct',       pct + '%');

    // Filter badges
    setText('badge-all',       total);
    setText('badge-pending',   pending);
    setText('badge-completed', completed);

    // SVG ring
    var circle = document.getElementById('ring-fill');
    if (circle) {
      circle.style.strokeDashoffset = CIRC * (1 - pct / 100);
      circle.style.stroke = (pct === 100) ? 'var(--done)' : 'var(--accent)';
    }

    // Percentage text colour
    var pctEl = document.getElementById('stat-pct');
    if (pctEl) {
      pctEl.style.color = (pct === 100) ? 'var(--done)' : 'var(--accent)';
    }
  }

  return { update: update };
})();

/* ── Tasks ────────────────────────────────────────────────── */
const Tasks = (() => {
  var PRIORITY = {
    high:   { label: 'High', cssClass: 'high'   },
    medium: { label: 'Med',  cssClass: 'medium' },
    low:    { label: 'Low',  cssClass: 'low'    },
  };

  function timeAgo(dateStr) {
    var diff = Date.now() - new Date(dateStr).getTime();
    if (diff < 60000)    return 'just now';
    if (diff < 3600000)  return Math.floor(diff / 60000)   + 'm ago';
    if (diff < 86400000) return Math.floor(diff / 3600000) + 'h ago';
    return Math.floor(diff / 86400000) + 'd ago';
  }

  function createCard(task) {
    var done = task.status === 'completed';
    var pm   = PRIORITY[task.priority] || PRIORITY.medium;

    // ── Wrapper ──────────────────────────────────────────────
    var card = document.createElement('div');
    card.className   = 'task-card ' + (done ? 'done' : 'pending');
    card.dataset.id  = task.id;

    // ── Heat bar ─────────────────────────────────────────────
    var heat = document.createElement('div');
    heat.className = 'task-card__heat ' + (done ? 'done' : pm.cssClass);

    // ── Checkbox button ──────────────────────────────────────
    var check = document.createElement('button');
    check.className  = 'task-card__check' + (done ? ' checked' : '');
    check.setAttribute('aria-label', done ? 'Reopen task' : 'Complete task');
    check.setAttribute('data-action', done ? 'reopen' : 'complete');
    check.setAttribute('type', 'button');
    check.textContent = done ? '✓' : '';

    // ── Content ──────────────────────────────────────────────
    var content = document.createElement('div');
    content.className = 'task-card__content';

    var titleEl = document.createElement('div');
    titleEl.className   = 'task-card__title' + (done ? ' done-text' : '');
    titleEl.textContent = task.title;          // safe textContent

    var meta = document.createElement('div');
    meta.className = 'task-card__meta';

    var priorityEl = document.createElement('span');
    priorityEl.className   = 'task-card__priority ' + (done ? 'done' : pm.cssClass);
    priorityEl.textContent = pm.label;

    var dot1 = document.createElement('span');
    dot1.className = 'task-card__dot';

    var timeEl = document.createElement('span');
    timeEl.className   = 'task-card__time';
    timeEl.textContent = timeAgo(task.created_at);

    meta.appendChild(priorityEl);
    meta.appendChild(dot1);
    meta.appendChild(timeEl);

    if (done) {
      var dot2 = document.createElement('span');
      dot2.className = 'task-card__dot';
      var badge = document.createElement('span');
      badge.className   = 'task-card__done-badge';
      badge.textContent = 'Done';
      meta.appendChild(dot2);
      meta.appendChild(badge);
    }

    content.appendChild(titleEl);
    content.appendChild(meta);

    // ── Actions ──────────────────────────────────────────────
    var actions = document.createElement('div');
    actions.className = 'task-card__actions';

    var toggleBtn = document.createElement('button');
    toggleBtn.setAttribute('type', 'button');
    toggleBtn.setAttribute('data-action', done ? 'reopen' : 'complete');
    toggleBtn.className   = 'btn-action ' + (done ? 'btn-reopen' : 'btn-done');
    toggleBtn.textContent = done ? 'Reopen' : 'Done';

    var delBtn = document.createElement('button');
    delBtn.setAttribute('type', 'button');
    delBtn.setAttribute('data-action', 'delete');
    delBtn.setAttribute('aria-label', 'Delete task');
    delBtn.className   = 'btn-action btn-delete';
    delBtn.textContent = '✕';

    actions.appendChild(toggleBtn);
    actions.appendChild(delBtn);

    // ── Assemble ─────────────────────────────────────────────
    card.appendChild(heat);
    card.appendChild(check);
    card.appendChild(content);
    card.appendChild(actions);

    return card;
  }

  function render(list, tasks) {
    list.innerHTML = '';

    if (!tasks || !tasks.length) {
      var empty = document.createElement('div');
      empty.className = 'empty';
      empty.innerHTML = '<div class="empty__icon">◎</div><div class="empty__text">No tasks here yet.</div>';
      list.appendChild(empty);
      return;
    }

    tasks.forEach(function(t) { list.appendChild(createCard(t)); });
  }

  return { render: render };
})();

/* ── Form ─────────────────────────────────────────────────── */
const Form = (() => {
  var touched = false;

  function init(onSubmit) {
    var card    = document.getElementById('form-card');
    var input   = document.getElementById('task-input');
    var errEl   = document.getElementById('input-err');
    var countEl = document.getElementById('input-count');
    var pills   = document.querySelectorAll('.priority-pill');
    var btn     = document.getElementById('btn-add');

    var selectedPriority = 'medium';

    // ── Declare helpers FIRST — before any addEventListener ──

    function updateCount() {
      var left = Validate.MAX - input.value.trim().length;
      countEl.textContent = left + ' left';
      countEl.classList.toggle('over', left < 0);
    }

    function liveValidate() {
      var err  = Validate.task(input.value);
      var left = Validate.MAX - input.value.trim().length;
      input.classList.toggle('error',   !!err);
      input.classList.toggle('success', !err && input.value.trim().length >= Validate.MIN);

      if (err) {
        errEl.textContent = err;
        errEl.className   = 'task-input__err';
      } else if (input.value.trim()) {
        errEl.textContent = '✓ Looks good';
        errEl.className   = 'task-input__err task-input__ok';
      } else {
        errEl.textContent = '';
        errEl.className   = 'task-input__err';
      }

      btn.disabled = (left < 0);
    }

    function submit() {
      touched = true;
      var err = Validate.task(input.value);
      if (err) {
        input.classList.add('error');
        errEl.textContent = err;
        errEl.className   = 'task-input__err';
        input.focus();
        return;
      }

      btn.disabled = true;
      onSubmit(input.value.trim(), selectedPriority, function reset() {
        input.value       = '';
        errEl.textContent = '';
        input.className   = 'task-input';
        countEl.textContent = Validate.MAX + ' left';
        touched           = false;
        btn.disabled      = false;
      });
    }

    // ── Priority pills ────────────────────────────────────────
    pills.forEach(function(pill) {
      pill.addEventListener('click', function() {
        pills.forEach(function(p) {
          p.className = 'priority-pill';
          p.setAttribute('aria-pressed', 'false');
        });
        pill.className = 'priority-pill active-' + pill.dataset.priority;
        pill.setAttribute('aria-pressed', 'true');
        selectedPriority = pill.dataset.priority;
      });
    });

    // ── Focus ring ────────────────────────────────────────────
    input.addEventListener('focus', function() { card.classList.add('focused'); });
    input.addEventListener('blur',  function() {
      card.classList.remove('focused');
      touched = true;
      liveValidate();
    });

    // ── Live validation on input ──────────────────────────────
    input.addEventListener('input', function() {
      updateCount();
      if (touched) liveValidate();
    });

    // ── Submit via Enter key or button ────────────────────────
    input.addEventListener('keydown', function(e) { if (e.key === 'Enter') submit(); });
    btn.addEventListener('click', submit);
  }

  return { init: init };
})();

/* ── App ──────────────────────────────────────────────────── */
const App = (() => {
  var currentFilter = 'all';

  async function loadAll() {
    var list = document.getElementById('task-list');
    list.innerHTML = '<div class="spinner"></div>';

    try {
      var results = await Promise.all([
        Api.getTasks(currentFilter),
        Api.getStats(),
      ]);
      var tasks = results[0];
      var stats = results[1];
      Tasks.render(list, tasks);
      Stats.update(stats);
    } catch (err) {
      list.innerHTML = '';
      var empty = document.createElement('div');
      empty.className = 'empty';
      var icon = document.createElement('div');
      icon.className   = 'empty__icon';
      icon.textContent = '⚠';
      var text = document.createElement('div');
      text.className   = 'empty__text';
      text.textContent = err.message;   // safe — textContent
      empty.appendChild(icon);
      empty.appendChild(text);
      list.appendChild(empty);
    }
  }

  function setFilter(f) {
    currentFilter = f;
    document.querySelectorAll('.filter-btn').forEach(function(btn) {
      btn.classList.toggle('active', btn.dataset.filter === f);
    });
    loadAll();
  }

  async function handleCardAction(e) {
    var actionEl = e.target.closest('[data-action]');
    if (!actionEl) return;

    var card = actionEl.closest('.task-card');
    if (!card) return;

    var id  = parseInt(card.dataset.id, 10);
    var act = actionEl.dataset.action;

    // Disable all action buttons on this card to prevent double-clicks
    card.querySelectorAll('[data-action]').forEach(function(b) { b.disabled = true; });

    try {
      if (act === 'complete') {
        await Api.setStatus(id, 'completed');
        Toast.show('Marked complete.', 'ok');
      } else if (act === 'reopen') {
        await Api.setStatus(id, 'pending');
        Toast.show('Moved to pending.', 'info');
      } else if (act === 'delete') {
        await Api.remove(id);
        Toast.show('Task removed.', 'warn');
      }
      await loadAll();
    } catch (err) {
      Toast.show(err.message, 'warn');
      // Re-enable buttons on failure
      card.querySelectorAll('[data-action]').forEach(function(b) { b.disabled = false; });
    }
  }

  function init() {
    // Clock
    var clockEl = document.getElementById('clock');
    if (clockEl) Clock.start(clockEl);

    // Filter buttons
    document.querySelectorAll('.filter-btn').forEach(function(btn) {
      btn.addEventListener('click', function() { setFilter(btn.dataset.filter); });
    });

    // Task list — event delegation
    var list = document.getElementById('task-list');
    list.addEventListener('click', handleCardAction);

    // Add-task form
    Form.init(async function(title, priority, reset) {
      try {
        await Api.create(title, priority);
        Toast.show('Task added!', 'ok');
        reset();
        await loadAll();
      } catch (err) {
        Toast.show(err.message, 'warn');
        reset();   // re-enable the button even on error
      }
    });

    // Initial data load
    loadAll();
  }

  return { init: init };
})();

document.addEventListener('DOMContentLoaded', function() { App.init(); });
