<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Shuchkin\SimpleXLSX;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use SplFileObject;
use App\Models\TireTestDataD;
class ExcelImportController extends Controller
{
    /**
     * Show the upload page.
     */
    public function index()
    {
        return view('upload');
    }

    /**
     * Handle file upload: convert XLSX → per-sheet CSVs, then redirect to preview.
     */
    public function upload(Request $request)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '600');

        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls,csv|max:51200',
            'preview_limit' => 'nullable|in:50,100,200,500',
        ]);

        $limit = (int) $request->input('preview_limit', 100);
        $file = $request->file('excel_file');

        // Cleanup old files older than 15 minutes
        $this->cleanupOldFiles();

        $baseName = preg_replace('/[^A-Za-z0-9\-\_]/', '_', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $uniqueBase = $baseName . '_' . time();

        $xlsxName = $uniqueBase . '.xlsx';
        $path = $file->storeAs('uploads', $xlsxName, 'local');
        $fullPath = Storage::disk('local')->path($path);

        // Parse XLSX and write each sheet as a separate CSV
        if ($xlsx = SimpleXLSX::parse($fullPath)) {
            foreach ($xlsx->sheetNames() as $sheetIndex => $sheetName) {
                $safeSheetName = preg_replace('/[^A-Za-z0-9\-\_]/', '_', $sheetName);
                $csvName = $uniqueBase . '_Sh_' . $safeSheetName . '.csv';
                $csvFullPath = Storage::disk('local')->path('uploads/' . $csvName);

                // First pass: read all rows and find the real last non-empty column
                $allRows = [];
                $maxUsedCol = 0;

                foreach ($xlsx->readRows($sheetIndex) as $r) {
                    $allRows[] = $r;
                    // Find rightmost non-empty cell in this row
                    for ($i = count($r) - 1; $i >= 0; $i--) {
                        if (isset($r[$i]) && trim((string)$r[$i]) !== '') {
                            $maxUsedCol = max($maxUsedCol, $i + 1);
                            break;
                        }
                    }
                }

                // Second pass: write trimmed rows to CSV
                $fp = fopen($csvFullPath, 'w');
                fputs($fp, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

                foreach ($allRows as $r) {
                    // Trim to actual data width
                    $r = array_slice($r, 0, $maxUsedCol);
                    // Pad short rows to match
                    if (count($r) < $maxUsedCol) {
                        $r = array_pad($r, $maxUsedCol, '');
                    }
                    fputcsv($fp, $r);
                }
                fclose($fp);
            }
            Storage::disk('local')->delete($path);
        } else {
            return back()->withErrors(['error' => 'ไม่สามารถอ่านไฟล์ Excel ได้: ' . SimpleXLSX::parseError()]);
        }

        return redirect()->route('preview', ['base_filename' => $uniqueBase, 'limit' => $limit]);
    }

    /**
     * Preview CSV data with pagination and sheet tabs.
     */
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
                $extractedSheet = str_replace([$base_filename . '_Sh_', '.csv'], '', $fName);
                $availableSheets[] = $extractedSheet;

                if ($currentSheet === $extractedSheet || ($currentSheet === '' && empty($currentCsvName))) {
                    $currentCsvName = $fName;
                    $currentSheet = $extractedSheet;
                }
            }
        }

        if (empty($currentCsvName)) {
            return redirect('/upload')->withErrors(['error' => 'ไม่พบข้อมูลไฟล์ หรือไฟล์หมดอายุ (เกิน 15 นาที)']);
        }

        $csvFullPath = Storage::disk('local')->path('uploads/' . $currentCsvName);
        $file = new SplFileObject($csvFullPath, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        // Read header rows (row 0 and 1)
        $headers = [];
        $file->seek(0);
        if (!$file->eof()) $headers[] = $file->current();
        $file->next();
        if (!$file->eof()) $headers[] = $file->current();

        // Count total data rows
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key() + 1;
        $totalDataRows = max(0, $totalLines - 2);
        $totalPages = max(1, (int) ceil($totalDataRows / $limit));
        $hasMore = $page < $totalPages;
        $offset = ($page - 1) * $limit;

        // Seek to the data offset (skip 2 header rows)
        $file->seek($offset + 2);

        $previewRows = [];
        $count = 0;
        $headerCount = isset($headers[0]) ? count($headers[0]) : 0;

        while (!$file->eof() && $count < $limit) {
            $row = $file->current();
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
            'total_data_rows' => $totalDataRows,
        ]);
    }

    /**
     * Import data to database based on sheet name.
     */
    public function import(Request $request)
    {
        $base_filename = $request->input('base_filename');
        $sheet = $request->input('sheet');
        
        if (empty($base_filename) || empty($sheet)) {
            return back()->withErrors(['error' => 'Invalid parameters for import.']);
        }

        if ($sheet === 'D') {
            return $this->importSheetD($base_filename);
        }

        return back()->withErrors(['error' => "Import for sheet '{$sheet}' is not implemented yet."]);
    }

    /**
     * Import logic specifically for Sheet D
     */
    private function importSheetD($base_filename)
    {
        $csvName = $base_filename . '_Sh_D.csv';
        if (!Storage::disk('local')->exists('uploads/' . $csvName)) {
            return back()->withErrors(['error' => 'CSV file for Sheet D not found.']);
        }

        $csvFullPath = Storage::disk('local')->path('uploads/' . $csvName);
        $file = new SplFileObject($csvFullPath, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        // Skip headers (Row 0 and 1)
        $file->seek(2);
        
        $batchSize = 500;
        $batch = [];
        $importedCount = 0;

        while (!$file->eof()) {
            $row = $file->current();
            
            // Check if row has enough data and an ID
            if ($row && isset($row[0]) && is_numeric($row[0])) {
                $id = (int) $row[0];
                
                // Parse date (e.g., "2026/ 4/24" -> "2026-04-24")
                $rawDate = trim($row[1]);
                $testDate = null;
                if ($rawDate) {
                    $rawDate = str_replace(' ', '', $rawDate); // "2026/4/24"
                    $parts = explode('/', $rawDate);
                    if (count($parts) === 3) {
                        $testDate = sprintf('%04d-%02d-%02d', $parts[0], $parts[1], $parts[2]);
                    }
                }

                $batch[] = [
                    'id'          => $id,
                    'test_date'   => $testDate,
                    'test_time'   => trim($row[2] ?? ''),
                    'shift'       => trim($row[3] ?? ''),
                    'model_no'    => trim($row[4] ?? ''),
                    // As per header CSV, index 5 is Total Rank
                    'total_rank'  => trim($row[5] ?? ''),
                    
                    'upper_val'   => isset($row[6]) && is_numeric($row[6]) ? (float) $row[6] : null,
                    'upper_deg'   => isset($row[7]) && is_numeric($row[7]) ? (float) $row[7] : null,
                    'upper_rank'  => trim($row[8] ?? ''),
                    
                    'lower_val'   => isset($row[9]) && is_numeric($row[9]) ? (float) $row[9] : null,
                    'lower_deg'   => isset($row[10]) && is_numeric($row[10]) ? (float) $row[10] : null,
                    'lower_rank'  => trim($row[11] ?? ''),
                    
                    'up_lo_val'   => isset($row[12]) && is_numeric($row[12]) ? (float) $row[12] : null,
                    'up_lo_rank'  => trim($row[13] ?? ''),
                    
                    'static_val'  => isset($row[14]) && is_numeric($row[14]) ? (float) $row[14] : null,
                    'static_deg'  => isset($row[15]) && is_numeric($row[15]) ? (float) $row[15] : null,
                    'static_rank' => trim($row[16] ?? ''),
                    
                    'couple_val'  => isset($row[17]) && is_numeric($row[17]) ? (float) $row[17] : null,
                    'couple_deg'  => isset($row[18]) && is_numeric($row[18]) ? (float) $row[18] : null,
                    'couple_rank' => trim($row[19] ?? ''),
                    
                    // We assume size_code is null for now unless provided from elsewhere
                    'size_code'   => null,
                ];

                if (count($batch) >= $batchSize) {
                    TireTestDataD::upsert($batch, ['id']);
                    $importedCount += count($batch);
                    $batch = [];
                }
            }
            $file->next();
        }

        // Insert remaining
        if (count($batch) > 0) {
            TireTestDataD::upsert($batch, ['id']);
            $importedCount += count($batch);
        }

        return redirect()->route('preview', [
            'base_filename' => $base_filename, 
            'limit' => request('limit', 100), 
            'page' => request('page', 1),
            'sheet' => 'D'
        ])->with('success', "นำเข้าข้อมูล Sheet D จำนวน {$importedCount} รายการ สำเร็จ!");
    }

    /**
     * Delete uploaded files older than 15 minutes.
     */
    private function cleanupOldFiles(): void
    {
        $files = Storage::disk('local')->files('uploads');
        $now = time();
        foreach ($files as $f) {
            if ($now - Storage::disk('local')->lastModified($f) > 900) {
                Storage::disk('local')->delete($f);
            }
        }
    }
}
