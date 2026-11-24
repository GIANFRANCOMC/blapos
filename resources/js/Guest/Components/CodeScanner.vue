<template>
    <div class="fg-guest-code-scanner">
        <select v-model="selectedCameraId" class="form-select mb-2" v-if="cameras.length > 1">
            <option v-for="camera in cameras" :key="camera.id" :value="camera.id">
                {{ camera.label || `Cámara ${camera.id}` }}
            </option>
        </select>

        <div ref="scannerContainer" class="fg-guest-code-scanner__box"></div>

        <div v-if="cameras.length > 0 && showControls" :class="['d-flex gap-2', isScanning ? 'mt-2' : '']">
            <button @click="startScanner()" v-if="!isScanning" type="button" class="btn btn-success btn-sm waves-effect">
                <i class="fa-solid fa-camera" aria-hidden="true"></i>
                <span class="ms-2">Escanear QR</span>
            </button>
            <button @click="stopScanner()" v-if="isScanning" type="button" class="btn btn-danger btn-sm waves-effect">
                <i class="fa-solid fa-stop" aria-hidden="true"></i>
                <span class="ms-2">Detener escáner</span>
            </button>
        </div>

        <div v-if="cameras.length === 0" class="d-flex">
            <span class="alert alert-danger w-100">No se detectó una cámara disponible.</span>
        </div>
    </div>
</template>

<script>
import * as Alerts from "../Helpers/Alerts.js";

export default {
    name: "CodeScanner",
    emits: ["result"],
    props: {
        showControls: {
            type: Boolean,
            default: true
        },
        qrbox: {
            type: Number,
            default: 250
        },
        fps: {
            type: Number,
            default: 10
        },
        limitScan: {
            type: Number,
            default: -1
        },
        canProcess: {
            type: Boolean,
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
    computed: {
        canScan() {
            return this.limitScan === -1 || this.counterScan < this.limitScan;
        }
    },
    methods: {
        async startScanner() {
            if(!window.Html5Qrcode) {
                Alerts.generateAlert({
                    type: "error",
                    msgContent: "No se pudo cargar el lector QR. Actualiza la página e intenta nuevamente."
                });
                return;
            }

            const config = {
                fps: this.fps,
                qrbox: {width: this.qrbox, height: this.qrbox}
            };
            const scannerId = this.$refs.scannerContainer.id || "html5qr-code";

            if(!this.scanner) this.scanner = new window.Html5Qrcode(scannerId);

            if(this.cameras.length === 0) {
                Alerts.generateAlert({type: "error", msgContent: "No se detectaron cámaras."});
                return;
            }

            try {
                await this.scanner.start(
                    this.selectedCameraId,
                    config,
                    (decodedText, decodedResult) => {
                        if(!this.canProcess) return;

                        if(this.limitScan !== -1) this.counterScan++;

                        this.$emit("result", decodedText, decodedResult);

                        if(!this.canScan) this.stopScanner();
                    }
                );

                this.isScanning = true;
            }catch(error) {
                console.error("Error al iniciar escáner", error);
                Alerts.generateAlert({
                    type: "error",
                    msgContent: "No pudimos iniciar la cámara. Revisa los permisos del navegador."
                });
            }
        },
        stopScanner() {
            if(!this.scanner || !this.isScanning) return;

            this.scanner.stop()
                .then(() => {
                    this.isScanning = false;
                })
                .catch(error => {
                    console.error("Error al detener escáner", error);
                });
        },
        decrementScanCounter() {
            if(this.limitScan !== -1 && this.counterScan > 0) this.counterScan--;
        }
    },
    mounted() {
        if(!this.$refs.scannerContainer.id) this.$refs.scannerContainer.id = "html5qr-code";

        if(!window.Html5Qrcode) {
            Alerts.toastrs({type: "error", subtitle: "No se pudo cargar el lector QR."});
            return;
        }

        window.Html5Qrcode
            .getCameras()
            .then(cameras => {
                this.cameras = cameras;

                if(cameras.length > 0) {
                    const backCameras = cameras.filter(camera => camera.label.toLowerCase().includes("back"));
                    this.selectedCameraId = backCameras.length > 0 ? backCameras[0].id : cameras[0].id;
                }
            })
            .catch(error => {
                console.error("Error al obtener cámaras:", error);
                Alerts.toastrs({type: "error", subtitle: "No se detectaron cámaras."});
            });
    },
    watch: {
        selectedCameraId(newValue, oldValue) {
            if(this.isScanning && newValue !== oldValue) this.stopScanner();
        }
    }
};
</script>
