<template>
    <button
        ref="button"
        type="button"
        :class="menuItem ? 'dropdown-item' : 'br-barcode-download-button'"
        :data-bs-toggle="menuItem ? null : 'tooltip'"
        :data-bs-placement="menuItem ? null : 'top'"
        :title="menuItem ? null : tooltipText"
        :aria-label="ariaLabel"
        :disabled="disabled || !cleanValue"
        @click="download">
        <i class="fa-solid fa-barcode" aria-hidden="true"></i>
        <span v-if="menuItem" v-text="tooltip"></span>
    </button>
</template>

<script>
import JsBarcode from "jsbarcode";
import {createTooltip, toastrs} from "@System/Helpers/Alerts.js";

export default {
    name: "BarcodeDownloadButton",
    props: {
        value: {
            type: [String, Number],
            default: ""
        },
        fileName: {
            type: String,
            default: ""
        },
        format: {
            type: String,
            default: "EAN13"
        },
        tooltip: {
            type: String,
            default: "Descargar"
        },
        disabled: {
            type: Boolean,
            default: false
        },
        menuItem: {
            type: Boolean,
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
        tooltipText() {

            return this.statusText ?? this.tooltip;

        },
        ariaLabel() {

            return `${this.tooltip}${this.cleanValue ? ` ${this.cleanValue}` : ""}`;

        },
        resolvedFileName() {

            const reference = this.fileName || this.subtitle || this.cleanValue || "producto";
            const safeReference = String(reference)
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .replace(/[^a-zA-Z0-9_-]+/g, "-")
                .replace(/^-+|-+$/g, "")
                .toLowerCase();

            return `codigo-barras-${safeReference || "producto"}.png`;

        }
    },
    beforeUnmount() {

        window.clearTimeout(this.resetTimeout);
        this.disposeTooltip();

    },
    methods: {
        async download() {

            if(this.disabled || !this.cleanValue) return;

            try {

                const barcodeCanvas = this.renderBarcode();
                const blob = await this.canvasToBlob(barcodeCanvas);
                const objectUrl = window.URL.createObjectURL(blob);
                const link = document.createElement("a");

                link.href = objectUrl;
                link.download = this.resolvedFileName;
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(objectUrl);
                if(!this.menuItem) this.showTooltip("Descargado");

            }catch(error) {

                if(this.menuItem) {

                    toastrs({type: "error", subtitle: "No se pudo generar el código de barras."});

                }else {

                    this.showTooltip("No se pudo generar el PNG");

                }

            }

        },
        renderBarcode() {

            const canvas = document.createElement("canvas");

            JsBarcode(canvas, this.cleanValue, {
                format: this.format,
                width: 4,
                height: 150,
                displayValue: true,
                font: "Arial",
                fontSize: 28,
                fontOptions: "bold",
                textMargin: 10,
                margin: 18,
                background: "rgba(255, 255, 255, 0)",
                lineColor: "#000000"
            });

            return canvas;

        },
        canvasToBlob(canvas) {

            return new Promise((resolve, reject) => {

                canvas.toBlob(blob => {

                    if(blob) {

                        resolve(blob);

                    }else {

                        reject(new Error("PNG generation failed"));

                    }

                }, "image/png");

            });

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

            return createTooltip(this.$refs.button, this.tooltipText);

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
