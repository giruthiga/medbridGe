<x-app-layout>
    {{-- Header --}}
    <div style="margin-bottom: 28px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <a href="/uploads/{{ $upload->id }}" class="back-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
            </a>
            <div>
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #5a5a5a; font-weight: 600; margin-bottom: 4px;">Export & Gap Analysis</div>
                <h1 style="font-size: 26px; font-weight: 800; color: #f5f5f5; margin: 0;">{{ $upload->original_filename }}</h1>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div style="margin-bottom: 20px; padding: 14px 18px; background-color: rgba(69, 10, 10, 0.4); border: 1px solid #991b1b; color: #fca5a5; border-radius: 14px; font-size: 13px;">
            {{ session('error') }}
        </div>
    @endif

    {{-- Top Summary --}}
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px;" class="export-stats">
        <div class="stat-card">
            <div class="stat-icon" style="background-color: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.3);">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="stat-label">Fields Matched</div>
            <div class="stat-value" style="color: #10b981;">{{ $gapAnalysis['matched_count'] }}<span style="font-size: 14px; color: #5a5a5a;">/{{ $gapAnalysis['total_fields'] }}</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background-color: rgba(245, 158, 11, 0.1); border-color: rgba(245, 158, 11, 0.3);">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div class="stat-label">Missing Fields</div>
            <div class="stat-value" style="color: #fbbf24;">{{ count($gapAnalysis['missing_fields']) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background-color: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.3);">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            </div>
            <div class="stat-label">Modules Ready</div>
            <div class="stat-value" style="color: #60a5fa;">{{ $gapAnalysis['ready_modules'] }}<span style="font-size: 14px; color: #5a5a5a;">/{{ $gapAnalysis['total_modules'] }}</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background-color: rgba(168, 85, 247, 0.1); border-color: rgba(168, 85, 247, 0.3);">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#c084fc" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            </div>
            <div class="stat-label">Coverage</div>
            <div class="stat-value" style="color: #c084fc;">{{ $gapAnalysis['coverage_pct'] }}<span style="font-size: 14px; color: #5a5a5a;">%</span></div>
        </div>
    </div>

    {{-- Two column layout --}}
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;" class="main-grid">

        {{-- Left: Modules --}}
        <div>
            <div style="background-color: #131313; border: 1px solid #222; border-radius: 20px; padding: 24px;">
                <div style="margin-bottom: 20px;">
                    <h2 style="font-size: 16px; font-weight: 700; color: #f5f5f5; margin: 0 0 4px 0;">Module Readiness</h2>
                    <p style="font-size: 12px; color: #8a8a8a; margin: 0;">Which standard modules your data can support right now</p>
                </div>

                @php
                    $moduleIcons = [
                        'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
                        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
                        'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>',
                        'dollar' => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
                        'pill' => '<path d="M10.5 20.5a7.5 7.5 0 0 1-10.6-10.6l10.6 10.6z"/><path d="M13.5 3.5a7.5 7.5 0 0 1 10.6 10.6L13.5 3.5z"/>',
                        'flask' => '<path d="M9 3v9l-5 8a2 2 0 0 0 1.8 3h14.4a2 2 0 0 0 1.8-3l-5-8V3"/><line x1="9" y1="3" x2="15" y2="3"/>',
                        'alert' => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
                        'drop' => '<path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>',
                    ];
                @endphp

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    @foreach($gapAnalysis['modules'] as $key => $module)
                        @php
                            $statusColor = match($module['status']) {
                                'ready' => '#10b981',
                                'partial' => '#f59e0b',
                                default => '#5a5a5a',
                            };
                            $statusLabel = match($module['status']) {
                                'ready' => 'Ready',
                                'partial' => 'Partial',
                                default => 'Locked',
                            };
                            $statusBg = match($module['status']) {
                                'ready' => 'rgba(16, 185, 129, 0.1)',
                                'partial' => 'rgba(245, 158, 11, 0.1)',
                                default => 'rgba(90, 90, 90, 0.1)',
                            };
                        @endphp
                        <div class="module-card" style="border-color: {{ $statusColor }}30; background-color: {{ $statusBg }}20;">
                            <div style="width: 44px; height: 44px; border-radius: 12px; background-color: {{ $statusBg }}; border: 1px solid {{ $statusColor }}40; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="{{ $statusColor }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    {!! $moduleIcons[$module['icon']] ?? $moduleIcons['file'] !!}
                                </svg>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                                    <div style="font-size: 14px; font-weight: 700; color: #f5f5f5;">{{ $module['label'] }}</div>
                                    <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; padding: 3px 9px; border-radius: 999px; background-color: {{ $statusBg }}; color: {{ $statusColor }}; border: 1px solid {{ $statusColor }}40;">
                                        {{ $statusLabel }}
                                    </span>
                                </div>
                                <div style="font-size: 12px; color: #8a8a8a; margin-bottom: 10px;">{{ $module['description'] }}</div>

                                @if($module['status'] !== 'ready')
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                        @foreach($module['missing_fields'] as $missing)
                                            <span style="font-size: 10px; font-weight: 600; padding: 3px 9px; border-radius: 999px; background-color: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);">
                                                Needs: {{ str_replace('_', ' ', $missing) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <div style="font-size: 11px; color: #10b981; font-weight: 700;">
                                        ✓ All required fields mapped
                                    </div>
                                @endif
                            </div>
                            <div style="text-align: right; flex-shrink: 0;">
                                <div style="font-size: 22px; font-weight: 800; color: {{ $statusColor }}; line-height: 1;">{{ $module['completion'] }}<span style="font-size: 12px;">%</span></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Recommendations --}}
            @if(!empty($gapAnalysis['recommendations']))
                <div style="background-color: #131313; border: 1px solid #222; border-radius: 20px; padding: 24px; margin-top: 20px;">
                    <h2 style="font-size: 16px; font-weight: 700; color: #f5f5f5; margin: 0 0 4px 0;">🎯 Priority Actions</h2>
                    <p style="font-size: 12px; color: #8a8a8a; margin: 0 0 20px 0;">Add these fields to unlock the most modules</p>

                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        @foreach($gapAnalysis['recommendations'] as $i => $rec)
                            <div style="display: flex; align-items: center; gap: 14px; padding: 14px 18px; background-color: #0a0a0a; border: 1px solid #222; border-radius: 12px;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; color: #10b981; flex-shrink: 0;">
                                    {{ $i + 1 }}
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-size: 13px; color: #f5f5f5; font-weight: 700;">Add "{{ $rec['label'] }}"</div>
                                    <div style="font-size: 11px; color: #8a8a8a;">Unlocks {{ $rec['impact'] }} additional module(s)</div>
                                </div>
                                <div style="font-size: 11px; font-weight: 700; color: #10b981;">+{{ $rec['impact'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Right: Export options --}}
        <div>
            <div style="background-color: #131313; border: 1px solid #222; border-radius: 20px; padding: 24px; position: sticky; top: 90px;">
                <h2 style="font-size: 16px; font-weight: 700; color: #f5f5f5; margin: 0 0 4px 0;">📤 Export Options</h2>
                <p style="font-size: 12px; color: #8a8a8a; margin: 0 0 20px 0;">Download your cleaned data</p>

                <div style="display: flex; flex-direction: column; gap: 10px;">

                    {{-- Standard CSV --}}
                    <a href="/uploads/{{ $upload->id }}/export/csv" class="export-option">
                        <div class="export-icon" style="background-color: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.3);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-size: 13px; font-weight: 700; color: #f5f5f5;">Cleaned CSV</div>
                            <div style="font-size: 11px; color: #5a5a5a;">Standard comma-separated format</div>
                        </div>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#5a5a5a" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>

                    {{-- MedPro CSV --}}
                    <a href="/uploads/{{ $upload->id }}/export/medpro" class="export-option">
                        <div class="export-icon" style="background-color: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.3);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-size: 13px; font-weight: 700; color: #f5f5f5;">MedPro-Ready CSV</div>
                            <div style="font-size: 11px; color: #5a5a5a;">Standard schema · ready to import</div>
                        </div>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#5a5a5a" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>

                    {{-- JSON --}}
                    <a href="/uploads/{{ $upload->id }}/export/json" class="export-option">
                        <div class="export-icon" style="background-color: rgba(245, 158, 11, 0.1); border-color: rgba(245, 158, 11, 0.3);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-size: 13px; font-weight: 700; color: #f5f5f5;">JSON Export</div>
                            <div style="font-size: 11px; color: #5a5a5a;">For API integrations</div>
                        </div>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#5a5a5a" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>

                    {{-- Text Report --}}
                    <a href="/uploads/{{ $upload->id }}/export/report" class="export-option">
                        <div class="export-icon" style="background-color: rgba(168, 85, 247, 0.1); border-color: rgba(168, 85, 247, 0.3);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c084fc" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-size: 13px; font-weight: 700; color: #f5f5f5;">Text Report</div>
                            <div style="font-size: 11px; color: #5a5a5a;">Summary + gap analysis</div>
                        </div>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#5a5a5a" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                </div>

                {{-- Preview card --}}
                @if($upload->cleanedRecords->isNotEmpty())
                    @php $sample = $upload->cleanedRecords->first(); @endphp
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #222;">
                        <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 700; margin-bottom: 12px;">Preview (first row)</div>
                        <div style="background-color: #0a0a0a; border: 1px solid #222; border-radius: 10px; padding: 12px; font-family: monospace; font-size: 11px; line-height: 1.6;">
                            @foreach($sample->cleaned_data as $key => $value)
                                <div><span style="color: #5a5a5a;">{{ $key }}:</span> <span style="color: #10b981;">{{ $value ?: '(empty)' }}</span></div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        .back-btn {
            width: 36px; height: 36px; border-radius: 10px; border: 1px solid #2a2a2a;
            display: flex; align-items: center; justify-content: center;
            color: #8a8a8a; text-decoration: none; flex-shrink: 0;
            transition: all 0.15s;
        }
        .back-btn:hover { border-color: #10b981; color: #10b981; }

        .stat-card {
            background-color: #131313; border: 1px solid #222; border-radius: 14px;
            padding: 20px; transition: all 0.2s;
        }
        .stat-card:hover { border-color: rgba(16, 185, 129, 0.3); transform: translateY(-2px); }
        .stat-icon {
            width: 32px; height: 32px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            border: 1px solid; margin-bottom: 12px;
        }
        .stat-label {
            font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em;
            color: #8a8a8a; font-weight: 700; margin-bottom: 6px;
        }
        .stat-value { font-size: 26px; font-weight: 800; line-height: 1; }

        .module-card {
            display: flex; align-items: flex-start; gap: 14px;
            padding: 16px; border: 1px solid; border-radius: 14px;
            transition: all 0.2s;
        }
        .module-card:hover { transform: translateX(4px); }

        .export-option {
            display: flex; align-items: center; gap: 14px;
            padding: 14px; background-color: #0a0a0a;
            border: 1px solid #222; border-radius: 12px;
            text-decoration: none; transition: all 0.2s;
        }
        .export-option:hover {
            border-color: rgba(16, 185, 129, 0.4);
            background-color: rgba(16, 185, 129, 0.05);
            transform: translateX(4px);
        }
        .export-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            border: 1px solid; flex-shrink: 0;
        }

        @media (max-width: 1100px) {
            .main-grid { grid-template-columns: 1fr !important; }
        }
        @media (max-width: 800px) {
            .export-stats { grid-template-columns: repeat(2, 1fr) !important; }
        }
    </style>
</x-app-layout>