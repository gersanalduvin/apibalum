<?php

use Illuminate\Contracts\Console\Kernel;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

class CountImport implements ToCollection
{
    public $totalRows = 0;
    public $rowsWithEmail = 0;
    public $uniqueEmails = [];

    public function collection(Collection $rows)
    {
        $this->totalRows = $rows->count();
        $headers = $rows->first();
        
        foreach ($rows->slice(1) as $row) {
            $email = trim($row[6] ?? '');
            if (!empty($email)) {
                $this->rowsWithEmail++;
                $this->uniqueEmails[$email] = true;
            }
        }
    }
}

$file = 'storage/app/public/ListadoBalumBotan.xlsx';
$import = new CountImport();
Excel::import($import, $file);

echo "Total rows (inc. header): " . $import->totalRows . PHP_EOL;
echo "Rows with email: " . $import->rowsWithEmail . PHP_EOL;
echo "Unique emails: " . count($import->uniqueEmails) . PHP_EOL;
