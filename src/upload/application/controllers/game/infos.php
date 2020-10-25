<?php
/**
 * Infos Controller
 *
 * @category Controller
 * @package  Application
 * @author   XG Proyect Team
 * @license  http://www.xgproyect.org XG Proyect
 * @link     http://www.xgproyect.org
 * @version  3.0.0
 */
namespace application\controllers\game;

use application\core\Controller;
use application\libraries\DevelopmentsLib;
use application\libraries\FleetsLib;
use application\libraries\FormatLib;
use application\libraries\FunctionsLib;
use application\libraries\OfficiersLib;
use application\libraries\ProductionLib;

/**
 * Infos Class
 */
class Infos extends Controller
{

    const MODULE_ID = 24;

    private $_current_user;
    private $_current_planet;
    private $_element_id;
    private $_resource;
    private $_pricelist;
    private $_combat_caps;
    private $_prod_grid;

    /**
     * __construct()
     */
    public function __construct()
    {
        parent::__construct();

        // check if session is active
        parent::$users->checkSession();

        // load Model
        parent::loadModel('game/infos');

        // load Language
        parent::loadLang(['game/global', 'game/infos', 'game/constructions', 'game/defenses', 'game/ships', 'game/technologies']);

        // Check module access
        FunctionsLib::moduleMessage(FunctionsLib::isModuleAccesible(self::MODULE_ID));

        $this->_resource = parent::$objects->getObjects();
        $this->_pricelist = parent::$objects->getPrice();
        $this->_combat_caps = parent::$objects->getCombatSpecs();
        $this->_prod_grid = parent::$objects->getProduction();
        $this->_current_user = parent::$users->getUserData();
        $this->_current_planet = parent::$users->getPlanetData();
        $this->_element_id = isset($_GET['gid']) ? (int) $_GET['gid'] : null;

        $this->build_page();
    }

