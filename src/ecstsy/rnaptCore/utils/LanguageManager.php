<?php

namespace ecstsy\rnaptCore\utils;

use ecstsy\rnaptCore\Loader;
use pocketmine\utils\Config;

class LanguageManager {

    private Config $config;
    private string $filePath;

    public function __construct(string $languageKey) {
        $pluginDataDir = Loader::getInstance()->getDataFolder();
        $localeDir = $pluginDataDir . '/locale/';
        $this->filePath = $localeDir . $languageKey . '.yml';
        
        if (!file_exists($this->filePath)) {
            throw new \RuntimeException("Language file not found for language key '$languageKey' at: " . $this->filePath);
        }
        
        $this->config = Utils::getConfiguration("locale/" . $languageKey . ".yml");
    }

    public function get(string $key): string {
        return $this->config->get($key, "Translation not found: " . $key);
    }

    public function getNested(string $key): mixed {
        return $this->config->getNested($key, "Translation not found: " . $key);
    }
    
    public function reload(): void {
        $this->config->reload();
    }

    public function getAll(): array {
        return $this->config->getAll();
    }
    
    public function getFilePath(): string {
        return $this->filePath;
    }
}