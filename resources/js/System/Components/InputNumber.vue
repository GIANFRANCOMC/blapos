<template>
    <template v-if="hasDiv">
        <div :class="[...divClass, divSizeClass]">
            <slot name="default"></slot>
            <label v-if="!!title" v-text="title" :class="[...titleClass]"></label>
            <label v-if="isRequired" v-text="requiredLabel" :class="[...requiredClass]"></label>
            <slot name="defaultAppend"></slot>
            <div :class="['input-group', 'br-form-control-group', {'is-invalid': hasError}]">
                <slot name="inputGroupPrepend"></slot>
                <input
                    type="text"
                    :value="displayValue"
                    @focus="handleFocus"
                    @blur="handleBlur"
                    @input="handleTyping($event.target.value)"
                    @keydown="handleKeyDown"
                    :class="[...inputClass]"
                    :placeholder="placeholder"
                    :disabled="disabled"
                    @keyup.enter="handleEnterKey"/>
                <slot name="inputGroupAppend"></slot>
            </div>
            <div v-if="hasTextBottom">
                <small v-if="textBottomType === 'first'" :class="[...textBottomClass, 'br-form-error']" v-text="textBottom"></small>
            </div>
        </div>
    </template>
    <template v-else>
        <slot name="default"></slot>
        <label v-if="!!title" v-text="title" :class="[...titleClass]"></label>
        <label v-if="isRequired" v-text="requiredLabel" :class="[...requiredClass]"></label>
        <slot name="defaultAppend"></slot>
        <div :class="['input-group', 'br-form-control-group', {'is-invalid': hasError}]">
            <slot name="inputGroupPrepend"></slot>
            <input
                type="text"
                :value="displayValue"
                @focus="handleFocus"
                @blur="handleBlur"
                @input="handleTyping($event.target.value)"
                @keydown="handleKeyDown"
                :class="[...inputClass]"
                :placeholder="placeholder"
                :disabled="disabled"
                @keyup.enter="handleEnterKey"/>
            <slot name="inputGroupAppend"></slot>
        </div>
        <div v-if="hasTextBottom">
            <small v-if="textBottomType === 'first'" :class="[...textBottomClass, 'br-form-error']" v-text="textBottom"></small>
        </div>
    </template>
</template>

<script>
import { generalConfig } from "../Helpers/Constants.js";
import { fixedNumber, isDefined, isNumber, resolveDecimals, separatorNumber } from "../Helpers/Utils.js";