    /**
     * method build_page
     * param
     * return main method, loads everything
     */
    private function build_page()
    {
        if (!array_key_exists($this->_element_id, $this->_resource)) {
            FunctionsLib::redirect('game.php?page=techtree');
        }

        $GateTPL = '';
        $DestroyTPL = '';
        $TableHeadTPL = '';
        $TableFooterTPL = '';

        $parse = $this->langs->language;
        $parse['dpath'] = DPATH;
        $parse['name'] = $this->langs->language[$this->_resource[$this->_element_id]];
        $parse['image'] = $this->_element_id;
        $parse['description'] = $this->langs->language['info'][$this->_resource[$this->_element_id]];

        if ($this->_element_id < 13 or ($this->_element_id == 43 && $this->_current_planet[$this->_resource[43]] > 0)) {
            $PageTPL = 'infos/info_buildings_table';
        } elseif ($this->_element_id < 200) {
            $PageTPL = 'infos/info_buildings_general';
        } elseif ($this->_element_id < 400) {
            $PageTPL = 'infos/info_buildings_fleet';
        } elseif ($this->_element_id < 600) {
            $PageTPL = 'infos/info_buildings_defense';
        } else {
            $PageTPL = 'infos/info_officiers_general';
        }

        //S�lo hay destroy en <200
        if ($this->_element_id < 200 && $this->_element_id != 33 && $this->_element_id != 41) {
            $DestroyTPL = 'infos/info_buildings_destroy';
        }

        if ($this->_element_id >= 1 && $this->_element_id <= 3) {
            $PageTPL = 'infos/info_buildings_table';
            $TableHeadTPL = 'infos/info_production_header';
            $TableTPL = 'infos/info_production_body';
        } elseif ($this->_element_id == 4) {
            $PageTPL = 'infos/info_buildings_table';
            $TableHeadTPL = 'infos/info_production_simple_header';
            $TableTPL = 'infos/info_production_simple_body';
        } elseif ($this->_element_id >= 22 && $this->_element_id <= 24) {
            $PageTPL = 'infos/info_buildings_table';
            $DestroyTPL = 'infos/info_buildings_destroy';
            $TableHeadTPL = 'infos/info_storage_header';
            $TableTPL = 'infos/info_storage_table';
        } elseif ($this->_element_id == 12) {
            $TableHeadTPL = 'infos/info_energy_header';
            $TableTPL = 'infos/info_energy_body';
        } elseif ($this->_element_id == 42) {
            $PageTPL = 'infos/info_buildings_table';
            $TableHeadTPL = 'infos/info_range_header';
            $TableTPL = 'infos/info_range_body';
        } elseif ($this->_element_id == 43) {
            $GateTPL = 'infos/info_gate_table';

            if ($_POST) {
                FunctionsLib::message($this->doFleetJump(), "game.php?page=infos&gid=43", 2);
            }
        } elseif ($this->_element_id == 124) {
            $PageTPL = 'infos/info_buildings_table';
            $DestroyTPL = 'infos/info_buildings_destroy';
            $TableHeadTPL = 'infos/info_astrophysics_header';
            $TableTPL = 'infos/info_astrophysics_table';
            $TableFooterTPL = 'infos/info_astrophysics_footer';
        } elseif ($this->_element_id >= 202 && $this->_element_id <= 250) {
            $PageTPL = 'infos/info_buildings_fleet';
            $parse['element_typ'] = $this->langs->language['ships'];
            $parse['rf_info_to'] = $this->ShowRapidFireTo();
            $parse['rf_info_fr'] = $this->ShowRapidFireFrom();
            $parse['hull_pt'] = FormatLib::prettyNumber($this->_pricelist[$this->_element_id]['metal'] + $this->_pricelist[$this->_element_id]['crystal']);
            $parse['shield_pt'] = FormatLib::prettyNumber($this->_combat_caps[$this->_element_id]['shield']);
            $parse['attack_pt'] = FormatLib::prettyNumber($this->_combat_caps[$this->_element_id]['attack']);
            $parse['capacity_pt'] = FormatLib::prettyNumber($this->_pricelist[$this->_element_id]['capacity']);
            $parse['base_speed'] = FormatLib::prettyNumber($this->_pricelist[$this->_element_id]['speed']);
            $parse['base_conso'] = FormatLib::prettyNumber($this->_pricelist[$this->_element_id]['consumption']);

            $parse['upd_speed'] = '';
            $parse['upd_conso'] = '';

            if ($this->_element_id == 202) {
                $parse['upd_speed'] = "<font color=\"yellow\">(" . FormatLib::prettyNumber($this->_pricelist[$this->_element_id]['speed2']) . ")</font>";
                $parse['upd_conso'] = "<font color=\"yellow\">(" . FormatLib::prettyNumber($this->_pricelist[$this->_element_id]['consumption2']) . ")</font>";
            } elseif ($this->_element_id == 211) {
                $parse['upd_speed'] = "<font color=\"yellow\">(" . FormatLib::prettyNumber($this->_pricelist[$this->_element_id]['speed2']) . ")</font>";
            }
        } elseif ($this->_element_id >= 401 && $this->_element_id <= 550) {
            $PageTPL = 'infos/info_buildings_defense';
            $parse['element_typ'] = $this->langs->language['defenses'];
            $parse['rf_info_to'] = '';
            $parse['rf_info_fr'] = '';

            if ($this->_element_id < 500) {
                $parse['rf_info_to'] = $this->ShowRapidFireTo();
                $parse['rf_info_fr'] = $this->ShowRapidFireFrom();
            }

            $parse['hull_pt'] = FormatLib::prettyNumber($this->_pricelist[$this->_element_id]['metal'] + $this->_pricelist[$this->_element_id]['crystal']);
            $parse['shield_pt'] = FormatLib::prettyNumber($this->_combat_caps[$this->_element_id]['shield']);
            $parse['attack_pt'] = FormatLib::prettyNumber($this->_combat_caps[$this->_element_id]['attack']);
        }

        if ($TableHeadTPL != '') {
            $parse['table_head'] = $this->getTemplate()->set($TableHeadTPL, $this->langs->language);

            if ($this->_element_id >= 22 && $this->_element_id <= 24) {
                $parse['table_data'] = $this->storage_table($TableTPL);
            } elseif ($this->_element_id == 124) {
                $parse['table_data'] = $this->astrophysics_table($TableTPL);
            } elseif ($this->_element_id == 42) {
                $parse['table_data'] = $this->phalanxRange($TableTPL);
            } else {
                $parse['table_data'] = $this->ShowProductionTable($TableTPL);
            }
        }

        $parse['table_footer'] = '';
        if ($TableFooterTPL != '') {
            $parse['table_footer'] = $this->getTemplate()->set($TableFooterTPL, $this->langs->language);
        }

        $page = $this->getTemplate()->set($PageTPL, $parse);

        if ($GateTPL != '') {
            if ($this->_current_planet[$this->_resource[$this->_element_id]] > 0) {
                $RestString = $this->GetNextJumpWaitTime($this->_current_planet);
                $parse['gate_start_link'] = $this->planet_link($this->_current_planet);
                if ($RestString['value'] != 0) {
                    $parse['gate_time_script'] = FunctionsLib::chronoApplet("Gate", "1", $RestString['value'], true);
                    $parse['gate_wait_time'] = "<div id=\"bxx" . "Gate" . "1" . "\"></div>";
                    $parse['gate_script_go'] = FunctionsLib::chronoApplet("Gate", "1", $RestString['value'], false);
                } else {
                    $parse['gate_time_script'] = "";
                    $parse['gate_wait_time'] = "";
                    $parse['gate_script_go'] = "";
                }
                $parse['gate_dest_moons'] = $this->BuildJumpableMoonCombo($this->_current_user, $this->_current_planet);
                $parse['gate_fleet_rows'] = $this->BuildFleetListRows($this->_current_planet);
                $page .= $this->getTemplate()->set($GateTPL, $parse);
            }
        }

        if ($DestroyTPL != '') {
            if (isset($this->_current_planet[$this->_resource[$this->_element_id]]) && $this->_current_planet[$this->_resource[$this->_element_id]] > 0) {
                $NeededRessources = DevelopmentsLib::developmentPrice($this->_current_user, $this->_current_planet, $this->_element_id, true, true);
                $DestroyTime = DevelopmentsLib::developmentTime($this->_current_user, $this->_current_planet, $this->_element_id) / 2;
                $DestroyTime = $DestroyTime < 1 ? 1 : $DestroyTime;
                $parse['destroyurl'] = "game.php?page=" . DevelopmentsLib::setBuildingPage($this->_element_id) . "&cmd=destroy&building=" . $this->_element_id;
                $parse['levelvalue'] = $this->_current_planet[$this->_resource[$this->_element_id]];
                $parse['metal'] = $this->langs->line('metal');
                $parse['crysta'] = $this->langs->line('crystal');
                $parse['deuterium'] = $this->langs->line('deuterium');
                $parse['nfo_metal'] = FormatLib::prettyNumber($NeededRessources['metal']);
                $parse['nfo_crystal'] = FormatLib::prettyNumber($NeededRessources['crystal']);
                $parse['nfo_deuterium'] = FormatLib::prettyNumber($NeededRessources['deuterium']);
                $parse['destroytime'] = FormatLib::prettyTime($DestroyTime);

                $page .= $this->getTemplate()->set($DestroyTPL, $parse);
            }
        }
        parent::$page->display($page);
    }

