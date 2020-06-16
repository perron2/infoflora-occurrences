<?php

namespace Occurrences;

interface RowProvider {
    /**
     * Returns the next occurrence definition or null if there is no more data available.
     * @return Occurrence|null
     */
    public function nextRow(): ?Occurrence;

    /**
     * Rewinds the provider. After this call the next call of {@see nextRow()} returns the first row again.
     */
    public function rewind(): void;
}