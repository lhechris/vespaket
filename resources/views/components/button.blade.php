@props(['active' => false])

<button {{ $attributes->class([
    'inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest',
    'focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150',
    'bg-indigo-600 text-white hover:bg-indigo-500 focus:bg-indigo-500 active:bg-indigo-700' => $active,
    'bg-gray-800 text-white hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900' => ! $active,
]) }}>
    {{ $slot }}
</button>