    /**
     * method storage_table
     * param
     * return builds the storage table
     */
    private function storage_table($template)
    {
        $current_built_lvl = $this->_current_planet[$this->_resource[$this->_element_id]];
        $BuildStartLvl = max(1, $current_built_lvl - 2);
        $Table = "";
        $ProdFirst = 0;
        $ActualProd = ProductionLib::maxStorable($current_built_lvl);

        for ($BuildLevel = $BuildStartLvl; $BuildLevel < $BuildStartLvl + 15; ++$BuildLevel) {
            $Prod = ProductionLib::maxStorable($BuildLevel);

            $bloc['build_lvl'] = ($current_built_lvl == $BuildLevel) ? "<font color=\"#ff0000\">" . $BuildLevel . "</font>" : $BuildLevel;
            $bloc['build_prod'] = FormatLib::prettyNumber($Prod);
            $bloc['build_prod_diff'] = FormatLib::colorNumber(FormatLib::prettyNumber(($Prod - $ActualProd)));

            if ($ProdFirst == 0) {
                $ProdFirst = floor($Prod);
            }

            $Table .= $this->getTemplate()->set($template, $bloc);
        }

        return $Table;
    }

    /**
     * method astrophysics_table
     * param
     * return builds the astrophysics table
     */
    private function astrophysics_table($template)
    {
        $current_built_lvl = $this->_current_user[$this->_resource[$this->_element_id]];
        $BuildStartLvl = max(1, $current_built_lvl - 2);
        $Table = "";

        for ($BuildLevel = $BuildStartLvl; $BuildLevel < $BuildStartLvl + 15; ++$BuildLevel) {
            $bloc['tech_lvl'] = ($current_built_lvl == $BuildLevel) ? "<font color=\"#ff0000\">" . $BuildLevel . "</font>" : $BuildLevel;
            $bloc['tech_colonies'] = FormatLib::prettyNumber(FleetsLib::getMaxColonies($BuildLevel));
            $bloc['tech_expeditions'] = FormatLib::prettyNumber(FleetsLib::getMaxExpeditions($BuildLevel));

            $Table .= $this->getTemplate()->set($template, $bloc);
        }

        return $Table;
    }

