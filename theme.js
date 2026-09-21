(function () {
    var STORAGE_KEY = 'ahc_theme';
    var MODES = ['light', 'dark'];

    // ── Apply theme to <html> element immediately (before paint) ──
    function getPreferred() {
        var saved = localStorage.getItem(STORAGE_KEY);
        // Sanitize: if old value was 'system', reset to 'light'
        if (!saved || saved === 'system') return 'light';
        return saved;
    }

    function applyTheme(mode) {
        if (!MODES.includes(mode)) mode = 'light';
        document.documentElement.setAttribute('data-theme', mode);
        document.documentElement.setAttribute('data-mode', mode);
        localStorage.setItem(STORAGE_KEY, mode);
        updateToggleUI(mode);
    }

    applyTheme(getPreferred());

    function updateToggleUI(mode) {
        var btns = document.querySelectorAll('[data-theme-btn]');
        btns.forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-theme-btn') === mode);
        });
        var label = document.getElementById('themeLabel');
        if (label) {
            label.textContent = mode === 'dark' ? 'Dark' : 'Light';
        }
        var icon = document.getElementById('themeIcon');
        if (icon) {
            var imgSrc = mode === 'dark'
                ? 'image/darkmode.png'
                : 'image/light.png';
            var prefix = '';
            var parts = window.location.pathname.replace(/\\/g, '/').split('/');
            if (parts.indexOf('admin') !== -1) {
                prefix = '../';
            }
            icon.innerHTML = '<img src="' + prefix + imgSrc + '" alt="' + (mode === 'dark' ? 'Dark' : 'Light') + ' mode" style="width:20px;height:20px;object-fit:contain;vertical-align:middle;">';
        }
    }

    window.cycleTheme = function () {
        var current = getPreferred();
        var next = current === 'dark' ? 'light' : 'dark';
        applyTheme(next);
    };

    // ── Set specific mode ──────────────────────────────────────────
    window.setTheme = function (mode) {
        if (MODES.includes(mode)) applyTheme(mode);
    };

    // ── Init UI after DOM ready ────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        updateToggleUI(getPreferred());
    });
})();
