import {createApp} from "vue";
import App from "./main.vue";
import InputText from "@System/Components/InputText.vue";
import StatusBadge from "@System/Components/Generics/StatusBadge.vue";

createApp(App)
    .component("InputText", InputText)
    .component("StatusBadge", StatusBadge)
    .mount("#app");