    private function GetNextJumpWaitTime($CurMoon)
    {
        $JumpGateLevel = $CurMoon[$this->_resource[43]];
        $LastJumpTime = $CurMoon['planet_last_jump_time'];
        if ($JumpGateLevel > 0) {
            $WaitBetweenJmp = (60 * 60) * (1 / $JumpGateLevel);
            $NextJumpTime = $LastJumpTime + $WaitBetweenJmp;
            if ($NextJumpTime >= time()) {
                $RestWait = $NextJumpTime - time();
                $RestString = " " . FormatLib::prettyTime($RestWait);
            } else {
                $RestWait = 0;
                $RestString = "";
            }
        } else {
            $RestWait = 0;
            $RestString = "";
        }
        $RetValue['string'] = $RestString;
        $RetValue['value'] = $RestWait;

        return $RetValue;
    }

    /**
     * doFleetJump
     *
     * @return string
     */
    private function doFleetJump()
    {
        if ($_POST) {

            $RestString = $this->GetNextJumpWaitTime($this->_current_planet);
            $NextJumpTime = $RestString['value'];
            $JumpTime = time();

            if ($NextJumpTime == 0) {

                $TargetPlanet = isset($_POST['jmpto']) ? $_POST['jmpto'] : '';

                if (!is_int($TargetPlanet)) {
                    $RetMessage = $this->langs->line('in_jump_gate_error_data');
                }

                $TargetGate = $this->Infos_Model->getTargetGate($TargetPlanet);

                if ($TargetGate['building_jump_gate'] > 0) {
                    $RestString = $this->GetNextJumpWaitTime($TargetGate);
                    $NextDestTime = $RestString['value'];

                    if ($NextDestTime == 0) {

                        $ShipArray = array();
                        $SubQueryOri = '';
                        $SubQueryDes = '';

                        for ($Ship = 200; $Ship < 300; $Ship++) {
                            $ShipLabel = "c" . $Ship;
                            $gemi_kontrol = isset($_POST[$ShipLabel]) ? $_POST[$ShipLabel] : null;

                            if (is_numeric($gemi_kontrol)) {
                                if ($gemi_kontrol > $this->_current_planet[$this->_resource[$Ship]]) {
                                    $ShipArray[$Ship] = $this->_current_planet[$this->_resource[$Ship]];
                                } else {
                                    $ShipArray[$Ship] = $gemi_kontrol;
                                }

                                if ($ShipArray[$Ship] > 0) {
                                    $SubQueryOri .= "`" . $this->_resource[$Ship] . "` = `" . $this->_resource[$Ship] . "` - '" . $ShipArray[$Ship] . "', ";
                                    $SubQueryDes .= "`" . $this->_resource[$Ship] . "` = `" . $this->_resource[$Ship] . "` + '" . $ShipArray[$Ship] . "', ";
                                }
                            }
                        }
                        if ($SubQueryOri != "") {
                            $this->Infos_Model->doJump(
                                $SubQueryOri,
                                $SubQueryDes,
                                $JumpTime,
                                $this->_current_planet['planet_id'],
                                $TargetGate['planet_id'],
                                $this->_current_user['user_id']
                            );

                            $this->_current_planet['planet_last_jump_time'] = $JumpTime;

                            $RestString = $this->GetNextJumpWaitTime($this->_current_planet);
                            $RetMessage = $this->langs->line('in_jump_gate_done') . $RestString['string'];
                        } else {
                            $RetMessage = $this->langs->line('in_jump_gate_error_data');
                        }
                    } else {
                        $RetMessage = $this->langs->line('in_jump_gate_not_ready_target') . $RestString['string'];
                    }
                } else {
                    $RetMessage = $this->langs->line('in_jump_gate_doesnt_have_one');
                }
            } else {
                $RetMessage = $this->langs->line('in_jump_gate_already_used') . $RestString['string'];
            }
        } else {
            $RetMessage = $this->langs->line('in_jump_gate_error_data');
        }

        return $RetMessage;
    }

