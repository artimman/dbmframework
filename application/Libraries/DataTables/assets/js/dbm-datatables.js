/**
 * DbM DataTables PHP, file: dbm-datatable.js
 * 
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 */

// Globalny stan do kontroli zdarzeń i żądań
const DT = window.__DT__ || (window.__DT__ = {
  wired: false,
  latestReqId: 0,
  aborter: null,
});

// loadDataTable z AbortController + wersjonowaniem
function loadDataTable(container, params = {}) {
  if (!container) return;

  const dtUrl = container.getAttribute('data-dt-url') || '/';
  const dtHead = container.querySelector('#dtHead');
  const dtBody = container.querySelector('#dtBody');
  const dtInfo = container.querySelector('#dtInfo');
  const dtPagination = container.querySelector('#dtPagination');

  if (DT.aborter) { try { DT.aborter.abort(); } catch {} }
  DT.aborter = ('AbortController' in window) ? new AbortController() : null;
  const signal = DT.aborter ? DT.aborter.signal : undefined;

  const reqId = ++DT.latestReqId;

  fetch(buildUrl(dtUrl, params), { signal })
    .then(r => r.json())
    .then(data => {
      if (reqId !== DT.latestReqId) return;

      if (data.success) {
        if (dtHead && data.thead_html) dtHead.innerHTML = data.thead_html;
        if (dtBody) dtBody.innerHTML = data.rows_html || '';
        if (dtInfo) dtInfo.innerHTML = data.info_html || '';
        if (dtPagination) dtPagination.innerHTML = data.pagination_html || '';

        initTooltips(dtBody);
      } else {
        console.error('Błąd w odpowiedzi API:', data.message);
      }
    })
    .catch(err => {
      if (err && err.name === 'AbortError') return;
      console.error('AJAX error', err);
    });
}

// Delegowane eventy
function wireGlobalOnce() {
  if (DT.wired) return;
  DT.wired = true;

  // per_page (zmiana selecta)
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
      query: getQuery(container) 
    });
  });

  // submit filtrów
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
      query: getQuery(container) 
    });
  });

  // reset filtrów
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

  // wyszukiwarka (enter)
  document.addEventListener('keyup', (e) => {
    const input = e.target && e.target.id === 'dtSearch' ? e.target : null;
    if (!input || e.key !== 'Enter') return;

    const container = input.closest('.datatableContainer');
    if (!container) return;

    loadDataTable(container, { 
      page: 1, 
      per_page: getPerPage(container), 
      ...getFilters(container), 
      query: input.value.trim() 
    });
  });

  // wyszukiwarka (submit przycisku w form)
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
      query: getQuery(container) 
    });
  });

  // paginacja
  document.addEventListener('click', (e) => {
    const a = e.target.closest && e.target.closest('#dtPagination a');
    if (!a) return;

    const container = a.closest('.datatableContainer');
    if (!container) return;

    e.preventDefault();
    const url = new URL(a.href, window.location.origin);
    const params = Object.fromEntries(url.searchParams.entries());

    loadDataTable(container, { 
      ...params, 
      ...getFilters(container), 
      query: getQuery(container), 
      per_page: getPerPage(container) 
    });
  });

  // sortowanie (thead)
  document.addEventListener('click', (e) => {
    const a = e.target.closest && e.target.closest('#dtHead a');
    if (!a) return;

    const container = a.closest('.datatableContainer');
    if (!container) return;

    e.preventDefault();
    const url = new URL(a.href, window.location.origin);
    const params = Object.fromEntries(url.searchParams.entries());

    loadDataTable(container, { 
      ...params, 
      per_page: getPerPage(container), 
      ...getFilters(container), 
      query: getQuery(container) 
    });
  });
}

// Helpers
function buildUrl(baseUrl, params) {
  const url = new URL(baseUrl, window.location.origin);

  Object.entries(params).forEach(([key, val]) => {
    if (val !== undefined && val !== null) {
      if (key === 'query') key = 'q'; // normalizacja aliasu
      url.searchParams.set(key, val);
    }
  });

  return url.toString();
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
  container.querySelectorAll('select[name^=filter_]').forEach(select => {
    if (select.value) filters[select.name] = select.value;
  });
  return filters;
}

function getQuery(container) {
  const input = container.querySelector('#dtSearch');
  return input && input.value.trim() ? input.value.trim() : '';
}

// Helper do inicjalizacji tooltipów
function initTooltips(root = document) {
  if (!window.bootstrap || typeof bootstrap.Tooltip !== "function") return;

  const els = (root || document).querySelectorAll('[data-bs-toggle="tooltip"]');
  els.forEach(el => {
    if (bootstrap.Tooltip.getInstance(el)) return; // już zainicjalizowany
    new bootstrap.Tooltip(el, {
      html: true,
      sanitize: false // wyłączona sanitizacja, jeśli potrzebujesz <img> w tooltipie
    });
  });
}

// Start
document.addEventListener('DOMContentLoaded', function () {
  wireGlobalOnce();

  document.querySelectorAll('.datatableContainer').forEach(container => {
    loadDataTable(container, { 
      page: 1, per_page: getPerPage(container), ...getFilters(container), query: getQuery(container)
    });

    initTooltips(container);
  });
});
