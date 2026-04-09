<?php

use Illuminate\Contracts\Console\Kernel;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

class HeaderImport implements ToCollection
{
    public $headers;
    public $rows;

    public function collection(Collection $rows)
    {
        $this->headers = $rows->first()->toArray();
        $this->rows = $rows->slice(1, 5)->toArray();
    }
}

$file = 'storage/app/public/ListadoBalumBotan.xlsx';
if (!file_exists($file)) {
    echo "File not found: $file\n";
    exit(1);
}

$import = new HeaderImport();
Excel::import($import, $file);

echo "Headers:\n";
print_r($import->headers);
echo "\nSample Data Rows:\n";
foreach ($import->rows as $row) {
    print_r($row);
}
