<?php

namespace App\Http\Controllers;

use App\Models\GlobalVariable;
use App\Models\Purok;
use App\Models\Record;
use App\Models\R402Hakbang;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
                'tarheta_status_allow',
                'filter_bnh_helper_allow',
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
            'r402Hakbangs' => R402Hakbang::query()
                ->orderBy('status')
                ->orderBy('code')
                ->get(),
        ]);
    }

    public function storeR402Hakbang(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['MS', 'UWP'])],
            'code' => [
                'required',
                'string',
                'max:30',
                'regex:/^\d+-\d+$/',
                Rule::unique('r4_02_hakbangs', 'code')->where('status', $request->input('status')),
            ],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $hakbang = R402Hakbang::query()->create([
            ...$validated,
            'code' => trim($validated['code']),
            'description' => trim($validated['description']),
        ]);

        return response()->json([
            'message' => "{$hakbang->status} hakbang {$hakbang->code} saved.",
            'data' => $hakbang,
        ], 201);
    }

    public function updateR402Hakbang(Request $request, R402Hakbang $hakbang): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['MS', 'UWP'])],
            'code' => [
                'required',
                'string',
                'max:30',
                'regex:/^\d+-\d+$/',
                Rule::unique('r4_02_hakbangs', 'code')
                    ->where('status', $request->input('status'))
                    ->ignore($hakbang->id),
            ],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $hakbang->fill([
            ...$validated,
            'code' => trim($validated['code']),
            'description' => trim($validated['description']),
        ])->save();

        return response()->json([
            'message' => "{$hakbang->status} hakbang {$hakbang->code} updated.",
            'data' => $hakbang->fresh(),
        ]);
    }

    public function destroyR402Hakbang(R402Hakbang $hakbang): JsonResponse
    {
        $hakbang->delete();

        return response()->json(['message' => 'R4-02 hakbang deleted.']);
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
            'permissions.*.tarheta_status_allow' => ['required', 'boolean'],
            'permissions.*.filter_bnh_helper_allow' => ['required', 'boolean'],
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
                    'tarheta_status_allow' => $permission['tarheta_status_allow'],
                    'filter_bnh_helper_allow' => $permission['filter_bnh_helper_allow'],
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

        foreach ($validated['puroks'] as $index => $purok) {
            if ($purok['grupo_end'] < $purok['grupo_start']) {
                throw ValidationException::withMessages([
                    "puroks.{$index}.grupo_end" => 'The end of the Sakop na Grupo range must not be less than its start.',
                ]);
            }
        }

        foreach ($validated['puroks'] as $index => $purok) {
            foreach ($validated['puroks'] as $otherIndex => $other) {
                if ($otherIndex <= $index
                    || (int) $purok['purok_number'] !== (int) $other['purok_number']) {
                    continue;
                }

                $completeIndex = $purok['full_purok']
                    ? $index
                    : ($other['full_purok'] ? $otherIndex : null);
                if ($completeIndex !== null) {
                    throw ValidationException::withMessages([
                        "puroks.{$completeIndex}.full_purok" => 'Complete Purok cannot be enabled when another configured Purok shares the same header number. Use non-overlapping Sakop na Grupo ranges instead.',
                    ]);
                }

                if ((int) $purok['grupo_start'] <= (int) $other['grupo_end']
                    && (int) $other['grupo_start'] <= (int) $purok['grupo_end']) {
                    throw ValidationException::withMessages([
                        "puroks.{$otherIndex}.grupo_start" => 'Sakop na Grupo ranges cannot overlap for Puroks sharing the same header number.',
                    ]);
                }
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
            'baptism_requirement' => ['required_if:dateofbaptism_requirement_boolean,1', 'nullable', 'date'],
            'entry_years_requirement' => ['required', 'integer', 'in:1,2,3'],
            'dateofbaptism_requirement_boolean' => ['required', 'boolean'],
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

    public function generateBnhResetCode(Request $request): JsonResponse
    {
        $previousCodeHash = $request->session()->get('bnh_reset_confirmation.hash');
        $request->session()->forget('bnh_reset_confirmation');
        $checkedCount = Record::query()
            ->where(fn ($query) => $query->where('is_valid1', true)->orWhere('is_valid2', true))
            ->count();
        if ($checkedCount === 0) {
            return response()->json([
                'empty' => true,
                'message' => 'BNH CHECKS is currently Empty.',
            ], 409);
        }

        do {
            $code = Str::lower(Str::random(12));
            $codeHash = hash('sha256', $code);
        } while ($previousCodeHash && hash_equals((string) $previousCodeHash, $codeHash));
        $request->session()->put('bnh_reset_confirmation', [
            'hash' => $codeHash,
            'expires_at' => now()->addSeconds(45)->timestamp,
        ]);

        return response()->json([
            'code' => $code,
            'expires_in_seconds' => 45,
            'message' => 'Type the generated code within 45 seconds to reset the BNH checks.',
        ]);
    }

    public function resetBnhHelperChecks(Request $request): JsonResponse
    {
        $request->validate([
            'confirmation' => ['required', 'string', 'size:12', 'regex:/^[a-z0-9]{12}$/'],
        ]);

        $affected = DB::transaction(function () use ($request): int {
            $confirmation = $request->session()->pull('bnh_reset_confirmation');
            if (
                ! is_array($confirmation)
                || (int) ($confirmation['expires_at'] ?? 0) <= now()->timestamp
                || ! hash_equals((string) ($confirmation['hash'] ?? ''), hash('sha256', $request->string('confirmation')->toString()))
            ) {
                throw ValidationException::withMessages([
                    'confirmation' => ['The generated code is invalid or expired. Generate a new code and try again.'],
                ]);
            }

            $count = Record::query()
                ->where(fn ($query) => $query->where('is_valid1', true)->orWhere('is_valid2', true))
                ->count();
            if ($count === 0) {
                return 0;
            }
            Record::query()->update(['is_valid1' => false, 'is_valid2' => false]);
            DB::table('system_maintenance_audits')->insert([
                'action' => 'reset_bnh_helper_checks',
                'affected_count' => $count,
                'changed_by' => $this->changedBy($request),
                'changed_at' => now(),
            ]);

            return $count;
        });

        if ($affected === 0) {
            return response()->json(['empty' => true, 'message' => 'BNH CHECKS is currently Empty.'], 409);
        }

        return response()->json(['message' => "✓ BNH helper checks cleared for {$affected} record".($affected === 1 ? '' : 's').'.', 'affected' => $affected]);
    }

    private function changedBy(Request $request): ?string
    {
        return trim(implode(' ', array_filter([
            (string) $request->session()->get('login_user.first_name', ''),
            (string) $request->session()->get('login_user.last_name', ''),
        ]))) ?: $request->session()->get('login_user.gmail');
    }
}
