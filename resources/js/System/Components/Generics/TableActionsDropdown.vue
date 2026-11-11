<template>
    <div ref="tooltip" class="dropdown br-table-actions-dropdown" title="Acciones" data-bs-toggle="tooltip">
        <button
            ref="button"
            type="button"
            class="br-table-actions-trigger waves-effect"
            data-bs-toggle="dropdown"
            data-bs-boundary="viewport"
            data-bs-config='{"popperConfig":{"strategy":"fixed"}}'
            aria-expanded="false"
            :aria-label="label"
            :disabled="disabled">
            <i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" :class="menuClass">
            <slot></slot>
        </ul>
    </div>
</template>

<script>
import {createTooltip} from "@System/Helpers/Alerts.js";

export default {
    name: "TableActionsDropdown",
    props: {
        label: {
            type: String,
            default: "Acciones del registro"
        },
        disabled: {
            type: Boolean,
            default: false
        },
        menuClass: {
            type: String,
            default: "br-btn-actions-menu"
        }
    },
    mounted() {
        const trigger = this.$refs.tooltip;
        trigger.addEventListener("show.bs.tooltip", this.preventTooltipWhenUnavailable);
        trigger.addEventListener("show.bs.dropdown", this.hideTooltipOnOpen);
        createTooltip(trigger);
    },
    beforeUnmount() {
        const trigger = this.$refs.tooltip;
        trigger.removeEventListener("show.bs.tooltip", this.preventTooltipWhenUnavailable);
        trigger.removeEventListener("show.bs.dropdown", this.hideTooltipOnOpen);
        window.bootstrap?.Tooltip?.getInstance(trigger)?.dispose();
    },
    watch: {
        disabled(isDisabled) {
            if(isDisabled) this.hideTooltip();
        }
    },
    methods: {
        preventTooltipWhenUnavailable(event) {
            if(event.target === this.$refs.tooltip
                && (this.disabled || this.$refs.button.getAttribute("aria-expanded") === "true")) {
                event.preventDefault();
            }
        },
        hideTooltipOnOpen(event) {
            if(event.target === this.$refs.button) this.hideTooltip();
        },
        hideTooltip() {
            window.bootstrap?.Tooltip?.getInstance(this.$refs.tooltip)?.hide();
        }
    }
};
</script>
