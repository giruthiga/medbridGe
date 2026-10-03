<x-guest-layout>
    <div style="margin-bottom: 24px;">
        <h1 style="font-size: 24px; font-weight: 700; color: #f5f5f5; margin: 0 0 6px 0;">
            Verify your email
        </h1>
        <p style="font-size: 13px; color: #8a8a8a; margin: 0;">
            Check your inbox and click the verification link.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div style="margin-bottom: 16px; padding: 12px; background-color: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; border-radius: 12px; font-size: 13px; color: #34d399;">
            A new verification link has been sent.
        </div>
    @endif

    <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px;">
        <form method="POST" action="{{ route('verification.send') }}" style="flex: 1;">
            @csrf
            <button type="submit" style="width: 100%;">Resend Email</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" style="background: transparent !important; color: #8a8a8a !important; padding: 12px 16px !important; font-weight: 500 !important; box-shadow: none !important;">
                Sign Out
            </button>
        </form>
    </div>
</x-guest-layout>