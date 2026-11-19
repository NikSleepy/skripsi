@props(['disabled' => false])

@php
    $name = $attributes->get('name');
    $invalid = $name ? $errors->has($name) : false;
@endphp

<input @disabled($disabled)
       {{ $attributes->merge(['class' => 'input-base'.($invalid ? ' input-error' : '')]) }}>
