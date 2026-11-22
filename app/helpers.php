<?php

use App\Support\Rupiah;

if (! function_exists('rupiah')) {
    function rupiah(int|string|null $amount): string
    {
        return Rupiah::format((int) $amount);
    }
}
