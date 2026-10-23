<div
    x-data="{
        showMenu: false,
        isAndroid: false,
        isIOS: false,
        init() {
            const ua = navigator.userAgent || navigator.vendor || window.opera;
            this.isAndroid = /android/i.test(ua);
            this.isIOS = /iPad|iPhone|iPod/.test(ua) && !window.MSStream;
        }
    }"
    class="relative inline-block"
>
    {{-- Android : lien direct geo:, l'OS gère le choix de l'app --}}
    <a x-show="isAndroid" x-cloak href="{{ $this->geoUrl }}" class="inline-flex items-center gap-2 bg-yellow-400 text-black font-semibold px-4 py-2 rounded-lg hover:bg-yellow-300 transition">
        📍 {{ __('vespaket.route') }}
    </a>

    {{-- iOS ou desktop : bouton qui ouvre un petit menu de choix --}}
    <button x-show="!isAndroid" x-cloak @click="showMenu = !showMenu" class="inline-flex items-center gap-2 bg-yellow-400 text-black font-semibold px-4 py-2 rounded-lg hover:bg-yellow-300 transition">
        📍 {{ __('vespaket.route') }}
    </button>

    <div x-show="showMenu && !isAndroid" x-cloak @click.outside="showMenu = false" x-transition class="absolute z-50 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
        <a href="{{ $this->googleUrl }}" target="_blank" class="block px-4 py-3 text-sm text-gray-800 hover:bg-gray-100">
            Google Maps
        </a>

        <a href="{{ $this->wazeUrl }}" target="_blank" class="block px-4 py-3 text-sm text-gray-800 hover:bg-gray-100 border-t border-gray-100">
            Waze
        </a>

        <a x-show="isIOS" x-cloak href="{{ $this->appleUrl }}" target="_blank" class="block px-4 py-3 text-sm text-gray-800 hover:bg-gray-100 border-t border-gray-100">
            Plans (Apple)
        </a>
    </div>
</div>