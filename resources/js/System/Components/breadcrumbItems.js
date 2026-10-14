export function withNavigationGroup(items, navigation = {}) {

    const visibleItems = items.filter(item => item?.title);
    const {world, group} = navigation;

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
