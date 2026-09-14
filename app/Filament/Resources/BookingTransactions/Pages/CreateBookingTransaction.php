<?php

namespace App\Filament\Resources\BookingTransactions\Pages;

use App\Filament\Resources\BookingTransactions\BookingTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBookingTransaction extends CreateRecord
{
    protected static string $resource = BookingTransactionResource::class;
}
