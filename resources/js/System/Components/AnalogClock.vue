<template>
    <div class="digital-clock" :class="{ static: !isDynamic }">
        <div class="date">{{ displayDate }}</div>
        <div class="time">
            <span class="digits">{{ displayHour }}</span>
            <span class="colon" :class="{ blink: isDynamic && blinkColon }">:</span>
            <span class="digits">{{ displayMinute }}</span>
            <span class="ampm">{{ displayMeridian }}</span>
        </div>
        <!-- <div v-if="!isDynamic" class="static-label">Hora fija</div> -->
    </div>
</template>

<script>
export default {
    name: "AnalogClock",
    props: {
        time: {
            type: String,
            default: null,
        },
        isDynamic: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            blinkColon: true,
            blinkInterval: null,
            clockInterval: null,
            now: new Date(),
        };
    },
    computed: {
        currentDate() {

            return this.isDynamic ? this.now : new Date(this.time);

        },
        displayDate() {

            const days = [
                "DOMINGO",
                "LUNES",
                "MARTES",
                "MIÉRCOLES",
                "JUEVES",
                "VIERNES",
                "SÁBADO",
            ];

            const months = [
                "ENERO",
                "FEBRERO",
                "MARZO",
                "ABRIL",
                "MAYO",
                "JUNIO",
                "JULIO",
                "AGOSTO",
                "SEPTIEMBRE",
                "OCTUBRE",
                "NOVIEMBRE",
                "DICIEMBRE",
            ];

            const dayName = days[this.currentDate.getDay()];
            const day = String(this.currentDate.getDate()).padStart(2, "0");
            const month = months[this.currentDate.getMonth()];
            const year = this.currentDate.getFullYear();

            return `${dayName}, ${day} ${month} ${year}`;

        },
        displayHour() {

            let hour = this.currentDate.getHours();
            hour = hour % 12 || 12;
            return String(hour).padStart(2, "0");

        },
        displayMinute() {

            return String(this.currentDate.getMinutes()).padStart(2, "0");

        },
        displayMeridian() {

            return this.currentDate.getHours() >= 12 ? "PM" : "AM";

        }
    },
    mounted() {

        if(this.isDynamic) {

            this.clockInterval = setInterval(() => {

                this.now = new Date();

            }, 1000);
        }

        this.blinkInterval = setInterval(() => {

            this.blinkColon = this.isDynamic ? !this.blinkColon : false;

        }, 1000);

    },
    beforeUnmount() {

        clearInterval(this.clockInterval);
        clearInterval(this.blinkInterval);

    }
};
</script>

<style scoped>
.digital-clock {
    font-family: var(--br-font-sans);
    font-variant-numeric: tabular-nums;
    background-color: #000;
    color: #0f0;
    font-weight: bold;
    padding: 4px 14px;
    border-radius: 6px;
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    /* box-shadow: 0 0 6px #0f0; */
    max-width: 250px;
    transition: background-color 0.4s, color 0.4s, box-shadow 0.4s;
}

/* Estilo modo ESTÁTICO */
.digital-clock.static {
    background-color: #000814;
    color: #00f0ff;
    /* box-shadow: 0 0 6px #00f0ff; */
}

.date {
    font-size: 12px;
    /* margin-bottom: 4px; */
    letter-spacing: 1px;
    opacity: 0.85;
    text-align: center;
}

.time {
    display: flex;
    align-items: center;
    font-size: 18px;
}

.digits {
    width: 20px;
    letter-spacing: 1px;
    text-align: center;
}

.colon {
    width: 10px;
    text-align: center;
    transition: opacity 0.3s;
}

.blink {
    opacity: 0;
}

.ampm {
    font-size: 12px;
    margin-left: 6px;
    text-transform: uppercase;
    opacity: 0.7;
}

.static-label {
    font-size: 10px;
    color: #00f0ff;
    margin-top: 4px;
    font-style: italic;
    opacity: 0.8;
}
</style>
