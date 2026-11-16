<template>
    <button
        ref="button"
        type="button"
        class="waves-effect waves-light"
        :class="buttonClass"
        :disabled="disabled"
        aria-label="Limpiar filtros"
        title="Limpiar filtros"
        data-bs-toggle="tooltip"
        data-bs-placement="top"
        data-bs-trigger="hover"
        @click="clear">
        <!-- Font Awesome Free 6.4.2, fa-brush by Fonticons, Inc. https://fontawesome.com/license/free
             Adaptación (CC BY 4.0) con inversión vertical y mango alargado. -->
        <svg viewBox="0 0 384 560" width="0.75em" height="1.09375em" fill="currentColor" aria-hidden="true" focusable="false">
            <path d="M162 554Q160 560 154 560H135Q128 560 126 554L105 502Q102 496 96 496T87 502L66 554Q64 560 58 560H48Q28 559 14 546T0 512V304H384V512Q383 532 370 546T336 560H231Q224 560 222 554L201 502Q198 496 192 496T183 502Z"/>
            <path fill-rule="evenodd" d="M0 272V240Q1 213 19 195T64 176H128V64Q129 37 147 19T192 0T237 19T256 64V176H320Q347 177 365 195T384 240V272ZM192 48Q207 49 208 64T192 80T176 64T192 48Z"/>
        </svg>
        <slot></slot>
    </button>
</template>

<script>
import {createTooltip} from "@System/Helpers/Alerts.js";

export default {
    name: "ClearFiltersButton",
    props: {
        disabled: Boolean,
        buttonClass: {
            type: String,
            default: "br-filter-clear"
        }
    },
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
