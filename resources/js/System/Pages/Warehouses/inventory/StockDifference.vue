<template>
    <span :class="['br-inventory__difference', tone]">{{ label }}</span>
</template>

<script>
import {normalizeNumber, quantityWithUnit, resolveDecimals} from "@System/Helpers/NumberUtils.js";

export default {
    name: "StockDifference",
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
        },
        label() {
            return `${this.difference > 0 ? "+" : ""}${quantityWithUnit(this.difference, this.unit)}`;
        }
    }
};
</script>
