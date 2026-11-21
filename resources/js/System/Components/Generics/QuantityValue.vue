<template>
    <span :class="{'fg-help': showDash}">{{ showDash ? "—" : amount }}<small v-if="!showDash && parts.abbreviation" class="fg-quantity-unit">{{ " " + parts.abbreviation }}</small></span>
</template>

<script>
import {compareNumbers, quantityParts} from "../../../Shared/Numbers.js";

export default {
    props: {
        value: {type: [Number, String], default: 0},
        unit: {type: Object, default: null},
        signed: Boolean,
        zeroAsDash: Boolean
    },
    computed: {
        showDash() {
            return this.zeroAsDash && compareNumbers(this.parts.amount, 0) === 0;
        },
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
