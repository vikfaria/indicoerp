<?php

namespace Workdo\Webhook\Extractors;

class VehicleDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->vehicle)) return [];

        $vehicle = $event->vehicle;

        return [
            'vehicle' => [
                'id' => $vehicle->id,
                'model_name' => $vehicle->model_name,
                'model_year' => $vehicle->model_year,
                'plate_number' => $vehicle->plate_number,
                'key_number' => $vehicle->key_number,
                'gear_box' => $vehicle->gear_box,
                'engine_number' => $vehicle->engine_number,
                'production_date' => $vehicle->production_date,
                'cost' => $vehicle->cost,
                'notes' => $vehicle->notes,
                'vehicle_type' => $vehicle->vehicleType?->name ?? null,
                'vehicle_brand' => $vehicle->vehicleBrand?->name ?? null,
                'vehicle_color' => $vehicle->vehicleColor?->name ?? null,
                'fuel_type' => $vehicle->fuelType?->name ?? null,
                'created_at' => $vehicle->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
