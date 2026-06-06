<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function updateToken(Request $request)
    {
       $request->user()->update(['token' => $request->token]);
         return response()->json(['message' => 'Token saved successfully']);
    }
}
