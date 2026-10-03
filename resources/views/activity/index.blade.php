<x-app-layout>
    @php
        // Extended stats
        $now = now();
        $todayCount = $activities->filter(fn($a) => $a['at']->isToday())->count();
        $yesterdayCount = $activities->filter(fn($a) => $a['at']->isYesterday())->count();
        $weekCount = $activities->filter(fn($a) => $a['at']->isCurrentWeek())->count();
        $monthCount = $activities->filter(fn($a) => $a['at']->isCurrentMonth())->count();

        // Type distribution
        $typeCounts = [];
        foreach ($activities as $a) {
            $typeCounts[$a['type']] = ($typeCounts[$a['type']] ?? 0) + 1;
        }
        $totalEvents = max($activities->count(), 1);

        $typeMeta = [
            'upload' => ['label' => 'Uploads', 'color' => '#60a5fa'],
            'parse'  => ['label' => 'Parses',  'color' => '#34d399'],
            'map'    => ['label' => 'Maps',    'color' => '#c084fc'],
            'clean'  => ['label' => 'Cleans',  'color' => '#22d3ee'],
            'score'  => ['label' => 'Scores',  'color' => '#fbbf24'],
        ];

        // Live indicator (activity within last 5 min)
        $hasRecent = $activities->filter(fn($a) => $a['at']->diffInMinutes(now()) < 5)->count() > 0;

        // Streak
        $activeDates = $activities->map(fn($a) => $a['at']->format('Y-m-d'))->unique()->sort()->values();
        $streak = 0;
        $checkDate = now()->format('Y-m-d');
        for ($i = 0; $i < 90; $i++) {
            if ($activeDates->contains($checkDate)) {
                $streak++;
                $checkDate = \Carbon\Carbon::parse($checkDate)->subDay()->format('Y-m-d');
            } else break;
        }

        // Heatmap data (last 12 weeks = 84 days)
        $heatmapDays = [];
        for ($i = 83; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateKey = $date->format('Y-m-d');
            $count = $activities->filter(fn($a) => $a['at']->format('Y-m-d') === $dateKey)->count();
            $heatmapDays[] = ['date' => $date, 'key' => $dateKey, 'count' => $count];
        }
        $maxHeat = max(array_column($heatmapDays, 'count')) ?: 1;

        // Peak hour
        $hourBuckets = array_fill(0, 24, 0);
        foreach ($activities as $a) {
            $hourBuckets[(int) $a['at']->format('H')]++;
        }
        $peakHour = array_search(max($hourBuckets), $hourBuckets);

        // Build filter chips counts
        $filterCounts = ['all' => $activities->count()];
        foreach ($typeMeta as $key => $meta) {
            $filterCounts[$key] = $typeCounts[$key] ?? 0;
        }

        $grouped = $activities->groupBy(fn($a) => $a['at']->format('Y-m-d'));
    @endphp

    {{-- Header --}}
    <div style="margin-bottom: 28px; display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 20px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.15em; color: #10b981; font-weight: 700; margin-bottom: 8px;">
                Activity
                @if($hasRecent)
                    <span class="live-pill">
                        <span class="live-dot"></span>
                        Live
                    </span>
                @endif
                @if($streak > 1)
                    <span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; background-color: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 999px; font-size: 9px; font-weight: 700; letter-spacing: 0.05em; text-transform: none; color: #fbbf24;">
                        🔥 {{ $streak }} day streak
                    </span>
                @endif
            </div>
            <h1 style="font-size: 34px; font-weight: 800; color: #f5f5f5; margin: 0 0 6px 0; letter-spacing: -0.02em;">
                Activity Stream
            </h1>
            <p style="color: #8a8a8a; font-size: 14px; margin: 0;">
                <span id="liveClock">{{ now()->format('l, F j, Y · H:i') }}</span>
            </p>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="button" id="viewToggle" class="act-btn act-btn-ghost">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/>
                    <circle cx="3.5" cy="6" r="0.5" fill="currentColor"/><circle cx="3.5" cy="12" r="0.5" fill="currentColor"/><circle cx="3.5" cy="18" r="0.5" fill="currentColor"/>
                </svg>
                <span id="viewToggleLabel">Stream View</span>
            </button>
        </div>
    </div>

    @if($activities->isEmpty())
        <div style="background: linear-gradient(135deg, #131313 0%, #0f1f1a 100%); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 24px; padding: 64px 32px; text-align: center;">
            <div style="width: 88px; height: 88px; margin: 0 auto 24px; border-radius: 24px; background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); display: flex; align-items: center; justify-content: center;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <h2 style="font-size: 24px; font-weight: 800; color: #f5f5f5; margin: 0 0 12px 0;">No activity yet</h2>
            <p style="color: #8a8a8a; font-size: 15px; margin: 0 0 28px 0; max-width: 480px; margin-left: auto; margin-right: auto;">
                Everything you do — uploads, matches, cleanings, and scores — will appear here as a chronological timeline.
            </p>
            <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: center;">
                <a href="{{ route('uploads.create') }}" class="act-btn act-btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Upload Your First File
                </a>
                <form action="{{ route('uploads.generate-sample') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="act-btn act-btn-ghost">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                        Try Sample Data
                    </button>
                </form>
            </div>
        </div>
    @else
        {{-- ============ STAT CARDS ============ --}}
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px;" class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background-color: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.3);">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="stat-label">Today</div>
                <div class="stat-value" style="color: #10b981;" data-count-to="{{ $todayCount }}">0</div>
                <div class="stat-sub">{{ $yesterdayCount }} yesterday</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background-color: rgba(52, 211, 153, 0.1); border-color: rgba(52, 211, 153, 0.3);">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <div class="stat-label">This Week</div>
                <div class="stat-value" style="color: #34d399;" data-count-to="{{ $weekCount }}">0</div>
                <div class="stat-sub">last 7 days</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background-color: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.3);">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2"><path d="M3 3v18h18"/><path d="M7 12l4-4 4 4 5-5"/></svg>
                </div>
                <div class="stat-label">This Month</div>
                <div class="stat-value" style="color: #60a5fa;" data-count-to="{{ $monthCount }}">0</div>
                <div class="stat-sub">last 30 days</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background-color: rgba(168, 85, 247, 0.1); border-color: rgba(168, 85, 247, 0.3);">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#c084fc" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                </div>
                <div class="stat-label">All Time</div>
                <div class="stat-value" style="color: #c084fc;" data-count-to="{{ $activities->count() }}">0</div>
                <div class="stat-sub">total events</div>
            </div>
        </div>

        {{-- ============ HEATMAP + TYPE DONUT ============ --}}
        <div style="display: grid; grid-template-columns: 1.7fr 1fr; gap: 16px; margin-bottom: 20px;" class="heatmap-grid">

            {{-- Heatmap --}}
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <div class="panel-title">Activity Heatmap</div>
                        <div class="panel-sub">Last 12 weeks · click any day for details</div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px; font-size: 10px; color: #5a5a5a; font-weight: 600;">
                        <span>Less</span>
                        <div style="width: 12px; height: 12px; border-radius: 3px; background-color: #1a1a1a;"></div>
                        <div style="width: 12px; height: 12px; border-radius: 3px; background-color: rgba(16, 185, 129, 0.25);"></div>
                        <div style="width: 12px; height: 12px; border-radius: 3px; background-color: rgba(16, 185, 129, 0.5);"></div>
                        <div style="width: 12px; height: 12px; border-radius: 3px; background-color: rgba(16, 185, 129, 0.75);"></div>
                        <div style="width: 12px; height: 12px; border-radius: 3px; background-color: #10b981;"></div>
                        <span>More</span>
                    </div>
                </div>
                <div style="padding: 24px;">
                    <div style="display: grid; grid-template-columns: repeat(12, 1fr); gap: 3px;">
                        @foreach($heatmapDays as $day)
                            @php
                                $intensity = $maxHeat > 0 ? $day['count'] / $maxHeat : 0;
                                $bg = $day['count'] === 0 ? '#1a1a1a' : 'rgba(16, 185, 129, ' . (0.2 + $intensity * 0.8) . ')';
                                $isToday = $day['date']->isToday();
                            @endphp
                            <div class="heat-cell"
                                 data-date="{{ $day['date']->format('M j, Y') }}"
                                 data-count="{{ $day['count'] }}"
                                 data-day="{{ $day['date']->format('l') }}"
                                 style="aspect-ratio: 1; background-color: {{ $bg }}; border-radius: 3px; cursor: pointer; transition: transform 0.15s, box-shadow 0.15s; position: relative; {{ $isToday ? 'box-shadow: 0 0 0 2px #10b981;' : '' }}"></div>
                        @endforeach
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 12px; font-size: 10px; color: #3a3a3a; font-weight: 700; letter-spacing: 0.05em;">
                        <span>{{ now()->subDays(83)->format('M j') }}</span>
                        <span>{{ now()->subDays(41)->format('M j') }}</span>
                        <span>{{ now()->format('M j') }}</span>
                    </div>

                    <div id="heatDetail" style="margin-top: 16px; padding: 12px 16px; background-color: #0a0a0a; border: 1px solid #222; border-radius: 10px; font-size: 12px; color: #8a8a8a; text-align: center;">
                        Hover a cell to see details
                    </div>
                </div>
            </div>

            {{-- Type Distribution --}}
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <div class="panel-title">Event Types</div>
                        <div class="panel-sub">What you do most</div>
                    </div>
                </div>
                <div style="padding: 24px;">
                    @php
                        $donutRadius = 50;
                        $donutCirc = 2 * 3.14159 * $donutRadius;
                        $donutOffset = 0;
                        $donutSegments = [];
                        foreach ($typeMeta as $key => $meta) {
                            $count = $typeCounts[$key] ?? 0;
                            if ($count === 0) continue;
                            $pct = $count / $totalEvents;
                            $dashLen = $donutCirc * $pct;
                            $donutSegments[] = ['key' => $key, 'color' => $meta['color'], 'dash' => $dashLen, 'gap' => $donutCirc - $dashLen, 'offset' => -$donutOffset, 'pct' => round($pct * 100), 'count' => $count];
                            $donutOffset += $dashLen;
                        }
                    @endphp

                    <div style="position: relative; width: 160px; height: 160px; margin: 0 auto 20px;">
                        <svg viewBox="0 0 140 140" style="width: 100%; height: 100%; transform: rotate(-90deg);">
                            <circle cx="70" cy="70" r="{{ $donutRadius }}" fill="none" stroke="#1a1a1a" stroke-width="14"/>
                            @foreach($donutSegments as $seg)
                                <circle cx="70" cy="70" r="{{ $donutRadius }}" fill="none" stroke="{{ $seg['color'] }}" stroke-width="14"
                                        stroke-dasharray="{{ $seg['dash'] }} {{ $seg['gap'] }}"
                                        stroke-dashoffset="{{ $seg['offset'] }}"
                                        stroke-linecap="butt"
                                        class="donut-seg"
                                        data-label="{{ $typeMeta[$seg['key']]['label'] }}"
                                        data-pct="{{ $seg['pct'] }}"
                                        data-count="{{ $seg['count'] }}"
                                        style="cursor: pointer; transition: opacity 0.2s;"/>
                            @endforeach
                        </svg>
                        <div id="donutCenter" style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none;">
                            <div id="donutVal" style="font-size: 28px; font-weight: 800; color: #f5f5f5; line-height: 1;">{{ $activities->count() }}</div>
                            <div id="donutLbl" style="font-size: 9px; text-transform: uppercase; letter-spacing: 0.15em; color: #5a5a5a; font-weight: 700; margin-top: 4px;">events</div>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        @foreach($typeMeta as $key => $meta)
                            @php $count = $typeCounts[$key] ?? 0; @endphp
                            <div class="donut-legend" data-key="{{ $key }}" style="display: flex; align-items: center; justify-content: space-between; padding: 6px 10px; border-radius: 8px; cursor: pointer; transition: all 0.15s;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="width: 8px; height: 8px; border-radius: 2px; background-color: {{ $meta['color'] }};"></div>
                                    <span style="font-size: 12px; color: #8a8a8a; font-weight: 500;">{{ $meta['label'] }}</span>
                                </div>
                                <span style="font-size: 12px; font-weight: 700; color: #f5f5f5;">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ FILTER BAR ============ --}}
        <div class="filter-bar">
            <div style="flex: 1; min-width: 220px; display: flex; align-items: center; gap: 10px; background-color: #0a0a0a; border: 1px solid #2a2a2a; border-radius: 999px; padding: 10px 16px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#8a8a8a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" id="actSearch" placeholder="Search activity... (press /)" style="flex: 1; background: transparent; border: none; color: #f5f5f5; font-size: 13px; outline: none; font-family: inherit;">
            </div>

            <div style="display: flex; gap: 4px; background-color: #0a0a0a; border: 1px solid #2a2a2a; border-radius: 999px; padding: 4px; overflow-x: auto;">
                <button type="button" class="chip chip-active" data-type="all">All <span class="chip-count">{{ $filterCounts['all'] }}</span></button>
                @foreach($typeMeta as $key => $meta)
                    @if(($filterCounts[$key] ?? 0) > 0)
                        <button type="button" class="chip" data-type="{{ $key }}" style="color: {{ $meta['color'] }};">
                            {{ $meta['label'] }} <span class="chip-count">{{ $filterCounts[$key] }}</span>
                        </button>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- ============ TIMELINE ============ --}}
        <div id="activityList">
            @forelse($grouped as $date => $dayActivities)
                @php
                    $carbonDate = \Carbon\Carbon::parse($date);
                    if ($carbonDate->isToday()) $dayLabel = 'Today';
                    elseif ($carbonDate->isYesterday()) $dayLabel = 'Yesterday';
                    elseif ($carbonDate->isCurrentWeek()) $dayLabel = $carbonDate->format('l');
                    else $dayLabel = $carbonDate->format('l, M j');
                @endphp

                <div class="day-group" data-date="{{ $date }}">
                    <div class="day-header">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="font-size: 13px; font-weight: 800; color: #f5f5f5;">{{ $dayLabel }}</div>
                            <div style="font-size: 11px; color: #5a5a5a; font-weight: 600;">{{ $carbonDate->format('M j, Y') }}</div>
                        </div>
                        <div style="font-size: 11px; color: #5a5a5a; font-weight: 600;">
                            {{ $dayActivities->count() }} {{ $dayActivities->count() === 1 ? 'event' : 'events' }}
                        </div>
                    </div>

                    <div class="timeline">
                        @foreach($dayActivities as $activity)
                            @php
                                $iconPath = match($activity['icon']) {
                                    'upload'  => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>',
                                    'file'    => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>',
                                    'link'    => '<line x1="4" y1="12" x2="20" y2="12"/><polyline points="14 6 20 12 14 18"/>',
                                    'sparkle' => '<path d="M12 3l1.9 5.8L20 10l-5.8 1.9L12 18l-1.9-5.8L4 10l5.8-1.9z"/>',
                                    'chart'   => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
                                    default   => '<circle cx="12" cy="12" r="10"/>',
                                };
                            @endphp
                            <a href="{{ $activity['url'] }}" class="timeline-item" data-type="{{ $activity['type'] }}" data-search="{{ strtolower($activity['title'] . ' ' . $activity['desc']) }}">
                                <div class="timeline-time">{{ $activity['at']->format('H:i') }}</div>
                                <div class="timeline-node" style="--dot-color: {{ $activity['color'] }};">
                                    <div class="timeline-dot" style="background-color: {{ $activity['color'] }};"></div>
                                </div>
                                <div class="timeline-card">
                                    <div class="timeline-icon" style="background-color: {{ $activity['color'] }}15; border-color: {{ $activity['color'] }}40;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="{{ $activity['color'] }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            {!! $iconPath !!}
                                        </svg>
                                    </div>
                                    <div style="flex: 1; min-width: 0;">
                                        <div style="font-size: 13px; color: #f5f5f5; font-weight: 600;">{{ $activity['title'] }}</div>
                                        <div style="font-size: 11px; color: #5a5a5a; margin-top: 2px;">{{ $activity['desc'] }}</div>
                                    </div>
                                    <div class="timeline-type-badge" style="background-color: {{ $activity['color'] }}15; color: {{ $activity['color'] }}; border-color: {{ $activity['color'] }}30;">
                                        {{ $typeMeta[$activity['type']]['label'] ?? $activity['type'] }}
                                    </div>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#5a5a5a" stroke-width="2" class="timeline-arrow" style="flex-shrink: 0;">
                                        <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                                    </svg>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @empty
                <div style="text-align: center; padding: 40px; color: #5a5a5a;">No activity to show</div>
            @endforelse
        </div>

        <div id="noResults" style="display: none; padding: 60px 24px; text-align: center; background-color: #131313; border: 1px dashed #2a2a2a; border-radius: 20px;">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#5a5a5a" stroke-width="1.5" style="margin-bottom: 12px;">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <div style="font-size: 13px; color: #8a8a8a;">No activity matches your filter</div>
        </div>
    @endif

    <style>
        @keyframes livePulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.4); }
        }

        .live-pill {
            display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px;
            background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 999px; font-size: 9px; font-weight: 700;
            letter-spacing: 0.05em; text-transform: none; color: #10b981;
        }

        .live-dot {
            width: 5px; height: 5px; border-radius: 50%; background-color: #10b981;
            animation: livePulse 2s ease-in-out infinite;
        }

        .act-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 12px 22px; border-radius: 999px; font-weight: 700; font-size: 13px;
            line-height: 1; text-decoration: none; cursor: pointer; transition: all 0.15s;
            font-family: inherit; height: 44px; box-sizing: border-box; border: 1px solid transparent;
        }
        .act-btn svg { display: block; flex-shrink: 0; }
        .act-btn-primary { background-color: #10b981; color: #000; }
        .act-btn-primary:hover { background-color: #34d399; transform: translateY(-1px); box-shadow: 0 6px 20px -6px rgba(16, 185, 129, 0.5); }
        .act-btn-ghost { background-color: transparent; color: #8a8a8a; border-color: #2a2a2a; }
        .act-btn-ghost:hover { color: #10b981; border-color: rgba(16, 185, 129, 0.4); }

        .stat-card {
            background-color: #131313; border: 1px solid #222; border-radius: 16px;
            padding: 20px; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative; overflow: hidden;
        }
        .stat-card:hover { border-color: rgba(16, 185, 129, 0.3); transform: translateY(-3px); box-shadow: 0 12px 24px -10px rgba(16, 185, 129, 0.15); }
        .stat-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; border: 1px solid; }
        .stat-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 700; margin-bottom: 8px; }
        .stat-value { font-size: 30px; font-weight: 800; line-height: 1; letter-spacing: -0.02em; }
        .stat-sub { font-size: 11px; color: #5a5a5a; margin-top: 6px; }

        .panel { background-color: #131313; border: 1px solid #222; border-radius: 20px; overflow: hidden; }
        .panel-header { padding: 18px 24px; border-bottom: 1px solid #222; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
        .panel-title { font-size: 14px; font-weight: 700; color: #f5f5f5; margin-bottom: 3px; }
        .panel-sub { font-size: 11px; color: #5a5a5a; }

        .heat-cell:hover {
            transform: scale(1.4);
            box-shadow: 0 0 0 2px #10b981, 0 0 20px rgba(16, 185, 129, 0.5);
            z-index: 10;
        }

        .donut-seg:hover { opacity: 0.75; }
        .donut-seg.dimmed { opacity: 0.15; }
        .donut-legend:hover { background-color: rgba(38, 38, 38, 0.5); }
        .donut-legend.dimmed { opacity: 0.3; }

        .filter-bar {
            display: flex; flex-wrap: wrap; gap: 12px; align-items: center;
            background-color: #131313; border: 1px solid #222; border-radius: 16px;
            padding: 14px 16px; margin-bottom: 24px;
        }

        .chip {
            display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px;
            border-radius: 999px; font-size: 12px; font-weight: 600;
            border: none; cursor: pointer; background: transparent; color: #8a8a8a;
            transition: all 0.15s; font-family: inherit; white-space: nowrap;
        }
        .chip:hover { color: #f5f5f5; background-color: rgba(38, 38, 38, 0.6); }
        .chip.chip-active { background-color: #10b981; color: #000 !important; }
        .chip-count {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 18px; height: 18px; padding: 0 5px; border-radius: 999px;
            font-size: 10px; font-weight: 800;
            background-color: rgba(255, 255, 255, 0.1); color: inherit;
        }
        .chip.chip-active .chip-count { background-color: rgba(0, 0, 0, 0.15); color: #000; }

        .day-group { margin-bottom: 24px; }
        .day-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 16px; background-color: rgba(10, 10, 10, 0.5);
            border-left: 2px solid #10b981; border-radius: 8px; margin-bottom: 12px;
        }

        .timeline { display: flex; flex-direction: column; gap: 8px; }

        .timeline-item {
            display: grid; grid-template-columns: 56px 20px 1fr; gap: 12px;
            align-items: center; text-decoration: none; transition: all 0.15s;
        }

        .timeline-time {
            font-size: 11px; color: #5a5a5a; font-weight: 700;
            text-align: right; font-family: monospace;
        }

        .timeline-node { position: relative; display: flex; align-items: center; justify-content: center; height: 100%; }
        .timeline-node::before {
            content: ''; position: absolute; top: 0; bottom: -8px; left: 50%;
            width: 2px; background-color: #1f1f1f; transform: translateX(-50%);
        }
        .timeline-item:last-child .timeline-node::before { display: none; }

        .timeline-dot {
            width: 10px; height: 10px; border-radius: 50%;
            border: 2px solid #131313; position: relative; z-index: 2;
            box-shadow: 0 0 0 3px #131313;
        }

        .timeline-card {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 18px; background-color: #131313;
            border: 1px solid #222; border-radius: 14px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); position: relative;
        }

        .timeline-item:hover .timeline-card {
            border-color: rgba(16, 185, 129, 0.4);
            transform: translateX(4px);
            box-shadow: 0 8px 20px -10px rgba(16, 185, 129, 0.2);
        }
        .timeline-item:hover .timeline-arrow { stroke: #10b981; transform: translateX(4px); }

        .timeline-icon {
            width: 36px; height: 36px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; border: 1px solid;
        }

        .timeline-type-badge {
            font-size: 9px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.1em; padding: 3px 9px; border-radius: 999px;
            border: 1px solid; white-space: nowrap; flex-shrink: 0;
        }

        .timeline-arrow { transition: all 0.2s; }

        .timeline-item.hidden { display: none; }

        @media (max-width: 1000px) { .heatmap-grid { grid-template-columns: 1fr !important; } }
        @media (max-width: 800px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr) !important; }
            .timeline-item { grid-template-columns: 40px 16px 1fr !important; gap: 8px; }
            .timeline-time { font-size: 10px; }
        }
        @media print { nav, .act-btn, .filter-bar { display: none !important; } }
    </style>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // ===== Count-up =====
            document.querySelectorAll('[data-count-to]').forEach(el => {
                const target = parseInt(el.dataset.countTo);
                if (isNaN(target)) return;
                let current = 0;
                const duration = 1200;
                const start = performance.now();
                function tick(now) {
                    const p = Math.min((now - start) / duration, 1);
                    const eased = 1 - Math.pow(1 - p, 3);
                    current = Math.round(eased * target);
                    el.textContent = current;
                    if (p < 1) requestAnimationFrame(tick);
                }
                requestAnimationFrame(tick);
            });

            // ===== Live clock =====
            const liveClock = document.getElementById('liveClock');
            function updateClock() {
                if (!liveClock) return;
                const d = new Date();
                const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
                const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
                const hh = String(d.getHours()).padStart(2, '0');
                const mm = String(d.getMinutes()).padStart(2, '0');
                liveClock.textContent = days[d.getDay()] + ', ' + months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear() + ' · ' + hh + ':' + mm;
            }
            updateClock();
            setInterval(updateClock, 30000);

            // ===== Heatmap hover =====
            const heatDetail = document.getElementById('heatDetail');
            document.querySelectorAll('.heat-cell').forEach(cell => {
                cell.addEventListener('mouseenter', () => {
                    const date = cell.dataset.date;
                    const count = cell.dataset.count;
                    const day = cell.dataset.day;
                    heatDetail.innerHTML = '<span style="color: #10b981; font-weight: 700;">' + day + '</span> · <span style="color: #f5f5f5;">' + date + '</span> · <span style="color: #f5f5f5; font-weight: 700;">' + count + ' event' + (count == 1 ? '' : 's') + '</span>';
                });
            });

            // ===== Donut interactions =====
            const donutVal = document.getElementById('donutVal');
            const donutLbl = document.getElementById('donutLbl');
            const totalEvents = {{ $activities->count() }};

            document.querySelectorAll('.donut-seg').forEach(seg => {
                seg.addEventListener('mouseenter', () => {
                    donutVal.textContent = seg.dataset.pct + '%';
                    donutLbl.textContent = seg.dataset.label.toLowerCase();
                    document.querySelectorAll('.donut-seg').forEach(s => {
                        if (s !== seg) s.classList.add('dimmed');
                    });
                });
                seg.addEventListener('mouseleave', () => {
                    donutVal.textContent = totalEvents;
                    donutLbl.textContent = 'events';
                    document.querySelectorAll('.donut-seg').forEach(s => s.classList.remove('dimmed'));
                });
                seg.addEventListener('click', () => {
                    const label = seg.dataset.label;
                    document.querySelectorAll('.chip').forEach(c => {
                        if (c.textContent.trim().startsWith(label)) c.click();
                    });
                });
            });

            document.querySelectorAll('.donut-legend').forEach(lg => {
                lg.addEventListener('mouseenter', () => {
                    const key = lg.dataset.key;
                    document.querySelectorAll('.donut-seg').forEach(s => {
                        const label = s.dataset.label;
                        const expected = key.charAt(0).toUpperCase() + key.slice(1) + 's';
                        if (label !== expected) s.classList.add('dimmed');
                    });
                });
                lg.addEventListener('mouseleave', () => {
                    document.querySelectorAll('.donut-seg').forEach(s => s.classList.remove('dimmed'));
                });
            });

            // ===== Filters =====
            const search = document.getElementById('actSearch');
            const chips = document.querySelectorAll('.chip[data-type]');
            const items = document.querySelectorAll('.timeline-item');
            const dayGroups = document.querySelectorAll('.day-group');
            const noResults = document.getElementById('noResults');
            let activeType = 'all';

            function applyFilters() {
                const q = (search?.value || '').toLowerCase();
                let visible = 0;

                items.forEach(item => {
                    const type = item.dataset.type;
                    const search = item.dataset.search;
                    const typeMatch = activeType === 'all' || type === activeType;
                    const searchMatch = !q || search.includes(q);
                    if (typeMatch && searchMatch) {
                        item.classList.remove('hidden');
                        visible++;
                    } else {
                        item.classList.add('hidden');
                    }
                });

                // Hide day groups with no visible items
                dayGroups.forEach(group => {
                    const hasVisible = group.querySelectorAll('.timeline-item:not(.hidden)').length > 0;
                    group.style.display = hasVisible ? '' : 'none';
                });

                noResults.style.display = visible === 0 ? 'block' : 'none';
            }

            search?.addEventListener('input', applyFilters);

            chips.forEach(chip => {
                chip.addEventListener('click', () => {
                    activeType = chip.dataset.type;
                    chips.forEach(c => c.classList.remove('chip-active'));
                    chip.classList.add('chip-active');
                    applyFilters();
                });
            });

            // ===== Keyboard shortcut =====
            document.addEventListener('keydown', (e) => {
                if (e.target.tagName === 'INPUT') return;
                if (e.key === '/') {
                    e.preventDefault();
                    search?.focus();
                }
            });

            // ===== View toggle (stream vs timeline) =====
            const viewToggle = document.getElementById('viewToggle');
            const viewToggleLabel = document.getElementById('viewToggleLabel');
            let isStreamView = false;

            viewToggle?.addEventListener('click', () => {
                isStreamView = !isStreamView;
                const timeline = document.querySelector('.timeline');
                if (isStreamView) {
                    viewToggleLabel.textContent = 'Timeline View';
                    document.querySelectorAll('.timeline-node').forEach(n => n.style.opacity = '0');
                    document.querySelectorAll('.timeline-time').forEach(t => t.style.opacity = '0');
                    document.querySelectorAll('.timeline-item').forEach(i => {
                        i.style.gridTemplateColumns = '1fr';
                    });
                } else {
                    viewToggleLabel.textContent = 'Stream View';
                    document.querySelectorAll('.timeline-node').forEach(n => n.style.opacity = '1');
                    document.querySelectorAll('.timeline-time').forEach(t => t.style.opacity = '1');
                    document.querySelectorAll('.timeline-item').forEach(i => {
                        i.style.gridTemplateColumns = '56px 20px 1fr';
                    });
                }
            });
        });
    </script>
    @endpush
</x-app-layout>