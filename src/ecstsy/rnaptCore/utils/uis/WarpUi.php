<?php

namespace ecstsy\rnaptCore\utils\uis;

use ecstsy\rnaptCore\Loader;
use ecstsy\rnaptCore\server\warps\Warp;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;
use Vecnavium\FormsUI\CustomForm;
use Vecnavium\FormsUI\SimpleForm;

class WarpUi {

    public static function getWarpManageForm(Player $player): SimpleForm {
        $lang = Loader::getLanguageManager();
        $form = new SimpleForm(function (Player $player, $data) {
            if ($data === null) {
                return;
            }

            switch ($data) {
                case 0:
                    $player->sendForm(self::getWarpCreateForm($player));
                    break;
                case 1:
                    $removeForm = self::getWarpRemoveForm($player);
                
                    if ($removeForm !== null) {
                        $player->sendForm($removeForm);
                        return;
                    }                    
                    break;
                case 2:
                    $editForm = self::getWarpEditListForm($player);

                    if ($editForm !== null) {
                        $player->sendForm($editForm);
                        return;
                    }
                    break;
            }
        });

        $form->setTitle(C::colorize($lang->getNested("warps.form.title")));

        $form->addButton(C::colorize($lang->getNested("warps.form.create")));
        $form->addButton(C::colorize($lang->getNested("warps.form.remove")));
        $form->addButton(C::colorize($lang->getNested("warps.form.edit")));
        return $form;
    }

    public static function getWarpCreateForm(Player $player): CustomForm {
        $lang = Loader::getLanguageManager();
        $warp = Loader::getWarpManager();
        $form = new CustomForm(function (Player $player, $data) use($warp, $lang) {
            if ($data === null) {
                $manageForm = self::getWarpManageForm($player);
                
                if ($manageForm !== null) {
                    $player->sendForm($manageForm);
                    return;
                }
                return;
            }

            if (!isset($data[0]) || $data[0] === "") {
                $player->sendMessage(C::colorize($lang->getNested("warps.create.input-name")));
                return;
            }

            $data[0] = (string)$data[0];

            if ($warp->getWarp($data[0]) !== null) {
                $player->sendMessage(C::colorize($lang->getNested("warps.create.already-exist")));
                return;
            }

            $settings = [
                "send_title"           => (bool) $data[1],  
                "add_particle" => (bool) $data[2],   
                "add_sound"           => (bool) $data[3],  
                "permit_required"         => (bool) $data[4],  
                "command_registered"      => (bool) $data[5],  
            ];

            $warp->createWarp($player, $data[0], $settings);
            $player->sendMessage(C::colorize(str_replace(["{warp}", "{prefix}"], [$data[0], $lang->getNested("warps.prefix")], $lang->getNested("warps.create.success"))));
        });

        $form->setTitle(C::colorize($lang->getNested("warps.create-form.title")));

        $form->addInput(C::colorize($lang->getNested("warps.create-form.input")));
        $form->addToggle(C::colorize($lang->getNested("warps.create-form.title-toggle")), true);
        $form->addToggle(C::colorize($lang->getNested("warps.create-form.particle-toggle")), true);
        $form->addToggle(C::colorize($lang->getNested("warps.create-form.sound-toggle")), true);
        $form->addToggle(C::colorize($lang->getNested("warps.create-form.permit-toggle")), true);
        $form->addToggle(C::colorize($lang->getNested("warps.create-form.command-register-toggle")), true);
        return $form;
    }

    public static function getWarpRemoveForm(Player $player): ?SimpleForm {
        $lang = Loader::getLanguageManager();
        $warpManager  = Loader::getWarpManager();
        $allWarps = $warpManager->getWarpList();

        $form = new SimpleForm(function (Player $player, $data) use ($warpManager, $lang, $allWarps) {
            if ($data === null) {
                $player->sendForm(self::getWarpManageForm($player));
                return;
            }
    
            if (isset($allWarps[$data])) {
                $selectedWarp = $allWarps[$data];
    
                $warpManager->deleteWarp($selectedWarp);
                $player->sendMessage(C::colorize(str_replace(["{warp}", "{prefix}"], [$selectedWarp->getName(), $lang->getNested("warps.prefix")], $lang->getNested("warps.remove.success"))));
            } else {
                $player->sendMessage(C::colorize($lang->getNested("warps.error")));
            }
        });

        $form->setTitle(C::colorize(Loader::getLanguageManager()->getNested("warps.remove-form.title")));


        if ($allWarps === null || count($allWarps) === 0) {
            $player->sendMessage(C::colorize($lang->getNested("warps.error")));
            return null;
        }

        foreach ($allWarps as $warp) {
            $buttonText = str_replace(["{warp}"], [$warp->getName()], $lang->getNested("warps.remove-form.button"));
            $form->addButton(C::colorize($buttonText));
        }

        return $form;
    }

