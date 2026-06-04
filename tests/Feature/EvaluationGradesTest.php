<?php

use App\Models\Evaluation;
use App\Models\ModuleGradeSummary;
use App\Models\TrainingModule;
use App\Models\User;

test('formateur can save and publish module grades from the combined entry page', function () {
    $this->seed();

    $formateur = User::where('email', 'formateur@ofppt-edu.ma')->firstOrFail();
    $pair = $formateur->teachingGroups()->firstOrFail();
    $module = TrainingModule::findOrFail($pair->pivot->module_id);
    $students = User::role(User::ROLE_STAGIAIRE)
        ->approved()
        ->where('group_id', $pair->id)
        ->orderBy('id')
        ->get();

    $grades = $students->mapWithKeys(fn (User $student) => [
        $student->id => [
            Evaluation::TYPE_CC1 => ['score' => '12,5'],
            Evaluation::TYPE_CC2 => ['score' => 14],
            Evaluation::TYPE_CC3 => ['score' => 16],
            Evaluation::TYPE_EFM => ['score' => 32],
        ],
    ])->all();

    $payload = [
        'group_id' => $pair->id,
        'module_id' => $module->id,
        'evaluation_date' => now()->toDateString(),
        'grades' => $grades,
    ];

    $this->actingAs($formateur)
        ->post(route('evaluations.grades.store'), $payload + ['action' => 'draft'])
        ->assertRedirect(route('evaluations.grades', [
            'group_id' => $pair->id,
            'module_id' => $module->id,
            'evaluation_date' => now()->toDateString(),
        ]));

    expect(Evaluation::where('group_id', $pair->id)->where('module_id', $module->id)->where('status', Evaluation::STATUS_DRAFT)->count())
        ->toBe(count($module->evaluationTypes()));

    $this->actingAs($formateur)
        ->post(route('evaluations.grades.store'), $payload + ['action' => 'publish'])
        ->assertRedirect(route('evaluations.grades', [
            'group_id' => $pair->id,
            'module_id' => $module->id,
            'evaluation_date' => now()->toDateString(),
        ]));

    expect(Evaluation::where('group_id', $pair->id)->where('module_id', $module->id)->where('status', Evaluation::STATUS_PUBLISHED)->count())
        ->toBe(count($module->evaluationTypes()));

    $summary = ModuleGradeSummary::where('stagiaire_id', $students->first()->id)
        ->where('group_id', $pair->id)
        ->where('module_id', $module->id)
        ->firstOrFail();

    expect((float) $summary->moy_cc)->toBe(14.17)
        ->and((float) $summary->moy_module)->toBe(15.39);
});
