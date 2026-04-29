<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\EmailTemplate;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response|RedirectResponse
    {
        // Check if registration is enabled
        $enableRegistration = admin_setting('enableRegistration');

        if ($enableRegistration === 'off') {
            return redirect()->route('login');
        }

        return Inertia::render('auth/register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Check if registration is enabled
        $enableRegistration = admin_setting('enableRegistration');

        if ($enableRegistration === 'off') {
            return redirect()->route('login');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        try {
            $enableEmailVerification = admin_setting('enableEmailVerification');

            $adminUser = User::where('type', 'superadmin')->first();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => $enableEmailVerification === 'on' ? null : now(),
                'type' => 'company',
                'lang' => admin_setting('defaultLanguage') ?? 'en',
                'created_by' => $adminUser ? $adminUser->id : null,
            ]);

            Auth::login($user);

            try {
                User::CompanySetting($user->id);
                User::MakeRole($user->id);

                $companyRole = Role::firstOrCreate(
                    [
                        'name' => $user->type,
                        'guard_name' => 'web',
                        'created_by' => $user->id,
                    ],
                    [
                        'label' => ucfirst($user->type),
                        'editable' => false,
                    ]
                );

                if (!$user->hasRole($companyRole->name)) {
                    $user->assignRole($companyRole);
                }
            } catch (\Throwable $roleSetupError) {
                report($roleSetupError);
            }

             // Send welcome email
            if (admin_setting('New User') == 'on' && $adminUser) {
                $emailData = [
                    'name' => $user->name,
                    'email' => $user->email,
                    'password' => $request->password,
                ];

                EmailTemplate::sendEmailTemplate('New User', [$user->email], $emailData, $adminUser->id);
            }

            if ($enableEmailVerification === 'on') {
                // Apply dynamic mail configuration
                if ($adminUser) {
                    SetConfigEmail($adminUser->id);
                }
                $user->sendEmailVerificationNotification();
                return redirect(route('verification.notice'))->with('status', 'verification-link-sent');
            }

            return redirect(route('dashboard', absolute: false));

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Registration failed. Please try again.']);
        }
    }
}
