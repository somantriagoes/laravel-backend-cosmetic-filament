<?php

namespace App\Filament\Resources\Cosmetics;

use App\Filament\Resources\Cosmetics\Pages\CreateCosmetic;
use App\Filament\Resources\Cosmetics\Pages\EditCosmetic;
use App\Filament\Resources\Cosmetics\Pages\ListCosmetics;
use App\Filament\Resources\Cosmetics\RelationManagers\TestimonialsRelationManager;
// use App\Filament\Resources\Cosmetics\Schemas\CosmeticForm;
// use App\Filament\Resources\Cosmetics\Tables\CosmeticsTable;
use App\Models\Cosmetic;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CosmeticResource extends Resource
{
    protected static ?string $model = Cosmetic::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = 'Products';
    
    public static function form(Schema $schema): Schema
    {
        // return CosmeticForm::configure($schema);
        return $schema
            ->schema([
                Fieldset::make('Details')
                    ->schema([
                        TextInput::make('name')
                            ->maxLength(255)
                            ->required()
                            ->label('Name Product'),
                        FileUpload::make('thumbnail')
                            ->image()
                            ->required()
                            ->label('Thumbnail'),
                        TextInput::make('price')
                            ->numeric()
                            ->prefix('IDR')
                            ->required(),
                        TextInput::make('stock')
                            ->numeric()
                            ->prefix('Qtys')
                            ->required(),
                    ]),

                Fieldset::make('Additional')
                    ->schema([
                        Repeater::make('benefits')
                            ->relationship('benefits')
                            ->schema([
                                TextInput::make('name'),
                            ]),
                        Repeater::make('photos')
                            ->relationship('photos')
                            ->schema([
                                FileUpload::make('photo')
                                    ->image()
                                    ->required(),
                            ]),
                        Textarea::make('about')
                            ->required(),
                        Select::make('is_populer')
                            ->options([
                                true => 'Populer',
                                false => 'Not Populer',
                            ])
                            ->required(),
                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('brand_id')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        // return CosmeticsTable::configure($table);
        return $table
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label('Photo'),
                TextColumn::make('name')
                    ->searchable()
                    ->label('Product Name'),
                TextColumn::make('category.name'),
                TextColumn::make('brand.name'),
                IconColumn::make('is_populer')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->label('Populer'),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name'),

                SelectFilter::make('brand_id')
                    ->label('Brand')
                    ->relationship('brand', 'name'),
                
                TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
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
            TestimonialsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCosmetics::route('/'),
            'create' => CreateCosmetic::route('/create'),
            'edit' => EditCosmetic::route('/{record}/edit'),
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
