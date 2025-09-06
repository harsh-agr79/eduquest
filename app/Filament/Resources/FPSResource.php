<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FPSResource\Pages;
use App\Filament\Resources\FPSResource\RelationManagers;
use App\Models\FPS;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FPSResource extends Resource
{
    protected static ?string $model = FPS::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Repeater::make('data')
                    ->schema([
                        Forms\Components\TextInput::make('question')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Repeater::make('options')
                        ->schema([
                            Forms\Components\TextInput::make('option')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Checkbox::make('is_correct')
                                ->label('Correct Answer'),
                        ])
                        ->required(),
                        Forms\Components\Textarea::make('explanation')
                            ->maxLength(65535),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListFPS::route('/'),
            'create' => Pages\CreateFPS::route('/create'),
            'edit' => Pages\EditFPS::route('/{record}/edit'),
        ];
    }
}
