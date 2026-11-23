<template>
    <span class="fg-product-identity" :class="{'fg-product-identity--expandable': canExpand}">
        <button
            v-if="canExpand"
            type="button"
            class="fg-product-identity__toggle"
            :aria-expanded="expanded"
            :aria-controls="codesId"
            :aria-label="`${expanded ? 'Ocultar' : 'Mostrar'} identificación de ${productName}`"
            @click.stop="toggleCodes">
            <span class="fg-product-identity__icon" aria-hidden="true">
                <i :class="expanded ? 'fa-solid fa-minus' : 'fa-solid fa-plus'"></i>
            </span>
            <span class="fg-product-name">{{ productName }}</span>
        </button>
        <span v-else class="fg-product-name">{{ productName }}</span>
        <span
            v-if="variant === 'full' || canExpand"
            v-show="showCodes"
            :id="canExpand ? codesId : undefined"
            class="fg-product-identity__codes">
            <template v-if="showCodes">
                <span class="fg-product-identity__code">
                    <small class="fg-product-identity__label">Cód. interno:</small>
                    <span class="fg-product-identity__value">{{ product?.internal_code || "—" }}</span>
                </span>
                <span v-if="product?.barcode" class="fg-product-identity__code">
                    <small class="fg-product-identity__label">Cód. barras:</small>
                    <span class="fg-product-identity__value">{{ product.barcode }}</span>
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
