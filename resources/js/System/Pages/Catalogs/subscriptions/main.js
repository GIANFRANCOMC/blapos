import {createApp} from "vue";
import App from "./main.vue";
import InputDate from "@System/Components/InputDate.vue";
import InputNumber from "@System/Components/InputNumber.vue";
import InputText from "@System/Components/InputText.vue";
import InputTextArea from "@System/Components/InputTextArea.vue";
import StatusBadge from "@System/Components/Generics/StatusBadge.vue";

createApp(App)
    .component("InputDate", InputDate)
    .component("InputNumber", InputNumber)
    .component("InputText", InputText)
    .component("InputTextArea", InputTextArea)
    .component("StatusBadge", StatusBadge)
    .mount("#app");
