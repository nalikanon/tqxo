<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excel Upload & Reader - Fast UI</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/upload.css') }}">
</head>

<body>

    <div class="container">
        <header>
            <h1>Excel Importer</h1>
        </header>

        @if ($errors->any())
            <div class="alert alert-error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if (session('success') || isset($success))
            <div class="alert alert-success">
                {{ session('success') ?? $success }}
            </div>
        @endif

        <div class="card">
            <form id="uploadForm" action="/upload" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="controls-bar" style="justify-content: center; margin-bottom: 1.5rem;">
                    <div>
                        <label for="previewLimit"
                            style="color: var(--text-muted); margin-right: 0.5rem;">จำนวนแถวที่ต้องการแสดงตัวอย่าง:</label>
                        <select name="limit" id="previewLimit"
                            onchange="window.location.href='?limit=' + this.value + '&page=1&sheet={{ urlencode($current_sheet ?? '') }}'">
                            <option value="50"
                                {{ isset($preview_limit) && $preview_limit == 50 ? 'selected' : '' }}>50 แถว</option>
                            <option value="100"
                                {{ isset($preview_limit) && $preview_limit == 100 ? 'selected' : (!isset($preview_limit) ? 'selected' : '') }}>
                                100 แถว</option>
                            <option value="200"
                                {{ isset($preview_limit) && $preview_limit == 200 ? 'selected' : '' }}>200 แถว
                            </option>
                            <option value="500"
                                {{ isset($preview_limit) && $preview_limit == 500 ? 'selected' : '' }}>500 แถว
                            </option>
                        </select>
                    </div>
                </div>

                <div class="upload-area" id="dropZone" onclick="document.getElementById('fileInput').click()">
                    <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📁</div>
                    <h3 style="margin-bottom: 0.5rem;">ลากไฟล์มาวางที่นี่ หรือ คลิกเพื่อเลือกไฟล์</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">รองรับไฟล์ .xlsx ขนาดสูงสุด 50MB</p>
                    <input type="file" name="excel_file" id="fileInput" accept=".xlsx, .xls, .csv" required>
                    <div id="fileName"
                        style="margin-top: 1rem; color: var(--success); display: none; font-weight: bold;"></div>
                </div>

                <div style="margin-top: 1.5rem; text-align: center;">
                    <button type="submit" class="btn" id="submitBtn" disabled>อัปโหลดและเปิดไฟล์ทันที</button>
                </div>

                <div class="loading-overlay" id="loadingOverlay">
                    <div class="spinner"></div>
                    <p>กำลังเปิดไฟล์...</p>
                </div>
            </form>
        </div>

        @if (isset($base_filename))
            <div class="glass-card" style="margin-top: 2rem;">
                <h2 style="color: var(--text-main); margin-bottom: 1.5rem; text-align: center;">ตัวอย่างข้อมูลจากไฟล์
                </h2>

                @if (isset($available_sheets) && count($available_sheets) > 1)
                    <!-- Sheet Tabs -->
                    <div
                        style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; overflow-x: auto;">
                        <span style="color: var(--text-muted); padding: 0.5rem 0;">เลือก Sheet:</span>
                        @foreach ($available_sheets as $sheet)
                            <a href="{{ route('preview', ['base_filename' => $base_filename, 'limit' => $preview_limit, 'page' => 1, 'sheet' => $sheet]) }}"
                                class="btn"
                                style="padding: 0.5rem 1rem; border-radius: 4px; font-weight: bold; white-space: nowrap; {{ $current_sheet === $sheet ? 'background: var(--primary-color); color: white;' : 'background: rgba(255,255,255,0.05); color: var(--text-muted);' }}">
                                {{ $sheet }}
                            </a>
                        @endforeach
                    </div>
                @endif
                <!-- Action Bar -->
                <div class="controls-bar" style="margin-top: 1rem; margin-bottom: 1rem;">
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <span style="color: var(--text-muted);">รวมทั้งหมด (เฉพาะชีตนี้): <strong
                                style="color: var(--text-main);">{{ $total_data_rows ?? 0 }}</strong> แถว</span>

                        @if (isset($current_sheet) && in_array($current_sheet, ['D', 'U']))
                            <form action="/import" method="POST" style="margin: 0;">
                                @csrf
                                <input type="hidden" name="base_filename" value="{{ $base_filename }}">
                                <input type="hidden" name="sheet" value="{{ $current_sheet }}">
                                <button type="submit" class="btn btn-success"
                                    style="padding: 0.5rem 1rem; font-size: 0.9rem;"
                                    onclick="this.innerHTML='กำลังนำเข้า...'; this.disabled=true; this.form.submit();">นำเข้าฐานข้อมูลแบบเฉพาะเจาะจง
                                    (Sheet {{ $current_sheet }})</button>
                            </form>
                        @endif
                        
                        <form action="/import-dynamic" method="POST" style="margin: 0; display: flex; gap: 0.5rem; align-items: center;">
                            @csrf
                            <input type="hidden" name="base_filename" value="{{ $base_filename }}">
                            <input type="hidden" name="sheet" value="{{ $current_sheet }}">
                            <select name="header_row" style="background: rgba(15, 23, 42, 0.9); color: var(--text-main); border: 1px solid var(--border-color); padding: 0.4rem 0.5rem; border-radius: 4px; font-size: 0.9rem;">
                                <option value="1">หัวข้ออยู่แถวที่ 1</option>
                                <option value="2">หัวข้ออยู่แถวที่ 2</option>
                                <option value="merge_1_2" selected>รวมแถวที่ 1 และ 2 เป็นหัวข้อเดียว</option>
                            </select>
                            <button type="submit" class="btn btn-success"
                                style="padding: 0.5rem 1rem; font-size: 0.9rem; background-color: #f59e0b;"
                                onclick="this.innerHTML='กำลังสร้างตารางและนำเข้า...'; this.disabled=true; this.form.submit();">สร้างตารางอัตโนมัติและนำเข้า (Dynamic)
                            </button>
                        </form>
                    </div>

                    <!-- Pagination Controls -->
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <a href="{{ isset($base_filename) ? route('preview', ['base_filename' => $base_filename, 'sheet' => $current_sheet ?? '', 'limit' => $preview_limit, 'page' => max(1, ($current_page ?? 1) - 1)]) : '#' }}"
                            class="btn"
                            style="padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); {{ ($current_page ?? 1) <= 1 ? 'pointer-events: none; opacity: 0.5;' : '' }}">
                            &laquo; ก่อนหน้า
                        </a>
                        <span style="color: var(--text-muted); font-weight: bold; margin: 0 0.5rem;">
                            หน้า {{ $current_page ?? 1 }} / {{ $total_pages ?? 1 }}
                        </span>
                        <a href="{{ isset($base_filename) ? route('preview', ['base_filename' => $base_filename, 'sheet' => $current_sheet ?? '', 'limit' => $preview_limit, 'page' => ($current_page ?? 1) + 1]) : '#' }}"
                            class="btn"
                            style="padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); {{ !($has_more ?? false) ? 'pointer-events: none; opacity: 0.5;' : '' }}">
                            ถัดไป &raquo;
                        </a>
                    </div>


                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                @if (isset($headers[0]))
                                    @foreach ($headers[0] as $index => $header)
                                        <th>{{ $header ?: 'Col ' . ($index + 1) }}</th>
                                    @endforeach
                                @endif
                            </tr>
                            @if (isset($headers[1]) && !is_numeric($headers[1][0]) && $headers[1][0] === 'in')
                                <tr>
                                    @foreach ($headers[1] as $subHeader)
                                        <th>{{ $subHeader }}</th>
                                    @endforeach
                                </tr>
                            @endif
                        </thead>
                        <tbody>
                            @foreach ($rows as $rowIndex => $rowData)
                                <tr>
                                    @foreach ($rowData as $cell)
                                        <td>{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>


        @endif
    </div>

    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const fileName = document.getElementById('fileName');
        const submitBtn = document.getElementById('submitBtn');
        const uploadForm = document.getElementById('uploadForm');
        const loadingOverlay = document.getElementById('loadingOverlay');

        if (dropZone) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
                document.body.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, unhighlight, false);
            });

            function highlight(e) {
                dropZone.classList.add('dragover');
            }

            function unhighlight(e) {
                dropZone.classList.remove('dragover');
            }

            dropZone.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                if (dt.files.length) {
                    fileInput.files = dt.files;
                    updateFileInfo();
                }
            }

            fileInput.addEventListener('change', updateFileInfo);

            function updateFileInfo() {
                if (fileInput.files.length > 0) {
                    const file = fileInput.files[0];
                    fileName.textContent = `ไฟล์ที่เลือก: ${file.name} (${(file.size / (1024 * 1024)).toFixed(2)} MB)`;
                    fileName.style.display = 'block';
                    submitBtn.disabled = false;
                } else {
                    fileName.style.display = 'none';
                    submitBtn.disabled = true;
                }
            }

            uploadForm.addEventListener('submit', function(e) {
                if (fileInput.files.length > 0) {
                    loadingOverlay.style.display = 'flex';
                    submitBtn.disabled = true;
                }
            });
        }
    </script>
</body>

</html>
