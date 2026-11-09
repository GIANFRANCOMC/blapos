<template>
    <button
        ref="button"
        type="button"
        class="br-filter-clear waves-effect waves-light"
        :disabled="disabled"
        aria-label="Limpiar filtros"
        title="Limpiar filtros"
        data-bs-toggle="tooltip"
        data-bs-placement="top"
        data-bs-trigger="hover"
        @click="clear">
        <i class="fa-solid fa-eraser" aria-hidden="true"></i>
    </button>
</template>

<script>
import {createTooltip} from "@System/Helpers/Alerts.js";

export default {
    name: "ClearFiltersButton",
    props: {disabled: Boolean},
    emits: ["clear"],
    mounted() {
        createTooltip(this.$refs.button);
    },
    beforeUnmount() {
        window.bootstrap?.Tooltip?.getInstance(this.$refs.button)?.dispose();
    },
    watch: {
        disabled(isDisabled) {
            if(isDisabled) this.hideTooltip();
        }
    },
    methods: {
        clear() {
            this.hideTooltip();
            this.$emit("clear");
        },
        hideTooltip() {
            window.bootstrap?.Tooltip?.getInstance(this.$refs.button)?.hide();
        }
    }
};
</script>
