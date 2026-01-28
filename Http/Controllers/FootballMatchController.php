<?php

namespace App\Http\Controllers;

use App\Models\FootballMatch;
use App\Models\FootballTeam;
use App\Models\SquadMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FootballMatchController extends Controller
{
    public function setupForm()
    {
        $user = Auth::guard('site')->user();
        $teams = FootballTeam::where('user_id', $user->id)->get();

        return view('matches.setup-match', compact('teams'));
    }

    public function storeMatch(Request $request)
    {
        $request->validate([
            'match_date' => 'required|date',
            'match_time' => 'required',
            'match_duration' => 'required|integer|min:1',
        ]);

        $user = Auth::guard('site')->user();

        DB::beginTransaction();
        try {
            // Handle Team 1
            $team1Id = $request->team1_id;
            if (!empty($request->new_team1)) {
                $team1 = FootballTeam::create([
                    'user_id' => $user->id,
                    'name' => $request->new_team1,
                ]);
                $team1Id = $team1->id;
            }

            // Handle Team 2
            $team2Id = $request->team2_id;
            if (!empty($request->new_team2)) {
                $team2 = FootballTeam::create([
                    'user_id' => $user->id,
                    'name' => $request->new_team2,
                ]);
                $team2Id = $team2->id;
            }

            if (empty($team1Id) || empty($team2Id)) {
                throw new \Exception('Both teams must be selected or created.');
            }

            if ($team1Id == $team2Id) {
                throw new \Exception('Teams must be different.');
            }

            // Create Match
            $match = FootballMatch::create([
                'user_id' => $user->id,
                'team1_id' => $team1Id,
                'team2_id' => $team2Id,
                'match_date' => $request->match_date,
                'match_time' => $request->match_time,
                'duration_minutes' => $request->match_duration,
                'status' => 'pending',
            ]);

            // Add Team 1 Players
            if ($request->has('team1_players')) {
                foreach ($request->team1_players as $index => $playerName) {
                    if (!empty($playerName)) {
                        SquadMember::create([
                            'match_id' => $match->id,
                            'team_id' => $team1Id,
                            'player_name' => $playerName,
                            'jersey_number' => $request->team1_jersey[$index] ?? null,
                        ]);
                    }
                }
            }

            // Add Team 2 Players
            if ($request->has('team2_players')) {
                foreach ($request->team2_players as $index => $playerName) {
                    if (!empty($playerName)) {
                        SquadMember::create([
                            'match_id' => $match->id,
                            'team_id' => $team2Id,
                            'player_name' => $playerName,
                            'jersey_number' => $request->team2_jersey[$index] ?? null,
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('matches.live-scoring', $match->id);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function destroyMatch($id)
    {
        $user = Auth::guard('site')->user();
        
        $match = FootballMatch::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if ($match) {
            $match->delete();
            session()->flash('message', 'Match report deleted successfully.');
            session()->flash('msg_type', 'success');
        } else {
            session()->flash('message', 'Error deleting match.');
            session()->flash('msg_type', 'error');
        }

        return redirect()->route('control-panel.overview');
    }

    public function finalReport($id)
    {
        $user = Auth::guard('site')->user();

        $match = FootballMatch::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['homeTeam', 'awayTeam', 'goals'])
            ->first();

        if (!$match) {
            return redirect()->route('control-panel.overview');
        }

        $team1Goals = $match->goals->where('team_id', $match->team1_id)->sortBy('minute');
        $team2Goals = $match->goals->where('team_id', $match->team2_id)->sortBy('minute');

        return view('matches.final-report', compact('match', 'team1Goals', 'team2Goals'));
    }
}