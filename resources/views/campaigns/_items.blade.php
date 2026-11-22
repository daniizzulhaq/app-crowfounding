@foreach ($campaigns as $campaign)
    <x-campaign.row :campaign="$campaign" />
@endforeach
