<x-guest-layout>
    <div style="margin-bottom: 24px;">
        <h1 style="font-size: 24px; font-weight: 700; color: #f5f5f5; margin: 0 0 6px 0;">
            Reset password
        </h1>
        <p style="font-size: 13px; color: #8a8a8a; margin: 0;">
            Choose a new password.
        </p>
    </div>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div style="margin-bottom: 20px;">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required>
            @error('email')
                <p style="margin-top: 6px; font-size: 12px; color: #f87171;">{{ $message }}</p>
            @enderror
        </div>

        <div style="margin-bottom: 20px;">
            <label for="password">New Password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">
            @error('password')
                <p style="margin-top: 6px; font-size: 12px; color: #f87171;">{{ $message }}</p>
            @enderror
        </div>

        <div style="margin-bottom: 24px;">
            <label for="password_confirmation">Confirm New Password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>

        <button type="submit">Reset Password</button>
    </form>
</x-guest-layout>