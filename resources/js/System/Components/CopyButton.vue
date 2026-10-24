<template>
    <button
        ref="button"
        type="button"
        class="br-copy-button"
        data-bs-toggle="tooltip"
        data-bs-placement="top"
        :title="tooltipText"
        :aria-label="ariaLabelText"
        @click="copy">
        <i class="fa-solid fa-copy" aria-hidden="true"></i>
    </button>
</template>

<script>
import {createTooltip} from "../Helpers/Alerts.js";
import {writeClipboard} from "../Helpers/Clipboard.js";

export default {
    name: "CopyButton",
    props: {
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
    beforeUnmount() {

        window.clearTimeout(this.resetTimeout);
        this.disposeTooltip();

    },
    methods: {
        async copy() {

            if(this.disabled || !this.cleanValue) {

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

            const button = this.$refs.button;

            return createTooltip(button, this.tooltipText);

        },
        disposeTooltip() {

            const button = this.$refs.button;
            const Bootstrap = window.bootstrap;

            if(!button || !Bootstrap?.Tooltip) return;

            Bootstrap.Tooltip.getInstance(button)?.dispose();

        }
    }
};
</script>
