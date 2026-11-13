export function internalCodePrefixLabel(prefix) {
    const value = String(prefix ?? "").replace(/^[\s-]+|[\s-]+$/g, "").toUpperCase();
    return value ? `${value}-` : "";
}

export function internalCodePrefixFor(value, prefixLabel) {
    return prefixLabel && !String(value ?? "").startsWith(prefixLabel) ? prefixLabel : "";
}

export function composeInternalCode(value, prefixLabel) {
    return value ? `${internalCodePrefixFor(value, prefixLabel)}${value}` : "";
}
