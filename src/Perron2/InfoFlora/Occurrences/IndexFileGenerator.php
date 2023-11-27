<?php

declare(strict_types=1);

namespace Perron2\InfoFlora\Occurrences;

use InvalidArgumentException;
use RuntimeException;

class IndexFileGenerator
{
    private const HEADER_SIZE = 8;
    private const SECTOR_SIZE = 10;
    private const SECTOR_SIZE_V2 = self::SECTOR_SIZE + 19;
    private const OCCURRENCE_SIZE = 5;

    /**
     * Creates an IFO binary file based on the occurrences delivered by the specified provider.
     * @param RowProvider $provider
     * @param string $outputFilename
     * @param bool $withStatistics
     */
    public function generateIndexFile(RowProvider $provider, string $outputFilename, bool $withStatistics = false)
    {
        $sectors = $this->getSectors($provider, $withStatistics);
        $provider->rewind();
        $output = fopen($outputFilename, 'wb');
        if ($output === false) {
            throw new RuntimeException("File \"{$outputFilename}\" cannot be created");
        }
        fwrite($output, 'IFO1');
        fwrite($output, pack('N', count($sectors)));
        $this->writeSectors($output, $sectors, $withStatistics);
        $this->writeOccurrences($output, $provider);
        fclose($output);
    }

    /**
     * Returns all sectors delivered by the specified provider.
     * @param RowProvider $provider
     * @param bool $withStatistics
     * @return Sector[]
     */
    private function getSectors(RowProvider $provider, bool $withStatistics): array
    {
        /** @var Sector $currentSector */
        $currentSector = null;
        /** @var Sector[] $sectors */
        $sectors = [];
        $lastSectorCode = 0;
        $knownTypes = [Type::CONFIRMED, Type::UNKNOWN_BUT_PROBABLE, Type::UNKNOWN_BUT_POSSIBLE];
        $currentYear = (int)date('Y');
        while ($row = $provider->nextRow()) {
            if ($row->sectorCode < $lastSectorCode) {
                throw new InvalidArgumentException('Sector codes must be sorted in ascending order');
            } elseif (!in_array($row->type, $knownTypes)) {
                $types = join(', ', $knownTypes);
                throw new InvalidArgumentException("Invalid type ({$types} expected)");
            } elseif ($row->type == Type::CONFIRMED && $row->year < 1000) {
                $type = Type::CONFIRMED;
                throw new InvalidArgumentException("Confirmed occurrences (type={$type}) require a year >= 1000");
            }
            $lastSectorCode = $row->sectorCode;
            if (!$currentSector || $currentSector->code != $row->sectorCode) {
                $currentSector = new Sector();
                $currentSector->code = $row->sectorCode;
                $sectors[] = $currentSector;
            }
            $currentSector->numOccurrences++;
            if ($withStatistics) {
                if ($row->type == Type::CONFIRMED) {
                    $currentSector->occurrencesLast5Years += ($row->year > $currentYear - 5) ? 1 : 0;
                    $currentSector->occurrencesLast10Years += ($row->year > $currentYear - 10) ? 1 : 0;
                    $currentSector->occurrencesLast20Years += ($row->year > $currentYear - 20) ? 1 : 0;
                    $currentSector->occurrencesSince1983 += ($row->year >= 1983) ? 1 : 0;
                    $currentSector->occurrencesSince1967 += ($row->year >= 1967) ? 1 : 0;
                }
                $currentSector->occurrencesProbable += ($row->type == Type::UNKNOWN_BUT_PROBABLE) ? 1 : 0;
                $currentSector->occurrencesPossible += ($row->type == Type::UNKNOWN_BUT_POSSIBLE) ? 1 : 0;
            }
        }
        if ($withStatistics) {
            foreach ($sectors as $sector) {
                $sector->occurrencesPotential = $sector->occurrencesSince1967 + $sector->occurrencesProbable;
                if ($sector->occurrencesPotential > 0) {
                    $sector->progressLast5Years = (int)round($sector->occurrencesLast5Years * 100.0 / $sector->occurrencesPotential);
                    $sector->progressLast10Years = (int)round($sector->occurrencesLast10Years * 100.0 / $sector->occurrencesPotential);
                    $sector->progressLast20Years = (int)round($sector->occurrencesLast20Years * 100.0 / $sector->occurrencesPotential);
                }
            }
        }
        return $sectors;
    }

    /**
     * Writes all known sectors to the specified file.
     * @param resource $file
     * @param Sector[] $sectors
     * @param bool $withStatistics
     */
    private function writeSectors($file, array $sectors, bool $withStatistics): void
    {
        $sectorSize = ($withStatistics ? self::SECTOR_SIZE_V2 : self::SECTOR_SIZE);
        $extraHeaderSize = ($withStatistics ? 4 : 0);
        $offset = self::HEADER_SIZE + count($sectors) * $sectorSize + $extraHeaderSize;
        foreach ($sectors as $sector) {
            fwrite($file, pack('N', $sector->code));
            fwrite($file, pack('N', $offset));
            fwrite($file, pack('n', $sector->numOccurrences));
            $offset += $sector->numOccurrences * self::OCCURRENCE_SIZE;
        }
        if ($withStatistics) {
            fwrite($file, 'STAT');
            foreach ($sectors as $sector) {
                fwrite($file, pack('n', $sector->occurrencesLast5Years));
                fwrite($file, pack('n', $sector->occurrencesLast10Years));
                fwrite($file, pack('n', $sector->occurrencesLast20Years));
                fwrite($file, pack('n', $sector->occurrencesSince1983));
                fwrite($file, pack('n', $sector->occurrencesSince1967));
                fwrite($file, pack('n', $sector->occurrencesProbable));
                fwrite($file, pack('n', $sector->occurrencesPossible));
                fwrite($file, pack('n', $sector->occurrencesPotential));
                fwrite($file, pack('C', $sector->progressLast5Years));
                fwrite($file, pack('C', $sector->progressLast10Years));
                fwrite($file, pack('C', $sector->progressLast20Years));
            }
        }
    }

    /**
     * Writes all known occurrences to the specified file.
     * @param resource $file
     * @param RowProvider $provider
     */
    private function writeOccurrences($file, RowProvider $provider): void
    {
        while ($row = $provider->nextRow()) {
            fwrite($file, pack(
                'CCC',
                ($row->speciesId >> 16) & 0xFF,
                ($row->speciesId >> 8) & 0xFF,
                $row->speciesId & 0XFF
            ));
            fwrite($file, pack('n', $row->type == Type::CONFIRMED ? $row->year : $row->type));
        }
    }
}
