<template>
    <Teleport to="body">
    <div
        :class="['modal', 'fade', 'br-entity-modal', modalClass]"
        :id="modalId"
        :data-bs-backdrop="staticBackdrop ? 'static' : null"
        :data-bs-keyboard="keyboard ? null : 'false'"
        tabindex="-1"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="titleId">
        <div class="modal-dialog modal-dialog-centered" :class="[modalSize, dialogClass]" role="document">
            <div class="modal-content">
                <div class="modal-header br-entity-modal__header">
                    <div>
                        <p
                            v-if="eyebrow"
                            class="br-entity-modal__eyebrow mb-1"
                            v-text="eyebrow"></p>
                        <h2
                            :id="titleId"
                            class="modal-title br-entity-modal__title br-entity-modal__title--form"
                            v-text="title"></h2>
                        <small
                            v-if="subtitle"
                            class="br-entity-modal__subtitle"
                            v-text="subtitle"></small>
                        <slot name="header"></slot>
                    </div>
                    <button
                        type="button"
                        class="br-modal-close"
                        data-bs-dismiss="modal"
                        :disabled="closeDisabled"
                        aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body br-entity-modal__body" :class="bodyClass">
                    <slot name="body">
                        <slot></slot>
                    </slot>
                </div>
                <div v-if="showFooter" class="modal-footer br-entity-modal__footer" :class="footerClass">
                    <slot name="footerClose">
                        <button type="button" class="br-btn br-btn-cancel" data-bs-dismiss="modal">
                            {{ closeButtonText }}
                        </button>
                    </slot>
                    <slot name="footer">
                        <button
                            v-if="showSubmit"
                            type="button"
                            :class="['br-btn', submitButtonClass]"
                            :disabled="submitDisabled"
                            @click="$emit('submit')">
                            <i v-if="submitButtonIcon" :class="submitButtonIcon" aria-hidden="true"></i>
                            <span>{{ submitButtonText }}</span>
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
        eyebrow: {
            type: String,
            default: ""
        },
        subtitle: {
            type: String,
            default: ""
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
        closeButtonText: {
            type: String,
            default: "Cancelar"
        },
        submitButtonText: {
            type: String,
            default: "Guardar"
        },
        submitButtonClass: {
            type: String,
            default: "br-btn-action-create"
        },
        submitButtonIcon: {
            type: String,
            default: ""
        }
    },
    computed: {
        titleId() {
            return `${this.modalId}-title`;
        }
    }
};
</script>

