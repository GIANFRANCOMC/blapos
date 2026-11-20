<template>
    <InputNumber
        v-bind="$attrs"
        hasDiv
        :isRequired="commissionRequiresValue(commissionType)"
        :disabled="disabled || commissionType === 'none'"
        :minValue="0"
        :maxValue="commissionType === 'percentage' ? 100 : null">
        <template #inputGroupPrepend>
            <span :class="'fg-field__prefix'">
                <span :class="'fg-field__prefix'" v-text="commissionType === 'none' ? '—' : commissionType === 'percentage' ? '%' : currencySign"></span>
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
        disabled: Boolean,
        commissionType: {type: String, default: ""},
        currencySign: {type: String, default: ""}
    },
    methods: {commissionRequiresValue}
};
</script>
