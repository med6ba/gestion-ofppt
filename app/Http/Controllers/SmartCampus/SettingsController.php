<?php

namespace App\Http\Controllers\SmartCampus;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.index', [
            'settings' => Setting::all()->keyBy('key'),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('update-settings');

        $data = $request->except('_token');

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => is_array($value) ? json_encode($value) : $value,
                    'type' => is_array($value) ? 'json' : (is_numeric($value) ? 'integer' : 'string'),
                    'updated_by' => auth()->id(),
                ]
            );
        }

        return back()->with('success', 'Paramètres mis à jour avec succès.');
    }
}
