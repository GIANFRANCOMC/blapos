<template>
    <QuantityValue :class="['br-inventory__difference', tone]" :value="difference" :unit="unit" signed/>
</template>

<script>
import QuantityValue from "@System/Components/Generics/QuantityValue.vue";
import {compareNumbers, subtractText} from "../../../../Shared/Numbers.js";

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
            return subtractText(this.current, this.minimum);
        },
        tone() {
            const sign = compareNumbers(this.difference, 0);
            if(sign > 0) return "is-positive";
            if(sign < 0) return "is-negative";
            return "is-neutral";
        }
    }
};
</script>
