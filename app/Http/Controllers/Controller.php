<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    /**
     * `$this->authorize()` nei controller: le policy sono la sola fonte dei
     * permessi, e vanno interrogate anche qui, non solo da Filament.
     */
    use AuthorizesRequests;
}
