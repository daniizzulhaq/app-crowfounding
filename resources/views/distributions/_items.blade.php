@foreach ($distributions as $distribution)
    <x-distribution.card :distribution="$distribution" />
@endforeach
