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

    private static function label(string $key): string
    {
        try {
            if (function_exists('app') && app()->bound('translator')) {
                return __($key);
            }
        } catch (\Throwable) {
            // Unit tests can call this helper without booting the Laravel translator.
        }

        $value = self::fallbackTranslation($key);

        return is_string($value) ? $value : $key;
    }

    private static function fallbackTranslation(string $key): mixed
    {
        static $messages = null;

        if ($messages === null) {
            $path = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.'fr'.DIRECTORY_SEPARATOR.'messages.php';
            $messages = is_file($path) ? require $path : [];
        }

        $value = $messages;
        $segments = explode('.', str_starts_with($key, 'messages.') ? substr($key, 9) : $key);

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
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
            'timetable.manage' => $request->routeIs('timetable.index', 'timetable.weekly.*', 'timetable.sessions.*', 'timetable.cancellations.*'),
            'resources' => $request->routeIs('resources.*'),
            'timetable.mine' => $request->routeIs('timetable.mine'),
            'formateur.groups' => $request->routeIs('formateur.teaching') && $request->query('tab', 'groups') === 'groups',
            'formateur.modules' => $request->routeIs('formateur.teaching') && $request->query('tab') === 'modules',
            'formateur.students' => $request->routeIs('formateur.teaching') && $request->query('tab') === 'students',
            'stagiaire.modules' => $request->routeIs('stagiaire.modules'),
            'stagiaire.badge' => $request->routeIs('stagiaire.badge', 'stagiaire.badge.*'),
            'attestations' => $request->routeIs('attestations.*'),
            'absences' => $request->routeIs('absences.*'),
            'formateur.absences' => $request->routeIs('formateur.absences'),
            'surveillant.absences' => $request->routeIs('surveillant.absences.*'),
            'attendance.reports' => $request->routeIs('attendance.reports') && $request->query('focus') !== 'severe-late',
            'attendance.severe-late' => $request->routeIs('attendance.severe-late.*')
                || ($request->routeIs('attendance.reports') && $request->query('focus') === 'severe-late'),
            'attendance.sessions' => $request->routeIs('attendance.index', 'attendance.show', 'attendance.qr.*', 'attendance.manual.*', 'attendance.late.*', 'attendance.correction.*', 'attendance.finalize'),
            'attendance.check-in' => $request->routeIs('attendance.check-in', 'attendance.scan', 'attendance.code.*', 'attendance.late.*'),
            'attendance.mine' => $request->routeIs('attendance.mine'),
            'attendance.xp' => $request->routeIs('attendance.leaderboard'),
            'evaluations.index' => $request->routeIs('evaluations.index'),
            'evaluations.grades' => $request->routeIs('evaluations.grades', 'evaluations.grades.*'),
            'evaluations.statistics' => $request->routeIs('evaluations.statistics'),
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
            ['label' => self::label('messages.nav.home'), 'icon' => 'dashboard', 'route' => $user->dashboardRoute(), 'context' => 'dashboard', 'roles' => self::ALL_ROLES, 'mobile' => true, 'mobilePriority' => 10],
            ['label' => self::label('messages.nav.services'), 'icon' => 'profile', 'categories' => ['documents'], 'roles' => [User::ROLE_DIRECTEUR, User::ROLE_SURVEILLANT, User::ROLE_FORMATEUR, User::ROLE_STAGIAIRE], 'children' => [
                ['label' => self::label('messages.nav.my_badge'), 'icon' => 'qr', 'route' => 'stagiaire.badge', 'context' => 'stagiaire.badge', 'roles' => [User::ROLE_STAGIAIRE], 'mobile' => true, 'mobilePriority' => 25],
                ['label' => self::label('messages.nav.attestation'), 'icon' => 'book', 'route' => 'attestations.index', 'context' => 'attestations', 'roles' => [User::ROLE_STAGIAIRE]],
                ['label' => self::label('messages.nav.absence'), 'icon' => 'calendar', 'route' => 'absences.index', 'context' => 'absences', 'roles' => [User::ROLE_STAGIAIRE]],
                ['label' => self::label('messages.nav.formateur_absence'), 'icon' => 'calendar', 'route' => 'formateur.absences', 'context' => 'formateur.absences', 'roles' => [User::ROLE_FORMATEUR]],
                ['label' => 'Suivi des Absences', 'icon' => 'calendar', 'route' => 'surveillant.absences.index', 'context' => 'surveillant.absences', 'roles' => self::ADMIN_ROLES],
                ['label' => self::label('messages.nav.attestation_requests'), 'icon' => 'book', 'route' => 'attestations.manage', 'context' => 'attestations', 'roles' => self::ADMIN_ROLES],
                ['label' => self::label('messages.nav.absence_requests'), 'icon' => 'calendar', 'route' => 'absences.manage', 'context' => 'absences', 'roles' => self::ADMIN_ROLES],
            ]],
            ['label' => self::label('messages.nav.users'), 'icon' => 'users', 'categories' => ['users'], 'roles' => self::ADMIN_ROLES, 'children' => [
                ['label' => self::label('messages.nav.all_users'), 'icon' => 'users', 'route' => 'users.index', 'context' => 'users.all', 'roles' => [User::ROLE_DIRECTEUR]],
                ['label' => self::label('messages.nav.create_user'), 'icon' => 'user-plus', 'route' => 'users.index', 'params' => ['panel' => 'create'], 'context' => 'users.create', 'roles' => [User::ROLE_DIRECTEUR]],
                ['label' => self::label('messages.nav.stagiaires'), 'icon' => 'users', 'route' => 'users.index', 'params' => ['role' => User::ROLE_STAGIAIRE], 'context' => 'users.stagiaires', 'roles' => self::ADMIN_ROLES, 'mobile' => true, 'mobilePriority' => 20],
                ['label' => self::label('messages.nav.approvals'), 'icon' => 'user-clock', 'route' => 'users.index', 'params' => ['role' => User::ROLE_STAGIAIRE, 'status' => 'pending'], 'context' => 'users.pending', 'roles' => self::ADMIN_ROLES],
            ]],
            ['label' => self::label('messages.nav.teaching'), 'icon' => 'academic', 'categories' => ['schedule'], 'roles' => self::ALL_ROLES, 'children' => [
                ['label' => self::label('messages.nav.campus_timetable'), 'icon' => 'calendar', 'route' => 'timetable.index', 'context' => 'timetable.manage', 'roles' => self::ADMIN_ROLES, 'mobile' => true, 'mobilePriority' => 30],
                ['label' => self::label('messages.nav.resources'), 'icon' => 'layers', 'route' => 'resources.index', 'context' => 'resources', 'roles' => self::ADMIN_ROLES],
                ['label' => self::label('messages.nav.my_timetable'), 'icon' => 'calendar', 'route' => 'timetable.mine', 'context' => 'timetable.mine', 'roles' => [User::ROLE_FORMATEUR, User::ROLE_STAGIAIRE], 'mobile' => true, 'mobilePriority' => 20],
                ['label' => self::label('messages.nav.my_groups'), 'icon' => 'users-group', 'route' => 'formateur.teaching', 'params' => ['tab' => 'groups'], 'context' => 'formateur.groups', 'roles' => [User::ROLE_FORMATEUR]],
                ['label' => self::label('messages.nav.my_modules'), 'icon' => 'layers', 'route' => 'formateur.teaching', 'params' => ['tab' => 'modules'], 'context' => 'formateur.modules', 'roles' => [User::ROLE_FORMATEUR]],
                ['label' => self::label('messages.nav.my_students'), 'icon' => 'users', 'route' => 'formateur.teaching', 'params' => ['tab' => 'students'], 'context' => 'formateur.students', 'roles' => [User::ROLE_FORMATEUR]],
                ['label' => self::label('messages.nav.my_modules'), 'icon' => 'layers', 'route' => 'stagiaire.modules', 'context' => 'stagiaire.modules', 'roles' => [User::ROLE_STAGIAIRE]],
            ]],
            ['label' => self::label('messages.nav.presence'), 'icon' => 'clock', 'categories' => ['risk', 'attendance'], 'roles' => self::ALL_ROLES, 'children' => [
                ['label' => self::label('messages.nav.reports'), 'icon' => 'chart-pie', 'route' => 'attendance.reports', 'context' => 'attendance.reports', 'roles' => self::ADMIN_ROLES, 'mobile' => true, 'mobilePriority' => 40],
                ['label' => self::label('messages.nav.severe_late'), 'icon' => 'user-minus', 'route' => 'attendance.reports', 'params' => ['focus' => 'severe-late'], 'context' => 'attendance.severe-late', 'roles' => [User::ROLE_SURVEILLANT]],
                ['label' => self::label('messages.nav.sessions'), 'icon' => 'check-circle', 'route' => 'attendance.index', 'context' => 'attendance.sessions', 'roles' => [User::ROLE_FORMATEUR], 'mobile' => true, 'mobilePriority' => 30],
                ['label' => self::label('messages.nav.check_in'), 'icon' => 'qr', 'route' => 'attendance.check-in', 'context' => 'attendance.check-in', 'roles' => [User::ROLE_STAGIAIRE], 'mobile' => true, 'mobilePriority' => 30],
                ['label' => self::label('messages.nav.my_tracking'), 'icon' => 'clock', 'route' => 'attendance.mine', 'context' => 'attendance.mine', 'roles' => [User::ROLE_STAGIAIRE], 'mobile' => true, 'mobilePriority' => 40],
                ['label' => self::label('messages.nav.presence_xp'), 'icon' => 'award', 'route' => 'attendance.leaderboard', 'context' => 'attendance.xp', 'roles' => self::ALL_ROLES],
            ]],
            ['label' => self::label('messages.nav.evaluations'), 'icon' => 'star', 'categories' => ['evaluations'], 'roles' => self::ALL_ROLES, 'children' => [
                ['label' => self::label('messages.nav.evaluations_list'), 'icon' => 'list', 'route' => 'evaluations.index', 'context' => 'evaluations.index', 'roles' => self::ALL_ROLES, 'mobile' => true, 'mobilePriority' => 45],
                ['label' => self::label('messages.nav.grade_entry'), 'icon' => 'pencil', 'route' => 'evaluations.grades', 'context' => 'evaluations.grades', 'roles' => [User::ROLE_FORMATEUR]],
                ['label' => self::label('messages.nav.evaluation_stats'), 'icon' => 'chart', 'route' => 'evaluations.statistics', 'context' => 'evaluations.statistics', 'roles' => self::ALL_ROLES],
            ]],
            ['label' => self::label('messages.nav.communication'), 'icon' => 'messages', 'categories' => ['messages', 'chat', 'announcements'], 'roles' => self::ALL_ROLES, 'children' => [
                ['label' => self::label('messages.nav.announcements'), 'icon' => 'megaphone', 'route' => 'announcements.index', 'context' => 'announcements', 'roles' => self::ALL_ROLES],
                ['label' => self::label('messages.common.chat'), 'icon' => 'chat-bubble', 'route' => 'chat.index', 'context' => 'chat', 'roles' => self::ALL_ROLES, 'mobile' => true, 'mobilePriority' => 50],
                ['label' => self::label('messages.nav.campus_ai'), 'icon' => 'ai', 'route' => 'ai.index', 'context' => 'ai', 'roles' => self::ALL_ROLES],
            ]],
            ['label' => self::label('messages.nav.account'), 'icon' => 'settings', 'categories' => ['system'], 'roles' => self::ALL_ROLES, 'children' => [
                ['label' => self::label('messages.common.notifications'), 'icon' => 'bell', 'route' => 'notifications.index', 'context' => 'notifications', 'roles' => self::ALL_ROLES],
                ['label' => self::label('messages.common.profile'), 'icon' => 'profile', 'route' => 'profile.show', 'params' => ['user' => $user->id], 'context' => 'profile', 'roles' => self::ALL_ROLES],
                ['label' => self::label('messages.common.settings'), 'icon' => 'settings', 'route' => 'settings.index', 'context' => 'settings', 'roles' => self::ALL_ROLES],
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
