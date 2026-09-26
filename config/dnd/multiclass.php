<?php

/*
 * Dati del multiclasse (Manuale del Giocatore, cap. 6).
 *
 * ATTENZIONE: questo file **non** è generato da tools/convert-data.mjs, a
 * differenza di tutti gli altri sotto config/dnd. La vecchia applicazione non
 * gestiva il multiclasse, quindi non c'era niente da convertire e questi valori
 * sono stati scritti a mano dal manuale.
 */

return [

    /*
     * I prerequisiti: punteggi minimi per entrare in una classe, e per
     * uscirne. Servono in entrambe le direzioni — per prendere Paladino
     * bisogna soddisfare i requisiti del Paladino *e* della classe che si ha
     * già.
     *
     * Ogni voce è un elenco di gruppi: dentro un gruppo basta una
     * caratteristica ('any'), fra i gruppi servono tutti.
     */
    'prerequisites' => [
        'Barbaro' => [['str']],
        'Bardo' => [['cha']],
        'Chierico' => [['wis']],
        'Druido' => [['wis']],
        // Al Guerriero basta una delle due.
        'Guerriero' => [['str', 'dex']],
        'Ladro' => [['dex']],
        'Mago' => [['int']],
        // Al Monaco servono entrambe: due gruppi da una.
        'Monaco' => [['dex'], ['wis']],
        'Paladino' => [['str'], ['cha']],
        'Ranger' => [['dex'], ['wis']],
        'Stregone' => [['cha']],
        'Warlock' => [['cha']],
    ],

    'minimum_score' => 13,

    /*
     * Le competenze che arrivano prendendo la classe come SECONDA.
     *
     * Sono molte meno di quelle iniziali, e soprattutto: **nessun tiro
     * salvezza**. Quelli restano quelli della prima classe, sempre. È la
     * ragione per cui nel gioco conta quale classe si è presa per prima.
     *
     * Qui si tiene solo ciò che il sistema modella davvero — le abilità.
     * Armi, armature e strumenti non hanno una rappresentazione sulla scheda.
     */
    'skills_on_entry' => [
        // Il Bardo sceglie una abilità qualsiasi.
        'Bardo' => ['count' => 1, 'from' => 'any'],
        'Ladro' => ['count' => 1, 'from' => ['acrobatics', 'athletics', 'deception', 'insight',
            'intimidation', 'investigation', 'perception', 'performance', 'persuasion',
            'sleightOfHand', 'stealth']],
        'Ranger' => ['count' => 1, 'from' => ['animalHandling', 'athletics', 'insight',
            'investigation', 'nature', 'perception', 'stealth', 'survival']],
    ],

];
