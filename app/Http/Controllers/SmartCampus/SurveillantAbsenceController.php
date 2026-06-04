<?php

namespace App\Http\Controllers\SmartCampus;

use App\Http\Controllers\Controller;
use App\Models\AbsenceFollowUp;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;

class SurveillantAbsenceController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isSurveillant() || $request->user()->isDirecteur(), 403);

        $groups = Group::withCount(['stagiaires' => function ($q) {
            $q->whereHas('absenceFollowUps', function ($q) {
                $q->whereIn('status', ['pending', 'under_review']);
            });
        }])->get();

        $followUpsQuery = AbsenceFollowUp::with(['stagiaire.behaviorScore', 'group', 'reviewer'])
            ->whereIn('status', ['pending', 'under_review']);

        if ($request->filled('group_id')) {
            $followUpsQuery->where('group_id', $request->group_id);
        }

        $followUps = $followUpsQuery->latest('updated_at')->paginate(20);

        return view('surveillant.absences.index', [
            'groups' => $groups,
            'followUps' => $followUps,
            'selectedGroup' => $request->group_id,
        ]);
    }

    public function show(AbsenceFollowUp $followUp)
    {
        abort_unless(auth()->user()->isSurveillant() || auth()->user()->isDirecteur(), 403);

        $followUp->load(['stagiaire.behaviorScoreLogs' => function($q) {
            $q->latest();
        }, 'stagiaire.behaviorScore', 'group', 'reviewer']);

        if ($followUp->status === 'pending') {
            $followUp->update([
                'status' => 'under_review',
                'reviewed_by' => auth()->id(),
            ]);
        }

        return view('surveillant.absences.show', [
            'followUp' => $followUp,
        ]);
    }

    public function resolve(Request $request, AbsenceFollowUp $followUp)
    {
        abort_unless(auth()->user()->isSurveillant() || auth()->user()->isDirecteur(), 403);

        $data = $request->validate([
            'decision_note' => 'required|string|max:1000',
            'action' => 'required|in:resolved,rejected',
        ]);

        $followUp->update([
            'status' => $data['action'],
            'decision_note' => $data['decision_note'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return redirect()->route('surveillant.absences.index')->with('status', 'Dossier de suivi traité avec succès.');
    }
}
