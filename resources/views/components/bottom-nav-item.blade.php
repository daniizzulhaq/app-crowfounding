@props(['item', 'active' => false])
<li>
    <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
       @class(['nav-item', 'is-active' => $active])>
        <span class="nav-item-icon"><x-glyph :name="$item['icon']" class="size-[22px]" /></span>
        <span>{{ $item['label'] }}</span>
    </a>
</li>
