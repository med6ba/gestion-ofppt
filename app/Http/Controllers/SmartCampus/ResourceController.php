<?php

namespace App\Http\Controllers\SmartCampus;

use App\Http\Controllers\Controller;
use App\Models\Filiere;
use App\Models\Group;
use App\Models\Room;
use App\Models\TrainingModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResourceController extends Controller
{
    public function index(): View
    {
        return view('resources.index', [
            'filieres' => Filiere::withCount('groups')->orderBy('code')->get(),
            'groups' => Group::with('filiere')->withCount('stagiaires')->orderBy('code')->get(),
            'modules' => TrainingModule::orderBy('code')->get(),
            'rooms' => Room::orderBy('code')->get(),
        ]);
    }

    public function storeFiliere(Request $request): RedirectResponse
    {
        Filiere::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40', 'unique:filieres,code'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]));

        return back()->with('status', 'Filiere created.');
    }

    public function storeGroup(Request $request): RedirectResponse
    {
        Group::create($request->validate([
            'filiere_id' => ['required', 'exists:filieres,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40', 'unique:groups,code'],
            'year_level' => ['nullable', 'string', 'max:80'],
            'capacity' => ['required', 'integer', 'between:1,80'],
        ]));

        return back()->with('status', 'Group created.');
    }

    public function storeModule(Request $request): RedirectResponse
    {
        TrainingModule::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40', 'unique:modules,code'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]));

        return back()->with('status', 'Module created.');
    }

    public function storeRoom(Request $request): RedirectResponse
    {
        Room::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40', 'unique:rooms,code'],
            'capacity' => ['required', 'integer', 'between:1,200'],
            'type' => ['required', 'in:classroom,lab,workshop,amphi'],
        ]));

        return back()->with('status', 'Room created.');
    }
}
