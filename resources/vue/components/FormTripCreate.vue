<!-- JUST A QUICK MOCKUP, NOT FOR PRODUCTION USE -->
<!-- First, the backend needs to be finished -->

<template>
    <form id="form-trip-create">
        <div class="form-floating mb-3">
            <select class="form-select" id="category" v-model="formData.category" required>
                <option>regional</option>
            </select>
            <label for="category" class="form-label">Kategorie</label>
        </div>

        <div class="form-floating mb-3">
            <input type="text" class="form-control" id="lineName" v-model="formData.lineName" required>
            <label for="lineName" class="form-label">Linie</label>
        </div>

        <div class="form-floating mb-3">
            <input type="number" class="form-control" id="journey_number" v-model="formData.journey_number" min="1">
            <label for="journey_number" class="form-label">Fahrtnummer</label>
        </div>

        <div class="form-floating mb-3">
            <input type="number" class="form-control" id="operator_id" v-model="formData.operator_id">
            <label for="operator_id" class="form-label">Betreiber</label>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-floating mb-3" id="origin-autocomplete-container">
                    <input type="text" class="form-control" id="originId" v-model="formData.originId" required>
                    <label for="originId" class="form-label">Abfahrtsstation</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating mb-3">
                    <input type="datetime-local" class="form-control" id="originDeparturePlanned"
                           v-model="formData.originDeparturePlanned" required>
                    <label for="originDeparturePlanned" class="form-label">Geplante Abfahrtszeit</label>
                </div>
            </div>
        </div>

        <div v-for="(stop, index) in formData.stops" :key="index" class="row mt-3">
            <div class="col-md-4">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" :id="'stopStation' + index" v-model="stop.station">
                    <label :for="'stopStation' + index">Station</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating mb-3">
                    <input type="datetime-local" class="form-control" :id="'departureTime' + index"
                           v-model="stop.departureTime">
                    <label :for="'departureTime' + index">Abfahrtszeit</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating mb-3">
                    <input type="datetime-local" class="form-control" :id="'arrivalTime' + index"
                           v-model="stop.arrivalTime">
                    <label :for="'arrivalTime' + index">Ankunftszeit</label>
                </div>
            </div>
        </div>

        <a @click="addStop">
            <i class="fa-solid fa-plus"></i>
            Zwischenhalt hinzufügen
        </a>

        <div class="row mt-3">
            <div class="col-md-6">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="destinationId" v-model="formData.destinationId"
                           required>
                    <label for="destinationId" class="form-label">Ankunftsstation</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating mb-3">
                    <input type="datetime-local" class="form-control" id="destinationArrivalPlanned"
                           v-model="formData.destinationArrivalPlanned" required>
                    <label for="destinationArrivalPlanned" class="form-label">Geplante Ankunftszeit</label>
                </div>
            </div>
        </div>


        <button type="button" class="btn btn-primary" @click="submitForm">
            Einreichen
        </button>
    </form>
</template>

<script>

export default {
    mounted() {
        this.setupAutocomplete(document.querySelector('#form-trip-create input[id="originId"]'));
        this.setupAutocomplete(document.querySelector('#form-trip-create input[id="destinationId"]'));
    },
    data() {
        return {
            formData: {
                category: 'regional',
                lineName: 'RE 10',
                journey_number: 12345,
                operator_id: 1,
                originId: 8045948,
                originDeparturePlanned: '2023-11-20T13:00+01:00',
                destinationId: 8445925,
                destinationArrivalPlanned: '2023-11-20T14:00+01:00',
                stops: []
            }
        };
    },
    methods: {
        submitForm() {
            console.clear();
            fetch('/api/v1/trains/trip', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(this.formData)
            })
                .then(response => response.json())
                .then(data => {
                    console.log('Success:', data);
                })
                .catch((error) => {
                    console.error('Error:', error);
                });
        },
        setupAutocomplete(input) {
            const container = input.parentNode;
            if (!input) return;

            const awesomplete = new Awesomplete(input, {
                minChars: 2,
                autoFirst: true,
                sort: false,
                list: [],
                container: () => {
                    container.classList.add("awesomplete");
                    return container;
                }
            });

            const debounce = (func, timeout = 300) => {
                let timer;
                return (...args) => {
                    clearTimeout(timer);
                    timer = setTimeout(() => {
                        func.apply(this, args);
                    }, timeout);
                };
            };

            const fetchStations = () => {
                if (input.value.length < 2) return;

                fetch("/transport/train/autocomplete/" + encodeURI(input.value))
                    .then(res => res.json())
                    .then(json => {
                        awesomplete.list = json.map(station => ({
                            value: station.ibnr,
                            label: station.name + (station.rilIdentifier ? " (" + station.rilIdentifier + ")" : "")
                        }));
                    });
            };

            const processChange = debounce(() => fetchStations());
            input.addEventListener("keyup", processChange);
        },
        addStop() {
            this.formData.stops.push({
                station: '',
                departureTime: '',
                arrivalTime: ''
            });
        }
    }
};
</script>

<style>

</style>
