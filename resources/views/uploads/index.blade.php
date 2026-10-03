<x-app-layout>
    @php
        // Precompute true statuses
        $uploadsWithStatus = $uploads->map(function ($upload) {
            $status = 'uploaded';
            if ($upload->readinessReport) $status = 'scored';
            elseif ($upload->cleanedRecords->isNotEmpty()) $status = 'cleaned';
            elseif ($upload->matches->isNotEmpty()) $status = 'mapped';
            elseif ($upload->rawRows->isNotEmpty()) $status = 'parsed';
            $upload->computed_status = $status;
            return $upload;
        });

        $statusCounts = [
            'all'      => $uploadsWithStatus->count(),
            'uploaded' => $uploadsWithStatus->where('computed_status', 'uploaded')->count(),
            'parsed'   => $uploadsWithStatus->where('computed_status', 'parsed')->count(),
            'mapped'   => $uploadsWithStatus->where('computed_status', 'mapped')->count(),
            'cleaned'  => $uploadsWithStatus->where('computed_status', 'cleaned')->count(),
            'scored'   => $uploadsWithStatus->where('computed_status', 'scored')->count(),
        ];

        // Top stats
        $totalRows = $uploads->sum('row_count');
        $scoredUploads = $uploadsWithStatus->where('computed_status', 'scored');
        $avgScore = $scoredUploads->count() > 0
            ? round($scoredUploads->avg(fn($u) => $u->readinessReport->score))
            : 0;

        $badgeStyles = [
            'scored'   => ['bg' => 'rgba(245, 158, 11, 0.12)', 'color' => '#fbbf24', 'border' => 'rgba(245, 158, 11, 0.4)', 'label' => 'Scored'],
            'cleaned'  => ['bg' => 'rgba(6, 182, 212, 0.12)',  'color' => '#22d3ee', 'border' => 'rgba(6, 182, 212, 0.4)',  'label' => 'Cleaned'],
            'mapped'   => ['bg' => 'rgba(168, 85, 247, 0.12)', 'color' => '#c084fc', 'border' => 'rgba(168, 85, 247, 0.4)', 'label' => 'Mapped'],
            'parsed'   => ['bg' => 'rgba(16, 185, 129, 0.12)', 'color' => '#34d399', 'border' => 'rgba(16, 185, 129, 0.4)', 'label' => 'Parsed'],
            'uploaded' => ['bg' => 'rgba(59, 130, 246, 0.12)', 'color' => '#60a5fa', 'border' => 'rgba(59, 130, 246, 0.4)', 'label' => 'Uploaded'],
        ];

        $stages = ['uploaded', 'parsed', 'mapped', 'cleaned', 'scored'];
    @endphp

    {{-- Header --}}
    <div style="margin-bottom: 28px;">
        <div style="display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 20px;">
            <div>
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.15em; color: #10b981; font-weight: 700; margin-bottom: 8px;">
                    Uploads
                </div>
                <h1 style="font-size: 36px; font-weight: 800; color: #f5f5f5; margin: 0 0 6px 0; letter-spacing: -0.02em;">
                    Your Uploads
                </h1>
                <p style="color: #8a8a8a; font-size: 14px; margin: 0;">
                    Every CSV you've uploaded to medbrid<span style="color: #10b981; font-weight: 700;">G</span>e, tracked through the pipeline.
                </p>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <form action="{{ route('uploads.generate-sample') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn-ghost">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                            <line x1="12" y1="22.08" x2="12" y2="12"/>
                        </svg>
                        <span>Generate Sample</span>
                    </button>
                </form>

                <a href="{{ route('uploads.create') }}" class="btn-primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    <span>New Upload</span>
                </a>
            </div>
        </div>

        {{-- Mini stat bar --}}
        @if($uploads->isNotEmpty())
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px;" class="mini-stats">
                <div class="mini-stat">
                    <div class="mini-stat-icon" style="background-color: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.3);">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                    </div>
                    <div>
                        <div class="mini-stat-label">Total</div>
                        <div class="mini-stat-value" data-count-to="{{ $statusCounts['all'] }}">0</div>
                    </div>
                </div>
                <div class="mini-stat">
                    <div class="mini-stat-icon" style="background-color: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.3);">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/>
                        </svg>
                    </div>
                    <div>
                        <div class="mini-stat-label">Rows</div>
                        <div class="mini-stat-value" data-count-to="{{ $totalRows }}">0</div>
                    </div>
                </div>
                <div class="mini-stat">
                    <div class="mini-stat-icon" style="background-color: rgba(245, 158, 11, 0.1); border-color: rgba(245, 158, 11, 0.3);">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                        </svg>
                    </div>
                    <div>
                        <div class="mini-stat-label">Scored</div>
                        <div class="mini-stat-value" style="color: #fbbf24;" data-count-to="{{ $statusCounts['scored'] }}">0</div>
                    </div>
                </div>
                <div class="mini-stat">
                    <div class="mini-stat-icon" style="background-color: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.3);">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
                        </svg>
                    </div>
                    <div>
                        <div class="mini-stat-label">Avg Score</div>
                        <div class="mini-stat-value" style="color: #10b981;">{{ $avgScore }}<span style="font-size: 14px; color: #5a5a5a;">%</span></div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Search + Filters --}}
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <div class="search-bar">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#8a8a8a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" id="searchInput" placeholder="Search by filename... (press /)" style="flex: 1; background: transparent; border: none; color: #f5f5f5; font-size: 13px; outline: none; font-family: inherit; padding: 0;">
                <kbd class="search-kbd">/</kbd>
            </div>

            <div class="filter-row">
                <button data-filter="all" class="filter-chip chip-active">
                    All <span class="chip-count">{{ $statusCounts['all'] }}</span>
                </button>
                <button data-filter="uploaded" class="filter-chip">
                    Uploaded <span class="chip-count">{{ $statusCounts['uploaded'] }}</span>
                </button>
                <button data-filter="parsed" class="filter-chip">
                    Parsed <span class="chip-count">{{ $statusCounts['parsed'] }}</span>
                </button>
                <button data-filter="mapped" class="filter-chip">
                    Mapped <span class="chip-count">{{ $statusCounts['mapped'] }}</span>
                </button>
                <button data-filter="cleaned" class="filter-chip">
                    Cleaned <span class="chip-count">{{ $statusCounts['cleaned'] }}</span>
                </button>
                <button data-filter="scored" class="filter-chip">
                    Scored <span class="chip-count">{{ $statusCounts['scored'] }}</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Table --}}
    @if($uploads->isEmpty())
        <div class="empty-state">
            <div class="empty-icon">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
            </div>
            <h3 style="font-size: 20px; font-weight: 800; color: #f5f5f5; margin: 0 0 8px 0;">No uploads yet</h3>
            <p style="color: #8a8a8a; margin: 0 0 24px 0; font-size: 14px;">Upload a hospital CSV to start scanning its readiness.</p>
            <div style="display: flex; flex-wrap: wrap; gap: 10px; justify-content: center;">
                <a href="{{ route('uploads.create') }}" class="btn-primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    <span>New Upload</span>
                </a>
            </div>
        </div>
    @else
        <div class="table-container">
            <div style="overflow-x: auto;">
                <table class="upload-table">
                    <thead>
                        <tr>
                            <th>File</th>
                            <th>Rows</th>
                            <th>Status</th>
                            <th>Pipeline</th>
                            <th>Uploaded</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($uploadsWithStatus as $upload)
                            @php
                                $stageIndex = array_search($upload->computed_status, $stages);
                                $progress = $stageIndex !== false ? round((($stageIndex + 1) / count($stages)) * 100) : 0;
                                $badge = $badgeStyles[$upload->computed_status];
                            @endphp
                            <tr class="upload-row" data-status="{{ $upload->computed_status }}" data-name="{{ strtolower($upload->original_filename) }}">
                                <td>
                                    <div class="file-cell">
                                        <div class="file-icon">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                <polyline points="14 2 14 8 20 8"/>
                                            </svg>
                                        </div>
                                        <div style="min-width: 0;">
                                            <div class="file-name">{{ $upload->original_filename }}</div>
                                            <div class="file-meta">{{ number_format($upload->row_count) }} rows</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="rows-value">{{ number_format($upload->row_count) }}</div>
                                </td>
                                <td>
                                    <span class="status-pill" style="background-color: {{ $badge['bg'] }}; color: {{ $badge['color'] }}; border-color: {{ $badge['border'] }};">
                                        <span class="status-dot" style="background-color: currentColor;"></span>
                                        {{ $badge['label'] }}
                                    </span>
                                </td>
                                <td style="min-width: 180px;">
                                    <div class="pipeline-cell">
                                        <div class="pipeline-track">
                                            <div class="pipeline-fill" style="width: {{ $progress }}%;"></div>
                                        </div>
                                        <span class="pipeline-pct">{{ $progress }}%</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="date-cell">{{ $upload->created_at->diffForHumans() }}</div>
                                </td>
                                <td style="text-align: right;">
                                    <a href="/uploads/{{ $upload->id }}" class="open-btn">
                                        Open
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="5" y1="12" x2="19" y2="12"/>
                                            <polyline points="12 5 19 12 12 19"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div id="noResults" style="display: none; padding: 64px 24px; text-align: center;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#5a5a5a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 16px;">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <p style="color: #8a8a8a; font-size: 14px; margin: 0;">No uploads match your filter.</p>
            </div>
        </div>
    @endif

    <style>
        .btn-primary {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 12px 22px; border-radius: 999px; font-weight: 700; font-size: 13px; line-height: 1;
            background-color: #10b981; color: #000; text-decoration: none; border: none; cursor: pointer;
            transition: all 0.15s ease; font-family: inherit; height: 44px; box-sizing: border-box;
        }
        .btn-primary:hover { background-color: #34d399; transform: translateY(-1px); box-shadow: 0 6px 20px -6px rgba(16, 185, 129, 0.5); }

        .btn-ghost {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 12px 22px; border-radius: 999px; font-weight: 600; font-size: 13px; line-height: 1;
            background-color: transparent; color: #8a8a8a; text-decoration: none;
            border: 1px solid #2a2a2a; cursor: pointer; transition: all 0.15s ease;
            font-family: inherit; height: 44px; box-sizing: border-box;
        }
        .btn-ghost:hover { border-color: #10b981; color: #10b981; }
        .btn-primary svg, .btn-ghost svg { display: block; flex-shrink: 0; }

        .mini-stats { }
        .mini-stat {
            background-color: #131313; border: 1px solid #222; border-radius: 14px;
            padding: 16px 18px; display: flex; align-items: center; gap: 12px;
            transition: all 0.2s;
        }
        .mini-stat:hover { border-color: rgba(16, 185, 129, 0.3); transform: translateY(-2px); }
        .mini-stat-icon {
            width: 36px; height: 36px; border-radius: 10px; display: flex;
            align-items: center; justify-content: center; border: 1px solid; flex-shrink: 0;
        }
        .mini-stat-label {
            font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em;
            color: #8a8a8a; font-weight: 700; margin-bottom: 3px;
        }
        .mini-stat-value { font-size: 20px; font-weight: 800; color: #f5f5f5; line-height: 1; letter-spacing: -0.02em; }

        .search-bar {
            display: flex; align-items: center; gap: 10px;
            background-color: #131313; border: 1px solid #2a2a2a;
            border-radius: 999px; padding: 10px 18px;
            transition: border-color 0.15s;
        }
        .search-bar:focus-within { border-color: #10b981; }
        .search-kbd {
            padding: 2px 8px; background-color: #1a1a1a; border: 1px solid #2a2a2a;
            border-radius: 6px; font-size: 10px; color: #5a5a5a; font-weight: 700;
            font-family: monospace;
        }

        .filter-row {
            display: flex; gap: 6px; background-color: #131313;
            border: 1px solid #2a2a2a; border-radius: 16px; padding: 6px; flex-wrap: wrap;
        }

        .filter-chip {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 14px; border-radius: 999px; font-size: 12px; font-weight: 600;
            border: none; cursor: pointer; background: transparent; color: #8a8a8a;
            transition: all 0.15s ease; font-family: inherit; white-space: nowrap;
        }
        .filter-chip:hover { color: #f5f5f5; background-color: rgba(38, 38, 38, 0.6); }
        .filter-chip.chip-active { background-color: #10b981; color: #000; }
        .chip-count {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 18px; height: 18px; padding: 0 5px; border-radius: 999px;
            font-size: 10px; font-weight: 800;
            background-color: rgba(255, 255, 255, 0.1); color: inherit;
        }
        .filter-chip.chip-active .chip-count { background-color: rgba(0, 0, 0, 0.15); color: #000; }

        .empty-state {
            background-color: #131313; border: 1px solid #222; border-radius: 20px;
            padding: 64px 24px; text-align: center;
        }
        .empty-icon {
            width: 80px; height: 80px; margin: 0 auto 20px; border-radius: 20px;
            background-color: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2);
            display: flex; align-items: center; justify-content: center;
        }

        .table-container {
            background-color: #131313; border: 1px solid #222; border-radius: 20px;
            overflow: hidden;
        }

        .upload-table {
            width: 100%; text-align: left; border-collapse: collapse; font-size: 14px;
        }
        .upload-table thead { background-color: rgba(10, 10, 10, 0.5); border-bottom: 1px solid #222; }
        .upload-table th {
            padding: 16px 20px; font-size: 10px; text-transform: uppercase;
            letter-spacing: 0.15em; color: #8a8a8a; font-weight: 700;
        }
        .upload-row {
            border-bottom: 1px solid #222;
            transition: all 0.15s ease;
        }
        .upload-row:hover { background-color: rgba(38, 38, 38, 0.4); }
        .upload-row:hover .file-icon {
            transform: scale(1.05);
            border-color: rgba(16, 185, 129, 0.5);
        }
        .upload-row.hidden { display: none; }

        .file-cell { display: flex; align-items: center; gap: 12px; padding: 18px 20px; }
        .file-icon {
            width: 40px; height: 40px; border-radius: 10px;
            background-color: #262626; border: 1px solid #2a2a2a;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; transition: all 0.2s ease;
        }
        .file-name {
            font-weight: 600; color: #f5f5f5; font-size: 14px;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 260px;
        }
        .file-meta { font-size: 11px; color: #5a5a5a; margin-top: 2px; }

        .rows-value {
            padding: 18px 20px; color: #f5f5f5; font-weight: 700; font-size: 15px;
        }

        .status-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 12px; font-size: 11px; border-radius: 999px;
            font-weight: 700; border: 1px solid;
            text-transform: uppercase; letter-spacing: 0.05em;
            margin-left: 20px;
        }
        .status-dot {
            width: 5px; height: 5px; border-radius: 50%;
        }

        .pipeline-cell {
            display: flex; align-items: center; gap: 12px; padding: 18px 20px;
        }
        .pipeline-track {
            flex: 1; height: 5px; background-color: #1f1f1f;
            border-radius: 999px; overflow: hidden;
        }
        .pipeline-fill {
            height: 100%; background: linear-gradient(90deg, #10b981, #34d399);
            border-radius: 999px; transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .pipeline-pct {
            font-size: 11px; color: #8a8a8a; font-weight: 700;
            flex-shrink: 0; min-width: 32px;
        }

        .date-cell {
            padding: 18px 20px; color: #8a8a8a; font-size: 12px; white-space: nowrap;
        }

        .open-btn {
            display: inline-flex; align-items: center; gap: 6px;
            color: #10b981; text-decoration: none; font-size: 13px; font-weight: 700;
            padding: 8px 16px; border-radius: 999px;
            background-color: rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(16, 185, 129, 0.2);
            transition: all 0.15s ease;
            margin-right: 20px;
        }
        .open-btn:hover {
            background-color: rgba(16, 185, 129, 0.15);
            border-color: #10b981;
            transform: translateX(2px);
        }

        @media (max-width: 900px) {
            .mini-stats { grid-template-columns: repeat(2, 1fr) !important; }
        }
        @media (max-width: 500px) {
            .mini-stats { grid-template-columns: 1fr !important; }
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

            // ===== Filters =====
            const searchInput = document.getElementById('searchInput');
            const rows = document.querySelectorAll('.upload-row');
            const chips = document.querySelectorAll('.filter-chip');
            const noResults = document.getElementById('noResults');
            let activeFilter = 'all';

            function applyFilters() {
                const query = (searchInput?.value || '').toLowerCase();
                let visibleCount = 0;
                rows.forEach(row => {
                    const nameMatch = row.dataset.name.includes(query);
                    const statusMatch = activeFilter === 'all' || row.dataset.status === activeFilter;
                    if (nameMatch && statusMatch) {
                        row.classList.remove('hidden');
                        visibleCount++;
                    } else {
                        row.classList.add('hidden');
                    }
                });
                if (noResults) {
                    noResults.style.display = visibleCount === 0 && rows.length > 0 ? 'block' : 'none';
                }
            }

            searchInput?.addEventListener('input', applyFilters);

            chips.forEach(chip => {
                chip.addEventListener('click', () => {
                    activeFilter = chip.dataset.filter;
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
                    searchInput?.focus();
                }
            });
        });
    </script>
    @endpush
</x-app-layout>