@foreach ($prayers as $donation)
    <x-prayer-card :donation="$donation" />
@endforeach
