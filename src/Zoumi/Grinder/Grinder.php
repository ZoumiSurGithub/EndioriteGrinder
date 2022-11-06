<?php

namespace Zoumi\Grinder;

use pocketmine\block\VanillaBlocks;
use pocketmine\item\ItemIds;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use Zoumi\Grinder\listeners\BlockListener;

class Grinder extends PluginBase {
    use SingletonTrait;

    public static array $structure = [];

    protected function onLoad(): void
    {
        self::setInstance($this);
    }

    protected function onEnable(): void
    {
        $this->saveDefaultConfig();
        self::$structure = [
            //BASE
            "0:-1:0" => $this->getConfig()->get("first-block"),
            "-1:-1:0" => $this->getConfig()->get("first-block"),
            "0:-1:-1" => $this->getConfig()->get("first-block"),
            "-1:-1:-1" => $this->getConfig()->get("first-block"),
            "1:-1:0" => $this->getConfig()->get("first-block"),
            "0:-1:1" => $this->getConfig()->get("first-block"),
            "1:-1:1" => $this->getConfig()->get("first-block"),
            "1:-1:-1" => $this->getConfig()->get("first-block"),
            "-1:-1:1" => $this->getConfig()->get("first-block"),

            //CENTER
            "0:0:0" => VanillaBlocks::LAVA()->asItem()->getId(),

            //FACE
            "-1:0:0" => $this->getConfig()->get("second-block"),
            "0:0:-1" => $this->getConfig()->get("second-block"),
            "1:0:0" => $this->getConfig()->get("second-block"),
            "0:0:1" => $this->getConfig()->get("second-block"),

            //COIN
            "-1:0:-1" => $this->getConfig()->get("first-block"),
            "1:0:1" => $this->getConfig()->get("first-block"),
            "1:0:-1" => $this->getConfig()->get("first-block"),
            "-1:0:1" => $this->getConfig()->get("first-block"),

            //HAUT
            "0:1:0" => $this->getConfig()->get("first-block"),
            "-1:1:0" => $this->getConfig()->get("first-block"),
            "0:1:-1" => $this->getConfig()->get("first-block"),
            "-1:1:-1" => $this->getConfig()->get("first-block"),
            "1:1:0" => $this->getConfig()->get("first-block"),
            "0:1:1" => $this->getConfig()->get("first-block"),
            "1:1:1" => $this->getConfig()->get("first-block"),
            "1:1:-1" => $this->getConfig()->get("first-block"),
            "-1:1:1" => $this->getConfig()->get("first-block"),
        ];
        $this->getServer()->getPluginManager()->registerEvents(new BlockListener(), $this);
    }

}