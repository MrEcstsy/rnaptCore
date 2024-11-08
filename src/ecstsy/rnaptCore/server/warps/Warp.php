<?php

declare(strict_types=1);

namespace ecstsy\rnaptCore\server\warps;

use ecstsy\rnaptCore\commands\ShortWarpCommand;
use ecstsy\rnaptCore\Loader;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\World;
use pocketmine\utils\TextFormat as C;
use pocketmine\world\particle\EndermanTeleportParticle;
use pocketmine\world\sound\EndermanTeleportSound;
use Ramsey\Uuid\UuidInterface;

final class Warp
{


    public function __construct(
        public string        $warp_name,
        public string        $world_name,
        public int           $x,
        public int           $y,
        public int           $z,
        public string        $settings
    )
    {
        if ($this->getSetting('command_register') === true) {
            $this->commandRegister();
        }
    }

    /**
     * Get warps name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->warp_name;
    }

    /**
     * Get the world of the warp
     *
     * @return World|null
     */
    public function getWorld(): ?World
    {
        return Server::getInstance()->getWorldManager()->getWorldByName($this->world_name);
    }

    /**
     * Get the position of the warp
     *
     * @return Position|null
     */
    public function getPosition(): ?Position
    {
        return ($world = $this->getWorld()) === null ? null : (new Position($this->x, $this->y, $this->z, $world));
    }

    /**
     * Teleport player to this warp.
     *
     * @param Player $player
     * @throws \RuntimeException
     */
    public function teleport(Player $player): void
    {
        $lang = Loader::getLanguageManager();
    
        if ($this->getSetting("permit_required") === false) {
            $permission = "core.warps." . strtolower($this->getName());
    
            if (!$player->hasPermission($permission) && !Server::getInstance()->isOp($player->getName())) {
                $player->sendMessage(C::colorize(str_replace(
                    ["{warp}", "{prefix}"], 
                    [$this->getName(), $lang->getNested("warps.prefix")], 
                    $lang->getNested("warps.no-permission")
                )));
                return;
            }
        }
    
        if (($pos = $this->getPosition()) === null) {
            throw new \RuntimeException("The target world is not available for teleport. Perhaps the world isn't loaded?");
        }
    
        if ($this->getSetting('send_title') === true) {
            $player->sendTitle(C::colorize($lang->getNested("warps.teleport.title")));
            $player->sendSubTitle(C::colorize(str_replace(
                ["{warp}", "{prefix}"], 
                [$this->getName(), $lang->getNested("warps.prefix")], 
                $lang->getNested("warps.teleport.subtitle")
            )));
        }
    
        $add_particle = $this->getSetting('add_particle');
        $add_sound = $this->getSetting('add_sound');
    
        Loader::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player, $add_particle, $add_sound): void {
            $world = $player->getWorld();
    
            if ($add_particle === true) {
                $world->addParticle($player->getPosition()->asVector3(), new EndermanTeleportParticle());
            }
    
            if ($add_sound === true) {
                $world->addSound($player->getPosition()->asVector3(), new EndermanTeleportSound());
            }
        }), 5);
    
        $player->teleport($pos);
    }
    

    /**
     * Toggle a setting between true and false.
     *
     * @param string $setting
     */
    public function toggleSetting(string $setting): void
    {
        $settings = $this->getSettingsArray();
        $settings[$setting] = !$this->getSetting($setting);
        $this->settings = json_encode($settings);
    }

    /**
     * Set a specific setting value directly.
     *
     * @param string $setting
     * @param bool $value
     */
    public function setSetting(string $setting, bool $value): void
    {
        $settings = $this->getSettingsArray();
        $settings[$setting] = $value; 
        $this->settings = json_encode($settings);
    }

    /**
     * Get a specific setting value.
     *
     * @param string $setting
     * @return mixed
     */
    public function getSetting(string $setting): mixed
    {
        $settings = $this->getSettingsArray(); 
        return $settings[$setting] ?? null; 
    }

    /**
     * Convert settings JSON string to an array.
     *
     * @return array
     */
    public function getSettingsArray(): array
    {
        return json_decode($this->settings, true) ?? []; 
    }

    /** 
     * Check if the command register is enabled.
     */
    public function isCommandRegister(): bool {
        return $this->getSetting('command_registered');
    }

    public function commandRegister(): void {
        $commandMap = Server::getInstance()->getCommandMap();
        $existingCommand = $commandMap->getCommand($this->getName());
    
        if ($existingCommand instanceof ShortWarpCommand) {
            Loader::getInstance()->getLogger()->info("Command {$this->getName()} already registered.");
            return;
        }
    
        $commandMap->register("Core", new ShortWarpCommand(Loader::getInstance(), $this->getName()));
        Loader::getInstance()->getLogger()->info("Command {$this->getName()} registered.");
        WarpManager::getInstance()->syncCommandData();
    }
    

    public function commandUnregister(): void {
        $command = Server::getInstance()->getCommandMap()->getCommand($this->getName());

        if (!$command instanceof ShortWarpCommand) {
            return;
        }

        Server::getInstance()->getCommandMap()->unregister($command);
        WarpManager::getInstance()->syncCommandData();
    }

    public function setIsCommandRegister(bool $isCommandRegister): void {
        $this->setSetting('command_registered', $isCommandRegister);

        if ($isCommandRegister === true) {
            $this->commandRegister();
        } else {
            $this->commandUnregister();
        }
    }
}