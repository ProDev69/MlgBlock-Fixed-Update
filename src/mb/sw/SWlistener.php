<?php

/*
 *                _   _
 *  ___  __   __ (_) | |   ___
 * / __| \ \ / / | | | |  / _ \
 * \__ \  \ / /  | | | | |  __/
 * |___/   \_/   |_| |_|  \___|
 *
 * SkyWars plugin for PocketMine-MP & forks
 *
 * @Author: mb
 * @Kik: _mb_
 * @Telegram_Group: https://telegram.me/mb
 * @E-mail: thesville@gmail.com
 * @Github: https://github.com/mbx/SkyWars-PocketMine
 *
 * Copyright (C) 2016 mb
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * DONORS LIST :
 * - Ahmet
 * - Jinsong Liu
 * - no one
 *
 */

namespace mb\sw;

use pocketmine\block\Block;
use pocketmine\Server;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\block\BlockPlaceEvent;

use pocketmine\level\Position;
use pocketmine\level\Location;

use pocketmine\Item\Item;

use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;


class SWlistener implements Listener {

    /** @var SWmain */
    private $plugin;

    public function __construct(SWmain $plugin)
    {
        $this->plugin = $plugin;
    }
/*
    public function onPortalBed(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        switch($player->getInventory()->getItemInHand()->getId()){
            case Item::BED:
                Server::getInstance()->getCommandMap()->dispatch($player, "mb quit");
            break;
			case Item::END_PORTAL;
                Server::getInstance()->getCommandMap()->dispatch($player, "mb quit");
			break;
		}
	}
*/
    public function onSignChange(SignChangeEvent $event) : void
    {
        $player = $event->getPlayer();
        if (!$player->isOp() || $event->getLine(0) !== 'mb') {
            return;
        }

        $arena = $event->getLine(1);
        if (!isset($this->plugin->arenas[$arena])) {
            $player->sendMessage(TextFormat::RED . "This arena doesn't exist, try " . TextFormat::GOLD . "/sw create");
            return;
        }

        if (in_array($arena, $this->plugin->signs)) {
            $player->sendMessage(TextFormat::RED . "A sign for this arena already exist, try " . TextFormat::GOLD . "/sw signdelete");
            return;
        }

        $block = $event->getBlock();
        $level = $block->getLevel();
        $level_name = $level->getFolderName();

        foreach ($this->plugin->arenas as $name => $arena_instance) {
            if ($arena_instance->getWorld() === $level_name) {
                $player->sendMessage(TextFormat::RED . "You can't place the join sign inside arenas.");
                return;
            }
        }

        if (!$this->plugin->arenas[$arena]->checkSpawns()) {
            $player->sendMessage(TextFormat::RED . "You haven't configured all the spawn points for this arena, use " . TextFormat::YELLOW . "/sw setspawn");
            return;
        }

        $this->plugin->setSign($arena, $block);
        $this->plugin->refreshSigns($arena);

        $event->setLine(0, $this->plugin->configs["1st_line"]);
        $event->setLine(1, str_replace("{SWNAME}", $this->plugin->arenas[$arena]->getName(), $this->plugin->configs["2nd_line"]));
        $player->sendMessage(TextFormat::GREEN . "Successfully created join sign for '" . TextFormat::YELLOW . $arena . TextFormat::GREEN . "'!");
    }