    private function BuildFleetListRows()
    {
        $RowsTPL = 'infos/info_gate_rows';
        $CurrIdx = 1;
        $Result = "";
        for ($Ship = 200; $Ship < 250; $Ship++) {
            if (isset($this->_resource[$Ship]) && $this->_resource[$Ship] != '') {
                if ($this->_current_planet[$this->_resource[$Ship]] > 0) {
                    $bloc['idx'] = $CurrIdx;
                    $bloc['fleet_id'] = $Ship;
                    $bloc['fleet_name'] = $this->langs->language[$this->_resource[$Ship]];
                    $bloc['fleet_max'] = FormatLib::prettyNumber($this->_current_planet[$this->_resource[$Ship]]);
                    $bloc['gate_ship_dispo'] = $this->langs->line('in_jump_gate_available');
                    $Result .= $this->getTemplate()->set($RowsTPL, $bloc);
                    $CurrIdx++;
                }
            }
        }
        return $Result;
    }

    private function BuildJumpableMoonCombo()
    {
        $MoonList = $this->Infos_Model->getListOfMoons($this->_current_user['user_id']);

        $Combo = "";

        foreach ($MoonList as $CurMoon) {
            if ($CurMoon['planet_id'] != $this->_current_planet['planet_id']) {
                $RestString = $this->GetNextJumpWaitTime($CurMoon);
                if ($CurMoon[$this->_resource[43]] >= 1) {
                    $Combo .= "<option value=\"" . $CurMoon['planet_id'] . "\">[" . $CurMoon['planet_galaxy'] . ":" . $CurMoon['planet_system'] . ":" . $CurMoon['planet_planet'] . "] " . $CurMoon['planet_name'] . $RestString['string'] . "</option>\n";
                }

            }
        }
        return $Combo;
    }

    private function phalanxRange($Template)
    {
        $current_built_lvl = $this->_current_planet[$this->_resource[$this->_element_id]];
        $BuildLevel = ($current_built_lvl > 0) ? $current_built_lvl : 1;
        $BuildStartLvl = $current_built_lvl - 2;

        if ($BuildStartLvl < 1) {
            $BuildStartLvl = 1;
        }

        $Table = '';

        for ($BuildLevel = $BuildStartLvl; $BuildLevel < $BuildStartLvl + 15; $BuildLevel++) {
            $bloc['build_lvl'] = ($current_built_lvl == $BuildLevel) ? "<font color=\"#ff0000\">" . $BuildLevel . "</font>" : $BuildLevel;
            $bloc['build_range'] = ($BuildLevel * $BuildLevel) - 1;

            $Table .= $this->getTemplate()->set($Template, $bloc);
        }

        return $Table;
    }

