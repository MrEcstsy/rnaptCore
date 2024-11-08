<?php

declare(strict_types=1);

namespace ecstsy\rnaptCore\tasks;

use ecstsy\rnaptCore\Loader;
use ecstsy\rnaptCore\managers\CoinFlipManager;
use ecstsy\rnaptCore\utils\Utils;
use muqsit\invmenu\InvMenu;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat as C;
use pocketmine\world\sound\PopSound;
use pocketmine\world\World;

class CoinFlipRollTask extends Task
{
    /** @var Item */
    private $head;
    /** @var Player */
    private $wagerer;
    /** @var InvMenu */
    private $menu;
    /** @var int */
    private $rollAmount = null;
    /** @var int */
    private $currentRoll = 0;

    public function __construct(InvMenu $menu, Item $head, Player $wagerer)
    {
        $this->menu = $menu;
        $this->head = $head;
        $this->wagerer = $wagerer;
        $this->rollAmount = mt_rand(20, 30);
    }
    
    public function onRun(): void
    {
        if ($this->currentRoll >= $this->rollAmount) {
            $this->end();
            return;
        }
        /** @var NamedTag $username */
        $username = $this->head->getNamedTag()->getTag("username");
        /** @var NamedTag $submitter */
        $submitter = $this->head->getNamedTag()->getTag("submitter");
        $username = $username->getValue() == $this->wagerer->getName() ? $submitter->getValue() : $this->wagerer->getName();

        $newItem = CoinFlipManager::getOppositeHead($this->head, $username);
        $this->menu->getInventory()->setItem(2, $newItem);

        $level = $this->wagerer->getWorld();
        if ($level instanceof World) {
            foreach ($this->menu->getInventory()->getViewers() as $player) {
                $sound = new PopSound();
                $level->addSound($player->getLocation()->asVector3(), $sound);
            }
        }

        $this->head = $newItem;

        ++$this->currentRoll;
    }

    public function end(): void
    {
        $lang = Loader::getLanguageManager();
    
        /** @var NamedTag $winne */
        $winne = $this->head->getNamedTag()->getTag("username");
        $winner = $winne->getValue();
        /** @var NamedTag $submitterNam */
        $submitterNam = $this->head->getNamedTag()->getTag("submitter");
        $submitterName = $submitterNam->getValue();
        $submitter = Loader::getInstance()->getServer()->getPlayerExact($submitterName);
        /** @var NamedTag $wager */
        $wager = $this->head->getNamedTag()->getTag("wager");
        $money = $wager->getValue();
        
        if ($this->wagerer->isOnline() || $submitter) {
            if ($winner === $this->wagerer->getName()) {
                $loser = $submitterName;
                $winnerMsg = $lang->getNested("coinflip.end.winner");
                $this->wagerer->sendMessage(C::colorize(str_replace(["{loser}", "{money}", "{prefix}"], [$loser, number_format($money), CoinFlipManager::getPrefix()], $winnerMsg)));
                
                if ($submitter) {
                    $loserMsg = $lang->getNested("coinflip.end.loser");
                    $submitter->sendMessage(C::colorize(str_replace(["{winner}", "{money}", "{prefix}"], [$winner, number_format($money), CoinFlipManager::getPrefix()], $loserMsg)));
                }
            } else {
                $loser = $this->wagerer->getName();
                $loserMsg = $lang->getNested("coinflip.end.loser");
                $this->wagerer->sendMessage(C::colorize(str_replace(["{winner}", "{money}", "{prefix}"], [$winner, number_format($money), CoinFlipManager::getPrefix()], $loserMsg)));
                
                if ($submitter) {
                    $winnerMsg = $lang->getNested("coinflip.end.winner");
                    $submitter->sendMessage(C::colorize(str_replace(["{loser}", "{money}", "{prefix}"], [$loser, number_format($money), CoinFlipManager::getPrefix()], $winnerMsg)));
                }
            }
        }
    
        $endItem = VanillaBlocks::GLASS_PANE()->asItem();
        $endItem->getNamedTag()->setString("ended", "true");
        $this->menu->getInventory()->setItem(0, $endItem);
    
        $player = Loader::getInstance()->getServer()->getPlayerExact($winner);
        $session = Loader::getPlayerManager()->getSession($player);
        $session->addBalance($money * 2);
        $this->getHandler()->cancel();
    }
    
}