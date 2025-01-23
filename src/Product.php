<?php

namespace App;

class Product
{
    private ?string $title;

    private ?float $price;

    private ?string $imageUrl;

    private ?int $capacityMB;

    private ?string $colour;

    private ?string $availabilityText;

    private ?bool $isAvailable;

    private ?string $shippingText;

    private ?string $shippingDate;

    private array $options_availability = [
        'in stock' => true,
        'out of stock' => false,
    ];

    private array $options_capacity = [
        'gb' => 1000,
        'mb' => 1,
    ];

    private string $template_availability = 'Availability:';

    public function __construct (
        ?string $title,
        ?string $price,
        ?string $imageUrl,
        ?string $capacityMB,
        ?string $colour,
        ?string $availabilityText,
        ?string $isAvailable,
        ?string $shippingText,
        ?string $shippingDate
    )
    {
        $this->title = (string)$title;
        $this->price = $this->setPriceValue($price);
        $this->imageUrl = (string)$imageUrl;
        $this->capacityMB = $this->setCapacityValue($capacityMB);
        $this->colour = $colour;
        $this->availabilityText = $this->setAvailabilityText($availabilityText);
        $this->isAvailable = $this->setAvailabilityStatus($isAvailable);
        $this->shippingText = $shippingText;
        $this->shippingDate = $this->setShippingDate($shippingDate);
    }

    private function setAvailabilityStatus (?string $value): ?bool
    {
        $status = null;
        $lowercased_value = strtolower($value);
        foreach ($this->options_availability as $match => $option_value) {
            if (str_contains($lowercased_value, $match)) {
                $status = $option_value;
                break;
            }
        }
        return $status;
    }

    private function setAvailabilityText (?string $value): string
    {
        if (str_starts_with($value, $this->template_availability)) {
            $text = str_replace($this->template_availability, '', $value);
            return trim($text);
        }
        return $value;
    }

    private function setCapacityValue (?string $value): ?int
    {
        $capacityMB = null;
        $lowercased_value = strtolower($value);
        foreach ($this->options_capacity as $match => $option_value) {
            if (str_contains($lowercased_value, $match)) {
                $capacityMB = (int)$lowercased_value * $option_value;
                break;
            }
        }
        return $capacityMB;
    }

    private function setPriceValue (?string $value): ?float
    {
        if (gettype($value) !== 'string') {
            return null;
        }
        return (float)preg_replace('/[^\\d.]+/', '', $value);
    }

    private function setShippingDate (?string $value): ?string
    {
        if (!$value || gettype($value) !== 'string') {
            return null;
        }

        $lowercased_value = strtolower($value);

        // creates date from word "tomorrow"
        if (str_contains($lowercased_value, 'tomorrow')) {
            return date('Y-m-d', strtotime('tomorrow'));
        }

        // extracts date from string, format like: 2025-01-23
        if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $lowercased_value, $matches)) {
            $day = $matches[3];
            $month = $matches[2];
            $year = $matches[1];
            return $year . '-' . $month . '-' . $day;
        }

        // extracts date from string, format like: 23th Jan 2025 or 23 Jan 2025
        if (preg_match('/(\d{1,2})(st|nd|rd|th)?\s([A-Za-z]+)\s(\d{4})/', $lowercased_value, $matches)) {
            $day = $matches[1];
            $month = $matches[3];
            $year = $matches[4];
            return date('Y-m-d', strtotime($day . ' ' . $month . ' ' . $year));
        }

        return null;
    }

    public function output (): array
    {
        return [
            'title' => $this->title,
            'price' => $this->price,
            'imageUrl' => $this->imageUrl,
            'capacityMB' => $this->capacityMB,
            'colour' => $this->colour,
            'availabilityText' => $this->availabilityText,
            'isAvailable' => $this->isAvailable,
            'shippingText' => $this->shippingText,
            'shippingDate' => $this->shippingDate,
        ];
    }
}
