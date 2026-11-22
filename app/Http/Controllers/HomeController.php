<?php

namespace App\Http\Controllers;

use App\Queries\HomePageData;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(HomePageData $data): View
    {
        return view('home', $data->get());
    }
}
