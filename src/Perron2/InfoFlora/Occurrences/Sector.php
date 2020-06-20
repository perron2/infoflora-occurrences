<?php

namespace Perron2\InfoFlora\Occurrences;

/**
 * Describes general information of a sector.
 */
class Sector
{
    /**
     * @var int A six-digit sector code
     */
    public $code;

    /**
     * @var int The number of occurrences in the sector
     */
    public $numOccurrences;

    /**
     * @var int Number of confirmed species during the last 5 years
     */
    public $occurrencesLast5Years;

    /**
     * @var int Number of confirmed species during the last 10 years
     */
    public $occurrencesLast10Years;

    /**
     * Number of confirmed species during the last 20 years
     */

    public $occurrencesLast20Years;

    /**
     * @var int Number of confirmed species since (and including) 1983
     */
    public $occurrencesSince1983;

    /**
     * @var int Number of confirmed species since 1967
     */
    public $occurrencesSince1967;

    /**
     * @var int Number of probable species
     */
    public $occurrencesProbable;

    /**
     * @var int Number of possible species
     */
    public $occurrencesPossible;

    /**
     * @var int Sum of numOccurrencesSince1967 + numOccurrencesProbable
     */
    public $occurrencesPotential;

    /**
     * @var int Mapping progress during the last 5 years (= numOccurrencesLast5Years * 100 / numPotentialOccurrences)
     */
    public $progressLast5Years;

    /**
     * @var int Mapping progress during the last 10 years (= numOccurrencesLast10Years * 100 / numPotentialOccurrences)
     */
    public $progressLast10Years;

    /**
     * @var int Mapping progress during the last 20 years (= numOccurrencesLast20Years * 100 / numPotentialOccurrences)
     */
    public $progressLast20Years;
}