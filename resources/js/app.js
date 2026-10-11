import './bootstrap';
import * as bootstrap from 'bootstrap';
import Swal from 'sweetalert2';
import DataTable from 'datatables.net-bs5';
import { Chart, registerables } from 'chart.js';
import $ from 'jquery';
import select2 from 'select2';

// Initialize Select2 on jQuery
select2($);

Chart.register(...registerables);

window.bootstrap = bootstrap;
window.Swal = Swal;
window.DataTable = DataTable;
window.Chart = Chart;
window.$ = $;
window.jQuery = $;

// Text this script writes itself, in the page language (<html lang>,
// set by App\Http\Middleware\SetLocale). Everything else is translated
// server-side.
const SW = document.documentElement.lang === 'sw';
const t = (en, sw) => (SW ? sw : en);

const THEME_STORAGE_KEY = 'app-theme';

// Chart text/grid colors follow the theme; re-applied on every toggle so
// charts already on screen recolor instead of keeping the old theme.
function applyChartTheme() {
    const styles = getComputedStyle(document.documentElement);
    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
    Chart.defaults.color = styles.getPropertyValue('--bs-secondary-color').trim() || '#475467';
    Chart.defaults.borderColor = styles.getPropertyValue('--bs-border-color-translucent').trim() || 'rgba(0,0,0,.1)';

    Object.values(Chart.instances).forEach((chart) => chart.update('none'));
}

function applyTheme(theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);
    applyChartTheme();
}

function initTheme() {
    // partials/head.blade.php already set data-bs-theme before first paint;
    // this only covers pages rendered without that partial.
    let stored = null;
    try {
        stored = localStorage.getItem(THEME_STORAGE_KEY);
    } catch (e) {
        // Storage blocked (private mode etc.) - fall back to the OS setting.
    }
    const preferred = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    applyTheme(stored === 'light' || stored === 'dark' ? stored : preferred);
}

window.toggleTheme = function toggleTheme() {
    const current = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    try {
        localStorage.setItem(THEME_STORAGE_KEY, next);
    } catch (e) {
        // Not persisted - still switch for this page view.
    }
    applyTheme(next);
};

initTheme();

function isDarkMode() {
    return document.documentElement.getAttribute('data-bs-theme') === 'dark';
}

// Shared confirmation dialog for every "delete this record" button in
// the app - one consistent look/copy pattern instead of each view
// repeating its own Swal.fire() block, and dark-mode aware (SweetAlert2
// doesn't pick up data-bs-theme on its own).
window.confirmDelete = function confirmDelete(name, form, text) {
    Swal.fire({
        title: t(`Delete ${name}?`, `Futa ${name}?`),
        text: text ?? t('This action cannot be undone.', 'Kitendo hiki hakiwezi kurudishwa.'),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: t('Delete', 'Futa'),
        cancelButtonText: t('Cancel', 'Ghairi'),
        confirmButtonColor: '#F04438',
        cancelButtonColor: '#667085',
        background: isDarkMode() ? '#10213A' : undefined,
        color: isDarkMode() ? '#E4E7EC' : undefined,
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
};

// Toast notifications - success/error/info/warning, top-right, theme
// aware. Layouts hand session flashes and validation errors over via
// window.__flashToasts (resources/views/partials/flash-toasts.blade.php);
// page scripts call window.showToast() directly for AJAX results.
window.showToast = function showToast(type, message) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: type === 'danger' ? 'error' : type,
        title: message,
        showConfirmButton: false,
        showCloseButton: true,
        // Errors stay up longer - they usually need reading and acting on.
        timer: type === 'error' || type === 'danger' ? 7000 : 4000,
        timerProgressBar: true,
        background: isDarkMode() ? '#18304F' : '#FFFFFF',
        color: isDarkMode() ? '#E4E7EC' : '#101828',
        customClass: { popup: 'app-toast' },
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        },
    });
};

document.addEventListener('DOMContentLoaded', () => {
    // SweetAlert shows one toast at a time - queue them.
    (window.__flashToasts || []).reduce(
        (previous, toast) => previous.then(() => new Promise((resolve) => {
            window.showToast(toast.type, toast.message);
            setTimeout(resolve, 1200);
        })),
        Promise.resolve(),
    );

    // Scroll to and focus the first invalid field after a failed submit.
    const firstInvalid = document.querySelector('.is-invalid');
    if (firstInvalid) {
        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (typeof firstInvalid.focus === 'function') {
            firstInvalid.focus({ preventScroll: true });
        }
    }
});

