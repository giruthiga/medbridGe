<x-app-layout>
    @php
        // Enhanced analytics
        $user = Auth::user();

        // FIELD-LEVEL ANALYSIS
        $fieldStats = [];
        $standardFields = [
            'full_name' => 'Full Name',
            'date_of_birth' => 'Date of Birth',
            'mobile_number' => 'Mobile Number',
            'sex' => 'Sex',
            'city' => 'City',
            'national_id' => 'National ID',
            'blood_type' => 'Blood Type',
            'emergency_contact' => 'Emergency Contact',
        ];
        foreach ($standardFields as $key => $label) {
            $fieldStats[$key] = [
                'label' => $label,
                'matched' => 0,
                'fixed' => 0,
                'total_uploads' => $totalUploads,
                'coverage' => 0,
                'fix_rate' => 0,
            ];
        }

        foreach ($uploads as $u) {
            foreach ($u->matches as $m) {
                if (isset($fieldStats[$m->med_field])) {
                    $fieldStats[$m->med_field]['matched']++;
                }
            }
            foreach ($u->cleanedRecords as $cr) {
                foreach (($cr->changes ?? []) as $field => $change) {
                    if (isset($fieldStats[$field])) {
                        $fieldStats[$field]['fixed']++;
                    }
                }
            }
        }

        foreach ($fieldStats as $k => &$fs) {
            $fs['coverage'] = $totalUploads > 0 ? round(($fs['matched'] / $totalUploads) * 100) : 0;
            $denom = max($fs['matched'], 1);
            $fs['fix_rate'] = $totalRows > 0 ? round(($fs['fixed'] / $totalRows) * 100, 1) : 0;
        }
        unset($fs);

        // Sort fields by coverage
        uasort($fieldStats, fn($a, $b) => $b['coverage'] <=> $a['coverage']);

        // TIME-OF-DAY ANALYSIS
        $hourDistribution = array_fill(0, 24, 0);
        foreach ($uploads as $u) {
            $hour = (int) $u->created_at->format('H');
            $hourDistribution[$hour]++;
        }
        $peakHour = array_search(max($hourDistribution), $hourDistribution);

        // DAY-OF-WEEK
        $dayDistribution = array_fill(0, 7, 0);
        $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        foreach ($uploads as $u) {
            $day = (int) $u->created_at->dayOfWeek;
            $dayDistribution[$day]++;
        }
        $peakDay = array_search(max($dayDistribution), $dayDistribution);

        // EFFICIENCY METRICS
        $avgTimeToScore = 0;
        $scoredUploads = $uploads->filter(fn($u) => $u->readinessReport);
        if ($scoredUploads->count() > 0) {
            $totalSeconds = 0;
            foreach ($scoredUploads as $u) {
                $totalSeconds += $u->readinessReport->created_at->diffInSeconds($u->created_at);
            }
            $avgTimeToScore = round($totalSeconds / $scoredUploads->count());
        }

        // DATA HEALTH SCORE
        // Composite: schema coverage × avg readiness × data cleanliness
        $totalMatchedSlots = $totalUploads * 8;
        $schemaCoverage = $totalMatchedSlots > 0 ? ($totalMatches / $totalMatchedSlots) * 100 : 0;
        $avgReadinessVal = $scoredUploads->count() > 0 ? $scoredUploads->avg(fn($u) => $u->readinessReport->score) : 0;
        $cleanliness = $totalRows > 0 ? max(0, 100 - ($totalChanges / $totalRows) * 100) : 100;
        $healthScore = round(($schemaCoverage * 0.3) + ($avgReadinessVal * 0.5) + ($cleanliness * 0.2));

        $healthLabel = match(true) {
            $healthScore >= 90 => 'Excellent',
            $healthScore >= 75 => 'Good',
            $healthScore >= 50 => 'Fair',
            default => 'Needs Work',
        };
        $healthColor = match(true) {
            $healthScore >= 90 => '#10b981',
            $healthScore >= 75 => '#34d399',
            $healthScore >= 50 => '#f59e0b',
            default => '#ef4444',
        };

        // FIELD EFFICIENCY HEATMAP (matched × fixed)
        // Upload bursts (max uploads in a single day)
        $uploadsByDay = $uploads->groupBy(fn($u) => $u->created_at->format('Y-m-d'))->map(fn($g) => $g->count());
        $maxBurst = $uploadsByDay->max() ?? 0;
        $burstDay = $uploadsByDay->search($maxBurst);

        // Issue type breakdown
        $issueBreakdown = ['phone' => 0, 'date' => 0, 'gender' => 0, 'name' => 0, 'city' => 0, 'other' => 0];
        foreach ($uploads as $u) {
            foreach ($u->cleanedRecords as $cr) {
                foreach (($cr->changes ?? []) as $field => $change) {
                    if (str_contains($field, 'phone') || str_contains($field, 'mobile') || str_contains($field, 'contact')) $issueBreakdown['phone']++;
                    elseif (str_contains($field, 'date') || str_contains($field, 'dob') || str_contains($field, 'birth')) $issueBreakdown['date']++;
                    elseif (str_contains($field, 'sex') || str_contains($field, 'gender')) $issueBreakdown['gender']++;
                    elseif (str_contains($field, 'name')) $issueBreakdown['name']++;
                    elseif (str_contains($field, 'city') || str_contains($field, 'address')) $issueBreakdown['city']++;
                    else $issueBreakdown['other']++;
                }
            }
        }
        $totalIssuesFound = array_sum($issueBreakdown);
    @endphp

    {{-- Header --}}
    <div style="margin-bottom: 28px; display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 20px;">
        <div>
            <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.15em; color: #10b981; font-weight: 700; margin-bottom: 8px;">
                Insights
            </div>
            <h1 style="font-size: 34px; font-weight: 800; color: #f5f5f5; margin: 0 0 6px 0; letter-spacing: -0.02em;">
                Data Intelligence
            </h1>
            <p style="color: #8a8a8a; font-size: 14px; margin: 0;">
                Deep analysis of your data patterns and quality
            </p>
        </div>
    </div>

    @if($totalUploads === 0)
        <div style="background: linear-gradient(135deg, #131313 0%, #0f1f1a 100%); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 24px; padding: 64px 32px; text-align: center;">
            <div style="width: 72px; height: 72px; margin: 0 auto 20px; border-radius: 20px; background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); display: flex; align-items: center; justify-content: center;">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 20V10M18 20V4M6 20v-4"/>
                </svg>
            </div>
            <h2 style="font-size: 22px; font-weight: 800; color: #f5f5f5; margin: 0 0 12px 0;">Nothing to analyze yet</h2>
            <p style="color: #8a8a8a; font-size: 14px; margin: 0 0 24px 0;">Upload files to start building insights.</p>
            <a href="{{ route('uploads.create') }}" class="ins-btn ins-btn-primary" style="display: inline-flex;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New Upload
            </a>
        </div>
    @else
        {{-- ============ HEALTH SCORE HERO ============ --}}
        <div style="background: linear-gradient(135deg, #131313 0%, #1a1a1a 100%); border: 1px solid {{ $healthColor }}40; border-radius: 24px; padding: 32px; margin-bottom: 20px; position: relative; overflow: hidden;">
            <div style="position: absolute; top: -80px; right: -80px; width: 300px; height: 300px; border-radius: 50%; background-color: {{ $healthColor }}; opacity: 0.15; filter: blur(80px);"></div>

            <div style="position: relative; display: grid; grid-template-columns: auto 1fr auto; gap: 32px; align-items: center;" class="health-hero">
                {{-- Big score ring --}}
                <div style="position: relative; width: 160px; height: 160px; flex-shrink: 0;">
                    <svg viewBox="0 0 160 160" style="width: 100%; height: 100%; transform: rotate(-90deg);">
                        <circle cx="80" cy="80" r="68" fill="none" stroke="#1f1f1f" stroke-width="10"/>
                        @php $dashArray = 2 * 3.14159 * 68; @endphp
                        <circle class="health-ring" cx="80" cy="80" r="68" fill="none" stroke="{{ $healthColor }}" stroke-width="10"
                                stroke-dasharray="{{ $dashArray }}"
                                stroke-dashoffset="{{ $dashArray }}"
                                stroke-linecap="round"
                                data-target="{{ $dashArray * (1 - $healthScore / 100) }}"
                                style="transition: stroke-dashoffset 1.5s cubic-bezier(0.4, 0, 0.2, 1);"/>
                    </svg>
                    <div style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <div class="count-up" style="font-size: 44px; font-weight: 800; color: {{ $healthColor }}; line-height: 1; letter-spacing: -0.02em;" data-count-to="{{ $healthScore }}">0</div>
                        <div style="font-size: 10px; color: #5a5a5a; text-transform: uppercase; letter-spacing: 0.15em; font-weight: 700;">health score</div>
                    </div>
                </div>

                {{-- Label --}}
                <div style="min-width: 0;">
                    <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.15em; color: {{ $healthColor }}; font-weight: 700; margin-bottom: 8px;">
                        Data Health Index
                    </div>
                    <div style="font-size: 36px; font-weight: 800; color: {{ $healthColor }}; margin-bottom: 8px; line-height: 1;">
                        {{ $healthLabel }}
                    </div>
                    <p style="font-size: 14px; color: #8a8a8a; margin: 0; max-width: 500px; line-height: 1.6;">
                        Composite metric across schema coverage, readiness scores, and data cleanliness. Weighted: 30% schema, 50% readiness, 20% cleanliness.
                    </p>
                </div>

                {{-- Sub metrics --}}
                <div style="display: flex; flex-direction: column; gap: 12px; flex-shrink: 0; min-width: 220px;">
                    <div style="padding: 12px 16px; background-color: rgba(0,0,0,0.4); border: 1px solid #2a2a2a; border-radius: 12px;">
                        <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; color: #8a8a8a; font-weight: 700; margin-bottom: 4px;">Schema Coverage</div>
                        <div style="font-size: 20px; font-weight: 800; color: #c084fc;"><span class="count-up" data-count-to="{{ round($schemaCoverage) }}">0</span>%</div>
                    </div>
                    <div style="padding: 12px 16px; background-color: rgba(0,0,0,0.4); border: 1px solid #2a2a2a; border-radius: 12px;">
                        <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; color: #8a8a8a; font-weight: 700; margin-bottom: 4px;">Data Cleanliness</div>
                        <div style="font-size: 20px; font-weight: 800; color: #22d3ee;"><span class="count-up" data-count-to="{{ round($cleanliness) }}">0</span>%</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ FIELD-LEVEL ANALYSIS ============ --}}
        <div style="background-color: #131313; border: 1px solid #222; border-radius: 20px; padding: 24px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
                <div>
                    <div style="font-size: 14px; font-weight: 700; color: #f5f5f5; margin-bottom: 3px;">Field Coverage Analysis</div>
                    <div style="font-size: 11px; color: #5a5a5a;">How often each standard field is used across your uploads</div>
                </div>
                <div style="font-size: 11px; color: #5a5a5a;">
                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 2px; background-color: #10b981; margin-right: 4px; vertical-align: middle;"></span>Coverage
                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 2px; background-color: #f59e0b; margin: 0 4px 0 12px; vertical-align: middle;"></span>Fix Rate
                </div>
            </div>

            @foreach($fieldStats as $key => $fs)
                <div class="field-row" style="display: flex; align-items: center; gap: 16px; padding: 14px 0; border-bottom: 1px solid #1a1a1a;">
                    <div style="width: 200px; flex-shrink: 0;">
                        <div style="font-size: 13px; font-weight: 600; color: #f5f5f5;">{{ $fs['label'] }}</div>
                        <div style="font-size: 10px; color: #5a5a5a; margin-top: 2px;">
                            {{ $fs['matched'] }} {{ Str::plural('upload', $fs['matched']) }} mapped
                        </div>
                    </div>

                    {{-- Coverage bar --}}
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                            <span style="font-size: 10px; color: #8a8a8a; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 700;">Coverage</span>
                            <span style="font-size: 12px; color: #10b981; font-weight: 800;"><span class="count-up" data-count-to="{{ $fs['coverage'] }}">0</span>%</span>
                        </div>
                        <div style="height: 6px; background-color: #1a1a1a; border-radius: 999px; overflow: hidden;">
                            <div class="field-bar" style="height: 100%; background: linear-gradient(90deg, #10b98180, #10b981); width: 0%; border-radius: 999px; transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);" data-target="{{ $fs['coverage'] }}"></div>
                        </div>
                    </div>

                    {{-- Fix rate --}}
                    <div style="width: 120px; flex-shrink: 0;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                            <span style="font-size: 10px; color: #8a8a8a; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 700;">Fixed</span>
                            <span style="font-size: 12px; color: #f59e0b; font-weight: 800;">{{ $fs['fix_rate'] }}%</span>
                        </div>
                        <div style="height: 6px; background-color: #1a1a1a; border-radius: 999px; overflow: hidden;">
                            <div class="field-bar" style="height: 100%; background: linear-gradient(90deg, #f59e0b80, #f59e0b); width: 0%; border-radius: 999px; transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);" data-target="{{ $fs['fix_rate'] }}"></div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ============ TIME PATTERNS + EFFICIENCY ============ --}}
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 20px;" class="pattern-grid">

            {{-- Time-of-day heatmap --}}
            <div style="background-color: #131313; border: 1px solid #222; border-radius: 20px; padding: 24px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                    <div>
                        <div style="font-size: 14px; font-weight: 700; color: #f5f5f5; margin-bottom: 3px;">Upload Time Patterns</div>
                        <div style="font-size: 11px; color: #5a5a5a;">When you upload most often</div>
                    </div>
                    <div style="padding: 4px 12px; background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 999px; font-size: 11px; color: #10b981; font-weight: 700;">
                        Peak: {{ str_pad($peakHour, 2, '0', STR_PAD_LEFT) }}:00
                    </div>
                </div>

                @php $maxHour = max(max($hourDistribution), 1); @endphp
                <div style="display: flex; align-items: flex-end; gap: 3px; height: 100px;">
                    @for($h = 0; $h < 24; $h++)
                        @php
                            $count = $hourDistribution[$h];
                            $h_bar = ($count / $maxHour) * 100;
                            $isPeak = $h === $peakHour && $count > 0;
                        @endphp
                        <div class="hour-bar-wrap" data-hour="{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00" data-count="{{ $count }}" style="flex: 1; height: 100%; display: flex; flex-direction: column; justify-content: flex-end; cursor: pointer; position: relative;">
                            <div class="hour-bar" style="width: 100%; height: {{ max($h_bar, 4) }}%; background: {{ $count > 0 ? ($isPeak ? '#10b981' : 'rgba(16, 185, 129, 0.4)') : '#1f1f1f' }}; border-radius: 3px 3px 0 0; min-height: 3px; transition: all 0.2s;"></div>
                        </div>
                    @endfor
                </div>
                <div style="display: flex; justify-content: space-between; margin-top: 8px; font-size: 9px; color: #3a3a3a; font-weight: 700;">
                    <span>00h</span>
                    <span>06h</span>
                    <span>12h</span>
                    <span>18h</span>
                    <span>23h</span>
                </div>
            </div>

            {{-- Day-of-week --}}
            <div style="background-color: #131313; border: 1px solid #222; border-radius: 20px; padding: 24px;">
                <div style="font-size: 14px; font-weight: 700; color: #f5f5f5; margin-bottom: 3px;">Weekly Pattern</div>
                <div style="font-size: 11px; color: #5a5a5a; margin-bottom: 20px;">Uploads by day of week</div>

                @php $maxDay = max(max($dayDistribution), 1); @endphp
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    @for($d = 0; $d < 7; $d++)
                        @php
                            $count = $dayDistribution[$d];
                            $d_bar = ($count / $maxDay) * 100;
                            $isPeak = $d === $peakDay && $count > 0;
                        @endphp
                        <div class="day-row" data-day="{{ $dayNames[$d] }}" data-count="{{ $count }}">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                                <span style="font-size: 11px; color: {{ $isPeak ? '#10b981' : '#8a8a8a' }}; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em;">{{ $dayNames[$d] }}</span>
                                <span style="font-size: 11px; color: {{ $isPeak ? '#10b981' : '#5a5a5a' }}; font-weight: 700;">{{ $count }}</span>
                            </div>
                            <div style="height: 5px; background-color: #1a1a1a; border-radius: 999px; overflow: hidden;">
                                <div class="field-bar" style="height: 100%; background: {{ $isPeak ? '#10b981' : 'rgba(16, 185, 129, 0.4)' }}; width: 0%; border-radius: 999px; transition: width 0.8s;" data-target="{{ $d_bar }}"></div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        {{-- ============ EFFICIENCY METRICS ============ --}}
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px;" class="metrics-grid">
            <div class="metric-card">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <div class="metric-label">Avg Time to Score</div>
                    <div class="metric-icon" style="background-color: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.3);">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                </div>
                <div style="font-size: 26px; font-weight: 800; color: #f5f5f5; line-height: 1;">
                    @if($avgTimeToScore > 3600)
                        {{ round($avgTimeToScore / 3600, 1) }}h
                    @elseif($avgTimeToScore > 60)
                        {{ round($avgTimeToScore / 60) }}m
                    @else
                        {{ $avgTimeToScore }}s
                    @endif
                </div>
                <div style="font-size: 11px; color: #5a5a5a; margin-top: 6px;">From upload to readiness score</div>
            </div>

            <div class="metric-card">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <div class="metric-label">Total Records Cleaned</div>
                    <div class="metric-icon" style="background-color: rgba(6, 182, 212, 0.1); border-color: rgba(6, 182, 212, 0.3);">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22d3ee" stroke-width="2"><path d="M12 3l1.9 5.8L20 10l-5.8 1.9L12 18l-1.9-5.8L4 10l5.8-1.9z"/></svg>
                    </div>
                </div>
                <div style="font-size: 26px; font-weight: 800; color: #22d3ee; line-height: 1;" class="count-up" data-count-to="{{ $totalCleaned }}">0</div>
                <div style="font-size: 11px; color: #5a5a5a; margin-top: 6px;">Records processed through cleaner</div>
            </div>

            <div class="metric-card">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <div class="metric-label">Issues Detected</div>
                    <div class="metric-icon" style="background-color: rgba(245, 158, 11, 0.1); border-color: rgba(245, 158, 11, 0.3);">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                </div>
                <div style="font-size: 26px; font-weight: 800; color: #fbbf24; line-height: 1;" class="count-up" data-count-to="{{ $totalIssuesFound }}">0</div>
                <div style="font-size: 11px; color: #5a5a5a; margin-top: 6px;">Total data fixes applied</div>
            </div>

            <div class="metric-card">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <div class="metric-label">Peak Day</div>
                    <div class="metric-icon" style="background-color: rgba(168, 85, 247, 0.1); border-color: rgba(168, 85, 247, 0.3);">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#c084fc" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    </div>
                </div>
                <div style="font-size: 26px; font-weight: 800; color: #c084fc; line-height: 1;">{{ $burstDay ? \Carbon\Carbon::parse($burstDay)->format('M j') : '—' }}</div>
                <div style="font-size: 11px; color: #5a5a5a; margin-top: 6px;">{{ $maxBurst }} uploads in one day</div>
            </div>
        </div>

        {{-- ============ ISSUE BREAKDOWN ============ --}}
        <div style="background-color: #131313; border: 1px solid #222; border-radius: 20px; padding: 24px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                <div>
                    <div style="font-size: 14px; font-weight: 700; color: #f5f5f5; margin-bottom: 3px;">Issue Categories</div>
                    <div style="font-size: 11px; color: #5a5a5a;">What kinds of problems appear in your data</div>
                </div>
                <div style="font-size: 11px; color: #5a5a5a;">{{ $totalIssuesFound }} total fixes</div>
            </div>

            @php
                $issueLabels = [
                    'phone' => ['Phone Numbers', '#10b981'],
                    'date' => ['Dates', '#3b82f6'],
                    'gender' => ['Gender Values', '#a855f7'],
                    'name' => ['Name Formatting', '#f59e0b'],
                    'city' => ['Cities', '#06b6d4'],
                    'other' => ['Other', '#8a8a8a'],
                ];
                $maxIssue = max(max($issueBreakdown), 1);
            @endphp

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                @foreach($issueBreakdown as $key => $count)
                    @php [$label, $color] = $issueLabels[$key]; @endphp
                    <div class="issue-card" style="padding: 16px; background-color: #0a0a0a; border: 1px solid #222; border-radius: 12px; transition: all 0.2s;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                            <div style="width: 8px; height: 8px; border-radius: 2px; background-color: {{ $color }};"></div>
                            <span style="font-size: 12px; color: #8a8a8a; font-weight: 600;">{{ $label }}</span>
                        </div>
                        <div style="font-size: 24px; font-weight: 800; color: {{ $color }}; line-height: 1;" class="count-up" data-count-to="{{ $count }}">0</div>
                        <div style="height: 4px; background-color: #1a1a1a; border-radius: 999px; overflow: hidden; margin-top: 12px;">
                            <div class="field-bar" style="height: 100%; background-color: {{ $color }}; width: 0%; border-radius: 999px; transition: width 0.8s;" data-target="{{ ($count / $maxIssue) * 100 }}"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <style>
        .ins-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 22px; border-radius: 999px; font-weight: 700; font-size: 13px; line-height: 1; text-decoration: none; cursor: pointer; transition: all 0.15s; font-family: inherit; border: 1px solid transparent; height: 44px; box-sizing: border-box; }
        .ins-btn svg { display: block; flex-shrink: 0; }
        .ins-btn-primary { background-color: #10b981; color: #000; }
        .ins-btn-primary:hover { background-color: #34d399; transform: translateY(-1px); box-shadow: 0 6px 20px -6px rgba(16, 185, 129, 0.5); }

        .metric-card { background-color: #131313; border: 1px solid #222; border-radius: 16px; padding: 20px; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
        .metric-card:hover { border-color: rgba(16, 185, 129, 0.3); transform: translateY(-3px); box-shadow: 0 12px 24px -10px rgba(16, 185, 129, 0.15); }
        .metric-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 700; }
        .metric-icon { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; border: 1px solid; }

        .field-row { transition: background-color 0.15s; }
        .field-row:hover { background-color: rgba(38, 38, 38, 0.3); border-radius: 8px; }

        .hour-bar-wrap:hover .hour-bar { filter: brightness(1.4); transform: scaleY(1.05); }
        .day-row { cursor: pointer; padding: 4px 8px; border-radius: 6px; transition: background-color 0.15s; }
        .day-row:hover { background-color: rgba(38, 38, 38, 0.4); }

        .issue-card:hover { border-color: rgba(16, 185, 129, 0.3); transform: translateY(-2px); }

        @media (max-width: 1100px) {
            .health-hero { grid-template-columns: 1fr !important; text-align: center; }
            .pattern-grid { grid-template-columns: 1fr !important; }
        }
        @media (max-width: 800px) {
            .metrics-grid { grid-template-columns: repeat(2, 1fr) !important; }
        }
        @media (max-width: 500px) {
            .metrics-grid { grid-template-columns: 1fr !important; }
        }
    </style>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // ===== Count-up animation =====
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

            // ===== Health ring animate =====
            setTimeout(() => {
                const ring = document.querySelector('.health-ring');
                if (ring) ring.style.strokeDashoffset = ring.dataset.target;
            }, 200);

            // ===== Field bars animate =====
            setTimeout(() => {
                document.querySelectorAll('.field-bar').forEach(bar => {
                    bar.style.width = bar.dataset.target + '%';
                });
            }, 400);

            // ===== Hour bar hover =====
            document.querySelectorAll('.hour-bar-wrap').forEach(el => {
                el.addEventListener('mouseenter', () => {
                    const hour = el.dataset.hour;
                    const count = el.dataset.count;
                    el.setAttribute('title', hour + ' · ' + count + ' uploads');
                });
            });
        });
    </script>
    @endpush
</x-app-layout>