export default {
    name: "InputNumber",
    emits: ["enterKeyPressed", "update:modelValue", "input", "change"],
    props: {
        modelValue: {
            type: [String, Number],
            default: ""
        },
        // Div - Show
        hasDiv: {
            type: Boolean,
            required: false,
            default: false
        },
        divClass: {
            type: Array,
            required: false,
            default: []
        },
        // Div - Title
        title: {
            type: String,
            required: false,
            default: ""
        },
        titleClass: {
            type: Array,
            required: false,
            default: ["form-label", "colon-at-end"]
        },
        // Input - Required
        isRequired: {
            type: Boolean,
            required: false,
            default: false
        },
        requiredLabel: {
            type: String,
            required: false,
            default: generalConfig.forms.inputs.required
        },
        requiredClass: {
            type: Array,
            required: false,
            default: ["text-danger", "ms-1", "fw-bold"]
        },
        // Input - Props
        inputClass:{
            type: Array,
            required: false,
            default: ["form-control"]
        },
        hasNegative: {
            type: Boolean,
            required: false,
            default: false
        },
        minValue: {
            type: [String, Number],
            required: false,
            default: generalConfig.forms.inputs.minValue
        },
        maxValue: {
            type: [String, Number],
            required: false,
            default: generalConfig.forms.inputs.maxValue
        },
        decimals: {
            type: [String, Number],
            required: false,
            default: null
        },
        placeholder: {
            type: String,
            required: false,
            default: ""
        },
        disabled: {
            type: Boolean,
            required: false,
            default: false
        },
        // Text Bottom
        hasTextBottom: {
            type: Boolean,
            required: false,
            default: false
        },
        textBottomType: {
            type: String,
            required: false,
            default: "first"
        },
        textBottomClass: {
            type: Array,
            required: false,
            default: [generalConfig.forms.errors.styles.default]
        },
        textBottomInfo: {
            type: Array,
            required: false,
            default: []
        },
        // Sizes
        xl: {
            type: [String, Number],
            required: false,
            default: "12"
        },
        lg: {
            type: [String, Number],
            required: false,
            default: "12"
        },
        md: {
            type: [String, Number],
            required: false,
            default: "12"
        },
        sm: {
            type: [String, Number],
            required: false,
            default: "12"
        }
    },
    data() {
        return {
            isEditing: false,
            localValue: ""
        };
    },
    computed: {
        hasError() {

            return Boolean(this.textBottom);

        },
        formattedValue() {

            if(!isDefined({value: this.modelValue})) {

                return "";

            }

            return separatorNumber(this.modelValue, this.decimalPlaces);

        },
        decimalPlaces() {

            return resolveDecimals(this.decimals);

        },
        displayValue() {

            return this.isEditing ? this.localValue : this.formattedValue;

        },
        textBottom() {

            try {

                return this.textBottomInfo.length > 0 ? this.textBottomInfo[0] : "";

            }catch(e) {

                return e;

            }

        },
        divSizeClass() {

            return `form-group col-xl-${this.xl} col-lg-${this.lg} col-md-${this.md} col-sm-${this.sm}`;

        }
    },
    watch: {
        modelValue: {
            immediate: true,
            handler(value) {

                if(!this.isEditing) {

                    this.localValue = this.editableValue(value);

                }

            }
        }
    },
    methods: {
        editableValue(value) {

            if(!isDefined({value})) return "";

            return String(value).replace(/,/g, "");

        },
        handleTyping(value) {

            this.localValue = value;

            // If value is completely empty, emit null
            const trimmedValue = String(value ?? "").trim();

            if(trimmedValue === "") {

                this.emitValue({reset: false, result: null});
                return;

            }

            // Allow typing "-" or "." while editing (will be validated on blur)
            // Update without validation (will be validated on blur)
            this.emitValue({reset: false, result: value});

        },
        updateValue(value) {

            const isDefinedMinValue = isDefined({value: this.minValue});
            const isDefinedMaxValue = isDefined({value: this.maxValue});

            let maxValue = isDefinedMaxValue ? this.maxValue : generalConfig.forms.inputs.maxValue;
            let minValue = isDefinedMinValue ? this.minValue : generalConfig.forms.inputs.minValue;

            if(this.hasNegative && !isDefinedMinValue) {

                minValue = -maxValue;

            }

            let valueString = String(value ?? "").trim().replace(/,/g, "");

            // If value is empty, emit null to keep field empty
            if(valueString === "" || valueString === "-" || valueString === ".") {

                this.emitValue({reset: false, result: null});
                this.localValue = "";
                return;

            }

            // Validate numeric format
            const hasFormattedNumber = this.hasNegative ? /^-?\d+(\.\d+)?$/.test(valueString) : /^\d+(\.\d+)?$/.test(valueString);
            const hasDecimalInitNumber = this.decimalPlaces > 0 && (this.hasNegative ? /^-?\d+\.$/.test(valueString) : /^\d+\.$/.test(valueString));

            if(hasFormattedNumber || hasDecimalInitNumber) {

                if(!isNumber({value: valueString, minValue: this.hasNegative ? -maxValue : 0})) {

                    // If not a valid number, keep current value or null
                    const result = isDefined({value: this.modelValue}) ? this.modelValue : null;
                    this.localValue = this.editableValue(result);
                    this.emitValue({reset: false, result});
                    return;

                }

                let numericValue = Number(valueString);

                // Apply min/max limits
                if(numericValue < minValue) {

                    numericValue = minValue;

                }else if(numericValue > maxValue) {

                    numericValue = maxValue;

                }

                const result = Number(fixedNumber(numericValue, this.decimalPlaces));
                this.localValue = String(result);
                this.emitValue({reset: false, result});

            }else {

                // Invalid format, keep current value if exists, otherwise null
                const result = isDefined({value: this.modelValue}) ? this.modelValue : null;

                this.localValue = this.editableValue(result);
                this.emitValue({reset: false, result});

            }

        },
        emitValue({reset = true, result}) {

            if(reset) {

                this.$emit("update:modelValue", null);

                this.$nextTick(() => {
                    this.$emit("update:modelValue", result);
                    this.$emit("input", result);
                    this.$emit("change", result);
                });

            }else {

                this.$emit("update:modelValue", result);
                this.$emit("input", result);
                this.$emit("change", result);

            }

        },
        handleFocus() {

            this.isEditing = true;
            this.localValue = this.editableValue(this.modelValue);

        },
        handleBlur() {

            this.updateValue(this.localValue);
            this.isEditing = false;

        },
        handleEnterKey() {

            this.updateValue(this.localValue);
            this.$emit("enterKeyPressed");

        },
        handleKeyDown(event) {

            let allowedKeys = ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0", "ArrowLeft", "ArrowRight", "Backspace", "Tab"];

            if(this.hasNegative) {

                allowedKeys.push("-");

            }

            if(this.decimalPlaces > 0) {

                allowedKeys.push(".");

            }

            if(!allowedKeys.includes(event.key)) {

                event.preventDefault();

            }

        }
    }
};
</script>

<style scoped>
    /* Chrome, Safari, Edge, Opera */
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Firefox */
    input[type=number] {
        -moz-appearance: textfield;
        appearance: textfield;
    }
</style>
