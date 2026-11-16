export function internalCodePrefixLabel(prefix) {
    const value = String(prefix ?? "").replace(/^[\s-]+|[\s-]+$/g, "").toUpperCase();
    return value ? `${value}-` : "";
}

export function splitInternalCode(value, prefix) {
    const code = String(value ?? "");
    const hasPrefix = Boolean(prefix && code.startsWith(prefix));

    return {hasPrefix, value: hasPrefix ? code.slice(prefix.length) : code};
}

export function internalCodeImportHelp(configuredPrefix) {
    const prefix = internalCodePrefixLabel(configuredPrefix);

    return prefix
        ? `Completa el código interno de cada fila con el prefijo ${prefix}.`
        : "Completa el código interno de cada fila.";
}