    public static function getWarpEditListForm(Player $player): ?SimpleForm {
        $lang = Loader::getLanguageManager();
        $warpManager = Loader::getWarpManager();
        $form = new SimpleForm(function (Player $player, $data) use($lang, $warpManager) {
            if ($data === null) {
                $player->sendForm(self::getWarpManageForm($player));
                return;
            }

            $warpName = $warpManager->getWarpList()[$data]->getName();
        
            $player->sendForm(self::getWarpEditForm($warpName, $player));
        });
        
        $form->setTitle(C::colorize($lang->getNested("warps.edit-form.list.title")));

        $allWarps = $warpManager->getWarpList();

        if ($allWarps === null || count($allWarps) === 0) {
            $player->sendMessage(C::colorize($lang->getNested("warps.error")));
            return null;
        }

        foreach ($allWarps as $index => $warp) {
            $buttonText = str_replace(["{warp}"], [$warp->getName()], $lang->getNested("warps.edit-form.list.button"));
            $form->addButton(C::colorize($buttonText), 0, '', $index);
        }
        return $form;
    }

    public static function getWarpEditForm(string $warpName, ?Player $player = null): CustomForm {
        $lang = Loader::getLanguageManager();
        $warpManager = Loader::getWarpManager();
        $form = new CustomForm(function (Player $player, $data) use($lang, $warpManager, $warpName) {
            if ($data === null) {
                $editForm = self::getWarpEditListForm($player);

                if ($editForm === null) {
                    return;
                }
                $player->sendForm($editForm);
                return;
            }

            $warp = $warpManager->getWarp($warpName);
            if ($warp === null) {
                $player->sendMessage(C::colorize($lang->getNested("warps.error")));
                return;
            }
    
            $warp->setSetting('send_title', $data[0]);
            $warp->setSetting('add_particle', $data[1]);
            $warp->setSetting('add_sound', $data[2]);
            $warp->setSetting('permit_required', $data[3]);
            $warp->setSetting('command_registered', $data[4]);
    
            $player->sendMessage(C::colorize(str_replace(["{warp}"], [$warp->getName()], $lang->getNested("warps.edit-form.edit.success"))));
        });

        $form->setTitle(C::colorize($lang->getNested("warps.edit-form.edit.title")));

        $warp = $warpManager->getWarp($warpName);
        if ($warp === null) {
            return null; 
        }
    
        $currentSettings = $warp->getSettingsArray();
    
        $form->addToggle(C::colorize($lang->getNested("warps.create-form.title-toggle")), $currentSettings['send_title'] ?? false);
        $form->addToggle(C::colorize($lang->getNested("warps.create-form.particle-toggle")), $currentSettings['add_particle'] ?? false);
        $form->addToggle(C::colorize($lang->getNested("warps.create-form.sound-toggle")), $currentSettings['add_sound'] ?? false);
        $form->addToggle(C::colorize($lang->getNested("warps.create-form.permit-toggle")), $currentSettings['permit_required'] ?? false);
        $form->addToggle(C::colorize($lang->getNested("warps.create-form.command-register-toggle")), $currentSettings['command_registered'] ?? false);
        return $form;
    }

    public static function getWarpMenuForm(Player $player): SimpleForm {
        $lang = Loader::getLanguageManager();
        $warpList = Loader::getWarpManager()->getWarpList();

        $form = new SimpleForm(function(Player $player, $data) use($warpList){
            if ($data === null) { return; }

           $warpArray = array_values($warpList);
          
           if (isset($warpArray[$data]) && $warpArray[$data] instanceof Warp) {
               $warpArray[$data]->teleport($player);
           } elseif ($data === count($warpArray)) {
               if ($player->hasPermission("core.warp.admin")) {
                   $player->sendForm(self::getWarpManageForm($player));
               }
           }
        });

        $form->setTitle(C::colorize($lang->getNested("warps.menu-form.title")));

        foreach ($warpList as $warp) {
            if ($warp instanceof Warp) {
                $form->addButton(C::colorize(str_replace(["{warp}"], [$warp->getName()], $lang->getNested("warps.menu-form.button"))));
            }
        }

        if ($player->hasPermission("core.warp.admin")) {
            $form->addButton(C::colorize($lang->getNested("warps.menu-form.manage")));
        }
        return $form;
    }
}