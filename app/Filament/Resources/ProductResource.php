<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;

class ProductResource extends Resource
{
    protected static int $globalSearchResultsLimit = 3;
    
    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    private static array $statuses = [
        'in stock' => 'in stock',
        'sold out' => 'sold out',
        'coming soon' => 'coming soon',
    ];


    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return self::getUrl('view', ['record' => $record]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'description'];
    }

    public static function getNavigationLabel(): string
    {
        return __('All Products');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([


                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Main data')->schema([
                        Forms\Components\Tabs::make()->tabs([

                            Forms\Components\Tabs\Tab::make('Main data')->schema([



                                Forms\Components\Section::make('Main data')
                                    ->description('What users totally need to fill in')
                                    ->schema([

                                        Forms\Components\TextInput::make('name')->required()->unique(ignoreRecord: true)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', str()->slug($state))),


                                        Forms\Components\TextInput::make('slug')->disabledOn('edit')->hiddenOn('edit')->visibleOn('create')
                                        ->required(), 


                                        Forms\Components\RichEditor::make('description')
                                            ->columnSpanFull()
                                            ->required(),

                                    ]),
                            ]),


                            Forms\Components\Tabs\Tab::make('Additional data')->schema([

                                Forms\Components\Fieldset::make('Additional data')->schema([
                                    Forms\Components\TextInput::make('price')->rule('numeric')->required(),
                                    Forms\Components\Radio::make('enabled')->options([
                                        0 => 'enabled',
                                        1 => 'disabled',
                                    ]),
                                    Forms\Components\Select::make('status')->options(self::$statuses),
                                    Forms\Components\Select::make('category_id')->relationship('category', 'name'),
                                    Forms\Components\Select::make('tags')->relationship('tags', 'name')->multiple()

                                ])

                            ])
                        ])

                    ])
                ])
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(isIndividual: true, isGlobal: false),
                // Tables\Columns\TextInputColumn::make('name')->rules(['required', 'min:3']), 
                Tables\Columns\TextColumn::make('price')->sortable()->money('eur')->getStateUsing(function (Product $record): float {
                    return $record->price / 100;
                })->alignEnd(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'in stock' => 'primary',
                    'sold out' => 'danger',
                    'coming soon' => 'info',
                }),
                Tables\Columns\TextColumn::make('enabled')->alignEnd(),
                Tables\Columns\TextColumn::make('category.name')->label('Category name'),
                Tables\Columns\ToggleColumn::make('is_active')->onColor('success') // default value: "primary"
                    ->offColor('danger'), // default value: "gray",

                /*
                Tables\Columns\SelectColumn::make('status')
                ->options(self::$statuses),
                */

                //Tables\Columns\CheckboxColumn::make('is_active'), 
                /*
                Tables\Columns\TextColumn::make('category.name')
                ->url(function (Product $product): string {
                    return CategoryResource::getUrl('edit', [
                        'record' => $product->category_id
                    ]);
                }),
                */

                Tables\Columns\TextColumn::make('tags.name')->badge(),
                Tables\Columns\TextColumn::make('created_at')->dateTime('m/d/Y H:i'),
                //Tables\Columns\TextColumn::make('created_at', 'created_at')->since(),
            ])
            ->defaultSort('price', 'desc')
            ->filters([
                Tables\Filters\Filter::make('is_featured')->query(fn (Builder $query): Builder => $query->where('enabled', true)),
                Tables\Filters\SelectFilter::make('status')
                    ->options(self::$statuses),
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
                Tables\Filters\Filter::make('created_from')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            );
                    }),
                Tables\Filters\Filter::make('created_until')
                    ->form([
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)->filtersFormColumns(5)
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ViewAction::make()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])->defaultSort('price', 'desc')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TagsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
            'view' => Pages\ViewProduct::route('/{record}')
        ];
    }
}
