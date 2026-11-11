<template>
    <template v-if="hasDiv">
        <div :class="[...divClass, divSizeClass]">
            <slot name="default"></slot>
            <label v-if="!!title" v-text="title" :class="[...titleClass]"></label>
            <label v-if="isRequired" v-text="requiredLabel" :class="[...requiredClass]"></label>
            <div :class="['input-group', 'br-form-control-group', {'is-invalid': hasError}]">
                <slot name="inputGroupPrepend"></slot>
                <div class="position-relative w-100">
                    <select
                        ref="select"
                        :id="id"
                        :class="[...inputClass]"
                        :disabled="disabled"
                        :multiple="multiple">
                        <option v-if="hasDefaultOption" :value="defaultOptionValue" v-text="defaultOptionLabel"></option>
                        <option v-for="(option, index) in options" :value="option.code" v-text="option.label" :key="option.code ?? index" :extras="option?.extras"></option>
                    </select>
                </div>
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
        <div :class="['input-group', 'br-form-control-group', {'is-invalid': hasError}]">
            <slot name="inputGroupPrepend"></slot>
            <div class="position-relative w-100">
                <select
                    ref="select"
                    :id="id"
                    :class="[...inputClass]"
                    :disabled="disabled"
                    :multiple="multiple">
                    <option v-if="hasDefaultOption" :value="defaultOptionValue" v-text="defaultOptionLabel"></option>
                    <option v-for="(option, index) in options" :value="option.code" v-text="option.label" :key="option.code ?? index" :extras="option?.extras"></option>
                </select>
            </div>
            <slot name="inputGroupAppend"></slot>
        </div>
        <div v-if="hasTextBottom">
            <small v-if="textBottomType === 'first'" :class="[...textBottomClass, 'br-form-error']" v-text="textBottom"></small>
        </div>
    </template>
</template>

<script>
import {select2Ajax} from "./Options.js";

export default {
    name: "InputSelect2",
    emits: ["update:modelValue", "change"],
    data() {
        return {abortOptions: null};
    },
    props: {
        id: {
            type: [String],
            required: true
        },
        modelValue: {type: [String, Number, Array], default: undefined},
        origin: {type: String, default: ""},
        entity: {type: String, default: ""},
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
            default: () => []
        },
        multiple: {
            type: Boolean,
            required: false,
            default: false
        },
        // Title
        hasDiv: {
            type: Boolean,
            required: false,
            default: false
        },
        divClass: {
            type: Array,
            required: false,
            default: () => []
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
            default: () => ["form-label", "colon-at-end"]
        },
        // Aspect
        isRequired: {
            type: Boolean,
            required: false,
            default: false
        },
        requiredLabel: {
            type: String,
            required: false,
            default: "*"
        },
        requiredClass: {
            type: Array,
            required: false,
            default: () => ["text-danger", "ms-1", "fw-bold"]
        },
        inputClass:{
            type: Array,
            required: false,
            default: () => ["select2", "form-select"]
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
            default: () => ["text-danger"]
        },
        textBottomInfo: {
            type: Array,
            required: false,
            default: () => []
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
    mounted() {
        this.mountSelect();
    },
    beforeUnmount() {
        this.destroySelect();
    },
    watch: {
        options: {handler: "mountSelect", flush: "post"},
        origin: {handler: "mountSelect", flush: "post"},
        entity: {handler: "mountSelect", flush: "post"},
        modelValue: {handler: "syncValue", flush: "post"}
    },
    methods: {
        mountSelect() {
            if(!window.jQuery?.fn.select2 || !this.$refs.select) return;

            this.destroySelect();
            const select = window.jQuery(this.$refs.select);
            const ajax = this.origin && this.entity ? select2Ajax({origin: this.origin, entity: this.entity}) : null;
            this.abortOptions = ajax?.abort ?? null;
            select.select2({
                placeholder: this.defaultOptionLabel,
                dropdownParent: select.parent(),
                language: {
                    noResults: () => "Sin opciones disponibles",
                    searching: () => "Buscando...",
                    inputTooShort: () => "Escribe para buscar"
                },
                ...(ajax ? {ajax} : {})
            });
            select.on("change.blaposOptions", event => {
                this.$emit("update:modelValue", select.val());
                this.$emit("change", event);
            });
            this.syncValue();
        },
        destroySelect() {
            this.abortOptions?.();
            this.abortOptions = null;
            const select = window.jQuery?.(this.$refs.select);
            if(!select?.data("select2")) return;
            select.off(".blaposOptions").select2("destroy");
        },
        syncValue() {
            if(this.modelValue === undefined) return;
            window.jQuery?.(this.$refs.select).val(this.modelValue).trigger("change.select2");
        }
    }
};
</script>
