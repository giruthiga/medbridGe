<x-guest-layout>
    <div style="margin-bottom: 24px;">
        <h1 style="font-size: 24px; font-weight: 700; color: #f5f5f5; margin: 0 0 6px 0;">
            Confirm password
        </h1>
        <p style="font-size: 13px; color: #8a8a8a; margin: 0;">
            This is a secure area. Please confirm your password.
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div style="margin-bottom: 24px;">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password">
            @error('password')
                <p style="margin-top: 6px; font-size: 12px; color: #f87171;">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit">Confirm</button>
    </form>
</x-guest-layout>