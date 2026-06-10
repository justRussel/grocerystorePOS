/**
 * GroceryPOS – app.js
 * Global helpers: loading spinner, toast notifications, AJAX error handling.
 */
(function () {
    'use strict';

    // ─── Loading Overlay ──────────────────────────────────────────────────────
    const overlay = document.getElementById('loadingOverlay');

    window.showLoading = function () {
        if (overlay) overlay.classList.add('active');
    };

    window.hideLoading = function () {
        if (overlay) overlay.classList.remove('active');
    };

    // ─── Toast helper ─────────────────────────────────────────────────────────
    /**
     * Show a Bootstrap 5 toast notification.
     * @param {string} message
     * @param {'success'|'danger'|'warning'|'info'} [type='info']
     */
    window.showToast = function (message, type = 'info') {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const id = 'toast-' + Date.now();
        const iconMap = {
            success: 'bi-check-circle-fill',
            danger:  'bi-x-circle-fill',
            warning: 'bi-exclamation-triangle-fill',
            info:    'bi-info-circle-fill',
        };
        const icon = iconMap[type] || iconMap.info;

        const html = `
            <div id="${id}" class="toast align-items-center text-bg-${type} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center gap-2">
                        <i class="bi ${icon}"></i>
                        ${escapeHtml(message)}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>`;

        container.insertAdjacentHTML('beforeend', html);

        const toastEl = document.getElementById(id);
        const bsToast = new bootstrap.Toast(toastEl, { delay: 4000 });
        bsToast.show();

        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    };

    // ─── Global fetch wrapper with error handling ─────────────────────────────
    /**
     * Thin wrapper around fetch() that handles 401 redirects and 500 errors.
     * @param {string} url
     * @param {RequestInit} [options]
     * @returns {Promise<any>} parsed JSON
     */
    window.apiFetch = function (url, options = {}) {
        showLoading();

        // Attach CSRF token from meta tag if present
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) {
            options.headers = Object.assign({}, options.headers || {}, {
                'X-CSRF-Token': csrfMeta.getAttribute('content'),
            });
        }

        return fetch(url, options)
            .then(function (response) {
                if (response.status === 401) {
                    // Session expired — redirect to login
                    window.location.href = (window.BASE_URL || '/') + '?module=auth&action=login';
                    return Promise.reject(new Error('Unauthorized'));
                }

                if (response.status >= 500) {
                    showToast('A server error occurred. Please try again.', 'danger');
                    return Promise.reject(new Error('Server error ' + response.status));
                }

                return response.json();
            })
            .catch(function (err) {
                if (err.message !== 'Unauthorized') {
                    showToast('Network error: ' + err.message, 'danger');
                }
                return Promise.reject(err);
            })
            .finally(hideLoading);
    };

    // ─── HTML escape utility ──────────────────────────────────────────────────
    function escapeHtml(str) {
        return String(str)
            .replace(/&/g,  '&amp;')
            .replace(/</g,  '&lt;')
            .replace(/>/g,  '&gt;')
            .replace(/"/g,  '&quot;')
            .replace(/'/g,  '&#039;');
    }

    window.escapeHtml = escapeHtml;

    // ─── Mobile sidebar toggle ────────────────────────────────────────────────
    const sidebarToggle = document.getElementById('sidebarToggleBtn');
    const sidebar       = document.getElementById('appSidebar');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('sidebar-open');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function (e) {
            if (window.innerWidth < 992
                && !sidebar.contains(e.target)
                && !sidebarToggle.contains(e.target)
            ) {
                sidebar.classList.remove('sidebar-open');
            }
        });
    }

    // ─── Inject loading overlay HTML if absent ────────────────────────────────
    if (!document.getElementById('loadingOverlay')) {
        document.body.insertAdjacentHTML('beforeend',
            '<div id="loadingOverlay" role="status" aria-label="Loading">' +
            '<div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading...</span></div>' +
            '</div>'
        );
    }

})();
