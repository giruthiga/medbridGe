<x-app-layout>
    @php
        $changeTypes = [
            'phone' => 0, 'date' => 0, 'gender' => 0,
            'name' => 0, 'city' => 0, 'other' => 0,
        ];
        foreach ($records as $rec) {
            if (!empty($rec->changes)) {
                foreach ($rec->changes as $field => $change) {
                    if ($field === '__malformed') continue;
                    if (str_contains($field, 'phone') || str_contains($field, 'mobile') || str_contains($field, 'contact')) $changeTypes['phone']++;
                    elseif (str_contains($field, 'date') || str_contains($field, 'dob') || str_contains($field, 'birth')) $changeTypes['date']++;
                    elseif (str_contains($field, 'sex') || str_contains($field, 'gender')) $changeTypes['gender']++;
                    elseif (str_contains($field, 'name')) $changeTypes['name']++;
                    elseif (str_contains($field, 'city') || str_contains($field, 'address')) $changeTypes['city']++;
                    else $changeTypes['other']++;
                }
            }
        }
        $cleanCount = $stats['total'] - $stats['changed'];
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
                <span style="color: #10b981;">Cleaning Results</span>
            </div>

            <h1 style="font-size: 32px; font-weight: 800; color: #f5f5f5; margin: 0 0 8px 0;">
                Cleaning Results
            </h1>
            <p style="color: #8a8a8a; font-size: 14px; margin: 0;">
                See exactly what medbrid<span style="color: #10b981; font-weight: 700;">G</span>e fixed in your data.
            </p>
        </div>

        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
            <a href="{{ $exportUrl }}"
               style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 999px; background-color: #c084fc; color: #000; text-decoration: none; font-size: 13px; font-weight: 700;">
                <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Export & Gaps
            </a>

            <a href="{{ route('uploads.show', $upload) }}"
               style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 999px; border: 1px solid #2a2a2a; color: #8a8a8a; text-decoration: none; font-size: 13px; font-weight: 500;">
                <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Back
            </a>
        </div>
    </div>

    {{-- Stats Row --}}
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px;" class="stats-grid">
        <div class="stat-card" style="background-color: #131313; border: 1px solid #222; border-radius: 16px; padding: 20px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <svg style="width: 14px; height: 14px; color: #8a8a8a;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600;">Total Records</div>
            </div>
            <div style="font-size: 32px; font-weight: 800; color: #f5f5f5; line-height: 1;">{{ $stats['total'] }}</div>
        </div>

        <div class="stat-card" style="background-color: #131313; border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 16px; padding: 20px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <svg style="width: 14px; height: 14px; color: #10b981;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #10b981; font-weight: 600;">Fixed</div>
            </div>
            <div style="font-size: 32px; font-weight: 800; color: #10b981; line-height: 1;">{{ $stats['changed'] }}</div>
        </div>

        <div class="stat-card" style="background-color: #131313; border: 1px solid #222; border-radius: 16px; padding: 20px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <svg style="width: 14px; height: 14px; color: #f5f5f5;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600;">Clean</div>
            </div>
            <div style="font-size: 32px; font-weight: 800; color: #f5f5f5; line-height: 1;">{{ $cleanCount }}</div>
        </div>

        <div class="stat-card" style="background-color: #131313; border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 16px; padding: 20px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <svg style="width: 14px; height: 14px; color: #f59e0b;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #f59e0b; font-weight: 600;">Issues</div>
            </div>
            <div style="font-size: 32px; font-weight: 800; color: #f59e0b; line-height: 1;">{{ $stats['issues'] }}</div>
        </div>
    </div>

    {{-- Change Types Breakdown --}}
    @if(array_sum($changeTypes) > 0)
        <div style="background-color: #131313; border: 1px solid #222; border-radius: 16px; padding: 20px; margin-bottom: 24px;">
            <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600; margin-bottom: 14px;">
                Types of Fixes Applied
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                @php
                    $typeLabels = [
                        'phone'  => ['label' => 'Phone numbers', 'icon' => '📞'],
                        'date'   => ['label' => 'Dates', 'icon' => '📅'],
                        'gender' => ['label' => 'Gender', 'icon' => '⚥'],
                        'name'   => ['label' => 'Names', 'icon' => '👤'],
                        'city'   => ['label' => 'Cities', 'icon' => '🏙️'],
                        'other'  => ['label' => 'Other', 'icon' => '🔧'],
                    ];
                @endphp

                @foreach($changeTypes as $type => $count)
                    @if($count > 0)
                        <span style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; background-color: #0a0a0a; border: 1px solid #2a2a2a; border-radius: 999px; font-size: 12px; color: #f5f5f5;">
                            <span style="font-size: 14px;">{{ $typeLabels[$type]['icon'] }}</span>
                            <span style="font-weight: 600;">{{ $count }}</span>
                            <span style="color: #8a8a8a;">{{ $typeLabels[$type]['label'] }}</span>
                        </span>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- Filters --}}
    <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 20px;">
        <div style="flex: 1; min-width: 240px; display: flex; align-items: center; gap: 10px; background-color: #131313; border: 1px solid #2a2a2a; border-radius: 999px; padding: 10px 18px;">
            <svg style="width: 14px; height: 14px; color: #8a8a8a;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
            </svg>
            <input id="searchInput" type="text" placeholder="Search any value..."
                   style="flex: 1; background: transparent; border: none; color: #f5f5f5; font-size: 13px; outline: none;">
        </div>

        <div style="display: flex; gap: 4px; background-color: #131313; border: 1px solid #2a2a2a; border-radius: 999px; padding: 4px;">
            <button data-filter="all" class="filter-chip active" style="padding: 8px 16px; border-radius: 999px; font-size: 12px; font-weight: 600; border: none; cursor: pointer; background-color: #10b981; color: #000;">
                All ({{ $stats['total'] }})
            </button>
            <button data-filter="fixed" class="filter-chip" style="padding: 8px 16px; border-radius: 999px; font-size: 12px; font-weight: 600; border: none; cursor: pointer; background: transparent; color: #8a8a8a;">
                Fixed ({{ $stats['changed'] }})
            </button>
            <button data-filter="issues" class="filter-chip" style="padding: 8px 16px; border-radius: 999px; font-size: 12px; font-weight: 600; border: none; cursor: pointer; background: transparent; color: #8a8a8a;">
                Issues
            </button>
            <button data-filter="clean" class="filter-chip" style="padding: 8px 16px; border-radius: 999px; font-size: 12px; font-weight: 600; border: none; cursor: pointer; background: transparent; color: #8a8a8a;">
                Clean ({{ $cleanCount }})
            </button>
        </div>
    </div>

    {{-- Records --}}
    <div id="recordsList">
        @forelse($records as $record)
            @php
                $hasChanges = !empty($record->changes);
                $hasIssues = $record->issues_count > 0;
                $isMalformed = isset($record->changes['__malformed']);
                $filterState = $hasIssues ? 'issues' : ($hasChanges ? 'fixed' : 'clean');
                $searchText = strtolower(json_encode($record->original_data) . json_encode($record->cleaned_data));
            @endphp

            <div class="record-card" data-filter="{{ $filterState }}" data-search="{{ $searchText }}"
                 style="background-color: #131313; border: 1px solid {{ $isMalformed ? 'rgba(239, 68, 68, 0.4)' : '#222' }}; border-radius: 16px; margin-bottom: 12px; overflow: hidden; transition: border-color 0.2s;">

                {{-- Header --}}
                <div class="record-header" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; cursor: pointer;">
                    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                        <span style="font-size: 11px; font-weight: 700; color: #10b981; background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 999px; padding: 4px 12px;">
                            Row {{ $record->row_number }}
                        </span>

                        @if($isMalformed)
                            <span style="font-size: 11px; font-weight: 700; color: #ef4444; background-color: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.4); border-radius: 999px; padding: 4px 12px;">
                                ⚠️ Malformed
                            </span>
                        @endif

                        @if($hasIssues && !$isMalformed)
                            <span style="font-size: 11px; font-weight: 700; color: #f59e0b; background-color: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 999px; padding: 4px 12px;">
                                {{ $record->issues_count }} issue{{ $record->issues_count > 1 ? 's' : '' }}
                            </span>
                        @endif

                        @if($hasChanges)
                            @php $fixCount = count($record->changes) - ($isMalformed ? 1 : 0); @endphp
                            @if($fixCount > 0)
                                <span style="font-size: 11px; font-weight: 700; color: #10b981; background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 999px; padding: 4px 12px;">
                                    {{ $fixCount }} fix{{ $fixCount > 1 ? 'es' : '' }}
                                </span>
                            @endif
                        @elseif(!$hasIssues)
                            <span style="font-size: 11px; font-weight: 700; color: #10b981; background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 999px; padding: 4px 12px;">
                                ✓ Clean
                            </span>
                        @endif
                    </div>

                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button class="copy-btn" data-copy="{{ json_encode($record->cleaned_data) }}" title="Copy cleaned data"
                                style="background: transparent; border: 1px solid #2a2a2a; border-radius: 8px; padding: 6px; cursor: pointer; color: #8a8a8a;">
                            <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </button>
                        <svg class="expand-icon" style="width: 16px; height: 16px; color: #8a8a8a; transition: transform 0.2s;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>

                {{-- Body (collapsible) --}}
                <div class="record-body" style="display: none; padding: 0 20px 20px 20px; border-top: 1px solid #222;">

                    @if($isMalformed)
                        <div style="margin-top: 16px; padding: 12px 16px; background-color: rgba(239, 68, 68, 0.1); border-left: 3px solid #ef4444; border-radius: 8px;">
                            <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #ef4444; font-weight: 700; margin-bottom: 4px;">
                                Malformed Row Detected
                            </div>
                            <div style="font-size: 12px; color: #fca5a5;">
                                {{ $record->changes['__malformed']['to'] ?? 'Data structure issue' }}
                            </div>
                        </div>
                    @endif

                    <div style="display: grid; grid-template-columns: 1fr 60px 1fr; gap: 16px; align-items: start; padding-top: 16px;" class="compare-grid">
                        <div>
                            <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600; margin-bottom: 10px;">Before</div>
                            <div style="background-color: #0a0a0a; border: 1px solid #2a2a2a; border-radius: 12px; padding: 14px; font-family: monospace; font-size: 12px;">
                                @foreach($record->original_data as $k => $v)
                                    <div style="margin-bottom: 6px; display: flex; gap: 8px;">
                                        <span style="color: #5a5a5a; min-width: 100px;">{{ $k }}:</span>
                                        <span style="color: {{ empty($v) ? '#ef4444' : '#f5f5f5' }};">{{ $v ?: '(empty)' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: center; padding-top: 40px;">
                            <svg style="width: 24px; height: 24px; color: #10b981;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </div>

                        <div>
                            <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #10b981; font-weight: 600; margin-bottom: 10px;">After</div>
                            <div style="background-color: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 12px; padding: 14px; font-family: monospace; font-size: 12px;">
                                @foreach($record->cleaned_data as $k => $v)
                                    @php $change = $record->changes[$k] ?? null; @endphp
                                    <div style="margin-bottom: 6px;">
                                        <div style="display: flex; gap: 8px;">
                                            <span style="color: #5a5a5a; min-width: 100px;">{{ $k }}:</span>
                                            <span style="color: {{ $change ? '#10b981' : (empty($v) ? '#ef4444' : '#f5f5f5') }}; {{ $change ? 'font-weight: 700;' : '' }}">
                                                {{ $v ?: '(empty)' }}
                                            </span>
                                        </div>
                                        @if($change && !empty($change['reason']))
                                            <div style="font-size: 10px; color: #f59e0b; margin-left: 108px; margin-top: 2px;">↳ {{ $change['reason'] }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Change Summary --}}
                    @if($hasChanges)
                        <div style="margin-top: 16px; padding: 12px 16px; background-color: rgba(16, 185, 129, 0.05); border-left: 3px solid #10b981; border-radius: 8px;">
                            <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #10b981; font-weight: 600; margin-bottom: 8px;">Changes Applied</div>
                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                @foreach($record->changes as $field => $change)
                                    @if($field === '__malformed') @continue @endif
                                    <div style="font-size: 12px; color: #8a8a8a;">
                                        <code style="background-color: #0a0a0a; color: #10b981; padding: 2px 6px; border-radius: 4px; font-size: 11px;">{{ $field }}</code>
                                        <span style="color: #5a5a5a; margin: 0 6px;">→</span>
                                        <span style="color: #f87171;">{{ $change['from'] ?: '(empty)' }}</span>
                                        <span style="color: #8a8a8a; margin: 0 6px;">became</span>
                                        <span style="color: #10b981;">{{ $change['to'] ?: '(empty)' }}</span>
                                        @if(!empty($change['reason']))
                                            <span style="color: #f59e0b; margin-left: 8px; font-size: 11px;">({{ $change['reason'] }})</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div style="background-color: #131313; border: 1px solid #222; border-radius: 16px; padding: 64px 24px; text-align: center;">
                <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.4;">🧼</div>
                <h3 style="font-size: 18px; font-weight: 700; color: #f5f5f5; margin: 0 0 8px 0;">No cleaning results yet</h3>
                <p style="color: #8a8a8a; font-size: 14px; margin: 0 0 20px 0;">
                    Go back to the upload and click <strong style="color: #f59e0b;">Clean Data</strong> to process the records.
                </p>
                <a href="{{ route('uploads.show', $upload) }}"
                   style="display: inline-flex; align-items: center; gap: 8px; background-color: #f59e0b; color: #000; padding: 12px 24px; border-radius: 999px; font-weight: 700; font-size: 14px; text-decoration: none;">
                    ← Back to Upload
                </a>
            </div>
        @endforelse
    </div>

    <div id="emptyFilter" style="display: none; background-color: #131313; border: 1px solid #222; border-radius: 16px; padding: 48px 24px; text-align: center;">
        <div style="font-size: 40px; margin-bottom: 12px; opacity: 0.4;">🔍</div>
        <p style="color: #8a8a8a; font-size: 14px; margin: 0;">No records match your filter.</p>
    </div>

    <style>
        @media (max-width: 800px) {
            .compare-grid { grid-template-columns: 1fr !important; }
            .stats-grid { grid-template-columns: repeat(2, 1fr) !important; }
        }
        .stat-card { transition: all 0.2s; }
        .stat-card:hover { transform: translateY(-2px); border-color: rgba(16, 185, 129, 0.3) !important; }
        .record-card:hover { border-color: rgba(16, 185, 129, 0.3) !important; }
        .filter-chip:hover { background-color: rgba(16, 185, 129, 0.2) !important; color: #10b981 !important; }
    </style>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.record-header').forEach(header => {
                header.addEventListener('click', (e) => {
                    if (e.target.closest('.copy-btn')) return;
                    const card = header.closest('.record-card');
                    const body = card.querySelector('.record-body');
                    const icon = header.querySelector('.expand-icon');
                    const isOpen = body.style.display !== 'none';
                    body.style.display = isOpen ? 'none' : 'block';
                    icon.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
                });
            });

            document.querySelectorAll('.copy-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    navigator.clipboard.writeText(btn.dataset.copy);
                    const original = btn.innerHTML;
                    btn.innerHTML = '✓';
                    btn.style.color = '#10b981';
                    setTimeout(() => {
                        btn.innerHTML = original;
                        btn.style.color = '#8a8a8a';
                    }, 1500);
                });
            });

            const chips = document.querySelectorAll('.filter-chip');
            const cards = document.querySelectorAll('.record-card');
            const searchInput = document.getElementById('searchInput');
            const emptyFilter = document.getElementById('emptyFilter');
            let activeFilter = 'all';

            function applyFilters() {
                const query = (searchInput?.value || '').toLowerCase();
                let visible = 0;
                cards.forEach(card => {
                    const matchesFilter = activeFilter === 'all' || card.dataset.filter === activeFilter;
                    const matchesSearch = !query || card.dataset.search.includes(query);
                    if (matchesFilter && matchesSearch) {
                        card.style.display = 'block';
                        visible++;
                    } else {
                        card.style.display = 'none';
                    }
                });
                emptyFilter.style.display = visible === 0 ? 'block' : 'none';
            }

            chips.forEach(chip => {
                chip.addEventListener('click', () => {
                    activeFilter = chip.dataset.filter;
                    chips.forEach(c => {
                        c.style.backgroundColor = 'transparent';
                        c.style.color = '#8a8a8a';
                    });
                    chip.style.backgroundColor = '#10b981';
                    chip.style.color = '#000';
                    applyFilters();
                });
            });

            searchInput?.addEventListener('input', applyFilters);
        });
    </script>
    @endpush
</x-app-layout>