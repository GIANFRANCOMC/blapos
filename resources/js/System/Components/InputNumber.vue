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
import {isDefined} from "../Helpers/Utils.js";
import {commitNumberInput, editableNumber, isNumberInputKey, numericBounds, resolveDecimals, separatorNumber} from "../../Shared/Numbers.js";

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
            default: null
        },
        maxValue: {
            type: [String, Number],
            required: false,
            default: null
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
        editableValue: editableNumber,
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

            const bounds = numericBounds(this);
            const parsed = commitNumberInput(value, {...bounds, hasNegative: this.hasNegative, decimals: this.decimalPlaces});
            const text = editableNumber(value);
            const result = ["", "-", "."].includes(text) ? null : (parsed ?? this.modelValue ?? null);

            this.localValue = editableNumber(result);
            this.emitValue({reset: false, result});

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

            if(!isNumberInputKey(event.key, {hasNegative: this.hasNegative, decimals: this.decimalPlaces})) {

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
