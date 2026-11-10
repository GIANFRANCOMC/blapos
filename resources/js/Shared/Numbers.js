const defaults = {
    round: 2,
    maxDecimals: 3,
    minValue: 0,
    maxValue: 999999999999.999,
    maxSize: 4096
};

const formatters = new Map();
const decimalPattern = /^([+-]?)(\d*)(?:\.(\d*))?(?:e([+-]?\d+))?$/i;
const zero = {coefficient: 0n, scale: 0};
let settings;

export function numericInputConfig() {
    if(!settings) {
        settings = {...defaults, ...globalThis.window?.numericInputConfig};
        settings.round = normalizePrecision(settings.round, settings.maxDecimals);
    }

    return settings;
}

export function applyNumericInputConfig({round, maxDecimals, minValue, maxValue, maxSize} = {}) {
    const config = numericInputConfig();
    config.maxDecimals = maxDecimals ?? config.maxDecimals;
    config.round = normalizePrecision(round ?? config.round, config.maxDecimals);
    config.minValue = minValue ?? config.minValue;
    config.maxValue = maxValue ?? config.maxValue;
    config.maxSize = maxSize ?? config.maxSize;

    return config;
}

export function normalizePrecision(value, maximum = numericInputConfig().maxDecimals) {
    const precision = typeof value === "string" && /^[+-]?\d+$/.test(value.trim())
        ? Number(value)
        : value;

    return Number.isInteger(precision)
        ? Math.max(0, Math.min(maximum, precision))
        : defaults.round;
}

export function precisionOptions() {
    return Array.from({length: numericInputConfig().maxDecimals + 1}, (_, precision) => precision);
}

export function resolveDecimals(decimals = null) {
    return normalizePrecision(decimals ?? numericInputConfig().round);
}

export function decimalStep(decimals = null) {
    return 10 ** -resolveDecimals(decimals);
}

function ungroup(value) {
    return /^[+-]?\d{1,3}(?:,\d{3})+(?:\.\d+)?$/.test(value) ? value.replace(/,/g, "") : value;
}

export function editableNumber(value) {
    return value == null ? "" : ungroup(String(value).trim());
}

function numericText(value) {
    if(typeof value !== "number" && typeof value !== "string") return null;

    const normalized = editableNumber(value);
    const match = decimalPattern.exec(normalized);
    if(!match || !(match[2] || match[3]) || normalized.length > 400
        || Math.abs(Number(match[4] ?? 0)) > 400 || !Number.isFinite(Number(normalized))) return null;

    return match;
}

function parseNumber(value) {
    const match = numericText(value);

    return match ? Number(match[0]) : null;
}

export function normalizeNumber(value) {
    return parseNumber(value) ?? 0;
}

export function isNumber({value, minValue = 0, maxValue = null, allowEmpty = false}) {
    if(editableNumber(value) === "") return allowEmpty;

    const number = parseNumber(value);
    const minimum = parseNumber(minValue) ?? 0;
    const maximum = parseNumber(maxValue);

    return number !== null && number >= minimum
        && (maxValue == null || maxValue === "" || (maximum !== null && number <= maximum));
}

export function numericBounds({minValue = null, maxValue = null, hasNegative = false} = {}) {
    const config = numericInputConfig();
    const maximum = parseNumber(maxValue) ?? config.maxValue;

    return {
        min: parseNumber(minValue) ?? (hasNegative ? -maximum : config.minValue),
        max: maximum
    };
}

export function commitNumberInput(value, {min, max, hasNegative = false, decimals = null}) {
    const editable = editableNumber(value);
    const validFormat = /^-?\d+(?:\.\d*)?$/.test(editable);

    if(!validFormat || (!hasNegative && editable.startsWith("-"))
        || (resolveDecimals(decimals) === 0 && editable.endsWith("."))) return null;

    const number = parseNumber(editable);
    if(number === null) return null;

    const bounded = number < min ? min : (number > max ? max : editable);
    let rounded = roundNumber(bounded, decimals);

    if(rounded > max) {
        rounded = roundNumber(rounded - decimalStep(decimals), decimals);
    }else if(rounded < min) {
        rounded = roundNumber(rounded + decimalStep(decimals), decimals);
    }

    return rounded >= min && rounded <= max ? rounded : null;
}

export function isNumberInputKey(key, {hasNegative = false, decimals = null} = {}) {
    return /^\d$/.test(key)
        || ["ArrowLeft", "ArrowRight", "Backspace", "Tab"].includes(key)
        || (hasNegative && key === "-")
        || (resolveDecimals(decimals) > 0 && key === ".");
}

function power(exponent) {
    return 10n ** BigInt(exponent);
}

function decimal(value) {
    const parts = numericText(value);
    if(!parts) return zero;

    const [, sign, whole, fraction = "", exponent = 0] = parts;
    const scale = fraction.length - Number(exponent);
    const coefficient = BigInt(`${sign}${whole || "0"}${fraction}`);

    return scale < 0
        ? {coefficient: coefficient * power(-scale), scale: 0}
        : {coefficient, scale};
}

function add(left, right) {
    const scale = Math.max(left.scale, right.scale);

    return {
        coefficient: left.coefficient * power(scale - left.scale) + right.coefficient * power(scale - right.scale),
        scale
    };
}

