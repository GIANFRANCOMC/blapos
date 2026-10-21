<template>
    <div class="br-template-file-picker">
        <span class="br-template-file-picker__name" :title="fileName || placeholder">
            {{ busy ? busyText : (fileName || placeholder) }}
        </span>

        <div class="br-template-file-picker__actions">
            <a
                :href="templateHref || '#'"
                class="br-template-file-picker__action"
                :aria-disabled="downloadDisabled ? 'true' : null"
                :tabindex="downloadDisabled ? -1 : null"
                @click.prevent="downloadTemplate">
                <i class="fa-solid fa-download" aria-hidden="true"></i>
                Descargar plantilla
            </a>

            <input
                :id="id"
                type="file"
                class="br-template-file-picker__input visually-hidden"
                :accept="accept"
                :aria-describedby="describedBy || null"
                aria-label="Subir plantilla"
                :disabled="uploadDisabled"
                @change="selectFile">

            <label
                :for="id"
                class="br-template-file-picker__action br-template-file-picker__upload"
                :class="{'is-disabled': uploadDisabled}">
                <i class="fa-solid fa-upload" aria-hidden="true"></i>
                Subir plantilla
            </label>
        </div>
    </div>
</template>

<script>
export default {
    name: "TemplateFilePicker",
    emits: ["download", "select"],
    props: {
        id: {type: String, required: true},
        templateHref: {type: String, default: ""},
        fileName: {type: String, default: ""},
        placeholder: {type: String, default: "Ninguna plantilla seleccionada"},
        busyText: {type: String, default: "Revisando archivo..."},
        accept: {type: String, default: ".xlsx,.xls,.csv"},
        describedBy: {type: String, default: ""},
        busy: {type: Boolean, default: false},
        downloadDisabled: {type: Boolean, default: false},
        uploadDisabled: {type: Boolean, default: false}
    },
    methods: {
        downloadTemplate() {
            if(!this.downloadDisabled) this.$emit("download");
        },
        selectFile(event) {
            const file = event.target.files?.[0];

            event.target.value = "";

            if(file) this.$emit("select", file);
        }
    }
};
</script>
