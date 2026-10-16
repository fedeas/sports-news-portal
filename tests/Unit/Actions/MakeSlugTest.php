<?php

use App\Actions\MakeSlug;

it('transliterates Greek text according to ELOT 743', function (string $text, string $slug) {
    expect((new MakeSlug)->handle($text))->toBe($slug);
})->with([
    'names' => ['Γιώργος Παπαδόπουλος', 'giorgos-papadopoulos'],
    'ου digraph and χ' => ['Μαρία Χριστοδούλου', 'maria-christodoulou'],
    'ευ before a voiceless consonant' => ['Ομόνοια Λευκωσίας', 'omonoia-lefkosias'],
    'ευ before a vowel and γγ' => ['Ευάγγελος', 'evangelos'],
    'αυ before a voiceless consonant' => ['Ναύτης', 'naftis'],
    'αυ before a voiced consonant' => ['Αυγή', 'avgi'],
    'μπ at the start of a word' => ['Μπάσκετ', 'basket'],
    'μπ inside a word' => ['Ολυμπιακός', 'olympiakos'],
    'γκ at the start of a word' => ['Γκολ', 'gol'],
    'ψ and final sigma' => ['Ψυχής', 'psychis'],
    'diaeresis' => ['Προϊόν', 'proion'],
    'uppercase with punctuation and numbers' => ['ΑΠΟΕΛ – Ανόρθωση 2-1', 'apoel-anorthosi-2-1'],
    'mixed Latin and Greek' => ['Champions League: Ολυμπιακός', 'champions-league-olympiakos'],
    'no letters' => ['!!!', ''],
]);
