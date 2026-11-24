import {createApp} from "vue";
import CommercialPerformance from "./main.vue";

const element = document.getElementById("app");
createApp(CommercialPerformance, {...element.dataset}).mount(element);
