<?php

namespace ecstsy\rnaptCore\utils\uis;

use ecstsy\rnaptCore\Loader;
use pocketmine\player\Player;
use Vecnavium\FormsUI\SimpleForm;
use pocketmine\utils\TextFormat as C;
use Vecnavium\FormsUI\CustomForm;

class AreaGuardUi {

    public static function getOpenAreaGuardForm(Player $player): SimpleForm {
        $lang = Loader::getLanguageManager();
        $form = new SimpleForm(function (Player $player, $data): void {
            if ($data === null) { return; }

            switch ($data) {
                case 0:
                    $player->sendForm(AreaGuardUi::getManageAreaForm($player));
                    break;
                case 1:
                    break;
            }
        });

        $form->setTitle(C::colorize($lang->getNested("areaguard.title")));
        $form->addButton(C::colorize($lang->getNested("areaguard.ui.manage-areas")));
        $form->addButton(C::colorize($lang->getNested("areaguard.ui.quit-button")));
        return $form;
    }

    public static function getManageAreaForm(Player $player): SimpleForm {
        $lang = Loader::getLanguageManager();
        $form = new SimpleForm(function (Player $player, $data): void {
           if ($data === null) { return; }
           
           switch ($data) {
               case 0:
                $player->sendForm(AreaGuardUi::getCreateNewAreaForm($player));
                break;
               case 1:
                $player->sendForm(AreaGuardUi::getManageAreaForm($player));
                break;
           }
        });
        $form->setTitle(C::colorize($lang->getNested("areaguard.title")));

        $form->addButton(C::colorize($lang->getNested("areaguard.ui.new-area")));
        $form->addButton(C::colorize($lang->getNested("areaguard.ui.return-button")));
        $form->addButton(C::colorize($lang->getNested("areaguard.ui.quit-button")));
        return $form;
    }

    public static function getCreateNewAreaForm(Player $player): CustomForm
    {
        $lang = Loader::getLanguageManager();
        $form = new CustomForm(function (Player $player, $data) use($lang): void {
            if ($data === null) {
                $player->sendMessage(C::colorize($lang->getNested("areaguard.ui.form-closed")));
                return;
            }
    
            $areaName = $data['areaName'] ?? null;
            $expandVertically = $data['expand'] ?? false;
    
            if (empty($areaName)) {
                $player->sendMessage(C::colorize($lang->getNested("areaguard.ui.invalid-area-name")));
                return;
            }
    
            $areaManager = Loader::getInstance()->getAreaManager();
            $areaManager->startAreaCreationProcess($player, $areaName, $expandVertically);
    
            $player->sendMessage(C::colorize($lang->getNested("areaguard.ui.start-creation", [
                'areaName' => $areaName,
                'expand' => $expandVertically ? $lang->getNested("areaguard.ui.expanded-yes") : $lang->getNested("areaguard.ui.expanded-no")
            ])));
        });

        $form->setTitle(C::colorize($lang->getNested("areaguard.title")));
        $form->addInput(C::colorize($lang->getNested("areaguard.ui.set-area-name")), '', null, 'areaName');
        $form->addToggle(C::colorize($lang->getNested("areaguard.ui.expand-vertically")), false, 'expand');
    
        return $form;
    }
    
}