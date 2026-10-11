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
        type="button"
        :class="triggerClasses"
        :title="showTooltip ? resolvedTitle : null"
        :aria-label="resolvedAriaLabel"
        :disabled="disabled"
        @click="handleClick">
        <i v-if="icon" :class="icon" aria-hidden="true"></i>
        <span v-if="mode !== 'icon' && text" v-text="text"></span>
    </button>
</template>

<script>
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
                "br-quick-create-trigger",
                `br-quick-create-trigger--${this.mode}`,
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
    methods: {
        handleClick(event) {

            if(this.disabled) return;

            this.$emit("click", event);

        }
    }
};
</script>
