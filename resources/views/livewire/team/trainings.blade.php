<div>
    <div class="flex gap-4 mb-2">
        <h2 class="text-lg font-semibold mb-4">{{ __('vespaket.trainings') }}</h2>
        <a href="{{ route('team.trainings.create', ['team' => $team->id ]) }}" >
            <x-button>➕️{{ __('actions.add') }}</x-button>
        </a>
    </div>

    <x-cards-scroll nextElementId="training-{{ $nextTrainingId }}" >
        @forelse($trainings as $training)
            <x-card title="{{$training->titre}}" 
                    id="training-{{ $training->id }}" 
                    href="{{ route('training.show', [ 'training' => $training->id]) }}"
                    description="{{ $training->formatdate() }}" >
                    <p>{{ $training->members_count }} {{ __('vespaket.players')}}</p>
            </x-card>
        @empty
            <p class="text-gray-500">{{ __('vespaket.no_training') }}</p>
        @endforelse
    </x-cards-scroll>
</div>
