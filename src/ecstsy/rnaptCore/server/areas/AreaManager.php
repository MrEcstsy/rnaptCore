<?php

declare(strict_types=1);

namespace ecstsy\rnaptCore\server\areas;

use ecstsy\rnaptCore\Loader;
use ecstsy\rnaptCore\utils\QueryStmts;
use pocketmine\block\Block;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\Position;

final class AreaManager
{
    use SingletonTrait;

    /** @var Area[] */
    private array $areas = [];

    private $processCreationNewArea = [];

    public function __construct(
        public Loader $plugin
    ){
        self::setInstance($this);
        $this->loadAreas();
    }

    /**
     * Load all areas from the database.
     */
    private function loadAreas(): void
    {
        Loader::getDatabase()->executeSelect(QueryStmts::AREA_SELECT, [], function (array $rows): void {
            foreach ($rows as $row) {
                $area = new Area(
                    $row["area_name"],
                    $row["world_name"],
                    $row["x1"],
                    $row["y1"],
                    $row["z1"],
                    $row["x2"],
                    $row["y2"],
                    $row["z2"],
                    $row["settings"]
                );

                $this->areas[$area->getName()] = $area;
            }
        });
    }

    /**
     * Create a new area.
     *
     * @param string $area_name
     * @param Player $player
     * @param array $settings
     */
    public function createArea(string $area_name, Player $player, Position $pos1, Position $pos2, array $settings = []): void
    {

        if ($this->isAreaOverlapping($pos1, $pos2)) {
            $player->sendMessage("The area overlaps with an existing area.");
            return;
        }

        $encodedSettings = json_encode($settings);

        $args = [
            "area_name"  => $area_name,
            "world_name" => $player->getWorld()->getFolderName(),
            "x1"         => $pos1->getFloorX(),
            "y1"         => $pos1->getFloorY(),
            "z1"         => $pos1->getFloorZ(),
            "x2"         => $pos2->getFloorX(),
            "y2"         => $pos2->getFloorY(),
            "z2"         => $pos2->getFloorZ(),
            "settings"   => $encodedSettings,
        ];

        Loader::getDatabase()->executeInsert(QueryStmts::AREA_CREATE, $args);

        $this->areas[$area_name] = new Area(
            $args["area_name"],
            $args["world_name"],
            $args["x1"],
            $args["y1"],
            $args["z1"],
            $args["x2"],
            $args["y2"],
            $args["z2"],
            $args["settings"]
        );
    }

    /**
     * Check if a new area would overlap with existing areas.
     *
     * @param Position $pos1
     * @param Position $pos2
     * @return bool
     */
    public function isAreaOverlapping(Position $pos1, Position $pos2): bool
    {
        foreach ($this->areas as $area) {
            if ($area->isPositionInside($pos1) || $area->isPositionInside($pos2)) {
                return true; 
            }
        }
        return false;
    }

    /**
     * Get an area by its name.
     *
     * @param string $area_name
     * @return Area|null
     */
    public function getArea(string $area_name): ?Area
    {
        return $this->areas[$area_name] ?? null;
    }

    /**
     * Get a list of all areas.
     *
     * @return Area[]
     */
    public function getAreas(): array
    {
        return $this->areas;
    }

    /**
     * Delete an area.
     *
     * @param string $area_name
     */
    public function deleteArea(string $area_name): void
    {
        Loader::getDatabase()->executeChange(QueryStmts::AREA_DELETE, [
            "area_name" => $area_name
        ]);

        unset($this->areas[$area_name]);
    }

    /**
     * Check if a position is within any area.
     *
     * @param Position $position
     * @return Area|null
     */
    public function getAreaByPosition(Position $position): ?Area
    {
        foreach ($this->areas as $area) {
            if ($area->isPositionInside($position)) {
                return $area;
            }
        }

        return null;
    }

    public function startAreaCreationProcess(Player $player, string $areaName, bool $expandVertically): void
    {
        $this->processCreationNewArea[$player->getName()] = [
            'areaName' => $areaName,
            'expand' => $expandVertically,
            'world' => null,
            'pos1' => null,
            'pos2' => null,
            'settings' => []
        ];

        $player->sendMessage("Please select the first block for your area.");
    }

    public function handleBlockInteractionForAreaCreation(Player $player, Block $block): bool
    {
        $playerName = $player->getName();
        if (!isset($this->processCreationNewArea[$playerName])) return false;

        $areaData = $this->processCreationNewArea[$playerName];
        $blockPosition = $block->getPosition();
        $xyz = [$blockPosition->getFloorX(), $blockPosition->getFloorY(), $blockPosition->getFloorZ()];
        $pos = implode(':', $xyz);

        if ($areaData['pos1'] === null) {
            $this->processCreationNewArea[$playerName]['pos1'] = $blockPosition;
            $this->processCreationNewArea[$playerName]['world'] = $blockPosition->getWorld();

            $player->sendMessage("First position set at: " . implode(', ', $xyz));
            $player->sendMessage("Please select the second block for your area.");
        } elseif ($areaData['pos2'] === null) {
            if ($this->processCreationNewArea[$playerName]['world'] === $blockPosition->getWorld()) {
                $this->processCreationNewArea[$playerName]['pos2'] = $blockPosition;

                $player->sendMessage("Second position set at: " . implode(', ', $xyz));

                $pos1 = $areaData['pos1'];
                $pos2 = $blockPosition;

                if ($areaData['expand']) {
                    $pos1->y = 0; 
                    $pos2->y = 256; 
                }

                $this->createArea($areaData['areaName'], $player, $pos1, $pos2, $areaData['settings']);

                $player->sendMessage("Area {$areaData['areaName']} has been successfully created.");
                unset($this->processCreationNewArea[$playerName]);

            } else {
                $player->sendMessage("Both positions must be in the same world.");
            }
        }

        return true;
    }

}
