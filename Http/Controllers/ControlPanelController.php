<?php

namespace App\Http\Controllers;

use App\Models\FootballMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ControlPanelController extends Controller
{
    public function overview()
    {
        $user = Auth::guard('site')->user();
        
        $matches = FootballMatch::where('user_id', $user->id)
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('control-panel.overview', compact('user', 'matches'));
    }
}