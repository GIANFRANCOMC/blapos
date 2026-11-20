<template>
    <FgField  :label="title" :required="isRequired" :input-id="controlId" :error="hasTextBottom ? textBottomInfo : null">
        <template #action><slot name="default"/><slot name="defaultAppend"/></template>
        <div :class="['fg-field__control', {'fg-field__control--invalid': hasError}]">
            <slot name="inputGroupPrepend"/>
            <input :id="controlId" type="text" inputmode="decimal" :value="displayValue" class="fg-input" :placeholder="placeholder" :disabled="disabled"
                :aria-label="ariaLabel || undefined" :aria-required="isRequired || undefined" :aria-invalid="hasError || undefined"
                :aria-describedby="showError ? controlId + '-error' : undefined"
                @focus="handleFocus" @blur="handleBlur" @input="handleTyping($event.target.value)" @keydown="handleKeyDown" @keyup.enter="handleEnterKey"/>
            <slot name="inputGroupAppend"/>
        </div>
    </FgField>


</template>

<script>
import FgField from "./Figma/FgField.vue";
import {useId} from "vue";
import { generalConfig } from "../Helpers/Constants.js";
import {commitNumberInput, editableNumber, isNumberInputKey, numberInputDraft, numericBounds, resolveDecimals, separatorNumber} from "../../Shared/Numbers.js";

export default {
    components: {FgField},
    name: "InputNumber",
    emits: ["enterKeyPressed", "update:modelValue", "input", "change"],
    setup() {
        const id = useId();
        return {controlId: id, errorId: "fg-number-error-" + id};
    },
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
            default: ["fg-field__label"]
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
            default: ["fg-required"]
        },
        // Input - Props
        inputClass:{
            type: Array,
            required: false,
            default: ["fg-input"]
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
        ariaLabel: {
            type: String,
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
        showError() {
            return this.hasError && this.hasTextBottom && this.textBottomType === "first";
        },
        formattedValue() {

            if(this.modelValue == null || this.modelValue === "") {

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

            this.emitValue({reset: false, result: numberInputDraft(value, this.hasNegative)});

        },
        updateValue(value) {

            const bounds = numericBounds(this);
            const result = commitNumberInput(value, {...bounds, hasNegative: this.hasNegative, decimals: this.decimalPlaces});

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

            if(event.ctrlKey || event.metaKey || event.isComposing) return;

            if(!isNumberInputKey(event.key, {hasNegative: this.hasNegative, decimals: this.decimalPlaces})) {

                event.preventDefault();

            }

        }
    }
};
</script>
