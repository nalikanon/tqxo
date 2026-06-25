<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excel Upload & Reader - Fast UI</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --bg-color: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.1);
            --success: #10b981;
            --danger: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
            background-image: 
                radial-gradient(at 0% 0%, hsla(253,16%,7%,1) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(225,39%,30%,1) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(339,49%,30%,1) 0, transparent 50%);
            background-attachment: fixed;
        }

        .container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
        }

        header {
            text-align: center;
            margin-bottom: 2rem;
            animation: fadeInDown 0.8s ease-out;
        }

        header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(to right, #a855f7, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        header p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            margin-bottom: 2rem;
            animation: fadeInUp 0.8s ease-out;
        }

        .upload-area {
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            background: rgba(255, 255, 255, 0.02);
        }

        .upload-area:hover, .upload-area.dragover {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.05);
            transform: translateY(-2px);
        }

        input[type="file"] {
            display: none;
        }

        .btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.4);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.5);
        }

        .btn:disabled {
            background: #475569;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .btn-success {
            background: var(--success);
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.4);
        }
        .btn-success:hover {
            background: #059669;
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.5);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            animation: fadeIn 0.5s;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #6ee7b7;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .controls-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        select {
            background: rgba(15, 23, 42, 0.8);
            color: white;
            border: 1px solid var(--border-color);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 1rem;
            outline: none;
        }

        .table-container {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: auto;
            max-height: 70vh;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeInUp 1s ease-out;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            white-space: nowrap;
        }

        th, td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.9rem;
        }

        th {
            background: rgba(15, 23, 42, 0.9);
            position: sticky;
            top: 0;
            z-index: 10;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        tr:nth-child(2) th {
            top: 45px;
            z-index: 9;
            background: rgba(30, 41, 59, 0.95);
        }

        tbody tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .checkbox-cell {
            position: sticky;
            left: 0;
            background: inherit;
            z-index: 8;
            border-right: 1px solid var(--border-color);
        }
        
        th.checkbox-cell {
            z-index: 11;
            background: rgba(15, 23, 42, 1);
        }

        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
            cursor: pointer;
        }

        .loading-overlay {
            display: none;
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.8);
            border-radius: 20px;
            z-index: 20;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            backdrop-filter: blur(4px);
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(99, 102, 241, 0.3);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }

        @keyframes spin { 100% { transform: rotate(360deg); } }
        @keyframes fadeInDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    </style>
