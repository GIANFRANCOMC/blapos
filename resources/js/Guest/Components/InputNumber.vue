<template>
    <template v-if="hasDiv">
        <div :class="[...divClass, divSizeClass]">
            <slot name="default"></slot>
            <label v-if="!!title" v-text="title" :class="[...titleClass]"></label>
            <label v-if="isRequired" v-text="requiredLabel" :class="[...requiredClass]"></label>
            <slot name="defaultAppend"></slot>
            <div class="input-group">
                <slot name="inputGroupPrepend"></slot>
                <input
                    type="text"
                    :value="isEditing ? modelValue : formattedValue"
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
                <small v-if="textBottomType === 'first'" :class="[...textBottomClass]" v-text="textBottom"></small>
            </div>
        </div>
    </template>
    <template v-else>
        <slot name="default"></slot>
        <label v-if="!!title" v-text="title" :class="[...titleClass]"></label>
        <label v-if="isRequired" v-text="requiredLabel" :class="[...requiredClass]"></label>
        <slot name="defaultAppend"></slot>
        <div class="input-group">
            <slot name="inputGroupPrepend"></slot>
            <input
                type="text"
                :value="isEditing ? modelValue : formattedValue"
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
            <small v-if="textBottomType === 'first'" :class="[...textBottomClass]" v-text="textBottom"></small>
        </div>
    </template>
</template>

<script>
import { generalConfig } from "../Helpers/Constants.js";
import { fixedNumber, isDefined, resolveDecimals, separatorNumber } from "../Helpers/Utils.js";

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
            isEditing: false
        };
    },
    computed: {
        formattedValue() {

            return separatorNumber(this.modelValue, this.decimalPlaces);

        },
        decimalPlaces() {

            return resolveDecimals(this.decimals);

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
    methods: {
        handleTyping(value) {

            // Update without validation
            this.emitValue({reset: false, result: value})

        },
        updateValue(value) {

            const isDefinedMinValue = isDefined({value: this.minValue});
            const isDefinedMaxValue = isDefined({value: this.maxValue});

            let maxValue = isDefinedMaxValue ? this.maxValue : generalConfig.forms.inputs.maxValue;
            let minValue = isDefinedMinValue ? this.minValue : generalConfig.forms.inputs.minValue;

            if(this.hasNegative && !isDefinedMinValue) {

                minValue = -maxValue;

            }

            const defaultValue = minValue;

            let valueString = String(value).trim();

            if(valueString === "") {

                this.emitValue({reset: false, result: defaultValue});

            }else {

                const hasFormattedNumber = this.hasNegative ? /^-?\d+(\.\d+)?$/.test(valueString) : /^\d+(\.\d+)?$/.test(valueString); // Case: 1  2  3.1  5.67  0.329
                const hasDecimalInitNumber = this.decimalPlaces > 0 && (this.hasNegative ? /^-?\d+\.$/.test(valueString) : /^\d+\.$/.test(valueString)); // Case: With decimals (Input: 12. 3134. 23461.)

                if(this.hasNegative && value == "-") {

                    //

                }else if(hasFormattedNumber || hasDecimalInitNumber) {

                    let numericValue = Number(value);

                    if(isNaN(numericValue)) {

                        this.emitValue({reset: false, result: defaultValue});

                    }else if(numericValue < minValue) {

                        this.emitValue({reset: false, result: minValue});

                    }else if(numericValue > maxValue) {

                        this.emitValue({reset: false, result: maxValue});

                    }else {

                        this.emitValue({reset: false, result: Number(fixedNumber(numericValue, this.decimalPlaces))});

                    }

                }else {

                    // console.error("Sin formato correcto");
                    this.emitValue({reset: false, result: defaultValue});

                }

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

        },
        handleBlur() {

            this.isEditing = false;
            this.updateValue(this.modelValue);

        },
        handleEnterKey() {

            this.updateValue(this.modelValue);
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
    }
</style>
