<template>
    <InputNumber
        v-bind="$attrs"
        hasDiv
        :isRequired="commissionRequiresValue(commissionType)"
        :disabled="commissionType === 'none'"
        :minValue="0"
        :maxValue="commissionType === 'percentage' ? 100 : null">
        <template #inputGroupPrepend>
            <span class="input-group-text br-currency-prefix">
                <span class="br-currency-prefix__symbol" v-text="commissionType === 'percentage' ? '%' : currencySign"></span>
            </span>
        </template>
    </InputNumber>
</template>

<script>
import InputNumber from "@System/Components/InputNumber.vue";
import {commissionRequiresValue} from "@System/Components/Catalogs/catalogFormValidation.js";

export default {
    name: "CommissionValueInput",
    components: {InputNumber},
    inheritAttrs: false,
    props: {
        commissionType: {type: String, default: ""},
        currencySign: {type: String, default: ""}
    },
    methods: {commissionRequiresValue}
};
</script>