</head>
<body>

    <div class="container">
        <header>
            <h1>Excel Importer</h1>
        </header>

        @if($errors->any())
            <div class="alert alert-error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if(session('success') || isset($success))
            <div class="alert alert-success">
                {{ session('success') ?? $success }}
            </div>
        @endif

        <div class="card">
            <form id="uploadForm" action="/upload" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="controls-bar" style="justify-content: center; margin-bottom: 1.5rem;">
                    <div>
                        <label for="previewLimit" style="color: var(--text-muted); margin-right: 0.5rem;">จำนวนแถวที่ต้องการแสดงตัวอย่าง:</label>
                        <select name="limit" id="previewLimit" onchange="window.location.href='?limit=' + this.value + '&page=1&sheet={{ urlencode($current_sheet ?? '') }}'">
                            <option value="50" {{ (isset($preview_limit) && $preview_limit == 50) ? 'selected' : '' }}>50 แถว</option>
                            <option value="100" {{ (isset($preview_limit) && $preview_limit == 100) ? 'selected' : (!isset($preview_limit) ? 'selected' : '') }}>100 แถว</option>
                            <option value="200" {{ (isset($preview_limit) && $preview_limit == 200) ? 'selected' : '' }}>200 แถว</option>
                            <option value="500" {{ (isset($preview_limit) && $preview_limit == 500) ? 'selected' : '' }}>500 แถว</option>
                        </select>
                    </div>
                </div>

                <div class="upload-area" id="dropZone" onclick="document.getElementById('fileInput').click()">
                    <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📁</div>
                    <h3 style="margin-bottom: 0.5rem;">ลากไฟล์มาวางที่นี่ หรือ คลิกเพื่อเลือกไฟล์</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">รองรับไฟล์ .xlsx ขนาดสูงสุด 50MB</p>
                    <input type="file" name="excel_file" id="fileInput" accept=".xlsx, .xls, .csv" required>
                    <div id="fileName" style="margin-top: 1rem; color: var(--success); display: none; font-weight: bold;"></div>
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

        @if(isset($base_filename))
            <div class="glass-card" style="margin-top: 2rem;">
                <h2 style="color: var(--text-main); margin-bottom: 1.5rem; text-align: center;">ตัวอย่างข้อมูลจากไฟล์</h2>
                
                @if(isset($available_sheets) && count($available_sheets) > 1)
                    <!-- Sheet Tabs -->
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; overflow-x: auto;">
                        <span style="color: var(--text-muted); padding: 0.5rem 0;">เลือก Sheet:</span>
                        @foreach($available_sheets as $sheet)
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
                        <span style="color: var(--text-muted);">รวมทั้งหมด (เฉพาะชีตนี้): <strong style="color: var(--text-main);">{{ $total_data_rows ?? 0 }}</strong> แถว</span>
                        
                        @if(isset($current_sheet) && $current_sheet === 'D')
                            <form action="/import" method="POST" style="margin: 0;">
                                @csrf
                                <input type="hidden" name="base_filename" value="{{ $base_filename }}">
                                <input type="hidden" name="sheet" value="{{ $current_sheet }}">
                                <button type="submit" class="btn btn-success" style="padding: 0.5rem 1rem; font-size: 0.9rem;" onclick="this.innerHTML='กำลังนำเข้า...'; this.disabled=true; this.form.submit();">นำเข้าฐานข้อมูล (Sheet D)</button>
                            </form>
                        @endif
                    </div>
                    
                    <!-- Pagination Controls -->
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <a href="{{ isset($base_filename) ? route('preview', ['base_filename' => $base_filename, 'sheet' => $current_sheet ?? '', 'limit' => $preview_limit, 'page' => max(1, ($current_page ?? 1) - 1)]) : '#' }}" 
                               class="btn" style="padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); {{ ($current_page ?? 1) <= 1 ? 'pointer-events: none; opacity: 0.5;' : '' }}">
                                &laquo; ก่อนหน้า
                            </a>
                            <span style="color: var(--text-muted); font-weight: bold; margin: 0 0.5rem;">
                                หน้า {{ $current_page ?? 1 }} / {{ $total_pages ?? 1 }}
                            </span>
                            <a href="{{ isset($base_filename) ? route('preview', ['base_filename' => $base_filename, 'sheet' => $current_sheet ?? '', 'limit' => $preview_limit, 'page' => ($current_page ?? 1) + 1]) : '#' }}" 
                               class="btn" style="padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); {{ !($has_more ?? false) ? 'pointer-events: none; opacity: 0.5;' : '' }}">
                                ถัดไป &raquo;
                            </a>
                        </div>


                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                @if(isset($headers[0]))
                                    @foreach($headers[0] as $index => $header)
                                        <th>{{ $header ?: 'Col ' . ($index + 1) }}</th>
                                    @endforeach
                                @endif
                            </tr>
                            @if(isset($headers[1]) && !is_numeric($headers[1][0]) && $headers[1][0] === 'in')
                                <tr>
                                    @foreach($headers[1] as $subHeader)
                                        <th>{{ $subHeader }}</th>
                                    @endforeach
                                </tr>
                            @endif
                        </thead>
                        <tbody>
                            @foreach($rows as $rowIndex => $rowData)
                                <tr>
                                    @foreach($rowData as $cell)
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

        if(dropZone) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
                document.body.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults (e) { e.preventDefault(); e.stopPropagation(); }

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, unhighlight, false);
            });

            function highlight(e) { dropZone.classList.add('dragover'); }
            function unhighlight(e) { dropZone.classList.remove('dragover'); }

            dropZone.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                if(dt.files.length) {
                    fileInput.files = dt.files;
                    updateFileInfo();
                }
            }

            fileInput.addEventListener('change', updateFileInfo);

            function updateFileInfo() {
                if(fileInput.files.length > 0) {
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
                if(fileInput.files.length > 0) {
                    loadingOverlay.style.display = 'flex';
                    submitBtn.disabled = true;
                }
            });
        }
    </script>
</body>
</html>