function product(left, right) {
    return {coefficient: left.coefficient * right.coefficient, scale: left.scale + right.scale};
}

function ratioUnits(value, divisor, precision) {
    const shift = precision + divisor.scale - value.scale;
    const numerator = value.coefficient * power(Math.max(0, shift));
    const denominator = divisor.coefficient * power(Math.max(0, -shift));
    const units = numerator / denominator;
    const remainder = numerator % denominator;
    const absoluteRemainder = remainder < 0n ? -remainder : remainder;
    const absoluteDenominator = denominator < 0n ? -denominator : denominator;

    if(absoluteRemainder * 2n < absoluteDenominator) return units;

    return units + ((numerator < 0n) === (denominator < 0n) ? 1n : -1n);
}

const one = {coefficient: 1n, scale: 0};
const hundred = {coefficient: 100n, scale: 0};

function fixedUnits(units, precision) {
    if(precision === 0) return String(units);

    const digits = String(units < 0n ? -units : units).padStart(precision + 1, "0");
    const sign = units < 0n ? "-" : "";

    return `${sign}${digits.slice(0, -precision)}.${digits.slice(-precision)}`;
}

function decimalResult(value, divisor = one, decimals = null) {
    const precision = resolveDecimals(decimals);

    return Number(fixedUnits(ratioUnits(value, divisor, precision), precision));
}

export function fixedNumber(value, decimals = null) {
    const precision = resolveDecimals(decimals);

    return fixedUnits(ratioUnits(decimal(value), one, precision), precision);
}

export function separatorNumber(value, decimals = null, locale = "en-US") {
    const precision = resolveDecimals(decimals);
    const formatted = fixedNumber(value, precision);

    if(locale === "en-US") {
        const [whole, fraction] = formatted.split(".");
        const grouped = whole.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        return fraction === undefined ? grouped : `${grouped}.${fraction}`;
    }

    const key = `${locale}:${precision}`;
    if(!formatters.has(key)) {
        formatters.set(key, new Intl.NumberFormat(locale, {
            minimumFractionDigits: precision,
            maximumFractionDigits: precision
        }));
    }

    return formatters.get(key).format(precision === 0 ? BigInt(formatted) : Number(formatted));
}

export function roundNumber(value, decimals = null) {
    return Number(fixedNumber(value, decimals));
}

export function sum(values) {
    let total = zero;
    for(const value of values) total = add(total, decimal(value));

    return decimalResult(total);
}

export function subtract(left, right) {
    const other = decimal(right);

    return decimalResult(add(decimal(left), {...other, coefficient: -other.coefficient}));
}

export function multiply(left, right) {
    return decimalResult(product(decimal(left), decimal(right)));
}

export function divide(value, divisor) {
    return decimalResult(decimal(value), decimal(divisor));
}

export function percentage(value, rate) {
    return decimalResult(product(decimal(value), decimal(rate)), hundred);
}

export function proportion(value, factor, divisor, decimals = null) {
    return decimalResult(product(decimal(value), decimal(factor)), decimal(divisor), decimals);
}

export function baseFromGross(gross, rate) {
    return decimalResult(product(decimal(gross), hundred), add(hundred, decimal(rate)));
}

export function lineTotal(quantity, unitPrice) {
    return multiply(roundNumber(quantity), roundNumber(unitPrice));
}

export function taxAmount(base, tax, quantity = 1) {
    const rate = tax.rate ?? 0;
    const amount = tax.calculation_type === "fixed"
        ? multiply(rate, quantity)
        : percentage(base, rate);

    return tax.operation_type === "subtraction" && amount !== 0 ? -amount : amount;
}

export function documentTax(tax, lines, quantity = 1) {
    if(tax.calculation_type === "fixed") {
        const amount = taxAmount(0, tax, quantity);
        return {baseAmount: 0, amount, totalImpact: amount};
    }

    const baseAmounts = [];
    const amounts = [];
    const impacts = [];
    const rate = normalizeNumber(tax.rate);

    for(const line of lines) {
        if(line.exempt || line.total <= 0) continue;

        if(line.includesTax && tax.operation_type !== "subtraction" && rate > 0) {
            const base = baseFromGross(line.total, rate);
            baseAmounts.push(base);
            amounts.push(subtract(line.total, base));
        }else {
            const amount = taxAmount(line.total, tax);
            baseAmounts.push(line.total);
            amounts.push(amount);
            impacts.push(amount);
        }
    }

    return {baseAmount: sum(baseAmounts), amount: sum(amounts), totalImpact: sum(impacts)};
}

export function quantityParts(value, measurementUnit = null, decimals = null) {
    return {
        amount: separatorNumber(value, decimals),
        abbreviation: measurementUnit?.abbreviation || measurementUnit?.unitAbbreviation || ""
    };
}

export function quantityWithUnit(value, measurementUnit = null, decimals = null) {
    const {amount, abbreviation} = quantityParts(value, measurementUnit, decimals);

    return abbreviation ? `${amount} ${abbreviation}` : amount;
}

export function calculateTotal({item}) {
    const quantity = isNumber({value: item?.quantity}) ? item.quantity : 0;
    const price = isNumber({value: item?.price}) ? item.price : 0;
    const precision = resolveDecimals();

    return fixedUnits(ratioUnits(product(decimal(quantity), decimal(price)), one, precision), precision);
}
