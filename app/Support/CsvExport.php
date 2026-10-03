<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a CSV download.
 *
 * Deliberately dependency-free: the admin screens only need plain tabular
 * exports, and pulling in a spreadsheet package for that would mean a
 * composer.lock change and a container rebuild for no gain.
 *
 * Streaming rather than building the file in memory keeps the footprint flat
 * as the booking table grows, and means nothing is written to disk — which
 * matters on Render, where the filesystem is ephemeral.
 */
class CsvExport
{
    /**
     * @param  string    $filename  Download name, e.g. "bookings-2026-10-03.csv"
     * @param  string[]  $headings  Column headers
     * @param  iterable  $rows      Each row an array matching $headings
     */
    public static function stream(string $filename, array $headings, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows) {
            $out = fopen('php://output', 'w');

            // Excel assumes the system codepage unless a UTF-8 BOM is present,
            // which mangles the peso sign and any accented customer name.
            fwrite($out, "\xEF\xBB\xBF");

            self::put($out, $headings);

            foreach ($rows as $row) {
                self::put($out, $row);
            }

            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Cache-Control'       => 'no-store, no-cache',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * An explicit empty escape character gives RFC 4180 output and avoids PHP's
     * legacy backslash escaping, which produces fields Excel reads back wrongly.
     */
    private static function put($handle, array $fields): void
    {
        fputcsv($handle, $fields, ',', '"', '');
    }

    /** Filename stamped with the date, e.g. "bookings-2026-10-03.csv". */
    public static function filename(string $prefix): string
    {
        return $prefix.'-'.now()->format('Y-m-d').'.csv';
    }
}
