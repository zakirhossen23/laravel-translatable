<?php

namespace Zakirhossen23\Translatable;

use ArrayAccess;
use Zakirhossen23\Translatable\Exception\LocalesNotDefinedException;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;

/**
 * @implements Arrayable<string, string>
 * @implements ArrayAccess<string, string>
 */
class Locales implements Arrayable, ArrayAccess
{
    /**
     * @var ConfigContract
     */
    protected $config;

    /**
     * @var array<string,string>
     */
    protected $locales = [];

    /**
     * @var TranslatorContract
     */
    protected $translator;

    public function __construct(ConfigContract $config, TranslatorContract $translator)
    {
        $this->config = $config;
        $this->translator = $translator;

        $this->load();
    }

    public function add(string $locale): void
    {
        $this->locales[$locale] = $locale;
    }

    /**
     * @return array<string>
     */
    public function all(): array
    {
        return array_values($this->locales);
    }

    public function current(): string
    {

        $key = app()->getLocale()?: $this->config->get('translatable.locale') ;
        if (array_key_exists($key,$this->locales)){
            return $this->locales[$key];
        }else{
            return $key;
        }


    }

    public function forget(string $locale): void
    {
        unset($this->locales[$locale]);
    }

    public function get(string $locale): ?string
    {
        return $this->locales[$locale] ?? null;
    }

    public function getCountryLocale(string $locale, string $country): string
    {
        return $locale.$this->getLocaleSeparator().$country;
    }

    public function getLanguageFromCountryBasedLocale(string $locale): string
    {
        return explode($this->getLocaleSeparator(), $locale)[0];
    }

    public function getLocaleSeparator(): string
    {
        return $this->config->get('translatable.locale_separator') ?: '-';
    }

    public function has(string $locale): bool
    {
        return isset($this->locales[$locale]);
    }

    public function isLocaleCountryBased(string $locale): bool
    {
        return strpos($locale, $this->getLocaleSeparator()) !== false;
    }

    public function load(): void

    {
        $locales_table = $this->config->get('translatable.model_namespace','').$this->config->get('translatable.locales_table',null);
        $locales_table_column = $this->config->get('translatable.locales_table_column','');
        $locales_lang_id_column = $this->config->get('translatable.locales_lang_id_column','');

        $localesConfig = (array) $this->config->get('translatable.locales', []);

        if (!isset($locales_table) || empty($locales_table_column)|| empty($locales_lang_id_column)) {
            throw LocalesNotDefinedException::make();
        }

        $allLocales = $locales_table::get();


        $this->locales = [];
        foreach ($allLocales as  $locale) {
            $this->locales[$locale[$locales_table_column]] = $locale[$locales_lang_id_column];
        }
    }

    public function offsetExists($key): bool
    {
        return $this->has($key);
    }

    public function offsetGet($key): ?string
    {
        return $this->get($key);
    }

    public function offsetSet($key, $value): void
    {
        if (is_string($key) && is_string($value)) {
            $this->add($this->getCountryLocale($key, $value));
        } elseif (is_string($value)) {
            $this->add($value);
        }
    }

    public function offsetUnset($key): void
    {
        $this->forget($key);
    }

    public function toArray(): array
    {
        return $this->all();
    }
}
