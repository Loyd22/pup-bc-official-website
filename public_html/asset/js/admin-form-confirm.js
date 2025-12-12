/**
 * Admin Form Confirmation Dialog
 * Intercepts POST/CREATE form submissions and shows a styled confirmation dialog
 */

(function() {
    'use strict';

    // Create confirmation dialog HTML and styles
    function createConfirmationDialog() {
        // Add styles if not already added
        if (!document.getElementById('admin-confirm-dialog-styles')) {
            const style = document.createElement('style');
            style.id = 'admin-confirm-dialog-styles';
            style.textContent = `
                .admin-confirm-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.5);
                    z-index: 10000;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                    animation: admin-confirm-fade-in 0.15s ease-out;
                }

                @keyframes admin-confirm-fade-in {
                    from {
                        opacity: 0;
                    }
                    to {
                        opacity: 1;
                    }
                }

                .admin-confirm-dialog {
                    background: var(--card, #ffffff);
                    border-radius: var(--radius-lg, 12px);
                    box-shadow: var(--shadow-lg, 0 4px 16px rgba(0, 0, 0, 0.1), 0 2px 6px rgba(0, 0, 0, 0.08));
                    max-width: 480px;
                    width: 100%;
                    padding: 24px;
                    animation: admin-confirm-slide-up 0.2s ease-out;
                }

                @keyframes admin-confirm-slide-up {
                    from {
                        transform: translateY(20px);
                        opacity: 0;
                    }
                    to {
                        transform: translateY(0);
                        opacity: 1;
                    }
                }

                .admin-confirm-dialog__icon {
                    width: 48px;
                    height: 48px;
                    border-radius: 50%;
                    background: rgba(122, 0, 25, 0.1);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin-bottom: 16px;
                    color: var(--maroon, #7a0019);
                }

                .admin-confirm-dialog__icon svg {
                    width: 24px;
                    height: 24px;
                }

                .admin-confirm-dialog__title {
                    font-size: 18px;
                    font-weight: 600;
                    color: var(--ink, #1f2328);
                    margin: 0 0 12px 0;
                }

                .admin-confirm-dialog__message {
                    font-size: 14px;
                    color: var(--muted, #6b7280);
                    line-height: 1.5;
                    margin: 0 0 24px 0;
                }

                .admin-confirm-dialog__actions {
                    display: flex;
                    gap: 12px;
                    justify-content: flex-end;
                }

                .admin-confirm-btn {
                    padding: 10px 20px;
                    border-radius: var(--radius, 8px);
                    font-size: 14px;
                    font-weight: 500;
                    cursor: pointer;
                    border: none;
                    transition: all 0.15s ease;
                    min-width: 100px;
                }

                .admin-confirm-btn--primary {
                    background: var(--maroon, #7a0019);
                    color: #ffffff;
                }

                .admin-confirm-btn--primary:hover {
                    background: var(--maroon-light, #9a0023);
                }

                .admin-confirm-btn--secondary {
                    background: var(--sidebar-hover, #f3f4f6);
                    color: var(--sidebar-fg, #374151);
                }

                .admin-confirm-btn--secondary:hover {
                    background: var(--border, #e5e7eb);
                }

                .admin-confirm-btn:active {
                    transform: translateY(1px);
                }
            `;
            document.head.appendChild(style);
        }

        // Create overlay
        const overlay = document.createElement('div');
        overlay.className = 'admin-confirm-overlay';
        overlay.innerHTML = `
            <div class="admin-confirm-dialog">
                <div class="admin-confirm-dialog__icon">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                    </svg>
                </div>
                <h3 class="admin-confirm-dialog__title">Confirm Action</h3>
                <p class="admin-confirm-dialog__message">Are you sure you want to post/create this content?</p>
                <div class="admin-confirm-dialog__actions">
                    <button type="button" class="admin-confirm-btn admin-confirm-btn--secondary" data-action="cancel">Cancel</button>
                    <button type="button" class="admin-confirm-btn admin-confirm-btn--primary" data-action="confirm">Confirm</button>
                </div>
            </div>
        `;

        return overlay;
    }

    // Check if form is a CREATE action (not an UPDATE)
    function isCreateAction(form) {
        // Exclude login forms and other non-content forms
        const formAction = form.action || '';
        const formId = form.id || '';
        const formClass = form.className || '';
        
        // Skip login forms
        if (formAction.includes('login') || formId.includes('login') || formClass.includes('login')) {
            return false;
        }

        // Check for hidden id field
        const idInput = form.querySelector('input[name="id"][type="hidden"]');
        if (idInput && idInput.value && idInput.value.trim() !== '') {
            return false; // Has an ID, so it's an UPDATE
        }

        // Check for id in URL parameters (for edit pages)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('id')) {
            return false; // Has ID in URL, likely editing
        }

        // Check button text for clues
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            const buttonText = submitButton.textContent.toLowerCase().trim();
            // If button says "create", "add", "post", "save new", "upload", it's likely a create
            if (buttonText.includes('create') || 
                buttonText.includes('add') || 
                buttonText.includes('post') || 
                buttonText.includes('upload') ||
                (buttonText.includes('save') && (buttonText.includes('new') || buttonText.includes('citizen')))) {
                return true;
            }
            // If button says "update", "edit", it's likely an update
            if (buttonText.includes('update') || buttonText.includes('edit')) {
                return false;
            }
        }

        // Check form title/heading for clues
        const formCard = form.closest('.card');
        if (formCard) {
            const heading = formCard.querySelector('h2');
            if (heading) {
                const headingText = heading.textContent.toLowerCase();
                if (headingText.includes('new') || 
                    headingText.includes('create') || 
                    headingText.includes('add') ||
                    headingText.includes('upload')) {
                    return true;
                }
                if (headingText.includes('edit') || headingText.includes('update')) {
                    return false;
                }
            }
        }

        // Default: if no ID field/value, assume it's a CREATE
        return true;
    }

    // Show confirmation dialog
    function showConfirmationDialog(form) {
        return new Promise((resolve) => {
            const overlay = createConfirmationDialog();
            document.body.appendChild(overlay);

            const confirmBtn = overlay.querySelector('[data-action="confirm"]');
            const cancelBtn = overlay.querySelector('[data-action="cancel"]');

            const cleanup = () => {
                overlay.remove();
            };

            const handleConfirm = () => {
                cleanup();
                resolve(true);
            };

            const handleCancel = () => {
                cleanup();
                resolve(false);
            };

            confirmBtn.addEventListener('click', handleConfirm);
            cancelBtn.addEventListener('click', handleCancel);

            // Close on overlay click (outside dialog)
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    handleCancel();
                }
            });

            // Close on Escape key
            const handleEscape = (e) => {
                if (e.key === 'Escape') {
                    handleCancel();
                    document.removeEventListener('keydown', handleEscape);
                }
            };
            document.addEventListener('keydown', handleEscape);

            // Focus the confirm button for accessibility
            confirmBtn.focus();
        });
    }

    // Initialize form confirmation interceptors
    function initFormConfirmations() {
        // Find all POST forms in the admin
        const forms = document.querySelectorAll('form[method="post"], form[method="POST"]');
        
        forms.forEach((form) => {
            // Skip if already processed
            if (form.dataset.confirmProcessed) {
                return;
            }
            form.dataset.confirmProcessed = 'true';

            const submitHandler = async (e) => {
                // Check if this is a CREATE action
                if (!isCreateAction(form)) {
                    // It's an UPDATE, allow normal submission
                    return;
                }

                // It's a CREATE action, intercept and show confirmation
                e.preventDefault();
                e.stopPropagation();

                // Show confirmation dialog
                const confirmed = await showConfirmationDialog(form);

                if (confirmed) {
                    // User confirmed, submit the form
                    // Remove the event listener temporarily to avoid infinite loop
                    form.removeEventListener('submit', submitHandler);
                    form.submit();
                }
                // If cancelled, do nothing (form won't submit)
            };

            form.addEventListener('submit', submitHandler);
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFormConfirmations);
    } else {
        // DOM already loaded
        initFormConfirmations();
    }

    // Also handle dynamically added forms (e.g., modals)
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1) { // Element node
                    // Check if the added node is a form
                    if (node.tagName === 'FORM' && 
                        (node.method === 'post' || node.method === 'POST')) {
                        initFormConfirmations();
                    }
                    // Check if the added node contains forms
                    const forms = node.querySelectorAll && node.querySelectorAll('form[method="post"], form[method="POST"]');
                    if (forms && forms.length > 0) {
                        initFormConfirmations();
                    }
                }
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
})();

