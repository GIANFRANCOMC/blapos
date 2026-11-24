<template>
    <div class="modal fade fg-modal fg-modal--compact" :id="modalId" :aria-labelledby="modalId + '-title'" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content fg-modal__content">
                <div class="modal-header fg-modal__header">
                    <h2 :id="modalId + '-title'" class="modal-title fg-modal__title">
                        {{ title ?? data?.serie_sequential ?? "Comprobante de venta" }}
                    </h2>
                    <button type="button" class="fg-button fg-button--icon" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body fg-modal__body">
                    <div class="row g-1">
                        <slot name="messageAppend"></slot>
                    </div>
                    <div class="row justify-content-center g-1 mt-1">
                        <div class="col-xl-3 col-lg-3 col-md-3 col-sm-3 mx-2" v-if="a4">
                            <div class="text-center">
                                <button
                                    type="button"
                                    class="fg-button fg-button--secondary"
                                    @click="exportpp({type: 'a4'})" aria-label="Imprimir en A4">
                                    <i class="fa fa-print" aria-hidden="true"></i>
                                </button>
                                <span class="fg-table-note">A4</span>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-3 col-md-3 col-sm-3 mx-2" v-if="mm80">
                            <div class="text-center">
                                <button
                                    type="button"
                                    class="fg-button fg-button--secondary"
                                    @click="exportpp({type: 'mm80'})" aria-label="Imprimir ticket de 80 mm">
                                    <i class="fa-solid fa-note-sticky" aria-hidden="true"></i>
                                </button>
                                <span class="fg-table-note">80 mm</span>
                            </div>
                        </div>
                        <slot name="extraGroupAppend"></slot>
                    </div>
                </div>
                <div class="modal-footer fg-modal__footer">
                    <button type="button" class="fg-button fg-button--secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import * as Requests  from "../../Helpers/Requests.js";

export default {
    name: "PrintSale",
    emits: [],
    props: {
        modalId: {
            type: String,
            required: true
        },
        title: {
            type: String,
            required: false
        },
        a4: {
            type: Boolean,
            required: false,
            default: true
        },
        mm80: {
            type: Boolean,
            required: false,
            default: true
        },
        data: {
            required: false
        }
    },
    computed: {
        //
    },
    methods: {
        async exportpp({type}) {

            let data = this.data;

            const url = await Requests.saleReportShareUrl({document: data?.id, type});

            if(url) window.open(url, "_blank", "noopener");

        }
    }
};
</script>
