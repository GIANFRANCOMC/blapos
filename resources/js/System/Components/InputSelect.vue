<template>
    <FgField :label="title" :required="isRequired" :input-id="controlId" :error="hasTextBottom ? textBottomInfo : null" :help="help">
        <template #action><slot name="default"/></template>
        <template #default="{describedBy}">
            <div class="fg-field__control">
                <slot name="inputGroupPrepend"/>
                <select :id="controlId" class="fg-input" :value="modelValue" :disabled="disabled" :aria-required="isRequired || undefined" :aria-invalid="hasError || undefined" :aria-describedby="describedBy" @input="updateValue($event.target.value)" @change="handleChange">
                    <option v-if="hasDefaultOption" :value="defaultOptionValue">{{ defaultOptionLabel }}</option>
                    <option v-for="(option,index) in options" :key="index" :value="option.code">{{ option.label }}</option>
                </select>
                <slot name="inputGroupAppend"/>
            </div>
        </template>
    </FgField>
</template>
<script>
import FgField from "./Figma/FgField.vue";
import {useId} from "vue";
import { generalConfig } from "../Helpers/Constants.js";

export default {
    name: "InputSelect",
    components: {FgField},
    setup() {return {controlId: useId()};},
    emits: ["changeEvent", "update:modelValue"],
    props: {
        help: {type: String, default: ""},
        modelValue: {
            type: [String, Number],
            default: ""
        },
        // Options
        hasDefaultOption:{
            type: Boolean,
            required: false,
            default: true
        },
        defaultOptionLabel: {
            type: String,
            required: false,
            default: "Seleccione"
        },
        defaultOptionValue: {
            type: String,
            required: false,
            default: ""
        },
        options: {
            type: Array,
            required: false,
            default: []
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

        },
        handleChange() {

            this.$emit("changeEvent");

        }
    }
};
</script>
