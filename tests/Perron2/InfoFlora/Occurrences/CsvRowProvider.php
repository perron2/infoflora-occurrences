<?php

declare(strict_types=1);

namespace Perron2\InfoFlora\Occurrences;

use InvalidArgumentException;

class CsvRowProvider implements RowProvider
{
    private $file;
    private $sectorIndex;
    private $speciesIndex;
    private $typeIndex;
    private $yearIndex;

    public function __construct(string $csvFile)
    {
        $this->file = fopen($csvFile, 'rt');
        if (!$this->file) {
            throw new InvalidArgumentException("Cannot open file \"$csvFile\"");
        }
        $headers = fgetcsv($this->file);
        $this->sectorIndex = $this->getIndex($headers, 'sector');
        $this->speciesIndex = $this->getIndex($headers, 'species');
        $this->typeIndex = $this->getIndex($headers, 'type');
        $this->yearIndex = $this->getIndex($headers, 'year');
    }

    public function __destruct()
    {
        $this->close();
    }

    public function close(): void
    {
        if ($this->file) {
            fclose($this->file);
            $this->file = null;
        }
    }

    public function nextRow(): ?Occurrence
    {
        $row = fgetcsv($this->file);
        if (!$row) {
            return null;
        }
        $occurrence = new Occurrence();
        $occurrence->sectorCode = (int)$row[$this->sectorIndex];
        $occurrence->speciesId = (int)$row[$this->speciesIndex];
        $occurrence->type = (int)$row[$this->typeIndex];
        $occurrence->year = (int)$row[$this->yearIndex];
        return $occurrence;
    }

    public function rewind(): void
    {
        fseek($this->file, 0);
        fgetcsv($this->file);  // skip header line
    }

    private function getIndex(array $headers, string $columnName): int
    {
        $index = array_search($columnName, $headers);
        if ($index === false) {
            throw new InvalidArgumentException("Column \"$columnName\" is missing from CSV file");
        }
        return $index;
    }
}
