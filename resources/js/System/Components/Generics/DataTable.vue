<template>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr class="text-center align-middle">
                    <th
                        v-for="column in columns"
                        :key="column.key"
                        :class="['bg-secondary', 'text-white', 'fw-semibold', column.class]"
                        :style="column.style">
                        {{ column.label }}
                    </th>
                    <th
                        v-if="showActions"
                        class="bg-secondary text-white fw-semibold"
                        style="width: 20%;">
                        ACCIONES
                    </th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0 bg-white">
                <template v-if="loading">
                    <tr class="text-center">
                        <td :colspan="columns.length + (showActions ? 1 : 0)" class="py-4">
                            <Loader/>
                        </td>
                    </tr>
                </template>
                <template v-else>
                    <template v-if="records.length > 0">
                        <tr
                            v-for="record in records"
                            :key="record.id"
                            class="text-center">
                            <td
                                v-for="column in columns"
                                :key="column.key"
                                :class="column.bodyClass">
                                <slot
                                    :name="`cell-${column.key}`"
                                    :record="record"
                                    :value="getNestedValue(record, column.key)">
                                    <span v-text="formatCellValue(record, column)"></span>
                                </slot>
                            </td>
                            <td v-if="showActions">
                                <InputSlot
                                    hasDiv
                                    :isInputGroup="false"
                                    :divInputClass="['d-flex flex-wrap justify-content-center gap-2 gap-md-1']"
                                    xl="12"
                                    lg="12">
                                    <template v-slot:input>
                                        <button
                                            v-for="action in actions"
                                            :key="action.key"
                                            :class="['btn', 'btn-sm', 'waves-effect', action.class || 'btn-primary']"
                                            :disabled="action.disabled && action.disabled(record)"
                                            @click="handleAction(action.key, record)"
                                            v-if="!action.condition || action.condition(record)">
                                            <i :class="action.icon"></i>
                                            <span class="ms-2">{{ action.label }}</span>
                                        </button>
                                    </template>
                                </InputSlot>
                            </td>
                        </tr>
                    </template>
                    <template v-else>
                        <tr>
                            <td :colspan="columns.length + (showActions ? 1 : 0)" class="text-center">
                                <WithoutData type="image"/>
                            </td>
                        </tr>
                    </template>
                </template>
            </tbody>
        </table>
    </div>
    <Paginator v-if="pagination" :links="pagination.links || []" :pagination="pagination" :loading="loading" @clickPage="$emit('pageChange', $event)"/>
</template>

<script>
import * as Utils from "@System/Helpers/Utils.js";

export default {
    name: "DataTable",
    emits: ["action", "pageChange"],
    props: {
        columns: {
            type: Array,
            required: true,
            default: () => []
        },
        records: {
            type: Array,
            default: () => []
        },
        loading: {
            type: Boolean,
            default: false
        },
        showActions: {
            type: Boolean,
            default: true
        },
        actions: {
            type: Array,
            default: () => []
        },
        pagination: {
            type: Object,
            default: null
        }
    },
    methods: {
        handleAction(actionKey, record) {
            this.$emit("action", { action: actionKey, record });
        },
        getNestedValue(obj, path) {
            return path.split(".").reduce((current, prop) => current?.[prop], obj);
        },
        formatCellValue(record, column) {
            const value = this.getNestedValue(record, column.key);

            if (column.formatter && typeof column.formatter === "function") {
                return column.formatter(value, record);
            }

            if (column.type === "badge" && value) {
                return value;
            }

            return value ?? "N/A";
        }
    }
};
</script>

