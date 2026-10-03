<section x-data="{ showModal: false }">
    <div style="margin-left: 48px;">
        <button type="button" @click="showModal = true"
                style="display: inline-flex; align-items: center; gap: 8px; background-color: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.4); color: #ef4444; padding: 12px 24px; border-radius: 999px; font-weight: 700; font-size: 14px; cursor: pointer; transition: all 0.2s;"
                onmouseover="this.style.backgroundColor='rgba(239, 68, 68, 0.2)'; this.style.borderColor='#ef4444';"
                onmouseout="this.style.backgroundColor='rgba(239, 68, 68, 0.1)'; this.style.borderColor='rgba(239, 68, 68, 0.4)';">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Delete Account
        </button>
    </div>

    {{-- Modal --}}
    <div x-show="showModal"
         x-transition.opacity
         @keydown.escape.window="showModal = false"
         style="position: fixed; inset: 0; z-index: 100; display: flex; align-items: center; justify-content: center; padding: 16px;">
        <div @click="showModal = false" style="position: absolute; inset: 0; background-color: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px);"></div>

        <div x-show="showModal"
             x-transition
             style="position: relative; background-color: #131313; border: 1px solid #2a2a2a; border-radius: 20px; padding: 32px; max-width: 480px; width: 100%; box-shadow: 0 20px 60px -10px rgba(0, 0, 0, 0.8);">

            <div style="display: flex; align-items: flex-start; gap: 16px; margin-bottom: 24px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background-color: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg style="width: 22px; height: 22px; color: #ef4444;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <h3 style="font-size: 18px; font-weight: 700; color: #f5f5f5; margin: 0 0 8px 0;">
                        Delete your account?
                    </h3>
                    <p style="font-size: 13px; color: #8a8a8a; margin: 0; line-height: 1.6;">
                        This will permanently delete your account and all associated data — uploads, matches, and reports.
                        <span style="color: #ef4444; font-weight: 600;">This action cannot be undone.</span>
                    </p>
                </div>
            </div>

            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')

                <div style="margin-bottom: 24px;">
                    <label for="delete_password" style="display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: #8a8a8a; margin-bottom: 8px;">
                        Confirm your password
                    </label>
                    <input id="delete_password" name="password" type="password" placeholder="Enter your password"
                           style="width: 100%; background-color: #0a0a0a; border: 1px solid #2a2a2a; color: #f5f5f5; border-radius: 12px; padding: 12px 16px; font-size: 14px; outline: none; transition: all 0.2s;">
                    @if($errors->userDeletion->has('password'))
                        <p style="margin-top: 6px; font-size: 12px; color: #f87171;">{{ $errors->userDeletion->first('password') }}</p>
                    @endif
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" @click="showModal = false"
                            style="padding: 12px 24px; border-radius: 999px; border: 1px solid #2a2a2a; background: transparent; color: #8a8a8a; font-weight: 500; font-size: 14px; cursor: pointer; transition: all 0.2s;"
                            onmouseover="this.style.backgroundColor='#262626'; this.style.color='#f5f5f5';"
                            onmouseout="this.style.backgroundColor='transparent'; this.style.color='#8a8a8a';">
                        Cancel
                    </button>
                    <button type="submit"
                            style="padding: 12px 24px; border-radius: 999px; background-color: #ef4444; color: #fff; font-weight: 700; font-size: 14px; border: none; cursor: pointer; transition: all 0.2s;"
                            onmouseover="this.style.backgroundColor='#dc2626';"
                            onmouseout="this.style.backgroundColor='#ef4444';">
                        Delete Forever
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($errors->userDeletion->isNotEmpty())
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const modal = document.querySelector('[x-data]');
                if (modal && modal.__x) modal.__x.$data.showModal = true;
            });
        </script>
    @endif

    <style>
        input:focus {
            border-color: #10b981 !important;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15) !important;
        }
        @media (max-width: 700px) {
            section > div { margin-left: 0 !important; }
        }
    </style>
</section>