/**
 * DbM DataTables PHP, file: dbm-datatable.js
 * 
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 * 
 * Manually refreshing the table and programmer's helper: refresh datatable after update
 * Usage: window.__DBM_DT_API__.refresh(document.querySelector('.datatableContainer'));
 */

(function () {
  'use strict';

  const NS = '__DBM_DT_API__';

  const DBM = window[NS] || (window[NS] = {
    wired: false,
    latestReqId: 0,
    aborter: null,
  });

  const DBM_CONFIG = window.DBM_CONFIG || { i18n: {}, paths: {} };

  const SPECIAL_CELLS = {
    cell_action: (row, col) => renderActionsHtml(col.tag_options || {}, row),
    cell_change_status: (row, col) => renderStatusHtml(row, col.field || 'status'),
    cell_image: (row, col) => renderImageHtml(row[col.field], col.tag_options || {}, row),
  };

  const CELL_TEXT_LIMIT = 30;

  // --- Public init ---
  document.addEventListener('DOMContentLoaded', () => {
    wireGlobalOnce();

    document.querySelectorAll('.datatableContainer').forEach(container => {
      const mode = (container.getAttribute('data-dt-mode') || '').toUpperCase();
      if (mode !== 'API') return;

      // initial load
      loadDataTable(container, {
        page: 1,
        per_page: getPerPage(container),
        ...getFilters(container),
        query: getQuery(container),
      });

      initTooltips(container);
    });
  });

  // --- Wire global delegated events once ---
  function wireGlobalOnce() {
    if (DBM.wired) return;
    DBM.wired = true;

    // per_page change
    document.addEventListener('change', (e) => {
      const sel = e.target && e.target.id === 'dtPerPage' ? e.target : null;
      if (!sel) return;
      const container = sel.closest('.datatableContainer');
      if (!container) return;

      let perPageValue = sel.value;
      if (perPageValue.includes('=')) {
        const urlParams = new URLSearchParams(perPageValue);
        perPageValue = urlParams.get('per_page') || perPageValue;
      }

      loadDataTable(container, {
        page: 1,
        per_page: perPageValue,
        ...getFilters(container),
        query: getQuery(container),
      });
    });

    // submit filters form
    document.addEventListener('submit', (e) => {
      const form = e.target;
      if (!form || form.id !== 'dtFilters') return;
      const container = form.closest('.datatableContainer');
      if (!container) return;
      e.preventDefault();

      loadDataTable(container, {
        page: 1,
        per_page: getPerPage(container),
        ...getFilters(container),
        query: getQuery(container),
      });
    });

    // reset filters
    document.addEventListener('click', (e) => {
      const reset = e.target.closest && e.target.closest('#dtResetFilters');
      if (!reset) return;
      const container = reset.closest('.datatableContainer');
      if (!container) return;

      e.preventDefault();
      const form = container.querySelector('#dtFilters');
      if (form) form.reset();
      const searchInput = container.querySelector('#dtSearch');
      if (searchInput) searchInput.value = '';

      loadDataTable(container, { page: 1, per_page: getPerPage(container) });
    });

    // search enter
    document.addEventListener('keyup', (e) => {
      const input = e.target && e.target.id === 'dtSearch' ? e.target : null;
      if (!input || e.key !== 'Enter') return;
      const container = input.closest('.datatableContainer');
      if (!container) return;

      loadDataTable(container, {
        page: 1,
        per_page: getPerPage(container),
        ...getFilters(container),
        query: input.value.trim(),
      });
    });

    // forms with dtSearch (search button)
    document.addEventListener('submit', (e) => {
      const form = e.target;
      if (!form || !form.querySelector || !form.querySelector('#dtSearch')) return;
      if (form.id === 'dtFilters') return;
      const container = form.closest('.datatableContainer');
      if (!container) return;
      e.preventDefault();

      loadDataTable(container, {
        page: 1,
        per_page: getPerPage(container),
        ...getFilters(container),
        query: getQuery(container),
      });
    });

    // pagination click
    document.addEventListener('click', (e) => {
      const a = e.target.closest && e.target.closest('#dtPagination a');
      if (!a) return;
      const container = a.closest('.datatableContainer');
      if (!container) return;
      e.preventDefault();

      const url = new URL(a.href, window.location.origin);
      const params = Object.fromEntries(url.searchParams.entries());

      loadDataTable(container, { ...params, ...getFilters(container), query: getQuery(container), per_page: getPerPage(container) });
    });

    // sorting (click on thead links)
    document.addEventListener('click', (e) => {
      const a = e.target.closest && e.target.closest('#dtHead a');
      if (!a) return;
      const container = a.closest('.datatableContainer');
      if (!container) return;
      e.preventDefault();
      const url = new URL(a.href, window.location.origin);
      const params = Object.fromEntries(url.searchParams.entries());

      loadDataTable(container, { ...params, per_page: getPerPage(container), ...getFilters(container), query: getQuery(container) });
    });

    // delegated action handlers (delete etc.)
    document.addEventListener('click', (e) => {
      const btn = e.target.closest && e.target.closest('[data-dt-action]');
      if (!btn) return;
      const container = btn.closest('.datatableContainer');
      if (!container) return;
      e.preventDefault();

      const action = btn.getAttribute('data-dt-action');
      const id = btn.getAttribute('data-id');

      // custom events: you can extend this to do AJAX deletes etc.
      container.dispatchEvent(new CustomEvent('dt:action', { detail: { action, id, button: btn } }));
    });
  }

  // --- Core: load data ---
  function loadDataTable(container, params = {}) {
    if (!container) return;

    // Base URL from container attribute
    const dtUrl = container.getAttribute('data-dt-url') || '/';
    const dtHead = container.querySelector('#dtHead');
    const dtBody = container.querySelector('#dtBody');
    const dtInfo = container.querySelector('#dtInfo');

    // Abort previous
    if (DBM.aborter) { try { DBM.aborter.abort(); } catch (e) {} }
    DBM.aborter = ('AbortController' in window) ? new AbortController() : null;
    const signal = DBM.aborter ? DBM.aborter.signal : undefined;
    const reqId = ++DBM.latestReqId;
    const url = buildUrl(dtUrl, params);

    fetch(url, { signal, headers: { 'Accept': 'application/json' } })
      .then(resp => {
        if (!resp.ok) throw new Error('Network response was not ok');
        return resp.json();
      })
      .then(data => {
        if (reqId !== DBM.latestReqId) return;
        if (!data || !data.success) {
          console.error('Error in API response:', data && data.message ? data.message : data);
          return;
        }

        // Render
        if (dtHead && data.columns) renderHead(dtHead, data.columns, container, data.sider);
        if (dtBody && data.rows) renderBody(dtBody, data.rows, data.columns, data.customRows || []);
        if (dtInfo && data.sider) renderInfo(dtInfo, data.sider);

        // Availability filters, buttons, pagination
        if (data.filters) renderFilters(container, data.filters);
        if (data.buttons) renderButtons(container, data.buttons);
        if (data.sider) renderPagination(container, data.sider, dtUrl, params);

        // Initializing tooltips
        initTooltips(container);

        // Preserving the state of filters and search engines
        if (data.filters) {
          renderFilters(container, data.filters);

          for (const [key, val] of Object.entries(params)) {
            if (key.startsWith('filter_')) {
              const sel = container.querySelector(`select[name="${key}"]`);
              if (sel) sel.value = val;
            }
          }
        }

        const searchInput = container.querySelector('#dtSearch');
        if (searchInput && params.query) {
          searchInput.value = params.query;
        }
      })
      .catch(err => {
        if (err && err.name === 'AbortError') return;
        console.error('AJAX error', err);
      });
  }

  // --- Rendering helpers - Rendering should cover all backend functions ---
  function renderHead(dtHead, columns, container, sider) {
    const tr = document.createElement('tr');
    columns.forEach(col => {
      const th = document.createElement('th');
      if (col.class) th.className = col.class;
      th.setAttribute('scope', 'col');

      const name = col.field || '';
      const label = col.label || (name.charAt(0).toUpperCase() + name.slice(1));

      if (col.sortable) {
        const a = document.createElement('a');
        a.href = buildUrl(container.getAttribute('data-dt-url') || '/', { q: '', per_page: sider.perPage, sort: col.field, dir: toggleDir(sider, col.field), page: 1 });
        a.className = 'text-decoration-none d-block link-dark';
        a.innerHTML = `${escapeHtml(label)} <i class="bi bi-arrow-down-up text-black-50 ms-1"></i>`;
        th.appendChild(a);
      } else {
        th.textContent = label;
      }

      tr.appendChild(th);
    });

    dtHead.innerHTML = '';
    dtHead.appendChild(tr);
  }

  function renderBody(dtBody, rows, columns, customRows = {}) {
    const frag = document.createDocumentFragment();
    const columnsCount = Math.max(1, (columns && columns.length) ? columns.length : 1);

    console.log(customRows);

    const customMap = {};
    for (const [pos, cfg] of Object.entries(customRows)) {
      const num = Number(pos);
      if (!customMap[num]) customMap[num] = [];
      if (Array.isArray(cfg)) {
        customMap[num].push(...cfg);
      } else {
        customMap[num].push(cfg);
      }
    }

    if (!rows || rows.length === 0) {
      const tr = document.createElement('tr');
      const td = document.createElement('td');
      td.colSpan = columnsCount;
      td.className = 'text-center small';
      td.textContent = trans('no_results');
      tr.appendChild(td);
      frag.appendChild(tr);
      dtBody.innerHTML = '';
      dtBody.appendChild(frag);
      return;
    }

    const appendCustom = (cfg) => {
      const tpl = document.createElement('template');
      tpl.innerHTML = String(renderCustomRow(cfg, columnsCount)).trim();
      const node = tpl.content.firstElementChild;
      if (node) frag.appendChild(node);
    };

    // przed pierwszym rekordem
    if (customMap[0]) customMap[0].forEach(appendCustom);

    rows.forEach((row, i) => {
      const tr = document.createElement('tr');

      columns.forEach(col => {
        const td = document.createElement('td');
        if (col.class) td.className = col.class;

        const field = col.field || '';
        const tag = col.tag || null;

        if (tag && SPECIAL_CELLS && SPECIAL_CELLS[tag]) {
          td.innerHTML = SPECIAL_CELLS[tag](row, col);
        } else if (field) {
          const val = row[field];
          if (val !== null && val !== undefined && val !== '') {
            if (tag || (typeof val === 'string' && val.includes('<'))) {
              td.innerHTML = String(val);
            } else {
              td.textContent = safeTruncate(String(val), CELL_TEXT_LIMIT);
            }
          } else {
            td.innerHTML = renderEmptyHtml();
          }
        } else {
          td.textContent = '';
        }

        tr.appendChild(td);
      });

      frag.appendChild(tr);

      // po bieżącym wierszu
      if (customMap[i + 1]) customMap[i + 1].forEach(appendCustom);
    });

    // po wszystkich wierszach
    const total = rows.length;
    Object.keys(customMap).map(Number).sort((a, b) => a - b).forEach(pos => {
      if (pos > total) {
        customMap[pos].forEach(appendCustom);
      }
    });

    dtBody.innerHTML = '';
    dtBody.appendChild(frag);
  }

  function renderImageHtml(filename, options = {}) {
    const srcDir = (options.src_dir || './') .replace(/\/$/, '') + '/';
    const placeholder = (options.placeholder || DBM_CONFIG.paths.placeholder);
    const src = filename ? (srcDir + filename) : placeholder;

    const alt = options.alt_field ? (options.alt_field in window ? window[options.alt_field] : 'Obraz') : 'Obraz';
    const width = options.width || 20;

    const titleImg = `<img src="${escapeAttr(src)}" class='img-fluid' alt='${escapeAttr(alt)}'>`;

    return `\n<p class="m-0" data-bs-toggle="tooltip" data-bs-html="true" title="${escapeAttr(titleImg)}">\n    <img src="${escapeAttr(src)}" class="img-fluid" alt="${escapeAttr(alt)}" style="height:${width}px;">\n</p>\n`;
  }

  function renderStatusHtml(row = {}, field = 'status') {
    const val = row?.[field];
    const status = (val !== undefined && val !== null && val !== '')
      ? String(val).toLowerCase()
      : 'unknown';

    const mapClass = {
      active: 'success',
      inactive: 'danger',
      new: 'warning',
    };
    const mapLabel = {
      active: 'active',
      inactive: 'inactive',
      new: 'new',
      unknown: 'unknown',
    };

    const cls = mapClass[status] || 'secondary';
    const labelKey = mapLabel[status] || 'unknown';
    const extraClass = status === 'new' ? ' text-dark' : '';

    return `<span class="badge bg-${cls}${extraClass}">${escapeHtml(trans(labelKey))}</span>`;
  }

  function renderActionsHtml(tagOptions = {}, row = {}) {
    const actions = tagOptions.actions || [];
    if (!actions.length) return '';

    let html = '<div class="dropdown">\n';
    html += '  <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">';
    html += '<i class="bi bi-three-dots-vertical"></i></button>\n';
    html += '  <ul class="dropdown-menu">\n';

    actions.forEach(act => {
      if (act.type === 'link') {
        let url = act.url || '#';
        Object.keys(row).forEach(k => {
          url = url.replace(new RegExp('\\{' + k + '\\}', 'g'), encodeURIComponent(row[k] ?? ''));
        });
        html += `    <li><a href="${escapeAttr(url)}" class="dropdown-item ${escapeAttr(act.class || '')}"><i class="${escapeAttr(act.icon || '')} me-2"></i>${escapeHtml(act.label || '')}</a></li>\n`;
      } else if (act.type === 'button') {
        const attrs = act.attrs || {};
        let dataAttrs = '';

        Object.entries(attrs).forEach(([k, v]) => {
          // podstaw placeholdery {field}
          Object.keys(row).forEach(rk => {
            v = String(v).replace(new RegExp('\\{' + rk + '\\}', 'g'), row[rk] ?? '');
          });
          dataAttrs += ` ${escapeAttr(k)}="${escapeAttr(v)}"`;
        });

        // wybierz nazwę akcji
        const actionName = act.action || act.type;

        html += `    <li><button type="button" class="dropdown-item ${escapeAttr(act.class || '')}" data-dt-action="${escapeAttr(actionName)}"${dataAttrs}><i class="${escapeAttr(act.icon || '')} me-2"></i>${escapeHtml(act.label || '')}</button></li>\n`;
      }
    });

    html += '  </ul>\n</div>\n';
    return html;
  }

  function renderFilters(container, filters) {
    const form = container.querySelector('#dtFilters');
    if (!form) return;

    // for each filter select in filters object, build options
    Object.entries(filters).forEach(([name, cfg]) => {
      const select = form.querySelector(`select[name=filter_${name}]`);
      if (!select) return;
      // clear
      select.innerHTML = '';
      // add placeholder
      const placeholder = document.createElement('option');
      placeholder.value = '';
      placeholder.textContent = `- ${cfg.label || name} -`;
      select.appendChild(placeholder);

      (cfg.options || []).forEach(opt => {
        const o = document.createElement('option');
        o.value = opt.value;
        o.textContent = opt.label;
        select.appendChild(o);
      });
    });
  }

  function renderButtons(container, buttons) {
    const holder = container.querySelector('#dtButtons');
    if (!holder) return;
    holder.innerHTML = '';

    buttons.forEach(act => {
      const a = document.createElement('a');
      a.href = act.url || '#';
      a.className = act.class || 'btn btn-sm btn-outline-secondary';
      a.innerHTML = (act.icon ? `<i class="${act.icon}"></i>` : '');
      holder.appendChild(a);
    });
  }

  function renderInfo(dtInfo, sider) {
    const from = (sider.page - 1) * sider.perPage + 1;
    const to = Math.min(sider.total, sider.page * sider.perPage);
    const total = sider.total;

    dtInfo.innerHTML = '<span class="small">' + vsprintf(trans('records_info'), [from, to, total]) + '</span>';
  }

  function renderPagination(container, sider, baseUrl, extraParams = {}) {
    const dtPagination = container.querySelector('#dtPagination');
    if (!dtPagination) return;

    const current = parseInt(sider.page || 1, 10);
    const perPage = parseInt(sider.perPage || 20, 10);
    const total = parseInt(sider.total || 0, 10);
    const pages = Math.ceil(total / perPage);

    if (pages <= 1) {
      dtPagination.innerHTML = '<!-- Pagination -->';
      return;
    }

    const buttons = paginationButtons(current, pages, 2);

    let html = '\n    <ul class="pagination pagination-sm m-0">\n';

    // Prev
    const prevDisabled = current <= 1 ? ' disabled' : '';
    html += `        <li class="page-item${prevDisabled}"><a class="page-link" href="${buildUrl(baseUrl, { ...extraParams, page: current - 1 })}" aria-label="${trans('previous')}">&lsaquo;</a></li>\n`;

    // Buttons
    for (const b of buttons) {
      if (b === '...') {
        html += '        <li class="page-item disabled"><span class="page-link">...</span></li>\n';
        continue;
      }
      const active = b === current ? ' active' : '';
      const activeLink = b === current ? ' bg-secondary link-light border border-dark' : ' link-dark';
      const aria = b === current ? ' aria-current="page"' : '';
      html += `        <li class="page-item${active}"><a class="page-link${activeLink}" href="${buildUrl(baseUrl, { ...extraParams, page: b })}"${aria}>${b}</a></li>\n`;
    }

    // Next
    const nextDisabled = current >= pages ? ' disabled' : '';
    html += `        <li class="page-item${nextDisabled}"><a class="page-link" href="${buildUrl(baseUrl, { ...extraParams, page: current + 1 })}" aria-label="${trans('next')}">&rsaquo;</a></li>\n`;

    html += '    </ul>\n';

    dtPagination.innerHTML = html;
  }

  function renderCustomRow(cfg, columnsCount) {
    switch (cfg._tag) {
      case "notice_row":
        return `<tr class="table-info"><td colspan="${columnsCount}" class="text-center">${cfg.message}</td></tr>`;
      case "custom_html":
        return `<tr><td colspan="${columnsCount}">${cfg.html}</td></tr>`;
      default:
        return "";
    }
  }

  // Pagination helper - Generowanie listy przycisków paginacji z wielokropkami
  function paginationButtons(current, total, adjacents = 2) {
    const buttons = [];

    if (total <= 1) {
      return buttons;
    }

    buttons.push(1);

    const start = Math.max(2, current - adjacents);
    const end = Math.min(total - 1, current + adjacents);

    if (start > 2) {
      buttons.push('...');
    }

    for (let i = start; i <= end; i++) {
      buttons.push(i);
    }

    if (end < total - 1) {
      buttons.push('...');
    }

    if (total > 1) {
      buttons.push(total);
    }

    // Usuwamy duplikaty liczb (ale zostawiamy wszystkie '...')
    const final = [];
    const seenNums = new Set();
    for (const b of buttons) {
      if (b === '...') {
        final.push(b);
        continue;
      }
      const num = Number(b);
      if (seenNums.has(num)) continue;
      seenNums.add(num);
      final.push(num);
    }

    return final;
  }

  // --- Utilities ---
  function buildUrl(baseUrl, params) {
    try {
      const url = new URL(baseUrl, window.location.href);
      Object.entries(params || {}).forEach(([key, val]) => {
        if (val === undefined || val === null || val === '') return;
        const k = (key === 'query') ? 'q' : key;
        url.searchParams.set(k, String(val));
      });
      return url.toString();
    } catch (e) {
      // fallback: simple concatenation
      let q = new URLSearchParams();
      Object.entries(params || {}).forEach(([key, val]) => { if (val !== undefined && val !== null && val !== '') q.set((key === 'query') ? 'q' : key, String(val)); });
      return baseUrl + (baseUrl.indexOf('?') === -1 ? '?' : '&') + q.toString();
    }
  }

  function getPerPage(container) {
    const select = container.querySelector('#dtPerPage');
    if (!select) return 20;
    let val = select.value;
    if (val.includes('=')) {
      const urlParams = new URLSearchParams(val);
      val = urlParams.get('per_page') || 20;
    }
    return val;
  }

  function getFilters(container) {
    const filters = {};
    container.querySelectorAll('select[name^="filter_"]').forEach(select => {
      if (select.value) {
        filters[select.name] = select.value;
      }
    });
    return filters;
  }

  function getQuery(container) {
    const input = container.querySelector('#dtSearch');
    return input && input.value.trim() ? input.value.trim() : '';
  }

  function toggleDir(sider, field) {
    if (!sider || !sider.sort) return 'ASC';
    if (sider.sort === field) return (sider.dir || 'ASC').toUpperCase() === 'ASC' ? 'DESC' : 'ASC';
    return 'ASC';
  }

  function initTooltips(root = document) {
    if (!window.bootstrap || typeof bootstrap.Tooltip !== 'function') return;
    const els = (root || document).querySelectorAll('[data-bs-toggle="tooltip"]');
    els.forEach(el => {
      if (bootstrap.Tooltip.getInstance(el)) return;
      new bootstrap.Tooltip(el, { html: true, sanitize: false });
    });
  }

  // --- Helpers ---
  // Mini vsprintf (obsługuje tylko %s)
  function vsprintf(format, args) {
    let i = 0;
    return format.replace(/%s/g, () => (i < args.length ? args[i++] : ''));
  }

  // Tłumaczenia – fallback do klucza
  function trans(key, ...args) {
    let msg = DBM_CONFIG.i18n[key];

    if (!msg) {
      console.warn(`[Warning] Missing translation key: "${key}" (locale: ${DBM_CONFIG.locale || 'unknown'})`);
      return key; // fallback do samego klucza
    }

    if (args.length) {
      try {
        msg = vsprintf(msg, args); // można podpiąć sprintf.js
      } catch (e) {
        console.error(`[Exception] Error formatting translation "${key}" with args:`, args, e);
      }
    }

    return msg;
  }

  function renderEmptyHtml() {
    return `<span class="badge bg-secondary">${trans('empty')}</span>`;
  }

  function safeTruncate(str, length = 250) {
    if (typeof str !== 'string') return str;

    const trimmed = str.trim();
    if (trimmed.length <= length) return trimmed;

    return trimmed.slice(0, length - 3).trimEnd() + '...';
  }

  // Debug helper - pokaże w konsoli szczegóły zapytania
  function debugRequest(url, params, response = null) {
    console.group('[DBM DT API] Request Debug');
    console.log('URL:', url.toString());
    console.log('Params:', params);
    if (response) {
      console.log('Response:', response);
    }
    console.groupEnd();
  }

  // Escaping helpers
  function escapeHtml(s) { return String(s || '').replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c])); }
  function escapeAttr(s) { return String(s || '').replace(/"/g, '&quot;'); }

  // Expose refresh helper
  window[NS].refresh = function(container) {
    if (!container) return;
    loadDataTable(container, { page: 1, per_page: getPerPage(container), ...getFilters(container), query: getQuery(container) });
  };
})();
