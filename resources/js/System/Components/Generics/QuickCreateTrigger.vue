<template>
    <a
        v-if="mode === 'link'"
        href="#"
        :class="triggerClasses"
        :title="showTooltip ? resolvedTitle : null"
        :aria-label="resolvedAriaLabel"
        :aria-disabled="disabled"
        @click.prevent="handleClick">
        <i v-if="icon" :class="icon" aria-hidden="true"></i>
        <span v-if="text" v-text="text"></span>
    </a>

    <button
        v-else
        ref="button"
        type="button"
        :class="triggerClasses"
        :title="showTooltip ? resolvedTitle : null"
        :data-bs-toggle="mode === 'icon' && showTooltip ? 'tooltip' : null"
        :aria-label="resolvedAriaLabel"
        :disabled="disabled"
        @click="handleClick">
        <i v-if="icon" :class="icon" aria-hidden="true"></i>
        <span v-if="mode !== 'icon' && text" v-text="text"></span>
    </button>
</template>

<script>
import {createTooltip} from "@System/Helpers/Alerts.js";

export default {
    name: "QuickCreateTrigger",
    emits: ["click"],
    props: {
        mode: {
            type: String,
            default: "link",
            validator: value => ["icon", "link", "button"].includes(value)
        },
        text: {
            type: String,
            default: "Agregar"
        },
        title: {
            type: String,
            default: ""
        },
        showTooltip: {
            type: Boolean,
            default: true
        },
        ariaLabel: {
            type: String,
            default: ""
        },
        icon: {
            type: String,
            default: "fa-solid fa-circle-plus"
        },
        disabled: {
            type: Boolean,
            default: false
        },
        customClass: {
            type: [String, Array, Object],
            default: ""
        }
    },
    computed: {
        triggerClasses() {

            return [
                this.mode === "icon" ? "fg-button fg-button--icon" : "fg-link",
                "fg-quick-create",
                this.customClass
            ];

        },
        resolvedTitle() {

            return this.title || this.text;

        },
        resolvedAriaLabel() {

            return this.ariaLabel || this.resolvedTitle;

        }
    },
    mounted() {
        if(this.mode === "icon" && this.showTooltip) {
            createTooltip(this.$refs.button, this.resolvedTitle);
        }
    },
    beforeUnmount() {
        if(this.$refs.button) window.bootstrap?.Tooltip?.getInstance(this.$refs.button)?.dispose();
    },
    watch: {
        resolvedTitle: {
            flush: "post",
            handler(title) {
                if(this.mode === "icon" && this.showTooltip) createTooltip(this.$refs.button, title);
            }
        }
    },
    methods: {
        handleClick(event) {

            if(this.disabled) return;

            if(this.mode === "icon") window.bootstrap?.Tooltip?.getInstance(event.currentTarget)?.hide();
            this.$emit("click", event);

        }
    }
};
</script>
