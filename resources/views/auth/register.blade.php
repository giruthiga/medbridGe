<x-guest-layout>
    <div style="margin-bottom: 24px;">
        <h1 style="font-size: 24px; font-weight: 700; color: #f5f5f5; margin: 0 0 6px 0;">
            Create your account
        </h1>
        <p style="font-size: 13px; color: #8a8a8a; margin: 0;">
            Start scanning hospital data in under a minute.
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        {{-- Name --}}
        <div style="margin-bottom: 20px;">
            <label for="name">Full Name</label>
            <input id="name"
                   type="text"
                   name="name"
                   value="{{ old('name') }}"
                   required
                   autofocus
                   autocomplete="name"
                   placeholder="e.g. Sara Ahmed">
            @error('name')
                <p style="margin-top: 6px; font-size: 12px; color: #f87171;">{{ $message }}</p>
            @enderror
        </div>

        {{-- Email --}}
        <div style="margin-bottom: 20px;">
            <label for="email">Email</label>
            <input id="email"
                   type="email"
                   name="email"
                   value="{{ old('email') }}"
                   required
                   autocomplete="username"
                   placeholder="you@hospital.bh">
            @error('email')
                <p style="margin-top: 6px; font-size: 12px; color: #f87171;">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div style="margin-bottom: 20px;">
            <label for="password">Password</label>
            <input id="password"
                   type="password"
                   name="password"
                   required
                   autocomplete="new-password"
                   placeholder="Min. 8 characters">
            @error('password')
                <p style="margin-top: 6px; font-size: 12px; color: #f87171;">{{ $message }}</p>
            @enderror
        </div>

        {{-- Confirm --}}
        <div style="margin-bottom: 24px;">
            <label for="password_confirmation">Confirm Password</label>
            <input id="password_confirmation"
                   type="password"
                   name="password_confirmation"
                   required
                   autocomplete="new-password"
                   placeholder="Repeat password">
        </div>

        {{-- Submit --}}
        <button type="submit">Create Account</button>

        {{-- Login link --}}
        <p style="text-align: center; font-size: 13px; color: #8a8a8a; margin-top: 20px;">
            Already have an account?
            <a href="{{ route('login') }}" style="color: #10b981; font-weight: 600; text-decoration: none;">
                Sign in
            </a>
        </p>
    </form>
</x-guest-layout>