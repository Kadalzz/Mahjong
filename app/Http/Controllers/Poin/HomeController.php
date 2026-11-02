<?php

namespace App\Http\Controllers\Poin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('poin.home', ['player' => $request->user('player')]);
    }
}
