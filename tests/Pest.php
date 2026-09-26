<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
// I Feature test usano il database; RefreshDatabase mantiene isolato lo stato tra i test.
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// Gli Unit test avviano Laravel per leggere config/dnd, ma non usano RefreshDatabase.
pest()->extend(TestCase::class)
    ->in('Unit');
