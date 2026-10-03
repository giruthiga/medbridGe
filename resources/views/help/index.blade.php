<x-app-layout>
    {{-- Header --}}
    <div style="margin-bottom: 28px;">
        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.15em; color: #10b981; font-weight: 700; margin-bottom: 8px;">
            Help
        </div>
        <h1 style="font-size: 34px; font-weight: 800; color: #f5f5f5; margin: 0 0 6px 0; letter-spacing: -0.02em;">
            How Can We Help?
        </h1>
        <p style="color: #8a8a8a; font-size: 14px; margin: 0;">
            Guides, FAQs, and keyboard shortcuts for medbridGe
        </p>
    </div>

    {{-- How it works --}}
    <div class="help-card" style="margin-bottom: 20px;">
        <div class="help-card-header">
            <div style="width: 36px; height: 36px; border-radius: 10px; background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); display: flex; align-items: center; justify-content: center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <div>
                <div class="help-card-title">How medbridGe Works</div>
                <div class="help-card-subtitle">The 5-step pipeline</div>
            </div>
        </div>
        <div class="help-card-body">
            <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px;" class="help-steps">
                @php
                    $steps = [
                        ['num' => '1', 'title' => 'Upload', 'desc' => 'Drop a CSV file', 'color' => '#60a5fa'],
                        ['num' => '2', 'title' => 'Parse', 'desc' => 'We read every row', 'color' => '#34d399'],
                        ['num' => '3', 'title' => 'Match', 'desc' => 'Map columns to schema', 'color' => '#c084fc'],
                        ['num' => '4', 'title' => 'Clean', 'desc' => 'Fix phones, dates, names', 'color' => '#22d3ee'],
                        ['num' => '5', 'title' => 'Score', 'desc' => 'Get readiness %', 'color' => '#fbbf24'],
                    ];
                @endphp
                @foreach($steps as $step)
                    <div style="text-align: center;">
                        <div style="width: 48px; height: 48px; margin: 0 auto 12px; border-radius: 50%; background-color: {{ $step['color'] }}15; border: 2px solid {{ $step['color'] }}50; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 800; color: {{ $step['color'] }};">
                            {{ $step['num'] }}
                        </div>
                        <div style="font-size: 13px; font-weight: 700; color: #f5f5f5; margin-bottom: 4px;">{{ $step['title'] }}</div>
                        <div style="font-size: 11px; color: #8a8a8a;">{{ $step['desc'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Keyboard shortcuts --}}
    <div class="help-card" style="margin-bottom: 20px;">
        <div class="help-card-header">
            <div style="width: 36px; height: 36px; border-radius: 10px; background-color: rgba(168, 85, 247, 0.1); border: 1px solid rgba(168, 85, 247, 0.3); display: flex; align-items: center; justify-content: center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c084fc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="6" width="18" height="12" rx="2"/><line x1="7" y1="10" x2="7" y2="10"/><line x1="11" y1="10" x2="11" y2="10"/><line x1="15" y1="10" x2="15" y2="10"/><line x1="7" y1="14" x2="17" y2="14"/>
                </svg>
            </div>
            <div>
                <div class="help-card-title">Keyboard Shortcuts</div>
                <div class="help-card-subtitle">Speed up your workflow</div>
            </div>
        </div>
        <div class="help-card-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;" class="help-shortcuts">
                @php
                    $shortcuts = [
                        ['keys' => ['⌘', 'K'], 'desc' => 'Open command palette'],
                        ['keys' => ['C'], 'desc' => 'Copy readiness score'],
                        ['keys' => ['D'], 'desc' => 'Download report'],
                        ['keys' => ['ESC'], 'desc' => 'Close modals'],
                    ];
                @endphp
                @foreach($shortcuts as $sc)
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background-color: #0a0a0a; border: 1px solid #222; border-radius: 10px;">
                        <span style="font-size: 13px; color: #f5f5f5;">{{ $sc['desc'] }}</span>
                        <div style="display: flex; gap: 4px;">
                            @foreach($sc['keys'] as $k)
                                <kbd style="display: inline-flex; align-items: center; justify-content: center; min-width: 24px; height: 24px; padding: 0 6px; background-color: #1a1a1a; border: 1px solid #2a2a2a; border-bottom-width: 2px; border-radius: 6px; font-size: 11px; font-weight: 700; color: #8a8a8a; font-family: monospace;">{{ $k }}</kbd>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- FAQ --}}
    <div class="help-card">
        <div class="help-card-header">
            <div style="width: 36px; height: 36px; border-radius: 10px; background-color: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); display: flex; align-items: center; justify-content: center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </div>
            <div>
                <div class="help-card-title">Frequently Asked Questions</div>
                <div class="help-card-subtitle">Common questions answered</div>
            </div>
        </div>
        <div class="help-card-body" style="padding: 0;">
            @php
                $faqs = [
                    ['q' => 'What CSV format does medbridGe accept?', 'a' => 'Any CSV with a header row. Columns can have any names — you\'ll map them to our standard schema in step 3.'],
                    ['q' => 'What happens to my data?', 'a' => 'Your data stays on this device. Nothing is sent to external servers.'],
                    ['q' => 'What is a "readiness score"?', 'a' => 'A weighted percentage indicating how ready your data is for import. It factors in column coverage (30%), row validity (40%), data quality (20%), and required fields (10%).'],
                    ['q' => 'Can I export the cleaned data?', 'a' => 'Yes — from the readiness report page, click Download to get a JSON export. CSV export is coming soon.'],
                    ['q' => 'What does the "Match Columns" step do?', 'a' => 'It lets you tell us which of your file\'s columns correspond to the standard schema fields (full_name, date_of_birth, etc.).'],
                    ['q' => 'How do I know if my data is ready to import?', 'a' => 'A score of 75% or above is considered ready. 90%+ is excellent.'],
                ];
            @endphp
            @foreach($faqs as $i => $faq)
                <div style="border-bottom: {{ $i === count($faqs) - 1 ? 'none' : '1px solid #1a1a1a' }};">
                    <div class="faq-q" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'block' ? 'none' : 'block'; this.querySelector('svg').style.transform = this.nextElementSibling.style.display === 'block' ? 'rotate(180deg)' : 'rotate(0)';">
                        <span style="font-size: 14px; font-weight: 600; color: #f5f5f5;">{{ $faq['q'] }}</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8a8a8a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="transition: transform 0.2s; flex-shrink: 0;">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </div>
                    <div class="faq-a" style="display: none; padding: 0 24px 20px 24px; font-size: 13px; color: #8a8a8a; line-height: 1.6;">
                        {{ $faq['a'] }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Contact --}}
    <div style="margin-top: 20px; padding: 24px; background: linear-gradient(135deg, #131313 0%, #0f1f1a 100%); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="font-size: 14px; font-weight: 700; color: #f5f5f5; margin-bottom: 4px;">Still need help?</div>
            <div style="font-size: 13px; color: #8a8a8a;">Reach out to the team — we respond within 24 hours.</div>
        </div>
        <a href="mailto:support@medbridge.local" style="display: inline-flex; align-items: center; gap: 8px; background-color: #10b981; color: #000; padding: 12px 22px; border-radius: 999px; font-weight: 700; font-size: 13px; text-decoration: none;">
            Contact Support
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
            </svg>
        </a>
    </div>

    <style>
        .help-card { background-color: #131313; border: 1px solid #222; border-radius: 20px; overflow: hidden; }
        .help-card-header { padding: 20px 24px; border-bottom: 1px solid #222; display: flex; align-items: center; gap: 14px; }
        .help-card-title { font-size: 15px; font-weight: 700; color: #f5f5f5; margin-bottom: 3px; }
        .help-card-subtitle { font-size: 11px; color: #5a5a5a; }
        .help-card-body { padding: 24px; }
        .faq-q { display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; cursor: pointer; transition: background-color 0.15s; }
        .faq-q:hover { background-color: rgba(38, 38, 38, 0.3); }
        @media (max-width: 900px) { .help-steps { grid-template-columns: repeat(2, 1fr) !important; } .help-shortcuts { grid-template-columns: 1fr !important; } }
    </style>
</x-app-layout>