<tr {{ $attributes->merge(['class' =>'odd:bg-gray-800 even:bg-gray-900 border-b border-default']) }}>
    {{ $slot }}
</tr>