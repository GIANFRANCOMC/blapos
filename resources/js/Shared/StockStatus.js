import labels from "../../stock-statuses.json" with {type: "json"};
import {compareNumbers} from "./Numbers.js";

export {labels};

export function fromLevels(quantity, minimum) {
    if(compareNumbers(quantity, 0) <= 0) return "empty";
    if(compareNumbers(quantity, minimum) <= 0) return "low";

    return "healthy";
}

export function forProduct(total, hasWarehouseAlerts) {
    if(fromLevels(total, 0) === "empty") return "empty";

    return hasWarehouseAlerts ? "low" : "healthy";
}
