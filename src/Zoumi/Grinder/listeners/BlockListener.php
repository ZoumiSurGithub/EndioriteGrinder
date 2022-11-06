<?php

namespace Zoumi\Grinder\listeners;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\sound\AnvilUseSound;
use Zoumi\Grinder\Grinder;

class BlockListener implements Listener
{

    /**
     * @param BlockBreakEvent $event
     * @return void
     */
    public function onBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $item = $event->getItem();
        if (in_array($block->asItem()->getId(), Grinder::getInstance()->getConfig()->get("ores"))) {
            if ($item->getNamedTag()->getInt("smelt", 0) === 1) {
                $event->setDrops($this->getDrops($block));
            }
            if ($item->getNamedTag()->getInt("fortune", 0) === 1) {
                $drops = $event->getDrops();
                $ndrops = [];
                foreach ($drops as $item) {
                    $ndrops[] = $item->setCount($item->getCount() * mt_rand(1, 3));
                }
                $event->setDrops($ndrops);
            }
        }
    }

    /**
     * @param PlayerItemHeldEvent $event
     * @return void
     */
    public function onHeld(PlayerItemHeldEvent $event): void
    {
        $item = $event->getItem();
        $player = $event->getPlayer();
        if ($item->getNamedTag()->getInt("speed", 0) === 1) {
            $player->getEffects()->add(new EffectInstance(VanillaEffects::HASTE(), 99999999, 2, true));
        } else {
            if ($player->getEffects()->has(VanillaEffects::HASTE())) {
                $player->getEffects()->remove(VanillaEffects::HASTE());
            }
        }
    }

    public static function getDrops(Block $block): array
    {
        $array = [];
        switch ($block->asItem()->getId()) {
            case ItemIds::DIAMOND_ORE:
                $array[] = VanillaItems::DIAMOND();
                break;
            case ItemIds::EMERALD_ORE:
                $array[] = VanillaItems::EMERALD();
                break;
            case ItemIds::GOLD_ORE:
                $array[] = VanillaItems::GOLD_INGOT();
                break;
            case ItemIds::IRON_ORE:
                $array[] = VanillaItems::IRON_INGOT();
                break;
            case -745:
                $array[] = ItemFactory::getInstance()->get(951);
                break;
            case -746:
                $array[] = ItemFactory::getInstance()->get(952);
                break;
            case -747:
                $array[] = ItemFactory::getInstance()->get(954);
                break;
        }
        return $array;
    }

    /**
     * @param PlayerInteractEvent $event
     * @return void
     */
    public function onInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if ($block->asItem()->getId() === (int)Grinder::getInstance()->getConfig()->get("third-block")) {
            $vecBlock = $block->getPosition();
            $vecPlayer = $player->getPosition()->floor();
            $vecDelta = $vecBlock->subtract($vecPlayer->getX(), $vecPlayer->getY(), $vecPlayer->getZ())
                ->ceil()
                ->normalize()
                ->round();
            $vecDelta = $vecBlock->add($vecDelta->getX(), 0, $vecDelta->getZ());

            $world = $player->getWorld();

            $pass = true;
            foreach (Grinder::$structure as $vec => $blockId) {
                $vec = explode(":", $vec);
                $vec = new Vector3($vec[0], $vec[1], $vec[2]);
                $pos = $vecDelta->add($vec->getX(), $vec->getY(), $vec->getZ());
                $id = $world->getBlock($pos)->asItem()->getId();
                if (
                    $id !== $blockId
                ) {
                    if ($pos->equals($vecBlock) and $id === Grinder::getInstance()->getConfig()->get("third-block")) {
                        continue;
                    }
                    $pass = false;
                    break;
                }
            }
            if ($pass) {
                // INV HERE
                $this->sendMenu($player);
                return;
            }
        }
    }

    /**
     * @param Player $player
     * @return void
     */
    public function sendMenu(Player $player): void
    {
        $inv = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $inv->setName("Grinder");
        for ($i = 0; $i < $inv->getInventory()->getSize(); $i++) {
            $inv->getInventory()->setItem($i, VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::BLACK())->asItem()->setCustomName("§r§c--"));
        }
        $firstSlotRecipe = 10;
        $secondSlotRecipe = 28;
        $resultSlot = 25;
        $firstModifierSlot = 38;
        $secondModifierSlot = 40;
        $resultModifierSlot = 42;
        $inv->getInventory()->setItem($firstSlotRecipe, VanillaBlocks::AIR()->asItem());
        $inv->getInventory()->setItem($secondSlotRecipe, VanillaBlocks::AIR()->asItem());
        $inv->getInventory()->setItem($resultSlot, VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::RED())->asItem()->setCustomName("§r§c--"));
        $inv->getInventory()->setItem($firstModifierSlot, VanillaBlocks::AIR()->asItem());
        $inv->getInventory()->setItem($secondModifierSlot, VanillaBlocks::AIR()->asItem());
        $inv->getInventory()->setItem($resultModifierSlot, VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::RED())->asItem()->setCustomName("§r§c--"));
        $inv->setListener(function (InvMenuTransaction $transaction) use ($inv, $firstSlotRecipe, $secondSlotRecipe, $resultSlot, $player, $firstModifierSlot, $secondModifierSlot, $resultModifierSlot): InvMenuTransactionResult {
            $itemClicked = $transaction->getItemClicked();
            $itemClickedWith = $transaction->getItemClickedWith();
            $slot = $transaction->getAction()->getSlot();
            if ($itemClicked->getCustomName() === "§r§c--") {
                return $transaction->discard();
            }
            if ($slot === $firstSlotRecipe) {
                $config = Grinder::getInstance()->getConfig()->get("recipes-socket");
                foreach ($config as $id => $value) {
                    $itemSecond = $inv->getInventory()->getItem($secondSlotRecipe);
                    if ($itemClickedWith->getId() === $value["recipe"][0][0] && $itemSecond->getId() === $value["recipe"][1][0]) {
                        $inv->getInventory()->setItem($resultSlot, ItemFactory::getInstance()->get($value["result"]));
                        break;
                    } else {
                        $inv->getInventory()->setItem($resultSlot, VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::RED())->asItem()->setCustomName("§r§c--"));
                    }
                }
            } elseif ($slot === $secondSlotRecipe) {
                $config = Grinder::getInstance()->getConfig()->get("recipes-socket");
                foreach ($config as $id => $value) {
                    $itemFirst = $inv->getInventory()->getItem($firstSlotRecipe);
                    if ($itemFirst->getId() === $value["recipe"][0][0] && $itemClickedWith->getId() === $value["recipe"][1][0]) {
                        $inv->getInventory()->setItem($resultSlot, ItemFactory::getInstance()->get($value["result"]));
                        break;
                    } else {
                        $inv->getInventory()->setItem($resultSlot, VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::RED())->asItem()->setCustomName("§r§c--"));
                    }
                }
            } elseif ($slot === $resultSlot) {
                $item = $inv->getInventory()->getItem($slot);
                if ($item->getId() !== ItemIds::AIR) {
                    $player->getWorld()->addSound($player->getPosition(), new AnvilUseSound());
                    $itemFirst = $inv->getInventory()->getItem($firstSlotRecipe);
                    $itemSecond = $inv->getInventory()->getItem($secondSlotRecipe);
                    $inv->getInventory()->setItem($firstSlotRecipe, $itemFirst->setCount($itemFirst->getCount() - 1));
                    $inv->getInventory()->setItem($secondSlotRecipe, $itemSecond->setCount($itemSecond->getCount() - 1));
                    $inv->getInventory()->setItem($resultSlot, VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::RED())->asItem()->setCustomName("§r§c--"));
                }else{
                    $inv->getInventory()->setItem($resultSlot, VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::RED())->asItem()->setCustomName("§r§c--"));
                    return $transaction->discard();
                }
            }
            if ($slot === $firstModifierSlot) {
                $config = Grinder::getInstance()->getConfig()->get("recipes-modifier");
                foreach ($config as $id => $value) {
                    $itemSecond = $inv->getInventory()->getItem($secondModifierSlot);
                    if (in_array($itemClickedWith->getId(), Grinder::getInstance()->getConfig()->get("hammer-item")) && $itemSecond->getId() === $value["recipe"][0][0]) {
                        $itemResult = clone $itemClickedWith;
                        switch ($value["result"]) {
                            case "smelt":
                                if ($itemResult->getNamedTag()->getInt("smelt", 0) !== 1) {
                                    $itemResult->getNamedTag()->setInt("smelt", 1);
                                    $lore = $itemResult->getLore();
                                    $lore[] = "§r§eSmelt";
                                    $itemResult->setLore($lore);
                                } else {
                                    $itemResult = VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::RED())->asItem()->setCustomName("§r§c--");
                                }
                                break;
                            case "fortune":
                                if ($itemResult->getNamedTag()->getInt("fortune", 0) !== 1) {
                                    $itemResult->getNamedTag()->setInt("fortune", 1);
                                    $lore = $itemResult->getLore();
                                    $lore[] = "§r§eFortune";
                                    $itemResult->setLore($lore);
                                } else {
                                    $itemResult = VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::RED())->asItem()->setCustomName("§r§c--");
                                }
                                break;
                            case "speed":
                                if ($itemResult->getNamedTag()->getInt("speed", 0) !== 1) {
                                    $itemResult->getNamedTag()->setInt("speed", 1);
                                    $lore = $itemResult->getLore();
                                    $lore[] = "§r§eSpeed";
                                    $itemResult->setLore($lore);
                                } else {
                                    $itemResult = VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::RED())->asItem()->setCustomName("§r§c--");
                                }
                                break;
                            default:
                                $itemResult = VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::RED())->asItem()->setCustomName("§r§c--");
                                break;
                        }
                        $inv->getInventory()->setItem($resultModifierSlot, $itemResult);
                        break;
                    } else {
                        $inv->getInventory()->setItem($resultModifierSlot, VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::RED())->asItem()->setCustomName("§r§c--"));
                    }
                }
            } elseif ($slot === $secondModifierSlot) {
                $config = Grinder::getInstance()->getConfig()->get("recipes-modifier");
                foreach ($config as $id => $value) {
                    $itemFirst = $inv->getInventory()->getItem($firstModifierSlot);
                    if (in_array($itemFirst->getId(), Grinder::getInstance()->getConfig()->get("hammer-item")) && $itemClickedWith->getId() === $value["recipe"][0][0]) {
                        $itemResult = clone $itemFirst;
                        switch ($value["result"]) {
                            case "smelt":
                                if ($itemResult->getNamedTag()->getInt("smelt", 0) !== 1) {
                                    $itemResult->getNamedTag()->setInt("smelt", 1);
                                    $lore = $itemResult->getLore();
                                    $lore[] = "§r§eSmelt";
                                    $itemResult->setLore($lore);
                                } else {
                                    $itemResult = VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::RED())->asItem()->setCustomName("§r§c--");
                                }
                                break;
                            case "fortune":
                                if ($itemResult->getNamedTag()->getInt("fortune", 0) !== 1) {
                                    $itemResult->getNamedTag()->setInt("fortune", 1);
                                    $lore = $itemResult->getLore();
                                    $lore[] = "§r§eFortune";
                                    $itemResult->setLore($lore);
                                } else {
                                    $itemResult = VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::RED())->asItem()->setCustomName("§r§c--");
                                }
                                break;
                            case "speed":
                                if ($itemResult->getNamedTag()->getInt("speed", 0) !== 1) {
                                    $itemResult->getNamedTag()->setInt("speed", 1);
                                    $lore = $itemResult->getLore();
                                    $lore[] = "§r§eSpeed";
                                    $itemResult->setLore($lore);
                                } else {
                                    $itemResult = VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::RED())->asItem()->setCustomName("§r§c--");
                                }
                                break;
                            default:
                                $itemResult = VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::RED())->asItem()->setCustomName("§r§c--");
                                break;
                        }
                        $inv->getInventory()->setItem($resultModifierSlot, $itemResult);
                        break;
                    } else {
                        $inv->getInventory()->setItem($resultModifierSlot, VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::RED())->asItem()->setCustomName("§r§c--"));
                    }
                }
            } elseif ($slot === $resultModifierSlot) {
                $item = $inv->getInventory()->getItem($slot);
                if ($item->getId() !== ItemIds::AIR) {
                    $player->getWorld()->addSound($player->getPosition(), new AnvilUseSound());
                    $itemFirst = $inv->getInventory()->getItem($firstModifierSlot);
                    $itemSecond = $inv->getInventory()->getItem($secondModifierSlot);
                    $inv->getInventory()->setItem($firstModifierSlot, $itemFirst->setCount($itemFirst->getCount() - 1));
                    $inv->getInventory()->setItem($secondModifierSlot, $itemSecond->setCount($itemSecond->getCount() - 1));
                    $inv->getInventory()->setItem($resultModifierSlot, VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::RED())->asItem()->setCustomName("§r§c--"));
                }else{
                    $inv->getInventory()->setItem($resultModifierSlot, VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::RED())->asItem()->setCustomName("§r§c--"));
                    return $transaction->discard();
                }
            }
            return $transaction->continue();
        });
        $inv->setInventoryCloseListener(function (Player $playerInv, Inventory $inventory) use ($inv, $player): void {
            if ($playerInv->getName() === $player->getName()) {
                foreach ($inv->getInventory()->getContents() as $item) {
                    if ($item->getCustomName() !== "§r§c--") {
                        if ($playerInv->getInventory()->canAddItem($item)) {
                            $playerInv->getInventory()->addItem($item);
                        } else {
                            $playerInv->dropItem($item);
                        }
                    }
                }
            }
        });
        $inv->send($player);
    }

}