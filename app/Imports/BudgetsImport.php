<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BudgetsImport implements ToArray, WithHeadingRow
{
    public function array(array $rows)
    {
        return $rows;
    }
}
