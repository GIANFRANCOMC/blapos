<template>
    <div :class="'fg-price'">
        <span :class="'fg-price__sale'">
            <strong>{{ amount(record.price) }}</strong>
        </span>
        <details v-if="limits.length" :class="'fg-price__limits'">
            <summary :class="'fg-price__summary'" aria-label="Límites de precio">
                Ver límites
                <span class="fg-chevron" aria-hidden="true"></span>
            </summary>
            <dl :class="'fg-price__values'">
                <div v-for="limit in limits" :key="limit.label" :class="'fg-price__limit'">
                    <dt :class="'fg-price__label'">{{ limit.label }}</dt>
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
