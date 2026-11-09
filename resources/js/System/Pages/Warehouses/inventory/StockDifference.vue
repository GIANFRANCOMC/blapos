<template>
    <QuantityValue :class="['br-inventory__difference', tone]" :value="difference" :unit="unit" signed/>
</template>

<script>
import QuantityValue from "@System/Components/Generics/QuantityValue.vue";
import {normalizeNumber, resolveDecimals} from "@System/Helpers/NumberUtils.js";

export default {
    name: "StockDifference",
    components: {QuantityValue},
    props: {
        current: {type: [Number, String], default: 0},
        minimum: {type: [Number, String], default: 0},
        unit: {type: Object, default: null}
    },
    computed: {
        difference() {
            const value = normalizeNumber(this.current) - normalizeNumber(this.minimum);
            const rounded = Number(value.toFixed(resolveDecimals()));
            return Object.is(rounded, -0) ? 0 : rounded;
        },
        tone() {
            if(this.difference > 0) return "is-positive";
            if(this.difference < 0) return "is-negative";
            return "is-neutral";
        }
    }
};
</script>
