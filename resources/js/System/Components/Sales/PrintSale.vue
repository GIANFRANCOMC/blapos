<template>
    <div class="modal fade" :id="modalId" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header br-modal-header">
                    <h5 class="modal-title text-uppercase fw-bold mb-0">
                        <span class="fw-semibold" v-text="'Documento:'"></span>
                        <span class="ms-2" v-text="title ?? data?.serie_sequential"></span>
                    </h5>
                    <button type="button" class="btn-header-modal" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa fa-times icon-close-modal" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row g-1">
                        <slot name="messageAppend"></slot>
                    </div>
                    <div class="row justify-content-center g-1 mt-1">
                        <div class="col-xl-3 col-lg-3 col-md-3 col-sm-3 mx-2" v-if="a4">
                            <div class="text-center">
                                <button
                                    type="button"
                                    class="br-btn br-btn-sm br-btn-secondary waves-effect p-3 rounded mb-1 d-inline-flex align-items-center justify-content-center"
                                    @click="exportpp({type: 'a4'})">
                                    <i class="fa fa-print" aria-hidden="true"></i>
                                </button>
                                <span class="d-block fw-semibold">A4</span>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-3 col-md-3 col-sm-3 mx-2" v-if="mm80">
                            <div class="text-center">
                                <button
                                    type="button"
                                    class="br-btn br-btn-sm br-btn-secondary waves-effect p-3 rounded mb-1 d-inline-flex align-items-center justify-content-center"
                                    @click="exportpp({type: 'mm80'})">
                                    <i class="fa-solid fa-note-sticky" aria-hidden="true"></i>
                                </button>
                                <span class="d-block fw-semibold">80MM</span>
                            </div>
                        </div>
                        <slot name="extraGroupAppend"></slot>
                    </div>
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

            window.open(url, "_blank");

        }
    }
};
</script>
