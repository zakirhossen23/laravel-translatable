<?php

namespace Zakirhossen23\Translatable\Validation;

use Zakirhossen23\Translatable\Locales;
use Illuminate\Contracts\Config\Repository;
use InvalidArgumentException;

class RuleFactory
{
    const FORMAT_ARRAY = 1;

    const FORMAT_KEY = 2;

    /**
     * @var int
     */
    protected $format;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var string
     */
    protected $suffix;

    /**
     * @var null|array<string>
     */
    protected $locales = null;

    public function __construct(Repository $config, ?int $format = null, ?string $prefix = null, ?string $suffix = null)
    {
        $this->format = $format ?? $config->get('translatable.rule_factory.format');
        $this->prefix = $prefix ?? $config->get('translatable.rule_factory.prefix');
        $this->suffix = $suffix ?? $config->get('translatable.rule_factory.suffix');
    }

    /**
     * Create a set of validation rules.
     *
     * @param  array<mixed>  $rules  The validation rules to be parsed.
     * @param  int|null  $format  The format to be used for parsing (e.g., 'dot' or 'bracket').
     * @param  string|null  $prefix  The prefix to be applied to each rule key.
     * @param  string|null  $suffix  The suffix to be applied to each rule key.
     * @param  array<string>|null  $locales  The locales to be used for translating rule attributes.
     * @return array<string,mixed> The parsed validation rules.
     */
    public static function make(array $rules, ?int $format = null, ?string $prefix = null, ?string $suffix = null, ?array $locales = null): array
    {
        /** @var RuleFactory $factory */
        $factory = app(static::class, compact('format', 'prefix', 'suffix'));

        $factory->setLocales($locales);

        return $factory->parse($rules);
    }

    /**
     * Set the locales to be used for translating rule attributes.
     *
     * @param  array<string>|null  $locales  The locales to be set. If null, all available locales will be used.
     *
     * @throws \InvalidArgumentException If a provided locale is not defined in the available locales.
     */
    public function setLocales(?array $locales = null): self
    {
        /** @var Locales */
        $helper = app(Locales::class);

        if (is_null($locales)) {
            $this->locales = $helper->all();

            return $this;
        }

        foreach ($locales as $locale) {
            if (! $helper->has($locale)) {
                throw new InvalidArgumentException(sprintf('The locale [%s] is not defined in available locales.', $locale));
            }
        }

        $this->locales = $locales;

        return $this;
    }

    /**
     * Parse the input array of rules, applying format and translation to translatable attributes.
     *
     * @param  array<mixed>  $input  The input array of rules to be parsed.
     * @return array<mixed> The parsed array of rules.
     */
    public function parse(array $input): array
    {
        $rules = [];

        foreach ($input as $key => $value) {
            if (! $this->isTranslatable($key)) {
                $rules[$key] = $value;

                continue;
            }

            foreach ($this->locales as $locale) {
                $rules[$this->formatKey($locale, $key)] = $this->formatRule($locale, $value);
            }
        }

        return $rules;
    }

    protected function formatKey(string $locale, string $key): string
    {
        return $this->replacePlaceholder($locale, $key);
    }

    /**
     * @param  string|string[]|mixed  $rule
     * @return string|string[]|mixed
     */
    protected function formatRule(string $locale, $rule)
    {
        if (is_string($rule)) {
            if (strpos($rule, '|')) {
                return implode('|', array_map(function (string $rule) use ($locale) {
                    return $this->replacePlaceholder($locale, $rule);
                }, explode('|', $rule)));
            }

            return $this->replacePlaceholder($locale, $rule);
        } elseif (is_array($rule)) {
            return array_map(function ($rule) use ($locale) {
                return $this->formatRule($locale, $rule);
            }, $rule);
        }

        return $rule;
    }

    protected function replacePlaceholder(string $locale, string $value): string
    {
        return preg_replace($this->getPattern(), $this->getReplacement($locale), $value);
    }

    protected function getReplacement(string $locale): string
    {
        switch ($this->format) {
            case self::FORMAT_KEY:
                return '$1:'.$locale;
            default:
            case self::FORMAT_ARRAY:
                return $locale.'.$1';
        }
    }

    protected function getPattern(): string
    {
        $prefix = preg_quote($this->prefix);
        $suffix = preg_quote($this->suffix);

        return '/'.$prefix.'([^\.'.$prefix.$suffix.']+)'.$suffix.'/';
    }

    protected function isTranslatable(string $key): bool
    {
        return strpos($key, $this->prefix) !== false && strpos($key, $this->suffix) !== false;
    }
}
