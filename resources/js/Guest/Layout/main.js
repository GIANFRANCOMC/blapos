const waves = window.Waves;
if(waves) {
    waves.init();
    const attachWaves = event => {
        const button = event.target.closest("button, a.fg-button, a.fg-guest-btn");
        if(button && !button.matches(":disabled, [aria-disabled='true'], .waves-effect")) waves.attach(button);
    };
    document.addEventListener("mousedown", attachWaves, true);
    document.addEventListener("touchstart", attachWaves, {capture: true, passive: true});
}

const menu = document.getElementById("navbarMenu");
const trigger = document.querySelector('[aria-controls="navbarMenu"]');

if(menu && trigger) {
    const closeMenu = () => window.bootstrap.Collapse.getOrCreateInstance(menu, {toggle: false}).hide();

    document.addEventListener("click", event => {
        if(menu.classList.contains("show") && !menu.contains(event.target) && !trigger.contains(event.target)) {
            closeMenu();
        }
    });

    menu.closest("nav").addEventListener("keydown", event => {
        if(event.key === "Escape" && menu.classList.contains("show")) {
            closeMenu();
            trigger.focus();
        }
    });
}
