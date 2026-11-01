import {createApp} from "vue";
import App from "./main.vue";
import Breadcrumb from "@System/Components/Breadcrumb.vue";
import InputBase from "@System/Components/InputBase.vue";
import InputDate from "@System/Components/InputDate.vue";
import InputSelect from "@System/Components/InputSelect.vue";
import InputText from "@System/Components/InputText.vue";
import "../../../../../css/System/Pages/Essentials/account/main.css";
import "../../../../../css/System/Pages/Essentials/account/skeleton.css";

createApp(App)
    .component("Breadcrumb", Breadcrumb)
    .component("InputBase", InputBase)
    .component("InputDate", InputDate)
    .component("InputSelect", InputSelect)
    .component("InputText", InputText)
    .mount("#app");
