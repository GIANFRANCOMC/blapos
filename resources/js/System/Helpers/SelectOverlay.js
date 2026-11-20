/** Vue Select owns the list and its keyboard behavior; this adapter only positions its body portal. */
export function positionSelectDropdown(dropdown, select) {
    dropdown.classList.add("fg-select-menu");
    dropdown.style.visibility = "hidden";
    const toggle = select.$refs.toggle;
    let frame;
    let disposed = false;
    const update = () => {
        if(disposed || !toggle.isConnected) return;
        const rect = toggle.getBoundingClientRect();
        const viewport = window.visualViewport;
        const viewportTop = viewport?.offsetTop || 0;
        const viewportHeight = viewport?.height || window.innerHeight;
        const viewportWidth = viewport?.width || window.innerWidth;
        const below = viewportTop + viewportHeight - rect.bottom - 12;
        const above = rect.top - viewportTop - 12;
        const desiredHeight = Math.min(dropdown.scrollHeight || 240, 240);
        const opensAbove = below < Math.min(desiredHeight, 144) && above > below;
        const available = Math.max(48, opensAbove ? above : below);
        const height = Math.min(desiredHeight, available);
        dropdown.style.width = Math.min(rect.width, viewportWidth - 16) + "px";
        dropdown.style.maxHeight = Math.min(240, available) + "px";
        dropdown.style.left = Math.max(8, Math.min(rect.left, viewportWidth - rect.width - 8)) + "px";
        dropdown.style.top = (opensAbove ? rect.top - height - 4 : rect.bottom + 4) + "px";
        dropdown.dataset.placement = opensAbove ? "top" : "bottom";
        dropdown.style.visibility = "visible";
    };
    const schedule = () => {
        window.cancelAnimationFrame(frame);
        frame = window.requestAnimationFrame(update);
    };
    const resizeObserver = new ResizeObserver(schedule);
    resizeObserver.observe(toggle);
    resizeObserver.observe(dropdown);
    window.addEventListener("resize", schedule);
    window.addEventListener("scroll", schedule, true);
    window.visualViewport?.addEventListener("resize", schedule);
    schedule();
    return () => {
        disposed = true;
        window.cancelAnimationFrame(frame);
        resizeObserver.disconnect();
        window.removeEventListener("resize", schedule);
        window.removeEventListener("scroll", schedule, true);
        window.visualViewport?.removeEventListener("resize", schedule);
    };
}
