<?php

use App\Models\User;
use App\Support\SmartCampusNavigation;

function navigationLabelsFor(string $role): array
{
    $user = new User([
        'name' => ucfirst($role),
        'email' => "{$role}@ofppt.test",
        'role' => $role,
        'approval_status' => 'approved',
    ]);
    $user->id = match ($role) {
        User::ROLE_DIRECTEUR => 1,
        User::ROLE_SURVEILLANT => 2,
        User::ROLE_FORMATEUR => 3,
        User::ROLE_STAGIAIRE => 4,
        default => 9,
    };

    return SmartCampusNavigation::flatFor($user)->pluck('label')->all();
}

test('directeur sidebar exposes monitoring and administration without formateur-only actions', function () {
    $labels = navigationLabelsFor(User::ROLE_DIRECTEUR);

    expect($labels)
        ->toContain('Tous les utilisateurs', 'Créer un utilisateur', 'Emploi campus', 'Ressources', 'Rapports', 'Présence XP', 'Chat', 'CampusAI')
        ->not->toContain('Séances', 'Check-in', 'Mes stagiaires');
});

test('surveillant sidebar exposes student approvals and schedule management only', function () {
    $labels = navigationLabelsFor(User::ROLE_SURVEILLANT);

    expect($labels)
        ->toContain('Stagiaires', 'Approbations', 'Emploi campus', 'Ressources', 'Retards importants', 'Chat')
        ->not->toContain('Créer un utilisateur', 'Séances', 'Check-in');
});

test('formateur sidebar exposes teaching and attendance session tools', function () {
    $labels = navigationLabelsFor(User::ROLE_FORMATEUR);

    expect($labels)
        ->toContain('Mon emploi du temps', 'Mes groupes', 'Mes modules', 'Mes stagiaires', 'Séances', 'Présence XP', 'Chat')
        ->not->toContain('Utilisateurs', 'Emploi campus', 'Ressources', 'Check-in');
});

test('stagiaire sidebar exposes learner-only destinations', function () {
    $labels = navigationLabelsFor(User::ROLE_STAGIAIRE);

    expect($labels)
        ->toContain('Mon emploi du temps', 'Mes modules', 'Check-in', 'Mon suivi', 'Présence XP', 'Chat')
        ->not->toContain('Utilisateurs', 'Emploi campus', 'Ressources', 'Séances', 'Rapports');
});
