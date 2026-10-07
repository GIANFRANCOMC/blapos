<template>
    <QuickCreateTrigger
        v-if="showTrigger"
        :mode="triggerMode"
        :text="triggerText"
        :title="triggerTitle || triggerText"
        :icon="triggerIcon"
        :custom-class="triggerClass"
        :disabled="disabled || opening"
        @click="open()"/>

    <component
        :is="editorComponent"
        v-if="editorComponent"
        ref="editor"
        :initial-options="initialOptions"
        @saved="handleSaved"/>
</template>

<script>
import {markRaw} from "vue";
import QuickCreateTrigger from "@System/Components/Generics/QuickCreateTrigger.vue";
import * as Alerts from "@System/Helpers/Alerts.js";

export default {
    name: "AddProduct",
    components: {
        QuickCreateTrigger
    },
    emits: ["created", "saved"],
    props: {
        initialOptions: {
            type: Object,
            default: () => ({})
        },
        showTrigger: {
            type: Boolean,
            default: true
        },
        triggerMode: {
            type: String,
            default: "link"
        },
        triggerText: {
            type: String,
            default: "Agregar producto"
        },
        triggerTitle: {
            type: String,
            default: ""
        },
        triggerIcon: {
            type: String,
            default: "fa-solid fa-circle-plus"
        },
        triggerClass: {
            type: [String, Array, Object],
            default: ""
        },
        disabled: {
            type: Boolean,
            default: false
        }
    },
    data() {

        return {
            editorComponent: null,
            opening: false,
            creating: false
        };

    },
    methods: {
        async open(record = null) {

            if(this.disabled || this.opening) {

                return;

            }

            this.opening = true;
            this.creating = !record?.id;

            try {

                if(!this.editorComponent) {

                    const module = await import("./ProductEditor.vue");
                    this.editorComponent = markRaw(module.default);
                    await this.$nextTick();

                }

                await this.$refs.editor?.openModal(record);

            }catch(error) {

                Alerts.toastrs({type: "error", subtitle: "No fue posible abrir el formulario de producto."});

            }finally {

                this.opening = false;

            }

        },
        handleSaved(record) {

            this.$emit("saved", record);

            if(this.creating) {

                this.$emit("created", {record});

            }

        }
    }
};
</script>
