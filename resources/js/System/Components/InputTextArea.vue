<template>
    <FgField  class="fg-field--textarea" :label="title" :required="isRequired" :input-id="controlId" :error="hasTextBottom ? textBottomInfo : null">
        <template #action><slot name="default"/></template>
        <div :class="['fg-field__control', {'fg-field__control--invalid': hasError}]">
            <textarea :id="controlId" :value="modelValue" class="fg-input" :placeholder="placeholder" :disabled="disabled" :maxlength="maxlength" :rows="rows"
                :aria-required="isRequired || undefined" :aria-invalid="hasError || undefined" :aria-describedby="hasError ? controlId + '-error' : undefined" @input="updateValue($event.target.value)" @keyup.enter="handleEnterKey"/>
            <span v-if="maxlength" class="fg-field__counter">{{ String(modelValue || '').length }} / {{ maxlength }}</span>
        </div>
    </FgField>


</template>

<script>
import FgField from "./Figma/FgField.vue";
import {useId} from "vue";
import { generalConfig } from "../Helpers/Constants.js";

export default {
    name: "InputTextArea",
    components: {FgField},
    setup() {
        return {controlId: useId()};
    },
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
            default: ["form-control", "no-resize"]
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
        maxlength: {
            type: [String, Number],
            required: false,
            default: generalConfig.forms.inputs.maxlength
        },
        rows: {
            type: [String, Number],
            required: false,
            default: 4
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
    computed: {
        hasError() {

            return Boolean(this.textBottom);

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
        updateValue(value) {

            this.$emit("update:modelValue", value);
            this.$emit("input", value);
            this.$emit("change", value);

        },
        handleEnterKey() {

            this.$emit("enterKeyPressed");

        }
    }
};
</script>
