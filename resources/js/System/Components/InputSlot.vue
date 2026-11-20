<template>
    <FgField  :label="title" :required="isRequired" :input-id="inputId || undefined" :error="hasTextBottom ? textBottomInfo : null">
        <template #action><slot name="default"/><slot name="defaultAppend"/></template>
        <div v-if="isInputGroup && ($slots.inputGroupPrepend || $slots.inputGroupAppend)" class="fg-field__control"><slot name="inputGroupPrepend"/><slot name="input"/><slot name="inputGroupAppend"/></div>
        <slot v-else name="input"/>
    </FgField>


</template>

<script>
import FgField from "./Figma/FgField.vue";
import { generalConfig } from "../Helpers/Constants.js";

export default {
    components: {FgField},
    name: "InputSlot",
    emits: [],
    props: {
        inputId: {type: String, default: ""},
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
        isInputGroup: {
            type: Boolean,
            required: false,
            default: true
        },
        divInputClass:{
            type: Array,
            required: false,
            default: []
        },
        inputClass:{
            type: Array,
            required: false,
            default: ["fg-input"]
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

    }
};
</script>
