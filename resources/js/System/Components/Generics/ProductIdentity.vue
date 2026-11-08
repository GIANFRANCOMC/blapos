<template>
    <span class="br-product-identity">
        <span class="br-product-identity__name">{{ product?.name || "Producto sin nombre" }}</span>
        <span class="br-product-identity__codes">
            <span class="br-product-identity__code">
                <small class="br-product-identity__label colon-at-end">Cód. interno</small>
                <span class="br-product-identity__value">{{ product?.internal_code || "—" }}</span>
            </span>
            <span v-if="product?.barcode" class="br-product-identity__code">
                <small class="br-product-identity__label colon-at-end">Cód. barras</small>
                <span class="br-product-identity__value">{{ product.barcode }}</span>
            </span>
        </span>
    </span>
</template>

<script>
export default {
    name: "ProductIdentity",
    props: {
        product: {type: Object, default: null}
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
