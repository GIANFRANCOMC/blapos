import {createApp} from "vue";

import App from "./main.vue";
import InputText from "@System/Components/InputText.vue";
import Paginator from "@System/Components/Paginator.vue";

createApp(App)
    .component("InputText", InputText)
    .component("Paginator", Paginator)
    .mount("#app");
