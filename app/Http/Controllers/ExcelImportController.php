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

        $baseName = 'import_' . time();
        $xlsxName = $baseName . '.xlsx';
        $csvName = $baseName . '.csv';
        
        $path = $file->storeAs('uploads', $xlsxName, 'local');
        $fullPath = Storage::disk('local')->path($path);
        $csvFullPath = Storage::disk('local')->path('uploads/' . $csvName);

        // Convert XLSX to CSV immediately
        if ($xlsx = SimpleXLSX::parse($fullPath)) {
            $fp = fopen($csvFullPath, 'w');
            
            // Add UTF-8 BOM so Excel opens the CSV correctly if needed
            fputs($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
            
            $headerRow0 = null;
            $headerRow1 = null;

            foreach ($xlsx->sheetNames() as $sheetIndex => $sheetName) {
                $rowIndex = 0;
                foreach ($xlsx->readRows($sheetIndex) as $r) {
                    // Keep track of the first sheet's headers
                    if ($sheetIndex === 0) {
                        if ($rowIndex === 0) $headerRow0 = $r;
                        if ($rowIndex === 1) $headerRow1 = $r;
                    } else {
                        // For Sheet 2 onwards, if the first two rows are identical headers, skip them
                        if ($rowIndex === 0 && $r === $headerRow0) {
                            $rowIndex++;
                            continue;
                        }
                        if ($rowIndex === 1 && $r === $headerRow1) {
                            $rowIndex++;
                            continue;
                        }
                    }
                    
                    fputcsv($fp, $r);
                    $rowIndex++;
                }
            }
            fclose($fp);
            
            // Optionally delete the original XLSX to save space
            Storage::disk('local')->delete($path);
        } else {
            return back()->withErrors(['error' => 'ไม่สามารถอ่านไฟล์ Excel ได้: ' . SimpleXLSX::parseError()]);
        }

        // Redirect to preview GET route with the limit parameter
        return redirect()->route('preview', ['filename' => $csvName, 'limit' => $limit]);
    }

    public function preview(Request $request, $filename)
    {
        $limit = (int) $request->input('limit', 100);
        $page = (int) $request->input('page', 1);
        $path = 'uploads/' . $filename;
        
        if (!Storage::disk('local')->exists($path)) {
            return redirect('/upload')->withErrors(['error' => 'ไม่พบข้อมูลไฟล์ หรือไฟล์หมดอายุ']);
        }

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
        $totalLines = $file->key();
        
        // Actual data rows = total lines - 2 header rows
        $totalDataRows = max(0, $totalLines - 1); // $file->key() is 0-indexed line number of the last empty line, so minus 1 or 2 depending on trailing empty lines
        
        // A more reliable way to count lines for CSV:
        $file->rewind();
        $totalLines = 0;
        // SplFileObject::seek(PHP_INT_MAX) is fast but sometimes gives slightly off counts. 
        // Let's just use the max key.
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        $totalDataRows = max(0, $totalLines - 2);

        $totalPages = max(1, (int) ceil($totalDataRows / $limit));
        $hasMore = $page < $totalPages;
        $offset = ($page - 1) * $limit;

        // Seek to the exact data offset (add 2 for headers)
        $file->seek($offset + 2);
        
        $previewRows = [];
        $count = 0;
        
        while (!$file->eof() && $count < $limit) {
            $row = $file->current();
            // SplFileObject returns false or empty array on empty lines
            if ($row && (count($row) > 1 || (count($row) === 1 && $row[0] !== null))) {
                $previewRows[$file->key()] = $row;
                $count++;
            }
            $file->next();
        }

        return view('upload', [
            'success' => "ระบบอ่านข้อมูลจากไฟล์ CSV โดยตรง! (หน้าที่ {$page} จาก {$totalPages}) ลดภาระ Database 100% ⚡",
            'headers' => $headers,
            'rows' => $previewRows,
            'file_path' => $path,
            'filename' => $filename,
            'preview_limit' => $limit,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'has_more' => $hasMore,
            'total_data_rows' => $totalDataRows
        ]);
    }

    public function import(Request $request)
    {
        $path = $request->input('file_path');
        
        if (!$path || !Storage::disk('local')->exists($path)) {
            return redirect('/upload')->withErrors(['error' => 'ไม่พบไฟล์ โปรดอัปโหลดใหม่']);
        }

        $csvFullPath = Storage::disk('local')->path($path);
        
        $file = new SplFileObject($csvFullPath, 'r');
        $file->seek(PHP_INT_MAX);
        $totalDataRows = max(0, $file->key() - 2);

        if ($totalDataRows <= 0) {
            return back()->withErrors(['error' => 'ไม่พบข้อมูลให้ดำเนินการ หรือไฟล์ว่างเปล่า']);
        }

        return back()->with('success', "เตรียมนำเข้าข้อมูลทั้งหมดจำนวน {$totalDataRows} แถว สำเร็จ! (เตรียมเขียนโค้ดเพื่อ Insert ลงตารางจริงในสเตปต่อไป)");
    }
}
