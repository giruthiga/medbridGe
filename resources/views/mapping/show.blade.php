<x-app-layout>
    @php
        $autoMatchRules = [
            'name'      => ['full_name'],
            'fullname'  => ['full_name'],
            'full'      => ['full_name'],
            'first'     => ['full_name'],
            'last'      => ['full_name'],
            'dob'       => ['date_of_birth'],
            'birth'     => ['date_of_birth'],
            'date'      => ['date_of_birth'],
            'phone'     => ['mobile_number'],
            'mobile'    => ['mobile_number'],
            'contact'   => ['mobile_number'],
            'tel'       => ['mobile_number'],
            'gender'    => ['sex'],
            'sex'       => ['sex'],
            'address'   => ['city'],
            'city'      => ['city'],
            'town'      => ['city'],
            'cpr'       => ['national_id'],
            'national'  => ['national_id'],
            'id'        => ['national_id'],
            'blood'     => ['blood_type'],
            'emergency' => ['emergency_contact'],
        ];

        $autoMatches = [];
        foreach ($yourFields as $field) {
            $lower = strtolower($field);
            foreach ($autoMatchRules as $pattern => $targets) {
                if (str_contains($lower, $pattern)) {
                    $autoMatches[$field] = $targets[0];
                    break;
                }
            }
        }

        $firstRow = $upload->rawRows()->first();
        $totalFields = count($yourFields);
        $preMatched = count($existing);
        $totalMedFields = count($medFields);

        $usedMedFields = [];
        foreach ($yourFields as $f) {
            $val = $existing[$f] ?? ($autoMatches[$f] ?? '');
            if ($val) $usedMedFields[] = $val;
        }
        $coverage = $totalFields > 0 ? round((count($usedMedFields) / $totalFields) * 100) : 0;
    @endphp

    {{-- Header --}}
    <div style="margin-bottom: 32px; display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 24px;">
        <div style="min-width: 0; flex: 1;">
            <div style="display: flex; align-items: center; gap: 8px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600; margin-bottom: 12px;">
                <a href="{{ route('uploads.index') }}" style="color: #8a8a8a; text-decoration: none;">Uploads</a>
                <span>/</span>
                <a href="{{ route('uploads.show', $upload) }}" style="color: #8a8a8a; text-decoration: none;">{{ $upload->original_filename }}</a>
                <span>/</span>
                <span style="color: #10b981;">Match Columns</span>
            </div>

            <h1 style="font-size: 32px; font-weight: 800; color: #f5f5f5; margin: 0 0 8px 0;">
                Match Your Columns
            </h1>
            <p style="color: #8a8a8a; font-size: 14px; margin: 0; max-width: 640px;">
                Map your file's columns to medbrid<span style="color: #10b981; font-weight: 700;">G</span>e's standard schema.
            </p>
        </div>

        <a href="{{ route('uploads.show', $upload) }}"
           style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 999px; border: 1px solid #2a2a2a; background: transparent; color: #8a8a8a; text-decoration: none; font-size: 13px; font-weight: 500; white-space: nowrap; transition: all 0.2s;"
           onmouseover="this.style.backgroundColor='#262626'; this.style.color='#f5f5f5'; this.style.borderColor='#10b981';"
           onmouseout="this.style.backgroundColor='transparent'; this.style.color='#8a8a8a'; this.style.borderColor='#2a2a2a';">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Back
        </a>
    </div>

    {{-- Stats Row --}}
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px;" class="stats-grid">
        <div class="stat-card" style="background-color: #131313; border: 1px solid #222; border-radius: 16px; padding: 20px; transition: all 0.2s; cursor: default;">
            <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600; margin-bottom: 8px;">Your Fields</div>
            <div style="font-size: 28px; font-weight: 800; color: #f5f5f5; line-height: 1;">{{ $totalFields }}</div>
        </div>

        <div class="stat-card" style="background-color: #131313; border: 1px solid #222; border-radius: 16px; padding: 20px; transition: all 0.2s; cursor: default;">
            <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600; margin-bottom: 8px;">Matched</div>
            <div style="font-size: 28px; font-weight: 800; color: #10b981; line-height: 1;"><span id="matchedCount">{{ $preMatched }}</span></div>
        </div>

        <div class="stat-card" style="background-color: #131313; border: 1px solid #222; border-radius: 16px; padding: 20px; transition: all 0.2s; cursor: default;">
            <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600; margin-bottom: 8px;">Standard Schema</div>
            <div style="font-size: 28px; font-weight: 800; color: #f5f5f5; line-height: 1;">{{ $totalMedFields }}</div>
        </div>

        <div class="stat-card" style="background-color: #131313; border: 1px solid #222; border-radius: 16px; padding: 20px; transition: all 0.2s; cursor: default;">
            <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600; margin-bottom: 8px;">Coverage</div>
            <div style="font-size: 28px; font-weight: 800; color: #f5f5f5; line-height: 1;"><span id="coveragePct">{{ $coverage }}</span><span style="color: #5a5a5a; font-size: 18px;">%</span></div>
        </div>
    </div>

    {{-- Action Bar --}}
    <div style="background-color: #131313; border: 1px solid #222; border-radius: 16px; padding: 16px 20px; margin-bottom: 24px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px;">
        <div style="flex: 1; min-width: 240px;">
            <div style="font-size: 12px; color: #8a8a8a; margin-bottom: 6px;">
                <span style="color: #f5f5f5; font-weight: 600;">Mapping Progress</span>
                — keep going until coverage is high
            </div>
            <div style="height: 6px; background-color: #262626; border-radius: 999px; overflow: hidden;">
                <div id="progressBar"
                     style="height: 100%; background: linear-gradient(90deg, #10b981, #34d399); border-radius: 999px; transition: width 0.4s ease; width: {{ $coverage }}%;">
                </div>
            </div>
        </div>

        <button type="button" id="autoFillBtn" class="action-button"
                style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; border-radius: 999px; border: 1px solid rgba(16, 185, 129, 0.4); background-color: rgba(16, 185, 129, 0.1); color: #10b981; font-weight: 600; font-size: 13px; cursor: pointer; white-space: nowrap; transition: all 0.2s;"
                onmouseover="this.style.backgroundColor='rgba(16, 185, 129, 0.2)'; this.style.borderColor='#10b981';"
                onmouseout="this.style.backgroundColor='rgba(16, 185, 129, 0.1)'; this.style.borderColor='rgba(16, 185, 129, 0.4)';">
            <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            Auto-Fill All Suggestions
        </button>
    </div>

    {{-- Mapping Table --}}
    <form action="{{ route('mapping.store', $upload) }}" method="POST" id="mappingForm">
        @csrf

        <div style="background-color: #131313; border: 1px solid #222; border-radius: 20px; overflow: hidden; margin-bottom: 24px;">
            <div style="overflow-x: auto;">
                <table style="width: 100%; text-align: left; font-size: 14px; border-collapse: collapse;">
                    <thead style="background-color: rgba(10, 10, 10, 0.5); border-bottom: 1px solid #222;">
                        <tr>
                            <th style="padding: 14px 16px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600; width: 30%;">Your Field</th>
                            <th style="padding: 14px 16px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600; width: 40px; text-align: center;"></th>
                            <th style="padding: 14px 16px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600; width: 20%;">Confidence</th>
                            <th style="padding: 14px 16px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600;">Standard Field</th>
                            <th style="padding: 14px 16px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #8a8a8a; font-weight: 600; width: 15%;">Sample</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($yourFields as $your)
                            @php
                                $matched = $existing[$your] ?? '';
                                $suggested = $autoMatches[$your] ?? '';
                                $sample = \Illuminate\Support\Str::limit($firstRow->data[$your] ?? '—', 24);
                                $isEmpty = empty($firstRow->data[$your] ?? '');
                            @endphp
                            <tr class="field-row" data-field="{{ $your }}">
                                <td style="padding: 16px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <code style="background-color: #0a0a0a; color: #10b981; padding: 5px 10px; border-radius: 8px; font-size: 13px; font-family: monospace; border: 1px solid #222;">
                                            {{ $your }}
                                        </code>
                                    </div>
                                </td>

                                <td style="padding: 16px; text-align: center;">
                                    <svg style="width: 18px; height: 18px; transition: color 0.2s;" class="arrow-icon"
                                         fill="none" stroke="#5a5a5a" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </td>

                                <td style="padding: 16px;">
                                    <span class="confidence-badge" style="display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; padding: 4px 10px; border-radius: 999px; background-color: #262626; color: #5a5a5a; border: 1px solid #2a2a2a;">
                                        <span class="conf-dot" style="width: 6px; height: 6px; border-radius: 50%; background-color: #5a5a5a;"></span>
                                        <span class="conf-text">Empty</span>
                                    </span>
                                </td>

                                <td style="padding: 16px;">
                                    <select name="mappings[{{ $your }}]"
                                            class="field-select"
                                            data-field="{{ $your }}"
                                            data-suggested="{{ $suggested }}"
                                            style="width: 100%; background-color: #0a0a0a; border: 1px solid #2a2a2a; color: #f5f5f5; border-radius: 10px; padding: 10px 40px 10px 14px; font-size: 14px; outline: none; cursor: pointer; appearance: none; transition: all 0.2s; background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%238a8a8a%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19 9l-7 7-7-7%22/></svg>'); background-repeat: no-repeat; background-position: right 12px center; background-size: 16px;">
                                        <option value="">— Skip this column —</option>
                                        @foreach($medFields as $key => $label)
                                            <option value="{{ $key }}" {{ ($matched === $key || (!$matched && $suggested === $key)) ? 'selected' : '' }} style="background-color: #131313;">
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td style="padding: 16px;">
                                    <code style="font-size: 12px; color: {{ $isEmpty ? '#5a5a5a' : '#8a8a8a' }}; font-family: monospace;">
                                        {{ $sample }}
                                    </code>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Actions --}}
        <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
            <button type="submit" class="primary-btn"
                    style="display: inline-flex; align-items: center; gap: 8px; background-color: #10b981; color: #000; padding: 14px 28px; border-radius: 999px; font-weight: 700; font-size: 14px; border: none; cursor: pointer; box-shadow: 0 0 24px -8px rgba(16, 185, 129, 0.5); transition: all 0.2s;"
                    onmouseover="this.style.backgroundColor='#34d399'; this.style.boxShadow='0 0 32px -8px rgba(16, 185, 129, 0.7)';"
                    onmouseout="this.style.backgroundColor='#10b981'; this.style.boxShadow='0 0 24px -8px rgba(16, 185, 129, 0.5)';">
                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Save Matches
            </button>

            <a href="{{ route('uploads.show', $upload) }}" class="secondary-btn"
               style="display: inline-flex; align-items: center; gap: 8px; padding: 14px 28px; border-radius: 999px; border: 1px solid #2a2a2a; color: #8a8a8a; text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.2s;"
               onmouseover="this.style.backgroundColor='#262626'; this.style.color='#f5f5f5'; this.style.borderColor='#10b981';"
               onmouseout="this.style.backgroundColor='transparent'; this.style.color='#8a8a8a'; this.style.borderColor='#2a2a2a';">
                Cancel
            </a>

            <div id="saveHint" style="font-size: 12px; color: #f59e0b; margin-left: auto; display: none;">
                Unsaved changes
            </div>
        </div>
    </form>

    {{-- Two-column: Legend + Coverage --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 32px;" class="bottom-grid">

        {{-- Legend Card --}}
        <div style="background-color: #131313; border: 1px solid #222; border-radius: 16px; padding: 24px; transition: border-color 0.3s;" onmouseover="this.style.borderColor='#10b98130'" onmouseout="this.style.borderColor='#222'">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
                <svg style="width: 16px; height: 16px; color: #10b981;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                </svg>
                <h3 style="font-size: 13px; font-weight: 700; color: #f5f5f5; margin: 0; text-transform: uppercase; letter-spacing: 0.1em;">
                    Confidence Levels
                </h3>
            </div>

            <div style="display: flex; flex-direction: column; gap: 10px;">
                <div class="legend-item" style="display: flex; align-items: center; gap: 12px; padding: 12px 14px; background-color: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 10px; transition: all 0.2s; cursor: default;">
                    <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; padding: 4px 10px; border-radius: 999px; background-color: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); flex-shrink: 0; width: 90px; justify-content: center;">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background-color: #10b981;"></span>
                        Manual
                    </span>
                    <div style="min-width: 0;">
                        <div style="font-size: 12px; color: #f5f5f5; font-weight: 500;">100% confidence</div>
                        <div style="font-size: 11px; color: #8a8a8a;">You selected this match yourself</div>
                    </div>
                </div>

                <div class="legend-item" style="display: flex; align-items: center; gap: 12px; padding: 12px 14px; background-color: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 10px; transition: all 0.2s; cursor: default;">
                    <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; padding: 4px 10px; border-radius: 999px; background-color: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); flex-shrink: 0; width: 90px; justify-content: center;">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background-color: #f59e0b;"></span>
                        Suggested
                    </span>
                    <div style="min-width: 0;">
                        <div style="font-size: 12px; color: #f5f5f5; font-weight: 500;">60% confidence</div>
                        <div style="font-size: 11px; color: #8a8a8a;">Auto-matched by name pattern</div>
                    </div>
                </div>

                <div class="legend-item" style="display: flex; align-items: center; gap: 12px; padding: 12px 14px; background-color: #0a0a0a; border: 1px solid #2a2a2a; border-radius: 10px; transition: all 0.2s; cursor: default;">
                    <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; padding: 4px 10px; border-radius: 999px; background-color: #262626; color: #8a8a8a; border: 1px solid #2a2a2a; flex-shrink: 0; width: 90px; justify-content: center;">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background-color: #8a8a8a;"></span>
                        Empty
                    </span>
                    <div style="min-width: 0;">
                        <div style="font-size: 12px; color: #f5f5f5; font-weight: 500;">Not matched</div>
                        <div style="font-size: 11px; color: #8a8a8a;">Will be skipped during export</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Coverage Card --}}
        <div style="background-color: #131313; border: 1px solid #222; border-radius: 16px; padding: 24px; transition: border-color 0.3s;" onmouseover="this.style.borderColor='#10b98130'" onmouseout="this.style.borderColor='#222'">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
                <svg style="width: 16px; height: 16px; color: #10b981;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <h3 style="font-size: 13px; font-weight: 700; color: #f5f5f5; margin: 0; text-transform: uppercase; letter-spacing: 0.1em;">
                    Schema Coverage
                </h3>
            </div>

            <p style="font-size: 12px; color: #8a8a8a; margin: 0 0 16px 0; line-height: 1.6;">
                Medbrid<span style="color: #10b981; font-weight: 700;">G</span>e supports {{ $totalMedFields }} standard fields. You've mapped <span style="color: #10b981; font-weight: 600;">{{ count(array_unique($usedMedFields)) }}</span> of them.
            </p>

            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                @foreach($medFields as $key => $label)
                    @php $isUsed = in_array($key, $usedMedFields); @endphp
                    <span class="coverage-pill" style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; padding: 5px 12px; border-radius: 999px; border: 1px solid; font-weight: 500; transition: all 0.2s; cursor: default;
                        {{ $isUsed
                            ? 'background-color: rgba(16, 185, 129, 0.1); color: #10b981; border-color: rgba(16, 185, 129, 0.3);'
                            : 'background-color: #0a0a0a; color: #5a5a5a; border-color: #2a2a2a;' }}">
                        @if($isUsed)
                            <svg style="width: 11px; height: 11px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        @else
                            <span style="width: 5px; height: 5px; border-radius: 50%; background-color: #5a5a5a;"></span>
                        @endif
                        {{ $label }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Pro Tip --}}
    <div style="margin-top: 16px; padding: 20px 24px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(16, 185, 129, 0.02) 100%); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 16px; display: flex; align-items: flex-start; gap: 16px; transition: border-color 0.3s;" onmouseover="this.style.borderColor='rgba(16, 185, 129, 0.5)'" onmouseout="this.style.borderColor='rgba(16, 185, 129, 0.3)'">
        <div style="width: 40px; height: 40px; border-radius: 10px; background-color: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <svg style="width: 20px; height: 20px; color: #10b981;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
        </div>
        <div style="flex: 1; min-width: 0;">
            <div style="font-size: 13px; color: #f5f5f5; font-weight: 700; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.1em;">
                Pro Tip
            </div>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <div style="display: flex; align-items: flex-start; gap: 10px;">
                    <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background-color: #10b981; flex-shrink: 0; margin-top: 7px;"></span>
                    <span style="font-size: 13px; color: #8a8a8a; line-height: 1.5;">
                        Multiple columns can map to the <span style="color: #10b981; font-weight: 600;">same standard field</span> — they'll be merged during cleaning.
                    </span>
                </div>
                <div style="display: flex; align-items: flex-start; gap: 10px;">
                    <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background-color: #10b981; flex-shrink: 0; margin-top: 7px;"></span>
                    <span style="font-size: 13px; color: #8a8a8a; line-height: 1.5;">
                        Skipped columns won't appear in the final export.
                    </span>
                </div>
                <div style="display: flex; align-items: flex-start; gap: 10px;">
                    <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background-color: #10b981; flex-shrink: 0; margin-top: 7px;"></span>
                    <span style="font-size: 13px; color: #8a8a8a; line-height: 1.5;">
                        You can always come back and edit these matches later.
                    </span>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media (max-width: 900px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr) !important; }
            .bottom-grid { grid-template-columns: 1fr !important; }
        }

        .stat-card:hover {
            border-color: rgba(16, 185, 129, 0.3) !important;
            transform: translateY(-2px);
        }

        .legend-item:hover {
            transform: translateX(4px);
        }

        .coverage-pill:hover {
            transform: scale(1.05);
        }

        .field-row {
            transition: background-color 0.2s ease;
            border-bottom: 1px solid #222;
        }

        .field-row:hover {
            background-color: rgba(38, 38, 38, 0.4);
        }

        .field-select:hover {
            border-color: #3a3a3a !important;
        }

        /* Smooth focus glow */
        .field-select:focus {
            border-color: #10b981 !important;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15) !important;
        }
    </style>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selects = document.querySelectorAll('.field-select');
            const matchedCount = document.getElementById('matchedCount');
            const coveragePct = document.getElementById('coveragePct');
            const progressBar = document.getElementById('progressBar');
            const autoFillBtn = document.getElementById('autoFillBtn');
            const saveHint = document.getElementById('saveHint');
            const total = selects.length;

            function updateRowUI(sel) {
                const row = sel.closest('.field-row');
                const arrow = row.querySelector('.arrow-icon');
                const badge = row.querySelector('.confidence-badge');
                const dot = row.querySelector('.conf-dot');
                const text = row.querySelector('.conf-text');
                const suggested = sel.dataset.suggested;

                if (sel.value) {
                    if (suggested === sel.value) {
                        badge.style.backgroundColor = 'rgba(245, 158, 11, 0.15)';
                        badge.style.color = '#f59e0b';
                        badge.style.borderColor = 'rgba(245, 158, 11, 0.3)';
                        dot.style.backgroundColor = '#f59e0b';
                        text.textContent = 'Suggested';
                        arrow.style.color = '#f59e0b';
                    } else {
                        badge.style.backgroundColor = 'rgba(16, 185, 129, 0.15)';
                        badge.style.color = '#10b981';
                        badge.style.borderColor = 'rgba(16, 185, 129, 0.3)';
                        dot.style.backgroundColor = '#10b981';
                        text.textContent = 'Manual';
                        arrow.style.color = '#10b981';
                    }
                } else {
                    badge.style.backgroundColor = '#262626';
                    badge.style.color = '#5a5a5a';
                    badge.style.borderColor = '#2a2a2a';
                    dot.style.backgroundColor = '#5a5a5a';
                    text.textContent = 'Empty';
                    arrow.style.color = '#5a5a5a';
                }
            }

            function updateProgress() {
                let count = 0;
                selects.forEach(sel => {
                    if (sel.value) count++;
                    updateRowUI(sel);
                });
                matchedCount.textContent = count;
                const pct = total > 0 ? Math.round((count / total) * 100) : 0;
                coveragePct.textContent = pct;
                progressBar.style.width = pct + '%';
            }

            selects.forEach(sel => {
                sel.addEventListener('change', () => {
                    updateProgress();
                    saveHint.style.display = 'block';
                });
            });

            autoFillBtn.addEventListener('click', () => {
                let filled = 0;
                selects.forEach(sel => {
                    const suggested = sel.dataset.suggested;
                    if (suggested && !sel.value) {
                        sel.value = suggested;
                        filled++;
                        sel.style.borderColor = '#f59e0b';
                        setTimeout(() => { sel.style.borderColor = '#2a2a2a'; }, 800);
                    }
                });
                updateProgress();
                if (filled > 0) {
                    saveHint.style.display = 'block';
                    autoFillBtn.innerHTML = `Applied ${filled} suggestions`;
                    setTimeout(() => {
                        autoFillBtn.innerHTML = `<svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg> Auto-Fill All Suggestions`;
                    }, 2000);
                } else {
                    autoFillBtn.innerHTML = `Nothing to apply`;
                    setTimeout(() => {
                        autoFillBtn.innerHTML = `<svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg> Auto-Fill All Suggestions`;
                    }, 2000);
                }
            });

            updateProgress();
        });
    </script>
    @endpush
</x-app-layout>