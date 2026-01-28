<?php

namespace App\Http\Controllers;

use App\Models\FootballMatch;
use App\Models\FootballGoal;
use App\Models\PlayerCard;
use App\Models\PlayerSubstitution;
use App\Models\SquadMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MatchEventsController extends Controller
{
    public function recordGoal(Request $request)
    {
        $request->validate([
            'match_id' => 'required|integer',
            'team_id' => 'required|integer',
            'player_id' => 'required|integer',
            'player_name' => 'required|string',
            'minute' => 'required|integer',
        ]);

        // Check for red card
        $hasRedCard = PlayerCard::where('match_id', $request->match_id)
            ->where('player_id', $request->player_id)
            ->where('card_type', 'red')
            ->exists();

        if ($hasRedCard) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot add goal: Player has a Red Card.'
            ]);
        }

        FootballGoal::create([
            'match_id' => $request->match_id,
            'team_id' => $request->team_id,
            'player_id' => $request->player_id,
            'player_name' => $request->player_name,
            'minute' => $request->minute,
        ]);

        // Recalculate score
        $match = FootballMatch::find($request->match_id);
        $goalCount = FootballGoal::where('match_id', $request->match_id)
            ->where('team_id', $request->team_id)
            ->count();

        if ($request->team_id == $match->team1_id) {
            $match->update(['team1_score' => $goalCount]);
        } else {
            $match->update(['team2_score' => $goalCount]);
        }

        return response()->json(['success' => true, 'score' => $goalCount]);
    }

    public function recordCard(Request $request)
    {
        $request->validate([
            'match_id' => 'required|integer',
            'team_id' => 'required|integer',
            'player_id' => 'required|integer',
            'card_type' => 'required|in:yellow,red',
            'minute' => 'required|integer',
        ]);

        PlayerCard::create($request->only([
            'match_id',
            'team_id',
            'player_id',
            'card_type',
            'minute',
        ]));

        return response()->json(['success' => true]);
    }

    public function recordSubstitution(Request $request)
    {
        $request->validate([
            'match_id' => 'required|integer',
            'team_id' => 'required|integer',
            'player_out_id' => 'required|integer',
            'player_in_id' => 'required|integer',
            'minute' => 'required|integer',
        ]);

        PlayerSubstitution::create($request->only([
            'match_id',
            'team_id',
            'player_out_id',
            'player_in_id',
            'minute',
        ]));

        // Update player status
        SquadMember::where('id', $request->player_out_id)->update(['is_playing' => false]);
        SquadMember::where('id', $request->player_in_id)->update(['is_playing' => true]);

        return response()->json(['success' => true]);
    }

    public function concludeMatch(Request $request)
    {
        $request->validate([
            'match_id' => 'required|integer',
            'duration' => 'required|integer',
        ]);

        FootballMatch::where('id', $request->match_id)->update([
            'status' => 'completed',
            'duration_minutes' => $request->duration,
        ]);

        return response()->json(['success' => true]);
    }
}