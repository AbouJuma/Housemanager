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

const THEME_STORAGE_KEY = 'app-theme';

function applyTheme(theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);
}

function initTheme() {
    const stored = localStorage.getItem(THEME_STORAGE_KEY);
    const preferred = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    applyTheme(stored ?? preferred);
}

window.toggleTheme = function toggleTheme() {
    const current = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    localStorage.setItem(THEME_STORAGE_KEY, next);
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
        title: `Delete ${name}?`,
        text: text ?? 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        background: isDarkMode() ? '#1a1d23' : undefined,
        color: isDarkMode() ? '#e9ecef' : undefined,
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
};

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
                    results.innerHTML = '<div class="list-group-item text-danger small">Search failed.</div>';
                });
        }, 250);
    });

    function renderCommandPaletteResults(data) {
        const groups = (data.groups || []).filter((group) => group.items.length > 0);

        if (groups.length === 0) {
            results.innerHTML = '<div class="list-group-item text-secondary small">No results</div>';
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
            list.innerHTML = '<div class="list-group-item text-secondary small">No notifications yet</div>';
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
        const qty = parseFloat(row.querySelector('.js-item-qty')?.value) || 0;
        const price = parseFloat(row.querySelector('.js-item-price')?.value) || 0;
        const amount = qty * price;
        row.querySelector('.js-item-amount').textContent = amount.toFixed(2);
        return amount;
    }

    function recalculateTotal() {
        let total = 0;
        body.querySelectorAll('.invoice-item-row').forEach((row) => {
            total += rowAmount(row);
        });
        totalEl.textContent = total.toFixed(2);
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
            totalDebits += parseFloat(row.querySelector('.js-line-debit')?.value) || 0;
            totalCredits += parseFloat(row.querySelector('.js-line-credit')?.value) || 0;
        });

        debitsEl.textContent = totalDebits.toFixed(2);
        creditsEl.textContent = totalCredits.toFixed(2);

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
                searchPlaceholder: 'Search…',
                paginate: {
                    previous: 'Prev',
                    next: 'Next',
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
