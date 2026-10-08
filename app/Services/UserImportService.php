<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class UserImportService
{
    /**
     * Single source of truth for every import column.
     *
     * - `required` → drives validation badge & required-column check
     * - `notes`    → shown in the Column Guide (may contain inline HTML like <code>)
     * - key order  → drives both the Excel template header order AND the guide display order
     *
     * Add or remove columns here and both the template and the guide update automatically.
     */
    public const COLUMN_GUIDE = [
        'nidn' => [
            'required' => true,
            'notes'    => '8–12 digits, must be unique',
        ],
        'full_name' => [
            'required' => true,
            'notes'    => 'Full name of the :role',
        ],
        'email' => [
            'required' => true,
            'notes'    => 'Must be a valid email and unique',
        ],
        'birthday' => [
            'required' => true,
            'notes'    => 'Format <code>YYYY-MM-DD</code>, e.g. <code>1990-05-15</code>',
        ],
        'gender' => [
            'required' => true,
            'notes'    => '<code>laki-laki</code> or <code>perempuan</code>',
        ],
        'address' => [
            'required' => true,
            'notes'    => 'Full address',
        ],
        'password' => [
            'required' => false,
            'notes'    => 'Min. 8 characters. Leave empty to use NIDN as the default password',
        ],
        'phone_number' => [
            'required' => false,
            'notes'    => 'Phone number, e.g. <code>081234567890</code>',
        ],
    ];

    public function __construct(
        protected string $roleCode,
    ) {}

    /**
     * Column guide rows ready to render.
     *
     * @return array<int, array{column: string, required: bool, notes: string}>
     */
    public function columnGuide(): array
    {
        $role = $this->roleCode === 'REVIEWER' ? 'reviewer' : 'user';

        $rows = [];
        foreach (self::COLUMN_GUIDE as $column => $meta) {
            $rows[] = [
                'column'   => $column,
                'required' => $meta['required'],
                'notes'    => __($meta['notes'], ['role' => $role]),
            ];
        }

        return $rows;
    }

    /**
     * Column names in the order they appear in the template.
     */
    public static function allColumns(): array
    {
        return array_keys(self::COLUMN_GUIDE);
    }

    /**
     * Columns that must be present in the uploaded file.
     */
    public static function requiredColumns(): array
    {
        return array_keys(array_filter(
            self::COLUMN_GUIDE,
            fn ($meta) => $meta['required'],
        ));
    }

    /* ─── Template download ─── */
    public function buildTemplate(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = self::allColumns();
        $sheet->fromArray($headers, null, 'A1');

        // Sample rows MUST follow $headers order
        $sheet->fromArray(
            ['1234567890', 'Ahmad Fauzi', 'ahmad@ua.ac.id', '1990-05-15', 'laki-laki', 'Jl. Merdeka No. 1, Jakarta', '', '081234567890'],
            null,
            'A2',
        );
        $sheet->fromArray(
            ['0987654321', 'Siti Nurhaliza', 'siti@ua.ac.id', '1988-11-20', 'perempuan', 'Jl. Sudirman 45, Bandung', 'secret123', ''],
            null,
            'A3',
        );

        $lastCol = chr(ord('A') + count($headers) - 1);
        $headerRange = "A1:{$lastCol}1";

        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('10B981');
        $sheet->getStyle($headerRange)->getFont()->getColor()->setRGB('FFFFFF');

        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /* ─── Parse & validate ─── */

    public function parseAndValidate(UploadedFile $file): array
    {
        try {
            $data = $this->readExcel($file);
        } catch (\Throwable $e) {
            report($e);
            return ['rows' => [], 'error' => 'Could not read file: ' . $e->getMessage()];
        }

        if (empty($data)) {
            return ['rows' => [], 'error' => 'File is empty.'];
        }

        $header  = array_map(fn ($h) => strtolower(trim((string) $h)), $data[0]);
        $missing = array_diff(self::requiredColumns(), $header);

        if (! empty($missing)) {
            return ['rows' => [], 'error' => 'Missing required columns: ' . implode(', ', $missing)];
        }

        $rows = $this->validateRows($data, $header);

        if (empty($rows)) {
            return ['rows' => [], 'error' => 'No data rows found.'];
        }

        return ['rows' => $rows, 'error' => null];
    }

    public function import(array $rows): array
    {
        $roleId = Role::where('role_code', $this->roleCode)->value('id');

        if (! $roleId) {
            throw new \RuntimeException("Role {$this->roleCode} not found.");
        }

        $validRows = array_filter($rows, fn ($r) => $r['valid']);

        $created = 0;
        $updated = 0;
        $failed  = [];

        foreach ($validRows as $row) {
            try {
                $action = $this->persistRow($row, $roleId);
                $action === 'update' ? $updated++ : $created++;
            } catch (\Throwable $e) {
                report($e);
                $failed[] = ['row' => $row['row'], 'reason' => $e->getMessage()];
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => count($rows) - count($validRows),
            'failed'  => $failed,
        ];
    }

    /* ─── Internals ─── */
    protected function readExcel(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        $reader = match ($ext) {
            'xlsx' => new Xlsx(),
            'xls'  => new Xls(),
            default => throw new \InvalidArgumentException(
                'Unsupported file type. Only .xlsx and .xls are allowed.'
            ),
        };

        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file->getRealPath());

        return $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
    }

    protected function validateRows(array $data, array $header): array
    {
        $rows = [];
        $seenNidn = $seenEmail = [];
        $rowNum = 1;

        for ($i = 1; $i < count($data); $i++) {
            $rowNum++;
            $dataRow = $data[$i] ?? [];

            if (count(array_filter($dataRow, fn ($v) => $v !== null && trim((string) $v) !== '')) === 0) {
                continue;
            }

            $row = [];
            foreach ($header as $j => $col) {
                $row[$col] = trim((string) ($dataRow[$j] ?? ''));
            }

            $rows[] = $this->validateRow($row, $rowNum, $seenNidn, $seenEmail);
        }

        return $rows;
    }

    protected function validateRow(array $row, int $rowNum, array &$seenNidn, array &$seenEmail): array
    {
        $errors = [];
        $existingUser = null;

        if ($row['nidn'] === '') {
            $errors[] = 'NIDN required';
        } elseif (! preg_match('/^\d{8,12}$/', $row['nidn'])) {
            $errors[] = 'NIDN must be 8–12 digits';
        } elseif (isset($seenNidn[$row['nidn']])) {
            $errors[] = 'Duplicate NIDN in file';
        } else {
            $existingUser = User::where('nidn', $row['nidn'])->first();
        }

        if ($row['email'] === '') {
            $errors[] = 'Email required';
        } elseif (! filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        } elseif (isset($seenEmail[strtolower($row['email'])])) {
            $errors[] = 'Duplicate email in file';
        } else {
            $emailOwner = User::where('email', $row['email'])->first();
            if ($emailOwner && (! $existingUser || $emailOwner->id !== $existingUser->id)) {
                $errors[] = 'Email already used by another user';
            }
        }

        if ($row['full_name'] === '') {
            $errors[] = 'Full name required';
        }

        if ($row['birthday'] === '') {
            $errors[] = 'Birthday required';
        } elseif (! strtotime($row['birthday'])) {
            $errors[] = 'Invalid birthday format';
        }

        $gender = strtolower($row['gender']);
        if ($gender === '') {
            $errors[] = 'Gender required';
        } elseif (! in_array($gender, ['laki-laki', 'perempuan'], true)) {
            $errors[] = 'Gender must be "laki-laki" or "perempuan"';
        }

        if ($row['address'] === '') {
            $errors[] = 'Address required';
        }

        if (! empty($row['password']) && strlen($row['password']) < 8) {
            $errors[] = 'Password min. 8 characters';
        }

        if ($row['nidn'] !== '')   $seenNidn[$row['nidn']] = true;
        if ($row['email'] !== '')  $seenEmail[strtolower($row['email'])] = true;

        return [
            'row'         => $rowNum,
            'data'        => $row,
            'errors'      => $errors,
            'valid'       => empty($errors),
            'existing_id' => $existingUser?->id,
            'action'      => $existingUser ? 'update' : 'create',
        ];
    }

    protected function persistRow(array $row, int $roleId): string
    {
        $data = $row['data'];

        $attributes = [
            'role_id'           => $roleId,
            'full_name'         => $data['full_name'],
            'email'             => $data['email'],
            'birthday'          => date('Y-m-d', strtotime($data['birthday'])),
            'email_verified_at' => now(),
            'gender'            => strtolower($data['gender']),
            'phone_number'      => $data['phone_number'] ?? null,
            'address'           => $data['address'],
        ];

        if (! empty($data['password'])) {
            $attributes['password']     = Hash::make($data['password']);
            $attributes['raw_password'] = $data['password'];
        }

        if ($row['existing_id']) {
            User::where('id', $row['existing_id'])->update($attributes);
            return 'update';
        }

        if (empty($data['password'])) {
            $attributes['password']     = Hash::make($data['nidn']);
            $attributes['raw_password'] = $data['nidn'];
        }
        $attributes['nidn'] = $data['nidn'];
        User::create($attributes);

        return 'create';
    }
}
