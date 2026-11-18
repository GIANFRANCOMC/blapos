<template>
    <div ref="tooltip" class="br-table-actions-dropdown" :class="{dropdown}" title="Acciones" data-bs-toggle="tooltip">
        <button
            ref="button"
            type="button"
            class="waves-effect"
            :class="[
                type === 'button' || variant === 'solid' ? 'br-btn br-btn-sm br-btn-actions' : 'br-table-actions-trigger',
                {'br-btn-actions--icon': type === 'icon' && variant === 'solid'}
            ]"
            :data-bs-toggle="dropdown ? 'dropdown' : null"
            data-bs-boundary="viewport"
            data-bs-config='{"popperConfig":{"strategy":"fixed"}}'
            :aria-expanded="dropdown ? 'false' : null"
            :aria-label="label"
            :disabled="disabled"
            @click="$emit('click', $event)">
            <i v-if="type === 'icon'" class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i>
            <template v-else>
                <span>{{ text }}</span>
                <i class="fa-solid fa-chevron-down br-btn-actions__chevron" aria-hidden="true"></i>
            </template>
        </button>
        <slot></slot>
    </div>
</template>

<script>
import {createTooltip} from "@System/Helpers/Alerts.js";

export default {
    name: "ActionsButton",
    emits: ["click"],
    props: {
        type: {type: String, default: "icon", validator: value => ["icon", "button"].includes(value)},
        variant: {type: String, default: "solid", validator: value => ["solid", "plain"].includes(value)},
        text: {type: String, default: "Acciones"},
        dropdown: {type: Boolean, default: false},
        label: {
            type: String,
            default: "Acciones"
        },
        disabled: {
            type: Boolean,
            default: false
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
