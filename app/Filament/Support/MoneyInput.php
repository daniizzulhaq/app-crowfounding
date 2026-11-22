<?php

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;
use Filament\Support\RawJs;

final class MoneyInput
{
    /** Rupiah input "25.000" stored as integer 25000. */
    public static function make(string $name): TextInput
    {
        return TextInput::make($name)
            ->prefix('Rp')
            ->mask(RawJs::make("\$money(\$input, ',', '.', 0)"))
            ->stripCharacters('.')
            ->numeric()
            ->integer()
            ->extraInputAttributes(['class' => 'tabular-nums']);
    }
}
