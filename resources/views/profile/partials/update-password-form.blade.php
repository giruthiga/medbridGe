<section>
    <form method="post" action="{{ route('password.update') }}" style="margin-left: 48px;">
        @csrf
        @method('put')

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;" class="password-grid">
            {{-- Current Password --}}
            <div>
                <label for="update_password_current_password" style="display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: #8a8a8a; margin-bottom: 8px;">
                    Current
                </label>
                <input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password"
                       placeholder="••••••••"
                       style="width: 100%; background-color: #0a0a0a; border: 1px solid #2a2a2a; color: #f5f5f5; border-radius: 12px; padding: 12px 16px; font-size: 14px; outline: none; transition: all 0.2s;">
                @if($errors->updatePassword->has('current_password'))
                    <p style="margin-top: 6px; font-size: 12px; color: #f87171;">{{ $errors->updatePassword->first('current_password') }}</p>
                @endif
            </div>

            {{-- New Password --}}
            <div>
                <label for="update_password_password" style="display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: #8a8a8a; margin-bottom: 8px;">
                    New Password
                </label>
                <input id="update_password_password" name="password" type="password" autocomplete="new-password"
                       placeholder="Min. 8 characters"
                       style="width: 100%; background-color: #0a0a0a; border: 1px solid #2a2a2a; color: #f5f5f5; border-radius: 12px; padding: 12px 16px; font-size: 14px; outline: none; transition: all 0.2s;">
                @if($errors->updatePassword->has('password'))
                    <p style="margin-top: 6px; font-size: 12px; color: #f87171;">{{ $errors->updatePassword->first('password') }}</p>
                @endif
            </div>

            {{-- Confirm Password --}}
            <div>
                <label for="update_password_password_confirmation" style="display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: #8a8a8a; margin-bottom: 8px;">
                    Confirm
                </label>
                <input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                       placeholder="Repeat new"
                       style="width: 100%; background-color: #0a0a0a; border: 1px solid #2a2a2a; color: #f5f5f5; border-radius: 12px; padding: 12px 16px; font-size: 14px; outline: none; transition: all 0.2s;">
                @if($errors->updatePassword->has('password_confirmation'))
                    <p style="margin-top: 6px; font-size: 12px; color: #f87171;">{{ $errors->updatePassword->first('password_confirmation') }}</p>
                @endif
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
            <button type="submit"
                    style="display: inline-flex; align-items: center; gap: 8px; background-color: #10b981; color: #000; padding: 12px 24px; border-radius: 999px; font-weight: 700; font-size: 14px; border: none; cursor: pointer; box-shadow: 0 0 24px -8px rgba(16, 185, 129, 0.5); transition: all 0.2s;"
                    onmouseover="this.style.backgroundColor='#34d399';"
                    onmouseout="this.style.backgroundColor='#10b981';">
                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
                Update Password
            </button>

            @if (session('status') === 'password-updated')
                <span style="font-size: 13px; color: #10b981; font-weight: 600;">
                    ✓ Password updated
                </span>
            @endif
        </div>
    </form>

    <style>
        @media (max-width: 700px) {
            .password-grid { grid-template-columns: 1fr !important; }
            form[method="post"] { margin-left: 0 !important; }
        }
        input:focus {
            border-color: #10b981 !important;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15) !important;
        }
    </style>
</section>