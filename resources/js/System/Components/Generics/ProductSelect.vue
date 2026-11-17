<template>
    <span v-if="showSummary" class="br-inline-text-action">
        <span class="br-inline-text-action__content" :title="modelValue.label">{{ modelValue.label }}</span>
        <button
            ref="change"
            type="button"
            class="br-link br-inline-text-action__button waves-effect"
            :disabled="disabled"
            :aria-label="`Cambiar producto ${modelValue.label}`"
            @click="changeProduct">
            Cambiar
        </button>
    </span>
    <VueSelect
        v-else
        ref="select"
        :model-value="modelValue"
        :options="options"
        :get-option-key="option => option.code"
        :class="selectClass"
        :clearable="false"
        :searchable="!disabled"
        :filterable="!remote"
        :loading="loading"
        :dropdown-should-open="dropdownShouldOpen"
        :calculate-position="positionDropdown"
        :placeholder="placeholder"
        :disabled="disabled"
        append-to-body
        @update:modelValue="selectProduct"
        @open="$emit('open')"
        @close="editing = false"
        @search="handleSearch"
        @keydown.esc="cancelChange">
        <template #no-options><slot name="no-options"><SelectNoOptions/></slot></template>
        <template v-if="$slots['list-footer']" #list-footer><slot name="list-footer"/></template>
    </VueSelect>
</template>

<script>
import VueSelect from "vue-select";
import "vue-select/dist/vue-select.css";

import SelectNoOptions from "./SelectNoOptions.vue";

export default {
    name: "ProductSelect",
    components: {VueSelect, SelectNoOptions},
    emits: ["update:modelValue", "open", "search"],
    props: {
        modelValue: {type: Object, default: null},
        options: {type: Array, default: () => []},
        remote: {type: Boolean, default: false},
        summarizeSelection: {type: Boolean, default: false},
        loading: {type: Boolean, default: false},
        placeholder: {type: String, default: ""},
        selectClass: {type: [String, Array, Object], default: ""},
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
            this.$nextTick(() => this.$refs.select?.searchEl?.focus({preventScroll: true}));
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
            Object.assign(dropdown.style, position);
            const toggle = select.$refs.toggle;
            const scroller = this.summarizeSelection && toggle.closest(".table-responsive");
            if(!scroller) return;

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

                dropdown.style.top = `${anchor.bottom + window.scrollY}px`;
                dropdown.style.left = `${anchor.left + window.scrollX}px`;
            };
            scroller.addEventListener("scroll", sync, {passive: true});
            return () => scroller.removeEventListener("scroll", sync);
        },
        dropdownShouldOpen({noDrop, open, mutableLoading}) {
            return !noDrop && open && (this.remote || !mutableLoading);
        },
        handleSearch(term) {
            if(this.remote && this.$refs.select?.open) this.$emit("search", term);
        }
    }
};
</script>
