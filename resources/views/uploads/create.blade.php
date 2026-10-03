<x-app-layout>
    <div style="margin-bottom: 32px;">
        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.15em; color: #10b981; font-weight: 600; margin-bottom: 8px;">
            Upload
        </div>
        <h1 style="font-size: 36px; font-weight: 800; color: #f5f5f5; margin: 0;">
            Upload Hospital Data
        </h1>
        <p style="color: #8a8a8a; margin-top: 6px; font-size: 14px; max-width: 640px;">
            Drop a CSV file and medbrid<span style="color: #10b981; font-weight: 700;">G</span>e will scan, parse, and prepare it for matching.
        </p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr; gap: 24px;" class="lg-grid">

        {{-- Main --}}
        <div style="grid-column: span 2;" class="lg-main">
            <form action="{{ route('uploads.store') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                @csrf

                {{-- Drop Zone --}}
                <div id="dropZone"
                     style="position: relative; background-color: #131313; border: 2px dashed #2a2a2a; border-radius: 24px; padding: 48px; text-align: center; cursor: pointer; transition: all 0.3s ease;"
                     onmouseover="this.style.borderColor='#10b98150'"
                     onmouseout="if(!this.dataset.hasFile) this.style.borderColor='#2a2a2a'">

                    <input type="file"
                           id="fileInput"
                           name="csv_file"
                           accept=".csv,.txt"
                           style="display: none;"
                           required>

                    <div id="dropContent">
                        <div style="width: 80px; height: 80px; margin: 0 auto 24px; border-radius: 24px; background-color: #262626; border: 1px solid #2a2a2a; display: flex; align-items: center; justify-content: center;">
                            <svg style="width: 40px; height: 40px;" fill="none" stroke="#10b981" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                        </div>

                        <h3 style="font-size: 24px; font-weight: 800; color: #f5f5f5; margin: 0 0 8px 0;">
                            Drop your CSV here
                        </h3>
                        <p style="color: #8a8a8a; font-size: 14px; margin: 0 0 24px 0;">
                            or <span style="color: #10b981; font-weight: 600;">click to browse</span> from your computer
                        </p>

                        <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 10px;">
                            <span style="display: inline-flex; align-items: center; gap: 6px; background-color: #0a0a0a; border: 1px solid #2a2a2a; border-radius: 999px; padding: 6px 14px; font-size: 12px; color: #8a8a8a;">
                                📄 CSV
                            </span>
                            <span style="display: inline-flex; align-items: center; gap: 6px; background-color: #0a0a0a; border: 1px solid #2a2a2a; border-radius: 999px; padding: 6px 14px; font-size: 12px; color: #8a8a8a;">
                                📝 TXT
                            </span>
                            <span style="display: inline-flex; align-items: center; gap: 6px; background-color: #0a0a0a; border: 1px solid #2a2a2a; border-radius: 999px; padding: 6px 14px; font-size: 12px; color: #8a8a8a;">
                                ⚡ Max 10MB
                            </span>
                        </div>
                    </div>

                    {{-- Preview --}}
                    <div id="filePreview" style="display: none;">
                        <div style="width: 80px; height: 80px; margin: 0 auto 24px; border-radius: 24px; background-color: rgba(16, 185, 129, 0.1); border: 2px solid #10b981; display: flex; align-items: center; justify-content: center;">
                            <svg style="width: 40px; height: 40px;" fill="none" stroke="#10b981" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.15em; color: #10b981; font-weight: 600; margin-bottom: 8px;">
                            File Ready
                        </div>
                        <h3 id="fileName" style="font-size: 22px; font-weight: 800; color: #f5f5f5; margin: 0 0 6px 0;">file.csv</h3>
                        <p id="fileSize" style="color: #8a8a8a; font-size: 14px; margin: 0 0 20px 0;">0 KB</p>

                        <button type="button" id="clearFile"
                                style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #8a8a8a; border: 1px solid #2a2a2a; border-radius: 999px; padding: 6px 14px; background: transparent; cursor: pointer;">
                            × Remove file
                        </button>
                    </div>
                </div>

                @if($errors->any())
                    <div style="margin-top: 16px; padding: 16px; background-color: rgba(127, 29, 29, 0.4); border: 1px solid #991b1b; color: #fca5a5; border-radius: 16px; font-size: 13px;">
                        <div style="font-weight: 600; margin-bottom: 8px;">Please fix these errors:</div>
                        <ul style="margin: 0; padding-left: 20px;">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div style="margin-top: 24px; display: flex; flex-wrap: wrap; gap: 12px;">
                    <button type="submit" id="submitBtn" disabled
                            style="background-color: #10b981; color: #000; padding: 14px 32px; border-radius: 999px; font-weight: 700; font-size: 14px; border: none; cursor: pointer; opacity: 0.4;">
                        Upload & Parse
                    </button>
                    <a href="{{ route('uploads.index') }}"
                       style="padding: 14px 32px; border-radius: 999px; border: 1px solid #2a2a2a; color: #8a8a8a; font-weight: 500; font-size: 14px; text-decoration: none;">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        {{-- Sidebar --}}
        <div style="display: flex; flex-direction: column; gap: 16px;">

            {{-- Format --}}
            <div style="background-color: #131313; border: 1px solid #222; border-radius: 16px; padding: 20px;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                    <span style="font-size: 18px;">💡</span>
                    <h3 style="font-size: 14px; font-weight: 600; color: #f5f5f5; margin: 0;">CSV Format</h3>
                </div>
                <p style="font-size: 12px; color: #8a8a8a; margin: 0 0 12px 0;">
                    Your file needs a header row. Example:
                </p>
                <pre style="background-color: #0a0a0a; border: 1px solid #222; border-radius: 12px; padding: 12px; font-size: 11px; color: #10b981; font-family: monospace; overflow-x: auto; margin: 0; line-height: 1.6;">name,dob,phone,gender
