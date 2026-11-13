import {separatorNumber} from "../../../../Shared/Numbers.js";

export function capacityLabel(record) {
    if(!record?.capacity_control_enabled) return "Ilimitados";

    return record.capacity_limit == null ? "—" : separatorNumber(record.capacity_limit, 0);
}
