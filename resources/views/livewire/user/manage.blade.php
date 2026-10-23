<div class="space-y-6 p-0 sm:p-6">
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">
            {{ __('vespaket.manage_users') }}
        </h1>
        @if(!$creating)
            <button wire:click="startCreate" type="button" class="rounded-lg bg-yellow-400 px-3 py-2 font-semibold text-black hover:bg-yellow-300">
                {{ __('vespaket.add_user') }}
            </button>
        @endif
    </div>

    <div>
        <input wire:model.live.debounce.300ms="userSearch" id="user-search" type="search" class="w-full rounded-lg border-gray-300 bg-white text-gray-900 shadow-sm focus:border-yellow-500 focus:ring-yellow-500" placeholder="{{ __('vespaket.search_user') }}" />
    </div>

    @if (session('success'))
        <p class="rounded-lg bg-green-100 px-4 py-3 text-sm text-green-800" role="status">
            {{ session('success') }}
        </p>
    @endif

    @if($link)
        <div class="rounded-xl border border-green-700 bg-green-950/40 p-4 text-white" role="status">
            <p class="font-semibold">{{ __('vespaket.invitation_created') }}</p>
            <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                <input type="text" value="{{ $link }}" readonly class="w-full rounded-md border-gray-700 bg-gray-900 text-white" />
                <button type="button" onclick="navigator.clipboard.writeText('{{ $link }}')" class="rounded-lg bg-green-400 px-3 py-2 font-semibold text-black hover:bg-green-300">
                    {{ __('actions.click_to_copy') }}
                </button>
                <a href="https://wa.me/?text={{ urlencode( __('vespaket.invitation_message') . $link) }}" target="_blank" rel="noopener noreferrer" class="rounded-lg bg-green-600 px-3 py-2 text-center font-semibold text-white hover:bg-green-500">
                    WhatsApp
                </a>
            </div>
        </div>
    @endif

    @if($creating)
        <div class="rounded-xl border border-gray-800 bg-yellow-100 text-black p-4 shadow">
            <h2 class="text-lg font-semibold">{{ __('vespaket.add_user') }}</h2>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="new-user-firstname" value="{{ __('vespaket.first_name') }}" />
                    <x-text-input wire:model="newUser.firstname" id="new-user-firstname" type="text" class="mt-1 w-full border-gray-700 bg-yellow-200" />
                    <x-input-error class="mt-1" :messages="$errors->get('newUser.firstname')" />
                </div>
                <div>
                    <x-input-label for="new-user-name" value="{{ __('Name') }}" />
                    <x-text-input wire:model="newUser.name" id="new-user-name" type="text" class="mt-1 w-full border-gray-700 bg-yellow-200" />
                    <x-input-error class="mt-1" :messages="$errors->get('newUser.name')" />
                </div>
                <div>
                    <x-input-label for="new-user-email" value="{{ __('Email') }}" />
                    <x-text-input wire:model="newUser.email" id="new-user-email" type="email" class="mt-1 w-full border-gray-700 bg-yellow-200" />
                    <x-input-error class="mt-1" :messages="$errors->get('newUser.email')" />
                </div>
                <div>
                    <x-input-label for="new-user-password" value="{{ __('Password') }}" />
                    <x-text-input wire:model="newUser.password" id="new-user-password" type="password" class="mt-1 w-full border-gray-700 bg-yellow-200" />
                    <x-input-error class="mt-1" :messages="$errors->get('newUser.password')" />
                </div>
                <div>
                    <x-input-label for="new-user-role" value="{{ __('vespaket.role') }}" />
                    <select wire:model="newUser.role" id="new-user-role" class="mt-1 w-full rounded-md border-gray-700 bg-yellow-200 focus:border-yellow-500 focus:ring-yellow-500">
                        <option value="player">{{ __('vespaket.player') }}</option>
                        <option value="parent">{{ __('vespaket.parent') }}</option>
                        <option value="coach">{{ __('vespaket.coach') }}</option>
                    </select>
                    <x-input-error class="mt-1" :messages="$errors->get('newUser.role')" />
                </div>
            </div>
            <div class="mt-5">
                <p class="font-semibold">{{ __('vespaket.associate_members') }}</p>
                @if($this->associatedMembers($newUser['selectedMembers'])->isNotEmpty())
                    <div class="mt-2 space-y-1 rounded-lg border border-gray-700 bg-yellow-200 p-2 text-sm">
                        @foreach($this->associatedMembers($newUser['selectedMembers']) as $member)
                            <p>{{ $member->prenom }} {{ $member->name }} ({{ $newUser['selectedMembers'][$member->id] }})</p>
                        @endforeach
                    </div>
                @endif
                <x-text-input wire:model.live.debounce.250ms="memberSearch" id="new-user-member-search" type="search" class="mt-2 w-full border-gray-700 bg-yellow-200" placeholder="{{ __('vespaket.search_member') }}" />
                <div class="mt-2 max-h-60 space-y-2 overflow-y-auto rounded-lg border border-gray-700 p-2 text-black">
                    @foreach($this->filteredMembers() as $member)
                        <label class="flex flex-col gap-2 rounded-lg bg-yellow-200 p-3 sm:flex-row">
                            <span class="w-64">{{ $member->prenom }} {{ $member->name }} ({{ $member->type }})</span>
                            <select wire:model="newUser.selectedMembers.{{ $member->id }}" class="rounded border-gray-700 bg-yellow-200 text-black p-1 w-32">
                                <option value="">--</option>
                                <option value="{{ \App\Enums\MemberRelation::PARENT }}">{{ __('vespaket.parent') }}</option>
                                <option value="{{ \App\Enums\MemberRelation::SELF }}">{{ __('vespaket.self') }}</option>
                                <option value="{{ \App\Enums\MemberRelation::COACH }}">{{ __('vespaket.coach') }}</option>
                            </select>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <button wire:click="cancelCreate" type="button" class="rounded-lg border border-gray-500 px-3 py-2 font-semibold text-gray-500 hover:bg-gray-700">
                    {{ __('actions.cancel') }}
                </button>
                <button wire:click="invit" type="button" class="rounded-lg border border-blue-500 px-3 py-2 font-semibold text-blue-500 hover:bg-blue-950">
                    {{ __('vespaket.create_invitation') }}
                </button>
                <button wire:click="createUser" type="button" class="rounded-lg bg-yellow-400 px-3 py-2 font-semibold text-black hover:bg-yellow-300">
                    {{ __('actions.save') }}
                </button>
            </div>
        </div>
    @endif
    @if($usersLastPage > 1)
        <div class="flex items-center justify-between gap-4 text-sm text-gray-700">
            <button wire:click="previousUsersPage" type="button" @disabled($usersPage === 1) class="rounded-lg border border-gray-700 px-3 py-2 font-semibold disabled:cursor-not-allowed disabled:opacity-40">
                {!! __('pagination.previous') !!}
            </button>
            <span>{{ $usersPage }} / {{ $usersLastPage }}</span>
            <button wire:click="nextUsersPage" type="button" @disabled($usersPage === $usersLastPage) class="rounded-lg border border-gray-700 px-3 py-2 font-semibold disabled:cursor-not-allowed disabled:opacity-40">
                {!! __('pagination.next') !!}
            </button>
        </div>
    @endif
    <div class="overflow-hidden rounded-xl border border-gray-800 bg-gray-900 shadow">
        <table class="w-full text-left text-sm text-white">
            <thead class="bg-black text-xs uppercase tracking-wide text-yellow-400">
                <tr>
                    <th class="px-4 py-3">{{ __('actions.user') }}</th>
                    <th></th>
                    <th class="px-4 py-3 text-right">{{ __('actions.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $index => $user)
                    <tr wire:key="user-{{ $user['id'] }}" class="border-b border-gray-800 last:border-0">
                        <td class="px-4 py-4">
                            <p class="font-semibold">{{ $user['firstname'] }} {{ $user['name'] }}</p>
                            <p class="mt-1 text-xs text-gray-400">{{ $user['email'] }}</p>
                        </td>
                        <td>
                            <span class="mt-1 text-xs text-gray-300"> {{ $user['memberNames'] }} </span>
                        </td>
                        <td class="whitespace-nowrap px-2 py-2 text-right">
                            <button wire:click="editUser({{ $index }})" type="button" class="ml-1 inline-flex h-8 w-8 items-center justify-center rounded-lg border border-yellow-400 bg-yellow-300 p-1 font-semibold text-black hover:bg-yellow-300">
                                <img src="{{ asset('images/parametres.png') }}" class="h-4 w-4 object-contain"/>
                            </button>
                            <button wire:click="delete({{ $user['id'] }})" wire:confirm="{{ __('vespaket.confirm_delete') }}" type="button" class="ml-1 inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-400 p-1 font-semibold text-red-400 hover:bg-red-950">
                                <img src="{{ asset('images/supprimer.png') }}" class="h-4 w-4 object-contain" />
                            </button>
                            <button wire:click="sendNotification({{ $user['id'] }})" type="button" class="ml-1 inline-flex h-8 w-8 items-center justify-center rounded-lg border border-blue-400 p-1 font-semibold text-blue-300 hover:bg-blue-950" title="{{ __('vespaket.send_notification') }}">
                                <img src="{{ asset('images/notification.png') }}" class="h-4 w-4 object-contain" />
                            </button>
                        </td>
                    </tr>

                    @if($editingIndex === $index)
                        <tr wire:key="user-edit-{{ $user['id'] }}" >
                            <td colspan="3" class="px-4 py-4">
                                <div class="px-4 py-4 m-2 border border-black bg-yellow-100 text-black">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <x-input-label for="user-firstname-{{ $user['id'] }}" value="{{ __('vespaket.first_name') }}" />
                                            <x-text-input wire:model="users.{{ $index }}.firstname" id="user-firstname-{{ $user['id'] }}" type="text" class="mt-1 w-full border-gray-700 bg-yellow-200" />
                                            <x-input-error class="mt-1" :messages="$errors->get('users.' . $index . '.firstname')" />
                                        </div>
                                        <div>
                                            <x-input-label for="user-name-{{ $user['id'] }}" value="{{ __('Name') }}" class="text-gray-900" />
                                            <x-text-input wire:model="users.{{ $index }}.name" id="user-name-{{ $user['id'] }}" type="text" class="mt-1 w-full border-gray-700 bg-yellow-200" />
                                            <x-input-error class="mt-1" :messages="$errors->get('users.' . $index . '.name')" />
                                        </div>
                                        <div>
                                            <x-input-label for="user-email-{{ $user['id'] }}" value="{{ __('Email') }}" class="text-gray-900" />
                                            <x-text-input wire:model="users.{{ $index }}.email" id="user-email-{{ $user['id'] }}" type="email" class="mt-1 w-full border-gray-700 bg-yellow-200" />
                                            <x-input-error class="mt-1" :messages="$errors->get('users.' . $index . '.email')" />
                                        </div>
                                        <div>
                                            <x-input-label for="user-password-{{ $user['id'] }}" value="{{ __('Password') }}" class="text-gray-900" />
                                            <x-text-input wire:model="users.{{ $index }}.password" id="user-password-{{ $user['id'] }}" type="password" class="mt-1 w-full border-gray-700 bg-yellow-200" placeholder="{{ __('vespaket.leave_blank') }}" />
                                            <x-input-error class="mt-1" :messages="$errors->get('users.' . $index . '.password')" />
                                        </div>
                                        <div>
                                            <x-input-label for="user-role-{{ $user['id'] }}" value="{{ __('vespaket.role') }}" class="text-gray-900" />
                                            <select wire:model="users.{{ $index }}.role" id="user-role-{{ $user['id'] }}" class="mt-1 w-full rounded-md border-gray-700 bg-yellow-200 focus:border-yellow-500 focus:ring-yellow-500">
                                                <option value="player">{{ __('vespaket.player') }}</option>
                                                <option value="parent">{{ __('vespaket.parent') }}</option>
                                                <option value="coach">{{ __('vespaket.coach') }}</option>
                                            </select>
                                            <x-input-error class="mt-1" :messages="$errors->get('users.' . $index . '.role')" />
                                        </div>
                                    </div>

                                    <div class="mt-5">
                                        <p class="font-semibold text-gray-900">{{ __('vespaket.associate_members') }}</p>
                                        @if($this->associatedMembers($user['selectedMembers'])->isNotEmpty())
                                            <div class="mt-2 space-y-1 rounded-lg border border-gray-700 bg-yellow-200 p-2 text-sm">
                                                @foreach($this->associatedMembers($user['selectedMembers']) as $member)
                                                    <p>{{ $member->prenom }} {{ $member->name }} ({{ $user['selectedMembers'][$member->id] }})</p>
                                                @endforeach
                                            </div>
                                        @endif
                                        <x-text-input wire:model.live.debounce.250ms="memberSearch" id="user-member-search-{{ $user['id'] }}" type="search" class="mt-2 w-full border-gray-700 bg-yellow-200" placeholder="{{ __('vespaket.search_member') }}" />
                                        <div class="mt-2 max-h-60 space-y-2 overflow-y-auto rounded-lg border border-gray-700 p-2">
                                            @foreach($this->filteredMembers() as $member)
                                                <label class="flex flex-col gap-2 rounded-lg bg-yellow-200 p-3 sm:flex-row ">
                                                    <span class="w-64">{{ $member->prenom }} {{ $member->name }} ({{ $member->type }})</span>
                                                    <select wire:model="users.{{ $index }}.selectedMembers.{{ $member->id }}" class="rounded border-gray-700 bg-yellow-200 p-1 w-32">
                                                        <option value="">--</option>
                                                        <option value="{{ \App\Enums\MemberRelation::PARENT }}">{{ __('vespaket.parent') }}</option>
                                                        <option value="{{ \App\Enums\MemberRelation::SELF }}">{{ __('vespaket.self') }}</option>
                                                        <option value="{{ \App\Enums\MemberRelation::COACH }}">{{ __('vespaket.coach') }}</option>
                                                    </select>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="mt-4 flex justify-end gap-2">
                                        <button wire:click="cancelEdit" type="button" class="rounded-lg border border-gray-500 px-3 py-2 font-semibold text-gray-500 hover:bg-gray-700">
                                            {{ __('actions.cancel') }}
                                        </button>
                                        <button wire:click="saveUser({{ $index }})" type="button" class="rounded-lg bg-yellow-400 px-3 py-2 font-semibold text-black hover:bg-yellow-300">
                                            {{ __('actions.save') }}
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="2" class="px-4 py-6 text-center text-gray-400">{{ __('vespaket.no_user') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($usersLastPage > 1)
        <div class="flex items-center justify-between gap-4 text-sm text-gray-700">
            <button wire:click="previousUsersPage" type="button" @disabled($usersPage === 1) class="rounded-lg border border-gray-700 px-3 py-2 font-semibold disabled:cursor-not-allowed disabled:opacity-40">
                {!! __('pagination.previous') !!}
            </button>
            <span>{{ $usersPage }} / {{ $usersLastPage }}</span>
            <button wire:click="nextUsersPage" type="button" @disabled($usersPage === $usersLastPage) class="rounded-lg border border-gray-700 px-3 py-2 font-semibold disabled:cursor-not-allowed disabled:opacity-40">
                {!! __('pagination.next') !!}
            </button>
        </div>
    @endif
</div>
