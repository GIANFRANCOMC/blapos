export const DEFAULT_DECIMAL_PRECISION = 2;
export const MAX_DECIMAL_PRECISION = 3;

const formatters = new Map();

export function normalizePrecision(value) {

    const precision = typeof value === "string" && /^[+-]?\d+$/.test(value.trim())
        ? Number(value)
        : value;

    return Number.isInteger(precision)
        ? Math.max(0, Math.min(MAX_DECIMAL_PRECISION, precision))
        : DEFAULT_DECIMAL_PRECISION;

}

export function formatDecimalNumber(value, precision, useGrouping = false) {

    const key = `${precision}:${useGrouping}`;

    if(!formatters.has(key)) {

        formatters.set(key, new Intl.NumberFormat("en-US", {
            minimumFractionDigits: precision,
            maximumFractionDigits: precision,
            useGrouping
        }));

    }

    const rounded = formatters.get(key).format(value);

    return /^-0(?:\.0+)?$/.test(rounded) ? rounded.slice(1) : rounded;

}
