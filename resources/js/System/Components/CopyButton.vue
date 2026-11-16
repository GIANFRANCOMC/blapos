<template>
    <component
        :is="isButton ? 'button' : 'span'"
        ref="control"
        :class="isButton ? 'br-copy-button' : 'br-copy-icon'"
        :type="isButton ? 'button' : undefined"
        :disabled="isButton ? disabled : undefined"
        :role="isButton ? undefined : 'button'"
        :tabindex="isButton || disabled ? undefined : 0"
        :aria-disabled="!isButton && disabled ? 'true' : undefined"
        data-bs-toggle="tooltip"
        data-bs-placement="top"
        :title="tooltipText"
        :aria-label="ariaLabelText"
        @click="copy"
        @keydown.enter="activateIcon"
        @keydown.space="activateIcon">
        <i class="fa-solid fa-clipboard" aria-hidden="true"></i>
    </component>
</template>

<script>
import {createTooltip} from "../Helpers/Alerts.js";
import {writeClipboard} from "../Helpers/Clipboard.js";

export default {
    name: "CopyButton",
    props: {
        type: {
            type: String,
            default: "icon",
            validator: value => ["icon", "button"].includes(value)
        },
        value: {
            type: [String, Number],
            required: false,
            default: ""
        },
        label: {
            type: String,
            required: false,
            default: "Valor"
        },
        tooltip: {
            type: String,
            required: false,
            default: null
        },
        copiedTooltip: {
            type: String,
            required: false,
            default: null
        },
        useLabelInTooltip: {
            type: Boolean,
            required: false,
            default: false
        },
        disabled: {
            type: Boolean,
            required: false,
            default: false
        }
    },
    data() {

        return {
            statusText: null,
            resetTimeout: null
        };

    },
    computed: {
        isButton() {
            return this.type === "button";
        },
        cleanValue() {

            return String(this.value ?? "").trim();

        },
        defaultTooltip() {

            return this.useLabelInTooltip
                ? `Copiar ${this.label.toLowerCase()}`
                : "Copiar";

        },
        defaultCopiedTooltip() {

            return this.useLabelInTooltip
                ? `${this.label} copiado`
                : "Copiado";

        },
        tooltipText() {

            return this.statusText ?? this.tooltip ?? this.defaultTooltip;

        },
        ariaLabelText() {

            return `${this.defaultTooltip}${this.cleanValue ? ` ${this.cleanValue}` : ""}`;

        }
    },
    mounted() {
        this.refreshTooltip();
    },
    watch: {
        type() {
            this.disposeTooltip();
            this.$nextTick(() => this.refreshTooltip());
        }
    },
    beforeUnmount() {

        window.clearTimeout(this.resetTimeout);
        this.disposeTooltip();

    },
    methods: {
        activateIcon(event) {
            if(this.isButton || this.disabled) return;

            event.preventDefault();
            if(!event.repeat) this.copy();
        },
        async copy() {
            if(this.disabled) return;

            if(!this.cleanValue) {

                this.showTooltip(`${this.label} sin valor`);
                return;

            }

            try {

                await writeClipboard(this.cleanValue);
                this.showTooltip(this.copiedTooltip ?? this.defaultCopiedTooltip);

            }catch(error) {

                this.showTooltip("No se pudo copiar");

            }

        },
        showTooltip(message) {

            this.statusText = message;

            this.$nextTick(() => {

                const instance = this.refreshTooltip();

                instance?.show();
                window.clearTimeout(this.resetTimeout);

                this.resetTimeout = window.setTimeout(() => {

                    this.statusText = null;
                    this.$nextTick(() => this.refreshTooltip());

                }, 1400);

            });

        },
        refreshTooltip() {

            return createTooltip(this.$refs.control, this.tooltipText);

        },
        disposeTooltip() {

            const control = this.$refs.control;
            const Bootstrap = window.bootstrap;

            if(!control || !Bootstrap?.Tooltip) return;

            Bootstrap.Tooltip.getInstance(control)?.dispose();

        }
    }
};
</script>