    private function ShowProductionTable($Template)
    {
        $BuildLevelFactor = $this->_current_planet['planet_' . $this->_resource[$this->_element_id] . '_percent'];
        $BuildTemp = $this->_current_planet['planet_temp_max'];
        $current_built_lvl = $this->_current_planet[$this->_resource[$this->_element_id]];
        $BuildLevel = ($current_built_lvl > 0) ? $current_built_lvl : 1;
        $BuildEnergy = $this->_current_user['research_energy_technology'];
        $game_resource_multiplier = FunctionsLib::readConfig('resource_multiplier');

        // BOOST
        $geologe_boost = 1 + (1 * (OfficiersLib::isOfficierActive($this->_current_user['premium_officier_geologist']) ? GEOLOGUE : 0));
        $engineer_boost = 1 + (1 * (OfficiersLib::isOfficierActive($this->_current_user['premium_officier_engineer']) ? ENGINEER_ENERGY : 0));

        // PRODUCTION FORMULAS
        $metal_prod = eval($this->_prod_grid[$this->_element_id]['formule']['metal']);
        $crystal_prod = eval($this->_prod_grid[$this->_element_id]['formule']['crystal']);
        $deuterium_prod = eval($this->_prod_grid[$this->_element_id]['formule']['deuterium']);
        $energy_prod = eval($this->_prod_grid[$this->_element_id]['formule']['energy']);

        // PRODUCTION
        $Prod[1] = ProductionLib::productionAmount($metal_prod, $geologe_boost, $game_resource_multiplier);
        $Prod[2] = ProductionLib::productionAmount($crystal_prod, $geologe_boost, $game_resource_multiplier);
        $Prod[3] = ProductionLib::productionAmount($deuterium_prod, $geologe_boost, $game_resource_multiplier);

        if ($this->_element_id >= 4) {
            $Prod[4] = ProductionLib::productionAmount($energy_prod, $engineer_boost, 0, true);
            $ActualProd = floor($Prod[4]);
        } else {
            $Prod[4] = ProductionLib::productionAmount($energy_prod, 1, 0, true);
            $ActualProd = floor($Prod[$this->_element_id]);
        }

        if ($this->_element_id != 12) {
            $ActualNeed = floor($Prod[4]);
        } else {
            $ActualNeed = floor($Prod[3]);
        }

        $BuildStartLvl = $current_built_lvl - 2;
        if ($BuildStartLvl < 1) {
            $BuildStartLvl = 1;
        }

        $Table = '';
        $ProdFirst = 0;

        for ($BuildLevel = $BuildStartLvl; $BuildLevel < $BuildStartLvl + 15; $BuildLevel++) {
            // PRODUCTION FORMULAS
            $metal_prod = eval($this->_prod_grid[$this->_element_id]['formule']['metal']);
            $crystal_prod = eval($this->_prod_grid[$this->_element_id]['formule']['crystal']);
            $deuterium_prod = eval($this->_prod_grid[$this->_element_id]['formule']['deuterium']);
            $energy_prod = eval($this->_prod_grid[$this->_element_id]['formule']['energy']);

            // PRODUCTION
            $Prod[1] = ProductionLib::productionAmount($metal_prod, $geologe_boost, $game_resource_multiplier);
            $Prod[2] = ProductionLib::productionAmount($crystal_prod, $geologe_boost, $game_resource_multiplier);
            $Prod[3] = ProductionLib::productionAmount($deuterium_prod, $geologe_boost, $game_resource_multiplier);

            if ($this->_element_id >= 4) {
                $Prod[4] = ProductionLib::productionAmount($energy_prod, $engineer_boost, 0, true);
            } else {
                $Prod[4] = ProductionLib::productionAmount($energy_prod, 1, 0, true);
            }

            $bloc['build_lvl'] = ($current_built_lvl == $BuildLevel) ? FormatLib::colorRed($BuildLevel) : $BuildLevel;

            if ($ProdFirst > 0) {
                if ($this->_element_id != 12) {
                    $level_diff = FormatLib::prettyNumber(floor($Prod[$this->_element_id] - $ProdFirst));
                } else {
                    $level_diff = FormatLib::prettyNumber(floor($Prod[4] - $ProdFirst));
                }
            } else {
                $level_diff = 0;

                if ($current_built_lvl == 0) {
                    $level_diff = $Prod[3];

                    if ($this->_element_id >= 4) {
                        $level_diff = $Prod[4];
                    }
                }
            }

            if ($this->_element_id != 12) {
                $prod_diff = floor($Prod[$this->_element_id] - $ActualProd);

                if ($current_built_lvl == 0) {
                    $prod_diff = $Prod[3];

                    if ($this->_element_id >= 4) {
                        $prod_diff = $Prod[4];
                    }
                }

                $bloc['build_prod'] = FormatLib::prettyNumber(floor($Prod[$this->_element_id]));
                $bloc['build_prod_diff'] = FormatLib::colorNumber(FormatLib::prettyNumber($prod_diff));
                $bloc['build_level_diff'] = FormatLib::colorGreen($level_diff);
                $bloc['build_need'] = FormatLib::colorNumber(FormatLib::prettyNumber(floor($Prod[4])));
                $bloc['build_need_diff'] = FormatLib::colorNumber(FormatLib::prettyNumber(floor($Prod[4] - $ActualNeed)));
            } else {
                $prod_diff = floor($Prod[4] - $ActualProd);
                $need_diff = floor($Prod[3] - $ActualNeed);

                if ($current_built_lvl == 0) {
                    $prod_diff = $Prod[4];
                    $need_diff = $Prod[3];
                }

                $bloc['build_prod'] = FormatLib::prettyNumber(floor($Prod[4]));
                $bloc['build_prod_diff'] = FormatLib::colorNumber(FormatLib::prettyNumber($prod_diff));
                $bloc['build_level_diff'] = FormatLib::colorGreen($level_diff);
                $bloc['build_need'] = FormatLib::colorNumber(FormatLib::prettyNumber(floor($Prod[3])));
                $bloc['build_need_diff'] = FormatLib::colorNumber(FormatLib::prettyNumber($need_diff));
            }

            if ($this->_element_id != 12) {
                $ProdFirst = floor($Prod[$this->_element_id]);
            } else {
                $ProdFirst = floor($Prod[4]);
            }

            $Table .= $this->getTemplate()->set($Template, $bloc);
        }

        return $Table;
    }

