<?php

namespace App\Models;

class Order
{
    public int $id;
    public string $name;
    public string $email;
    public string $phone;
    public string $event_type;
    public string $date;
    public string $time;
    public int $guests;
    public string $created_at;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'event_type' => $this->event_type,
            'date' => $this->date,
            'time' => $this->time,
            'guests' => $this->guests,
            'created_at' => $this->created_at,
        ];
    }
}
