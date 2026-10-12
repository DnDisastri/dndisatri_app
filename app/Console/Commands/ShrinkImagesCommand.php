<?php

namespace App\Console\Commands;

use App\Models\AboutPage;
use App\Models\Build;
use App\Models\Campaign;
use App\Models\Character;
use App\Models\Event;
use App\Models\Map;
use App\Models\Npc;
use App\Models\Post;
use App\Support\ImageShrinker;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

/** Riduce le immagini caricate prima che si riducessero da sole al salvataggio. */
class ShrinkImagesCommand extends Command
{
    protected $signature = 'dndisastri:riduci-immagini
        {--dry-run : Mostra quanto si risparmierebbe senza toccare i file}';

    protected $description = 'Riduce le immagini già caricate (copertine, sfondi, ritratti, mappe)';

    /** @var list<class-string<Model>> */
    private const MODELLI = [Campaign::class, Event::class, Post::class, AboutPage::class, Build::class, Map::class, Npc::class, Character::class];

    public function handle(ImageShrinker $shrinker): int
    {
        $prova = (bool) $this->option('dry-run');
        $righe = [];
        $totale = 0;

        foreach (self::MODELLI as $classe) {
            foreach ((new $classe)->imageLimits() as $colonna => $lato) {
                $classe::query()->whereNotNull($colonna)->orderBy('id')->each(
                    function (Model $record) use ($shrinker, $colonna, $lato, $prova, &$righe, &$totale) {
                        $risparmio = $shrinker->shrink('public', $record->{$colonna}, $lato, $prova);

                        if ($risparmio !== null) {
                            $totale += $risparmio;
                            $righe[] = [class_basename($record), $record->getKey(), $record->{$colonna}, $this->kb($risparmio)];
                        }
                    }
                );
            }
        }

        if ($righe === []) {
            $this->info('Nessuna immagine da ridurre.');

            return self::SUCCESS;
        }

        $this->table(['Tipo', 'Id', 'File', 'Risparmio'], $righe);
        $this->info(($prova ? 'Prova: niente è stato toccato. Si risparmierebbero ' : 'Fatto. Risparmiati ').$this->kb($totale).'.');

        return self::SUCCESS;
    }

    private function kb(int $byte): string
    {
        return number_format($byte / 1024, 0, ',', '.').' KB';
    }
}
