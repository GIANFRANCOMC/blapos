import {separatorNumber} from "../../../../Shared/Numbers.js";

export function commissionLabel(record) {
    const value = Number(record.commission_value);
    if(!(value > 0)) return null;

    if(record.commission_type === "percentage") return `${separatorNumber(value)}%`;
    if(record.commission_type === "fixed") return `${record.currency?.sign ?? ""} ${separatorNumber(value)}`.trim();

    return null;
}
