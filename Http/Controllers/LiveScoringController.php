<?php

namespace App\Http\Controllers;

use App\Models\FootballMatch;
use App\Models\SquadMember;
use App\Models\PlayerCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LiveScoringController extends Controller
{
    public function show($id)
    {
        $user = Auth::guard('site')->user();

        $match = FootballMatch::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['homeTeam', 'awayTeam', 'goals', 'cards.player', 'substitutions.playerOut', 'substitutions.playerIn'])
            ->first();

        if (!$match) {
            return redirect()->route('control-panel.overview');
        }

        $team1Players = SquadMember::where('match_id', $id)
            ->where('team_id', $match->team1_id)
            ->orderBy('jersey_number')
            ->get();

        $team2Players = SquadMember::where('match_id', $id)
            ->where('team_id', $match->team2_id)
            ->orderBy('jersey_number')
            ->get();

        $redCardPlayerIds = PlayerCard::where('match_id', $id)
            ->where('card_type', 'red')
            ->pluck('player_id')
            ->toArray();

        // Update status to live
        if ($match->status !== 'live') {
            $match->update(['status' => 'live']);
        }

        return view('matches.live-scoring', compact(
            'match',
            'team1Players',
            'team2Players',
            'redCardPlayerIds'
        ));
    }
}