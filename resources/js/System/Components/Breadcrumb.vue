<template>
    <nav
        v-if="visibleItems.length"
        class="br-breadcrumb"
        aria-label="Ubicación actual">
        <ol class="br-breadcrumb__list">
            <li
                v-for="(item, index) in visibleItems"
                :key="`${item.title}-${index}`"
                :class="['br-breadcrumb__item', {'is-current': item.active || index === visibleItems.length - 1}]"
                :aria-current="item.active || index === visibleItems.length - 1 ? 'page' : undefined">
                <i v-if="index > 0" class="fa-solid fa-chevron-right br-breadcrumb__separator" aria-hidden="true"></i>
                <span>{{ item.title }}</span>
            </li>
        </ol>
    </nav>
</template>

<script>
import {withNavigationGroup} from "./breadcrumbItems.js";

export default {
    name: "Breadcrumb",
    emits: [],
    props: {
        list: {
            type: Array,
            required: false,
            default: () => []
        }
    },
    computed: {
        visibleItems() {

            const navigation = typeof document === "undefined"
                ? {}
                : {
                    world: document.documentElement.dataset.navigationWorld,
                    group: document.documentElement.dataset.navigationGroup
                };

            return withNavigationGroup(this.list, navigation);

        }
    },
    methods: {
        //
    }
};
</script>
