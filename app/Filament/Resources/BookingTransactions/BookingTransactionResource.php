<?php

namespace App\Filament\Resources\BookingTransactions;

use App\Filament\Resources\BookingTransactions\Pages\CreateBookingTransaction;
use App\Filament\Resources\BookingTransactions\Pages\EditBookingTransaction;
use App\Filament\Resources\BookingTransactions\Pages\ListBookingTransactions;
// use App\Filament\Resources\BookingTransactions\Schemas\BookingTransactionForm;
// use App\Filament\Resources\BookingTransactions\Tables\BookingTransactionsTable;
use App\Models\BookingTransaction;
use App\Models\Cosmetic;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class BookingTransactionResource extends Resource
{
    protected static ?string $model = BookingTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShoppingCart;

    protected static string|UnitEnum|null $navigationGroup = 'Customers';

    public static function uploadTotals(Get $get, Set $set): void
    {
        $selectedCosmetics = collect($get('transactionDetails'))
            ->filter(fn ($item) => ! empty($item['cosmetic_id']) && ! empty($item['quantity'])
            );

        $prices = Cosmetic::find($selectedCosmetics->pluck('cosmetic_id'))->pluck('price', 'id');

        $subtotal = $selectedCosmetics->reduce(function ($subtotal, $item) use ($prices) {
            return $subtotal + ($prices[$item['cosmetic_id']] * $item['quantity']);
        }, 0);

        $total_tax_amount = round($subtotal * 0.11);

        $total_amount = round($subtotal + $total_tax_amount);

        $total_quantity = $selectedCosmetics->sum('quantity');

        $set('total_amount', number_format($total_amount, 0, '.', ''));

        $set('total_tax_amount', number_format($total_tax_amount, 0, '.', ''));

        $set('sub_total_amount', number_format($subtotal, 0, '.', ''));

        $set('quantity', $total_quantity);

    }

    public static function form(Schema $schema): Schema
    {
        // return BookingTransactionForm::configure($schema);
        return $schema
            ->schema([
                Wizard::make([
                    Step::make('Product and Price')
                        ->completedIcon('heroicon-m-hand-thumb-up')
                        ->description('Add your product items')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Repeater::make('transactionDetails')
                                        ->relationship('transactionDetails')
                                        ->schema([
                                            Select::make('cosmetic_id')
                                                ->relationship('cosmetic', 'name')
                                                ->searchable()
                                                ->preload()
                                                ->required()
                                                ->label('Select Product')
                                                ->live()
                                                ->afterStateUpdated(function ($state, callable $set) {
                                                    $cosmetic = Cosmetic::find($state);
                                                    $set('price', $cosmetic ? $cosmetic->price : 0);
                                                }),
                                            TextInput::make('price')
                                                ->required()
                                                ->numeric()
                                                ->readOnly()
                                                ->label('Price')
                                                ->hint('Price will be filled automatically based on product selection'),
                                            TextInput::make('quantity')
                                                ->integer()
                                                ->default(1)
                                                ->required(),

                                        ])
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            self::uploadTotals($get, $set);
                                        })
                                        ->minItems(1)
                                        ->columnSpan('full')
                                        ->label('Choose Products'),
                                ]),

                            Grid::make(4)
                                ->schema([
                                    TextInput::make('quantity')
                                        ->integer()
                                        ->label('Total Quantity')
                                        ->readOnly()
                                        ->default(1)
                                        ->required(),
                                    TextInput::make('sub_total_amount')
                                        ->numeric()
                                        ->readOnly()
                                        ->label('Sub Total Amount'),
                                    TextInput::make('total_amount')
                                        ->numeric()
                                        ->readOnly()
                                        ->label('Total Amount'),
                                    TextInput::make('total_tax_amount')
                                        ->numeric()
                                        ->readOnly()
                                        ->label('Total Tax (11%)'),
                                ]),
                        ]),

                    Step::make('Customer Information')
                        ->completedIcon('heroicon-m-hand-thumb-up')
                        ->description('For our marketing')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('name')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('phone')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('email')
                                        ->required()
                                        ->maxLength(255),
                                ]),
                        ]),

                    Step::make('Delivery Information')
                        ->completedIcon('heroicon-m-hand-thumb-up')
                        ->description('Put your correct address')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('city')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('post_code')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('address')
                                        ->required()
                                        ->maxLength(255),
                                ]),
                        ]),
                    Step::make('Payment Information')
                        ->completedIcon('heroicon-m-hand-thumb-up')
                        ->description('Review your payment')
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    TextInput::make('booking_trx_id')
                                        ->required()
                                        ->maxLength(255),

                                    ToggleButtons::make('is_paid')
                                        ->label('Apakah sudah membayar?')
                                        ->boolean()
                                        ->grouped()
                                        ->icons([
                                            true => 'heroicon-o-pencil',
                                            false => 'heroicon-o-clock',
                                        ])
                                        ->required(),

                                    FileUpload::make('proof')
                                        ->image()
                                        ->directory('proofs')
                                        ->required(),
                                ]),
                        ]),

                ])
                    ->columnSpan('full')
                    ->columns(1)
                    ->skippable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        // return BookingTransactionsTable::configure($table);
        return $table
            ->columns([
                TextColumn::make('name'),

                TextColumn::make('booking_trx_id')
                    ->label('Booking ID'),

                IconColumn::make('is_paid')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->label('Terverifikasi'),
                ImageColumn::make('proof')
                    ->label('Payment Information')
                    ->disk('public'),

            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('approve')
                    ->label('Approve')
                    ->action(function (BookingTransaction $record) {
                        $record->is_paid = true;
                        $record->save();

                        Notification::make()
                            ->title('Order Approved')
                            ->success()
                            ->body('The order has been successfuly approved.')
                            ->send();
                    })
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (BookingTransaction $record) => !$record->is_paid)
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookingTransactions::route('/'),
            'create' => CreateBookingTransaction::route('/create'),
            'edit' => EditBookingTransaction::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
