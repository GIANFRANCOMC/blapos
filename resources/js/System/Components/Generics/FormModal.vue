<template>
    <Teleport to="body">
    <div
        :class="['modal', 'fade', 'fg-modal', modalClass]"
        :id="modalId"
        :data-bs-backdrop="staticBackdrop ? 'static' : null"
        :data-bs-keyboard="keyboard ? null : 'false'"
        tabindex="-1"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="titleId">
        <div class="modal-dialog modal-dialog-centered" :class="[modalSize, dialogClass]" role="document">
            <div :class="'fg-modal__content'">
                <div :class="'fg-modal__header'">
                    <h2
                        :id="titleId"
                        :class="'fg-modal__title'"
                        v-text="title"></h2>
                    <button
                        type="button"
                        :class="'fg-button fg-button--icon'"
                        data-bs-dismiss="modal"
                        :disabled="closeDisabled"
                        aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div :class="['fg-modal__body', bodyClass]">
                    <slot name="body">
                        <slot></slot>
                    </slot>
                </div>
                <div v-if="showFooter" :class="['fg-modal__footer', footerClass]">
                    <slot name="footerClose">
                        <button type="button" :class="'fg-button fg-button--secondary'" :disabled="closeDisabled" data-bs-dismiss="modal">
                            Cerrar
                        </button>
                    </slot>
                    <slot name="footer">
                        <button
                            v-if="showSubmit"
                            type="button"
                            :class="['fg-button']"
                            :disabled="submitDisabled"
                            @click="$emit('submit')">
                            <i v-if="submitButtonIcon" :class="submitButtonIcon" aria-hidden="true"></i>
                            <span>{{ resolvedSubmitButtonText }}</span>
                        </button>
                    </slot>
                </div>
            </div>
        </div>
    </div>
    </Teleport>
</template>

<script>
export default {
    name: "FormModal",
    emits: ["submit"],
    props: {
        modalId: {
            type: String,
            required: true
        },
        staticBackdrop: {
            type: Boolean,
            default: true
        },
        keyboard: {
            type: Boolean,
            default: true
        },
        closeDisabled: {
            type: Boolean,
            default: false
        },
        title: {
            type: String,
            required: true
        },
        modalSize: {
            type: String,
            default: ""
        },
        modalClass: {
            type: [String, Array, Object],
            default: ""
        },
        dialogClass: {
            type: [String, Array, Object],
            default: ""
        },
        bodyClass: {
            type: [String, Array, Object],
            default: ""
        },
        footerClass: {
            type: [String, Array, Object],
            default: ""
        },
        showFooter: {
            type: Boolean,
            default: true
        },
        showSubmit: {
            type: Boolean,
            default: true
        },
        submitDisabled: {
            type: Boolean,
            default: false
        },
        submitButtonText: {
            type: String,
            default: ""
        },
        submitButtonClass: {
            type: String,
            default: "fg-button"
        },
        submitButtonIcon: {
            type: String,
            default: ""
        }
    },
    computed: {
        resolvedSubmitButtonText() {

            if(this.submitButtonText) return this.submitButtonText;

            const entity = this.title.trim().match(/^(?:AGREGAR|EDITAR|CREAR|REGISTRAR)\s+(.+)$/i)?.[1];

            return entity ? `Guardar ${entity.toLocaleLowerCase("es-PE")}` : "Guardar";

        },
        titleId() {
            return `${this.modalId}-title`;
        }
    }
};
</script>

