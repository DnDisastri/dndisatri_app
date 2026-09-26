<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\Quest;
use App\Models\User;


describe('la sua scheda', function () {
    it('ha nome, ritratto e descrizione', function () {
        $campaign = Campaign::factory()->create([
            'quest_giver' => 'Maestra Ilva',
            'quest_giver_description' => 'Tiene il banco degli incarichi da trent\'anni.',
            'quest_giver_photo' => 'capigilda/ilva.jpg',
        ]);

        $fresh = $campaign->fresh();

        expect($fresh->hasQuestGiver())->toBeTrue()
            ->and($fresh->quest_giver_description)->toContain('trent\'anni')
            ->and($fresh->questGiverPhotoUrl())->toContain('capigilda/ilva.jpg');
    });

    it('e senza ritratto non inventa un indirizzo', function () {
        $campaign = Campaign::factory()->create(['quest_giver' => 'Maestra Ilva']);

        expect($campaign->fresh()->questGiverPhotoUrl())->toBeNull();
    });

    it('una campagna può non averlo affatto', function () {
        expect(Campaign::factory()->create(['quest_giver' => null])->hasQuestGiver())->toBeFalse();
    });
});
// Il capogilda vive sulla campagna: tutte le quest dello stesso tavolo leggono quindi gli stessi dati.
describe('il legame con le quest', function () {
    it('le quest lo leggono dalla campagna, non ne tengono una copia', function () {
        $campaign = Campaign::factory()->create(['quest_giver' => 'Maestra Ilva']);
        $quest = Quest::factory()->inCampaign($campaign)->create();

        expect($quest->questGiver())->toBe('Maestra Ilva');

        $campaign->forceFill(['quest_giver' => 'Maestro Corvo'])->save();

        expect($quest->fresh()->questGiver())->toBe('Maestro Corvo');
    });
});

describe('due tavoli, due capigilda', function () {
    it('lo stesso DM può averne uno diverso per ogni campagna', function () {
        $dm = User::factory()->dm()->create();

        $locanda = Campaign::factory()->runBy($dm)->create(['quest_giver' => 'Oste Bardo']);
        $corte = Campaign::factory()->runBy($dm)->create(['quest_giver' => 'Siniscalco Reale']);

        expect($locanda->fresh()->quest_giver)->toBe('Oste Bardo')
            ->and($corte->fresh()->quest_giver)->toBe('Siniscalco Reale');
    });

// Lo stesso NPC in campagne diverse è duplicato nei dati e può quindi divergere tra i tavoli.
    it('ma uno che torna in un tavolo nuovo è una copia, e le due possono divergere', function () {

        $prima = Campaign::factory()->create([
            'quest_giver' => 'Maestra Ilva',
            'quest_giver_description' => 'Tiene il banco degli incarichi.',
        ]);

        $seconda = Campaign::factory()->create([
            'quest_giver' => 'Maestra Ilva',
            'quest_giver_description' => 'Tiene il banco degli incarichi da trent\'anni.',
        ]);

        expect($prima->quest_giver)->toBe($seconda->quest_giver)
            ->and($prima->quest_giver_description)->not->toBe($seconda->quest_giver_description);
    });
});
