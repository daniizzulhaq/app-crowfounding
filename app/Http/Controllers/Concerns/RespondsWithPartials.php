<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

trait RespondsWithPartials
{
    protected function wantsPartial(Request $request): bool
    {
        return $request->headers->has('X-Partial');
    }

    /**
     * Partial HTML for infinite scroll: rendered items + next page url in a header.
     */
    protected function partial(string $view, array $data, Paginator $paginator): Response
    {
        return response()
            ->view($view, $data)
            ->header('X-Next-Page', (string) $paginator->nextPageUrl())
            ->header('Vary', 'X-Partial');
    }
}
