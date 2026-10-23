<div class="max-w-xl mx-auto space-y-6">

    <h1 class="text-2xl font-bold text-gray-900">
        {{ __('vespaket.new_event', ['team' => $team->name]) }}
    </h1>

    <form wire:submit="save" class="bg-white p-4 rounded-xl shadow space-y-3">

        <div>
            <x-input-label for="titre" :value="__('vespaket.title')" />
            <x-text-input wire:model="titre" id="titre" name="titre" type="text" class="mt-1 block w-full" required autofocus autocomplete="titre" />
            <x-input-error class="mt-2" :messages="$errors->get('titre')" />
        </div>

        <div>
            <x-input-label for="date" :value="__('vespaket.date')" />
            <x-text-input wire:model="date" id="date" name="date" type="datetime-local" class="mt-1 block w-full" required autofocus autocomplete="date" />
            <x-input-error class="mt-2" :messages="$errors->get('date')" />
        </div>

        <div>
            <x-input-label for="location" :value="__('vespaket.location')" />
            <x-text-input wire:model="location" id="location" name="location" type="text" class="mt-1 block w-full" required autofocus autocomplete="location" />
            <x-input-error class="mt-2" :messages="$errors->get('location')" />
        </div>

        <div>
            <x-input-label for="description" :value="__('vespaket.description')" />
            <x-text-input wire:model="description" id="description" name="description" type="text" class="mt-1 block w-full" required autofocus autocomplete="commentaire" />
            <x-input-error class="mt-2" :messages="$errors->get('description')" />
        </div>

        <x-primary-button >
            {{ __('vespaket.add_event') }}
        </x-primary-button>

    </form>

</div>