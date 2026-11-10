<template>
    <QuantityValue :class="['br-inventory__difference', tone]" :value="difference" :unit="unit" signed/>
</template>

<script>
import QuantityValue from "@System/Components/Generics/QuantityValue.vue";
import {normalizeNumber, roundNumber} from "../../../../Shared/Numbers.js";

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
            return roundNumber(value);
        },
        tone() {
            if(this.difference > 0) return "is-positive";
            if(this.difference < 0) return "is-negative";
            return "is-neutral";
        }
    }
};
</script>
