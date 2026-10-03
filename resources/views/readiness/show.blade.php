<x-app-layout>
    @php
        $score = $report->score;
        $scoreColor = match(true) {
            $score >= 90 => '#10b981',
            $score >= 75 => '#34d399',
            $score >= 50 => '#f59e0b',
            $score >= 25 => '#fb923c',
            default      => '#ef4444',
        };
        $scoreLabel = match(true) {
            $score >= 90 => 'Excellent',
            $score >= 75 => 'Good',
            $score >= 50 => 'Fair',
            $score >= 25 => 'Poor',
            default      => 'Critical',
        };
        $scoreEmoji = match(true) {
            $score >= 90 => '🎯',
            $score >= 75 => '👍',
            $score >= 50 => '⚠️',
            $score >= 25 => '🔧',
            default      => '🚨',
        };

        $bands = [
            ['label' => 'Critical',  'range' => '0–24',   'color' => '#ef4444', 'min' => 0,   'max' => 25,  'desc' => 'Not ready. Major issues need fixing before import.'],
            ['label' => 'Poor',      'range' => '25–49',  'color' => '#fb923c', 'min' => 25,  'max' => 50,  'desc' => 'Significant work needed. Expect import failures.'],
            ['label' => 'Fair',      'range' => '50–74',  'color' => '#f59e0b', 'min' => 50,  'max' => 75,  'desc' => 'Usable but risky. Manual review recommended.'],
            ['label' => 'Good',      'range' => '75–89',  'color' => '#34d399', 'min' => 75,  'max' => 90,  'desc' => 'Ready for import with minor fixes.'],
            ['label' => 'Excellent', 'range' => '90–100', 'color' => '#10b981', 'min' => 90,  'max' => 100, 'desc' => 'Production-ready. Import immediately.'],
        ];

        $recommendations = [];
        foreach ($report->breakdown as $key => $factor) {
            if ($factor['score'] < 90) {
                $suggestion = match($key) {
                    'coverage' => 'Map more standard fields to boost coverage.',
                    'validity' => 'Review rows with issues to improve validity.',
                    'quality'  => 'Original data had many issues — check cleaner rules.',
                    'required' => 'Map all required fields for import.',
                    default    => 'Improve this factor.',
                };
                $potential = match($key) {
                    'coverage' => round((100 - $factor['score']) * 0.30, 1),
                    'validity' => round((100 - $factor['score']) * 0.40, 1),
                    'quality'  => round((100 - $factor['score']) * 0.20, 1),
                    'required' => round((100 - $factor['score']) * 0.10, 1),
                    default    => 0,
                };
                $recommendations[] = [
                    'factor' => $factor['label'],
                    'key' => $key,
                    'message' => $suggestion,
                    'potential' => $potential,
                    'priority' => $potential > 5 ? 'high' : ($potential > 2 ? 'medium' : 'low'),
                ];
            }
        }
        usort($recommendations, fn($a, $b) => $b['potential'] <=> $a['potential']);
        $totalPotential = array_sum(array_column($recommendations, 'potential'));
        $potentialScore = min(100, $score + $totalPotential);

        $exportUrl = '/uploads/' . $upload->id . '/export';
    @endphp

    {{-- Header --}}
    <div style="margin-bottom: 32px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 24px;">
        <div style="min-width: 0; flex: 1;">
            <div style="display: flex; align-items: center; gap: 8px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600; margin-bottom: 12px;">
                <a href="{{ route('uploads.index') }}" style="color: #8a8a8a; text-decoration: none;">Uploads</a>
                <span>/</span>
                <a href="{{ route('uploads.show', $upload) }}" style="color: #8a8a8a; text-decoration: none;">{{ $upload->original_filename }}</a>
                <span>/</span>
                <span style="color: {{ $scoreColor }};">Readiness</span>
            </div>

            <h1 style="font-size: 32px; font-weight: 800; color: #f5f5f5; margin: 0 0 8px 0;">
                Readiness Report
            </h1>
            <p style="color: #8a8a8a; font-size: 14px; margin: 0;">
                {{ $report->summary }}
            </p>
        </div>

        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
            <button id="copyScoreBtn"
                    style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 999px; border: 1px solid rgba(16, 185, 129, 0.4); background-color: rgba(16, 185, 129, 0.1); color: #10b981; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;"
                    onmouseover="this.style.backgroundColor='rgba(16, 185, 129, 0.2)'"
                    onmouseout="this.style.backgroundColor='rgba(16, 185, 129, 0.1)'">
                <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                Copy Score
            </button>

            <button id="downloadBtn"
                    style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 999px; border: 1px solid #2a2a2a; color: #8a8a8a; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.2s;"
                    onmouseover="this.style.backgroundColor='#262626'; this.style.color='#f5f5f5'"
                    onmouseout="this.style.backgroundColor='transparent'; this.style.color='#8a8a8a'">
                <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Download JSON
            </button>

            {{-- NEW: Export & Gap Analysis --}}
            <a href="{{ $exportUrl }}"
               style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 999px; background-color: #c084fc; color: #000; font-size: 13px; font-weight: 700; text-decoration: none; transition: all 0.2s;"
               onmouseover="this.style.backgroundColor='#d8b4fe'; this.style.transform='translateY(-1px)';"
               onmouseout="this.style.backgroundColor='#c084fc'; this.style.transform='translateY(0)';">
                <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Export & Gaps
            </a>

            <a href="{{ route('uploads.show', $upload) }}"
               style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 999px; border: 1px solid #2a2a2a; color: #8a8a8a; text-decoration: none; font-size: 13px;">
                <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Back
            </a>
        </div>
    </div>

    {{-- Hero Score --}}
    <div style="background: linear-gradient(135deg, #131313 0%, #1a1a1a 100%); border: 1px solid {{ $scoreColor }}40; border-radius: 24px; padding: 48px; margin-bottom: 32px; position: relative; overflow: hidden;">
        <div style="position: absolute; top: -80px; right: -80px; width: 300px; height: 300px; border-radius: 50%; background-color: {{ $scoreColor }}; opacity: 0.15; filter: blur(80px);"></div>
        <div style="position: absolute; bottom: -100px; left: -100px; width: 250px; height: 250px; border-radius: 50%; background-color: {{ $scoreColor }}; opacity: 0.08; filter: blur(80px);"></div>

        <div style="position: relative; display: grid; grid-template-columns: auto 1fr; gap: 48px; align-items: center;" class="hero-grid">

            <div style="position: relative; width: 220px; height: 220px; flex-shrink: 0;">
                <svg viewBox="0 0 220 220" style="width: 100%; height: 100%; transform: rotate(-90deg);">
                    <circle cx="110" cy="110" r="92" fill="none" stroke="#1f1f1f" stroke-width="16"/>
                    <circle id="scoreRing" cx="110" cy="110" r="92" fill="none" stroke="{{ $scoreColor }}" stroke-width="16"
                            stroke-dasharray="{{ (2 * 3.14159 * 92) }}"
                            stroke-dashoffset="{{ (2 * 3.14159 * 92) }}"
                            stroke-linecap="round"
                            style="transition: stroke-dashoffset 1.5s cubic-bezier(0.4, 0, 0.2, 1);"
                            data-target="{{ (2 * 3.14159 * 92) * (1 - $score / 100) }}"/>
                </svg>
                <div style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <div id="scoreNumber" style="font-size: 64px; font-weight: 800; color: {{ $scoreColor }}; line-height: 1; letter-spacing: -0.03em;" data-target="{{ $score }}">0</div>
                    <div style="font-size: 14px; color: #8a8a8a; margin-top: 4px; font-weight: 600;">out of 100</div>
                </div>
            </div>

            <div style="position: relative;">
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600; margin-bottom: 12px;">
                    Overall Readiness
                </div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <span style="font-size: 24px;">{{ $scoreEmoji }}</span>
                    <span style="font-size: 44px; font-weight: 800; color: {{ $scoreColor }}; line-height: 1;">
                        {{ $scoreLabel }}
                    </span>
                </div>
                <p style="font-size: 15px; color: #8a8a8a; margin: 0 0 24px 0; max-width: 480px; line-height: 1.6;">
                    {{ $report->summary }}
                </p>

                <div style="max-width: 480px;">
                    <div style="display: flex; justify-content: space-between; font-size: 11px; color: #8a8a8a; margin-bottom: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em;">
                        <span>Percentile Rank</span>
                        <span style="color: {{ $scoreColor }};">Top {{ 100 - $score }}%</span>
                    </div>
                    <div style="height: 8px; background-color: #1f1f1f; border-radius: 999px; overflow: hidden; position: relative;">
                        <div style="height: 100%; width: {{ $score }}%; background: linear-gradient(90deg, {{ $scoreColor }}80, {{ $scoreColor }}); border-radius: 999px; transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1);"></div>
                        <div style="position: absolute; top: -3px; left: 62%; width: 2px; height: 14px; background-color: #8a8a8a; border-radius: 2px;"></div>
                    </div>
                    <div style="font-size: 11px; color: #5a5a5a; margin-top: 6px;">
                        Median upload: 62%
                    </div>
                </div>

                @if($score >= 90)
                    <div style="margin-top: 20px; padding: 14px 18px; background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 12px; display: inline-flex; align-items: center; gap: 10px; font-size: 13px; color: #10b981;">
                        🎉 Ready for production import — excellent work!
                    </div>
                @elseif($score < 75)
                    <div style="margin-top: 20px; padding: 14px 18px; background-color: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 12px; display: inline-flex; align-items: center; gap: 10px; font-size: 13px; color: #f59e0b;">
                        ⚠️ Below 75% threshold — see recommendations below.
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Score Bands Gauge --}}
    <div style="background-color: #131313; border: 1px solid #222; border-radius: 20px; padding: 28px; margin-bottom: 32px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
            <h2 style="font-size: 13px; font-weight: 700; color: #f5f5f5; margin: 0; text-transform: uppercase; letter-spacing: 0.1em;">
                Score Bands
            </h2>
            <span style="font-size: 11px; color: #8a8a8a;">Click a band for details</span>
        </div>

        <div style="display: flex; gap: 4px; height: 60px;">
            @foreach($bands as $band)
                @php
                    $isActive = $score >= $band['min'] && $score < $band['max'];
                    $isPassed = $score >= $band['max'];
                @endphp
                <div class="band-segment" data-band-index="{{ $loop->index }}"
                     style="flex: 1; position: relative; border-radius: {{ $loop->first ? '8px 0 0 8px' : ($loop->last ? '0 8px 8px 0' : '0') }}; background-color: {{ $band['color'] }}{{ $isActive ? '' : ($isPassed ? '60' : '15') }}; border: {{ $isActive ? '2px solid ' . $band['color'] : '1px solid ' . $band['color'] . '30' }}; transition: all 0.3s; display: flex; align-items: center; justify-content: center; flex-direction: column; overflow: hidden; cursor: pointer;"
                     onmouseover="this.style.transform='scale(1.05)'; this.style.zIndex='10';"
                     onmouseout="this.style.transform='scale(1)'; this.style.zIndex='1';">
                    @if($isActive)
                        <div style="position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.2) 50%, transparent 70%); animation: shine 3s infinite; pointer-events: none;"></div>
                    @endif
                    <div style="font-size: 11px; font-weight: 700; color: {{ $isActive || $isPassed ? '#000' : '#5a5a5a' }}; text-transform: uppercase; letter-spacing: 0.05em; position: relative;">
                        {{ $band['label'] }}
                    </div>
                    <div style="font-size: 10px; color: {{ $isActive || $isPassed ? '#00000090' : '#5a5a5a90' }}; margin-top: 2px; position: relative;">
                        {{ $band['range'] }}
                    </div>
                    @if($isActive)
                        <div style="font-size: 9px; color: #000; font-weight: 700; margin-top: 2px; position: relative;">← YOU</div>
                    @endif
                </div>
            @endforeach
        </div>

        <div id="bandDetail" style="margin-top: 20px; padding: 16px; background-color: #0a0a0a; border: 1px solid #222; border-radius: 12px; font-size: 13px; color: #8a8a8a; min-height: 60px; display: flex; align-items: center;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 10px; height: 10px; border-radius: 50%; background-color: {{ $scoreColor }};"></div>
                <span><strong style="color: {{ $scoreColor }};">{{ $scoreLabel }}</strong> — {{ collect($bands)->firstWhere('label', $scoreLabel)['desc'] }}</span>
            </div>
        </div>
    </div>

    {{-- Factor Breakdown + Radar --}}
    <div style="display: grid; grid-template-columns: 1fr 420px; gap: 24px; margin-bottom: 32px; align-items: stretch;" class="factor-layout">

        <div style="display: flex; flex-direction: column;">
            <h2 style="font-size: 16px; font-weight: 700; color: #f5f5f5; margin: 0 0 20px 0;">
                Score Breakdown
            </h2>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; flex: 1;" class="breakdown-grid">
                @foreach($report->breakdown as $key => $factor)
                    @php
                        $barColor = match(true) {
                            $factor['score'] >= 80 => '#10b981',
                            $factor['score'] >= 60 => '#f59e0b',
                            default                => '#ef4444',
                        };
                        $iconSvg = match($key) {
                            'coverage' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" />',
                            'validity' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />',
                            'quality' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />',
                            'required' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
                            default => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2z" />',
                        };
                    @endphp

                    <div class="factor-card" data-factor-key="{{ $key }}" data-factor-label="{{ $factor['label'] }}" data-factor-score="{{ $factor['score'] }}" data-factor-weight="{{ $factor['weight'] }}" data-factor-weighted="{{ $factor['weighted'] }}" data-factor-detail="{{ $factor['detail'] }}"
                         style="background-color: #131313; border: 1px solid #222; border-radius: 16px; padding: 24px; transition: all 0.3s; cursor: pointer; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 16px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 36px; height: 36px; border-radius: 10px; background-color: {{ $barColor }}20; border: 1px solid {{ $barColor }}50; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <svg style="width: 18px; height: 18px; color: {{ $barColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            {!! $iconSvg !!}
                                        </svg>
                                    </div>
                                    <div>
                                        <div style="font-size: 15px; font-weight: 700; color: #f5f5f5;">
                                            {{ $factor['label'] }}
                                        </div>
                                        <div style="font-size: 11px; color: #8a8a8a; margin-top: 2px;">
                                            Weight: {{ $factor['weight'] }}%
                                        </div>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 32px; font-weight: 800; color: {{ $barColor }}; line-height: 1;">
                                        {{ $factor['score'] }}
                                    </div>
                                    <div style="font-size: 11px; color: #8a8a8a; margin-top: 2px;">
                                        +{{ $factor['weighted'] }} pts
                                    </div>
                                </div>
                            </div>

                            <div style="height: 8px; background-color: #1f1f1f; border-radius: 999px; overflow: hidden; margin-bottom: 12px;">
                                <div style="height: 100%; background: linear-gradient(90deg, {{ $barColor }}80, {{ $barColor }}); width: 0%; border-radius: 999px; transition: width 1.2s cubic-bezier(0.4, 0, 0.2, 1);" data-bar-width="{{ $factor['score'] }}"></div>
                            </div>
                        </div>

                        <div style="font-size: 13px; color: #8a8a8a;">
                            {{ $factor['detail'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div style="display: flex; flex-direction: column;">
            <h2 style="font-size: 16px; font-weight: 700; color: #f5f5f5; margin: 0 0 20px 0;">
                Factor Radar
            </h2>

            <div style="background-color: #131313; border: 1px solid #222; border-radius: 16px; padding: 24px; flex: 1; display: flex; flex-direction: column;">
                @php
                    $radarData = [];
                    foreach ($report->breakdown as $key => $factor) {
                        $radarData[] = [
                            'key' => $key,
                            'label' => $factor['label'],
                            'value' => $factor['score'],
                            'detail' => $factor['detail'],
                        ];
                    }
                    $axisCount = count($radarData);
                    $angleStep = 360 / $axisCount;
                    $centerX = 150;
                    $centerY = 150;
                    $maxRadius = 110;
                @endphp

                <svg id="radarChart" viewBox="-80 -30 460 360" style="width: 100%; display: block; flex: 1;">
                    @for($i = 1; $i <= 4; $i++)
                        @php $r = $maxRadius * ($i / 4); @endphp
                        <circle cx="{{ $centerX }}" cy="{{ $centerY }}" r="{{ $r }}" fill="none" stroke="#2a2a2a" stroke-width="1"/>
                    @endfor

                    @foreach($radarData as $i => $axis)
                        @php
                            $angle = ($angleStep * $i - 90) * M_PI / 180;
                            $edgeX = $centerX + $maxRadius * cos($angle);
                            $edgeY = $centerY + $maxRadius * sin($angle);
                            $labelX = $centerX + ($maxRadius + 30) * cos($angle);
                            $labelY = $centerY + ($maxRadius + 30) * sin($angle);

                            $anchor = 'middle';
                            if (cos($angle) > 0.3) $anchor = 'start';
                            elseif (cos($angle) < -0.3) $anchor = 'end';
                        @endphp
                        <line x1="{{ $centerX }}" y1="{{ $centerY }}" x2="{{ $edgeX }}" y2="{{ $edgeY }}" stroke="#2a2a2a" stroke-width="1"/>

                        <text x="{{ $labelX }}" y="{{ $labelY - 4 }}" text-anchor="{{ $anchor }}" fill="#8a8a8a" font-size="10" font-weight="600" style="text-transform: uppercase; letter-spacing: 0.05em;">
                            {{ $axis['label'] }}
                        </text>
                        <text x="{{ $labelX }}" y="{{ $labelY + 10 }}" text-anchor="{{ $anchor }}" fill="{{ $scoreColor }}" font-size="13" font-weight="800">
                            {{ $axis['value'] }}
                        </text>
                    @endforeach

                    @php
                        $points = '';
                        foreach ($radarData as $i => $axis) {
                            $angle = ($angleStep * $i - 90) * M_PI / 180;
                            $r = $maxRadius * ($axis['value'] / 100);
                            $x = $centerX + $r * cos($angle);
                            $y = $centerY + $r * sin($angle);
                            $points .= "$x,$y ";
                        }
                    @endphp
                    <polygon points="{{ $points }}" fill="{{ $scoreColor }}25" stroke="{{ $scoreColor }}" stroke-width="2" stroke-linejoin="round"/>

                    @foreach($radarData as $i => $axis)
                        @php
                            $angle = ($angleStep * $i - 90) * M_PI / 180;
                            $r = $maxRadius * ($axis['value'] / 100);
                            $x = $centerX + $r * cos($angle);
                            $y = $centerY + $r * sin($angle);
                        @endphp
                        <circle cx="{{ $x }}" cy="{{ $y }}" r="6" fill="{{ $scoreColor }}" stroke="#131313" stroke-width="2"
                                style="cursor: pointer; transition: r 0.2s;"
                                class="radar-point"
                                data-key="{{ $axis['key'] }}"
                                data-label="{{ $axis['label'] }}"
                                data-value="{{ $axis['value'] }}"
                                data-detail="{{ $axis['detail'] }}"
                                onmouseover="this.setAttribute('r', '9')"
                                onmouseout="this.setAttribute('r', '6')"/>
                    @endforeach
                </svg>

                <div id="radarDetail" style="margin-top: 16px; padding: 12px; background-color: #0a0a0a; border: 1px solid #222; border-radius: 10px; font-size: 12px; color: #8a8a8a; text-align: center;">
                    Click any point on the radar for details
                </div>
            </div>
        </div>
    </div>

    {{-- What-If Simulator --}}
    @if(count($recommendations) > 0)
        <div style="background: linear-gradient(135deg, #131313 0%, #0f1f1a 100%); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 20px; padding: 28px; margin-bottom: 32px; position: relative; overflow: hidden;">
            <div style="position: absolute; top: -60px; right: -60px; width: 200px; height: 200px; border-radius: 50%; background-color: #10b981; opacity: 0.1; filter: blur(60px);"></div>

            <div style="position: relative;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 24px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <svg style="width: 20px; height: 20px; color: #10b981;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <h2 style="font-size: 14px; font-weight: 700; color: #f5f5f5; margin: 0; text-transform: uppercase; letter-spacing: 0.1em;">
                            What-If Simulator
                        </h2>
                    </div>
                    <button id="applyAllBtn"
                            style="padding: 8px 16px; border-radius: 999px; border: 1px solid rgba(16, 185, 129, 0.4); background-color: rgba(16, 185, 129, 0.1); color: #10b981; font-size: 12px; font-weight: 600; cursor: pointer;">
                        Toggle All
                    </button>
                </div>

                <div style="background-color: #0a0a0a; border: 1px solid #222; border-radius: 16px; padding: 24px; margin-bottom: 24px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 24px; margin-bottom: 20px;">
                        <div style="flex: 1; min-width: 140px;">
                            <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600; margin-bottom: 6px;">Current</div>
                            <div style="font-size: 40px; font-weight: 800; color: {{ $scoreColor }}; line-height: 1;">{{ $score }}%</div>
                            <div style="font-size: 11px; color: #5a5a5a; margin-top: 4px;">{{ $scoreLabel }}</div>
                        </div>

                        <div style="flex-shrink: 0;">
                            <svg style="width: 32px; height: 32px; color: #10b981;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </div>

                        <div style="flex: 1; min-width: 140px;">
                            <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #10b981; font-weight: 600; margin-bottom: 6px;">Potential</div>
                            <div id="potentialScore" style="font-size: 40px; font-weight: 800; color: #10b981; line-height: 1;" data-max="{{ round($potentialScore) }}">{{ round($potentialScore) }}%</div>
                            <div id="potentialDelta" style="font-size: 11px; color: #10b981; margin-top: 4px;">+{{ round($totalPotential, 1) }} points</div>
                        </div>
                    </div>

                    <div style="position: relative; height: 12px; background-color: #1f1f1f; border-radius: 999px; overflow: hidden;">
                        <div style="position: absolute; top: 0; left: 0; height: 100%; width: {{ $score }}%; background-color: {{ $scoreColor }}; border-radius: 999px 0 0 999px; transition: all 0.4s;"></div>
                        <div id="potentialBar" style="position: absolute; top: 0; left: {{ $score }}%; height: 100%; width: {{ $potentialScore - $score }}%; background: linear-gradient(90deg, #10b981, #34d399); transition: all 0.4s;"></div>
                    </div>

                    <div style="display: flex; justify-content: space-between; font-size: 10px; color: #5a5a5a; margin-top: 8px;">
                        <span>0</span>
                        <span>50</span>
                        <span>100</span>
                    </div>
                </div>

                <div style="padding-top: 20px; border-top: 1px solid #222;">
                    <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600; margin-bottom: 12px;">
                        Recommendations ({{ count($recommendations) }}) — toggle to see impact
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        @foreach($recommendations as $index => $rec)
                            @php
                                $priorityColor = match($rec['priority']) {
                                    'high' => '#ef4444',
                                    'medium' => '#f59e0b',
                                    default => '#8a8a8a',
                                };
                            @endphp
                            <div class="recommendation" data-potential="{{ $rec['potential'] }}"
                                 style="display: flex; align-items: center; gap: 14px; padding: 12px 16px; background-color: #0a0a0a; border: 1px solid #222; border-radius: 12px; cursor: pointer; transition: all 0.2s;">
                                <div style="width: 20px; height: 20px; border-radius: 5px; border: 2px solid #2a2a2a; background-color: #0a0a0a; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.2s;" class="rec-checkbox">
                                    <svg class="rec-check" style="width: 12px; height: 12px; display: none; color: #000;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span style="font-size: 10px; font-weight: 700; color: {{ $priorityColor }}; background-color: {{ $priorityColor }}20; border: 1px solid {{ $priorityColor }}40; border-radius: 999px; padding: 3px 10px; text-transform: uppercase; letter-spacing: 0.05em; flex-shrink: 0;">
                                    {{ $rec['priority'] }}
                                </span>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-size: 13px; color: #f5f5f5; font-weight: 600;">
                                        {{ $rec['factor'] }}
                                    </div>
                                    <div style="font-size: 12px; color: #8a8a8a;">
                                        {{ $rec['message'] }}
                                    </div>
                                </div>
                                <div style="text-align: right; flex-shrink: 0;">
                                    <div style="font-size: 16px; font-weight: 700; color: #10b981;">+{{ $rec['potential'] }}</div>
                                    <div style="font-size: 10px; color: #5a5a5a;">pts</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Data Summary --}}
    <div style="background-color: #131313; border: 1px solid #222; border-radius: 20px; padding: 28px; margin-bottom: 32px;">
        <h2 style="font-size: 14px; font-weight: 700; color: #f5f5f5; margin: 0 0 20px 0; text-transform: uppercase; letter-spacing: 0.1em;">
            Data Summary
        </h2>

        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;" class="summary-grid">
            <div class="summary-card" style="padding: 16px; background-color: #0a0a0a; border: 1px solid #222; border-radius: 12px; transition: all 0.2s;">
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600; margin-bottom: 8px;">Total Records</div>
                <div style="font-size: 26px; font-weight: 800; color: #f5f5f5;">{{ $report->details['total_records'] }}</div>
            </div>
            <div class="summary-card" style="padding: 16px; background-color: #0a0a0a; border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 12px; transition: all 0.2s;">
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #10b981; font-weight: 600; margin-bottom: 8px;">Valid Rows</div>
                <div style="font-size: 26px; font-weight: 800; color: #10b981;">{{ $report->details['valid_rows'] }}</div>
            </div>
            <div class="summary-card" style="padding: 16px; background-color: #0a0a0a; border: 1px solid #222; border-radius: 12px; transition: all 0.2s;">
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600; margin-bottom: 8px;">Matched Fields</div>
                <div style="font-size: 26px; font-weight: 800; color: #f5f5f5;">{{ $report->details['matched_fields'] }}<span style="font-size: 14px; color: #5a5a5a;">/8</span></div>
            </div>
            <div class="summary-card" style="padding: 16px; background-color: #0a0a0a; border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 12px; transition: all 0.2s;">
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #f59e0b; font-weight: 600; margin-bottom: 8px;">Fields Fixed</div>
                <div style="font-size: 26px; font-weight: 800; color: #f59e0b;">{{ $report->details['total_changes'] }}</div>
            </div>
        </div>
    </div>

    {{-- Bottom CTA: Export & Gaps --}}
    <div style="background: linear-gradient(135deg, #131313 0%, #1f0f2e 100%); border: 1px solid rgba(192, 132, 252, 0.3); border-radius: 20px; padding: 32px; text-align: center; position: relative; overflow: hidden;">
        <div style="position: absolute; top: -100px; right: -100px; width: 300px; height: 300px; border-radius: 50%; background-color: #c084fc; opacity: 0.1; filter: blur(80px);"></div>

        <div style="position: relative;">
            <div style="width: 64px; height: 64px; margin: 0 auto 16px; border-radius: 16px; background-color: rgba(192, 132, 252, 0.1); border: 1px solid rgba(192, 132, 252, 0.3); display: flex; align-items: center; justify-content: center;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#c084fc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
            </div>
            <h3 style="font-size: 22px; font-weight: 800; color: #f5f5f5; margin: 0 0 8px 0;">Ready to use your data?</h3>
            <p style="font-size: 14px; color: #8a8a8a; margin: 0 0 24px 0; max-width: 500px; margin-left: auto; margin-right: auto;">
                Export the cleaned data in multiple formats, or see which standard modules your data can power.
            </p>
            <a href="{{ $exportUrl }}"
               style="display: inline-flex; align-items: center; gap: 10px; background-color: #c084fc; color: #000; padding: 14px 28px; border-radius: 999px; font-weight: 800; font-size: 14px; text-decoration: none; box-shadow: 0 0 30px -8px rgba(192, 132, 252, 0.6); transition: all 0.2s;"
               onmouseover="this.style.backgroundColor='#d8b4fe'; this.style.transform='translateY(-2px)';"
               onmouseout="this.style.backgroundColor='#c084fc'; this.style.transform='translateY(0)';">
                Export & Gap Analysis
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </a>
        </div>
    </div>

    {{-- Factor Detail Modal --}}
    <div id="factorModal" style="display: none; position: fixed; inset: 0; z-index: 100; align-items: center; justify-content: center; padding: 16px;">
        <div id="factorModalOverlay" style="position: absolute; inset: 0; background-color: rgba(0,0,0,0.75); backdrop-filter: blur(6px);"></div>
        <div style="position: relative; background-color: #131313; border: 1px solid #2a2a2a; border-radius: 20px; padding: 32px; max-width: 520px; width: 100%; box-shadow: 0 20px 60px -10px rgba(0,0,0,0.8);">
            <button id="factorModalClose" style="position: absolute; top: 16px; right: 16px; width: 32px; height: 32px; border-radius: 8px; border: 1px solid #2a2a2a; background: transparent; color: #8a8a8a; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600; margin-bottom: 8px;">Factor Details</div>
            <h3 id="factorModalTitle" style="font-size: 24px; font-weight: 800; color: #f5f5f5; margin: 0 0 16px 0;">—</h3>

            <div style="display: flex; align-items: baseline; gap: 16px; margin-bottom: 20px;">
                <div id="factorModalScore" style="font-size: 48px; font-weight: 800; color: #10b981; line-height: 1;">—</div>
                <div style="font-size: 14px; color: #8a8a8a;">out of 100</div>
            </div>

            <div style="padding: 20px; background-color: #0a0a0a; border: 1px solid #222; border-radius: 12px;">
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600; margin-bottom: 10px;">How it's calculated</div>
                <div id="factorModalDetail" style="font-size: 14px; color: #8a8a8a; line-height: 1.7;">—</div>
                <div id="factorModalFormula" style="font-size: 13px; color: #f5f5f5; margin-top: 12px; padding-top: 12px; border-top: 1px solid #222;">—</div>
            </div>
        </div>
    </div>

    <style>
        @media (max-width: 1100px) {
            .factor-layout { grid-template-columns: 1fr !important; }
        }
        @media (max-width: 900px) {
            .hero-grid { grid-template-columns: 1fr !important; gap: 24px !important; text-align: center; }
            .breakdown-grid { grid-template-columns: 1fr !important; }
            .summary-grid { grid-template-columns: repeat(2, 1fr) !important; }
        }

        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%); }
            100% { transform: translateX(100%) translateY(100%); }
        }

        .factor-card:hover {
            border-color: rgba(16, 185, 129, 0.4) !important;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px -8px rgba(16, 185, 129, 0.2);
        }

        .summary-card:hover {
            transform: translateY(-2px);
            border-color: rgba(16, 185, 129, 0.4) !important;
        }

        .recommendation:hover {
            border-color: rgba(16, 185, 129, 0.4) !important;
        }
        .recommendation.active {
            border-color: rgba(16, 185, 129, 0.5) !important;
            background-color: rgba(16, 185, 129, 0.05);
        }
        .recommendation.active .rec-checkbox {
            background-color: #10b981 !important;
            border-color: #10b981 !important;
        }
        .recommendation.active .rec-check {
            display: block !important;
        }
    </style>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // ========== Animate score ring ==========
            const ring = document.getElementById('scoreRing');
            const scoreNum = document.getElementById('scoreNumber');
            if (ring && scoreNum) {
                const target = ring.dataset.target;
                const numTarget = parseInt(scoreNum.dataset.target);
                setTimeout(() => { ring.style.strokeDashoffset = target; }, 100);

                let current = 0;
                const duration = 1500;
                const start = performance.now();
                function tick(now) {
                    const elapsed = now - start;
                    const progress = Math.min(elapsed / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    current = Math.round(eased * numTarget);
                    scoreNum.textContent = current;
                    if (progress < 1) requestAnimationFrame(tick);
                }
                requestAnimationFrame(tick);
            }

            // ========== Animate factor bars ==========
            setTimeout(() => {
                document.querySelectorAll('[data-bar-width]').forEach(bar => {
                    bar.style.width = bar.dataset.barWidth + '%';
                });
            }, 200);

            // ========== Copy score ==========
            document.getElementById('copyScoreBtn')?.addEventListener('click', (e) => {
                const text = `medbridGe Readiness Score: {{ $score }}% — {{ $scoreLabel }}\n{{ $report->summary }}`;
                navigator.clipboard.writeText(text);
                const btn = e.currentTarget;
                const original = btn.innerHTML;
                btn.innerHTML = '✓ Copied!';
                setTimeout(() => { btn.innerHTML = original; }, 1500);
            });

            // ========== Download report ==========
            document.getElementById('downloadBtn')?.addEventListener('click', () => {
                const report = {
                    filename: '{{ $upload->original_filename }}',
                    score: {{ $score }},
                    label: '{{ $scoreLabel }}',
                    summary: '{{ $report->summary }}',
                    breakdown: @json($report->breakdown),
                    details: @json($report->details),
                    generated_at: new Date().toISOString(),
                };
                const blob = new Blob([JSON.stringify(report, null, 2)], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `readiness-{{ $upload->id }}.json`;
                a.click();
                URL.revokeObjectURL(url);
            });

            // ========== Keyboard shortcuts ==========
            document.addEventListener('keydown', (e) => {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
                if (e.key === 'c' || e.key === 'C') document.getElementById('copyScoreBtn')?.click();
                if (e.key === 'd' || e.key === 'D') document.getElementById('downloadBtn')?.click();
            });

            // ========== Band click ==========
            const bands = @json($bands);
            document.querySelectorAll('.band-segment').forEach(seg => {
                seg.addEventListener('click', () => {
                    const i = parseInt(seg.dataset.bandIndex);
                    const band = bands[i];
                    document.getElementById('bandDetail').innerHTML = `<div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 10px; height: 10px; border-radius: 50%; background-color: ${band.color};"></div>
                        <span><strong style="color: ${band.color};">${band.label} (${band.range})</strong> — ${band.desc}</span>
                    </div>`;
                });
            });

            // ========== Factor card click → modal ==========
            const modal = document.getElementById('factorModal');
            document.querySelectorAll('.factor-card').forEach(card => {
                card.addEventListener('click', () => {
                    const label = card.dataset.factorLabel;
                    const score = card.dataset.factorScore;
                    const weight = card.dataset.factorWeight;
                    const weighted = card.dataset.factorWeighted;
                    const detail = card.dataset.factorDetail;

                    document.getElementById('factorModalTitle').textContent = label;
                    document.getElementById('factorModalScore').textContent = score;
                    document.getElementById('factorModalDetail').textContent = detail;
                    document.getElementById('factorModalFormula').innerHTML =
                        `<strong>${score}%</strong> × ${weight}% weight = <strong>+${weighted} points</strong> to your total score`;

                    const color = score >= 80 ? '#10b981' : (score >= 60 ? '#f59e0b' : '#ef4444');
                    document.getElementById('factorModalScore').style.color = color;

                    modal.style.display = 'flex';
                });
            });

            document.getElementById('factorModalClose')?.addEventListener('click', () => modal.style.display = 'none');
            document.getElementById('factorModalOverlay')?.addEventListener('click', () => modal.style.display = 'none');
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') modal.style.display = 'none'; });

            // ========== Radar point click ==========
            document.querySelectorAll('.radar-point').forEach(point => {
                point.addEventListener('click', () => {
                    const label = point.dataset.label;
                    const value = point.dataset.value;
                    const detail = point.dataset.detail;
                    document.getElementById('radarDetail').innerHTML =
                        `<div style="text-align: left;">
                            <div style="font-size: 13px; color: #f5f5f5; font-weight: 700; margin-bottom: 4px;">${label} — ${value}%</div>
                            <div style="font-size: 12px; color: #8a8a8a;">${detail}</div>
                        </div>`;
                });
            });

            // ========== What-If Simulator ==========
            const baseScore = {{ $score }};
            const potentialEl = document.getElementById('potentialScore');
            const deltaEl = document.getElementById('potentialDelta');
            const potentialBar = document.getElementById('potentialBar');

            function updatePotential() {
                let added = 0;
                document.querySelectorAll('.recommendation.active').forEach(r => {
                    added += parseFloat(r.dataset.potential);
                });
                const newScore = Math.min(100, Math.round(baseScore + added));

                potentialEl.textContent = newScore + '%';
                potentialEl.style.color = newScore > baseScore ? '#10b981' : (newScore < baseScore ? '#ef4444' : '#8a8a8a');
                deltaEl.textContent = (added >= 0 ? '+' : '') + added.toFixed(1) + ' points';
                deltaEl.style.color = newScore > baseScore ? '#10b981' : (newScore < baseScore ? '#ef4444' : '#8a8a8a');

                potentialBar.style.width = Math.max(0, newScore - baseScore) + '%';
            }

            document.querySelectorAll('.recommendation').forEach(rec => {
                rec.addEventListener('click', () => {
                    rec.classList.toggle('active');
                    updatePotential();
                });
            });

            document.querySelectorAll('.recommendation').forEach(rec => rec.classList.add('active'));

            document.getElementById('applyAllBtn')?.addEventListener('click', () => {
                const recs = document.querySelectorAll('.recommendation');
                const allActive = Array.from(recs).every(r => r.classList.contains('active'));
                recs.forEach(r => {
                    if (allActive) r.classList.remove('active');
                    else r.classList.add('active');
                });
                updatePotential();
            });

            updatePotential();
        });
    </script>
    @endpush
</x-app-layout>