<template>
    <figure class="fg-performance-figure">
        <figcaption class="fg-performance-figure__caption">
            <span class="fg-section-title">{{ series.code }}</span>
            <span v-if="activeDay !== null" class="fg-help">
                {{ formatDay(days[activeDay], shortDate) }} · {{ formatCurrencyAmount(series.totals[activeDay], series) }}
            </span>
        </figcaption>
        <div class="fg-performance-figure__chart">
            <div class="fg-performance-figure__amounts" aria-hidden="true">
                <span v-for="(amount, index) in ticks" :key="index">{{ separatorNumber(amount) }}</span>
            </div>
            <svg class="fg-performance-figure__plot" :viewBox="`0 0 ${width} ${height}`" preserveAspectRatio="none"
                role="img" :aria-label="`Evolución diaria del importe vendido en ${series.code}. Consulta los importes por fecha debajo del gráfico.`"
                @pointermove="inspectDay" @pointerleave="activeDay = null">
                <line v-for="(amount, index) in ticks" :key="index" x1="0" :x2="width" :y1="y(amount)" :y2="y(amount)"
                    class="fg-performance-figure__grid" vector-effect="non-scaling-stroke"/>
                <polyline :points="points" class="fg-performance-figure__line" vector-effect="non-scaling-stroke"/>
                <line v-if="days.length === 1 || activeDay !== null" :x1="x(activeDay ?? 0)" :x2="x(activeDay ?? 0)"
                    :y1="y(values[activeDay ?? 0]) - 0.01" :y2="y(values[activeDay ?? 0]) + 0.01"
                    class="fg-performance-figure__point" vector-effect="non-scaling-stroke"/>
            </svg>
            <div class="fg-performance-figure__days" aria-hidden="true">
                <span v-for="day in axisDays" :key="day">{{ formatDay(day, shortDate) }}</span>
            </div>
        </div>
        <details class="fg-performance-figure__details">
            <summary>Ver importes por fecha</summary>
            <ResponsiveTable scroll-y>
                <table class="fg-table">
                    <thead><tr><th scope="col">Fecha</th><th scope="col" class="text-end">Importe vendido</th></tr></thead>
                    <tbody>
                        <tr v-for="(day, index) in days" :key="day">
                            <td><time :datetime="day">{{ formatDay(day) }}</time></td>
                            <td class="text-end text-nowrap">{{ formatCurrencyAmount(series.totals[index], series) }}</td>
                        </tr>
                    </tbody>
                </table>
            </ResponsiveTable>
        </details>
    </figure>
</template>

<script setup>
import {computed, ref} from "vue";
import ResponsiveTable from "@/Shared/ResponsiveTable.vue";
import {decimalStep, formatCurrencyAmount, separatorNumber} from "@/Shared/Numbers.js";

const props = defineProps({
    days: {type: Array, required: true},
    series: {type: Object, required: true},
});

const shortDate = new Intl.DateTimeFormat("es-PE", {day: "2-digit", month: "short", timeZone: "UTC"});
const fullDate = new Intl.DateTimeFormat("es-PE", {dateStyle: "medium", timeZone: "UTC"});
const formatDay = (day, formatter = fullDate) => formatter.format(new Date(`${day}T00:00:00Z`));
const width = 1000;
const height = 200;
const inset = 5;
const activeDay = ref(null);
// Binary numbers are used only to place points; displayed amounts use the decimal strings.
const values = computed(() => props.series.totals.map(Number));
const top = computed(() => Math.max(0, ...values.value));
const bottom = computed(() => Math.min(0, ...values.value));
const span = computed(() => top.value - bottom.value);
const ticks = computed(() => {
    if (span.value === 0) return [0];
    const steps = span.value < decimalStep() * 4 ? 1 : 4;
    return Array.from({length: steps + 1}, (_, index) => index === steps
        ? bottom.value
        : top.value - (span.value * index) / steps);
});
const axisDays = computed(() => {
    if (props.days.length <= 3) return props.days;
    return [props.days[0], props.days[Math.floor((props.days.length - 1) / 2)], props.days.at(-1)];
});
const x = index => props.days.length === 1 ? width / 2 : inset + (width - 2 * inset) * index / (props.days.length - 1);
const y = amount => span.value === 0 ? height / 2 : inset + (height - 2 * inset) * (top.value - amount) / span.value;
const points = computed(() => values.value.map((value, index) => `${x(index)},${y(value)}`).join(" "));

function inspectDay(event) {
    const rect = event.currentTarget.getBoundingClientRect();
    const coordinate = width * (event.clientX - rect.left) / rect.width;
    const index = Math.round((coordinate - inset) * (props.days.length - 1) / (width - 2 * inset));
    activeDay.value = Math.min(props.days.length - 1, Math.max(0, index));
}
</script>

<style>
.fg-performance-figure {
    min-width: 0;
    margin: 0;
    padding: var(--fg-space-4);
    border: 1px solid var(--fg-border);
    border-radius: var(--fg-panel-radius);
    background: var(--fg-surface);
}

.fg-performance-figure__caption {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: var(--fg-space-2) var(--fg-space-3);
    min-height: 1.5em;
}

.fg-performance-figure__chart {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: var(--fg-space-2);
    margin-block: var(--fg-space-3);
    color: var(--fg-muted);
    font-size: var(--fg-help-size);
}

.fg-performance-figure__amounts {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: flex-end;
    font-variant-numeric: tabular-nums;
}

.fg-performance-figure__amounts > span:only-child { margin-block: auto; }

.fg-performance-figure__plot {
    display: block;
    width: 100%;
    height: clamp(10rem, 22vw, 15rem);
    overflow: visible;
}

.fg-performance-figure__grid { stroke: var(--fg-border); }
.fg-performance-figure__line { fill: none; stroke: var(--fg-primary); stroke-width: 2px; stroke-linejoin: round; }
.fg-performance-figure__point { stroke: var(--fg-primary); stroke-width: 7px; stroke-linecap: round; }

.fg-performance-figure__days {
    grid-column: 2;
    display: flex;
    justify-content: space-between;
}

.fg-performance-figure__days > span:only-child {
    margin-inline: auto;
}

.fg-performance-figure__details > summary {
    width: fit-content;
    color: var(--fg-primary);
    font-size: var(--fg-help-size);
    cursor: pointer;
}

.fg-performance-figure__details > .fg-table-scroll {
    max-height: 12rem;
    margin-block-start: var(--fg-space-3);
    overflow-y: auto;
}
</style>
