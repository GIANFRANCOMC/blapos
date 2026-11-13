import {composeInternalCode, internalCodePrefixFor, internalCodePrefixLabel} from "../Helpers/InternalCodes.js";

export default {
    data() {
        return {originalInternalCode: "", initialEditableInternalCode: ""};
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
        stripInternalCodePrefix(value) {

            const code = String(value ?? "");
            const prefix = this.internalCodePrefixLabel;

            return prefix && code.startsWith(prefix)
                ? code.slice(prefix.length)
                : code;

        },
        setEditableInternalCode(value) {
            this.originalInternalCode = String(value ?? "");
            this.initialEditableInternalCode = this.stripInternalCodePrefix(value);

            return this.initialEditableInternalCode;
        },
        internalCodeDisplayedPrefix(id, value) {
            if(id && value === this.initialEditableInternalCode) {
                return this.originalInternalCode.slice(0, this.originalInternalCode.length - value.length);
            }

            return internalCodePrefixFor(value, this.internalCodePrefixLabel);
        },
        internalCodeForSave(id, value) {
            return id && value === this.initialEditableInternalCode
                ? this.originalInternalCode
                : composeInternalCode(value, this.internalCodePrefixLabel);
        }
    }
};