    private function ShowRapidFireTo()
    {
        $ResultString = "";
        for ($Type = 200; $Type < 500; $Type++) {
            if (isset($this->_combat_caps[$this->_element_id]['sd'][$Type]) && $this->_combat_caps[$this->_element_id]['sd'][$Type] > 1) {
                $ResultString .= $this->langs->line('in_rf_again') . " " . $this->langs->language[$this->_resource[$Type]] . " <font color=\"#00ff00\">" . $this->_combat_caps[$this->_element_id]['sd'][$Type] . "</font><br>";
            }

        }
        return $ResultString;
    }

    private function ShowRapidFireFrom()
    {
        $ResultString = "";
        for ($Type = 200; $Type < 500; $Type++) {
            if (isset($this->_combat_caps[$Type]['sd'][$this->_element_id]) && $this->_combat_caps[$Type]['sd'][$this->_element_id] > 1) {
                $ResultString .= $this->langs->line('in_rf_from') . " " . $this->langs->language[$this->_resource[$Type]] . " <font color=\"#ff0000\">" . $this->_combat_caps[$Type]['sd'][$this->_element_id] . "</font><br>";
            }

        }
        return $ResultString;
    }

    private function planet_link($current_planet)
    {
        return "<a href=\"game.php?page=galaxy&mode=3&galaxy=" . $current_planet['planet_galaxy'] . "&system=" . $current_planet['planet_system'] . "\">[" . $current_planet['planet_galaxy'] . ":" . $current_planet['planet_system'] . ":" . $current_planet['planet_planet'] . "]</a>";
    }
}

/* end of infos.php */
