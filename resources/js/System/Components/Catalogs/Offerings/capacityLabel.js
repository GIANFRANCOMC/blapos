import {separatorNumber} from "../../../../Shared/Numbers.js";

export function capacityLabel(record) {
    return record.capacity_limit == null ? "—" : separatorNumber(record.capacity_limit, 0);
}
