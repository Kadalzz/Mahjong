@props(['size' => 56, 'src' => null, 'alt' => ''])
<span {{ $attributes->class(['pn-avatar', "pn-avatar--{$size}"]) }}>
    @if($src)
        <img class="pn-avatar__image" src="{{ $src }}" alt="{{ $alt }}">
    @else
        <span class="pn-avatar__silhouette" style="--icon: url('{{ asset('images/poin/icons/user.svg') }}')"></span>
    @endif
</span>
