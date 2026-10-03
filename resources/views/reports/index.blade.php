<x-app-layout>
    @php
        $scores = $allScored->pluck('score')->values();
        $sortedScores = $scores->sort()->values();
        $median = $sortedScores->count() > 0 ? $sortedScores->get(intval($sortedScores->count() / 2)) : 0;
        $bestScore = $scores->max() ?? 0;
        $worstScore = $scores->min() ?? 0;

        $getPercentile = function($score) use ($scores) {
            if ($scores->count() === 0) return 0;
            $below = $scores->filter(fn($s) => $s < $score)->count();
            return round(($below / $scores->count()) * 100);
        };

        $scoringDates = $allScored->pluck('scored_at')->map(fn($d) => $d->format('Y-m-d'))->unique()->sort()->values();
        $streak = 0;
        $checkDate = now()->format('Y-m-d');
        for ($i = 0; $i < 30; $i++) {
            if ($scoringDates->contains($checkDate)) {
                $streak++;
                $checkDate = \Carbon\Carbon::parse($checkDate)->subDay()->format('Y-m-d');
            } else break;
        }

        $readyCount = $buckets['excellent'] + $buckets['good'];
        $needsWork = $buckets['fair'] + $buckets['poor'];

        $getTag = function($score) {
            if ($score >= 90) return ['label' => 'Priority', 'color' => '#10b981'];
            if ($score >= 75) return ['label' => 'Approved', 'color' => '#34d399'];
            if ($score >= 50) return ['label' => 'Review', 'color' => '#f59e0b'];
            return ['label' => 'Blocked', 'color' => '#ef4444'];
        };

        // Score history data for interactive chart
        $historyPoints = $allScored->sortBy('scored_at')->values();
    @endphp

    {{-- Header --}}
    <div style="margin-bottom: 24px; display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 20px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.15em; color: #10b981; font-weight: 700; margin-bottom: 8px;">
                Reports
                @if($streak > 0)
                    <span class="streak-badge" style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; background-color: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 999px; font-size: 9px; font-weight: 700; letter-spacing: 0.05em; text-transform: none; color: #fbbf24;">
                        🔥 {{ $streak }} day{{ $streak > 1 ? 's' : '' }} streak
                    </span>
                @endif
            </div>
            <h1 style="font-size: 34px; font-weight: 800; color: #f5f5f5; margin: 0 0 6px 0; letter-spacing: -0.02em;">
                Readiness Leaderboard
            </h1>
            <p style="color: #8a8a8a; font-size: 14px; margin: 0;">
                Every scored upload, ranked by readiness
            </p>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="button" onclick="window.print()" class="rpt-btn rpt-btn-ghost">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                    <rect x="6" y="14" width="12" height="8"/>
                </svg>
                Export
            </button>
            <a href="{{ route('uploads.create') }}" class="rpt-btn rpt-btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                New Upload
            </a>
        </div>
    </div>

    @if($allScored->isEmpty())
        <div style="background: linear-gradient(135deg, #131313 0%, #0f1f1a 100%); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 24px; padding: 64px 32px; text-align: center;">
            <div style="width: 72px; height: 72px; margin: 0 auto 20px; border-radius: 20px; background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); display: flex; align-items: center; justify-content: center;">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
            </div>
            <h2 style="font-size: 22px; font-weight: 800; color: #f5f5f5; margin: 0 0 12px 0;">No scored uploads yet</h2>
            <p style="color: #8a8a8a; font-size: 14px; margin: 0 0 24px 0;">Score your first file to build a report.</p>
            <a href="{{ route('uploads.index') }}" class="rpt-btn rpt-btn-primary" style="display: inline-flex;">Go to Uploads →</a>
        </div>
    @else
        {{-- ============ INTERACTIVE SCORE HISTORY CHART ============ --}}
        <div style="background-color: #131313; border: 1px solid #222; border-radius: 20px; padding: 24px; margin-bottom: 20px; position: relative;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
                <div>
                    <div style="font-size: 14px; font-weight: 700; color: #f5f5f5; margin-bottom: 3px;">Score History</div>
                    <div style="font-size: 11px; color: #5a5a5a;">Hover, drag, or click points to explore</div>
                </div>
                <div style="display: flex; gap: 16px; font-size: 11px; color: #8a8a8a;">
                    <span>Median: <strong style="color: #f5f5f5;" data-count-to="{{ $median }}">0</strong>%</span>
                    <span>Best: <strong style="color: #10b981;" data-count-to="{{ $bestScore }}">0</strong>%</span>
                    <span>Worst: <strong style="color: #ef4444;" data-count-to="{{ $worstScore }}">0</strong>%</span>
                </div>
            </div>

            @if($historyPoints->count() > 1)
                @php
                    $chartW = 1000; $chartH = 160; $pad = 30;
                    $usableW = $chartW - $pad * 2; $usableH = $chartH - $pad * 2;
                    $pathD = '';
                    $pointData = [];
                    foreach ($historyPoints as $i => $p) {
                        $x = $pad + ($i / max($historyPoints->count() - 1, 1)) * $usableW;
                        $y = $pad + $usableH - ($p['score'] / 100) * $usableH;
                        $pathD .= ($i === 0 ? 'M ' : ' L ') . $x . ' ' . $y;
                        $pointData[] = [
                            'x' => $x, 'y' => $y, 'score' => $p['score'],
                            'name' => $p['upload']->original_filename,
                            'date' => $p['scored_at']->format('M j, Y'),
                            'id' => $p['upload']->id,
                        ];
                    }
                @endphp

                <div id="historyChartWrap" style="position: relative; user-select: none;">
                    <svg id="historyChart" viewBox="0 0 {{ $chartW }} {{ $chartH }}" preserveAspectRatio="none" style="width: 100%; height: 160px; display: block; cursor: crosshair;">
                        <defs>
                            <linearGradient id="historyGrad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#10b981" stop-opacity="0.35"/>
                                <stop offset="100%" stop-color="#10b981" stop-opacity="0"/>
                            </linearGradient>
                        </defs>
                        @for($i = 0; $i <= 4; $i++)
                            @php $y = $pad + ($i / 4) * $usableH; @endphp
                            <line x1="{{ $pad }}" y1="{{ $y }}" x2="{{ $chartW - $pad }}" y2="{{ $y }}" stroke="#1a1a1a" stroke-width="1"/>
                        @endfor

                        {{-- Crosshair --}}
                        <line id="historyCrosshair" x1="0" y1="{{ $pad }}" x2="0" y2="{{ $pad + $usableH }}" stroke="#10b981" stroke-width="1.5" stroke-dasharray="4 4" opacity="0" style="pointer-events: none;"/>

                        <path d="{{ $pathD }} L {{ $pad + $usableW }} {{ $pad + $usableH }} L {{ $pad }} {{ $pad + $usableH }} Z" fill="url(#historyGrad)"/>
                        <path d="{{ $pathD }}" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>

                        @foreach($pointData as $i => $pt)
                            <g class="history-point-group" data-index="{{ $i }}" style="cursor: pointer;">
                                <circle cx="{{ $pt['x'] }}" cy="{{ $pt['y'] }}" r="18" fill="transparent"/>
                                <circle class="history-dot" cx="{{ $pt['x'] }}" cy="{{ $pt['y'] }}" r="5" fill="#131313" stroke="#10b981" stroke-width="2" style="transition: all 0.15s;"/>
                            </g>
                        @endforeach
                    </svg>

                    {{-- Tooltip --}}
                    <div id="historyTooltip" style="position: absolute; display: none; background-color: #0a0a0a; border: 1px solid #10b981; border-radius: 12px; padding: 12px 16px; pointer-events: none; z-index: 20; transform: translate(-50%, calc(-100% - 14px)); box-shadow: 0 12px 40px -8px rgba(16,185,129,0.5); min-width: 160px;">
                        <div id="historyTooltipName" style="font-size: 12px; color: #f5f5f5; font-weight: 700; margin-bottom: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px;">—</div>
                        <div style="display: flex; align-items: baseline; gap: 8px;">
                            <div id="historyTooltipScore" style="font-size: 24px; font-weight: 800; color: #10b981; line-height: 1;">—</div>
                            <div style="font-size: 12px; color: #5a5a5a;">%</div>
                        </div>
                        <div id="historyTooltipDate" style="font-size: 10px; color: #8a8a8a; margin-top: 4px;">—</div>
                    </div>

                    {{-- Y axis --}}
                    <div style="position: absolute; top: 0; left: 0; height: 160px; display: flex; flex-direction: column; justify-content: space-between; padding: 30px 0; pointer-events: none;">
                        @for($i = 0; $i <= 4; $i++)
                            <span style="font-size: 9px; color: #3a3a3a; font-weight: 700;">{{ 100 - ($i * 25) }}%</span>
                        @endfor
                    </div>
                </div>
            @else
                <div style="padding: 40px; text-align: center; font-size: 12px; color: #5a5a5a;">Score more files to see the trend</div>
            @endif
        </div>

        {{-- ============ PODIUM (with count-up) ============ --}}
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px;" class="podium-grid">
            @foreach($allScored->take(3) as $i => $row)
                @php
                    $score = $row['score'];
                    $color = $score >= 90 ? '#10b981' : ($score >= 75 ? '#34d399' : ($score >= 50 ? '#f59e0b' : '#ef4444'));
                    $medals = ['🥇', '🥈', '🥉'];
                    $medal = $medals[$i] ?? '';
                    $tag = $getTag($score);
                    $pct = $getPercentile($score);
                    $dashArray = 2 * 3.14159 * 50;
                @endphp
                <a href="/uploads/{{ $row['upload']->id }}/readiness"
                   class="podium-card animate-in"
                   style="--stagger: {{ $i * 80 }}ms; position: relative; display: block; text-decoration: none; background-color: #131313; border: 1px solid {{ $color }}50; border-radius: 24px; padding: 32px 24px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); overflow: hidden;">
                    <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; border-radius: 50%; background-color: {{ $color }}; opacity: 0.1; filter: blur(50px); pointer-events: none;"></div>

                    <div style="position: absolute; top: 16px; left: 16px; display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 999px; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; background-color: {{ $tag['color'] }}20; color: {{ $tag['color'] }}; border: 1px solid {{ $tag['color'] }}40;">
                        {{ $tag['label'] }}
                    </div>

                    <div style="position: absolute; top: 16px; right: 16px; font-size: 10px; color: #5a5a5a; font-weight: 600;">
                        Top {{ 100 - $pct }}%
                    </div>

                    <div style="position: relative; display: flex; flex-direction: column; align-items: center; text-align: center; padding-top: 20px;">
                        <div style="font-size: 32px; margin-bottom: 8px;">{{ $medal }}</div>

                        <div style="position: relative; width: 120px; height: 120px; margin-bottom: 20px;">
                            <svg viewBox="0 0 120 120" style="width: 100%; height: 100%; transform: rotate(-90deg);">
                                <circle cx="60" cy="60" r="50" fill="none" stroke="#1f1f1f" stroke-width="8"/>
                                <circle class="score-ring" cx="60" cy="60" r="50" fill="none" stroke="{{ $color }}" stroke-width="8"
                                        stroke-dasharray="{{ $dashArray }}"
                                        stroke-dashoffset="{{ $dashArray }}"
                                        stroke-linecap="round"
                                        data-target="{{ $dashArray * (1 - $score / 100) }}"
                                        style="transition: stroke-dashoffset 1.5s cubic-bezier(0.4, 0, 0.2, 1);"/>
                            </svg>
                            <div style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                <div class="score-count" style="font-size: 30px; font-weight: 800; color: {{ $color }}; line-height: 1;" data-count-to="{{ $score }}">0</div>
                                <div style="font-size: 9px; color: #5a5a5a; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 700;">percent</div>
                            </div>
                        </div>

                        <div style="font-size: 14px; font-weight: 700; color: #f5f5f5; margin-bottom: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%;">
                            {{ $row['upload']->original_filename }}
                        </div>
                        <div style="font-size: 11px; color: #8a8a8a;">
                            {{ number_format($row['upload']->row_count) }} rows · {{ $row['scored_at']->diffForHumans() }}
                        </div>
                    </div>
                </a>
            @endforeach

            @for($i = $allScored->count(); $i < 3; $i++)
                <a href="{{ route('uploads.create') }}" class="podium-card-empty animate-in" style="--stagger: {{ $i * 80 }}ms; display: block; text-decoration: none; background-color: rgba(19, 19, 19, 0.5); border: 1px dashed #2a2a2a; border-radius: 24px; padding: 32px 24px; transition: all 0.25s;">
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 240px; text-align: center;">
                        <div style="width: 56px; height: 56px; border-radius: 14px; background-color: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2); display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                        </div>
                        <div style="font-size: 13px; font-weight: 700; color: #8a8a8a; margin-bottom: 4px;">Slot {{ $i + 1 }} is open</div>
                        <div style="font-size: 11px; color: #5a5a5a;">Upload to fill the podium</div>
                    </div>
                </a>
            @endfor
        </div>

        {{-- ============ FILTER BAR ============ --}}
        <div style="background-color: #131313; border: 1px solid #222; border-radius: 16px; padding: 16px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
            <div style="flex: 1; min-width: 220px; display: flex; align-items: center; gap: 10px; background-color: #0a0a0a; border: 1px solid #2a2a2a; border-radius: 999px; padding: 10px 16px; transition: border-color 0.2s;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#8a8a8a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" id="reportSearch" placeholder="Search reports... (press /)" style="flex: 1; background: transparent; border: none; color: #f5f5f5; font-size: 13px; outline: none; font-family: inherit;">
            </div>

            <div style="display: flex; gap: 4px; background-color: #0a0a0a; border: 1px solid #2a2a2a; border-radius: 999px; padding: 4px;">
                <button type="button" class="quick-chip active" data-filter="all">All</button>
                <button type="button" class="quick-chip" data-filter="ready" style="color: #10b981;">Ready ({{ $readyCount }})</button>
                <button type="button" class="quick-chip" data-filter="needs" style="color: #f59e0b;">Needs Work ({{ $needsWork }})</button>
            </div>

            <select id="reportSort" style="background-color: #0a0a0a; border: 1px solid #2a2a2a; color: #8a8a8a; border-radius: 999px; padding: 10px 16px; font-size: 12px; font-weight: 600; outline: none; cursor: pointer; font-family: inherit;">
                <option value="score-desc">Score ↓</option>
                <option value="score-asc">Score ↑</option>
                <option value="newest">Newest</option>
                <option value="oldest">Oldest</option>
                <option value="name">Name A-Z</option>
            </select>

            <button type="button" id="compareToggle" class="quick-chip" style="padding: 10px 16px;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px; vertical-align: middle;">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><line x1="21" y1="3" x2="14" y2="10"/>
                </svg>
                Compare
            </button>
        </div>

        {{-- Bulk bar --}}
        <div id="bulkBar" style="display: none; position: sticky; top: 80px; z-index: 20; margin-bottom: 16px; padding: 14px 20px; background: linear-gradient(135deg, #131313 0%, #0f1f1a 100%); border: 1px solid #10b981; border-radius: 16px; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 13px; color: #f5f5f5; font-weight: 600;"><span id="selectedCount">0</span> selected</span>
                <button type="button" id="clearSelection" style="background: none; border: none; color: #8a8a8a; font-size: 12px; cursor: pointer; text-decoration: underline; font-family: inherit;">Clear</button>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="rpt-btn rpt-btn-ghost" onclick="exportSelected()">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Export CSV
                </button>
            </div>
        </div>

        {{-- Distribution + Summary --}}
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;" class="dist-grid">
            <div style="background-color: #131313; border: 1px solid #222; border-radius: 20px; padding: 24px;">
                <div style="font-size: 14px; font-weight: 700; color: #f5f5f5; margin-bottom: 4px;">Score Distribution</div>
                <div style="font-size: 11px; color: #5a5a5a; margin-bottom: 20px;">Click a level to filter</div>

                @php
                    $total = max($allScored->count(), 1);
                    $bucketsList = [
                        ['key' => 'excellent', 'label' => 'Excellent', 'range' => '90+', 'count' => $buckets['excellent'], 'color' => '#10b981'],
                        ['key' => 'good', 'label' => 'Good', 'range' => '75-89', 'count' => $buckets['good'], 'color' => '#34d399'],
                        ['key' => 'fair', 'label' => 'Fair', 'range' => '50-74', 'count' => $buckets['fair'], 'color' => '#f59e0b'],
                        ['key' => 'poor', 'label' => 'Poor', 'range' => '<50', 'count' => $buckets['poor'], 'color' => '#ef4444'],
                    ];
                @endphp

                @foreach($bucketsList as $b)
                    @php $pct = ($b['count'] / $total) * 100; @endphp
                    <div class="bucket-row" data-bucket="{{ $b['key'] }}" style="margin-bottom: 16px; cursor: pointer; padding: 8px 10px; border-radius: 8px; transition: all 0.15s; position: relative;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="min-width: 44px; height: 22px; border-radius: 6px; background-color: {{ $b['color'] }}20; border: 1px solid {{ $b['color'] }}40; display: flex; align-items: center; justify-content: center;">
                                    <span style="font-size: 10px; color: {{ $b['color'] }}; font-weight: 800;">{{ $b['range'] }}</span>
                                </div>
                                <span style="font-size: 13px; color: #8a8a8a; font-weight: 600;">{{ $b['label'] }}</span>
                            </div>
                            <span style="font-size: 16px; font-weight: 800; color: {{ $b['color'] }};" data-count-to="{{ $b['count'] }}">0</span>
                        </div>
                        <div style="height: 6px; background-color: #1a1a1a; border-radius: 999px; overflow: hidden;">
                            <div class="bucket-bar" style="height: 100%; background: linear-gradient(90deg, {{ $b['color'] }}80, {{ $b['color'] }}); width: 0%; border-radius: 999px; transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);" data-target="{{ $pct }}"></div>
                        </div>
                    </div>
                @endforeach

                <div style="border-top: 1px solid #222; margin-top: 16px; padding-top: 16px; display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 12px; color: #8a8a8a;">Average Score</span>
                    <span style="font-size: 22px; font-weight: 800; color: {{ $avgScore >= 75 ? '#10b981' : ($avgScore >= 50 ? '#f59e0b' : '#ef4444') }};" data-count-to="{{ $avgScore }}">0</span><span style="font-size: 14px; color: #5a5a5a;">%</span>
                </div>
            </div>

            <div style="background-color: #131313; border: 1px solid #222; border-radius: 20px; padding: 24px;">
                <div style="font-size: 14px; font-weight: 700; color: #f5f5f5; margin-bottom: 4px;">Summary</div>
                <div style="font-size: 11px; color: #5a5a5a; margin-bottom: 20px;">Snapshot of your readiness data</div>

                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div class="summary-row" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background-color: #0a0a0a; border: 1px solid #222; border-radius: 12px;">
                        <span style="font-size: 12px; color: #8a8a8a;">Total Reports</span>
                        <span style="font-size: 18px; font-weight: 800; color: #f5f5f5;" data-count-to="{{ $allScored->count() }}">0</span>
                    </div>
                    <div class="summary-row" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background-color: #0a0a0a; border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 12px;">
                        <span style="font-size: 12px; color: #10b981;">Ready to Import (75+)</span>
                        <span style="font-size: 18px; font-weight: 800; color: #10b981;" data-count-to="{{ $readyCount }}">0</span>
                    </div>
                    <div class="summary-row" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background-color: #0a0a0a; border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 12px;">
                        <span style="font-size: 12px; color: #f59e0b;">Needs Attention (&lt;75)</span>
                        <span style="font-size: 18px; font-weight: 800; color: #f59e0b;" data-count-to="{{ $needsWork }}">0</span>
                    </div>
                    <div class="summary-row" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background-color: #0a0a0a; border: 1px solid rgba(168, 85, 247, 0.3); border-radius: 12px;">
                        <span style="font-size: 12px; color: #c084fc;">Median Score</span>
                        <span style="font-size: 18px; font-weight: 800; color: #c084fc;"><span data-count-to="{{ $median }}">0</span>%</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Report cards --}}
        @if($allScored->count() > 3)
            <div style="margin-bottom: 16px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 700;">
                        All Reports ({{ $allScored->count() - 3 }} more)
                    </div>
                </div>
                <div id="reportGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 12px;">
                    @foreach($allScored->skip(3) as $idx => $row)
                        @php
                            $score = $row['score'];
                            $color = $score >= 90 ? '#10b981' : ($score >= 75 ? '#34d399' : ($score >= 50 ? '#f59e0b' : '#ef4444'));
                            $tag = $getTag($score);
                            $pct = $getPercentile($score);
                        @endphp
                        <div class="mini-report-wrapper animate-in" data-report-id="{{ $row['upload']->id }}" data-score="{{ $score }}" data-name="{{ strtolower($row['upload']->original_filename) }}" data-date="{{ $row['scored_at']->timestamp }}" style="--stagger: {{ $idx * 40 }}ms;">
                            <a href="/uploads/{{ $row['upload']->id }}/readiness" class="mini-report" style="display: flex; align-items: center; gap: 14px; padding: 16px; background-color: #131313; border: 1px solid #222; border-radius: 14px; text-decoration: none; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden;">
                                <div class="mini-report-glow" style="position: absolute; inset: 0; background: radial-gradient(circle at var(--mouse-x, 50%) var(--mouse-y, 50%), {{ $color }}15, transparent 40%); opacity: 0; transition: opacity 0.3s; pointer-events: none;"></div>

                                <div class="bulk-checkbox" style="display: none; width: 20px; height: 20px; border-radius: 6px; border: 2px solid #2a2a2a; background-color: #0a0a0a; flex-shrink: 0; align-items: center; justify-content: center; transition: all 0.15s;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="3" style="display: none;"><polyline points="20 6 9 17 4 12"/></svg>
                                </div>
                                <div style="width: 56px; height: 56px; border-radius: 12px; background-color: {{ $color }}15; border: 1px solid {{ $color }}40; display: flex; flex-direction: column; align-items: center; justify-content: center; flex-shrink: 0; position: relative; z-index: 1;">
                                    <div style="font-size: 17px; font-weight: 800; color: {{ $color }}; line-height: 1;">{{ $score }}</div>
                                    <div style="font-size: 8px; color: #5a5a5a; font-weight: 700;">%</div>
                                </div>
                                <div style="flex: 1; min-width: 0; position: relative; z-index: 1;">
                                    <div style="font-size: 13px; font-weight: 600; color: #f5f5f5; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $row['upload']->original_filename }}</div>
                                    <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                                        <span style="font-size: 9px; padding: 2px 7px; border-radius: 999px; font-weight: 700; text-transform: uppercase; background-color: {{ $tag['color'] }}15; color: {{ $tag['color'] }}; border: 1px solid {{ $tag['color'] }}30;">{{ $tag['label'] }}</span>
                                        <span style="font-size: 10px; color: #5a5a5a;">Top {{ 100 - $pct }}%</span>
                                        <span style="font-size: 10px; color: #5a5a5a;">· {{ $row['scored_at']->diffForHumans() }}</span>
                                    </div>
                                </div>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#5a5a5a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; transition: all 0.2s; position: relative; z-index: 1;" class="arrow-icon">
                                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                                </svg>
                            </a>
                        </div>
                    @endforeach
                </div>
                <div id="noFilterResults" style="display: none; padding: 40px; text-align: center; background-color: #131313; border: 1px dashed #2a2a2a; border-radius: 16px; color: #5a5a5a; font-size: 13px;">
                    No reports match your filter.
                </div>
            </div>
        @endif
    @endif

    {{-- Toast container --}}
    <div id="toastContainer" style="position: fixed; bottom: 24px; right: 24px; z-index: 100; display: flex; flex-direction: column; gap: 8px;"></div>

    <style>
        /* Entrance animation */
        @keyframes slideUpFade {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-in {
            animation: slideUpFade 0.5s cubic-bezier(0.4, 0, 0.2, 1) both;
            animation-delay: var(--stagger, 0ms);
        }

        /* Ripple */
        @keyframes ripple {
            to { transform: scale(4); opacity: 0; }
        }
        .ripple {
            position: absolute; border-radius: 50%; background-color: rgba(16, 185, 129, 0.4);
            transform: scale(0); animation: ripple 0.6s linear;
            pointer-events: none; width: 20px; height: 20px; margin-left: -10px; margin-top: -10px;
        }

        /* Toast */
        .toast {
            background-color: #131313; border: 1px solid #10b981; color: #f5f5f5;
            padding: 12px 16px; border-radius: 12px; font-size: 13px; font-weight: 600;
            box-shadow: 0 10px 30px -8px rgba(16, 185, 129, 0.5);
            animation: slideInRight 0.3s ease;
            min-width: 200px;
        }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .rpt-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 11px 20px; border-radius: 999px; font-weight: 700; font-size: 12px; line-height: 1; text-decoration: none; cursor: pointer; transition: all 0.15s; font-family: inherit; border: 1px solid transparent; }
        .rpt-btn svg { display: block; flex-shrink: 0; }
        .rpt-btn-primary { background-color: #10b981; color: #000; border-color: #10b981; }
        .rpt-btn-primary:hover { background-color: #34d399; transform: translateY(-1px); box-shadow: 0 6px 20px -6px rgba(16, 185, 129, 0.5); }
        .rpt-btn-ghost { background-color: transparent; color: #8a8a8a; border-color: #2a2a2a; }
        .rpt-btn-ghost:hover { color: #10b981; border-color: rgba(16, 185, 129, 0.4); }

        .podium-card { --stagger: 0ms; }
        .podium-card:hover { transform: translateY(-6px); border-color: currentColor !important; box-shadow: 0 24px 48px -20px currentColor; }
        .podium-card:hover .score-ring { filter: drop-shadow(0 0 12px currentColor); }
        .podium-card-empty:hover { border-color: #10b981; background-color: rgba(16, 185, 129, 0.05); transform: translateY(-2px); }

        .mini-report:hover { border-color: rgba(16, 185, 129, 0.4); transform: translateY(-3px); box-shadow: 0 12px 30px -10px rgba(16, 185, 129, 0.2); }
        .mini-report:hover .mini-report-glow { opacity: 1; }
        .mini-report:hover .arrow-icon { transform: translateX(4px); stroke: #10b981; }

        .bucket-row:hover { background-color: rgba(38, 38, 38, 0.4); }
        .bucket-row:active { transform: scale(0.98); }

        .summary-row { transition: all 0.2s; }
        .summary-row:hover { border-color: rgba(16, 185, 129, 0.3); transform: translateX(2px); }

        .quick-chip { display: inline-flex; align-items: center; padding: 8px 14px; border-radius: 999px; font-size: 12px; font-weight: 600; border: none; cursor: pointer; background: transparent; color: #8a8a8a; transition: all 0.15s; font-family: inherit; }
        .quick-chip:hover { color: #f5f5f5; background-color: rgba(38, 38, 38, 0.6); }
        .quick-chip.active { background-color: #10b981; color: #000 !important; }

        .mini-report-wrapper.selected .mini-report { border-color: #10b981; background-color: rgba(16, 185, 129, 0.08); }
        .mini-report-wrapper.selected .bulk-checkbox { background-color: #10b981; border-color: #10b981; }
        .mini-report-wrapper.selected .bulk-checkbox svg { display: block !important; }

        #compareToggle.active { background-color: #c084fc; color: #000; }

        /* Smooth transitions for filter */
        .mini-report-wrapper { transition: opacity 0.3s ease, transform 0.3s ease; }
        .mini-report-wrapper.hidden { opacity: 0; transform: scale(0.95); pointer-events: none; }

        .history-dot { transition: all 0.15s ease; }
        .history-point-group:hover .history-dot { r: 8; fill: #10b981; filter: drop-shadow(0 0 8px #10b981); }

        kbd { background-color: #1a1a1a; border: 1px solid #2a2a2a; border-radius: 4px; padding: 2px 6px; font-family: monospace; font-size: 11px; color: #8a8a8a; }

        @media (max-width: 900px) {
            .podium-grid { grid-template-columns: 1fr !important; }
            .dist-grid { grid-template-columns: 1fr !important; }
        }
        @media print { nav, .rpt-btn, #bulkBar, .quick-chip { display: none !important; } }
    </style>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // ========== Count-up animation ==========
            document.querySelectorAll('[data-count-to]').forEach(el => {
                const target = parseInt(el.dataset.countTo);
                if (isNaN(target)) return;
                let current = 0;
                const start = performance.now();
                const duration = 1200;
                function tick(now) {
                    const p = Math.min((now - start) / duration, 1);
                    const eased = 1 - Math.pow(1 - p, 3);
                    current = Math.round(eased * target);
                    el.textContent = current;
                    if (p < 1) requestAnimationFrame(tick);
                }
                requestAnimationFrame(tick);
            });

            // ========== Score rings animate ==========
            setTimeout(() => {
                document.querySelectorAll('.score-ring').forEach(ring => {
                    ring.style.strokeDashoffset = ring.dataset.target;
                });
            }, 200);

            // ========== Bucket bars fill ==========
            setTimeout(() => {
                document.querySelectorAll('.bucket-bar').forEach(bar => {
                    bar.style.width = bar.dataset.target + '%';
                });
            }, 300);

            // ========== Interactive history chart ==========
            const chartWrap = document.getElementById('historyChartWrap');
            const chartSvg = document.getElementById('historyChart');
            const tooltip = document.getElementById('historyTooltip');
            const tooltipName = document.getElementById('historyTooltipName');
            const tooltipScore = document.getElementById('historyTooltipScore');
            const tooltipDate = document.getElementById('historyTooltipDate');
            const crosshair = document.getElementById('historyCrosshair');

            if (chartSvg && chartWrap && tooltip) {
                const viewBox = chartSvg.viewBox.baseVal;
                const points = @json($pointData ?? []);
                let pinnedIndex = null;

                function nearestPoint(svgX) {
                    let nearest = points[0], minD = Math.abs(points[0].x - svgX);
                    points.forEach(p => {
                        const d = Math.abs(p.x - svgX);
                        if (d < minD) { minD = d; nearest = p; }
                    });
                    return nearest;
                }

                function showPoint(p, pin) {
                    const rect = chartSvg.getBoundingClientRect();
                    const sx = rect.width / viewBox.width;
                    const sy = rect.height / viewBox.height;
                    tooltip.style.left = (p.x * sx) + 'px';
                    tooltip.style.top = (p.y * sy) + 'px';
                    tooltip.style.display = 'block';
                    tooltipName.textContent = p.name;
                    tooltipScore.textContent = p.score;
                    tooltipDate.textContent = p.date + (pin ? ' · 📌 pinned' : '');

                    crosshair.setAttribute('x1', p.x);
                    crosshair.setAttribute('x2', p.x);
                    crosshair.setAttribute('opacity', '1');

                    document.querySelectorAll('.history-dot').forEach(d => {
                        d.setAttribute('r', '5');
                        d.setAttribute('fill', '#131313');
                    });
                    const active = document.querySelector(`.history-point-group[data-index="${points.indexOf(p)}"] .history-dot`);
                    if (active) {
                        active.setAttribute('r', '8');
                        active.setAttribute('fill', '#10b981');
                    }
                }

                function hidePoint() {
                    if (pinnedIndex !== null) {
                        const p = points[pinnedIndex];
                        if (p) { showPoint(p, true); return; }
                    }
                    tooltip.style.display = 'none';
                    crosshair.setAttribute('opacity', '0');
                    document.querySelectorAll('.history-dot').forEach(d => {
                        d.setAttribute('r', '5');
                        d.setAttribute('fill', '#131313');
                    });
                }

                chartSvg.addEventListener('mousemove', (e) => {
                    const rect = chartSvg.getBoundingClientRect();
                    const svgX = ((e.clientX - rect.left) / rect.width) * viewBox.width;
                    const p = nearestPoint(svgX);
                    if (p) showPoint(p, false);
                });

                chartSvg.addEventListener('mouseleave', hidePoint);

                document.querySelectorAll('.history-point-group').forEach((g, i) => {
                    g.addEventListener('click', (e) => {
                        e.stopPropagation();
                        if (pinnedIndex === i) {
                            pinnedIndex = null;
                            hidePoint();
                            showToast('Unpinned');
                        } else {
                            pinnedIndex = i;
                            showPoint(points[i], true);
                            showToast('📌 ' + points[i].name + ' pinned');
                        }
                    });
                });

                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && pinnedIndex !== null) {
                        pinnedIndex = null;
                        hidePoint();
                    }
                });
            }

            // ========== Filters + Sort ==========
            const search = document.getElementById('reportSearch');
            const chips = document.querySelectorAll('.quick-chip[data-filter]');
            const cards = document.querySelectorAll('.mini-report-wrapper');
            const noResults = document.getElementById('noFilterResults');
            const sortSelect = document.getElementById('reportSort');
            const grid = document.getElementById('reportGrid');
            const bulkBar = document.getElementById('bulkBar');
            const selectedCountEl = document.getElementById('selectedCount');
            const clearSel = document.getElementById('clearSelection');
            const compareToggle = document.getElementById('compareToggle');
            let activeFilter = 'all';
            let compareMode = false;
            let selected = new Set();

            function applyFilters() {
                const q = (search?.value || '').toLowerCase();
                let visible = 0;
                cards.forEach(card => {
                    const name = card.dataset.name;
                    const score = parseInt(card.dataset.score);
                    let matches = name.includes(q);
                    if (activeFilter === 'ready') matches = matches && score >= 75;
                    else if (activeFilter === 'needs') matches = matches && score < 75;
                    if (matches) {
                        card.classList.remove('hidden');
                        setTimeout(() => { card.style.display = ''; }, 10);
                        visible++;
                    } else {
                        card.classList.add('hidden');
                        setTimeout(() => { card.style.display = 'none'; }, 250);
                    }
                });
                setTimeout(() => {
                    if (noResults) noResults.style.display = (visible === 0 && cards.length > 0) ? 'block' : 'none';
                }, 260);
            }

            function applySort() {
                const mode = sortSelect?.value || 'score-desc';
                const arr = Array.from(cards);
                arr.sort((a, b) => {
                    const aS = parseInt(a.dataset.score), bS = parseInt(b.dataset.score);
                    const aD = parseInt(a.dataset.date), bD = parseInt(b.dataset.date);
                    if (mode === 'score-desc') return bS - aS;
                    if (mode === 'score-asc') return aS - bS;
                    if (mode === 'newest') return bD - aD;
                    if (mode === 'oldest') return aD - bD;
                    if (mode === 'name') return a.dataset.name.localeCompare(b.dataset.name);
                    return 0;
                });
                arr.forEach(c => {
                    c.style.opacity = '0';
                    c.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        grid.appendChild(c);
                        setTimeout(() => { c.style.opacity = '1'; c.style.transform = 'scale(1)'; }, 20);
                    }, 100);
                });
            }

            function updateBulk() {
                selectedCountEl.textContent = selected.size;
                bulkBar.style.display = (selected.size > 0 || compareMode) ? 'flex' : 'none';
            }

            search?.addEventListener('input', applyFilters);
            sortSelect?.addEventListener('change', () => { showToast('Sorted by ' + sortSelect.options[sortSelect.selectedIndex].text); applySort(); });

            chips.forEach(chip => {
                chip.addEventListener('click', () => {
                    if (!chip.dataset.filter) return;
                    activeFilter = chip.dataset.filter;
                    chips.forEach(c => { if (c.dataset.filter) c.classList.remove('active'); });
                    chip.classList.add('active');
                    applyFilters();
                });
            });

            // Bucket click with ripple
            document.querySelectorAll('.bucket-row').forEach(row => {
                row.addEventListener('click', (e) => {
                    // Ripple
                    const ripple = document.createElement('span');
                    ripple.className = 'ripple';
                    const rect = row.getBoundingClientRect();
                    ripple.style.left = (e.clientX - rect.left) + 'px';
                    ripple.style.top = (e.clientY - rect.top) + 'px';
                    row.style.position = 'relative';
                    row.style.overflow = 'hidden';
                    row.appendChild(ripple);
                    setTimeout(() => ripple.remove(), 600);

                    const bucket = row.dataset.bucket;
                    activeFilter = (bucket === 'excellent' || bucket === 'good') ? 'ready' : 'needs';
                    chips.forEach(c => { if (c.dataset.filter) c.classList.toggle('active', c.dataset.filter === activeFilter); });
                    applyFilters();
                    showToast('Filtered: ' + bucket);
                });
            });

            compareToggle?.addEventListener('click', () => {
                compareMode = !compareMode;
                compareToggle.classList.toggle('active', compareMode);
                document.querySelectorAll('.bulk-checkbox').forEach(cb => {
                    cb.style.display = compareMode ? 'flex' : 'none';
                });
                if (!compareMode) {
                    selected.clear();
                    document.querySelectorAll('.mini-report-wrapper').forEach(w => w.classList.remove('selected'));
                }
                updateBulk();
                showToast(compareMode ? 'Compare mode ON' : 'Compare mode OFF');
            });

            cards.forEach(card => {
                // Hover glow follows mouse
                const report = card.querySelector('.mini-report');
                report?.addEventListener('mousemove', (e) => {
                    const rect = report.getBoundingClientRect();
                    report.style.setProperty('--mouse-x', ((e.clientX - rect.left) / rect.width * 100) + '%');
                    report.style.setProperty('--mouse-y', ((e.clientY - rect.top) / rect.height * 100) + '%');
                });

                card.addEventListener('click', (e) => {
                    if (!compareMode) return;
                    e.preventDefault();
                    card.classList.toggle('selected');
                    const id = card.dataset.reportId;
                    if (card.classList.contains('selected')) {
                        selected.add(id);
                        showToast('Selected (' + selected.size + ')');
                    } else {
                        selected.delete(id);
                    }
                    updateBulk();
                    if (selected.size === 2) {
                        const ids = Array.from(selected);
                        showToast('Opening compare view...');
                        setTimeout(() => {
                            window.location.href = '/reports?compare=' + ids[0] + ',' + ids[1];
                        }, 500);
                    }
                });
            });

            clearSel?.addEventListener('click', () => {
                selected.clear();
                document.querySelectorAll('.mini-report-wrapper').forEach(w => w.classList.remove('selected'));
                updateBulk();
                showToast('Selection cleared');
            });

            window.exportSelected = function() {
                if (selected.size === 0) return;
                showToast('Exporting ' + selected.size + ' reports...');
            };

            // ========== Keyboard shortcuts ==========
            document.addEventListener('keydown', (e) => {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT') return;
                if (e.key === '/') {
                    e.preventDefault();
                    search?.focus();
                } else if (e.key === 'c' || e.key === 'C') {
                    compareToggle?.click();
                }
            });

            // ========== Toast system ==========
            function showToast(msg) {
                const container = document.getElementById('toastContainer');
                const toast = document.createElement('div');
                toast.className = 'toast';
                toast.textContent = msg;
                container.appendChild(toast);
                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateX(30px)';
                    toast.style.transition = 'all 0.3s';
                    setTimeout(() => toast.remove(), 300);
                }, 2200);
            }
            window.showToast = showToast;

            // Welcome toast
            setTimeout(() => showToast('Tip: press / to search, C to compare'), 800);
        });
    </script>
    @endpush
</x-app-layout>