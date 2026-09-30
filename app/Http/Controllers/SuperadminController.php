<?php

namespace App\Http\Controllers;

use App\Models\GlobalVariable;
use App\Models\Purok;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SuperadminController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->select([
                'id',
                'gmail',
                'tungkulin',
                'is_verified',
                'first_name',
                'last_name',
                'profile_picture',
                'dashboard_allow',
                'form_generator_allow',
                'Delistment_allow',
                'Inquire_Allow',
            ])
            ->orderBy('id')
            ->get();

        $puroks = Purok::query()
            ->with('secretaryUser:id,first_name,last_name,gmail')
            ->orderBy('purok_number')
            ->get();

        return view('superadmin-dashboard', [
            'users' => $users,
            'puroks' => $puroks,
            'globalVariables' => GlobalVariable::query()->first(),
        ]);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'tungkulin' => ['sometimes', 'nullable', 'string', 'max:100', Rule::in(['Secretary', 'Head Secretary'])],
            'is_verified' => ['sometimes', 'boolean'],
        ]);

        if ($validated === []) {
            throw ValidationException::withMessages([
                'user' => 'Choose a role or access value to update.',
            ]);
        }

        $user->fill($validated)->save();
        if (array_key_exists('is_verified', $validated) && ! $user->is_verified) {
            $user->forceFill([
                'remember_token' => null,
                'remember_token_expires_at' => null,
            ])->save();
        }

        return response()->json([
            'data' => [
                'id' => $user->id,
                'tungkulin' => $user->tungkulin,
                'is_verified' => (bool) $user->is_verified,
                'revoked_current_session' => (int) $request->session()->get('login_user.id') === (int) $user->id && ! $user->is_verified,
            ],
        ]);
    }

    public function updatePermissions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'permissions' => ['present', 'array'],
            'permissions.*.id' => ['required', 'integer', 'distinct'],
            'permissions.*.dashboard_allow' => ['required', 'boolean'],
            'permissions.*.form_generator_allow' => ['required', 'boolean'],
            'permissions.*.Delistment_allow' => ['required', 'boolean'],
            'permissions.*.Inquire_Allow' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($validated): void {
            foreach ($validated['permissions'] as $permission) {
                $user = User::query()
                    ->whereKey($permission['id'])
                    ->where('is_verified', 1)
                    ->first();

                if (! $user) {
                    throw ValidationException::withMessages([
                        'permissions' => 'A selected user is no longer verified. Reload the dashboard and try again.',
                    ]);
                }

                $user->fill([
                    'dashboard_allow' => $permission['dashboard_allow'],
                    'form_generator_allow' => $permission['form_generator_allow'],
                    'Delistment_allow' => $permission['Delistment_allow'],
                    'Inquire_Allow' => $permission['Inquire_Allow'],
                ])->save();
            }
        });

        return response()->json(['message' => 'Permissions saved.']);
    }

    public function updateSignatories(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'puroks' => ['present', 'array'],
            'puroks.*.id' => ['nullable', 'integer', 'exists:puroks,id'],
            'puroks.*.purok_number' => ['required', 'integer', 'min:1'],
            'puroks.*.purok_name' => ['required', 'string', 'max:40'],
            'puroks.*.secretary' => ['nullable', 'integer', 'exists:login_user,id'],
            'puroks.*.manggagawa' => ['nullable', 'string', 'max:255'],
            'puroks.*.ministerial_id' => ['nullable', 'string', 'max:50'],
            'puroks.*.grupo_start' => ['required', 'integer', 'min:0'],
            'puroks.*.grupo_end' => ['required', 'integer', 'min:0'],
            'puroks.*.full_purok' => ['required', 'boolean'],
        ]);

        $numbers = array_map(
            static fn (array $purok): int => (int) $purok['purok_number'],
            $validated['puroks']
        );

        if (count($numbers) !== count(array_unique($numbers))) {
            throw ValidationException::withMessages([
                'puroks' => 'Each Purok must have a unique assigned number.',
            ]);
        }

        $submittedIds = array_filter(array_column($validated['puroks'], 'id'));
        foreach ($validated['puroks'] as $purok) {
            $existing = DB::table('puroks')
                ->where('purok_number', $purok['purok_number'])
                ->first();
            if ($existing && (int) $existing->id !== (int) ($purok['id'] ?? 0)
                && ! in_array($existing->id, $submittedIds)) {
                throw ValidationException::withMessages([
                    'puroks' => 'The assigned Purok number is already in use.',
                ]);
            }
        }

        foreach ($validated['puroks'] as $purok) {
            if ($purok['grupo_end'] < $purok['grupo_start']) {
                throw ValidationException::withMessages([
                    'puroks' => 'The end of the Sakop na Grupo range must not be less than its start.',
                ]);
            }
        }

        DB::transaction(function () use ($validated): void {
            $savedIds = [];

            foreach ($validated['puroks'] as $attributes) {
                $id = $attributes['id'] ?? null;
                unset($attributes['id']);

                if ($id) {
                    $purok = Purok::query()->findOrFail($id);
                    $purok->fill($attributes)->save();
                } else {
                    $purok = Purok::query()->create($attributes);
                }

                $savedIds[] = $purok->id;
            }

            $query = Purok::query();
            if ($savedIds !== []) {
                $query->whereNotIn('id', $savedIds);
            }
            $query->delete();
        });

        return response()->json(['message' => 'Signatories saved.']);
    }

    public function updateGlobalVariables(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'locale' => ['nullable', 'string', 'max:255'],
            'lcode' => ['nullable', 'string', 'max:50'],
            'district' => ['nullable', 'string', 'max:255'],
            'dcode' => ['nullable', 'string', 'max:50'],
            's_number' => ['nullable', 'string', 'max:50'],
            'pastor_destinado' => ['nullable', 'string', 'max:255'],
            'ksp_pd' => ['nullable', 'string', 'max:255'],
            'kalihim_ms' => ['nullable', 'string', 'max:255'],
            'kalihim_uwp' => ['nullable', 'string', 'max:255'],
            'baptism_requirement' => ['nullable', 'date'],
            'entry_years_requirement' => ['nullable', 'integer', 'min:1', 'max:100'],
            'dateofbaptism_requirement_boolean' => ['required', 'boolean'],
            'dateofentryrequirement_boolean' => ['required', 'boolean'],
        ]);

        $globalVariables = GlobalVariable::query()->first() ?? new GlobalVariable;
        $globalVariables->fill($validated)->save();

        return response()->json([
            'message' => 'Global variables saved.',
            'data' => $globalVariables,
        ]);
    }

    public function updateSecuritySettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'require_otp' => ['required', 'boolean'],
            'allow_forgot_password' => ['required', 'boolean'],
        ]);
        $globalVariables = GlobalVariable::query()->first() ?? new GlobalVariable;
        $globalVariables->fill($validated)->save();

        return response()->json([
            'message' => 'Security settings saved.',
            'data' => [
                'require_otp' => (bool) $globalVariables->require_otp,
                'allow_forgot_password' => (bool) $globalVariables->allow_forgot_password,
            ],
        ]);
    }
}
