<x-app-layout>
    @php
        $user = Auth::user();
        $uploads = $user->uploads()->with(['readinessReport', 'cleanedRecords', 'matches', 'rawRows'])->latest()->get();
        $totalUploads = $uploads->count();
        $totalRows = $uploads->sum('row_count');
        $totalMatches = $uploads->sum(fn($u) => $u->matches->count());
        $totalCleaned = $uploads->sum(fn($u) => $u->cleanedRecords->count());

        $scoredUploads = $uploads->filter(fn($u) => $u->readinessReport);
        $avgReadiness = $scoredUploads->count() > 0 ? round($scoredUploads->avg(fn($u) => $u->readinessReport->score)) : null;

        $pipelineStats = [
            'uploaded' => $uploads->filter(fn($u) => !$u->rawRows->isNotEmpty())->count(),
            'parsed'   => $uploads->filter(fn($u) => $u->rawRows->isNotEmpty() && !$u->matches->isNotEmpty())->count(),
            'mapped'   => $uploads->filter(fn($u) => $u->matches->isNotEmpty() && !$u->cleanedRecords->isNotEmpty())->count(),
            'cleaned'  => $uploads->filter(fn($u) => $u->cleanedRecords->isNotEmpty() && !$u->readinessReport)->count(),
            'scored'   => $uploads->filter(fn($u) => $u->readinessReport)->count(),
        ];

        $recent = $uploads->take(5);
        $weeklyData = collect(range(6, 0))->map(fn($d) => $uploads->filter(fn($u) => $u->created_at->format('Y-m-d') === now()->subDays($d)->format('Y-m-d'))->count())->toArray();
        $bestUpload = $scoredUploads->sortByDesc(fn($u) => $u->readinessReport->score)->first();
        $totalIssues = $uploads->sum(fn($u) => $u->cleanedRecords->sum('issues_count'));

        $readinessBuckets = ['excellent' => 0, 'good' => 0, 'fair' => 0, 'poor' => 0];
        foreach ($scoredUploads as $u) {
            $s = $u->readinessReport->score;
            if ($s >= 90) $readinessBuckets['excellent']++;
            elseif ($s >= 75) $readinessBuckets['good']++;
            elseif ($s >= 50) $readinessBuckets['fair']++;
            else $readinessBuckets['poor']++;
        }

        $pipelineTotal = max(array_sum($pipelineStats), 1);
        $pipelineSegments = [
            ['key' => 'scored',   'label' => 'Scored',   'color' => '#fbbf24', 'count' => $pipelineStats['scored']],
            ['key' => 'cleaned',  'label' => 'Cleaned',  'color' => '#22d3ee', 'count' => $pipelineStats['cleaned']],
            ['key' => 'mapped',   'label' => 'Mapped',   'color' => '#c084fc', 'count' => $pipelineStats['mapped']],
            ['key' => 'parsed',   'label' => 'Parsed',   'color' => '#34d399', 'count' => $pipelineStats['parsed']],
            ['key' => 'uploaded', 'label' => 'Uploaded', 'color' => '#60a5fa', 'count' => $pipelineStats['uploaded']],
        ];

        // 30-day trend
        $trendData = collect(range(29, 0))->map(function ($daysAgo) use ($scoredUploads) {
            $date = now()->subDays($daysAgo)->format('Y-m-d');
            $dayScores = $scoredUploads->filter(fn($u) => $u->readinessReport->created_at->format('Y-m-d') === $date)->map(fn($u) => $u->readinessReport->score);
            return $dayScores->count() > 0 ? round($dayScores->avg()) : null;
        });

        $trendPoints = [];
        $last = null;
        foreach ($trendData as $val) {
            if ($val !== null) $last = $val;
            $trendPoints[] = $last;
        }

        $greeting = match(true) {
            now()->hour < 12 => 'Good morning',
            now()->hour < 18 => 'Good afternoon',
            default => 'Good evening',
        };
    @endphp

    {{-- Header --}}
    <div style="margin-bottom: 28px; display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 20px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.15em; color: #10b981; font-weight: 700; margin-bottom: 8px;">
                Dashboard
                <span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 999px; font-size: 9px; font-weight: 700; letter-spacing: 0.05em; text-transform: none; color: #10b981;">
                    <span style="width: 5px; height: 5px; border-radius: 50%; background-color: #10b981; animation: livePulse 2s ease-in-out infinite;"></span>
                    Live
                </span>
            </div>
            <h1 style="font-size: 34px; font-weight: 800; color: #f5f5f5; margin: 0 0 6px 0; letter-spacing: -0.02em;">
                {{ $greeting }}, {{ explode(' ', $user->name)[0] }}.
            </h1>
            <p style="color: #8a8a8a; font-size: 14px; margin: 0;">
                Your workspace at a glance — {{ now()->format('l, F j') }}
            </p>
        </div>

        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button type="button" onclick="window.print()" class="dash-btn dash-btn-ghost">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                <span>Export</span>
            </button>
            <a href="{{ route('uploads.create') }}" class="dash-btn dash-btn-primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span>New Upload</span>
            </a>
        </div>
    </div>

    @if($totalUploads === 0)
        <div style="background: linear-gradient(135deg, #131313 0%, #0f1f1a 100%); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 24px; padding: 64px 32px; text-align: center;">
            <h2 style="font-size: 26px; font-weight: 800; color: #f5f5f5; margin: 0 0 12px 0;">Welcome to medbrid<span style="color: #10b981;">G</span>e</h2>
            <p style="color: #8a8a8a; font-size: 15px; margin: 0 auto 32px; max-width: 480px;">Upload a hospital CSV to start.</p>
            <a href="{{ route('uploads.create') }}" class="dash-btn dash-btn-primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span>Upload Your First File</span>
            </a>
        </div>
    @else
        {{-- STAT CARDS (clickable) --}}
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 20px;" class="stat-grid-lg">
            <a href="{{ route('uploads.index') }}" class="dash-stat" style="text-decoration: none;">
                <div class="dash-stat-header">
                    <div class="dash-stat-label">Total Uploads</div>
                    <div class="dash-stat-icon" style="background-color: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.3);">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                </div>
                <div class="dash-stat-value">{{ $totalUploads }}</div>
                <div class="dash-stat-footer">
                    <span style="color: #60a5fa; font-weight: 600;">{{ number_format($totalRows) }}</span>
                    <span style="color: #5a5a5a;">rows total</span>
                </div>
            </a>

            <div class="dash-stat">
                <div class="dash-stat-header">
                    <div class="dash-stat-label">Avg. Readiness</div>
                    <div class="dash-stat-icon" style="background-color: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.3);">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    </div>
                </div>
                @if($avgReadiness !== null)
                    <div class="dash-stat-value" style="color: #10b981;">{{ $avgReadiness }}<span style="font-size: 20px; color: #5a5a5a;">%</span></div>
                    <div class="dash-stat-footer"><span style="color: #10b981; font-weight: 600;">{{ $scoredUploads->count() }}</span><span style="color: #5a5a5a;">files scored</span></div>
                @else
                    <div class="dash-stat-value" style="color: #5a5a5a;">—</div>
                    <div class="dash-stat-footer"><span style="color: #5a5a5a;">No scored files yet</span></div>
                @endif
            </div>

            <div class="dash-stat">
                <div class="dash-stat-header">
                    <div class="dash-stat-label">Fields Matched</div>
                    <div class="dash-stat-icon" style="background-color: rgba(168, 85, 247, 0.1); border-color: rgba(168, 85, 247, 0.3);">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#c084fc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    </div>
                </div>
                <div class="dash-stat-value">{{ $totalMatches }}</div>
                <div class="dash-stat-footer"><span style="color: #c084fc; font-weight: 600;">{{ $totalUploads > 0 ? round($totalMatches / ($totalUploads * 8) * 100) : 0 }}%</span><span style="color: #5a5a5a;">coverage</span></div>
            </div>

            <div class="dash-stat">
                <div class="dash-stat-header">
                    <div class="dash-stat-label">Issues Found</div>
                    <div class="dash-stat-icon" style="background-color: rgba(245, 158, 11, 0.1); border-color: rgba(245, 158, 11, 0.3);">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                </div>
                <div class="dash-stat-value" style="color: #fbbf24;">{{ $totalIssues }}</div>
                <div class="dash-stat-footer"><span style="color: #fbbf24; font-weight: 600;">{{ $totalCleaned }}</span><span style="color: #5a5a5a;">processed</span></div>
            </div>
        </div>

        {{-- ===== INTERACTIVE TREND CHART ===== --}}
        <div class="dash-card" style="margin-bottom: 20px;">
            <div class="dash-card-header">
                <div>
                    <div class="dash-card-title">Readiness Trend</div>
                    <div class="dash-card-subtitle">Hover to inspect · click to pin · drag to scrub</div>
                </div>
                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <div id="trendLiveValue" style="display: inline-flex; align-items: center; gap: 6px; font-size: 11px; color: #10b981; font-weight: 700; background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 999px; padding: 4px 12px; min-width: 130px; justify-content: center; transition: all 0.15s;">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background-color: #10b981;"></span>
                        <span id="trendLiveText">Now: {{ $avgReadiness ?? '—' }}%</span>
                    </div>
                    <div style="display: flex; gap: 3px; background-color: #1a1a1a; border: 1px solid #2a2a2a; border-radius: 8px; padding: 3px;">
                        <button type="button" class="range-btn active" data-days="30">30d</button>
                        <button type="button" class="range-btn" data-days="14">14d</button>
                        <button type="button" class="range-btn" data-days="7">7d</button>
                    </div>
                </div>
            </div>
            <div style="padding: 24px;">
                @php
                    $chartWidth = 1000;
                    $chartHeight = 200;
                    $padding = 30;
                    $usableW = $chartWidth - $padding * 2;
                    $usableH = $chartHeight - $padding * 2;

                    $points = [];
                    foreach ($trendPoints as $i => $val) {
                        $x = $padding + ($i / max(count($trendPoints) - 1, 1)) * $usableW;
                        $y = $val !== null ? $padding + $usableH - ($val / 100) * $usableH : $padding + $usableH;
                        $points[] = ['x' => $x, 'y' => $y, 'val' => $val, 'day' => now()->subDays(29 - $i), 'index' => $i];
                    }

                    $pathD = '';
                    $hasData = false;
                    foreach ($points as $p) {
                        if ($p['val'] !== null) {
                            $pathD .= ($hasData ? ' L ' : 'M ') . $p['x'] . ' ' . $p['y'];
                            $hasData = true;
                        }
                    }

                    $areaD = $pathD;
                    if ($hasData) {
                        $firstX = collect($points)->first(fn($p) => $p['val'] !== null)['x'];
                        $lastX = collect($points)->reverse()->first(fn($p) => $p['val'] !== null)['x'];
                        $areaD .= ' L ' . $lastX . ' ' . ($padding + $usableH) . ' L ' . $firstX . ' ' . ($padding + $usableH) . ' Z';
                    }
                @endphp

                @if($hasData)
                    <div id="trendWrap" style="position: relative; width: 100%; user-select: none; touch-action: none;">
                        <svg id="trendSvg" viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" preserveAspectRatio="none" style="width: 100%; height: 220px; display: block; cursor: crosshair;">
                            <defs>
                                <linearGradient id="trendGradient" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#10b981" stop-opacity="0.35"/>
                                    <stop offset="100%" stop-color="#10b981" stop-opacity="0"/>
                                </linearGradient>
                                <filter id="glowFilter">
                                    <feGaussianBlur stdDeviation="4" result="blur"/>
                                    <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
                                </filter>
                            </defs>

                            {{-- Grid --}}
                            @for($i = 0; $i <= 4; $i++)
                                @php $y = $padding + ($i / 4) * $usableH; @endphp
                                <line x1="{{ $padding }}" y1="{{ $y }}" x2="{{ $chartWidth - $padding }}" y2="{{ $y }}" stroke="#1a1a1a" stroke-width="1"/>
                            @endfor

                            {{-- Hover crosshair --}}
                            <line id="crosshair" x1="0" y1="{{ $padding }}" x2="0" y2="{{ $padding + $usableH }}" stroke="#10b981" stroke-width="1.5" stroke-dasharray="3 3" opacity="0" style="pointer-events: none;"/>

                            {{-- Area --}}
                            <path d="{{ $areaD }}" fill="url(#trendGradient)"/>

                            {{-- Line --}}
                            <path d="{{ $pathD }}" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>

                            {{-- Points --}}
                            @foreach($points as $i => $p)
                                @if($p['val'] !== null)
                                    <g class="trend-pt" data-index="{{ $i }}" style="cursor: pointer;">
                                        <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="20" fill="transparent"/>
                                        <circle class="pt-dot" cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="5" fill="#131313" stroke="#10b981" stroke-width="2.5" style="transition: all 0.15s;"/>
                                        <circle class="pt-pulse" cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="5" fill="none" stroke="#10b981" stroke-width="2" opacity="0" style="pointer-events: none;"/>
                                    </g>
                                @endif
                            @endforeach
                        </svg>

                        {{-- Tooltip --}}
                        <div id="trendTip" style="position: absolute; display: none; background-color: #0a0a0a; border: 1px solid #10b981; border-radius: 12px; padding: 12px 16px; pointer-events: none; z-index: 20; transform: translate(-50%, calc(-100% - 16px)); box-shadow: 0 12px 40px -8px rgba(16, 185, 129, 0.5); min-width: 120px;">
                            <div id="tipDate" style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; color: #8a8a8a; font-weight: 700; margin-bottom: 6px;">—</div>
                            <div style="display: flex; align-items: baseline; gap: 8px;">
                                <div id="tipValue" style="font-size: 28px; font-weight: 800; color: #10b981; line-height: 1;">—</div>
                                <div style="font-size: 14px; color: #5a5a5a; font-weight: 600;">%</div>
                            </div>
                            <div id="tipDelta" style="font-size: 11px; color: #5a5a5a; margin-top: 4px;">readiness</div>
                        </div>

                        {{-- Y-axis --}}
                        <div style="position: absolute; top: 0; left: 0; height: 220px; display: flex; flex-direction: column; justify-content: space-between; padding: 30px 0; pointer-events: none;">
                            @for($i = 0; $i <= 4; $i++)
                                <span style="font-size: 9px; color: #3a3a3a; font-weight: 700;">{{ 100 - ($i * 25) }}%</span>
                            @endfor
                        </div>
                    </div>

                    {{-- X-axis --}}
                    <div id="trendXAxis" style="display: flex; justify-content: space-between; margin-top: 8px; padding: 0 30px;">
                        @foreach([0, 7, 14, 21, 29] as $dayIndex)
                            <span style="font-size: 10px; color: #5a5a5a; font-weight: 600;">
                                {{ now()->subDays(29 - $dayIndex)->format('M j') }}
                            </span>
                        @endforeach
                    </div>

                    {{-- Trend summary --}}
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #222;">
                        @php
                            $validTrend = collect($trendPoints)->filter()->values();
                            $startVal = $validTrend->first();
                            $endVal = $validTrend->last();
                            $delta = ($startVal !== null && $endVal !== null) ? $endVal - $startVal : 0;
                            $best = $validTrend->max();
                            $worst = $validTrend->min();
                        @endphp
                        <div style="text-align: center;">
                            <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; color: #5a5a5a; font-weight: 700; margin-bottom: 4px;">Change</div>
                            <div style="font-size: 18px; font-weight: 800; color: {{ $delta >= 0 ? '#10b981' : '#ef4444' }};">
                                {{ $delta >= 0 ? '+' : '' }}{{ $delta }}%
                            </div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; color: #5a5a5a; font-weight: 700; margin-bottom: 4px;">Best</div>
                            <div style="font-size: 18px; font-weight: 800; color: #10b981;">{{ $best ?? '—' }}%</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; color: #5a5a5a; font-weight: 700; margin-bottom: 4px;">Lowest</div>
                            <div style="font-size: 18px; font-weight: 800; color: #f59e0b;">{{ $worst ?? '—' }}%</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; color: #5a5a5a; font-weight: 700; margin-bottom: 4px;">Days</div>
                            <div style="font-size: 18px; font-weight: 800; color: #f5f5f5;">{{ $validTrend->count() }}</div>
                        </div>
                    </div>
                @else
                    <div style="text-align: center; padding: 40px;">
                        <div style="font-size: 13px; color: #5a5a5a;">No scored uploads in the last 30 days</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- 3 CHARTS ROW --}}
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 20px;" class="charts-grid">
            {{-- Donut --}}
            <div class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <div class="dash-card-title">Pipeline Breakdown</div>
                        <div class="dash-card-subtitle">Click a segment to filter</div>
                    </div>
                </div>
                <div style="padding: 24px;">
                    @php
                        $radius = 60;
                        $circumference = 2 * 3.14159 * $radius;
                        $offset = 0;
                        $donutSegments = [];
                        foreach ($pipelineSegments as $seg) {
                            if ($seg['count'] === 0) continue;
                            $pct = $seg['count'] / $pipelineTotal;
                            $dashLength = $circumference * $pct;
                            $donutSegments[] = [
                                'color' => $seg['color'], 'dash' => $dashLength, 'gap' => $circumference - $dashLength,
                                'offset' => -$offset, 'label' => $seg['label'], 'count' => $seg['count'], 'pct' => round($pct * 100),
                            ];
                            $offset += $dashLength;
                        }
                    @endphp

                    <div style="position: relative; width: 100%; max-width: 220px; margin: 0 auto 20px;">
                        <svg viewBox="0 0 160 160" style="width: 100%; height: auto; transform: rotate(-90deg);">
                            <circle cx="80" cy="80" r="{{ $radius }}" fill="none" stroke="#1a1a1a" stroke-width="18"/>
                            @foreach($donutSegments as $seg)
                                <circle cx="80" cy="80" r="{{ $radius }}" fill="none" stroke="{{ $seg['color'] }}" stroke-width="18"
                                        stroke-dasharray="{{ $seg['dash'] }} {{ $seg['gap'] }}"
                                        stroke-dashoffset="{{ $seg['offset'] }}" stroke-linecap="butt"
                                        class="donut-segment"
                                        data-label="{{ $seg['label'] }}" data-count="{{ $seg['count'] }}" data-pct="{{ $seg['pct'] }}"
                                        style="cursor: pointer; transition: all 0.2s; transform-origin: 80px 80px;"/>
                            @endforeach
                        </svg>
                        <div style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none;">
                            <div id="donutValue" style="font-size: 36px; font-weight: 800; color: #f5f5f5; line-height: 1; transition: all 0.2s;">{{ $pipelineTotal }}</div>
                            <div id="donutLabel" style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; color: #5a5a5a; font-weight: 700; margin-top: 4px;">Files</div>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 4px;">
                        @foreach($pipelineSegments as $seg)
                            <div class="donut-legend" data-label="{{ $seg['label'] }}" style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; border-radius: 8px; cursor: pointer; transition: all 0.15s;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 10px; height: 10px; border-radius: 3px; background-color: {{ $seg['color'] }}; transition: all 0.15s;"></div>
                                    <span style="font-size: 12px; color: #8a8a8a; font-weight: 500;">{{ $seg['label'] }}</span>
                                </div>
                                <span style="font-size: 13px; font-weight: 700; color: #f5f5f5;">{{ $seg['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Weekly Bars --}}
            <div class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <div class="dash-card-title">Weekly Activity</div>
                        <div class="dash-card-subtitle">Click a bar for details</div>
                    </div>
                    <div style="font-size: 11px; color: #10b981; font-weight: 700; background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 999px; padding: 3px 10px;">
                        {{ array_sum($weeklyData) }} total
                    </div>
                </div>
                <div style="padding: 24px;">
                    @php $maxWeekly = max(max($weeklyData), 1); @endphp
                    <div style="position: relative; height: 160px; margin-bottom: 12px;">
                        <div style="position: absolute; inset: 0; display: flex; flex-direction: column; justify-content: space-between; pointer-events: none;">
                            @for($i = 0; $i <= 4; $i++)
                                <div style="height: 1px; background-color: #1a1a1a; position: relative;">
                                    <span style="position: absolute; left: -28px; top: -7px; font-size: 9px; color: #3a3a3a; font-weight: 600;">{{ round($maxWeekly * (4 - $i) / 4) }}</span>
                                </div>
                            @endfor
                        </div>
                        <div style="position: absolute; inset: 0; display: flex; align-items: flex-end; justify-content: space-between; gap: 8px; padding-left: 8px;">
                            @foreach($weeklyData as $i => $count)
                                @php
                                    $barHeight = ($count / $maxWeekly) * 100;
                                    $isToday = $i === count($weeklyData) - 1;
                                @endphp
                                <div class="weekly-col" data-count="{{ $count }}" data-day="{{ $isToday ? 'Today' : now()->subDays(6 - $i)->format('l') }}" data-date="{{ now()->subDays(6 - $i)->format('M j') }}"
                                     style="flex: 1; height: 100%; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; cursor: pointer; position: relative;">
                                    <div class="weekly-tip" style="position: absolute; bottom: calc(100% + 8px); font-size: 11px; font-weight: 700; color: #f5f5f5; background-color: #0a0a0a; border: 1px solid #10b981; border-radius: 6px; padding: 3px 8px; opacity: 0; transition: opacity 0.15s; pointer-events: none; white-space: nowrap; z-index: 10;">
                                        {{ $count }} {{ $count === 1 ? 'file' : 'files' }}
                                    </div>
                                    <div class="weekly-bar" style="width: 100%; max-width: 40px; height: {{ $barHeight }}%; background: {{ $count > 0 ? ($isToday ? 'linear-gradient(180deg, #10b981, #059669)' : 'linear-gradient(180deg, #10b98190, #10b98150)') : '#1f1f1f' }}; border-radius: 6px 6px 0 0; min-height: 6px; transition: all 0.2s;"></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 10px; color: #5a5a5a; font-weight: 600; padding-left: 8px;">
                        @foreach($weeklyData as $i => $count)
                            <span class="weekly-label" style="flex: 1; text-align: center; transition: all 0.15s; {{ $i === count($weeklyData) - 1 ? 'color: #10b981; font-weight: 800;' : '' }}">
                                {{ now()->subDays(6 - $i)->format('D')[0] }}
                            </span>
                        @endforeach
                    </div>
                    <div id="weeklyDetail" style="margin-top: 16px; padding: 10px 14px; background-color: #0a0a0a; border: 1px solid #222; border-radius: 10px; font-size: 12px; color: #5a5a5a; text-align: center; min-height: 38px; display: flex; align-items: center; justify-content: center;">
                        Hover a bar for details
                    </div>
                </div>
            </div>

            {{-- Readiness Levels --}}
            <div class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <div class="dash-card-title">Readiness Levels</div>
                        <div class="dash-card-subtitle">Click a level to filter</div>
                    </div>
                </div>
                <div style="padding: 24px;">
                    @php
                        $buckets = [
                            ['key' => 'excellent', 'label' => 'Excellent', 'range' => '90+',   'color' => '#10b981'],
                            ['key' => 'good',      'label' => 'Good',      'range' => '75+',   'color' => '#34d399'],
                            ['key' => 'fair',      'label' => 'Fair',      'range' => '50+',   'color' => '#f59e0b'],
                            ['key' => 'poor',      'label' => 'Poor',      'range' => '<50',   'color' => '#ef4444'],
                        ];
                        $totalScored = max($scoredUploads->count(), 1);
                    @endphp

                    @foreach($buckets as $bucket)
                        @php
                            $count = $readinessBuckets[$bucket['key']];
                            $pct = ($count / $totalScored) * 100;
                        @endphp
                        <div class="readiness-row" style="margin-bottom: 12px; cursor: pointer; padding: 10px 12px; border-radius: 10px; border: 1px solid transparent; transition: all 0.15s;"
                             data-bucket="{{ $bucket['key'] }}" data-count="{{ $count }}" data-label="{{ $bucket['label'] }}" data-color="{{ $bucket['color'] }}">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; gap: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                                    <div style="min-width: 46px; height: 24px; border-radius: 6px; background-color: {{ $bucket['color'] }}20; border: 1px solid {{ $bucket['color'] }}40; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <span style="font-size: 11px; color: {{ $bucket['color'] }}; font-weight: 800;">{{ $bucket['range'] }}</span>
                                    </div>
                                    <span style="font-size: 13px; color: #8a8a8a; font-weight: 600;">{{ $bucket['label'] }}</span>
                                </div>
                                <span style="font-size: 18px; font-weight: 800; color: {{ $bucket['color'] }};">{{ $count }}</span>
                            </div>
                            <div style="height: 6px; background-color: #1a1a1a; border-radius: 999px; overflow: hidden;">
                                <div class="readiness-bar" style="height: 100%; background: linear-gradient(90deg, {{ $bucket['color'] }}80, {{ $bucket['color'] }}); width: {{ $pct }}%; border-radius: 999px; transition: width 0.6s;"></div>
                            </div>
                        </div>
                    @endforeach

                    <div id="bucketDetail" style="margin-top: 12px; padding: 10px 14px; background-color: #0a0a0a; border: 1px solid #222; border-radius: 10px; font-size: 12px; color: #5a5a5a; text-align: center;">
                        Click a level to see details
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent + Top Performer --}}
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px;" class="main-grid">
            <div class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <div class="dash-card-title">Recent Activity</div>
                        <div class="dash-card-subtitle">Latest uploads</div>
                    </div>
                    <a href="{{ route('uploads.index') }}" class="dash-card-action">View All →</a>
                </div>
                <div>
                    @foreach($recent as $upload)
                        @php
                            $status = 'uploaded'; $statusColor = '#60a5fa'; $statusBg = 'rgba(59, 130, 246, 0.12)'; $progress = 20;
                            if ($upload->readinessReport) { $status = 'scored'; $statusColor = '#fbbf24'; $statusBg = 'rgba(245, 158, 11, 0.12)'; $progress = 100; }
                            elseif ($upload->cleanedRecords->isNotEmpty()) { $status = 'cleaned'; $statusColor = '#22d3ee'; $statusBg = 'rgba(6, 182, 212, 0.12)'; $progress = 80; }
                            elseif ($upload->matches->isNotEmpty()) { $status = 'mapped'; $statusColor = '#c084fc'; $statusBg = 'rgba(168, 85, 247, 0.12)'; $progress = 60; }
                            elseif ($upload->rawRows->isNotEmpty()) { $status = 'parsed'; $statusColor = '#34d399'; $statusBg = 'rgba(16, 185, 129, 0.12)'; $progress = 40; }
                        @endphp
                        <a href="{{ route('uploads.show', $upload) }}" class="recent-row" style="display: flex; align-items: center; gap: 14px; padding: 14px 24px; text-decoration: none; border-top: 1px solid #1a1a1a;">
                            <div style="width: 38px; height: 38px; border-radius: 10px; background-color: #262626; border: 1px solid #2a2a2a; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                                    <span style="font-size: 13px; font-weight: 600; color: #f5f5f5; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 300px;">{{ $upload->original_filename }}</span>
                                    <span style="font-size: 10px; padding: 3px 9px; border-radius: 999px; font-weight: 700; text-transform: uppercase; background-color: {{ $statusBg }}; color: {{ $statusColor }};">{{ $status }}</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 100px; height: 4px; background-color: #1f1f1f; border-radius: 999px; overflow: hidden;">
                                        <div style="height: 100%; background: linear-gradient(90deg, #10b981, #34d399); width: {{ $progress }}%; border-radius: 999px;"></div>
                                    </div>
                                    <span style="font-size: 11px; color: #5a5a5a;">{{ $upload->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#5a5a5a" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <div class="dash-card-title">Top Performer</div>
                        <div class="dash-card-subtitle">Highest readiness</div>
                    </div>
                </div>
                <div style="padding: 24px;">
                    @if($bestUpload)
                        <div style="text-align: center;">
                            <div style="position: relative; display: inline-block; margin-bottom: 16px;">
                                <svg width="140" height="140" viewBox="0 0 140 140" style="transform: rotate(-90deg);">
                                    <circle cx="70" cy="70" r="58" fill="none" stroke="#1f1f1f" stroke-width="8"/>
                                    @php $dashArray = 2 * 3.14159 * 58; @endphp
                                    <circle id="bestRing" cx="70" cy="70" r="58" fill="none" stroke="#10b981" stroke-width="8"
                                            stroke-dasharray="{{ $dashArray }}" stroke-dashoffset="{{ $dashArray }}"
                                            stroke-linecap="round" style="transition: stroke-dashoffset 1.5s ease;"
                                            data-target="{{ $dashArray * (1 - $bestUpload->readinessReport->score / 100) }}"/>
                                </svg>
                                <div style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                    <div id="bestScore" style="font-size: 36px; font-weight: 800; color: #10b981; line-height: 1;" data-target="{{ $bestUpload->readinessReport->score }}">0</div>
                                    <div style="font-size: 10px; color: #5a5a5a; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 700;">percent</div>
                                </div>
                            </div>
                            <div style="font-size: 14px; font-weight: 600; color: #f5f5f5; margin-bottom: 4px;">{{ $bestUpload->original_filename }}</div>
                            <div style="font-size: 12px; color: #8a8a8a; margin-bottom: 20px;">{{ $bestUpload->row_count }} rows</div>
                            <a href="{{ route('readiness.show', $bestUpload) }}" style="display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 999px; background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); color: #10b981; text-decoration: none; font-size: 12px; font-weight: 700;">
                                View Report →
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Command Palette --}}
    <div id="cmdPalette" style="display: none; position: fixed; inset: 0; z-index: 200; align-items: flex-start; justify-content: center; padding-top: 10vh;">
        <div id="cmdOverlay" style="position: absolute; inset: 0; background-color: rgba(0,0,0,0.75); backdrop-filter: blur(8px);"></div>
        <div style="position: relative; background-color: #131313; border: 1px solid #2a2a2a; border-radius: 16px; width: 100%; max-width: 580px; margin: 0 16px; box-shadow: 0 30px 80px -10px rgba(0,0,0,0.9); overflow: hidden;">
            <div style="display: flex; align-items: center; gap: 12px; padding: 16px 20px; border-bottom: 1px solid #222;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#8a8a8a" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="cmdInput" placeholder="Search or type a command..." autocomplete="off"
                       style="flex: 1; background: transparent; border: none; color: #f5f5f5; font-size: 15px; outline: none;">
                <kbd style="padding: 4px 8px; background-color: #1a1a1a; border: 1px solid #2a2a2a; border-radius: 6px; font-size: 10px; color: #8a8a8a; font-weight: 600;">ESC</kbd>
            </div>
            <div id="cmdResults" style="max-height: 400px; overflow-y: auto; padding: 8px;"></div>
        </div>
    </div>

    @push('scripts')
    <script>
        const searchableUploads = @json($uploads->map(fn($u) => ['id' => $u->id, 'name' => $u->original_filename, 'rows' => $u->row_count]));
        const trendPointsData = @json($trendPoints);
        const trendDatesData = @json(collect(range(29, 0))->map(fn($d) => now()->subDays($d)->format('M j'))->values());
        const avgReadinessVal = {{ $avgReadiness ?? 'null' }};

        document.addEventListener('DOMContentLoaded', () => {
            // ===== Best Performer Ring =====
            const bestRing = document.getElementById('bestRing');
            const bestScore = document.getElementById('bestScore');
            if (bestRing && bestScore) {
                const targetOffset = bestRing.dataset.target;
                const targetScore = parseInt(bestScore.dataset.target);
                setTimeout(() => { bestRing.style.strokeDashoffset = targetOffset; }, 200);
                let c = 0;
                const start = performance.now();
                function tick(now) {
                    const p = Math.min((now - start) / 1400, 1);
                    c = Math.round((1 - Math.pow(1 - p, 3)) * targetScore);
                    bestScore.textContent = c;
                    if (p < 1) requestAnimationFrame(tick);
                }
                requestAnimationFrame(tick);
            }

            // ===== TREND CHART: FULL INTERACTIVITY =====
            const trendSvg = document.getElementById('trendSvg');
            const trendWrap = document.getElementById('trendWrap');
            const trendTip = document.getElementById('trendTip');
            const tipDate = document.getElementById('tipDate');
            const tipValue = document.getElementById('tipValue');
            const tipDelta = document.getElementById('tipDelta');
            const crosshair = document.getElementById('crosshair');
            const trendLiveText = document.getElementById('trendLiveText');
            const trendLiveValue = document.getElementById('trendLiveValue');
            const trendXAxis = document.getElementById('trendXAxis');

            if (trendSvg && trendWrap) {
                const viewBox = trendSvg.viewBox.baseVal;
                let pinnedIndex = null;
                let activeIndex = null;

                const allPts = [];
                document.querySelectorAll('.trend-pt').forEach(g => {
                    const dot = g.querySelector('.pt-dot');
                    const idx = parseInt(g.dataset.index);
                    allPts.push({
                        index: idx,
                        el: g,
                        dot: dot,
                        pulse: g.querySelector('.pt-pulse'),
                        x: parseFloat(dot.getAttribute('cx')),
                        y: parseFloat(dot.getAttribute('cy')),
                        value: trendPointsData[idx],
                    });
                });

                function findNearest(svgX) {
                    if (allPts.length === 0) return null;
                    let nearest = allPts[0], minD = Math.abs(allPts[0].x - svgX);
                    allPts.forEach(p => {
                        const d = Math.abs(p.x - svgX);
                        if (d < minD) { minD = d; nearest = p; }
                    });
                    return nearest;
                }

                function renderPoint(p, isPinned) {
                    if (!p || p.value === null) return;
                    const rect = trendSvg.getBoundingClientRect();
                    const sx = rect.width / viewBox.width;
                    const sy = rect.height / viewBox.height;
                    const left = p.x * sx;
                    const top = p.y * sy;

                    trendTip.style.left = left + 'px';
                    trendTip.style.top = top + 'px';
                    trendTip.style.display = 'block';
                    tipDate.textContent = trendDatesData[p.index];
                    tipValue.textContent = p.value;
                    tipDelta.textContent = isPinned ? '📌 pinned' : 'readiness';
                    tipDelta.style.color = isPinned ? '#10b981' : '#5a5a5a';

                    crosshair.setAttribute('x1', p.x);
                    crosshair.setAttribute('x2', p.x);
                    crosshair.setAttribute('opacity', '1');

                    // Legend
                    trendLiveText.textContent = trendDatesData[p.index] + ': ' + p.value + '%';
                    trendLiveValue.style.backgroundColor = 'rgba(16, 185, 129, 0.2)';
                    trendLiveValue.style.borderColor = '#10b981';

                    // Reset visuals
                    allPts.forEach(ap => {
                        ap.dot.setAttribute('r', '5');
                        ap.dot.setAttribute('fill', '#131313');
                        ap.dot.setAttribute('stroke-width', '2.5');
                        ap.pulse.setAttribute('opacity', '0');
                    });

                    // Highlight active
                    p.dot.setAttribute('r', '8');
                    p.dot.setAttribute('fill', '#10b981');
                    p.dot.setAttribute('stroke-width', '3');
                    p.dot.style.filter = 'drop-shadow(0 0 12px #10b981)';

                    if (isPinned) {
                        p.pulse.setAttribute('opacity', '0.6');
                        p.pulse.setAttribute('r', '12');
                        p.pulse.style.animation = 'pinPulse 1.5s ease-in-out infinite';
                    }

                    activeIndex = p.index;
                }

                function clearPoint() {
                    if (pinnedIndex !== null) {
                        const pinned = allPts.find(p => p.index === pinnedIndex);
                        if (pinned) { renderPoint(pinned, true); return; }
                    }
                    trendTip.style.display = 'none';
                    crosshair.setAttribute('opacity', '0');
                    trendLiveText.textContent = 'Now: ' + (avgReadinessVal ?? '—') + '%';
                    trendLiveValue.style.backgroundColor = 'rgba(16, 185, 129, 0.1)';
                    trendLiveValue.style.borderColor = 'rgba(16, 185, 129, 0.3)';
                    allPts.forEach(ap => {
                        ap.dot.setAttribute('r', '5');
                        ap.dot.setAttribute('fill', '#131313');
                        ap.dot.setAttribute('stroke-width', '2.5');
                        ap.dot.style.filter = 'none';
                        ap.pulse.setAttribute('opacity', '0');
                    });
                    activeIndex = null;
                }

                function handleMove(clientX) {
                    const rect = trendSvg.getBoundingClientRect();
                    const svgX = ((clientX - rect.left) / rect.width) * viewBox.width;
                    const nearest = findNearest(svgX);
                    if (nearest) renderPoint(nearest, false);
                }

                trendSvg.addEventListener('mousemove', e => handleMove(e.clientX));
                trendSvg.addEventListener('mouseleave', () => clearPoint());

                trendSvg.addEventListener('touchstart', e => { handleMove(e.touches[0].clientX); e.preventDefault(); }, { passive: false });
                trendSvg.addEventListener('touchmove', e => { handleMove(e.touches[0].clientX); e.preventDefault(); }, { passive: false });
                trendSvg.addEventListener('touchend', () => clearPoint());

                // Click to pin
                document.querySelectorAll('.trend-pt').forEach(g => {
                    g.addEventListener('click', e => {
                        e.stopPropagation();
                        const idx = parseInt(g.dataset.index);
                        if (pinnedIndex === idx) {
                            pinnedIndex = null;
                            clearPoint();
                        } else {
                            pinnedIndex = idx;
                            const p = allPts.find(x => x.index === idx);
                            if (p) renderPoint(p, true);
                        }
                    });
                });

                // ESC to unpin
                document.addEventListener('keydown', e => {
                    if (e.key === 'Escape' && pinnedIndex !== null) {
                        pinnedIndex = null;
                        clearPoint();
                    }
                });

                // RANGE BUTTONS — filter visible days
                document.querySelectorAll('.range-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        document.querySelectorAll('.range-btn').forEach(b => b.classList.remove('active'));
                        btn.classList.add('active');
                        const days = parseInt(btn.dataset.days);
                        const cutoff = 30 - days;

                        allPts.forEach(p => {
                            if (p.index >= cutoff) {
                                p.el.style.opacity = '1';
                                p.el.style.pointerEvents = 'auto';
                            } else {
                                p.el.style.opacity = '0.15';
                                p.el.style.pointerEvents = 'none';
                            }
                        });
                    });
                });
            }

            // ===== DONUT CHART =====
            const donutValue = document.getElementById('donutValue');
            const donutLabel = document.getElementById('donutLabel');
            const totalFiles = {{ $pipelineTotal }};
            let selectedDonut = null;

            document.querySelectorAll('.donut-legend').forEach(legend => {
                legend.addEventListener('click', () => {
                    const label = legend.dataset.label;
                    const count = legend.querySelector('span:last-child').textContent;
                    const seg = document.querySelector(`.donut-segment[data-label="${label}"]`);

                    if (selectedDonut === label) {
                        selectedDonut = null;
                        donutValue.textContent = totalFiles;
                        donutLabel.textContent = 'Files';
                        document.querySelectorAll('.donut-segment').forEach(s => { s.style.opacity = '1'; s.style.transform = 'scale(1)'; });
                        document.querySelectorAll('.donut-legend').forEach(l => { l.style.opacity = '1'; l.style.backgroundColor = 'transparent'; });
                    } else {
                        selectedDonut = label;
                        donutValue.textContent = count;
                        donutLabel.textContent = label;
                        document.querySelectorAll('.donut-segment').forEach(s => {
                            if (s.dataset.label === label) { s.style.opacity = '1'; s.style.transform = 'scale(1.05)'; s.style.filter = 'drop-shadow(0 0 12px currentColor)'; }
                            else { s.style.opacity = '0.15'; s.style.transform = 'scale(1)'; s.style.filter = 'none'; }
                        });
                        document.querySelectorAll('.donut-legend').forEach(l => {
                            if (l.dataset.label === label) { l.style.opacity = '1'; l.style.backgroundColor = 'rgba(16, 185, 129, 0.1)'; }
                            else { l.style.opacity = '0.4'; l.style.backgroundColor = 'transparent'; }
                        });
                    }
                });

                legend.addEventListener('mouseenter', () => {
                    if (selectedDonut) return;
                    const label = legend.dataset.label;
                    document.querySelectorAll('.donut-segment').forEach(s => {
                        s.style.opacity = s.dataset.label === label ? '1' : '0.3';
                    });
                });
                legend.addEventListener('mouseleave', () => {
                    if (selectedDonut) return;
                    document.querySelectorAll('.donut-segment').forEach(s => { s.style.opacity = '1'; });
                });
            });

            document.querySelectorAll('.donut-segment').forEach(seg => {
                seg.addEventListener('mouseenter', () => {
                    if (selectedDonut) return;
                    donutValue.textContent = seg.dataset.pct + '%';
                    donutLabel.textContent = seg.dataset.label;
                    document.querySelectorAll('.donut-segment').forEach(s => {
                        s.style.opacity = s === seg ? '1' : '0.3';
                    });
                });
                seg.addEventListener('mouseleave', () => {
                    if (selectedDonut) return;
                    donutValue.textContent = totalFiles;
                    donutLabel.textContent = 'Files';
                    document.querySelectorAll('.donut-segment').forEach(s => { s.style.opacity = '1'; });
                });
            });

            // ===== WEEKLY BARS =====
            const weeklyDetail = document.getElementById('weeklyDetail');
            document.querySelectorAll('.weekly-col').forEach(col => {
                const tip = col.querySelector('.weekly-tip');
                col.addEventListener('mouseenter', () => {
                    tip.style.opacity = '1';
                    col.querySelector('.weekly-bar').style.filter = 'brightness(1.3)';
                    col.querySelector('.weekly-bar').style.transform = 'scaleY(1.05)';
                    weeklyDetail.innerHTML = `<div style="display: flex; align-items: center; justify-content: center; gap: 12px; flex-wrap: wrap;">
        <span style="color: #f5f5f5; font-weight: 700; font-size: 13px;">${col.dataset.day}</span>
        <span style="color: #2a2a2a; font-size: 14px;">|</span>
        <span style="color: ${col.dataset.count === '0' ? '#5a5a5a' : '#10b981'}; font-weight: 700; font-size: 13px;">
            ${col.dataset.count} ${col.dataset.count === '1' ? 'file' : 'files'}
        </span>
        <span style="color: #2a2a2a; font-size: 14px;">|</span>
        <span style="color: #8a8a8a; font-size: 12px;">${col.dataset.date}</span>
    </div>`;
                });
                col.addEventListener('mouseleave', () => {
                    tip.style.opacity = '0';
                    col.querySelector('.weekly-bar').style.filter = 'none';
                    col.querySelector('.weekly-bar').style.transform = 'scaleY(1)';
                });
            });

            // ===== READINESS BUCKETS =====
            const bucketDetail = document.getElementById('bucketDetail');
            let selectedBucket = null;
            document.querySelectorAll('.readiness-row').forEach(row => {
                row.addEventListener('click', () => {
                    const bucket = row.dataset.bucket;
                    if (selectedBucket === bucket) {
                        selectedBucket = null;
                        document.querySelectorAll('.readiness-row').forEach(r => { r.style.borderColor = 'transparent'; r.style.backgroundColor = 'transparent'; });
                        bucketDetail.innerHTML = '<span style="color: #5a5a5a;">Click a level to see details</span>';
                    } else {
                        selectedBucket = bucket;
                        document.querySelectorAll('.readiness-row').forEach(r => {
                            if (r === row) { r.style.borderColor = row.dataset.color; r.style.backgroundColor = row.dataset.color + '10'; }
                            else { r.style.borderColor = 'transparent'; r.style.backgroundColor = 'transparent'; }
                        });
                        bucketDetail.innerHTML = `<span style="color: ${row.dataset.color}; font-weight: 700;">${row.dataset.label}</span> <span style="color: #5a5a5a;">—</span> <span style="color: #f5f5f5; font-weight: 700;">${row.dataset.count} ${row.dataset.count === '1' ? 'file' : 'files'}</span>`;
                    }
                });
            });

            // ===== COMMAND PALETTE =====
            const cmdPalette = document.getElementById('cmdPalette');
            const cmdInput = document.getElementById('cmdInput');
            const cmdResults = document.getElementById('cmdResults');
            const cmdOverlay = document.getElementById('cmdOverlay');
            let cmdIndex = 0, cmdFiltered = [];

            const commands = [
                { type: 'action', label: 'New Upload', desc: 'Start a fresh CSV upload', url: '{{ route("uploads.create") }}', icon: 'plus' },
                { type: 'action', label: 'Browse Uploads', desc: 'View all your files', url: '{{ route("uploads.index") }}', icon: 'list' },
                @if($bestUpload)
                { type: 'action', label: 'View Top Report', desc: '{{ $bestUpload->original_filename }}', url: '{{ route("readiness.show", $bestUpload) }}', icon: 'chart' },
                @endif
                { type: 'action', label: 'Profile Settings', desc: 'Manage your account', url: '{{ route("profile.edit") }}', icon: 'user' },
                { type: 'action', label: 'Export Dashboard', desc: 'Download as PDF', action: 'export', icon: 'download' },
            ];
            const uploads = searchableUploads.map(u => ({ type: 'upload', label: u.name, desc: u.rows + ' rows', url: '{{ url("/uploads") }}/' + u.id, icon: 'file' }));

            function openCmd() { cmdPalette.style.display = 'flex'; cmdInput.value = ''; cmdInput.focus(); renderCmd(''); }
            function closeCmd() { cmdPalette.style.display = 'none'; }

            function renderCmd(q) {
                q = q.toLowerCase();
                let items = q === '' ? [...commands, ...uploads.slice(0, 5)] : [...commands, ...uploads].filter(i => i.label.toLowerCase().includes(q) || (i.desc && i.desc.toLowerCase().includes(q)));
                cmdFiltered = items.slice(0, 10);
                cmdIndex = 0;

                if (cmdFiltered.length === 0) {
                    cmdResults.innerHTML = '<div style="padding: 40px; text-align: center; color: #5a5a5a; font-size: 13px;">No results</div>';
                    return;
                }

                const iconMap = {
                    plus: '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
                    list: '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>',
                    chart: '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
                    user: '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
                    download: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
                    file: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>',
                };

                cmdResults.innerHTML = cmdFiltered.map((item, i) => `
                    <div class="cmd-result" data-index="${i}" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 10px; cursor: pointer; ${i === 0 ? 'background-color: rgba(16, 185, 129, 0.1);' : ''}">
                        <div style="width: 32px; height: 32px; border-radius: 8px; background-color: ${item.type === 'action' ? 'rgba(16, 185, 129, 0.1)' : '#262626'}; border: 1px solid ${item.type === 'action' ? 'rgba(16, 185, 129, 0.3)' : '#2a2a2a'}; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="${item.type === 'action' ? '#10b981' : '#8a8a8a'}" stroke-width="2">${iconMap[item.icon]}</svg>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-size: 13px; color: #f5f5f5; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${item.label}</div>
                            <div style="font-size: 11px; color: #8a8a8a;">${item.desc || ''}</div>
                        </div>
                    </div>
                `).join('');

                document.querySelectorAll('.cmd-result').forEach(el => {
                    el.addEventListener('click', () => selectCmd(parseInt(el.dataset.index)));
                });
            }

            function selectCmd(index) {
                const item = cmdFiltered[index];
                if (!item) return;
                if (item.action === 'export') window.print();
                else if (item.url) window.location.href = item.url;
                closeCmd();
            }

            function updateCmdSel() {
                document.querySelectorAll('.cmd-result').forEach((el, i) => {
                    el.style.backgroundColor = i === cmdIndex ? 'rgba(16, 185, 129, 0.1)' : 'transparent';
                });
            }

            cmdInput?.addEventListener('input', e => renderCmd(e.target.value));

            document.addEventListener('keydown', e => {
                if ((e.metaKey || e.ctrlKey) && e.key === 'k') { e.preventDefault(); openCmd(); }
                if (e.key === 'Escape' && cmdPalette.style.display !== 'none') closeCmd();
                if (cmdPalette.style.display !== 'none') {
                    if (e.key === 'ArrowDown') { e.preventDefault(); cmdIndex = Math.min(cmdIndex + 1, cmdFiltered.length - 1); updateCmdSel(); }
                    else if (e.key === 'ArrowUp') { e.preventDefault(); cmdIndex = Math.max(cmdIndex - 1, 0); updateCmdSel(); }
                    else if (e.key === 'Enter') { e.preventDefault(); selectCmd(cmdIndex); }
                }
            });

            cmdOverlay?.addEventListener('click', closeCmd);
        });
    </script>
    @endpush

    <style>
        @keyframes livePulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.3); }
        }

        @keyframes pinPulse {
            0%, 100% { opacity: 0.6; r: 12; }
            50% { opacity: 0.2; r: 16; }
        }

        .range-btn {
            padding: 5px 12px; background-color: transparent; border: none; border-radius: 6px;
            color: #8a8a8a; font-size: 11px; font-weight: 700; cursor: pointer;
            font-family: inherit; transition: all 0.15s;
        }
        .range-btn.active { background-color: #10b981; color: #000; }
        .range-btn:hover:not(.active) { color: #f5f5f5; }

        .trend-pt .pt-dot { transition: all 0.15s ease; }
        .trend-pt:hover .pt-dot { r: 7; fill: #10b981; stroke-width: 3; }

        .cmd-result:hover { background-color: rgba(38, 38, 38, 0.6) !important; }

        .dash-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 22px; border-radius: 999px; font-weight: 700; font-size: 13px; line-height: 1; text-decoration: none; cursor: pointer; transition: all 0.15s ease; font-family: inherit; height: 44px; box-sizing: border-box; }
        .dash-btn-primary { background-color: #10b981; color: #000; border: none; }
        .dash-btn-primary:hover { background-color: #34d399; transform: translateY(-1px); box-shadow: 0 6px 20px -6px rgba(16, 185, 129, 0.5); }
        .dash-btn-ghost { background-color: transparent; color: #8a8a8a; border: 1px solid #2a2a2a; }
        .dash-btn-ghost:hover { border-color: #10b981; color: #10b981; }
        .dash-btn svg { display: block; flex-shrink: 0; }

        .dash-card { background-color: #131313; border: 1px solid #222; border-radius: 20px; overflow: hidden; }
        .dash-card-header { padding: 18px 22px; border-bottom: 1px solid #222; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .dash-card-title { font-size: 14px; font-weight: 700; color: #f5f5f5; margin-bottom: 3px; }
        .dash-card-subtitle { font-size: 11px; color: #5a5a5a; }
        .dash-card-action { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #10b981; text-decoration: none; font-weight: 700; }
        .dash-card-action:hover { color: #34d399; }

        .dash-stat { background-color: #131313; border: 1px solid #222; border-radius: 16px; padding: 20px; transition: all 0.2s ease; display: block; }
        .dash-stat:hover { border-color: rgba(16, 185, 129, 0.3); transform: translateY(-2px); }
        .dash-stat-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
        .dash-stat-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 700; }
        .dash-stat-icon { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; border: 1px solid; }
        .dash-stat-value { font-size: 32px; font-weight: 800; color: #f5f5f5; line-height: 1; margin-bottom: 8px; letter-spacing: -0.02em; }
        .dash-stat-footer { display: flex; align-items: center; gap: 6px; font-size: 12px; }

        .donut-segment { transition: opacity 0.2s, transform 0.2s; }
        .donut-legend { transition: all 0.15s; }
        .donut-legend:hover { background-color: rgba(38, 38, 38, 0.5); }

        .weekly-col:hover .weekly-bar { filter: brightness(1.3); transform: scaleY(1.05); }

        .readiness-row:hover { background-color: rgba(38, 38, 38, 0.3); }
        .recent-row:hover { background-color: rgba(38, 38, 38, 0.4); }

        @media (max-width: 1100px) {
            .charts-grid { grid-template-columns: 1fr 1fr !important; }
            .main-grid { grid-template-columns: 1fr !important; }
        }
        @media (max-width: 800px) {
            .charts-grid { grid-template-columns: 1fr !important; }
            .stat-grid-lg { grid-template-columns: repeat(2, 1fr) !important; }
        }

        @media print {
            nav, .dash-btn, #cmdPalette { display: none !important; }
        }
    </style>
</x-app-layout>