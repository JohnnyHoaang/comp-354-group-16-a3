@extends('layout')

@section('title', 'Account settings')

@section('content')
    @if (session('status'))
        <p role="status">{{ session('status') }}</p>
    @endif

    <p>Update your contact information below.</p>

    <form
        method="POST"
        action="{{ route('account.settings.update') }}"
        data-current-email="{{ $user->email }}"
        data-current-phone="{{ $user->phone ?? '' }}"
        data-email-availability-url="{{ route('account.settings.email-availability') }}"
    >
        @csrf
        @method('PUT')

        <wa-input
            type="email"
            name="email"
            label="Email"
            value="{{ old('email', $user->email) }}"
            autocomplete="email"
            required
        ></wa-input>
        @error('email')
            <p class="error" role="alert">{{ $message }}</p>
        @enderror

        <wa-input
            type="tel"
            name="phone"
            label="Phone number"
            value="{{ old('phone', $user->phone) }}"
            autocomplete="tel"
            aria-describedby="phone-help"
            required
        ></wa-input>
        <p id="phone-help" class="help-text" hidden>Use a US or Canadian 10-digit number. The area code and exchange must each start with 2-9. You may include +1 and formatting, for example (514) 223-2345.</p>
        @error('phone')
            <p class="error" role="alert">{{ $message }}</p>
        @enderror

        <p id="email-availability" class="help-text" role="status" hidden></p>
        <wa-button type="submit" variant="brand" disabled>Save contact information</wa-button>
    </form>

    <p><a href="{{ route('home') }}">Back to home</a></p>

    <script>
        Promise.all([
            customElements.whenDefined('wa-input'),
            customElements.whenDefined('wa-button'),
        ]).then(() => {
            const form = document.querySelector('form[data-email-availability-url]');
            const emailInput = form.querySelector('wa-input[name="email"]');
            const phoneInput = form.querySelector('wa-input[name="phone"]');
            const phoneHelp = document.getElementById('phone-help');
            const emailAvailability = document.getElementById('email-availability');
            const saveButton = form.querySelector('wa-button[type="submit"]');
            const northAmericanPhonePattern = /^(?:\+?1[\s.-]?)?(?:\([2-9][0-9]{2}\)|[2-9][0-9]{2})[\s.-]?[2-9][0-9]{2}[\s.-]?[0-9]{4}$/;
            const originalEmail = form.dataset.currentEmail.trim().toLowerCase();
            const originalPhone = form.dataset.currentPhone;
            let emailCheckTimer;
            let latestEmailCheck = 0;
            let checkedEmail = null;
            let emailIsAvailable = false;

            const normalizedPhone = phone => {
                const digits = phone.replace(/\D/g, '');
                const nationalNumber = digits.length === 11 ? digits.slice(1) : digits;

                return `+1${nationalNumber}`;
            };

            const showEmailAvailability = (message, isError = false) => {
                emailAvailability.textContent = message;
                emailAvailability.hidden = message.length === 0;
                emailAvailability.classList.toggle('error', isError);
            };

            const checkEmailAvailability = async email => {
                const checkId = ++latestEmailCheck;
                checkedEmail = email;
                emailIsAvailable = false;
                showEmailAvailability('Checking email availability...');

                try {
                    const response = await fetch(form.dataset.emailAvailabilityUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                        },
                        body: JSON.stringify({ email }),
                    });

                    if (!response.ok) {
                        throw new Error('Email availability check failed.');
                    }

                    const result = await response.json();

                    if (checkId !== latestEmailCheck) {
                        return;
                    }

                    emailIsAvailable = result.available;
                    showEmailAvailability(
                        emailIsAvailable ? '' : 'This email address is already in use.',
                        !emailIsAvailable
                    );
                    updateFormState();
                } catch {
                    if (checkId !== latestEmailCheck) {
                        return;
                    }

                    showEmailAvailability('Unable to check email availability. Edit the email address and try again.', true);
                    updateFormState();
                }
            };

            const updateFormState = () => {
                const email = emailInput.value.trim().toLowerCase();
                const phone = phoneInput.value.trim();
                const phoneIsValid = northAmericanPhonePattern.test(phone);
                const emailIsValid = emailInput.validity.valid;
                const emailIsUnchanged = email === originalEmail;
                const phoneIsUnchanged = originalPhone !== ''
                    && phoneIsValid
                    && normalizedPhone(phone) === originalPhone;
                const hasChanges = !emailIsUnchanged || !phoneIsUnchanged;

                phoneHelp.hidden = phone.length === 0 || phoneIsValid;

                if (!emailIsValid || !hasChanges) {
                    clearTimeout(emailCheckTimer);
                    latestEmailCheck++;
                    checkedEmail = null;
                    emailIsAvailable = false;
                    saveButton.disabled = true;

                    if (!emailIsValid) {
                        showEmailAvailability(
                            email.length === 0 ? '' : 'Enter a valid email address.',
                            email.length > 0
                        );
                    } else if (!hasChanges) {
                        showEmailAvailability('Change at least one contact detail to enable saving.');
                    } else if (emailIsUnchanged) {
                        showEmailAvailability('');
                    }

                    return;
                }

                if (emailIsUnchanged) {
                    saveButton.disabled = !phoneIsValid;
                    return;
                }

                if (checkedEmail === email) {
                    saveButton.disabled = !phoneIsValid || !emailIsAvailable;
                    return;
                }

                clearTimeout(emailCheckTimer);
                saveButton.disabled = true;
                emailCheckTimer = setTimeout(() => checkEmailAvailability(email), 300);
            };

            emailInput.addEventListener('input', updateFormState);
            phoneInput.addEventListener('input', updateFormState);
            updateFormState();
        });
    </script>
@endsection
