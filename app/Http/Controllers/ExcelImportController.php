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

        if ($sheet === 'U') {
            return $this->importSheetU($base_filename);
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
                    $rawDate = str_replace(' ', '', $rawDate);
                    $separator = strpos($rawDate, '-') !== false ? '-' : '/';
                    $parts = explode($separator, $rawDate);
                    if (count($parts) === 3) {
                        $testDate = sprintf('%04d-%02d-%02d', $parts[0], $parts[1], $parts[2]);
                    }
                }

                $batch[] = [
                    'id'          => $id,
                    'test_date'   => $testDate,
                    'test_time'   => trim($row[2] ?? ''),
                    'model_no'    => trim($row[3] ?? ''),
                    'size_code'   => trim($row[4] ?? ''),
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
                    'num'         => trim($row[20] ?? ''),
                ];

                if (count($batch) >= $batchSize) {
                    TireTestDataD::insert($batch);
                    $importedCount += count($batch);
                    $batch = [];
                }
            }
            $file->next();
        }

        // Insert remaining
        if (count($batch) > 0) {
            TireTestDataD::insert($batch);
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

    /**
     * Import logic specifically for Sheet U
     */
    private function importSheetU($base_filename)
    {
        $csvName = $base_filename . '_Sh_U.csv';
        if (!Storage::disk('local')->exists('uploads/' . $csvName)) {
            return back()->withErrors(['error' => 'CSV file for Sheet U not found.']);
        }

        $csvFullPath = Storage::disk('local')->path('uploads/' . $csvName);
        $file = new SplFileObject($csvFullPath, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        // Skip headers (Row 0 and 1)
        $file->seek(2);
        
        $batchSize = 250;
        $batch = [];
        $importedCount = 0;

        while (!$file->eof()) {
            $row = $file->current();
            
            if ($row && isset($row[0]) && is_numeric($row[0])) {
                $id = (int) $row[0];
                
                $rawDate = trim($row[1]);
                $testDate = null;
                if ($rawDate) {
                    $rawDate = str_replace(' ', '', $rawDate);
                    $separator = strpos($rawDate, '-') !== false ? '-' : '/';
                    $parts = explode($separator, $rawDate);
                    if (count($parts) === 3) {
                        $testDate = sprintf('%04d-%02d-%02d', $parts[0], $parts[1], $parts[2]);
                    }
                }

                $mapped = [
                    'id'          => $id,
                    'test_date'   => $testDate,
                    'test_time'   => trim($row[2] ?? ''),
                    'shift'       => trim($row[3] ?? ''),
                    'model_no'    => trim($row[4] ?? ''),
                    'size_code'   => trim($row[5] ?? ''),
                    'barcode'     => trim($row[6] ?? ''),
                    'bead_dia'    => isset($row[7]) && is_numeric($row[7]) ? (float) $row[7] : null,
                    'air_press'   => isset($row[8]) && is_numeric($row[8]) ? (float) $row[8] : null,
                    'load_kgf'    => isset($row[9]) && is_numeric($row[9]) ? (float) $row[9] : null,
                    'cw_rfv_oa_val' => isset($row[10]) && is_numeric($row[10]) ? (float) $row[10] : null, 'cw_rfv_oa_deg' => isset($row[11]) && is_numeric($row[11]) ? (float) $row[11] : null, 'cw_rfv_oa_rank' => trim($row[12] ?? ''),
                    'cw_rfv_1h_val' => isset($row[13]) && is_numeric($row[13]) ? (float) $row[13] : null, 'cw_rfv_1h_deg' => isset($row[14]) && is_numeric($row[14]) ? (float) $row[14] : null, 'cw_rfv_1h_rank' => trim($row[15] ?? ''),
                    'cw_rfv_2h_val' => isset($row[16]) && is_numeric($row[16]) ? (float) $row[16] : null, 'cw_rfv_2h_deg' => isset($row[17]) && is_numeric($row[17]) ? (float) $row[17] : null, 'cw_rfv_2h_rank' => trim($row[18] ?? ''),
                    'cw_rfv_3h_val' => isset($row[19]) && is_numeric($row[19]) ? (float) $row[19] : null, 'cw_rfv_3h_deg' => isset($row[20]) && is_numeric($row[20]) ? (float) $row[20] : null, 'cw_rfv_3h_rank' => trim($row[21] ?? ''),
                    'cw_rfv_4h_val' => isset($row[22]) && is_numeric($row[22]) ? (float) $row[22] : null, 'cw_rfv_4h_deg' => isset($row[23]) && is_numeric($row[23]) ? (float) $row[23] : null, 'cw_rfv_4h_rank' => trim($row[24] ?? ''),
                    'cw_rfv_5h_val' => isset($row[25]) && is_numeric($row[25]) ? (float) $row[25] : null, 'cw_rfv_5h_deg' => isset($row[26]) && is_numeric($row[26]) ? (float) $row[26] : null, 'cw_rfv_5h_rank' => trim($row[27] ?? ''),
                    'cw_rfv_6h_val' => isset($row[28]) && is_numeric($row[28]) ? (float) $row[28] : null, 'cw_rfv_6h_deg' => isset($row[29]) && is_numeric($row[29]) ? (float) $row[29] : null, 'cw_rfv_6h_rank' => trim($row[30] ?? ''),
                    'cw_rfv_7h_val' => isset($row[31]) && is_numeric($row[31]) ? (float) $row[31] : null, 'cw_rfv_7h_deg' => isset($row[32]) && is_numeric($row[32]) ? (float) $row[32] : null, 'cw_rfv_7h_rank' => trim($row[33] ?? ''),
                    'cw_rfv_8h_val' => isset($row[34]) && is_numeric($row[34]) ? (float) $row[34] : null, 'cw_rfv_8h_deg' => isset($row[35]) && is_numeric($row[35]) ? (float) $row[35] : null, 'cw_rfv_8h_rank' => trim($row[36] ?? ''),
                    'cw_rfv_9h_val' => isset($row[37]) && is_numeric($row[37]) ? (float) $row[37] : null, 'cw_rfv_9h_deg' => isset($row[38]) && is_numeric($row[38]) ? (float) $row[38] : null, 'cw_rfv_9h_rank' => trim($row[39] ?? ''),
                    'cw_rfv_10h_val' => isset($row[40]) && is_numeric($row[40]) ? (float) $row[40] : null, 'cw_rfv_10h_deg' => isset($row[41]) && is_numeric($row[41]) ? (float) $row[41] : null, 'cw_rfv_10h_rank' => trim($row[42] ?? ''),
                    'cw_lfv_oa_val' => isset($row[43]) && is_numeric($row[43]) ? (float) $row[43] : null, 'cw_lfv_oa_deg' => isset($row[44]) && is_numeric($row[44]) ? (float) $row[44] : null, 'cw_lfv_oa_rank' => trim($row[45] ?? ''),
                    'cw_lfv_1h_val' => isset($row[46]) && is_numeric($row[46]) ? (float) $row[46] : null, 'cw_lfv_1h_deg' => isset($row[47]) && is_numeric($row[47]) ? (float) $row[47] : null, 'cw_lfv_1h_rank' => trim($row[48] ?? ''),
                    'cw_lfv_2h_val' => isset($row[49]) && is_numeric($row[49]) ? (float) $row[49] : null, 'cw_lfv_2h_deg' => isset($row[50]) && is_numeric($row[50]) ? (float) $row[50] : null, 'cw_lfv_2h_rank' => trim($row[51] ?? ''),
                    'cw_lfv_3h_val' => isset($row[52]) && is_numeric($row[52]) ? (float) $row[52] : null, 'cw_lfv_3h_deg' => isset($row[53]) && is_numeric($row[53]) ? (float) $row[53] : null, 'cw_lfv_3h_rank' => trim($row[54] ?? ''),
                    'cw_lfv_4h_val' => isset($row[55]) && is_numeric($row[55]) ? (float) $row[55] : null, 'cw_lfv_4h_deg' => isset($row[56]) && is_numeric($row[56]) ? (float) $row[56] : null, 'cw_lfv_4h_rank' => trim($row[57] ?? ''),
                    'cw_lfv_5h_val' => isset($row[58]) && is_numeric($row[58]) ? (float) $row[58] : null, 'cw_lfv_5h_deg' => isset($row[59]) && is_numeric($row[59]) ? (float) $row[59] : null, 'cw_lfv_5h_rank' => trim($row[60] ?? ''),
                    'cw_lfv_6h_val' => isset($row[61]) && is_numeric($row[61]) ? (float) $row[61] : null, 'cw_lfv_6h_deg' => isset($row[62]) && is_numeric($row[62]) ? (float) $row[62] : null, 'cw_lfv_6h_rank' => trim($row[63] ?? ''),
                    'cw_lfv_7h_val' => isset($row[64]) && is_numeric($row[64]) ? (float) $row[64] : null, 'cw_lfv_7h_deg' => isset($row[65]) && is_numeric($row[65]) ? (float) $row[65] : null, 'cw_lfv_7h_rank' => trim($row[66] ?? ''),
                    'cw_lfv_8h_val' => isset($row[67]) && is_numeric($row[67]) ? (float) $row[67] : null, 'cw_lfv_8h_deg' => isset($row[68]) && is_numeric($row[68]) ? (float) $row[68] : null, 'cw_lfv_8h_rank' => trim($row[69] ?? ''),
                    'cw_lfv_9h_val' => isset($row[70]) && is_numeric($row[70]) ? (float) $row[70] : null, 'cw_lfv_9h_deg' => isset($row[71]) && is_numeric($row[71]) ? (float) $row[71] : null, 'cw_lfv_9h_rank' => trim($row[72] ?? ''),
                    'cw_lfv_10h_val' => isset($row[73]) && is_numeric($row[73]) ? (float) $row[73] : null, 'cw_lfv_10h_deg' => isset($row[74]) && is_numeric($row[74]) ? (float) $row[74] : null, 'cw_lfv_10h_rank' => trim($row[75] ?? ''),
                    'cw_lfd_val' => isset($row[76]) && is_numeric($row[76]) ? (float) $row[76] : null, 'cw_lfd_rank' => trim($row[77] ?? ''),
                    'ccw_rfv_oa_val' => isset($row[78]) && is_numeric($row[78]) ? (float) $row[78] : null, 'ccw_rfv_oa_deg' => isset($row[79]) && is_numeric($row[79]) ? (float) $row[79] : null, 'ccw_rfv_oa_rank' => trim($row[80] ?? ''),
                    'ccw_rfv_1h_val' => isset($row[81]) && is_numeric($row[81]) ? (float) $row[81] : null, 'ccw_rfv_1h_deg' => isset($row[82]) && is_numeric($row[82]) ? (float) $row[82] : null, 'ccw_rfv_1h_rank' => trim($row[83] ?? ''),
                    'ccw_rfv_2h_val' => isset($row[84]) && is_numeric($row[84]) ? (float) $row[84] : null, 'ccw_rfv_2h_deg' => isset($row[85]) && is_numeric($row[85]) ? (float) $row[85] : null, 'ccw_rfv_2h_rank' => trim($row[86] ?? ''),
                    'ccw_rfv_3h_val' => isset($row[87]) && is_numeric($row[87]) ? (float) $row[87] : null, 'ccw_rfv_3h_deg' => isset($row[88]) && is_numeric($row[88]) ? (float) $row[88] : null, 'ccw_rfv_3h_rank' => trim($row[89] ?? ''),
                    'ccw_rfv_4h_val' => isset($row[90]) && is_numeric($row[90]) ? (float) $row[90] : null, 'ccw_rfv_4h_deg' => isset($row[91]) && is_numeric($row[91]) ? (float) $row[91] : null, 'ccw_rfv_4h_rank' => trim($row[92] ?? ''),
                    'ccw_rfv_5h_val' => isset($row[93]) && is_numeric($row[93]) ? (float) $row[93] : null, 'ccw_rfv_5h_deg' => isset($row[94]) && is_numeric($row[94]) ? (float) $row[94] : null, 'ccw_rfv_5h_rank' => trim($row[95] ?? ''),
                    'ccw_rfv_6h_val' => isset($row[96]) && is_numeric($row[96]) ? (float) $row[96] : null, 'ccw_rfv_6h_deg' => isset($row[97]) && is_numeric($row[97]) ? (float) $row[97] : null, 'ccw_rfv_6h_rank' => trim($row[98] ?? ''),
                    'ccw_rfv_7h_val' => isset($row[99]) && is_numeric($row[99]) ? (float) $row[99] : null, 'ccw_rfv_7h_deg' => isset($row[100]) && is_numeric($row[100]) ? (float) $row[100] : null, 'ccw_rfv_7h_rank' => trim($row[101] ?? ''),
                    'ccw_rfv_8h_val' => isset($row[102]) && is_numeric($row[102]) ? (float) $row[102] : null, 'ccw_rfv_8h_deg' => isset($row[103]) && is_numeric($row[103]) ? (float) $row[103] : null, 'ccw_rfv_8h_rank' => trim($row[104] ?? ''),
                    'ccw_rfv_9h_val' => isset($row[105]) && is_numeric($row[105]) ? (float) $row[105] : null, 'ccw_rfv_9h_deg' => isset($row[106]) && is_numeric($row[106]) ? (float) $row[106] : null, 'ccw_rfv_9h_rank' => trim($row[107] ?? ''),
                    'ccw_rfv_10h_val' => isset($row[108]) && is_numeric($row[108]) ? (float) $row[108] : null, 'ccw_rfv_10h_deg' => isset($row[109]) && is_numeric($row[109]) ? (float) $row[109] : null, 'ccw_rfv_10h_rank' => trim($row[110] ?? ''),
                    'ccw_lfv_oa_val' => isset($row[111]) && is_numeric($row[111]) ? (float) $row[111] : null, 'ccw_lfv_oa_deg' => isset($row[112]) && is_numeric($row[112]) ? (float) $row[112] : null, 'ccw_lfv_oa_rank' => trim($row[113] ?? ''),
                    'ccw_lfv_1h_val' => isset($row[114]) && is_numeric($row[114]) ? (float) $row[114] : null, 'ccw_lfv_1h_deg' => isset($row[115]) && is_numeric($row[115]) ? (float) $row[115] : null, 'ccw_lfv_1h_rank' => trim($row[116] ?? ''),
                    'ccw_lfv_2h_val' => isset($row[117]) && is_numeric($row[117]) ? (float) $row[117] : null, 'ccw_lfv_2h_deg' => isset($row[118]) && is_numeric($row[118]) ? (float) $row[118] : null, 'ccw_lfv_2h_rank' => trim($row[119] ?? ''),
                    'ccw_lfv_3h_val' => isset($row[120]) && is_numeric($row[120]) ? (float) $row[120] : null, 'ccw_lfv_3h_deg' => isset($row[121]) && is_numeric($row[121]) ? (float) $row[121] : null, 'ccw_lfv_3h_rank' => trim($row[122] ?? ''),
                    'ccw_lfv_4h_val' => isset($row[123]) && is_numeric($row[123]) ? (float) $row[123] : null, 'ccw_lfv_4h_deg' => isset($row[124]) && is_numeric($row[124]) ? (float) $row[124] : null, 'ccw_lfv_4h_rank' => trim($row[125] ?? ''),
                    'ccw_lfv_5h_val' => isset($row[126]) && is_numeric($row[126]) ? (float) $row[126] : null, 'ccw_lfv_5h_deg' => isset($row[127]) && is_numeric($row[127]) ? (float) $row[127] : null, 'ccw_lfv_5h_rank' => trim($row[128] ?? ''),
                    'ccw_lfv_6h_val' => isset($row[129]) && is_numeric($row[129]) ? (float) $row[129] : null, 'ccw_lfv_6h_deg' => isset($row[130]) && is_numeric($row[130]) ? (float) $row[130] : null, 'ccw_lfv_6h_rank' => trim($row[131] ?? ''),
                    'ccw_lfv_7h_val' => isset($row[132]) && is_numeric($row[132]) ? (float) $row[132] : null, 'ccw_lfv_7h_deg' => isset($row[133]) && is_numeric($row[133]) ? (float) $row[133] : null, 'ccw_lfv_7h_rank' => trim($row[134] ?? ''),
                    'ccw_lfv_8h_val' => isset($row[135]) && is_numeric($row[135]) ? (float) $row[135] : null, 'ccw_lfv_8h_deg' => isset($row[136]) && is_numeric($row[136]) ? (float) $row[136] : null, 'ccw_lfv_8h_rank' => trim($row[137] ?? ''),
                    'ccw_lfv_9h_val' => isset($row[138]) && is_numeric($row[138]) ? (float) $row[138] : null, 'ccw_lfv_9h_deg' => isset($row[139]) && is_numeric($row[139]) ? (float) $row[139] : null, 'ccw_lfv_9h_rank' => trim($row[140] ?? ''),
                    'ccw_lfv_10h_val' => isset($row[141]) && is_numeric($row[141]) ? (float) $row[141] : null, 'ccw_lfv_10h_deg' => isset($row[142]) && is_numeric($row[142]) ? (float) $row[142] : null, 'ccw_lfv_10h_rank' => trim($row[143] ?? ''),
                    'ccw_lfd_val' => isset($row[144]) && is_numeric($row[144]) ? (float) $row[144] : null, 'ccw_lfd_rank' => trim($row[145] ?? ''),
                    'con_val' => isset($row[146]) && is_numeric($row[146]) ? (float) $row[146] : null, 'con_rank' => trim($row[147] ?? ''),
                    'ply_val' => isset($row[148]) && is_numeric($row[148]) ? (float) $row[148] : null, 'ply_rank' => trim($row[149] ?? ''),
                    'ufm_rank' => trim($row[150] ?? ''),
                    'lt_oa_val' => isset($row[151]) && is_numeric($row[151]) ? (float) $row[151] : null, 'lt_oa_deg' => isset($row[152]) && is_numeric($row[152]) ? (float) $row[152] : null, 'lt_oa_rank' => trim($row[153] ?? ''),
                    'lt_1h_val' => isset($row[154]) && is_numeric($row[154]) ? (float) $row[154] : null, 'lt_1h_deg' => isset($row[155]) && is_numeric($row[155]) ? (float) $row[155] : null, 'lt_1h_rank' => trim($row[156] ?? ''),
                    'lb_oa_val' => isset($row[157]) && is_numeric($row[157]) ? (float) $row[157] : null, 'lb_oa_deg' => isset($row[158]) && is_numeric($row[158]) ? (float) $row[158] : null, 'lb_oa_rank' => trim($row[159] ?? ''),
                    'lb_1h_val' => isset($row[160]) && is_numeric($row[160]) ? (float) $row[160] : null, 'lb_1h_deg' => isset($row[161]) && is_numeric($row[161]) ? (float) $row[161] : null, 'lb_1h_rank' => trim($row[162] ?? ''),
                    'rt_oa_val' => isset($row[163]) && is_numeric($row[163]) ? (float) $row[163] : null, 'rt_oa_deg' => isset($row[164]) && is_numeric($row[164]) ? (float) $row[164] : null, 'rt_oa_rank' => trim($row[165] ?? ''),
                    'rt_1h_val' => isset($row[166]) && is_numeric($row[166]) ? (float) $row[166] : null, 'rt_1h_deg' => isset($row[167]) && is_numeric($row[167]) ? (float) $row[167] : null, 'rt_1h_rank' => trim($row[168] ?? ''),
                    'rc_oa_val' => isset($row[169]) && is_numeric($row[169]) ? (float) $row[169] : null, 'rc_oa_deg' => isset($row[170]) && is_numeric($row[170]) ? (float) $row[170] : null, 'rc_oa_rank' => trim($row[171] ?? ''),
                    'rc_1h_val' => isset($row[172]) && is_numeric($row[172]) ? (float) $row[172] : null, 'rc_1h_deg' => isset($row[173]) && is_numeric($row[173]) ? (float) $row[173] : null, 'rc_1h_rank' => trim($row[174] ?? ''),
                    'rb_oa_val' => isset($row[175]) && is_numeric($row[175]) ? (float) $row[175] : null, 'rb_oa_deg' => isset($row[176]) && is_numeric($row[176]) ? (float) $row[176] : null, 'rb_oa_rank' => trim($row[177] ?? ''),
                    'rb_1h_val' => isset($row[178]) && is_numeric($row[178]) ? (float) $row[178] : null, 'rb_1h_deg' => isset($row[179]) && is_numeric($row[179]) ? (float) $row[179] : null, 'rb_1h_rank' => trim($row[180] ?? ''),
                    'lt_bulg_val' => isset($row[181]) && is_numeric($row[181]) ? (float) $row[181] : null, 'lt_bulg_deg' => isset($row[182]) && is_numeric($row[182]) ? (float) $row[182] : null, 'lt_bulg_rank' => trim($row[183] ?? ''),
                    'lt_dent_val' => isset($row[184]) && is_numeric($row[184]) ? (float) $row[184] : null, 'lt_dent_deg' => isset($row[185]) && is_numeric($row[185]) ? (float) $row[185] : null, 'lt_dent_rank' => trim($row[186] ?? ''),
                    'lb_bulg_val' => isset($row[187]) && is_numeric($row[187]) ? (float) $row[187] : null, 'lb_bulg_deg' => isset($row[188]) && is_numeric($row[188]) ? (float) $row[188] : null, 'lb_bulg_rank' => trim($row[189] ?? ''),
                    'lb_dent_val' => isset($row[190]) && is_numeric($row[190]) ? (float) $row[190] : null, 'lb_dent_deg' => isset($row[191]) && is_numeric($row[191]) ? (float) $row[191] : null, 'lb_dent_rank' => trim($row[192] ?? ''),
                    'ro_rank' => trim($row[193] ?? ''),
                    'upper_val' => isset($row[194]) && is_numeric($row[194]) ? (float) $row[194] : null, 'upper_deg' => isset($row[195]) && is_numeric($row[195]) ? (float) $row[195] : null, 'upper_rank' => trim($row[196] ?? ''),
                    'lower_val' => isset($row[197]) && is_numeric($row[197]) ? (float) $row[197] : null, 'lower_deg' => isset($row[198]) && is_numeric($row[198]) ? (float) $row[198] : null, 'lower_rank' => trim($row[199] ?? ''),
                    'static_val' => isset($row[200]) && is_numeric($row[200]) ? (float) $row[200] : null, 'static_deg' => isset($row[201]) && is_numeric($row[201]) ? (float) $row[201] : null, 'static_rank' => trim($row[202] ?? ''),
                    'couple_val' => isset($row[203]) && is_numeric($row[203]) ? (float) $row[203] : null, 'couple_deg' => isset($row[204]) && is_numeric($row[204]) ? (float) $row[204] : null, 'couple_rank' => trim($row[205] ?? ''),
                    'up_low_val' => isset($row[206]) && is_numeric($row[206]) ? (float) $row[206] : null, 'up_low_rank' => trim($row[207] ?? ''),
                    'bal_rank' => trim($row[208] ?? ''),
                    'total_rank' => trim($row[209] ?? ''),
                ];
                $batch[] = $mapped;

                if (count($batch) >= $batchSize) {
                    \App\Models\TireTestDataU::insert($batch);
                    $importedCount += count($batch);
                    $batch = [];
                }
            }
            $file->next();
        }

        if (count($batch) > 0) {
            \App\Models\TireTestDataU::insert($batch);
            $importedCount += count($batch);
        }

        return redirect()->route('preview', [
            'base_filename' => $base_filename, 
            'limit' => request('limit', 100), 
            'page' => request('page', 1),
            'sheet' => 'U'
        ])->with('success', "นำเข้าข้อมูล Sheet U จำนวน {$importedCount} รายการ สำเร็จ!");
    }

}
