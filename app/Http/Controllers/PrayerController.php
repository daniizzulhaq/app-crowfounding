<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithPartials;
use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PrayerController extends Controller
{
    use RespondsWithPartials;

    public function __invoke(Request $request): View|Response
    {
        $prayers = Donation::query()
            ->withPublicPrayer()
            ->publicColumns()
            ->with('campaign:id,title,slug')
            ->latest('paid_at')
            ->simplePaginate(12);

        if ($this->wantsPartial($request)) {
            return $this->partial('prayers._items', compact('prayers'), $prayers);
        }

        return view('prayers.index', compact('prayers'));
    }
}
