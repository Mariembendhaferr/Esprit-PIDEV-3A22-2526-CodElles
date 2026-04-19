<?php

namespace App\Service;

use Lara\AccessKey;
use Lara\LaraException;
use Lara\Translator;
use Lara\TranslatorOptions;

/**
 * Wraps Lara's official SDK (challenge auth at /v2/auth, then Bearer on /translate).
 * A naive HTTP client with X-API-ID headers does not work with api.laratranslate.com.
 */
class LaraTranslateService
{
    private ?Translator $translator = null;

    public function __construct(
        private readonly ?string $apiId = null,
        private readonly ?string $apiSecret = null,
        private readonly ?string $serverBaseUrl = null,
        /** Absolute path to cacert.pem — required on Windows PHP without curl.cainfo (SSL error 60). */
        private readonly ?string $sslCaBundlePath = null,
    ) {
    }

    public function translate(string $text, string $source, string $target): string
    {
        $text = trim($text);
        if ('' === $text || $source === $target) {
            return $text;
        }

        $translator = $this->getTranslator();
        if (null === $translator) {
            return $text;
        }

        $sourceLocale = $this->toLaraLocale($source);
        $targetLocale = $this->toLaraLocale($target);

        try {
            $result = $translator->translate($text, $sourceLocale, $targetLocale);
            $out = $result->getTranslation();

            if (\is_string($out)) {
                $trimmed = trim($out);

                return '' !== $trimmed ? $trimmed : $text;
            }

            if (\is_array($out)) {
                $strings = [];
                foreach ($out as $part) {
                    if (\is_string($part)) {
                        $strings[] = $part;
                    }
                }

                $joined = trim(implode("\n", $strings));

                return '' !== $joined ? $joined : $text;
            }
        } catch (LaraException) {
            // Credentials or network/API error — keep original text
        }

        return $text;
    }

    /**
     * Detects whether the text is French or English, then translates to $target (fr|en).
     * Used when the UI only chooses the display language.
     */
    public function translateToLanguage(string $text, string $target): string
    {
        $text = trim($text);
        $target = strtolower($target);
        if ('' === $text || !\in_array($target, ['fr', 'en'], true)) {
            return $text;
        }

        $translator = $this->getTranslator();
        if (null === $translator) {
            return $text;
        }

        try {
            $detectResult = $translator->detect($text, null, ['fr-FR', 'en-US']);
            $source = $this->shortLanguageFromLara($detectResult->getLanguage());

            if ($source === $target) {
                return $text;
            }

            return $this->translate($text, $source, $target);
        } catch (LaraException) {
            return $this->translate($text, $target === 'fr' ? 'en' : 'fr', $target);
        }
    }

    private function shortLanguageFromLara(string $laraLanguageCode): string
    {
        $c = strtolower($laraLanguageCode);
        if (str_starts_with($c, 'en')) {
            return 'en';
        }
        if (str_starts_with($c, 'fr')) {
            return 'fr';
        }

        return 'fr';
    }

    private function getTranslator(): ?Translator
    {
        if (null !== $this->translator) {
            return $this->translator;
        }

        $this->exportSslCaBundleEnv();

        if (null === $this->apiId || '' === $this->apiId || null === $this->apiSecret || '' === $this->apiSecret) {
            return null;
        }

        try {
            $auth = new AccessKey($this->apiId, $this->apiSecret);
            $base = $this->normalizeServerBaseUrl($this->serverBaseUrl);
            $options = null;
            if (null !== $base && '' !== $base) {
                $options = new TranslatorOptions(['serverUrl' => $base]);
            }
            $this->translator = new Translator($auth, $options);
        } catch (\Throwable) {
            return null;
        }

        return $this->translator;
    }

    /**
     * Lara SDK uses libcurl; on Windows, PHP often has no CA file → errno 60. Patched vendor HttpClient reads APP_LARA_CAINFO.
     */
    private function exportSslCaBundleEnv(): void
    {
        $path = $this->sslCaBundlePath;
        if (!\is_string($path) || '' === $path || !@is_readable($path)) {
            return;
        }

        $_ENV['APP_LARA_CAINFO'] = $path;
        putenv('APP_LARA_CAINFO='.$path);
    }

    /**
     * Lara expects API host root (e.g. https://api.laratranslate.com), not …/translate.
     */
    private function normalizeServerBaseUrl(?string $endpoint): ?string
    {
        if (null === $endpoint || '' === $endpoint) {
            return null;
        }

        $endpoint = rtrim($endpoint, '/');
        if (str_ends_with($endpoint, '/translate')) {
            return substr($endpoint, 0, -\strlen('/translate'));
        }

        return $endpoint;
    }

    private function toLaraLocale(string $short): string
    {
        return match (strtolower($short)) {
            'fr' => 'fr-FR',
            'en' => 'en-US',
            default => $short,
        };
    }
}
