<?php

namespace ecstsy\rnaptCore;

use ecstsy\rnaptCore\commands\CoinFlipCommand;
use ecstsy\rnaptCore\commands\RelicsCommand;
use ecstsy\rnaptCore\commands\ShortWarpCommand;
use ecstsy\rnaptCore\commands\WarpCommand;
use ecstsy\rnaptCore\commands\WarpManagerCommand;
use ecstsy\rnaptCore\listeners\AreaListener;
use ecstsy\rnaptCore\listeners\EventListener;
use ecstsy\rnaptCore\listeners\RelicListener;
use ecstsy\rnaptCore\managers\RelicsManager;
use ecstsy\rnaptCore\player\PlayerManager;
use ecstsy\rnaptCore\server\areas\AreaManager;
use ecstsy\rnaptCore\server\warps\WarpManager;
use ecstsy\rnaptCore\utils\LanguageManager;
use ecstsy\rnaptCore\utils\QueryStmts;
use ecstsy\rnaptCore\utils\Utils;
use JackMD\ConfigUpdater\ConfigUpdater;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;

class Loader extends PluginBase {

    use SingletonTrait;

    private const CFG_VERSION = 1;

    public static DataConnector $connector;

    public static LanguageManager $lang;

    public static PlayerManager $manager;

    public static WarpManager $warpManager;

    public static AreaManager $areaManager;

    protected function onLoad(): void {
        self::setInstance($this);
    }

    protected function onEnable(): void {
        $subDirectories = ["locale", "features"];

        foreach ($subDirectories as $directory) {
            $this->saveAllFilesInDirectory($directory);
        }

        ConfigUpdater::checkUpdate($this, $this->getConfig(), "version", self::CFG_VERSION);

        self::$connector = libasynql::create($this, ["type" => "sqlite", "sqlite" => ["file" => "sqlite.sql"], "worker-limit" => 2], ["sqlite" => "sqlite.sql"]);
        self::$connector->executeGeneric(QueryStmts::PLAYERS_INIT);
        self::$connector->executeGeneric(QueryStmts::COINFLIP_INIT);
        self::$connector->executeGeneric(QueryStmts::WARPS_INIT);
        self::$connector->executeGeneric(QueryStmts::AREA_INIT);
        self::$connector->waitAll();

        self::$manager = new PlayerManager($this);
        self::$lang = new LanguageManager($this->getConfig()->getNested("settings.language"));
        self::$warpManager = new WarpManager($this);
        self::$areaManager = new AreaManager($this);

        $listeners = [
            new EventListener(),
            new RelicListener(),
            new AreaListener($this->getAreaManager())
        ];

        foreach ($listeners as $listener) {
            $this->getServer()->getPluginManager()->registerEvents($listener, $this);
        }


        $this->getServer()->getCommandMap()->registerAll("Core", [
            new RelicsCommand($this, "relics", "Allows you to spawn in relics"),
            new CoinFlipCommand($this, "coinflip", "CoinFlip Command", ["cf"]),
            new WarpManagerCommand($this, $this->getLanguageManager()->getNested("warps.commands.warpmanager.name"), $this->getLanguageManager()->getNested("warps.commands.warpmanager.description")),
            new WarpCommand($this, $this->getLanguageManager()->getNested("warps.commands.warp.name"), $this->getLanguageManager()->getNested("warps.commands.warp.description")),
        ]);

        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }

        RelicsManager::init();
        
    }

    protected function onDisable(): void {
        if (isset(self::$connector)) {
            self::$connector->close();
        }
    }

    public static function getDatabase(): DataConnector {
        return self::$connector;
    }

    public static function getLanguageManager(): LanguageManager {
        return self::$lang;
    }

    public static function getPlayerManager(): PlayerManager {
        return self::$manager;
    }

    public static function getWarpManager(): WarpManager {
        return self::$warpManager;
    }

    public static function getAreaManager(): AreaManager {
        return self::$areaManager;
    }

    private function saveAllFilesInDirectory(string $directory): void {
        $resourcePath = $this->getFile() . "resources/$directory/";
        if (!is_dir($resourcePath)) {
            $this->getLogger()->warning("Directory $directory does not exist.");
            return;
        }

        $files = scandir($resourcePath);
        if ($files === false) {
            $this->getLogger()->warning("Failed to read directory $directory.");
            return;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $this->saveResource("$directory/$file");
        }
    }
}