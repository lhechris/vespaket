<div class="max-w-lg mx-auto bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-bold text-black mb-4">
        {{ $place ? __('vespaket.edit_sport_hall') : __('vespaket.new_sport_hall') }}
    </h2>

    @if (session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-4">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{__('vespaket.name_of_sport_hall')}}</label>
            <input
                type="text"
                wire:model="name"
                class="w-full border-gray-300 rounded-lg focus:ring-yellow-400 focus:border-yellow-400"
                placeholder="Ex: Labarthe sur Lèze, Halle des sports"
            >
            @error('name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{__('vespaket.sport_hall_address')}}</label>
            <div class="flex gap-2">
                <input
                    type="text"
                    wire:model="address"
                    class="flex-1 border-gray-300 rounded-lg focus:ring-yellow-400 focus:border-yellow-400"
                    placeholder="Ex: place fournil, Labarthe sur lèze"
                >
                <button
                    type="button"
                    wire:click="geocode"
                    wire:loading.attr="disabled"
                    wire:target="geocode"
                    class="bg-black text-yellow-400 font-semibold px-4 py-2 rounded-lg hover:bg-gray-800 transition disabled:opacity-50 whitespace-nowrap"
                >
                    <span wire:loading.remove wire:target="geocode"> {{__('vespaket.locate')}}</span>
                    <span wire:loading wire:target="geocode"> {{__('vespaket.search_in_progress')}}...</span>
                </button>
            </div>
            @error('address') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror

            @if ($geocodeError)
                <p class="text-red-600 text-xs mt-1">{{ $geocodeError }}</p>
            @endif

            @if ($geocodeSuccess)
                <p class="text-green-600 text-xs mt-1">✓ {{__('vespaket.coord_found')}}</p>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('vespaket.latitude')}}</label>
                <input
                    type="number"
                    step="any"
                    wire:model="lat"
                    class="w-full border-gray-300 rounded-lg focus:ring-yellow-400 focus:border-yellow-400"
                >
                @error('lat') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('vespaket.longitude')}} </label>
                <input
                    type="number"
                    step="any"
                    wire:model="lng"
                    class="w-full border-gray-300 rounded-lg focus:ring-yellow-400 focus:border-yellow-400"
                >
                @error('lng') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>

<div
            x-data="{
                map: null,
                marker: null,
                lat: @entangle('lat'),
                lng: @entangle('lng'),
                initMap() {
                    this.map = L.map(this.$refs.mapContainer, {
                        scrollWheelZoom: false,
                    }).setView([this.lat || 46.6, this.lng || 2.2], this.lat ? 15 : 5);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors',
                        maxZoom: 19,
                    }).addTo(this.map);

                    if (this.lat && this.lng) {
                        this.placeMarker(this.lat, this.lng);
                    }

                    this.map.on('click', (e) => {
                        this.lat = e.latlng.lat;
                        this.lng = e.latlng.lng;
                        this.placeMarker(e.latlng.lat, e.latlng.lng);
                    });

                    // Recentrer / replacer le marqueur si lat/lng changent (géocodage ou saisie manuelle)
                    this.$watch('lat', () => this.syncMarker());
                    this.$watch('lng', () => this.syncMarker());
                },
                syncMarker() {
                    const lat = parseFloat(this.lat);
                    const lng = parseFloat(this.lng);

                    if (isNaN(lat) || isNaN(lng)) return;

                    this.placeMarker(lat, lng);
                    this.map.setView([lat, lng], 15);
                },
                placeMarker(lat, lng) {
                    if (this.marker) {
                        this.marker.setLatLng([lat, lng]);
                    } else {
                        this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);

                        this.marker.on('dragend', (e) => {
                            const pos = e.target.getLatLng();
                            this.lat = pos.lat;
                            this.lng = pos.lng;
                        });
                    }
                }
            }"
            x-init="initMap()"
            wire:ignore
        >
            <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ __('actions.preview') }} ( {{ __('vespaket.clic_or_drag_marker') }} )
            </label>
            <div x-ref="mapContainer" class="w-full h-64 rounded-lg border border-gray-300"></div>
        </div>

        <button
            type="submit"
            class="w-full bg-yellow-400 text-black font-semibold py-2 rounded-lg hover:bg-yellow-300 transition"
        >
            {{ __('actions.save') }}
        </button>
    </form>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""></script>   

</div>