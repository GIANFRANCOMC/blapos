export function tooltipOptionsFor(element, navigationContainer = document.body) {

    const customClass = element?.dataset?.bsCustomClass || "fg-tooltip";
    const isNavigationTooltip = customClass.includes("fg-tooltip");

    return {
        animation: false,
        boundary: "viewport",
        container: isNavigationTooltip ? navigationContainer : false,
        customClass,
        delay: {
            show: 0,
            hide: 0
        },
        trigger: element?.dataset?.bsTrigger ?? "hover focus"
    };

}
