<?php

namespace App\Http\Controllers\SmartCampus;

use App\Http\Controllers\Controller;
use App\Http\Requests\AskCampusAiRequest;
use App\Services\CampusAiService;
use Illuminate\View\View;

class AiAssistantController extends Controller
{
    public function index(): View
    {
        return view('ai.index', [
            'answer' => null,
            'question' => null,
            'aiAvailable' => (bool) config('smartcampus.groq.api_key'),
        ]);
    }

    public function ask(AskCampusAiRequest $request, CampusAiService $campusAi): View
    {
        $question = $request->validated('question');

        return view('ai.index', [
            'answer' => $campusAi->answer($request->user(), $question),
            'question' => $question,
            'aiAvailable' => (bool) config('smartcampus.groq.api_key'),
        ]);
    }
}
