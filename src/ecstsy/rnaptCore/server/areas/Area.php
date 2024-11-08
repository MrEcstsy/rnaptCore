<?php

declare(strict_types=1);

namespace ecstsy\rnaptCore\server\areas;

use pocketmine\world\Position;
use pocketmine\world\World;
use pocketmine\Server;

final class Area
{
    private int $minX;
    private int $maxX;
    private int $minY;
    private int $maxY;
    private int $minZ;
    private int $maxZ;

    public function __construct(
        public string $area_name,
        public string $world_name,
        public int $x1,
        public int $y1,
        public int $z1,
        public int $x2,
        public int $y2,
        public int $z2,
        public string $settings
    ) {
        $this->minX = min($this->x1, $this->x2);
        $this->maxX = max($this->x1, $this->x2);
        $this->minY = min($this->y1, $this->y2);
        $this->maxY = max($this->y1, $this->y2);
        $this->minZ = min($this->z1, $this->z2);
        $this->maxZ = max($this->z1, $this->z2);
    }

    /**
     * Get the area's name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->area_name;
    }

    /**
     * Get the world of the area.
     *
     * @return World|null
     */
    public function getWorld(): ?World
    {
        return Server::getInstance()->getWorldManager()->getWorldByName($this->world_name);
    }

    /**
     * Get the coordinates of the area as positions.
     *
     * @return array
     */
    public function getBoundaryPositions(): array
    {
        $world = $this->getWorld();
        if ($world === null) {
            return [];
        }
    
        return [
            new Position($this->x1, $this->y1, $this->z1, $world),
            new Position($this->x2, $this->y2, $this->z2, $world),
        ];
    }

    /**
     * Get a specific setting value.
     *
     * @param string $setting
     * @return mixed
     */
    public function getSetting(string $setting, $default = null): mixed
    {
        $settings = $this->getSettingsArray();
        return $settings[$setting] ?? $default;
    }    

    /**
     * Convert settings JSON string to an array.
     *
     * @return array
     */
    public function getSettingsArray(): array {
        $settings = json_decode($this->settings, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return []; 
        }
        return $settings ?? [];
    }    

    /**
     * Set or update a setting for the area.
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
     * Check if a given position is inside the area.
     *
     * @param Position $position
     * @return bool
     */
    public function isPositionInside(Position $position): bool
    {
        return $position->x >= $this->minX && $position->x <= $this->maxX
            && $position->y >= $this->minY && $position->y <= $this->maxY
            && $position->z >= $this->minZ && $position->z <= $this->maxZ;
    }
}
