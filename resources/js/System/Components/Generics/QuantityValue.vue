<template>
    <span>{{ amount }}<small v-if="parts.abbreviation" class="br-quantity-value__unit">{{ " " + parts.abbreviation }}</small></span>
</template>

<script>
import {compareNumbers, quantityParts} from "../../../Shared/Numbers.js";

export default {
    props: {
        value: {type: [Number, String], default: 0},
        unit: {type: Object, default: null},
        signed: Boolean
    },
    computed: {
        parts() {
            return quantityParts(this.value, this.unit);
        },
        amount() {
            const sign = this.signed && compareNumbers(this.parts.amount, 0) > 0 ? "+" : "";
            return sign + this.parts.amount;
        }
    }
};
</script>

<style scoped>
.br-quantity-value__unit {
    font-size: var(--br-font-size-066);
}
</style>
