<template>
    <div>
        <div class="overflow-auto">
            <div id="myWebCompany" ref="myWebCompany" class="card carnet-card-entity mx-auto shadow-sm border border-light" style="min-width: 330px;" :style="{ backgroundImage: backgroundImage }">
                <!-- <img :src="getAsset(config.essential.ownerApp?.assets.img.logomark, {type: 'none', back: 1})" class="carnet-watermark-entity img-fluid" width="45px" height="45px"/> -->
                <div class="card-header d-flex align-items-center justify-content-start carnet-header-entity pb-0 pt-2">
                    <span class="text-white fw-semibold text-uppercase">Visita la página oficial</span>
                </div>
                <div class="card-body d-flex flex-column align-items-center py-3" v-if="isDefined({value: config.essential.company?.logomark})">
                    <div class="carnet-qr-entity flex-shrink-0 p-2 bg-white rounded-4 shadow-sm">
                        <img :src="qrImage" alt="QR" class="img-fluid" style="width: 150px; height: 150px;"/>
                    </div>
                    <div class="mt-3 text-center text-dark text-shadow-dark-1">
                        <span class="fw-bold">
                            <!-- <i class="fa fa-warehouse"></i> -->
                            <span class="ms-2" v-text="config.essential.company?.commercial_name ?? ''"></span>
                        </span>
                    </div>
                </div>
                <div v-else class="card-body py-3">
                    <div class="alert alert-warning mb-1">
                        <i class="fa fa-warning"></i>
                        <span class="ms-2">Sin vista previa.</span>
                    </div>
                    <span class="small">Revise el isotipo.</span>
                </div>
            </div>
        </div>
        <div class="d-flex flex-wrap justify-content-center gap-2 gap-md-3 mt-3" v-if="isDefined({value: config.essential.company?.logomark})">
            <button @click="shareResource" class="btn btn-primary btn-sm waves-effect">
                <i class="fa-solid fa-share-nodes"></i>
                <span class="ms-2">Compartir</span>
            </button>
            <button @click="downloadResource" class="btn btn-success btn-sm waves-effect">
                <i class="fa fa-download"></i>
                <span class="ms-2">Descargar</span>
            </button>
        </div>
    </div>
</template>

<script>
import QRCodeStyling from "qr-code-styling";
import html2canvas from "html2canvas";

import { generalConfig } from "../../Helpers/Constants.js";
import { isDefined, getAsset } from "../../Helpers/Utils.js";

export default {
    name: "MyWebCompany",
    props: {},
    data() {
        return {
            qrImage: "",
            config: {
                ...generalConfig
            }
        };
    },
    computed: {
        backgroundImage() {

            return `url('${this.config.assets.backgrounds.images.bg2}')`;

        }
    },
    mounted() {

        if(this.isDefined({value: this.config.essential.company?.logomark})) {

            this.generateQR();

        }

    },
    methods: {
        isDefined({value}) {

            return isDefined({value});

        },
        getAsset(path, {type, back}) {

            return getAsset(path, {type, back});

        },
        generateQR() {

            const qr = new QRCodeStyling({
                width: 1000,
                height: 1000,
                data: generateMyUrl(this.config.essential.company, false, "my_web"),
                image: getAsset(this.config.essential.company?.logomark, {type: "storage"}),
                dotsOptions: {
                    color: "#000000",
                    type: "rounded"
                },
                backgroundOptions: {
                    color: "#ffffff"
                },
                imageOptions: {
                    crossOrigin: "anonymous",
                    imageSize: 0.40
                }
            });

            qr.getRawData("png")
            .then((blob) => {

                this.qrImage = URL.createObjectURL(blob);

            });

        },
        async shareResource() {

            const original = this.$refs.myWebCompany;
            const cloned = original.cloneNode(true);

            document.body.appendChild(cloned);

            // 2. Esperar a que todas las <img> (logo + QR) estén cargadas
            const imgs = Array.from(cloned.querySelectorAll("img"));
            await Promise.all(imgs.map(img => img.complete ? Promise.resolve() : new Promise(res => { img.onload = img.onerror = res; })));

            // 3. Renderizar con html2canvas a mayor resolución
            const canvas = await html2canvas(cloned, {
                scale: 2,
                useCORS: true,
                allowTaint: false,
                backgroundColor: null
            });

            // 4. Eliminar el clon
            document.body.removeChild(cloned);

            // 5. Compartir
            canvas.toBlob(async blob => {

                const file = new File([blob], "my_web.png", { type: "image/png" });

                if(navigator.canShare && navigator.canShare({ files: [file] })) {

                    try {

                        await navigator.share({title: "my_web", text: this.config.essential.company?.commercial_name, files: [file]});

                    }catch(err) {

                        console.error("Error al compartir:", err);

                    }

                }else {

                    alert("Tu navegador no soporta compartir archivos.");
                }

            }, "image/png");

        },
        async downloadResource() {

            const original = this.$refs.myWebCompany;
            const cloned = original.cloneNode(true);

            cloned.style.width  = "300px";
            cloned.style.height = "auto";

            document.body.appendChild(cloned);

            const imgs = Array.from(cloned.querySelectorAll("img"));

            await Promise.all(imgs.map(img => img.complete ? Promise.resolve() : new Promise(res => { img.onload = img.onerror = res; })));

            const canvas = await html2canvas(cloned, {
                scale: 2,
                useCORS: true,
                allowTaint: false,
                backgroundColor: null
            });

            document.body.removeChild(cloned);

            const link = document.createElement("a");
            link.href = canvas.toDataURL("image/png");
            link.download = `my_web.png`;
            link.click();

        }
    }
};
</script>

<style scoped>
.carnet-card-entity {
    max-width: 300px;
    min-width: 300px;
    background-color: #ffffff;
    border-radius: 1.5rem;
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    background-size: cover;
    background-repeat: no-repeat;
}

.carnet-header-entity {
    background: linear-gradient(135deg, #2899E5, #1A1A35);
}

.carnet-qr-entity {
    border: 3px solid #a19ca9;
    border-radius: 1rem;
    box-shadow: inset 0 0 8px rgba(157, 140, 140, 0.05);
    backdrop-filter: blur(2px);
}

.carnet-watermark-entity {
    position: absolute;
    top: 13px;
    right: 13px;
    z-index: 5;
    pointer-events: none;
}
</style>
