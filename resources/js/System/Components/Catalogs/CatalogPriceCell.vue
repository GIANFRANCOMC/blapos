<template>
    <div class="br-entity-prices">
        <span class="br-entity-price-row is-sale">
            <strong>{{ amount(record.price) }}</strong>
        </span>
        <details v-if="limits.length" class="br-catalog-disclosure">
            <summary class="br-link br-catalog-disclosure__summary">
                Límites
                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
            </summary>
            <dl class="br-catalog-disclosure__values">
                <div v-for="limit in limits" :key="limit.label" class="br-entity-price-row">
                    <dt class="colon-at-end">{{ limit.label }}</dt>
                    <dd>{{ amount(limit.value) }}</dd>
                </div>
            </dl>
        </details>
    </div>
</template>

<script>
import {formatCurrencyAmount} from "../../../Shared/Numbers.js";

export default {
    name: "CatalogPriceCell",
    props: {
        record: {type: Object, required: true}
    },
    computed: {
        limits() {
            return [
                {label: "Mínimo", value: this.record.min_price},
                {label: "Máximo", value: this.record.max_price}
            ].filter(({value}) => value !== null && value !== undefined && value !== "");
        }
    },
    methods: {
        amount(value) {
            return formatCurrencyAmount(value, this.record.currency);
        }
    }
};
</script>
