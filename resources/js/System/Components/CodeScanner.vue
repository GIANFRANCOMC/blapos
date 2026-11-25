<template>
    <div class="fg-attendance-modal__body">
        <FgField v-if="cameras.length > 1" label="Cámara" v-slot="{id}">
            <div class="fg-field__control">
                <select :id="id" v-model="selectedCameraId" class="fg-input">
                    <option v-for="(camera, index) in cameras" :key="camera.id" :value="camera.id">
                        {{ camera.label || 'Cámara ' + (index + 1) }}
                    </option>
                </select>
            </div>
        </FgField>
        <div ref="scannerContainer"></div>
        <div v-if="cameras.length && showControls">
            <FgButton v-if="!isScanning" tone="secondary" icon="fa-solid fa-camera" @click="startScanner()">
                Escanear QR
            </FgButton>
            <FgButton v-else tone="secondary" icon="fa-solid fa-stop" @click="stopScanner()">
                Detener escáner
            </FgButton>
        </div>
        <p v-if="!cameras.length" class="fg-notice fg-notice--danger" role="status">No detectamos una cámara disponible.</p>
    </div>
</template>

<script>
    import * as Alerts from "../Helpers/Alerts.js";
    import FgButton from "@System/Components/Figma/FgButton.vue";
    import FgField from "@System/Components/Figma/FgField.vue";

    export default {
        name: "CodeScanner",
        components: {FgButton, FgField},
        emits: ["result"],
        computed: {
            canScan: function() {

                return this.limitScan == -1 || this.counterScan < this.limitScan;

            }
        },
        props: {
            showControls: {
                type: Boolean,
                required: false,
                default: true
            },
            qrbox: {
                type: Number,
                required: false,
                default: 250
            },
            fps: {
                type: Number,
                required: false,
                default: 10
            },
            limitScan: {
                type: Number,
                required: false,
                default: -1
            },
            canProcess: {
                type: Boolean,
                required: false,
                default: true
            }
        },
        data() {
            return {
                scanner: null,
                isScanning: false,
                counterScan: 0,
                cameras: [],
                selectedCameraId: null
            };
        },
        methods: {
            async startScanner() {

                const config = {
                    fps: this.fps,
                    qrbox: { width: this.qrbox, height: this.qrbox }
                };

                const scannerId = this.$refs.scannerContainer.id || "html5qr-code";

                if(!this.scanner) {

                    this.scanner = new window.Html5Qrcode(scannerId);

                }

                const cameras = this.cameras;

                if(cameras.length > 0) {

                    this.scanner.start(
                        this.selectedCameraId,
                        config,
                        (decodedText, decodedResult) => {

                            if(!this.canProcess) return;

                            if(this.limitScan != -1) {

                                this.counterScan++;

                            }

                            this.$emit("result", decodedText, decodedResult);

                            if(!this.canScan) {

                                this.stopScanner();

                            }

                        }
                    );

                    this.isScanning = true;

                }else {

                    Alerts.generateAlert({type: "error", msgContent: `No se detectaron cámaras.`});

                }

            },
            stopScanner() {

                if(this.scanner && this.isScanning) {

                    this.scanner.stop()
                    .then(() => {

                        this.isScanning = false;

                    })
                    .catch(err => {

                        console.error("Error al detener escáner", err);

                    });

                }

            },
            decrementScanCounter() {

                if(this.counterScan != -1) {

                    this.counterScan--;

                }

            }
        },
        mounted() {

            // Set force: ID
            if(!this.$refs.scannerContainer.id) {

                this.$refs.scannerContainer.id = "html5qr-code";

            }

            window.Html5Qrcode
            .getCameras()
            .then(cameras => {

                this.cameras = cameras;

                if(cameras.length > 0) {

                    let camerasBack = cameras.filter(e => (e.label.toLowerCase()).includes("back"));

                    this.selectedCameraId = camerasBack.length > 0 ? camerasBack[0].id :cameras[0].id;

                }

            })
            .catch(err => {

                console.error("Error al obtener cámaras:", err);
                Alerts.toastrs({type: "error", subtitle: "No se detectaron cámaras."});
                // Alerts.generateAlert({ type: "error", msgContent: `No se detectaron cámaras.` });

            });

        },
        watch: {
            selectedCameraId(newVal, oldVal) {

                if(this.isScanning && newVal !== oldVal) {

                    this.stopScanner();

                }

            }
        }
    };
</script>
