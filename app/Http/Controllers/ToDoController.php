<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ToDoController extends Controller
{
    public function test()
    {
        return response([
            auth()->user()->getAuthIdentifier()
        ], 200);
    }
}
