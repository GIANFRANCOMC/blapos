<template>
    <table v-if="events.length" class="fg-table" :aria-label="label">
        <thead>
            <tr>
                <th scope="col">Movimiento</th>
                <th scope="col">Fecha de registro</th>
                <th scope="col">Responsable</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="(event, index) in events" :key="event.id"
                :aria-current="index === events.length - 1 ? 'step' : null">
                <td><span :class="['fg-badge', 'fg-badge--' + event.tone]">{{ event.label }}</span></td>
                <td><time :datetime="event.occurred_at">{{ formatDate(event.occurred_at) }}</time></td>
                <td>{{ event.user_name }}</td>
            </tr>
        </tbody>
    </table>
    <p v-else class="fg-help">No hay cambios de estado registrados.</p>
</template>

<script>
export default {
    props: {
        events: {type: Array, default: () => []},
        label: {type: String, default: "Historial de estados"},
        formatDate: {type: Function, required: true}
    }
};
</script>
