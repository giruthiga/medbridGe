<x-guest-layout>
    <div style="margin-bottom: 24px;">
        <h1 style="font-size: 24px; font-weight: 700; color: #f5f5f5; margin: 0 0 6px 0;">
            Welcome back
        </h1>
        <p style="font-size: 13px; color: #8a8a8a; margin: 0;">
            Sign in to continue to your workspace.
        </p>
    </div>

    @if (session('status'))
        <div style="margin-bottom: 16px; padding: 12px; background-color: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; border-radius: 12px; font-size: 13px; color: #34d399;">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- Email --}}
        <div style="margin-bottom: 20px;">
            <label for="email">Email</label>
            <input id="email"
                   type="email"
                   name="email"
                   value="{{ old('email') }}"
                   required
                   autofocus
                   autocomplete="username"
                   placeholder="you@hospital.bh">
            @error('email')
                <p style="margin-top: 6px; font-size: 12px; color: #f87171;">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div style="margin-bottom: 16px;">
            <label for="password">Password</label>
            <input id="password"
                   type="password"
                   name="password"
                   required
                   autocomplete="current-password"
                   placeholder="••••••••">
            @error('password')
                <p style="margin-top: 6px; font-size: 12px; color: #f87171;">{{ $message }}</p>
            @enderror
        </div>

        {{-- Remember + Forgot --}}
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; gap: 12px;">
            <label for="remember_me" style="display: flex; align-items: center; gap: 10px; margin: 0; cursor: pointer; text-transform: none; letter-spacing: 0; font-size: 13px; color: #8a8a8a; font-weight: 500;">
                <input id="remember_me" type="checkbox" name="remember">
                <span>Remember me</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}"
                   style="font-size: 13px; color: #10b981; text-decoration: none; font-weight: 500; white-space: nowrap;"
                   onmouseover="this.style.color='#34d399'"
                   onmouseout="this.style.color='#10b981'">
                    Forgot password?
                </a>
            @endif
        </div>

        {{-- Submit --}}
        <button type="submit">Sign In</button>

        {{-- Register --}}
        <p style="text-align: center; font-size: 13px; color: #8a8a8a; margin-top: 20px;">
            Don't have an account?
            <a href="{{ route('register') }}" style="color: #10b981; font-weight: 600; text-decoration: none;">
                Create one
            </a>
        </p>
    </form>
</x-guest-layout>