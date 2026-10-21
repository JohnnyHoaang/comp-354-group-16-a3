<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.settings', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'email' => [
                'bail',
                'required',
                'string',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($user): void {
                    $email = Str::lower(trim($value));

                    if ($email !== Str::lower($user->email)) {
                        $emailIsInUse = User::query()
                            ->whereRaw('LOWER(email) = ?', [$email])
                            ->whereKeyNot($user->getKey())
                            ->exists();

                        if ($emailIsInUse) {
                            $fail('This email address is already in use.');
                        }
                    }
                },
            ],
            'phone' => [
                'bail',
                'required',
                'string',
                'regex:/^(?:\+?1[\s.-]?)?(?:\([2-9][0-9]{2}\)|[2-9][0-9]{2})[\s.-]?[2-9][0-9]{2}[\s.-]?[0-9]{4}$/',
            ],
        ], [
            'phone.regex' => 'Enter a valid US or Canadian phone number.',
        ]);

        $emailIsUnchanged = Str::lower(trim($validated['email'])) === Str::lower($user->email);
        $phoneIsUnchanged = $user->phone !== null
            && $this->normalizePhoneNumber($user->phone) === $this->normalizePhoneNumber($validated['phone']);

        if ($emailIsUnchanged && $phoneIsUnchanged) {
            throw ValidationException::withMessages([
                'email' => 'No changes detected. Update your email address or phone number.',
            ]);
        }

        $updatedContactInformation = [];

        if (! $emailIsUnchanged) {
            $updatedContactInformation['email'] = Str::lower(trim($validated['email']));
        }

        if (! $phoneIsUnchanged) {
            $updatedContactInformation['phone'] = $this->normalizePhoneNumber($validated['phone']);
        }

        $user->update($updatedContactInformation);

        return redirect()->route('account.settings.edit')->with('status', 'Your contact information was updated.');
    }

    private function normalizePhoneNumber(string $phone): string
    {
        $digits = str_replace(['+', ' ', '-', '.', '(', ')'], '', $phone);
        $nationalNumber = strlen($digits) === 11 ? substr($digits, 1) : $digits;

        return '+1'.$nationalNumber;
    }
}
