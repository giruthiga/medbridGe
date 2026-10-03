<x-app-layout>
    @php
        $trueStatus = 'uploaded';
        if ($upload->readinessReport) $trueStatus = 'scored';
        elseif ($upload->cleanedRecords->isNotEmpty()) $trueStatus = 'cleaned';
        elseif ($upload->matches->isNotEmpty()) $trueStatus = 'mapped';
        elseif ($upload->rawRows->isNotEmpty()) $trueStatus = 'parsed';

        $stages = [
            'uploaded' => ['label' => 'Uploaded', 'icon' => 'upload'],
            'parsed'   => ['label' => 'Parsed',   'icon' => 'parse'],
            'mapped'   => ['label' => 'Mapped',   'icon' => 'map'],
            'cleaned'  => ['label' => 'Cleaned',  'icon' => 'sparkle'],
            'scored'   => ['label' => 'Scored',   'icon' => 'chart'],
        ];
        $stageKeys = array_keys($stages);
        $currentIndex = array_search($trueStatus, $stageKeys);
        if ($currentIndex === false) $currentIndex = 0;

        $badgeStyle = match($trueStatus) {
            'scored'  => 'background-color: rgba(245, 158, 11, 0.12); color: #fbbf24; border-color: rgba(245, 158, 11, 0.4);',
            'cleaned' => 'background-color: rgba(6, 182, 212, 0.12); color: #22d3ee; border-color: rgba(6, 182, 212, 0.4);',
            'mapped'  => 'background-color: rgba(168, 85, 247, 0.12); color: #c084fc; border-color: rgba(168, 85, 247, 0.4);',
            'parsed'  => 'background-color: rgba(16, 185, 129, 0.12); color: #34d399; border-color: rgba(16, 185, 129, 0.4);',
            default   => 'background-color: rgba(59, 130, 246, 0.12); color: #60a5fa; border-color: rgba(59, 130, 246, 0.4);',
        };

        $readinessScore = $upload->readinessReport?->score;
        $recordsWithIssues = $upload->cleanedRecords->where('issues_count', '>', 0)->count();
        $totalChanges = 0;
        foreach ($upload->cleanedRecords as $cr) {
            $totalChanges += count($cr->changes ?? []);
        }

        // User's avg score
        $userAvgScore = 0;
        $userScored = \App\Models\Upload::where('user_id', auth()->id())->whereHas('readinessReport')->with('readinessReport')->get();
        if ($userScored->count() > 0) {
            $userAvgScore = round($userScored->avg(fn($u) => $u->readinessReport->score));
        }
        $scoreDelta = ($readinessScore && $userAvgScore) ? $readinessScore - $userAvgScore : null;

        $firstRow = $upload->rawRows()->first();

        $fieldQuality = [];
        if ($firstRow) {
            foreach (array_keys($firstRow->data) as $field) {
                $filled = 0;
                foreach ($upload->rawRows as $row) {
                    if (!empty($row->data[$field] ?? '')) $filled++;
                }
                $fieldQuality[$field] = [
                    'filled' => $filled,
                    'pct' => $upload->rawRows->count() > 0 ? round(($filled / $upload->row_count) * 100) : 0,
                ];
            }
        }

        $mappingUrl   = '/uploads/' . $upload->id . '/map';
        $cleaningUrl  = '/uploads/' . $upload->id . '/clean';
        $previewUrl   = '/uploads/' . $upload->id . '/cleaned';
        $readinessUrl = '/uploads/' . $upload->id . '/readiness';
        $scoreUrl     = '/uploads/' . $upload->id . '/score';
        $exportUrl    = '/uploads/' . $upload->id . '/export';

        $nextAction = null;
        if ($trueStatus === 'parsed' && $upload->matches->isEmpty()) {
            $nextAction = ['label' => 'Match Columns', 'url' => $mappingUrl, 'color' => '#10b981', 'reason' => 'Map your columns to the standard schema', 'method' => 'GET'];
        } elseif ($trueStatus === 'mapped' && $upload->cleanedRecords->isEmpty()) {
            $nextAction = ['label' => 'Clean Data', 'url' => $cleaningUrl, 'color' => '#f59e0b', 'reason' => 'Normalize phones, dates, and gender fields', 'method' => 'POST'];
        } elseif ($upload->cleanedRecords->isNotEmpty() && !$upload->readinessReport) {
            $nextAction = ['label' => 'Score Readiness', 'url' => $scoreUrl, 'color' => '#3b82f6', 'reason' => 'Calculate the overall readiness score', 'method' => 'POST'];
        } elseif ($upload->readinessReport) {
            $nextAction = ['label' => 'Export & Analyze Gaps', 'url' => $exportUrl, 'color' => '#c084fc', 'reason' => 'Your data is scored — export it or see what modules it can unlock', 'method' => 'GET'];
        }
    @endphp

    {{-- Header --}}
    <div style="margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 14px; min-width: 0; flex: 1;">
            <a href="{{ route('uploads.index') }}" class="back-btn" title="Back to uploads">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
            </a>
            <div style="min-width: 0;">
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #5a5a5a; font-weight: 600; margin-bottom: 4px;">Upload Detail</div>
                <h1 id="filename" style="font-size: 22px; font-weight: 800; color: #f5f5f5; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; cursor: pointer;" title="Click to copy">{{ $upload->original_filename }}</h1>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; font-size: 11px; border-radius: 999px; font-weight: 700; border: 1px solid; text-transform: uppercase; letter-spacing: 0.05em; {{ $badgeStyle }}">
                <span style="width: 6px; height: 6px; border-radius: 50%; background-color: currentColor; animation: pulseDot 2s ease-in-out infinite;"></span>
                {{ $trueStatus }}
            </span>
            <button type="button" class="icon-btn" onclick="copyFilename()" title="Copy filename">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg>
            </button>
            <button type="button" class="icon-btn" onclick="window.print()" title="Export as PDF">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                    <rect x="6" y="14" width="12" height="8"/>
                </svg>
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="flash flash-success">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="flash flash-error">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    {{-- Next Best Action --}}
    @if($nextAction)
        <div class="next-action" style="--accent-color: {{ $nextAction['color'] }};">
            <div style="position: absolute; top: -40px; right: -40px; width: 160px; height: 160px; border-radius: 50%; background-color: {{ $nextAction['color'] }}; opacity: 0.08; filter: blur(50px); pointer-events: none;"></div>
            <div style="position: relative; display: flex; align-items: center; gap: 16px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background-color: {{ $nextAction['color'] }}20; border: 1px solid {{ $nextAction['color'] }}50; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="{{ $nextAction['color'] }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                    </svg>
                </div>
                <div>
                    <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: {{ $nextAction['color'] }}; font-weight: 700; margin-bottom: 4px;">Next Best Action</div>
                    <div style="font-size: 14px; color: #f5f5f5; font-weight: 600;">{{ $nextAction['reason'] }}</div>
                </div>
            </div>
            @if($nextAction['method'] === 'GET')
                <a href="{{ $nextAction['url'] }}" class="next-action-btn" style="--btn-color: {{ $nextAction['color'] }};">
                    {{ $nextAction['label'] }}
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </a>
            @else
                <form action="{{ $nextAction['url'] }}" method="POST" style="position: relative; margin: 0;">
                    @csrf
                    <button type="submit" class="next-action-btn" style="--btn-color: {{ $nextAction['color'] }};">
                        {{ $nextAction['label'] }}
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </button>
                </form>
            @endif
        </div>
    @endif

    {{-- Pipeline --}}
    <div class="panel" style="margin-bottom: 20px;">
        <div class="panel-header">
            <h2 style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600; margin: 0;">Pipeline Progress</h2>
            <span style="font-size: 11px; color: #10b981; font-weight: 600; background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 999px; padding: 4px 12px;">
                Step {{ $currentIndex + 1 }} of {{ count($stages) }}
            </span>
        </div>
        <div style="padding: 28px 32px;">
            <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; position: relative;">
                <div style="position: absolute; top: 22px; left: 10%; right: 10%; height: 2px; background-color: #1f1f1f; z-index: 0;"></div>
                <div class="pipeline-progress" style="position: absolute; top: 22px; left: 10%; height: 2px; background-color: #10b981; z-index: 1; transition: width 1s cubic-bezier(0.4, 0, 0.2, 1); width: 0%;" data-target="{{ ($currentIndex / (count($stages) - 1)) * 80 }}"></div>
                @foreach($stages as $key => $stage)
                    @php
                        $stageIndex = array_search($key, $stageKeys);
                        $isDone = $stageIndex < $currentIndex;
                        $isCurrent = $stageIndex === $currentIndex;
                    @endphp
                    <div class="pipeline-step" style="display: flex; flex-direction: column; align-items: center; text-align: center; position: relative; z-index: 2;">
                        <div class="pipeline-icon" style="
                            width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 10px; background-color: #131313; border: 2px solid; transition: all 0.3s;
                            @if($isDone) border-color: #10b981; background-color: #10b981; color: #000;
                            @elseif($isCurrent) border-color: #10b981; color: #10b981; box-shadow: 0 0 0 5px rgba(16, 185, 129, 0.1);
                            @else border-color: #2a2a2a; color: #5a5a5a;
                            @endif
                        ">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                @if($isDone)
                                    <polyline points="20 6 9 17 4 12"/>
                                @elseif($stage['icon'] === 'upload')
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="17 8 12 3 7 8"/>
                                    <line x1="12" y1="3" x2="12" y2="15"/>
                                @elseif($stage['icon'] === 'parse')
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                @elseif($stage['icon'] === 'map')
                                    <line x1="4" y1="12" x2="20" y2="12"/>
                                    <polyline points="14 6 20 12 14 18"/>
                                @elseif($stage['icon'] === 'sparkle')
                                    <path d="M12 3l1.9 5.8L20 10l-5.8 1.9L12 18l-1.9-5.8L4 10l5.8-1.9z"/>
                                @elseif($stage['icon'] === 'chart')
                                    <line x1="18" y1="20" x2="18" y2="10"/>
                                    <line x1="12" y1="20" x2="12" y2="4"/>
                                    <line x1="6" y1="20" x2="6" y2="14"/>
                                @endif
                            </svg>
                        </div>
                        <div style="font-size: 12px; font-weight: 600; @if($isDone || $isCurrent) color: #f5f5f5; @else color: #5a5a5a; @endif">
                            {{ $stage['label'] }}
                        </div>
                        <div style="font-size: 9px; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 700; margin-top: 3px;
                            @if($isDone) color: #10b981;
                            @elseif($isCurrent) color: #10b981;
                            @else color: #3a3a3a;
                            @endif">
                            @if($isDone) Done
                            @elseif($isCurrent) Current
                            @else Pending
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#8a8a8a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <span class="stat-label">Status</span>
            </div>
            <span class="status-badge" style="{{ $badgeStyle }}">
                <span style="width: 5px; height: 5px; border-radius: 50%; background-color: currentColor;"></span>
                {{ $trueStatus }}
            </span>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#8a8a8a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/>
                    <line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
                </svg>
                <span class="stat-label">Rows Parsed</span>
            </div>
            <div class="stat-value" data-count-to="{{ $upload->row_count }}">0</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#8a8a8a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                </svg>
                <span class="stat-label">Matched Fields</span>
            </div>
            <div class="stat-value"><span data-count-to="{{ $upload->matches->count() }}">0</span><span style="font-size: 16px; color: #5a5a5a;">/8</span></div>
        </div>

        <div class="stat-card" style="{{ $readinessScore ? 'border-color: rgba(16, 185, 129, 0.4);' : '' }}">
            <div class="stat-header">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="{{ $readinessScore ? '#10b981' : '#8a8a8a' }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
                <span class="stat-label" style="{{ $readinessScore ? 'color: #10b981;' : '' }}">Readiness</span>
            </div>
            @if($readinessScore)
                <div class="stat-value" style="color: #10b981;">
                    <span data-count-to="{{ $readinessScore }}">0</span><span style="font-size: 16px; color: #5a5a5a;">%</span>
                </div>
                @if($scoreDelta !== null)
                    <div style="font-size: 11px; color: {{ $scoreDelta >= 0 ? '#10b981' : '#ef4444' }}; font-weight: 700; margin-top: 4px;">
                        {{ $scoreDelta >= 0 ? '↑' : '↓' }} {{ abs($scoreDelta) }}% vs your avg ({{ $userAvgScore }}%)
                    </div>
                @endif
            @else
                <div class="stat-value" style="color: #5a5a5a;">—</div>
            @endif
        </div>
    </div>

    {{-- Actions --}}
    <div class="actions-row">
        @if($upload->rawRows->isNotEmpty())
            <a href="{{ $mappingUrl }}" class="action-btn btn-primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="4" y1="12" x2="20" y2="12"/>
                    <polyline points="14 6 20 12 14 18"/>
                </svg>
                <span>{{ $upload->matches->isNotEmpty() ? 'Edit Matches' : 'Match Columns' }}</span>
            </a>
        @endif

        @if($upload->matches->isNotEmpty())
            <form action="{{ $cleaningUrl }}" method="POST" style="display: inline-flex; margin: 0;">
                @csrf
                <button type="submit" class="action-btn btn-warning">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3l1.9 5.8L20 10l-5.8 1.9L12 18l-1.9-5.8L4 10l5.8-1.9z"/>
                    </svg>
                    <span>Clean Data</span>
                </button>
            </form>
        @endif

        @if($upload->cleanedRecords->isNotEmpty())
            <a href="{{ $previewUrl }}" class="action-btn btn-ghost">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                <span>View Cleaning</span>
            </a>
        @endif

        @if($upload->cleanedRecords->isNotEmpty() && !$upload->readinessReport)
            <form action="{{ $scoreUrl }}" method="POST" style="display: inline-flex; margin: 0;">
                @csrf
                <button type="submit" class="action-btn btn-info">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                    <span>Score Readiness</span>
                </button>
            </form>
        @endif

        @if($upload->readinessReport)
            <a href="{{ $readinessUrl }}" class="action-btn btn-success">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
                <span>View Readiness ({{ $upload->readinessReport->score }}%)</span>
            </a>

            {{-- NEW: Export & Gap Analysis --}}
            <a href="{{ $exportUrl }}" class="action-btn" style="background-color: #c084fc; color: #000;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                <span>Export & Gap Analysis</span>
            </a>
        @endif
    </div>

    {{-- Data Insights --}}
    @if($upload->cleanedRecords->isNotEmpty())
        <div class="insights-row">
            <div class="insight-card">
                <div class="insight-icon" style="background-color: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.3);">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <div>
                    <div class="insight-label">Clean Records</div>
                    <div class="insight-value" style="color: #10b981;" data-count-to="{{ $upload->cleanedRecords->count() - $recordsWithIssues }}">0</div>
                </div>
            </div>
            <div class="insight-card">
                <div class="insight-icon" style="background-color: rgba(245, 158, 11, 0.1); border-color: rgba(245, 158, 11, 0.3);">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><path d="M12 3l1.9 5.8L20 10l-5.8 1.9L12 18l-1.9-5.8L4 10l5.8-1.9z"/></svg>
                </div>
                <div>
                    <div class="insight-label">Fields Fixed</div>
                    <div class="insight-value" style="color: #f59e0b;" data-count-to="{{ $totalChanges }}">0</div>
                </div>
            </div>
            <div class="insight-card">
                <div class="insight-icon" style="background-color: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.3);">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <div>
                    <div class="insight-label">Rows with Issues</div>
                    <div class="insight-value" style="color: #ef4444;" data-count-to="{{ $recordsWithIssues }}">0</div>
                </div>
            </div>
        </div>
    @endif

    {{-- Field Quality Analysis --}}
    @if(!empty($fieldQuality))
        <div class="panel" style="margin-bottom: 20px;">
            <div class="panel-header">
                <div>
                    <div style="font-size: 14px; font-weight: 700; color: #f5f5f5; margin-bottom: 3px;">Field Quality</div>
                    <div style="font-size: 11px; color: #5a5a5a;">How complete is each column across all rows</div>
                </div>
            </div>
            <div style="padding: 20px 24px;">
                @foreach($fieldQuality as $field => $quality)
                    <div class="field-quality-row">
                        <div style="width: 140px; flex-shrink: 0;">
                            <code style="font-size: 12px; color: #10b981; font-family: monospace; background-color: #0a0a0a; padding: 3px 8px; border-radius: 6px; border: 1px solid #222;">{{ $field }}</code>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="height: 6px; background-color: #1a1a1a; border-radius: 999px; overflow: hidden;">
                                <div class="quality-bar" style="height: 100%; width: 0%; border-radius: 999px; transition: width 1s cubic-bezier(0.4, 0, 0.2, 1); background: linear-gradient(90deg, {{ $quality['pct'] >= 90 ? '#10b98180, #10b981' : ($quality['pct'] >= 70 ? '#f59e0b80, #f59e0b' : '#ef444480, #ef4444') }});" data-target="{{ $quality['pct'] }}"></div>
                            </div>
                        </div>
                        <div style="width: 100px; text-align: right; font-size: 12px; font-weight: 700; color: {{ $quality['pct'] >= 90 ? '#10b981' : ($quality['pct'] >= 70 ? '#f59e0b' : '#ef4444') }};">
                            {{ $quality['pct'] }}% filled
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Parsed Rows Table --}}
    @if($upload->rawRows->isNotEmpty())
        <div class="panel" style="overflow: hidden;">
            <div class="panel-header">
                <div>
                    <div style="font-size: 14px; font-weight: 700; color: #f5f5f5; margin-bottom: 3px;">Parsed Rows</div>
                    <div style="font-size: 11px; color: #5a5a5a;">Showing first 20 of {{ number_format($upload->rawRows->count()) }} rows</div>
                </div>
                <span style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600; background-color: #262626; border: 1px solid #2a2a2a; border-radius: 999px; padding: 4px 12px;">Preview</span>
            </div>
            <div style="overflow-x: auto;">
                <table style="width: 100%; text-align: left; font-size: 14px; border-collapse: collapse;">
                    <thead style="background-color: rgba(10, 10, 10, 0.5); border-bottom: 1px solid #222;">
                        <tr>
                            <th style="padding: 14px 20px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600; width: 60px;">#</th>
                            @foreach(array_keys($firstRow->data) as $col)
                                <th style="padding: 14px 20px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600; white-space: nowrap;">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($upload->rawRows->take(20) as $row)
                            <tr class="data-row">
                                <td style="padding: 14px 20px; color: #5a5a5a; font-size: 12px; font-family: monospace;">{{ $row->row_number }}</td>
                                @foreach($row->data as $value)
                                    <td style="padding: 14px 20px; color: {{ empty($value) ? '#5a5a5a' : '#f5f5f5' }}; font-size: 13px;">{{ $value ?: '—' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($upload->rawRows->count() > 20)
                <div style="padding: 14px; font-size: 12px; color: #8a8a8a; background-color: rgba(10, 10, 10, 0.5); border-top: 1px solid #222; text-align: center;">
                    Showing 20 of {{ number_format($upload->rawRows->count()) }} rows.
                </div>
            @endif
        </div>
    @endif

    {{-- Toast container --}}
    <div id="toastContainer" style="position: fixed; bottom: 24px; right: 24px; z-index: 100; display: flex; flex-direction: column; gap: 8px;"></div>

    <style>
        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.3); }
        }

        @keyframes slideUpFade {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .back-btn {
            width: 36px; height: 36px; border-radius: 10px; border: 1px solid #2a2a2a;
            display: flex; align-items: center; justify-content: center;
            color: #8a8a8a; text-decoration: none; flex-shrink: 0;
            transition: all 0.15s;
        }
        .back-btn:hover { border-color: #10b981; color: #10b981; background-color: rgba(16, 185, 129, 0.05); }

        .icon-btn {
            width: 36px; height: 36px; border-radius: 10px; border: 1px solid #2a2a2a;
            background: transparent; color: #8a8a8a; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.15s;
        }
        .icon-btn:hover { border-color: #10b981; color: #10b981; background-color: rgba(16, 185, 129, 0.05); transform: translateY(-1px); }

        .flash {
            margin-bottom: 20px; padding: 14px 18px; border-radius: 14px;
            font-size: 13px; display: flex; align-items: center; gap: 12px;
            animation: slideUpFade 0.4s ease;
        }
        .flash-success { background-color: rgba(6, 78, 59, 0.4); border: 1px solid #10b981; color: #34d399; }
        .flash-error { background-color: rgba(69, 10, 10, 0.4); border: 1px solid #991b1b; color: #fca5a5; }

        .next-action {
            margin-bottom: 24px; padding: 20px 24px;
            background: linear-gradient(135deg, #131313 0%, #0f1f1a 100%);
            border: 1px solid var(--accent-color, #10b981);
            border-radius: 18px; display: flex; align-items: center;
            justify-content: space-between; flex-wrap: wrap; gap: 16px;
            position: relative; overflow: hidden;
            animation: slideUpFade 0.4s ease;
        }

        .next-action-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 12px 22px; border-radius: 999px; font-weight: 700; font-size: 13px;
            background-color: var(--btn-color); color: #000; text-decoration: none;
            border: none; cursor: pointer; font-family: inherit;
            transition: all 0.15s; flex-shrink: 0;
        }
        .next-action-btn:hover { transform: translateY(-1px); filter: brightness(1.15); }

        .panel { background-color: #131313; border: 1px solid #222; border-radius: 18px; }
        .panel-header { padding: 18px 24px; border-bottom: 1px solid #222; display: flex; align-items: center; justify-content: space-between; }

        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 24px; }
        .stat-card {
            background-color: #131313; border: 1px solid #222; border-radius: 14px;
            padding: 20px; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            animation: slideUpFade 0.5s ease;
        }
        .stat-card:hover {
            border-color: rgba(16, 185, 129, 0.3) !important;
            transform: translateY(-3px);
            box-shadow: 0 12px 24px -10px rgba(16, 185, 129, 0.15);
        }
        .stat-header { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
        .stat-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600; }
        .stat-value { font-size: 28px; font-weight: 800; color: #f5f5f5; line-height: 1; }

        .status-badge {
            display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px;
            font-size: 11px; border-radius: 999px; font-weight: 700; border: 1px solid;
            text-transform: uppercase; letter-spacing: 0.05em;
        }

        .actions-row {
            display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 24px; align-items: center;
        }

        .action-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 12px 22px; border-radius: 999px; font-weight: 700; font-size: 13px;
            line-height: 1; text-decoration: none; border: 1px solid transparent;
            cursor: pointer; transition: all 0.15s ease; font-family: inherit;
            height: 44px; box-sizing: border-box;
        }
        .action-btn svg { display: block; flex-shrink: 0; }
        .action-btn span { display: block; line-height: 1; white-space: nowrap; }
        .action-btn:hover { transform: translateY(-1px); }
        .btn-primary { background-color: #10b981; color: #000; }
        .btn-primary:hover { background-color: #34d399; box-shadow: 0 6px 20px -6px rgba(16, 185, 129, 0.5); }
        .btn-warning { background-color: #f59e0b; color: #000; }
        .btn-warning:hover { background-color: #fbbf24; box-shadow: 0 6px 20px -6px rgba(245, 158, 11, 0.5); }
        .btn-info { background-color: #3b82f6; color: #fff; }
        .btn-info:hover { background-color: #60a5fa; box-shadow: 0 6px 20px -6px rgba(59, 130, 246, 0.5); }
        .btn-success { background-color: rgba(16, 185, 129, 0.08); color: #10b981; border-color: rgba(16, 185, 129, 0.4); }
        .btn-success:hover { background-color: rgba(16, 185, 129, 0.15); border-color: #10b981; }
        .btn-ghost { background-color: transparent; color: #8a8a8a; border-color: #2a2a2a; }
        .btn-ghost:hover { color: #10b981; border-color: rgba(16, 185, 129, 0.4); }

        .insights-row {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 24px;
        }
        .insight-card {
            background-color: #131313; border: 1px solid #222; border-radius: 14px;
            padding: 18px; display: flex; align-items: center; gap: 14px;
            transition: all 0.2s;
        }
        .insight-card:hover { border-color: rgba(16, 185, 129, 0.3); transform: translateY(-2px); }
        .insight-icon {
            width: 36px; height: 36px; border-radius: 10px; display: flex;
            align-items: center; justify-content: center; border: 1px solid; flex-shrink: 0;
        }
        .insight-label {
            font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em;
            color: #8a8a8a; font-weight: 700; margin-bottom: 4px;
        }
        .insight-value { font-size: 22px; font-weight: 800; line-height: 1; }

        .field-quality-row {
            display: flex; align-items: center; gap: 16px; padding: 10px 0;
            border-bottom: 1px solid #1a1a1a;
        }
        .field-quality-row:last-child { border-bottom: none; }

        .data-row { border-bottom: 1px solid #222; transition: background-color 0.15s; }
        .data-row:hover { background-color: rgba(38, 38, 38, 0.4); }

        .toast {
            background-color: #131313; border: 1px solid #10b981; color: #f5f5f5;
            padding: 12px 16px; border-radius: 12px; font-size: 13px; font-weight: 600;
            box-shadow: 0 10px 30px -8px rgba(16, 185, 129, 0.5);
            animation: slideInRight 0.3s ease; min-width: 200px;
        }

        #filename:hover { color: #10b981; }

        @media (max-width: 900px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr) !important; }
            .insights-row { grid-template-columns: 1fr !important; }
        }
        @media print {
            nav, .action-btn, .next-action-btn, .icon-btn { display: none !important; }
        }
    </style>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // ===== Count-up =====
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

            // ===== Pipeline progress =====
            setTimeout(() => {
                const bar = document.querySelector('.pipeline-progress');
                if (bar) bar.style.width = bar.dataset.target + '%';
            }, 200);

            // ===== Quality bars =====
            setTimeout(() => {
                document.querySelectorAll('.quality-bar').forEach(bar => {
                    bar.style.width = bar.dataset.target + '%';
                });
            }, 400);

            // ===== Copy filename =====
            window.copyFilename = function() {
                const name = document.getElementById('filename').textContent.trim();
                navigator.clipboard.writeText(name);
                showToast('✓ Filename copied');
            };

            // ===== Toast system =====
            window.showToast = function(msg) {
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
            };

            // ===== Keyboard shortcuts =====
            document.addEventListener('keydown', (e) => {
                if (e.target.tagName === 'INPUT') return;
                if (e.key === 'c' || e.key === 'C') {
                    if (e.metaKey || e.ctrlKey) return;
                    window.copyFilename();
                }
                if (e.key === 'p' || e.key === 'P') {
                    if (e.metaKey || e.ctrlKey) return;
                    window.print();
                }
            });
        });
    </script>
    @endpush
</x-app-layout>