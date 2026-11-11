<template>
    <span class="br-product-identity" :class="{'br-product-identity--expandable': canExpand}">
        <button
            v-if="canExpand"
            type="button"
            class="br-product-identity__toggle"
            :aria-expanded="expanded"
            :aria-controls="codesId"
            :aria-label="`${expanded ? 'Ocultar' : 'Mostrar'} identificación de ${productName}`"
            @click.stop="toggleCodes">
            <span class="br-product-identity__icon" aria-hidden="true">
                <i :class="expanded ? 'fa-solid fa-minus' : 'fa-solid fa-plus'"></i>
            </span>
            <span class="br-product-identity__name">{{ productName }}</span>
        </button>
        <span v-else class="br-product-identity__name">{{ productName }}</span>
        <span
            v-if="variant === 'full' || canExpand"
            v-show="showCodes"
            :id="canExpand ? codesId : undefined"
            class="br-product-identity__codes">
            <template v-if="showCodes">
                <span class="br-product-identity__code">
                    <small class="br-product-identity__label colon-at-end">Cód. interno</small>
                    <span class="br-product-identity__value">{{ product?.internal_code || "—" }}</span>
                </span>
                <span v-if="product?.barcode" class="br-product-identity__code">
                    <small class="br-product-identity__label colon-at-end">Cód. barras</small>
                    <span class="br-product-identity__value">{{ product.barcode }}</span>
                </span>
            </template>
        </span>
    </span>
</template>

<script>
import {useId} from "vue";

export default {
    name: "ProductIdentity",
    props: {
        product: {type: Object, default: null},
        variant: {
            type: String,
            default: "full",
            validator: value => ["full", "expandable"].includes(value)
        }
    },
    setup() {
        return {codesId: `product-codes-${useId()}`};
    },
    data() {
        return {expanded: false};
    },
    computed: {
        productName() {
            return this.product?.name || "Producto sin nombre";
        },
        productKey() {
            return this.product?.id ?? this.product?.internal_code ?? this.product?.barcode ?? this.product?.name;
        },
        canExpand() {
            return this.variant === "expandable" && Boolean(this.product?.internal_code || this.product?.barcode);
        },
        showCodes() {
            return this.variant === "full" || this.expanded;
        }
    },
    watch: {
        productKey() {
            this.expanded = false;
        }
    },
    methods: {
        toggleCodes(event) {
            this.expanded = !this.expanded;
            if(event.detail > 0) event.currentTarget.blur();
        }
    }
};
</script>

<style scoped>
.br-product-identity {
    display: block;
    min-width: 0;
    white-space: normal;
    overflow-wrap: anywhere;
}

.br-product-identity__name {
    display: block;
    color: var(--br-secondary);
    font-size: var(--br-ui-font-size);
    font-weight: var(--br-font-weight-value);
}

.br-product-identity--expandable {
    --br-product-identity-toggle-size: 1.5rem;
    --br-product-identity-toggle-gap: 0.4rem;
}

.br-product-identity__toggle {
    display: inline-flex;
    align-items: baseline;
    gap: var(--br-product-identity-toggle-gap);
    max-inline-size: 100%;
    padding: 0;
    padding-inline-end: var(--br-product-identity-toggle-gap);
    border: 0;
    border-radius: var(--br-radius-sm);
    background: transparent;
    text-align: start;
}

.br-product-identity__toggle:focus-visible {
    outline: none;
    box-shadow: var(--br-focus-ring);
}

.br-product-identity__icon {
    display: inline-grid;
    flex: 0 0 var(--br-product-identity-toggle-size);
    block-size: var(--br-product-identity-toggle-size);
    place-items: center;
    border-radius: var(--br-radius-sm);
    background: var(--br-primary-soft);
    color: var(--br-primary-active);
    font-size: var(--br-product-code-label-font-size);
}

.br-product-identity__toggle:hover .br-product-identity__icon {
    background: var(--br-primary);
    color: var(--br-white);
}

.br-product-identity--expandable .br-product-identity__codes {
    margin-inline-start: calc(var(--br-product-identity-toggle-size) + var(--br-product-identity-toggle-gap));
}

.br-product-identity__codes {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem 0.75rem;
    margin-top: 0.3rem;
}

.br-product-identity__code {
    display: inline-flex;
    align-items: baseline;
    gap: 0.28rem;
    min-width: 0;
}

.br-product-identity__label {
    color: var(--br-text-muted);
    font-size: var(--br-product-code-label-font-size);
    font-weight: var(--br-font-weight-body);
    white-space: nowrap;
}

.br-product-identity__value {
    min-width: 0;
    color: var(--br-secondary);
    font-variant-numeric: tabular-nums;
    font-size: var(--br-product-code-font-size);
    font-weight: var(--br-font-weight-body);
}
</style>
