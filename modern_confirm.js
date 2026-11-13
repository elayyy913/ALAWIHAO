(function () {
    if (window.modernConfirmReady) return;
    window.modernConfirmReady = true;

    const styles = `
        .modern-confirm-dialog {
            width: min(420px, calc(100vw - 32px));
            padding: 28px;
            border: 1px solid #e5e9e2;
            border-radius: 18px;
            color: #263126;
            background: #fff;
            box-shadow: 0 24px 70px rgba(20, 30, 18, .24);
            text-align: center;
        }
        .modern-confirm-dialog::backdrop {
            background: rgba(15, 23, 18, .48);
            backdrop-filter: blur(3px);
        }
        .modern-confirm-icon {
            display: grid;
            place-items: center;
            width: 52px;
            height: 52px;
            margin: 0 auto 15px;
            border-radius: 16px;
            color: #a93630;
            background: #fff1f0;
        }
        .modern-confirm-icon svg { width: 23px; height: 23px; }
        .modern-confirm-dialog h2 {
            margin: 0 0 8px;
            color: #263126;
            font-size: 1.15rem;
        }
        .modern-confirm-dialog p {
            margin: 0;
            color: #687365;
            font-size: .92rem;
            line-height: 1.5;
        }
        .modern-confirm-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 24px;
        }
        .modern-confirm-button {
            min-height: 42px;
            padding: 0 16px;
            border: 1px solid transparent;
            border-radius: 9px;
            font: inherit;
            font-size: .88rem;
            font-weight: 650;
            cursor: pointer;
            transition: background .16s ease, transform .16s ease;
        }
        .modern-confirm-button:hover { transform: translateY(-1px); }
        .modern-confirm-button:focus-visible {
            outline: 3px solid #91a77d;
            outline-offset: 2px;
        }
        .modern-confirm-button.secondary {
            color: #40503a;
            background: #f3f5f1;
            border-color: #dfe5da;
        }
        .modern-confirm-button.secondary:hover { background: #e9eee5; }
        .modern-confirm-button.danger { color: #fff; background: #a93630; }
        .modern-confirm-button.danger:hover { background: #8f2924; }
        @media (prefers-reduced-motion: reduce) {
            .modern-confirm-button { transition: none; }
        }
    `;

    const styleElement = document.createElement('style');
    styleElement.textContent = styles;
    document.head.appendChild(styleElement);

    const dialog = document.createElement('dialog');
    dialog.className = 'modern-confirm-dialog';
    dialog.setAttribute('aria-labelledby', 'modernConfirmTitle');
    dialog.setAttribute('aria-describedby', 'modernConfirmMessage');
    dialog.innerHTML = `
        <div class="modern-confirm-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 9v4"></path>
                <path d="M12 17h.01"></path>
                <path d="M10.3 3.9 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3l-7.5-13.1a2 2 0 0 0-3.4 0Z"></path>
            </svg>
        </div>
        <h2 id="modernConfirmTitle"></h2>
        <p id="modernConfirmMessage"></p>
        <div class="modern-confirm-actions">
            <button type="button" class="modern-confirm-button secondary" data-confirm-cancel>Cancel</button>
            <button type="button" class="modern-confirm-button danger" data-confirm-accept>Confirm</button>
        </div>
    `;
    document.body.appendChild(dialog);

    const title = dialog.querySelector('#modernConfirmTitle');
    const message = dialog.querySelector('#modernConfirmMessage');
    const cancelButton = dialog.querySelector('[data-confirm-cancel]');
    const acceptButton = dialog.querySelector('[data-confirm-accept]');
    let pendingAction = null;
    let restoreFocusTo = null;

    function openConfirmation(element, action, submitter) {
        pendingAction = { element: element, action: action, submitter: submitter };
        restoreFocusTo = element;
        title.textContent = element.dataset.confirmTitle || 'Please confirm';
        message.textContent = element.dataset.confirm || 'Are you sure you want to continue?';
        acceptButton.textContent = element.dataset.confirmLabel || 'Confirm';
        dialog.showModal();
        cancelButton.focus();
    }

    document.addEventListener('click', function (event) {
        const link = event.target.closest('a[data-confirm]');
        if (!link) return;

        if (link.dataset.confirmed === 'true') {
            delete link.dataset.confirmed;
            return;
        }

        if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        openConfirmation(link, 'navigate', null);
    });

    document.addEventListener('submit', function (event) {
        const form = event.target.closest('form[data-confirm]');
        if (!form) return;

        if (form.dataset.confirmed === 'true') {
            delete form.dataset.confirmed;
            return;
        }

        event.preventDefault();
        openConfirmation(form, 'submit', event.submitter || null);
    }, true);

    cancelButton.addEventListener('click', function () {
        pendingAction = null;
        dialog.close('cancel');
    });

    acceptButton.addEventListener('click', function () {
        const action = pendingAction;
        pendingAction = null;
        restoreFocusTo = null;
        dialog.close('confirm');
        if (!action) return;

        action.element.dataset.confirmed = 'true';
        if (action.action === 'navigate') {
            action.element.click();
        } else if (action.submitter && action.submitter.isConnected) {
            action.element.requestSubmit(action.submitter);
        } else {
            action.element.requestSubmit();
        }
    });

    dialog.addEventListener('click', function (event) {
        if (event.target === dialog) {
            pendingAction = null;
            dialog.close('cancel');
        }
    });

    dialog.addEventListener('cancel', function () {
        pendingAction = null;
    });

    dialog.addEventListener('close', function () {
        if (restoreFocusTo && restoreFocusTo.isConnected) restoreFocusTo.focus();
        restoreFocusTo = null;
    });
})();
