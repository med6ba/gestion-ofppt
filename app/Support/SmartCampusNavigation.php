<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SmartCampusNavigation
{
    private const ADMIN_ROLES = [User::ROLE_DIRECTEUR, User::ROLE_SURVEILLANT];
    private const ALL_ROLES = [User::ROLE_DIRECTEUR, User::ROLE_SURVEILLANT, User::ROLE_FORMATEUR, User::ROLE_STAGIAIRE];

    public static function groupsFor(?User $user): Collection
    {
        if (!$user) {
            return collect();
        }

        $role = $user->role;
        $canSee = fn (array $item): bool => empty($item['roles']) || in_array($role, $item['roles'], true);

        return collect(self::matrix($user))
            ->filter($canSee)
            ->map(function (array $group) use ($canSee) {
                if (!empty($group['children'])) {
                    $group['children'] = collect($group['children'])->filter($canSee)->values()->all();
                }

                return $group;
            })
            ->filter(fn (array $group) => empty($group['children']) || count($group['children']) > 0)
            ->values();
    }

    public static function flatFor(?User $user): Collection
    {
        return self::groupsFor($user)->flatMap(fn (array $item) => $item['children'] ?? [$item])->values();
    }

    public static function mobileFor(?User $user, int $limit = 5): Collection
    {
        return self::flatFor($user)
            ->filter(fn (array $item) => $item['mobile'] ?? false)
            ->sortBy(fn (array $item) => $item['mobilePriority'] ?? 100)
            ->take($limit)
            ->values();
    }

    public static function hrefFor(array $item): string
    {
        return isset($item['route'])
            ? route($item['route'], $item['params'] ?? [])
            : ($item['url'] ?? '#');
    }

    public static function activeGroups(Collection $groups, Request $request, ?User $user): Collection
    {
        return $groups
            ->filter(fn (array $group) => collect($group['children'] ?? [])->contains(fn (array $item) => self::isActive($item, $request, $user)))
            ->pluck('label')
            ->values();
    }

    public static function isActive(array $item, Request $request, ?User $user): bool
    {
        return match ($item['context'] ?? null) {
            'dashboard' => $user && $request->routeIs('dashboard.redirect', $user->dashboardRoute()),
            'users.all' => self::isUsersAllActive($request, $user),
            'users.create' => $request->routeIs('users.index') && $request->query('panel') === 'create',
            'users.stagiaires' => self::isUsersStagiairesActive($request),
            'users.pending' => $request->routeIs('stagiaires.*')
                || ($request->routeIs('users.index') && $request->query('role') === User::ROLE_STAGIAIRE && $request->query('status') === 'pending'),
            'timetable.manage' => $request->routeIs('timetable.index', 'timetable.edit', 'timetable.store', 'timetable.update', 'timetable.destroy', 'timetable.active-week'),
            'resources' => $request->routeIs('resources.*'),
            'timetable.mine' => $request->routeIs('timetable.mine'),
            'formateur.groups' => $request->routeIs('formateur.teaching') && $request->query('tab', 'groups') === 'groups',
            'formateur.modules' => $request->routeIs('formateur.teaching') && $request->query('tab') === 'modules',
            'formateur.students' => $request->routeIs('formateur.teaching') && $request->query('tab') === 'students',
            'stagiaire.modules' => $request->routeIs('stagiaire.modules'),
            'attendance.reports' => $request->routeIs('attendance.reports') && $request->query('focus') !== 'severe-late',
            'attendance.severe-late' => $request->routeIs('attendance.severe-late.*')
                || ($request->routeIs('attendance.reports') && $request->query('focus') === 'severe-late'),
            'attendance.sessions' => $request->routeIs('attendance.index', 'attendance.show', 'attendance.qr.*', 'attendance.manual.*', 'attendance.late.*', 'attendance.correction.*', 'attendance.finalize'),
            'attendance.check-in' => $request->routeIs('attendance.check-in', 'attendance.scan', 'attendance.code.*', 'attendance.late.*'),
            'attendance.mine' => $request->routeIs('attendance.mine'),
            'attendance.xp' => $request->routeIs('attendance.leaderboard'),
            'announcements' => $request->routeIs('announcements.*'),
            'chat' => $request->routeIs('chat.*'),
            'ai' => $request->routeIs('ai.*'),
            'notifications' => $request->routeIs('notifications.*'),
            'profile' => self::isOwnProfileActive($request, $user),
            'settings' => $request->routeIs('settings.*'),
            default => self::matchesRoutePatterns($item, $request),
        };
    }

    private static function matrix(User $user): array
    {
        return [
            ['label' => 'Accueil', 'icon' => 'dashboard', 'route' => $user->dashboardRoute(), 'context' => 'dashboard', 'roles' => self::ALL_ROLES, 'mobile' => true, 'mobilePriority' => 10],
            ['label' => 'Utilisateurs', 'icon' => 'users', 'roles' => self::ADMIN_ROLES, 'children' => [
                ['label' => 'Tous les utilisateurs', 'icon' => 'users', 'route' => 'users.index', 'context' => 'users.all', 'roles' => [User::ROLE_DIRECTEUR]],
                ['label' => 'Créer un utilisateur', 'icon' => 'user-plus', 'route' => 'users.index', 'params' => ['panel' => 'create'], 'context' => 'users.create', 'roles' => [User::ROLE_DIRECTEUR]],
                ['label' => 'Stagiaires', 'icon' => 'users', 'route' => 'users.index', 'params' => ['role' => User::ROLE_STAGIAIRE], 'context' => 'users.stagiaires', 'roles' => self::ADMIN_ROLES, 'mobile' => true, 'mobilePriority' => 20],
                ['label' => 'Approbations', 'icon' => 'user-clock', 'route' => 'users.index', 'params' => ['role' => User::ROLE_STAGIAIRE, 'status' => 'pending'], 'context' => 'users.pending', 'roles' => self::ADMIN_ROLES],
            ]],
            ['label' => 'Enseignement', 'icon' => 'academic', 'roles' => self::ALL_ROLES, 'children' => [
                ['label' => 'Emploi campus', 'icon' => 'calendar', 'route' => 'timetable.index', 'context' => 'timetable.manage', 'roles' => self::ADMIN_ROLES, 'mobile' => true, 'mobilePriority' => 30],
                ['label' => 'Ressources', 'icon' => 'layers', 'route' => 'resources.index', 'context' => 'resources', 'roles' => self::ADMIN_ROLES],
                ['label' => 'Mon emploi du temps', 'icon' => 'calendar', 'route' => 'timetable.mine', 'context' => 'timetable.mine', 'roles' => [User::ROLE_FORMATEUR, User::ROLE_STAGIAIRE], 'mobile' => true, 'mobilePriority' => 20],
                ['label' => 'Mes groupes', 'icon' => 'users-group', 'route' => 'formateur.teaching', 'params' => ['tab' => 'groups'], 'context' => 'formateur.groups', 'roles' => [User::ROLE_FORMATEUR]],
                ['label' => 'Mes modules', 'icon' => 'layers', 'route' => 'formateur.teaching', 'params' => ['tab' => 'modules'], 'context' => 'formateur.modules', 'roles' => [User::ROLE_FORMATEUR]],
                ['label' => 'Mes stagiaires', 'icon' => 'users', 'route' => 'formateur.teaching', 'params' => ['tab' => 'students'], 'context' => 'formateur.students', 'roles' => [User::ROLE_FORMATEUR]],
                ['label' => 'Mes modules', 'icon' => 'layers', 'route' => 'stagiaire.modules', 'context' => 'stagiaire.modules', 'roles' => [User::ROLE_STAGIAIRE]],
            ]],
            ['label' => 'Présence', 'icon' => 'clock', 'roles' => self::ALL_ROLES, 'children' => [
                ['label' => 'Rapports', 'icon' => 'chart-pie', 'route' => 'attendance.reports', 'context' => 'attendance.reports', 'roles' => self::ADMIN_ROLES, 'mobile' => true, 'mobilePriority' => 40],
                ['label' => 'Retards importants', 'icon' => 'user-minus', 'route' => 'attendance.reports', 'params' => ['focus' => 'severe-late'], 'context' => 'attendance.severe-late', 'roles' => [User::ROLE_SURVEILLANT]],
                ['label' => 'Séances', 'icon' => 'check-circle', 'route' => 'attendance.index', 'context' => 'attendance.sessions', 'roles' => [User::ROLE_FORMATEUR], 'mobile' => true, 'mobilePriority' => 30],
                ['label' => 'Check-in', 'icon' => 'qr', 'route' => 'attendance.check-in', 'context' => 'attendance.check-in', 'roles' => [User::ROLE_STAGIAIRE], 'mobile' => true, 'mobilePriority' => 30],
                ['label' => 'Mon suivi', 'icon' => 'clock', 'route' => 'attendance.mine', 'context' => 'attendance.mine', 'roles' => [User::ROLE_STAGIAIRE], 'mobile' => true, 'mobilePriority' => 40],
                ['label' => 'Présence XP', 'icon' => 'award', 'route' => 'attendance.leaderboard', 'context' => 'attendance.xp', 'roles' => self::ALL_ROLES],
            ]],
            ['label' => 'Communication', 'icon' => 'messages', 'roles' => self::ALL_ROLES, 'children' => [
                ['label' => 'Annonces', 'icon' => 'megaphone', 'route' => 'announcements.index', 'context' => 'announcements', 'roles' => self::ALL_ROLES],
                ['label' => 'Chat', 'icon' => 'chat-bubble', 'route' => 'chat.index', 'context' => 'chat', 'roles' => self::ALL_ROLES, 'mobile' => true, 'mobilePriority' => 50],
                ['label' => 'CampusAI', 'icon' => 'ai', 'route' => 'ai.index', 'context' => 'ai', 'roles' => self::ALL_ROLES],
            ]],
            ['label' => 'Compte', 'icon' => 'settings', 'roles' => self::ALL_ROLES, 'children' => [
                ['label' => 'Notifications', 'icon' => 'bell', 'route' => 'notifications.index', 'context' => 'notifications', 'roles' => self::ALL_ROLES],
                ['label' => 'Profil', 'icon' => 'profile', 'route' => 'profile.show', 'params' => ['user' => $user->id], 'context' => 'profile', 'roles' => self::ALL_ROLES],
                ['label' => 'Paramètres', 'icon' => 'settings', 'route' => 'settings.index', 'context' => 'settings', 'roles' => self::ALL_ROLES],
            ]],
        ];
    }

    private static function isUsersAllActive(Request $request, ?User $user): bool
    {
        $profile = $request->route('user');

        return ($profile instanceof User && !$profile->isStagiaire() && (!$user || (int) $profile->id !== (int) $user->id))
            || ($request->routeIs('users.index') && !$request->query('role') && !$request->query('status') && !$request->query('panel'));
    }

    private static function isUsersStagiairesActive(Request $request): bool
    {
        $profile = $request->route('user');

        return ($profile instanceof User && $profile->isStagiaire())
            || ($request->routeIs('users.index') && $request->query('role') === User::ROLE_STAGIAIRE && $request->query('status') !== 'pending');
    }

    private static function isOwnProfileActive(Request $request, ?User $user): bool
    {
        $profile = $request->route('user');

        return $user
            && $request->routeIs('profile.show')
            && $profile instanceof User
            && (int) $profile->id === (int) $user->id;
    }

    private static function matchesRoutePatterns(array $item, Request $request): bool
    {
        $patterns = array_filter($item['active'] ?? [$item['route'] ?? null]);

        return $patterns && $request->routeIs(...$patterns);
    }
}
