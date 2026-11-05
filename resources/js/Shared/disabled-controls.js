// Solo se observa el control enfocado y sus contenedores de bloqueo relevantes.
export function initializeDisabledControlFocus() {
    const observer = new MutationObserver(blurIfDisabled);

    function blurIfDisabled() {
        const active = document.activeElement;

        if(!(active instanceof HTMLElement)) return;

        if(active.matches(":disabled, [aria-disabled='true']")
            || active.closest(".vs--disabled, .select2-container--disabled, [aria-disabled='true'], [inert]")) {
            active.blur();
        }
    }

    function watchFocusedControl() {
        observer.disconnect();

        const active = document.activeElement;

        if(!(active instanceof HTMLElement)
            || !active.matches("input, textarea, select, [contenteditable='true'], .select2-selection")) return;

        observer.observe(active, {attributes: true, attributeFilter: ["disabled", "aria-disabled"]});

        for(let ancestor = active.parentElement; ancestor; ancestor = ancestor.parentElement) {
            const attributeFilter = ["aria-disabled", "inert"];

            if(ancestor.matches(".v-select, .select2-container")) attributeFilter.push("class");
            if(ancestor.matches("fieldset")) attributeFilter.push("disabled");

            observer.observe(ancestor, {attributes: true, attributeFilter});
        }

        blurIfDisabled();
    }

    document.addEventListener("focusin", watchFocusedControl, true);
    document.addEventListener("focusout", () => queueMicrotask(watchFocusedControl), true);
}

if(typeof document !== "undefined" && typeof MutationObserver !== "undefined") {
    initializeDisabledControlFocus();
}
