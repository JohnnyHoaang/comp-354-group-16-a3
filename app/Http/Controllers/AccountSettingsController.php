<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

                    if ($email === Str::lower($user->email)) {
                        $fail('The new email address is the same as the current one.');

                        return;
                    }

                    $emailIsInUse = User::query()
                        ->whereRaw('LOWER(email) = ?', [$email])
                        ->whereKeyNot($user->getKey())
                        ->exists();

                    if ($emailIsInUse) {
                        $fail('This email address is already in use.');
                    }
                },
            ],
            'phone' => [
                'bail',
                'required',
                'string',
                'regex:/^(?:\+?1[\s.-]?)?(?:\([2-9][0-9]{2}\)|[2-9][0-9]{2})[\s.-]?[2-9][0-9]{2}[\s.-]?[0-9]{4}$/',
                function (string $attribute, mixed $value, \Closure $fail) use ($user): void {
                    if ($user->phone !== null && $this->normalizePhoneNumber($user->phone) === $this->normalizePhoneNumber($value)) {
                        $fail('The new phone number is the same as the current one.');
                    }
                },
            ],
        ], [
            'phone.regex' => 'Enter a valid US or Canadian phone number.',
        ]);

        $user->update([
            'email' => Str::lower(trim($validated['email'])),
            'phone' => $this->normalizePhoneNumber($validated['phone']),
        ]);

        return redirect()->route('account.settings.edit')->with('status', 'Your contact information was updated.');
    }

    private function normalizePhoneNumber(string $phone): string
    {
        $digits = str_replace(['+', ' ', '-', '.', '(', ')'], '', $phone);
        $nationalNumber = strlen($digits) === 11 ? substr($digits, 1) : $digits;

        return '+1'.$nationalNumber;
    }
}
