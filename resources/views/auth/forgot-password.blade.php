<x-guest-layout>
    <div style="margin-bottom: 24px;">
        <h1 style="font-size: 24px; font-weight: 700; color: #f5f5f5; margin: 0 0 6px 0;">
            Forgot password?
        </h1>
        <p style="font-size: 13px; color: #8a8a8a; margin: 0;">
            Enter your email and we'll send a reset link.
        </p>
    </div>

    @if (session('status'))
        <div style="margin-bottom: 16px; padding: 12px; background-color: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; border-radius: 12px; font-size: 13px; color: #34d399;">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div style="margin-bottom: 24px;">
            <label for="email">Email</label>
            <input id="email"
                   type="email"
                   name="email"
                   value="{{ old('email') }}"
                   required
                   autofocus
                   placeholder="you@hospital.bh">
            @error('email')
                <p style="margin-top: 6px; font-size: 12px; color: #f87171;">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit">Send Reset Link</button>

        <p style="text-align: center; font-size: 13px; color: #8a8a8a; margin-top: 20px;">
            <a href="{{ route('login') }}" style="color: #10b981; font-weight: 600; text-decoration: none;">
                ← Back to sign in
            </a>
        </p>
    </form>
</x-guest-layout>