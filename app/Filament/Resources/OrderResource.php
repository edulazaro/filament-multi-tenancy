<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Collection;

class OrderResource extends Resource
{
    protected static ?int $navigationSort = 6;

    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        $text = Order::whereDate('created_at', today())->count();
        $text2 = Order::whereDate('created_at', today())->count() ? ' NEW' : '';

        return $text . $text2;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('product.name'),
                Tables\Columns\TextColumn::make('user.name'),
                Tables\Columns\TextColumn::make('price')
                    ->money('usd')
                    ->getStateUsing(function (Order $record): float {
                        return $record->price / 100;
                    })
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->formatStateUsing(fn ($state) => '$' . number_format($state / 100, 2))
                    ),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultGroup('product.name')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('Change is completed')
                        ->icon('heroicon-o-check-badge')
                        ->fillForm(function (Order $order) {
                            return ['is_completed' => $order->is_completed];
                        })
                        ->form([
                            Forms\Components\Checkbox::make('is_completed'),
                        ])
                        ->action(function (Order $order, array $data): void {
                            $order->update(['is_completed' => $data['is_completed']]);
                        }),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('Mark as Completed')
                        ->icon('heroicon-o-check-badge')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['is_completed' => true]))
                        // The selected checkboxes are de-selected after the action is processed
                        ->deselectRecordsAfterCompletion()
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('New Order')
                    ->url(fn (): string => OrderResource::getUrl('create')),
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
