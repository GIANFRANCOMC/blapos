export function withNavigationGroup(items, navigation = {}) {

    const visibleItems = items.filter(item => item?.title);
    const {world, group, page} = navigation;

    if(world && page) {

        const titles = [world, group, page]
            .map(title => String(title || "").trim())
            .filter(Boolean)
            .filter((title, index, all) => index === 0 || all[index - 1].toLocaleLowerCase("es") !== title.toLocaleLowerCase("es"));

        return titles.map((title, index) => ({title, active: index === titles.length - 1}));

    }

    if(
        visibleItems.length < 2
        || !world
        || !group
        || world === group
        || visibleItems[0].title !== world
        || visibleItems.some(item => item.title === group)
    ) {

        return visibleItems;

    }

    return [visibleItems[0], {title: group}, ...visibleItems.slice(1)];

}
