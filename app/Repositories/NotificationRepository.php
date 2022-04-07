<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class NotificationRepository
{
    private const TABLE_NAME = 'notifications', COLUMN_READ_AT = "read_at";
    private QueryBuilder $QueryBuilder;

    private function __construct(QueryBuilder|null $builder = null)
    {
        if ($builder) {
            $this->QueryBuilder = $builder;
        } else $this->QueryBuilder = DB::table(self::TABLE_NAME);

        return $this;
    }

    public static function make(): static
    {
        return new static();
    }

    public static function decode(array|object $notification): object
    {
        if (is_array($notification))
            $notification = (object) $notification; // to object

        if (isset($notification->data))
            $notification->data = json_decode($notification->data); // parse data 
        return $notification; // return array to object  
    }

    public static function find(string $notificationID): static
    {
        // $query = DB::table(self::TABLE_NAME)->where("id", $notificationID);
        $query = self::make()->getBuilder()->where("id", $notificationID);
        return new static($query);
    }

    public function getBuilder(): QueryBuilder
    {
        return $this->QueryBuilder;
    }

    private function getUserID($user): string
    {
        if ($user instanceof User) {
            return $user->id;
        }
        return $user;
    }

    public function where_Notifiable(User|string $user)
    {
        $this->QueryBuilder = $this->QueryBuilder->where('notifiable_id', $this->getUserID($user));
        return $this;
    }

    public function where_Unread()
    {
        $this->QueryBuilder = $this->QueryBuilder->where(self::COLUMN_READ_AT, null);
        return $this;
    }

    public function where_Read()
    {
        $this->QueryBuilder = $this->QueryBuilder->where(self::COLUMN_READ_AT, "!=", null);
        return $this;
    }

    public function where_CreatedAt_About24HAgo()
    {
        $this->QueryBuilder =  $this->QueryBuilder->where("created_at", ">=", now()->subDay(1)->toDateTimeString());
        return $this;
    }

    public function where_CreatedAt_moreThan24HAgo()
    {
        $this->QueryBuilder =  $this->QueryBuilder->where("created_at", "<", now()->subDay(1)->toDateTimeString());
        return $this;
    }

    public function markAsRead(): bool
    {
        return boolval($this->QueryBuilder->update([self::COLUMN_READ_AT => now()->toDateTimeString()]));
    }

    public function markAsUnread(): bool
    {
        return  boolval($this->QueryBuilder->update([self::COLUMN_READ_AT => null]));
    }
}
