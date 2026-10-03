<section>
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" style="margin-left: 48px;">
        @csrf
        @method('patch')

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;" class="form-grid">
            {{-- Name --}}
            <div>
                <label for="name" style="display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: #8a8a8a; margin-bottom: 8px;">
                    Full Name
                </label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name"
                       style="width: 100%; background-color: #0a0a0a; border: 1px solid #2a2a2a; color: #f5f5f5; border-radius: 12px; padding: 12px 16px; font-size: 14px; outline: none; transition: all 0.2s;">
                @error('name')
                    <p style="margin-top: 6px; font-size: 12px; color: #f87171;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" style="display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: #8a8a8a; margin-bottom: 8px;">
                    Email Address
                </label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                       style="width: 100%; background-color: #0a0a0a; border: 1px solid #2a2a2a; color: #f5f5f5; border-radius: 12px; padding: 12px 16px; font-size: 14px; outline: none; transition: all 0.2s;">
                @error('email')
                    <p style="margin-top: 6px; font-size: 12px; color: #f87171;">{{ $message }}</p>
                @enderror

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div style="margin-top: 12px; padding: 12px; background-color: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 10px; font-size: 12px; color: #f59e0b;">
                        Your email address is unverified.
                        <button form="send-verification" style="background: none; border: none; color: #10b981; text-decoration: underline; cursor: pointer; font-size: 12px; padding: 0; font-weight: 600;">
                            Click here to re-send the verification email.
                        </button>
                    </div>

                    @if (session('status') === 'verification-link-sent')
                        <p style="margin-top: 8px; font-size: 12px; color: #10b981;">
                            A new verification link has been sent to your email address.
                        </p>
                    @endif
                @endif
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
            <button type="submit"
                    style="display: inline-flex; align-items: center; gap: 8px; background-color: #10b981; color: #000; padding: 12px 24px; border-radius: 999px; font-weight: 700; font-size: 14px; border: none; cursor: pointer; box-shadow: 0 0 24px -8px rgba(16, 185, 129, 0.5); transition: all 0.2s;"
                    onmouseover="this.style.backgroundColor='#34d399';"
                    onmouseout="this.style.backgroundColor='#10b981';">
                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Save Changes
            </button>

            @if (session('status') === 'profile-updated')
                <span style="font-size: 13px; color: #10b981; font-weight: 600;">
                    ✓ Saved successfully
                </span>
            @endif
        </div>
    </form>

    <style>
        @media (max-width: 700px) {
            .form-grid { grid-template-columns: 1fr !important; margin-left: 0 !important; }
            form[method="post"] { margin-left: 0 !important; }
        }
        input:focus {
            border-color: #10b981 !important;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15) !important;
        }
    </style>
</section>