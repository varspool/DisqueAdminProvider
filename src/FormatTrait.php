<?php

namespace Varspool\DisqueAdmin;

use DateInterval;
use DateTime;
use DateTimeImmutable;

trait FormatTrait
{
    public function formatJobCount(string $jobs): string
    {
        return number_format($jobs) . ' jobs';
    }

    public function formatCount(string $jobs): string
    {
        return number_format($jobs);
    }

    public function formatIntervalSeconds(string $seconds): string
    {
        $d1 = new DateTimeImmutable();
        $d2 = $d1->add(new DateInterval('PT' . (int)$seconds . 'S'));
        $interval = $d2->diff($d1);

        if ($interval->days) {
            return $interval->days . ' day' . ($interval->days == 1 ? '' : 's');
        }

        if ($interval->h) {
            return $interval->h . ' hour' . ($interval->h == 1 ? '' : 's');
        }

        if ($interval->i) {
            return $interval->i . ' minute' . ($interval->i == 1 ? '' : 's');
        }

        return $seconds . ' seconds';
    }

    public function formatIntervalMillis(string $milliseconds): string
    {
        return $this->formatIntervalSeconds((int)$milliseconds / 1000);
    }

    public function formatCTime(string $ctime): string
    {
        return date(DATE_ISO8601, (int)((int)$ctime / 1000000000));
    }

    protected function formatObject(array $job)
    {
        foreach ($job as $name => &$value) {
            if (!empty($this->format[$name])) {
                $value = call_user_func([$this, $this->format[$name]], $value);
            }
        }

        return $job;
    }
}
