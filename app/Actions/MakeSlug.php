<?php

namespace App\Actions;

use Illuminate\Support\Str;

class MakeSlug
{
    /**
     * Accented vowels and their plain form. Diaeresis (ϊ, ϋ) is kept so it can block digraphs.
     */
    private const ACCENTS = ['ά' => 'α', 'έ' => 'ε', 'ή' => 'η', 'ί' => 'ι', 'ό' => 'ο', 'ύ' => 'υ', 'ώ' => 'ω'];

    /**
     * ELOT 743 transliteration of Greek digraphs and letters. strtr() matches the longest keys first.
     */
    private const TRANSLITERATION = [
        'ου' => 'ou', 'μπ' => 'mp', 'ντ' => 'nt', 'γκ' => 'gk', 'γγ' => 'ng', 'γξ' => 'nx', 'γχ' => 'nch',
        'α' => 'a', 'β' => 'v', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z', 'η' => 'i', 'θ' => 'th',
        'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => 'x', 'ο' => 'o', 'π' => 'p',
        'ρ' => 'r', 'σ' => 's', 'ς' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'χ' => 'ch', 'ψ' => 'ps',
        'ω' => 'o', 'ϊ' => 'i', 'ΐ' => 'i', 'ϋ' => 'y', 'ΰ' => 'y',
    ];

    /**
     * Build a URL slug, transliterating Greek according to ELOT 743.
     *
     * "Γιώργος Παπαδόπουλος" → "giorgos-papadopoulos", "Ομόνοια Λευκωσίας" → "omonoia-lefkosias".
     */
    public function handle(string $text): string
    {
        $greek = strtr(mb_strtolower($text, 'UTF-8'), self::ACCENTS);

        $greek = preg_replace_callback_array([
            // αυ/ευ/ηυ are "af/ef/if" before voiceless consonants or at the end of a word, "av/ev/iv" otherwise.
            '/([αεη])υ(?=[θκξπστφχψ]|[^\p{Greek}]|$)/u' => fn (array $match) => self::TRANSLITERATION[$match[1]].'f',
            '/([αεη])υ/u' => fn (array $match) => self::TRANSLITERATION[$match[1]].'v',
            // μπ/ντ/γκ are pronounced "b/d/g" at the start of a word.
            '/(?<!\p{Greek})μπ/u' => fn () => 'b',
            '/(?<!\p{Greek})ντ/u' => fn () => 'd',
            '/(?<!\p{Greek})γκ/u' => fn () => 'g',
        ], $greek);

        return Str::slug(strtr($greek, self::TRANSLITERATION));
    }
}
