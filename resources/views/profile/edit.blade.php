<x-app-layout>
    @php
        $user = Auth::user();
        $initials = strtoupper(substr($user->name, 0, 1));
        if (str_contains($user->name, ' ')) {
            $parts = explode(' ', $user->name);
            $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
        }

        $uploadsCount = $user->uploads()->count();
        $scoredCount = $user->uploads()->whereHas('readinessReport')->count();
        $avgScore = 0;
        if ($scoredCount > 0) {
            $scored = $user->uploads()->whereHas('readinessReport')->with('readinessReport')->get();
            $avgScore = round($scored->avg(fn($u) => $u->readinessReport->score));
        }
        $rowsProcessed = $user->uploads()->sum('row_count');
        $matchesCount = $user->uploads()->with('matches')->get()->sum(fn($u) => $u->matches->count());

        // Recent activity
        $recentUploads = $user->uploads()->latest()->take(4)->get();
    @endphp

    {{-- Header --}}
    <div style="margin-bottom: 24px;">
        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.15em; color: #10b981; font-weight: 700; margin-bottom: 8px;">
            Account
        </div>
        <h1 style="font-size: 34px; font-weight: 800; color: #f5f5f5; margin: 0 0 6px 0; letter-spacing: -0.02em;">
            Profile & Settings
        </h1>
        <p style="color: #8a8a8a; font-size: 14px; margin: 0;">
            Manage your account, security, and workspace preferences
        </p>
    </div>

    {{-- Layout: Sidebar + Content --}}
    <div style="display: grid; grid-template-columns: 260px 1fr; gap: 20px;" class="profile-layout">

        {{-- Sidebar --}}
        <div style="display: flex; flex-direction: column; gap: 16px;">

            {{-- Avatar card --}}
            <div style="background: linear-gradient(135deg, #131313 0%, #1a1a1a 100%); border: 1px solid #222; border-radius: 20px; padding: 24px; text-align: center; position: relative; overflow: hidden;">
                <div style="position: absolute; top: -60px; right: -60px; width: 200px; height: 200px; border-radius: 50%; background-color: #10b981; opacity: 0.08; filter: blur(60px); pointer-events: none;"></div>

                <div style="position: relative;">
                    <div style="width: 88px; height: 88px; margin: 0 auto 16px; border-radius: 24px; background: linear-gradient(135deg, #10b981 0%, #34d399 100%); display: flex; align-items: center; justify-content: center; font-size: 34px; font-weight: 800; color: #000; box-shadow: 0 0 40px -8px rgba(16, 185, 129, 0.6);">
                        {{ $initials }}
                    </div>
                    <div style="font-size: 18px; font-weight: 800; color: #f5f5f5; margin-bottom: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        {{ $user->name }}
                    </div>
                    <div style="font-size: 12px; color: #8a8a8a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-bottom: 12px;">
                        {{ $user->email }}
                    </div>
                    <div style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 999px; font-size: 10px; font-weight: 700; color: #10b981; text-transform: uppercase; letter-spacing: 0.05em;">
                        <span style="width: 5px; height: 5px; border-radius: 50%; background-color: #10b981;"></span>
                        Active Account
                    </div>
                </div>
            </div>

            {{-- Sidebar nav --}}
            <div style="background-color: #131313; border: 1px solid #222; border-radius: 16px; padding: 8px; position: sticky; top: 90px;">
                <div class="nav-item nav-active" data-section="personal">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span>Personal</span>
                </div>
                <div class="nav-item" data-section="security">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <span>Security</span>
                </div>
                <div class="nav-item" data-section="activity">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span>Activity</span>
                </div>
                <div class="nav-item" data-section="preferences">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <span>Preferences</span>
                </div>
                <div class="nav-item" data-section="danger" style="color: #ef4444;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <span>Danger Zone</span>
                </div>
            </div>
        </div>

        {{-- Main content --}}
        <div style="display: flex; flex-direction: column; gap: 20px;">

            {{-- Stats row --}}
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;" class="profile-stats">
                <div class="p-stat">
                    <div class="p-stat-label">Uploads</div>
                    <div class="p-stat-value" data-count-to="{{ $uploadsCount }}">0</div>
                    <div class="p-stat-icon" style="color: #60a5fa;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                </div>
                <div class="p-stat">
                    <div class="p-stat-label">Scored</div>
                    <div class="p-stat-value" style="color: #10b981;" data-count-to="{{ $scoredCount }}">0</div>
                    <div class="p-stat-icon" style="color: #10b981;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    </div>
                </div>
                <div class="p-stat">
                    <div class="p-stat-label">Avg Score</div>
                    <div class="p-stat-value" style="color: #c084fc;">{{ $avgScore }}%</div>
                    <div class="p-stat-icon" style="color: #c084fc;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    </div>
                </div>
                <div class="p-stat">
                    <div class="p-stat-label">Rows</div>
                    <div class="p-stat-value" style="color: #fbbf24;">{{ number_format($rowsProcessed) }}</div>
                    <div class="p-stat-icon" style="color: #fbbf24;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    </div>
                </div>
            </div>

            {{-- Section: Personal --}}
            <div class="section-block" id="section-personal">
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon" style="background-color: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.3);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <div style="flex: 1;">
                            <div class="card-title">Personal Information</div>
                            <div class="card-subtitle">Update your name and email address</div>
                        </div>
                    </div>
                    <div class="card-body">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>
            </div>

            {{-- Section: Security --}}
            <div class="section-block" id="section-security">
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon" style="background-color: rgba(245, 158, 11, 0.1); border-color: rgba(245, 158, 11, 0.3);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </div>
                        <div style="flex: 1;">
                            <div class="card-title">Password & Security</div>
                            <div class="card-subtitle">Change your password to keep your account secure</div>
                        </div>
                    </div>
                    <div class="card-body">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>
            </div>

            {{-- Section: Activity --}}
            <div class="section-block" id="section-activity">
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon" style="background-color: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.3);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <div style="flex: 1;">
                            <div class="card-title">Recent Activity</div>
                            <div class="card-subtitle">Your latest uploads</div>
                        </div>
                        <a href="/activity" class="card-link">View all →</a>
                    </div>
                    <div class="card-body" style="padding: 12px;">
                        @forelse($recentUploads as $upload)
                            <a href="/uploads/{{ $upload->id }}" class="activity-item">
                                <div style="width: 36px; height: 36px; border-radius: 10px; background-color: #262626; border: 1px solid #2a2a2a; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-size: 13px; font-weight: 600; color: #f5f5f5; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $upload->original_filename }}</div>
                                    <div style="font-size: 11px; color: #5a5a5a; margin-top: 2px;">{{ $upload->created_at->diffForHumans() }} · {{ $upload->row_count }} rows</div>
                                </div>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#5a5a5a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            </a>
                        @empty
                            <div style="padding: 32px; text-align: center; font-size: 13px; color: #5a5a5a;">
                                No uploads yet
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Section: Preferences --}}
            <div class="section-block" id="section-preferences">
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon" style="background-color: rgba(168, 85, 247, 0.1); border-color: rgba(168, 85, 247, 0.3);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c084fc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        </div>
                        <div style="flex: 1;">
                            <div class="card-title">Preferences</div>
                            <div class="card-subtitle">Customize your workspace experience</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <div class="pref-row">
                                <div style="flex: 1;">
                                    <div style="font-size: 13px; font-weight: 600; color: #f5f5f5;">Email Notifications</div>
                                    <div style="font-size: 12px; color: #8a8a8a; margin-top: 2px;">Receive updates about your uploads and reports</div>
                                </div>
                                <label class="toggle">
                                    <input type="checkbox" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div class="pref-row">
                                <div style="flex: 1;">
                                    <div style="font-size: 13px; font-weight: 600; color: #f5f5f5;">Auto-clean on Upload</div>
                                    <div style="font-size: 12px; color: #8a8a8a; margin-top: 2px;">Run the cleaner immediately after parsing</div>
                                </div>
                                <label class="toggle">
                                    <input type="checkbox">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div class="pref-row">
                                <div style="flex: 1;">
                                    <div style="font-size: 13px; font-weight: 600; color: #f5f5f5;">Compact Tables</div>
                                    <div style="font-size: 12px; color: #8a8a8a; margin-top: 2px;">Show more rows with tighter spacing</div>
                                </div>
                                <label class="toggle">
                                    <input type="checkbox">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div class="pref-row">
                                <div style="flex: 1;">
                                    <div style="font-size: 13px; font-weight: 600; color: #f5f5f5;">Dark Mode</div>
                                    <div style="font-size: 12px; color: #8a8a8a; margin-top: 2px;">Currently enabled (default)</div>
                                </div>
                                <label class="toggle">
                                    <input type="checkbox" checked disabled>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section: Danger Zone --}}
            <div class="section-block" id="section-danger">
                <div class="card" style="border-color: rgba(239, 68, 68, 0.3);">
                    <div class="card-header" style="border-color: rgba(239, 68, 68, 0.2);">
                        <div class="card-icon" style="background-color: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.3);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        </div>
                        <div style="flex: 1;">
                            <div class="card-title" style="color: #ef4444;">Danger Zone</div>
                            <div class="card-subtitle">Permanent actions that cannot be undone</div>
                        </div>
                    </div>
                    <div class="card-body">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .card {
            background-color: #131313;
            border: 1px solid #222;
            border-radius: 20px;
            overflow: hidden;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 20px 24px;
            border-bottom: 1px solid #222;
            flex-wrap: wrap;
        }

        .card-title {
            font-size: 15px;
            font-weight: 700;
            color: #f5f5f5;
            margin-bottom: 3px;
        }

        .card-subtitle {
            font-size: 12px;
            color: #5a5a5a;
        }

        .card-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid;
            flex-shrink: 0;
        }

        .card-body {
            padding: 24px;
        }

        .card-link {
            font-size: 12px;
            color: #10b981;
            text-decoration: none;
            font-weight: 700;
            margin-left: auto;
        }
        .card-link:hover { color: #34d399; }

        .p-stat {
            background-color: #131313;
            border: 1px solid #222;
            border-radius: 14px;
            padding: 18px;
            position: relative;
            transition: all 0.2s;
        }
        .p-stat:hover {
            border-color: rgba(16, 185, 129, 0.3);
            transform: translateY(-2px);
        }
        .p-stat-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: #8a8a8a;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .p-stat-value {
            font-size: 26px;
            font-weight: 800;
            color: #f5f5f5;
            line-height: 1;
        }
        .p-stat-icon {
            position: absolute;
            top: 18px;
            right: 18px;
            opacity: 0.4;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            color: #8a8a8a;
            cursor: pointer;
            transition: all 0.15s;
            user-select: none;
        }
        .nav-item:hover {
            color: #f5f5f5;
            background-color: rgba(38, 38, 38, 0.5);
        }
        .nav-item.nav-active {
            color: #10b981;
            background-color: rgba(16, 185, 129, 0.1);
        }
        .nav-item svg { flex-shrink: 0; }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 10px;
            text-decoration: none;
            transition: background-color 0.15s;
        }
        .activity-item:hover { background-color: rgba(38, 38, 38, 0.5); }

        .pref-row {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 16px;
            background-color: #0a0a0a;
            border: 1px solid #1a1a1a;
            border-radius: 12px;
            transition: all 0.2s;
        }
        .pref-row:hover { border-color: #2a2a2a; }

        .toggle {
            position: relative;
            display: inline-block;
            width: 40px;
            height: 22px;
            flex-shrink: 0;
        }
        .toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background-color: #2a2a2a;
            transition: all 0.2s;
            border-radius: 999px;
        }
        .toggle-slider::before {
            content: '';
            position: absolute;
            height: 16px;
            width: 16px;
            left: 3px;
            bottom: 3px;
            background-color: #f5f5f5;
            transition: all 0.2s;
            border-radius: 50%;
        }
        .toggle input:checked + .toggle-slider {
            background-color: #10b981;
        }
        .toggle input:checked + .toggle-slider::before {
            transform: translateX(18px);
        }
        .toggle input:disabled + .toggle-slider {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .section-block {
            scroll-margin-top: 100px;
            transition: all 0.3s;
        }

        @media (max-width: 1100px) {
            .profile-layout {
                grid-template-columns: 1fr !important;
            }
            .profile-layout > div:first-child {
                position: static;
            }
        }
        @media (max-width: 800px) {
            .profile-stats {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }
    </style>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Count-up
            document.querySelectorAll('[data-count-to]').forEach(el => {
                const target = parseInt(el.dataset.countTo);
                if (isNaN(target)) return;
                let current = 0;
                const start = performance.now();
                function tick(now) {
                    const p = Math.min((now - start) / 1200, 1);
                    const eased = 1 - Math.pow(1 - p, 3);
                    current = Math.round(eased * target);
                    el.textContent = current;
                    if (p < 1) requestAnimationFrame(tick);
                }
                requestAnimationFrame(tick);
            });

            // Sidebar nav — click to scroll
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => {
                item.addEventListener('click', () => {
                    const section = item.dataset.section;
                    const target = document.getElementById('section-' + section);
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                    navItems.forEach(n => n.classList.remove('nav-active'));
                    item.classList.add('nav-active');
                });
            });

            // Auto-highlight nav on scroll
            const sections = document.querySelectorAll('.section-block');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const id = entry.target.id.replace('section-', '');
                        navItems.forEach(n => {
                            n.classList.toggle('nav-active', n.dataset.section === id);
                        });
                    }
                });
            }, { threshold: 0.3, rootMargin: '-100px 0px -50% 0px' });

            sections.forEach(s => observer.observe(s));
        });
    </script>
    @endpush
</x-app-layout>