    public function onInteract(PlayerInteractEvent $event) : void
    {
        $block = $event->getBlock();
        $player = $event->getPlayer();
        $name = $player->getName();
        $item = $player->getInventory()->getItemInHand();
        $itemid = $item->getID();
        if (($block->getId() === Block::SIGN_POST || $block->getId() === Block::WALL_SIGN) && $event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
            $arena = $this->plugin->getArenaFromSign($block);
            if ($arena !== null) {
                $player = $event->getPlayer();
                if ($this->plugin->getPlayerArena($player) === null) {
                    $this->plugin->arenas[$arena]->join($player);
                }
            }
        }
		if($item->getName() === "§cLOBBY"){
			
$player->addTitle("§cWelcome To Lobby", 10, 30, 10);
			
	$player->getLevel()->addSound((new \pocketmine\level\sound\FizzSound($player)), [$player]);
		Server::getInstance()->getCommandMap()->dispatch($player, "mb quit"); 
     }
 
  if($item->getName() === "§aINFO MLGBLOCK"){
  	
	$player->sendMessage("§eIt is an online game that is played between the stronger players. It is the one who will win the tour only. Here is help. If you fall from the top, you just have to press the feathers.");	
	     
$player->addTitle("§cINFO §bMLGBLOCK", 10, 30, 10);

$player->getLevel()->addSound((new \pocketmine\level\sound\FizzSound($player)), [$player]);
} 
 }

    public function onLevelChange(EntityLevelChangeEvent $event) : void
    {//no fucking clue why this check exists
        $player = $event->getEntity();
        if ($player instanceof Player && $this->plugin->getPlayerArena($player) !== null) {
            $event->setCancelled();
        }
    }

    public function onDropItem(PlayerDropItemEvent $event) : void
    {
        $player = $event->getPlayer();
        $arena = $this->plugin->getPlayerArena($player);

        if ($arena !== null) {
            $type = $arena->inArena($player);
            if ($type === SWarena::PLAYER_SPECTATING || ($type === SWarena::PLAYER_PLAYING && !$this->plugin->configs["player.drop.item"])) {
                $event->setCancelled();
            }
        }
    }

    public function onPickUp(InventoryPickupItemEvent $event) : void
    {
        $player = $event->getInventory()->getHolder();
        if ($player instanceof Player && ($arena = $this->plugin->getPlayerArena($player)) !== null && $arena->inArena($player) === SWarena::PLAYER_SPECTATING) {
            $event->setCancelled();
        }
    }

    public function onItemHeld(PlayerItemHeldEvent $event) : void
    {
        $player = $event->getPlayer();
        if ($player instanceof Player && ($arena = $this->plugin->getPlayerArena($player)) !== null && $arena->inArena($player) === SWarena::PLAYER_SPECTATING) {
            $item = $event->getItem();
            if (($item->getId() . ':' . $item->getDamage()) === $this->plugin->configs["spectator.quit.item"]) {
                $arena->closePlayer($player);
            }
            $event->setCancelled();
            $player->getInventory()->setHeldItemIndex(1);
        }
    }

    public function onMove(PlayerMoveEvent $event) : void
    {
        $from = $event->getFrom();
        $to = $event->getTo();
        $player = $event->getPlayer();
        if (floor($from->x) !== floor($to->x) || floor($from->z) !== floor($to->z) || floor($from->y) !== floor($from->y)) {//moved a block
            $arena = $this->plugin->getPlayerArena($player);
            if ($arena !== null) {
                if ($arena->GAME_STATE === SWarena::STATE_COUNTDOWN) {
                    $event->setCancelled();
                } elseif ($arena->void >= floor($to->y)) {
                    $player->attack(new EntityDamageEvent($player, EntityDamageEvent::CAUSE_VOID, 100));
                }
                return;
            }
            if ($this->plugin->configs["sign.knockBack"]) {
                foreach ($this->plugin->getNearbySigns($to, $this->plugin->configs["knockBack.radius.from.sign"]) as $pos) {
                    $player->knockBack($player, 0, $from->x - $pos->x, $from->z - $pos->z, $this->plugin->configs["knockBack.intensity"] / 5);
                    break;
                }
            }
        }
    }
    public function onQuit(PlayerQuitEvent $event) : void
    {
        $player = $event->getPlayer();
        $arena = $this->plugin->getPlayerArena($player);

        if ($arena !== null) {
            $arena->closePlayer($player);
        }
    }

