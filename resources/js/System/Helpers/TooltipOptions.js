export function tooltipOptionsFor(element, navigationContainer = document.body) {

    const customClass = element?.dataset?.bsCustomClass || "br-tooltip";
    const isNavigationTooltip = customClass === "br-navigation-tooltip"
        || customClass === "br-navigation-logout-tooltip";

    return {
        animation: false,
        boundary: "viewport",
        container: isNavigationTooltip ? navigationContainer : false,
        customClass,
        delay: {
            show: 0,
            hide: 0
        },
        trigger: "hover focus"
    };

}
