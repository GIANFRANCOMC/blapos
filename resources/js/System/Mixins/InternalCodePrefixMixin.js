import {internalCodePrefixLabel, splitInternalCode} from "../Helpers/InternalCodes.js";

export default {
    data() {
        return {originalInternalCode: null, internalCodePrefixOverride: null};
    },
    computed: {
        internalCodeEntityKey() {

            return this.MODULE?.config?.internalCodeEntity
                ?? this.MODULE?.internalCodeEntity
                ?? "";

        },
        internalCodePrefixes() {

            return this.options?.internal_code_prefixes ?? {};

        },
        internalCodePrefixLabel() {

            return internalCodePrefixLabel(this.internalCodePrefixes[this.internalCodeEntityKey]);

        }
    },
    methods: {
        setEditableInternalCode(value, isNew = false) {
            this.originalInternalCode = isNew ? null : String(value ?? "");
            this.internalCodePrefixOverride = null;

            return splitInternalCode(value, this.internalCodePrefixLabel).value;
        },
        internalCodeHasPrefix(value) {
            if(!this.internalCodePrefixLabel) return false;
            if(this.internalCodePrefixOverride !== null) return this.internalCodePrefixOverride;
            if(this.originalInternalCode === null) return true;

            const original = splitInternalCode(this.originalInternalCode, this.internalCodePrefixLabel);
            return original.hasPrefix || value !== original.value;
        },
        normalizeInternalCodeInput(value) {
            const parts = splitInternalCode(value, this.internalCodePrefixLabel);
            if(parts.hasPrefix) this.internalCodePrefixOverride = true;

            return parts.value;
        },
        internalCodeInputProps(form) {
            return {
                prefix: this.internalCodePrefixLabel,
                usePrefix: this.internalCodeHasPrefix(form.internal_code),
                transformValue: this.normalizeInternalCodeInput,
                onTogglePrefix: () => {
                    this.internalCodePrefixOverride = !this.internalCodeHasPrefix(form.internal_code);
                    if(this.internalCodePrefixOverride) {
                        form.internal_code = splitInternalCode(form.internal_code, this.internalCodePrefixLabel).value;
                    }
                }
            };
        },
        internalCodePayload(value) {
            const prefix = this.internalCodeHasPrefix(value) ? this.internalCodePrefixLabel : "";

            return {
                internal_code: value ? `${prefix}${value}` : "",
                internal_code_without_prefix: Boolean(this.internalCodePrefixLabel && this.internalCodePrefixOverride === false)
            };
        }
    }
};
