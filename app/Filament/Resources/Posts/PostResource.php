<?php

namespace App\Filament\Resources\Posts;

use App\Enums\Icon;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Posts\Schemas\PostForm;
use App\Filament\Resources\Posts\Tables\PostsTable;
use App\Models\Post;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Le news della gilda. Solo admin: lo impone `PostPolicy`, che Filament
 * interroga da solo.
 */
class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::News;

    protected static string|UnitEnum|null $navigationGroup = 'Redazione';

    protected static ?string $modelLabel = 'news';

    protected static ?string $pluralModelLabel = 'news';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return PostForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PostsTable::configure($table);
    }

    /**
     * Il pannello è un'altra cosa dal sito pubblico: l'elenco delle news lo
     * legge tutto il gruppo (`viewAny` nella policy è aperto), ma la sezione
     * di redazione è degli admin. Filament usa la stessa policy per entrambi,
     * quindi la distinzione va fatta qui.
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
