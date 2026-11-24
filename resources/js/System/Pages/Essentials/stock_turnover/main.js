import {createApp} from "vue";
import StockTurnover from "./main.vue";

const element = document.getElementById("app");
createApp(StockTurnover, {...element.dataset}).mount(element);
