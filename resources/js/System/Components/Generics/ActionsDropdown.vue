<template>
    <ActionsButton v-bind="$attrs" dropdown>
        <ul class="dropdown-menu dropdown-menu-end" :class="menuClass">
            <slot></slot>
        </ul>
    </ActionsButton>
</template>

<script>
import ActionsButton from "./ActionsButton.vue";

export default {
    name: "ActionsDropdown",
    inheritAttrs: false,
    components: {ActionsButton},
    props: {
        menuClass: {type: String, default: "br-btn-actions-menu"}
    }
};
</script>
