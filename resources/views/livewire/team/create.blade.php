<div class="p-4 rounded shadow">
    <h2 class="text-lg font-bold mb-4">{{ __('vespaket.add_team') }}</h2>

    @if(session()->has('success'))
        <div class="text-green-600">
            {{ session('success') }}
        </div>
    @endif

    <input 
        type="text" 
        wire:model="name" 
        placeholder="{{ __('vespaket.team_name') }}"
        class="border p-2 w-full mb-2"
    >

    <input 
        type="text" 
        wire:model="whatsapp" 
        placeholder="{{ __('vespaket.link_whatsapp') }}"
        class="border p-2 w-full mb-2"
    >

    <textarea
        wire:model="msg_convocation"        
        rows="6" 
        class="w-full rounded border border-gray-300 px-3 py-2 text-black"
    ></textarea>

    @error('name') 
        <div class="text-red-500">{{ $message }}</div> 
    @enderror

    <button 
        wire:click="create"
        class="bg-blue-500 text-white px-4 py-2 mt-2"
    >
        {{ __('vespaket.add_team') }}
    </button>
</div>