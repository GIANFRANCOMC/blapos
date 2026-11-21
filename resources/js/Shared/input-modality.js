// Track intentional navigation; Shift and other modifiers do not change the mode.
export function initializeInputModality(doc = document) {
    const root = doc.documentElement;
    if(root.dataset.fgInputMode) return () => {};

    root.dataset.fgInputMode = "pointer";
    const navigationKeys = new Set(["Tab", "ArrowUp", "ArrowDown", "ArrowLeft", "ArrowRight", "Home", "End", "PageUp", "PageDown", "Enter", " ", "Escape"]);
    const keydown = event => {
        if(event.isComposing || event.altKey || event.ctrlKey || event.metaKey) return;
        if(navigationKeys.has(event.key)) root.dataset.fgInputMode = "keyboard";
    };
    const pointerdown = () => { root.dataset.fgInputMode = "pointer"; };
    doc.addEventListener("keydown", keydown, true);
    doc.addEventListener("pointerdown", pointerdown, true);
    return () => {
        doc.removeEventListener("keydown", keydown, true);
        doc.removeEventListener("pointerdown", pointerdown, true);
        delete root.dataset.fgInputMode;
    };
}

if(typeof document !== "undefined") initializeInputModality();
