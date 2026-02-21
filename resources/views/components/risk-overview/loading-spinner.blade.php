@props(['class' => 'py-8'])

<div {{ $attributes->merge(['class' => 'flex items-center justify-center ' . $class]) }}>
    <i class="fa-regular fa-spinner-third fa-spin text-3xl text-blue-500"></i>
</div>
