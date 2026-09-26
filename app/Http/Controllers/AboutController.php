<?php

namespace App\Http\Controllers;

use App\Models\AboutPage;
use Illuminate\View\View;

class AboutController extends Controller
{
    /** «Chi siamo»: pagina pubblica, raggiungibile anche dalla presentazione. */
    public function show(): View
    {
        return view('about', ['about' => AboutPage::query()->first()]);
    }
}
