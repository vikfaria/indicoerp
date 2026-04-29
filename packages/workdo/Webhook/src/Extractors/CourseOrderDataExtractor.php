<?php

namespace Workdo\Webhook\Extractors;

class CourseOrderDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->order)) return [];

        $order = $event->order;

        return [
            'course_order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'student' => $order->student?->name ?? null,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'notes' => $order->notes,
                'order_date' => $order->order_date,
                'original_total' => $order->original_total,
                'subtotal' => $order->subtotal,
                'discount_amount' => $order->discount_amount,
                'item_discount' => $order->item_discount,
                'total_discount' => $order->total_discount,
                'total_amount' => $order->total_amount,
                'created_at' => $order->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
