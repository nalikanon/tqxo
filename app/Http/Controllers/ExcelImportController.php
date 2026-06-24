<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Shuchkin\SimpleXLSX;
use Illuminate\Support\Facades\Storage;
use SplFileObject;

class ExcelImportController extends Controller
{
    public function index()
    {
        return view('upload');
    }

    public function upload(Request $request)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '600');

        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls,csv|max:51200', // 50MB max
            'preview_limit' => 'nullable|in:50,100,200,500',
        ]);

        $limit = (int) $request->input('preview_limit', 100);
        $file = $request->file('excel_file');
        
        // Cleanup old CSV/XLSX files older than 15 minutes to save space
        $files = Storage::disk('local')->files('uploads');
        $now = time();
        foreach ($files as $f) {
            if ($now - Storage::disk('local')->lastModified($f) > 900) { // 900 seconds = 15 minutes
                Storage::disk('local')->delete($f);
            }
        }

        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        // Replace spaces or invalid chars with underscores to be safe for filenames
        $baseName = preg_replace('/[^A-Za-z0-9\-\_]/', '_', $baseName);
        $uniqueBase = $baseName . '_' . time();
        
        $xlsxName = $uniqueBase . '.xlsx';
        $path = $file->storeAs('uploads', $xlsxName, 'local');
        $fullPath = Storage::disk('local')->path($path);

        // Convert XLSX to CSV immediately, separating by sheet
        if ($xlsx = SimpleXLSX::parse($fullPath)) {
            
            foreach ($xlsx->sheetNames() as $sheetIndex => $sheetName) {
                // sanitize sheetname
                $safeSheetName = preg_replace('/[^A-Za-z0-9\-\_]/', '_', $sheetName);
                $csvName = $uniqueBase . '_Sh_' . $safeSheetName . '.csv';
                
                $csvFullPath = Storage::disk('local')->path('uploads/' . $csvName);
                $fp = fopen($csvFullPath, 'w');
                fputs($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
                
                $dim = $xlsx->dimension($sheetIndex);
                $cols = $dim[0] ?? 0;
                
                // Get header length from first row as fallback
                $firstRow = null;
                
                foreach ($xlsx->readRows($sheetIndex) as $r) {
                    if ($firstRow === null) {
                        $firstRow = $r;
                        $cols = max($cols, count($r));
                    }
                    if (count($r) < $cols) {
                        $r = array_pad($r, $cols, '');
                    }
                    fputcsv($fp, $r);
                }
                fclose($fp);
            }
            
            // Delete the original XLSX to save space
            Storage::disk('local')->delete($path);
        } else {
            return back()->withErrors(['error' => 'ไม่สามารถอ่านไฟล์ Excel ได้: ' . SimpleXLSX::parseError()]);
        }

        // Redirect to preview GET route with the base filename
        return redirect()->route('preview', ['base_filename' => $uniqueBase, 'limit' => $limit]);
    }

    public function preview(Request $request, $base_filename)
    {
        $limit = (int) $request->input('limit', 100);
        $page = (int) $request->input('page', 1);
        $currentSheet = $request->input('sheet', '');
        
        // Find all CSVs belonging to this base_filename
        $allFiles = Storage::disk('local')->files('uploads');
        $availableSheets = [];
        $currentCsvName = '';
        
        foreach ($allFiles as $f) {
            $fName = basename($f);
            if (str_starts_with($fName, $base_filename . '_Sh_') && str_ends_with($fName, '.csv')) {
                // Extract sheet name
                $extractedSheet = str_replace([$base_filename . '_Sh_', '.csv'], '', $fName);
                $availableSheets[] = $extractedSheet;
                
                // Determine which CSV to load (default to first found if not specified)
                if ($currentSheet === $extractedSheet || ($currentSheet === '' && empty($currentCsvName))) {
                    $currentCsvName = $fName;
                    $currentSheet = $extractedSheet;
                }
            }
        }
        
        if (empty($currentCsvName)) {
            return redirect('/upload')->withErrors(['error' => 'ไม่พบข้อมูลไฟล์ หรือไฟล์หมดอายุ (เกิน 15 นาที)']);
        }
        
        $path = 'uploads/' . $currentCsvName;
        $csvFullPath = Storage::disk('local')->path($path);
        
        // Use SplFileObject for high-speed file reading
        $file = new SplFileObject($csvFullPath, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
        
        $headers = [];
        
        // Read header rows (row 0 and 1)
        $file->seek(0);
        if (!$file->eof()) $headers[] = $file->current();
        
        $file->next();
        if (!$file->eof()) $headers[] = $file->current();

        // Calculate total rows by seeking to the end of the file
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key() + 1;
        $totalDataRows = max(0, $totalLines - 2);

        $totalPages = max(1, (int) ceil($totalDataRows / $limit));
        $hasMore = $page < $totalPages;
        $offset = ($page - 1) * $limit;

        // Seek to the exact data offset (add 2 for headers)
        $file->seek($offset + 2);
        
        $previewRows = [];
        $count = 0;
        
        $headerCount = isset($headers[0]) ? count($headers[0]) : 0;
        
        while (!$file->eof() && $count < $limit) {
            $row = $file->current();
            // SplFileObject returns false or empty array on empty lines
            if ($row && (count($row) > 1 || (count($row) === 1 && $row[0] !== null))) {
                if ($headerCount > 0 && count($row) < $headerCount) {
                    $row = array_pad($row, $headerCount, '');
                }
                $previewRows[] = $row;
                $count++;
            }
            $file->next();
        }

        return view('upload', [
            'success' => "เปิดไฟล์ชีต {$currentSheet} เรียบร้อยแล้ว! (หน้าที่ {$page} จาก {$totalPages}) ",
            'headers' => $headers,
            'rows' => $previewRows,
            'base_filename' => $base_filename,
            'available_sheets' => $availableSheets,
            'current_sheet' => $currentSheet,
            'preview_limit' => $limit,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'has_more' => $hasMore,
            'total_data_rows' => $totalDataRows
        ]);
    }

    public function import(Request $request)
    {
        $baseFilename = $request->input('base_filename');
        $selectedSheets = $request->input('selected_sheets', []);
        
        if (!$baseFilename) {
            return redirect('/upload')->withErrors(['error' => 'ไม่พบไฟล์ โปรดอัปโหลดใหม่']);
        }

        if (empty($selectedSheets)) {
            return back()->withErrors(['error' => 'กรุณาเลือกอย่างน้อย 1 Sheet เพื่อนำเข้าข้อมูล']);
        }

        $allFiles = Storage::disk('local')->files('uploads');
        $csvPaths = [];
        
        foreach ($allFiles as $f) {
            $fName = basename($f);
            if (str_starts_with($fName, $baseFilename . '_Sh_') && str_ends_with($fName, '.csv')) {
                $extractedSheet = str_replace([$baseFilename . '_Sh_', '.csv'], '', $fName);
                
                // Only process files for the selected sheets
                if (in_array($extractedSheet, $selectedSheets)) {
                    $csvPaths[] = Storage::disk('local')->path($f);
                }
            }
        }
        
        if (empty($csvPaths)) {
            return back()->withErrors(['error' => 'ไม่พบข้อมูลให้ดำเนินการ หรือไฟล์หมดอายุ']);
        }

        $totalInserted = 0;
        $skippedSheets = [];
        
        foreach ($csvPaths as $csvPath) {
            $fName = basename($csvPath);
            $extractedSheet = str_replace([$baseFilename . '_Sh_', '.csv'], '', $fName);
            
            $file = new \SplFileObject($csvPath, 'r');
            $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::READ_AHEAD | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
            
            // Read first line to detect format
            $file->seek(0);
            $headerRow1 = $file->current();
            
            $tableName = null;
            
            // Auto-detect logic
            if ($headerRow1 && is_array($headerRow1) && count($headerRow1) > 5) {
                $h0 = trim($headerRow1[0], "\xEF\xBB\xBF \t\n\r\0\x0B");
                $h1 = trim($headerRow1[1] ?? '');
                
                // Check if it's Sheet U: Starts with No,Date,Time
                if ($h0 === 'No' && $h1 === 'Date') {
                    $tableName = 'tire_test_data_u';
                } 
                // Check if it's Sheet D: Starts with multiple empties and has "Total Rank" around index 5
                elseif (
                    empty($h0) && empty($h1) && 
                    (in_array('Total Rank', $headerRow1, true) || in_array('Total Rank ', $headerRow1, true) || in_array('"Total Rank"', $headerRow1, true))
                ) {
                    $tableName = 'tire_test_data_d';
                }
            }
            
            if (!$tableName) {
                // Cannot detect format, skip this sheet
                $skippedSheets[] = $extractedSheet;
                continue;
            }
            
            // Skip headers
            $file->seek(2);
            
            $batch = [];
            $batchSize = 250;
            
            // Get table columns and exclude id, timestamps
            $dbColumns = \Illuminate\Support\Facades\Schema::getColumnListing($tableName);
            $dbColumns = array_values(array_filter($dbColumns, fn($c) => !in_array($c, ['id', 'created_at', 'updated_at'])));
            $colCount = count($dbColumns);
            
            while (!$file->eof()) {
                $row = $file->current();
                if ($row && (count($row) > 1 || (count($row) === 1 && $row[0] !== null))) {
                    // Match row length to dbColumns length
                    if (count($row) > $colCount) {
                        $row = array_slice($row, 0, $colCount);
                    } elseif (count($row) < $colCount) {
                        $row = array_pad($row, $colCount, null);
                    }
                    
                    $data = array_combine($dbColumns, $row);
                    $data['created_at'] = now();
                    $data['updated_at'] = now();
                    
                    $batch[] = $data;
                    $totalInserted++;
                    
                    if (count($batch) >= $batchSize) {
                        \Illuminate\Support\Facades\DB::table($tableName)->insert($batch);
                        $batch = [];
                    }
                }
                $file->next();
            }
            
            // Insert remaining rows
            if (!empty($batch)) {
                \Illuminate\Support\Facades\DB::table($tableName)->insert($batch);
            }
        }

        $sheetNames = implode(', ', $selectedSheets);
        $message = "นำเข้าข้อมูลจากชีต [{$sheetNames}] จำนวนรวม {$totalInserted} แถว เข้าสู่ฐานข้อมูลจริงเรียบร้อยแล้ว!";
        if (!empty($skippedSheets)) {
            $skippedNames = implode(', ', $skippedSheets);
            $message .= " (ข้ามชีตที่ฟอร์แมตไม่ถูกต้อง: {$skippedNames})";
        }

        return back()->with('success', $message);
    }
}