// ---------------------------------------------------------------------
// Money inputs: thousand separators while typing ("1,250,000.50").
// Any <input> named like an amount/price/rent/... (or marked
// data-money) is switched from type=number to a text input that shows
// grouped digits; the separators are stripped again on submit (and by
// App\Http\Middleware\NormalizeMoneyInput server-side, as a backstop).
// ---------------------------------------------------------------------
const MONEY_NAME = /(amount|price|rent|deposit|cost|fee|total|paid|balance|salary|budget|value|debit|credit)/i;

function formatMoneyValue(raw) {
    const cleaned = String(raw ?? '').replace(/[^\d.-]/g, '');
    if (cleaned === '' || cleaned === '-') return cleaned;

    const negative = cleaned.startsWith('-');
    const [whole, ...decimals] = cleaned.replace(/-/g, '').split('.');
    const grouped = (whole.replace(/^0+(?=\d)/, '') || '0').replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    const decimal = decimals.length ? '.' + decimals.join('').slice(0, 2) : '';

    return (negative ? '-' : '') + grouped + decimal;
}

const formatAmount = (number) => number.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

window.unformatMoney = (value) => String(value ?? '').replace(/,/g, '');

function enhanceMoneyInput(input) {
    if (input.dataset.moneyReady) return;
    input.dataset.moneyReady = '1';
    input.type = 'text';
    input.inputMode = 'decimal';
    input.autocomplete = 'off';
    input.classList.add('money-input');
    input.removeAttribute('step');
    input.value = formatMoneyValue(input.value);

    input.addEventListener('input', () => {
        // Keep the caret in place relative to the digits typed so far.
        const digitsBeforeCaret = input.value.slice(0, input.selectionStart).replace(/[^\d.]/g, '').length;
        input.value = formatMoneyValue(input.value);
        let position = 0;
        let seen = 0;
        while (position < input.value.length && seen < digitsBeforeCaret) {
            if (/[\d.]/.test(input.value[position])) seen++;
            position++;
        }
        input.setSelectionRange(position, position);
    });
}

// Lets page scripts set a money field programmatically (e.g. the lease
// form prefilling rent from the selected unit) and keep the formatting.
window.setMoneyValue = function setMoneyValue(input, value) {
    input.value = input.dataset.moneyReady ? formatMoneyValue(value) : value;
    input.dispatchEvent(new Event('change', { bubbles: true }));
};

function enhanceMoneyInputs(root = document) {
    root.querySelectorAll('input[type="number"], input[data-money]').forEach((input) => {
        if (input.dataset.money !== undefined || MONEY_NAME.test(input.name || '')) {
            enhanceMoneyInput(input);
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    enhanceMoneyInputs();

    // Rows added later (invoice line items, journal lines) get it too.
    new MutationObserver((mutations) => {
        mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
            if (node.nodeType === 1) enhanceMoneyInputs(node.parentNode || node);
        }));
    }).observe(document.body, { childList: true, subtree: true });

    // Strip separators right before any form posts (capture phase, so it
    // runs ahead of other submit handlers and FormData snapshots).
    document.addEventListener('submit', (event) => {
        event.target.querySelectorAll('input.money-input').forEach((input) => {
            input.value = window.unformatMoney(input.value);
        });
    }, true);
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.js-confirm-delete').forEach((button) => {
        button.addEventListener('click', () => {
            window.confirmDelete(button.dataset.name, button.closest('form'), button.dataset.text);
        });
    });
});

