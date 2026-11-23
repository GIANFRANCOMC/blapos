<template>
    <span v-if="showSummary" class="fg-selection-summary">
        <span class="fg-selection-summary__value" :title="modelValue.label">{{ modelValue.label }}</span>
        <button
            ref="change"
            type="button"
            class="fg-link"
            :disabled="disabled"
            :aria-label="`Cambiar producto ${modelValue.label}`"
            @click="changeProduct">
            Cambiar
        </button>
    </span>
    <FgSelect
        v-else
        ref="select"
        :model-value="modelValue"
        :options="options"
        :get-option-key="option => option.code"
        :aria-label="ariaLabel"
        :clearable="false"
        :searchable="!disabled"
        :filterable="!remote"
        :loading="loading"
        :dropdown-should-open="dropdownShouldOpen"
        :calculate-position="positionDropdown"
        :placeholder="placeholder"
        :disabled="disabled"
        @update:modelValue="selectProduct"
        @open="$emit('open')"
        @close="editing = false"
        @search="handleSearch"
        @keydown.esc="cancelChange">
        <template #no-options><slot name="no-options"><SelectNoOptions/></slot></template>
        <template v-if="$slots['list-footer']" #list-footer><slot name="list-footer"/></template>
    </FgSelect>
</template>

<script>
import FgSelect from "@System/Components/Figma/FgSelect.vue";
import {positionSelectDropdown} from "@System/Helpers/SelectOverlay.js";
import SelectNoOptions from "./SelectNoOptions.vue";

export default {
    name: "ProductSelect",
    components: {FgSelect, SelectNoOptions},
    emits: ["update:modelValue", "open", "search"],
    props: {
        modelValue: {type: Object, default: null},
        options: {type: Array, default: () => []},
        remote: {type: Boolean, default: false},
        summarizeSelection: {type: Boolean, default: false},
        loading: {type: Boolean, default: false},
        placeholder: {type: String, default: ""},
        ariaLabel: {type: String, default: "Producto"},
        disabled: {type: Boolean, default: false}
    },
    data() {
        return {editing: false};
    },
    computed: {
        showSummary() {
            return this.summarizeSelection && this.modelValue && !this.editing;
        }
    },
    methods: {
        changeProduct() {
            this.editing = true;
            this.$nextTick(() => this.$refs.select?.focus({preventScroll: true}));
        },
        selectProduct(product) {
            this.$emit("update:modelValue", product);
            if(this.summarizeSelection) this.finishChange();
        },
        cancelChange(event) {
            if(!this.editing) return;

            event.stopPropagation();
            this.finishChange();
        },
        finishChange() {
            this.editing = false;
            this.$nextTick(() => this.$refs.change?.focus({preventScroll: true}));
        },
        positionDropdown(dropdown, select, position) {
            const stopPositioning = positionSelectDropdown(dropdown, select, position);
            const toggle = select.$refs.toggle;
            const scroller = this.summarizeSelection && toggle.closest(".fg-table-scroll--records");
            if(!scroller) return stopPositioning;

            const header = scroller.querySelector("thead");
            const sync = () => {
                const anchor = toggle.getBoundingClientRect();
                const bounds = scroller.getBoundingClientRect();
                const top = Math.max(bounds.top, header?.getBoundingClientRect().bottom ?? bounds.top);
                if(anchor.bottom <= top || anchor.top >= bounds.bottom || anchor.right <= bounds.left || anchor.left >= bounds.right) {
                    select.closeSearchOptions();
                    select.searchEl?.blur();
                    return;
                }

            };
            scroller.addEventListener("scroll", sync, {passive: true});
            return () => {
                stopPositioning();
                scroller.removeEventListener("scroll", sync);
            };
        },
        dropdownShouldOpen({noDrop, open, mutableLoading}) {
            return !noDrop && open && (this.remote || !mutableLoading);
        },
        handleSearch(term) {
            if(this.remote && this.$refs.select?.isOpen()) this.$emit("search", term);
        }
    }
};
</script>
