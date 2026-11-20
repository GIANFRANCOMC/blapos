const icons = Object.freeze({
    loading: new URL('../../../assets/fg/feedback/loading.svg', import.meta.url).href,
    success: new URL('../../../assets/fg/feedback/success.svg', import.meta.url).href,
    error: new URL('../../../assets/fg/feedback/error.svg', import.meta.url).href,
    warning: new URL('../../../assets/fg/feedback/warning.svg', import.meta.url).href,
    export: new URL('../../../assets/fg/feedback/export.svg', import.meta.url).href,
    exportWhite: new URL('../../../assets/fg/feedback/export-white.svg', import.meta.url).href
});

export function escapeFeedbackText(value) {
    return String(value ?? "").replace(/[&<>"']/g, character => ({"&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"})[character]);
}

export function feedbackIconHtml(type, spin = false) {
    const src = icons[type] || icons.warning;
    const size = type.startsWith("export") ? 16 : 32;
    return `<img class="fg-feedback-icon${spin ? ' fa-spin' : ''}" src="${src}" width="${size}" height="${size}" alt="" aria-hidden="true">`;
}

/** Replace application theme classes; keep SweetAlert's API, lifecycle and decisions intact. */
export function feedbackOptions(options = {}) {
    const custom = options.customClass || {};
    const confirmation = Boolean(options.showCancelButton || options.showDenyButton);
    const loading = String(custom.popup || '').includes('fg-swal--loading');
    const variant = loading ? 'loading' : confirmation ? 'confirm' : 'result';
    const width = typeof options.width === 'number' && options.width > 0 ? options.width : null;
    const willOpen = options.willOpen;
    return {
        ...options,
        width: `min(var(--fg-swal-width, ${confirmation ? 480 : 420}px), calc(100vw - 32px))`,
        buttonsStyling: false,
        ...(options.icon && !String(options.iconHtml || "").includes("fg-feedback-icon") ? {iconHtml: feedbackIconHtml(options.icon)} : {}),
        customClass: {
            container: 'fg-swal-layer',
            popup: `fg-swal fg-swal--${variant}`,
            title: 'fg-swal__title',
            htmlContainer: 'fg-swal__html',
            icon: 'fg-swal__icon',
            confirmButton: /danger/.test(custom.confirmButton || '') ? 'fg-button fg-button--danger' : 'fg-button',
            cancelButton: 'fg-button fg-button--secondary',
            denyButton: 'fg-button fg-button--secondary',
            input: 'fg-swal__input'
        },
        willOpen(popup) {
            if(width) popup.style.setProperty('--fg-swal-width', width + 'px');
            willOpen?.call(this, popup);
        }
    };
}

export function initializeFeedback(Swal) {
    const installed = Symbol.for('blapos.fgFeedback');
    if(!Swal?.fire || Swal[installed]) return;
    const fire = Swal.fire;
    Swal.fire = function(title, html, icon) {
        const options = title && typeof title === 'object' ? title : {title, html, icon};
        return fire.call(this, feedbackOptions(options));
    };
    Swal[installed] = true;
}
