<template>
    <button
        ref="button"
        type="button"
        class="br-search-button waves-effect waves-light"
        :class="buttonClass"
        title="Buscar"
        aria-label="Buscar"
        data-bs-toggle="tooltip"
        data-bs-placement="top"
        @click="search">
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
    </button>
</template>

<script>
import {tooltipOptionsFor} from "@System/Helpers/TooltipOptions.js";

export default {
    name: "SearchButton",
    props: {
        buttonClass: {
            type: String,
            default: "br-btn br-btn-search"
        }
    },
    emits: ["click"],
    mounted() {
        const button = this.$refs.button;
        window.bootstrap?.Tooltip?.getOrCreateInstance(button, tooltipOptionsFor(button));
    },
    beforeUnmount() {
        window.bootstrap?.Tooltip?.getInstance(this.$refs.button)?.dispose();
    },
    methods: {
        search(event) {
            window.bootstrap?.Tooltip?.getInstance(event.currentTarget)?.hide();
            this.$emit("click", event);
        }
    }
};
</script>

<style scoped>
button.br-search-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: none;
    inline-size: var(--br-control-height, 2rem);
    padding-inline: 0;
}

button.br-search-button .fa-magnifying-glass {
    font-size: var(--br-font-size-090);
    line-height: 1;
}
</style>