function initCommandPalette() {
    const modalEl = document.getElementById('commandPaletteModal');
    const input = document.getElementById('commandPaletteInput');
    const results = document.getElementById('commandPaletteResults');

    if (!modalEl || !input || !results) {
        return;
    }

    const modal = new bootstrap.Modal(modalEl);
    let debounceTimer;

    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
            event.preventDefault();
            modal.show();
        }
    });

    modalEl.addEventListener('shown.bs.modal', () => input.focus());

    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const query = input.value.trim();

        if (query.length < 2) {
            results.innerHTML = '';
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch(`/search?q=${encodeURIComponent(query)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then((response) => response.json())
                .then(renderCommandPaletteResults)
                .catch(() => {
                    results.innerHTML = `<div class="list-group-item text-danger small">${t('Search failed.', 'Utafutaji umeshindwa.')}</div>`;
                });
        }, 250);
    });

    function renderCommandPaletteResults(data) {
        const groups = (data.groups || []).filter((group) => group.items.length > 0);

        if (groups.length === 0) {
            results.innerHTML = `<div class="list-group-item text-secondary small">${t('No results', 'Hakuna matokeo')}</div>`;
            return;
        }

        results.innerHTML = groups
            .map((group) => {
                const items = group.items
                    .map((item) => `
                        <a href="${item.url}" class="list-group-item list-group-item-action">
                            <div>${escapeHtml(item.title)}</div>
                            <div class="small text-secondary">${escapeHtml(item.subtitle ?? '')}</div>
                        </a>
                    `)
                    .join('');

                return `<div class="list-group-item bg-body-secondary small text-secondary fw-semibold">${escapeHtml(group.label)}</div>${items}`;
            })
            .join('');
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value;
        return div.innerHTML;
    }
}

document.addEventListener('DOMContentLoaded', initCommandPalette);

function initNotificationBell() {
    const badge = document.getElementById('notificationUnreadBadge');
    const dropdown = document.getElementById('notificationDropdown');
    const list = document.getElementById('notificationList');
    const bellButton = document.getElementById('notificationBellButton');

    if (!badge || !dropdown || !list || !bellButton) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    function setBadge(count) {
        if (count > 0) {
            badge.textContent = count > 9 ? '9+' : String(count);
            badge.classList.remove('d-none');
        } else {
            badge.classList.add('d-none');
        }
    }

    function renderNotifications(notifications) {
        if (notifications.length === 0) {
            list.innerHTML = `<div class="list-group-item text-secondary small">${t('No notifications yet', 'Bado hakuna arifa')}</div>`;
            return;
        }

        list.innerHTML = notifications
            .map((item) => `
                <a href="#" class="list-group-item list-group-item-action js-notification-item ${item.read ? '' : 'bg-primary-subtle bg-opacity-25'}" data-id="${item.id}" data-url="${item.url ?? ''}">
                    <div class="fw-semibold small">${escapeHtml(item.title)}</div>
                    <div class="text-secondary small">${escapeHtml(item.message)}</div>
                    <div class="text-secondary small">${escapeHtml(item.created_at)}</div>
                </a>
            `)
            .join('');
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    }

    function loadNotifications() {
        fetch('/notifications/recent', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then((response) => response.json())
            .then((data) => {
                setBadge(data.unread_count);
                renderNotifications(data.notifications);
            })
            .catch(() => {
                list.innerHTML = '<div class="list-group-item text-danger small">Could not load notifications.</div>';
            });
    }

    list.addEventListener('click', (event) => {
        const item = event.target.closest('.js-notification-item');

        if (!item) {
            return;
        }

        event.preventDefault();
        const url = item.dataset.url;

        fetch(`/notifications/${item.dataset.id}/read`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
        }).finally(() => {
            if (url) {
                window.location = url;
            }
        });
    });

    // Fetch the unread count once on load (for the badge); the list
    // itself is only fetched when the dropdown is actually opened -
    // no polling, this app targets shared hosting with no WebSockets.
    loadNotifications();
    bellButton.addEventListener('shown.bs.dropdown', loadNotifications);
}

document.addEventListener('DOMContentLoaded', initNotificationBell);

// Invoice create/edit forms' repeatable line-item rows - plain DOM
// cloning off a <template>, no client-side framework (this app targets
// shared hosting with no Node in production, see ARCHITECTURE.md §1).
function initInvoiceLineItems() {
    const body = document.getElementById('invoiceItemsBody');
    const template = document.getElementById('invoiceItemRowTemplate');
    const addButton = document.getElementById('addInvoiceItemBtn');
    const totalEl = document.getElementById('invoiceItemsTotal');

    if (!body || !template || !addButton || !totalEl) {
        return;
    }

    let nextIndex = body.querySelectorAll('.invoice-item-row').length;

    function rowAmount(row) {
        const qty = parseFloat(window.unformatMoney(row.querySelector('.js-item-qty')?.value)) || 0;
        const price = parseFloat(window.unformatMoney(row.querySelector('.js-item-price')?.value)) || 0;
        const amount = qty * price;
        row.querySelector('.js-item-amount').textContent = formatAmount(amount);
        return amount;
    }

    function recalculateTotal() {
        let total = 0;
        body.querySelectorAll('.invoice-item-row').forEach((row) => {
            total += rowAmount(row);
        });
        totalEl.textContent = formatAmount(total);
    }

    addButton.addEventListener('click', () => {
        const wrapper = document.createElement('tbody');
        wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(nextIndex));
        body.appendChild(wrapper.firstElementChild);
        nextIndex++;
        recalculateTotal();
    });

    body.addEventListener('input', (event) => {
        if (event.target.classList.contains('js-item-qty') || event.target.classList.contains('js-item-price')) {
            recalculateTotal();
        }
    });

    body.addEventListener('click', (event) => {
        const removeButton = event.target.closest('.js-remove-item');

        if (!removeButton || body.querySelectorAll('.invoice-item-row').length <= 1) {
            return;
        }

        removeButton.closest('.invoice-item-row').remove();
        recalculateTotal();
    });

    recalculateTotal();
}

document.addEventListener('DOMContentLoaded', initInvoiceLineItems);

// Journal entry create form's repeatable debit/credit rows - same
// <template>-cloning approach as initInvoiceLineItems(), plus a live
// running debit/credit total so a user can see the entry balance
// before submitting (the server re-validates this regardless - see
// App\Modules\Accounting\Services\JournalEntryService::assertBalanced()).
function initJournalEntryLines() {
    const body = document.getElementById('journalLinesBody');
    const template = document.getElementById('journalLineRowTemplate');
    const addButton = document.getElementById('addJournalLineBtn');
    const debitsEl = document.getElementById('journalTotalDebits');
    const creditsEl = document.getElementById('journalTotalCredits');
    const statusEl = document.getElementById('journalBalanceStatus');

    if (!body || !template || !addButton || !debitsEl || !creditsEl || !statusEl) {
        return;
    }

    let nextIndex = body.querySelectorAll('.journal-line-row').length;

    function recalculateTotals() {
        let totalDebits = 0;
        let totalCredits = 0;

        body.querySelectorAll('.journal-line-row').forEach((row) => {
            totalDebits += parseFloat(window.unformatMoney(row.querySelector('.js-line-debit')?.value)) || 0;
            totalCredits += parseFloat(window.unformatMoney(row.querySelector('.js-line-credit')?.value)) || 0;
        });

        debitsEl.textContent = formatAmount(totalDebits);
        creditsEl.textContent = formatAmount(totalCredits);

        const balanced = totalDebits > 0 && Math.abs(totalDebits - totalCredits) < 0.005;
        statusEl.textContent = balanced ? 'Balanced' : 'Not balanced';
        statusEl.className = balanced ? 'text-success fw-semibold' : 'text-danger fw-semibold';
    }

    addButton.addEventListener('click', () => {
        const wrapper = document.createElement('tbody');
        wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(nextIndex));
        body.appendChild(wrapper.firstElementChild);
        nextIndex++;
        recalculateTotals();
    });

    body.addEventListener('input', (event) => {
        if (event.target.classList.contains('js-line-debit') || event.target.classList.contains('js-line-credit')) {
            recalculateTotals();
        }
    });

    body.addEventListener('click', (event) => {
        const removeButton = event.target.closest('.js-remove-line');

        if (!removeButton || body.querySelectorAll('.journal-line-row').length <= 2) {
            return;
        }

        removeButton.closest('.journal-line-row').remove();
        recalculateTotals();
    });

    recalculateTotals();
}

document.addEventListener('DOMContentLoaded', initJournalEntryLines);

function initDataTables() {
    const tables = new Map();
    let autoIdCount = 0;

    document.querySelectorAll('table.table:not([data-dt-ignore])').forEach((table) => {
        if (!table.id) {
            table.id = `datatable-${autoIdCount++}`;
        }

        const lengthMenu = table.dataset.dtLengthMenu
            ? JSON.parse(table.dataset.dtLengthMenu)
            : [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']];

        const dt = new DataTable(table, {
            pageLength: Number(table.dataset.dtLength) || 25,
            lengthMenu,
            lengthChange: true,
            searching: true,
            order: [],
            dom: 'lfrtip',
            language: {
                search: '_INPUT_',
                searchPlaceholder: t('Search…', 'Tafuta…'),
                lengthMenu: t('_MENU_ per page', '_MENU_ kwa ukurasa'),
                info: t('Showing _START_ to _END_ of _TOTAL_', 'Inaonyesha _START_ hadi _END_ kati ya _TOTAL_'),
                infoEmpty: t('Nothing to show', 'Hakuna cha kuonyesha'),
                infoFiltered: t('(filtered from _MAX_)', '(kati ya _MAX_)'),
                zeroRecords: t('No matching records', 'Hakuna kinacholingana'),
                emptyTable: t('No data yet', 'Bado hakuna data'),
                paginate: {
                    previous: t('Prev', 'Nyuma'),
                    next: t('Next', 'Mbele'),
                },
            },
        });

        tables.set(table.id, dt);
    });

    document.querySelectorAll('[data-dt-filter]').forEach((filter) => {
        const tableId = filter.dataset.dtFilter;
        const columnIndex = Number(filter.dataset.dtFilterColumn);
        const dt = tables.get(tableId);

        if (!dt || Number.isNaN(columnIndex)) {
            return;
        }

        filter.addEventListener('change', () => {
            const value = filter.value;
            dt.column(columnIndex).search(value ? `^${value}$` : '', true, false).draw();
        });
    });
}

document.addEventListener('DOMContentLoaded', initDataTables);
