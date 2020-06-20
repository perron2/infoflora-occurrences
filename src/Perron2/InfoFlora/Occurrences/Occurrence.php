<?php

namespace Perron2\InfoFlora\Occurrences;

/**
 * Describes a single occurrence of a species inside a sector.
 */
class Occurrence
{
    /**
     * @var int A six-digit sector code
     */
    public $sectorCode;

    /**
     * @var int An Info Flora species ID
     */
    public $speciesId;

    /**
     * @var int The type of the occurrence (one of {@link Type})
     */
    public $type;

    /**
     * @var int The year of the last confirmed sighting or 0 if none exists
     */
    public $year;
}