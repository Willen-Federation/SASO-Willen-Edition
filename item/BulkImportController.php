<?php
namespace saso\item;

use saso\framework\Controller;
use saso\framework\DTO;
use saso\framework\Input;

final class BulkImportController implements Controller
{
    use Input;

    private DTO $data;

    public function __construct(
        string $tmpFilePath,
        \DateTime $now,
    ) {
        $rows = [];
        $handle = fopen($tmpFilePath, 'r');
        if ($handle !== false) {
            $headers = null;
            while (($line = fgetcsv($handle)) !== false) {
                if ($headers === null) {
                    $headers = array_map('trim', $line);
                    if (!empty($headers[0]) && str_starts_with($headers[0], "\xEF\xBB\xBF")) {
                        $headers[0] = substr($headers[0], 3);
                    }
                    continue;
                }
                if (!empty(array_filter($line))) {
                    $rows[] = array_combine(
                        $headers,
                        array_pad($line, count($headers), '')
                    );
                }
            }
            fclose($handle);
        }
        $this->data = new BulkImportInputData($rows, $now);
    }
}