Ahmed Ali,1985-04-12,39123456,Male
Fatima H.,1990-08-23,36123457,Female</pre>
            </div>

            {{-- Pipeline --}}
            <div style="background-color: #131313; border: 1px solid #222; border-radius: 16px; padding: 20px;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
                    <span style="font-size: 18px;">⚙️</span>
                    <h3 style="font-size: 14px; font-weight: 600; color: #f5f5f5; margin: 0;">What Happens Next</h3>
                </div>
                <div style="display: flex; flex-direction: column; gap: 12px;">

                    <div style="display: flex; align-items: flex-start; gap: 12px;">
                        <div style="width: 24px; height: 24px; border-radius: 50%; background-color: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 11px; color: #10b981; font-weight: 700;">1</div>
                        <div>
                            <div style="font-size: 13px; color: #f5f5f5; font-weight: 500;">Parse</div>
                            <div style="font-size: 11px; color: #8a8a8a;">We read every row</div>
                        </div>
                    </div>

                    <div style="display: flex; align-items: flex-start; gap: 12px;">
                        <div style="width: 24px; height: 24px; border-radius: 50%; background-color: #262626; border: 1px solid #2a2a2a; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 11px; color: #8a8a8a; font-weight: 700;">2</div>
                        <div>
                            <div style="font-size: 13px; color: #f5f5f5; font-weight: 500;">Match</div>
                            <div style="font-size: 11px; color: #8a8a8a;">Map columns to MedPro</div>
                        </div>
                    </div>

                    <div style="display: flex; align-items: flex-start; gap: 12px;">
                        <div style="width: 24px; height: 24px; border-radius: 50%; background-color: #262626; border: 1px solid #2a2a2a; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 11px; color: #8a8a8a; font-weight: 700;">3</div>
                        <div>
                            <div style="font-size: 13px; color: #f5f5f5; font-weight: 500;">Clean</div>
                            <div style="font-size: 11px; color: #8a8a8a;">Fix phones, dates, formats</div>
                        </div>
                    </div>

                    <div style="display: flex; align-items: flex-start; gap: 12px;">
                        <div style="width: 24px; height: 24px; border-radius: 50%; background-color: #262626; border: 1px solid #2a2a2a; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 11px; color: #8a8a8a; font-weight: 700;">4</div>
                        <div>
                            <div style="font-size: 13px; color: #f5f5f5; font-weight: 500;">Score & Export</div>
                            <div style="font-size: 11px; color: #8a8a8a;">Readiness % + clean file</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sample --}}
            <div style="background-color: #131313; border: 1px solid #222; border-radius: 16px; padding: 20px;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                    <span style="font-size: 18px;">✨</span>
                    <h3 style="font-size: 14px; font-weight: 600; color: #f5f5f5; margin: 0;">No CSV handy?</h3>
                </div>
                <p style="font-size: 12px; color: #8a8a8a; margin: 0 0 16px 0;">
                    Generate a realistic messy hospital dataset.
                </p>
                <form action="{{ route('uploads.generate-sample') }}" method="POST">
                    @csrf
                    <button type="submit"
                            style="width: 100%; background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); color: #10b981; padding: 10px 16px; border-radius: 999px; font-weight: 600; font-size: 12px; cursor: pointer;">
                        ✨ Generate Sample
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Responsive grid for large screens --}}
    <style>
        @media (min-width: 1024px) {
            .lg-grid {
                grid-template-columns: 2fr 1fr !important;
            }
            .lg-main {
                grid-column: span 1 !important;
            }
        }
    </style>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const dropZone = document.getElementById('dropZone');
            const fileInput = document.getElementById('fileInput');
            const dropContent = document.getElementById('dropContent');
            const filePreview = document.getElementById('filePreview');
            const fileName = document.getElementById('fileName');
            const fileSize = document.getElementById('fileSize');
            const clearFile = document.getElementById('clearFile');
            const submitBtn = document.getElementById('submitBtn');

            function formatSize(bytes) {
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
            }

            function showFile(file) {
                fileName.textContent = file.name;
                fileSize.textContent = formatSize(file.size);
                dropContent.style.display = 'none';
                filePreview.style.display = 'block';
                dropZone.style.borderColor = '#10b981';
                dropZone.style.backgroundColor = 'rgba(16, 185, 129, 0.05)';
                dropZone.dataset.hasFile = 'true';
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
            }

            function resetFile() {
                fileInput.value = '';
                dropContent.style.display = 'block';
                filePreview.style.display = 'none';
                dropZone.style.borderColor = '#2a2a2a';
                dropZone.style.backgroundColor = '#131313';
                dropZone.dataset.hasFile = '';
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.4';
                submitBtn.style.cursor = 'not-allowed';
            }

            dropZone.addEventListener('click', (e) => {
                if (e.target === clearFile || clearFile.contains(e.target)) return;
                fileInput.click();
            });

            fileInput.addEventListener('change', () => {
                if (fileInput.files.length) showFile(fileInput.files[0]);
            });

            clearFile.addEventListener('click', (e) => {
                e.stopPropagation();
                resetFile();
            });

            ['dragenter', 'dragover'].forEach(evt => {
                dropZone.addEventListener(evt, (e) => {
                    e.preventDefault();
                    dropZone.style.borderColor = '#10b981';
                    dropZone.style.backgroundColor = 'rgba(16, 185, 129, 0.05)';
                });
            });

            ['dragleave', 'drop'].forEach(evt => {
                dropZone.addEventListener(evt, (e) => {
                    e.preventDefault();
                    if (!dropZone.dataset.hasFile) {
                        dropZone.style.borderColor = '#2a2a2a';
                        dropZone.style.backgroundColor = '#131313';
                    }
                });
            });

            dropZone.addEventListener('drop', (e) => {
                const files = e.dataTransfer.files;
                if (files.length) {
                    fileInput.files = files;
                    showFile(files[0]);
                }
            });
        });
    </script>
    @endpush
</x-app-layout>