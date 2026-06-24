<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CampaignService
{
    // ─── GSM-7 charset ──────────────────────────────────────────────────────────

    /** Caracteres GSM-7 básicos (cuentan 1 cada uno) */
    private const GSM7_BASIC = [
        '@', '£', '$', '¥', 'è', 'é', 'ù', 'ì', 'ò', 'Ç', "\n", 'Ø', 'ø', "\r",
        'Å', 'å', 'Δ', '_', 'Φ', 'Γ', 'Λ', 'Ω', 'Π', 'Ψ', 'Σ', 'Θ', 'Ξ',
        'Æ', 'æ', 'ß', 'É', ' ', '!', '"', '#', '¤', '%', '&', "'", '(', ')',
        '*', '+', ',', '-', '.', '/',
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
        ':', ';', '<', '=', '>', '?', '¡',
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
        'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        'Ä', 'Ö', 'Ñ', 'Ü', '§', '¿',
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
        'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
        'ä', 'ö', 'ñ', 'ü', 'à',
    ];

    /** Caracteres GSM-7 extendidos (cuentan 2 cada uno) */
    private const GSM7_EXTENDED = ['^', '{', '}', '[', ']', '\\', '~', '|', '€'];

    // ─── Excel parsing ───────────────────────────────────────────────────────────

    /**
     * Lee el archivo Excel y retorna las cabeceras de la primera fila.
     * Ejemplo de retorno: ['A' => 'Nombre', 'B' => 'Telefono', 'C' => 'Mensaje']
     *
     * @param  string  $filePath  Ruta absoluta al archivo
     * @return array<string, string>
     */
    public function parseExcelHeaders(string $filePath): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        // Solo leer fila 1 para no cargar todo el archivo en memoria
        $reader->setReadFilter(new class implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool {
                return $row === 1;
            }
        });

        $spreadsheet     = $reader->load($filePath);
        $sheet           = $spreadsheet->getActiveSheet();
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(
            $sheet->getHighestColumn()
        );

        $headers = [];
        for ($col = 1; $col <= $highestColIndex; $col++) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $value  = $sheet->getCell([$col, 1])->getValue();
            if ($value !== null && $value !== '') {
                $headers[$letter] = (string) $value;
            }
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $headers;
    }

    /**
     * Lee todas las filas del Excel (desde la 2) y retorna array de destinatarios.
     *
     * @param  string       $filePath      Ruta absoluta al archivo
     * @param  string       $phoneCol      Letra de columna con el teléfono (ej. 'A')
     * @param  string|null  $messageCol    Letra de columna con el mensaje (nullable)
     * @param  string|null  $fixedMessage  Mensaje fijo si $messageCol es null
     * @param  int|null     $limit         Limitar número de filas (para preview)
     * @return array
     */
    /**
     * @param  string[]|null  $messageCols      Columnas a concatenar (modo __concat__)
     * @param  string         $messageSeparator Separador entre columnas al concatenar
     */
    public function parseExcelRows(
        string $filePath,
        string $phoneCol,
        ?string $messageCol,
        ?string $fixedMessage,
        ?int $limit = null,
        ?array $messageCols = null,
        ?array $messageSeps = null
    ): array {
        // Aumentar memoria para archivos masivos (250k+ filas)
        $prevMemory = ini_get('memory_limit');
        ini_set('memory_limit', '1G');

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);

        $phoneColIndex   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($phoneCol);
        $messageColIndex = $messageCol
            ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($messageCol)
            : null;

        // Índices de columnas para modo concatenar
        $concatColIndexes = [];
        if (!empty($messageCols)) {
            foreach ($messageCols as $col) {
                $concatColIndexes[] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($col);
            }
        }

        // Leer solo las columnas necesarias para ahorrar memoria
        $neededCols = [$phoneColIndex];
        if (!empty($concatColIndexes)) {
            array_push($neededCols, ...$concatColIndexes);
        } elseif ($messageColIndex !== null) {
            $neededCols[] = $messageColIndex;
        }
        $neededLetters = array_map(
            fn($i) => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i),
            array_unique($neededCols)
        );
        $reader->setReadFilter(new class($neededLetters) implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
            public function __construct(private array $cols) {}
            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool {
                return $row >= 2 && in_array($columnAddress, $this->cols, true);
            }
        });

        $spreadsheet = $reader->load($filePath);
        $sheet       = $spreadsheet->getActiveSheet();
        $recipients  = [];
        $processed   = 0;

        foreach ($sheet->getRowIterator(2) as $rowObj) {
            if ($limit !== null && $processed >= $limit) {
                break;
            }

            $rowIndex = $rowObj->getRowIndex();
            $rawPhone = $sheet->getCell([$phoneColIndex, $rowIndex])->getValue();

            if ($rawPhone === null || trim((string) $rawPhone) === '') {
                continue;
            }

            $phone = $this->normalizePhone((string) $rawPhone);

            if (!$this->isValidPhone($phone)) {
                continue;
            }

            if (!empty($concatColIndexes)) {
                $message = '';
                foreach ($concatColIndexes as $i => $idx) {
                    $message .= (string) ($sheet->getCell([$idx, $rowIndex])->getValue() ?? '');
                    if ($i < count($concatColIndexes) - 1) {
                        $message .= $messageSeps[$i] ?? '';
                    }
                }
            } elseif ($messageColIndex !== null) {
                $message = (string) ($sheet->getCell([$messageColIndex, $rowIndex])->getValue() ?? '');
            } else {
                $message = (string) ($fixedMessage ?? '');
            }

            $segmentInfo  = $this->calculateSegments($message);
            $recipients[] = [
                'phone'    => $phone,
                'message'  => $message,
                'segments' => $segmentInfo['segments'],
                'encoding' => $segmentInfo['encoding'],
            ];

            $processed++;
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        ini_set('memory_limit', $prevMemory);

        return $recipients;
    }

    // ─── SMS segment calculator ──────────────────────────────────────────────────

    /**
     * Calcula segmentos SMS, encoding, longitud y caracteres restantes.
     *
     * @param  string  $message
     * @return array{segments: int, encoding: string, length: int, chars_remaining: int, chars_per_segment: int}
     */
    public function calculateSegments(string $message): array
    {
        $isGsm7  = $this->isGsm7($message);
        $length  = $isGsm7 ? $this->getGsm7Length($message) : mb_strlen($message);
        $encoding = $isGsm7 ? 'GSM-7' : 'Unicode';

        if ($isGsm7) {
            $singleLimit = 160;
            $multiLimit  = 153;
        } else {
            $singleLimit = 70;
            $multiLimit  = 67;
        }

        if ($length <= $singleLimit) {
            $segments       = 1;
            $charsPerSegment = $singleLimit;
            $charsRemaining  = $singleLimit - $length;
        } else {
            $segments        = (int) ceil($length / $multiLimit);
            $charsPerSegment = $multiLimit;
            $used            = $length % $multiLimit;
            $charsRemaining  = $used === 0 ? 0 : $multiLimit - $used;
        }

        return [
            'segments'         => $segments,
            'encoding'         => $encoding,
            'length'           => $length,
            'chars_remaining'  => $charsRemaining,
            'chars_per_segment' => $charsPerSegment,
        ];
    }

    /**
     * Determina si todos los caracteres del mensaje están en el charset GSM-7.
     */
    private function isGsm7(string $message): bool
    {
        $allGsm7 = array_merge(self::GSM7_BASIC, self::GSM7_EXTENDED);
        $chars   = preg_split('//u', $message, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($chars as $char) {
            if (!in_array($char, $allGsm7, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calcula la longitud GSM-7 del mensaje (extendidos cuentan como 2).
     */
    private function getGsm7Length(string $message): int
    {
        $chars  = preg_split('//u', $message, -1, PREG_SPLIT_NO_EMPTY);
        $length = 0;

        foreach ($chars as $char) {
            $length += in_array($char, self::GSM7_EXTENDED, true) ? 2 : 1;
        }

        return $length;
    }

    // ─── Phone validity count ─────────────────────────────────────────────────────

    /**
     * Recorre todo el archivo y cuenta teléfonos válidos e inválidos en la columna dada.
     *
     * @param  string  $filePath   Ruta absoluta al archivo
     * @param  string  $phoneCol   Letra de columna con el teléfono (ej. 'A')
     * @return array{valid: int, invalid: int, total: int}
     */
    public function countPhoneValidity(string $filePath, string $phoneCol): array
    {
        $prevMemory = ini_get('memory_limit');
        ini_set('memory_limit', '1G');

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);

        $phoneColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($phoneCol);

        $reader->setReadFilter(new class([$phoneCol]) implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
            public function __construct(private array $cols) {}
            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool {
                return $row >= 2 && in_array($columnAddress, $this->cols, true);
            }
        });

        $spreadsheet = $reader->load($filePath);
        $sheet       = $spreadsheet->getActiveSheet();
        $valid       = 0;
        $invalid     = 0;

        foreach ($sheet->getRowIterator(2) as $rowObj) {
            $rawPhone = $sheet->getCell([$phoneColIndex, $rowObj->getRowIndex()])->getValue();

            if ($rawPhone === null || trim((string) $rawPhone) === '') {
                continue;
            }

            $phone = $this->normalizePhone((string) $rawPhone);
            $this->isValidPhone($phone) ? $valid++ : $invalid++;
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        ini_set('memory_limit', $prevMemory);

        return ['valid' => $valid, 'invalid' => $invalid, 'total' => $valid + $invalid];
    }

    // ─── File management ─────────────────────────────────────────────────────────

    /**
     * Cuenta el número de filas de datos (excluyendo la cabecera).
     * Para XLSX usa ZipArchive+XMLReader (muy rápido); para XLS usa PhpSpreadsheet.
     */
    public function countRows(string $filePath): int
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($ext === 'xlsx') {
            $count = $this->countRowsXlsx($filePath);
            if ($count >= 0) {
                return $count;
            }
        }

        if ($ext === 'csv') {
            $handle = fopen($filePath, 'r');
            if ($handle === false) {
                return 0;
            }
            $count = 0;
            while (fgets($handle) !== false) {
                $count++;
            }
            fclose($handle);

            return max(0, $count - 1);
        }

        // Fallback PhpSpreadsheet para .xls
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $reader->setReadFilter(new class implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool {
                return $columnAddress === 'A';
            }
        });

        $spreadsheet = $reader->load($filePath);
        $count       = max(0, $spreadsheet->getActiveSheet()->getHighestRow() - 1);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $count;
    }

    /**
     * Cuenta filas en XLSX abriendo el ZIP y contando elementos <row> en el XML.
     * Retorna -1 si no puede abrir el archivo.
     */
    private function countRowsXlsx(string $filePath): int
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            return -1;
        }

        // Encontrar la primera hoja vía relaciones del workbook
        $sheetXmlPath = 'xl/worksheets/sheet1.xml';
        $relsContent  = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($relsContent !== false) {
            try {
                $rels = new \SimpleXMLElement($relsContent);
                foreach ($rels->Relationship as $rel) {
                    if (str_contains((string) $rel['Type'], '/worksheet')) {
                        $target       = ltrim((string) $rel['Target'], '/');
                        $sheetXmlPath = str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
                        break;
                    }
                }
            } catch (\Throwable) {
                // usar ruta por defecto
            }
        }

        $sheetContent = $zip->getFromName($sheetXmlPath);
        $zip->close();

        if ($sheetContent === false) {
            return -1;
        }

        $count = preg_match_all('/<row\b/i', $sheetContent, $matches);

        return max(0, $count - 1);
    }

    /**
     * Guarda el archivo subido en un directorio temporal y retorna el path relativo.
     *
     * @param  UploadedFile  $file
     * @return string  Path relativo (campaigns/temp/{uuid}.xlsx)
     */
    public function storeTempFile(UploadedFile $file): string
    {
        $uuid      = (string) Str::uuid();
        $extension = $file->getClientOriginalExtension() ?: 'xlsx';
        $filename  = $uuid . '.' . $extension;
        $directory = 'campaigns/temp';

        $file->storeAs($directory, $filename, 'local');

        return $directory . '/' . $filename;
    }

    /**
     * Elimina un archivo temporal del storage.
     *
     * @param  string  $relativePath
     * @return void
     */
    public function deleteTempFile(string $relativePath): void
    {
        \Illuminate\Support\Facades\Storage::disk('local')->delete($relativePath);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    /**
     * Normaliza un número de teléfono al formato mexicano de 12 dígitos (52XXXXXXXXXX).
     */
    private function normalizePhone(string $raw): string
    {
        // Solo dígitos
        $digits = preg_replace('/\D/', '', $raw);

        // Si tiene 10 dígitos, agregar prefijo 52
        if (strlen($digits) === 10) {
            return '52' . $digits;
        }

        // Si empieza con 52 y tiene 12 dígitos, ya está correcto
        if (strlen($digits) === 12 && str_starts_with($digits, '52')) {
            return $digits;
        }

        // Retornar tal cual (puede ser internacional u otro formato)
        return $digits;
    }

    /**
     * Valida que el teléfono tenga el formato mexicano: 12 dígitos comenzando con 52.
     */
    private function isValidPhone(string $phone): bool
    {
        return (bool) preg_match('/^52\d{10}$/', $phone);
    }
}
