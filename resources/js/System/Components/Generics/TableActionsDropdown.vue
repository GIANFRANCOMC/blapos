<template>
    <ActionsDropdown variant="plain" v-bind="$attrs"><slot></slot></ActionsDropdown>
</template>

<script>
import ActionsDropdown from "./ActionsDropdown.vue";

export default {
    name: "TableActionsDropdown",
    inheritAttrs: false,
    components: {ActionsDropdown}
};
</script>
