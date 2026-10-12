@props(['code' => false])

<span {{ $attributes->class(['card-text', 'code' => $code]) }}>{{ $slot }}</span>