    public function onDeath(PlayerDeathEvent $event) : void
    {
        $player = $event->getPlayer();
        $arena = $this->plugin->getPlayerArena($player);

        if ($arena !== null) {
            $this->plugin->sendDeathMessage($player);
            $arena->closePlayer($player);
            $event->setDeathMessage("");
            $player->addTitle("§l§cYOU DIED!", "§r§7You are now a lobby!");

            if (!$this->plugin->configs["drops.on.death"]) {
                $event->setDrops([]);
            }
        }
    }

    /**
     * @param EntityDamageEvent $event
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function onDamage(EntityDamageEvent $event) : void
    {
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            $arena = $this->plugin->getPlayerArena($entity);
            if ($arena !== null) {
                if (
                    $arena->inArena($entity) !== SWarena::PLAYER_PLAYING ||
                    $arena->GAME_STATE === SWarena::STATE_COUNTDOWN ||
                    $arena->GAME_STATE === SWarena::STATE_NOPVP ||
                    in_array($event->getCause(), $this->plugin->configs["damage.cancelled.causes"])
                ) {
                    $event->setCancelled();
                    return;
                }
                if ($event instanceof EntityDamageByEntityEvent && ($damager = $event->getDamager()) instanceof Player) {
                    if ($arena->inArena($damager) !== SWarena::PLAYER_PLAYING) {
                        $damager->getLevel()->broadcastLevelSoundEvent(new Vector3($damager->getX(), $damager->getY(), $damager->getZ()), LevelSoundEventPacket::SOUND_NOTE, [$damager]);
                        $event->setCancelled();
                        return;
                    }
                }
                if ($this->plugin->configs["death.spectator"]) {
                    if ($entity->getHealth() <= $event->getFinalDamage()) {
                        $event->setCancelled();
                        $this->plugin->sendDeathMessage($entity);
                        if ($this->plugin->configs["drops.on.death"]) {
                            $entity->getInventory()->dropContents($entity->getLevel(), $entity->asVector3());
                        }
                        $arena->closePlayer($entity, false, true);
                    }
                }
            }
        }
    }

    public function onRespawn(PlayerRespawnEvent $event) : void
    {
        if ($this->plugin->configs["always.spawn.in.defaultLevel"]) {
            $event->setRespawnPosition($this->plugin->getServer()->getDefaultLevel()->getSpawnLocation());
        }
        
        

        if ($this->plugin->configs["clear.inventory.on.respawn&join"]) {
            $event->getPlayer()->getInventory()->clearAll();
        }

        if ($this->plugin->configs["clear.effects.on.respawn&join"]) {
            $event->getPlayer()->removeAllEffects();        
        }
    }

    public function onBreak(BlockBreakEvent $event) : void
    {
        $player = $event->getPlayer();
        $arena = $this->plugin->getPlayerArena($player);

        if ($arena !== null && $arena->inArena($player) !== SWarena::PLAYER_PLAYING) {
            $event->setCancelled();
        }

        $block = $event->getBlock();
        $sign = $this->plugin->getArenaFromSign($block);
        if ($sign !== null) {
            if (!$player->isOp()) {
                $event->setCancelled();
                return;
            }

            $this->plugin->deleteSign($block);
            $player->sendMessage(TextFormat::GREEN . "Removed join sign for arena '" . TextFormat::YELLOW . $arena . TextFormat::GREEN . "'!");
        }
    }

    public function onPlace(BlockPlaceEvent $event) : void
    {
        $player = $event->getPlayer();
        $arena = $this->plugin->getPlayerArena($player);

        if ($arena !== null && $arena->inArena($player) !== SWarena::PLAYER_PLAYING) {
            $event->setCancelled();
        }
    }


    public function onCommand(PlayerCommandPreprocessEvent $event) : void
    {
        $command = $event->getMessage();
        if ($command[0] === "/") {
            $player = $event->getPlayer();
            if ($this->plugin->getPlayerArena($player) !== null) {
                if (in_array(strtolower(explode(" ", $command, 2)[0]), $this->plugin->configs["banned.commands.while.in.game"])) {
                    $player->sendMessage($this->plugin->lang["banned.command.msg"]);
                    $event->setCancelled();
                }
            }
        }
    }
}