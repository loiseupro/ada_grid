<?php
/**
* 2007-2019 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2019 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) exit();

include_once _PS_MODULE_DIR_.'ada_grid/classes/Adagrid.php';

class Ada_grid extends Module{

    protected $config_form = false;
    public function __construct(){

        $this->name = 'ada_grid';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Lois';
        $this->need_instance = 1;
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Ada Grid');
        $this->description = $this->l('Product combination grid ');
        $this->confirmUninstall = $this->l('¿Estás seguro de esto? Todos los datos del módulo se eliminarán');
        $this->ps_versions_compliancy = array('min' => '1.7.8.6', 'max' => _PS_VERSION_);
    }


    public function install(){
        include(dirname(__FILE__).'/sql/install.php'); 

        /* Init configuration vars to default configuration */
        Configuration::updateValue("ADA_GRID_ENABLE", 1);      
        Configuration::updateValue("ADA_GRID_SHOW_EAN", 0);
        Configuration::updateValue("ADA_GRID_SHOW_ISBN", 0);
        Configuration::updateValue("ADA_GRID_SHOW_IMAGE", 0);
        Configuration::updateValue("ADA_GRID_SHOW_MIN_QTY", 0);
        Configuration::updateValue("ADA_GRID_SHOW_ONE_IMAGE", 0);
        Configuration::updateValue("ADA_GRID_SHOW_REFERENCE", 0);

        return parent::install() &&
        $this->registerHook('header') &&
        $this->registerHook('backOfficeHeader') &&
        $this->registerHook('displayBackOfficeHome') &&
        $this->registerHook('displayProductAdditionalInfo');           
    }

    /* Uninstall */
    public function uninstall(){
        include(dirname(__FILE__).'/sql/uninstall.php');
        return parent::uninstall();
    }


    /* Module backend */
    public function getContent(){
        /* Form process */
        if((bool)Tools::isSubmit('submit_ada_grid') == true) {
            $this->postProcess();
        }
        /* Load vars and view */
        $this->context->smarty->assign('module_dir', $this->_path);
        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/header.tpl');
        $output .= $this->renderForm();
        $output .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/footer.tpl');
        return $output;        
    }


    /* Build configuration form */
    protected function renderForm(){
        $helper = new HelperForm();
        $helper->show_toolbar = true;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit_ada_grid';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
        .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );
        return $helper->generateForm(array($this->getConfigForm()));
    }


    /* The configuration form */
    protected function getConfigForm(){
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Configuration params'),
                    'icon' => 'icon-cogs',
                ),           
                'input' => array(                  
                    array(
                        'type' => 'radio',
                        'name' => 'ADA_GRID_ENABLE',
                        'label' => $this->l('Grid enable?'),
                        'lang' => false,
                        'required' => true,
                        'values' => array(
                            array(
                                'id' => 0,
                                'value' => 0,
                                'label' => $this->l('No'),
                            ),
                            array(
                                'id' => 1,
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ),
                        ),
                        'class' => 'ada-grid-input ada-input-radio',
                    ),
                    array(
                        'type' => 'radio',
                        'name' => 'ADA_GRID_SHOW_REFERENCE',
                        'label' => $this->l('Show reference?'),
                        'lang' => false,
                        'required' => true,
                        'values' => array(
                            array(
                                'id' => 0,
                                'value' => 0,
                                'label' => $this->l('No'),
                            ),
                            array(
                                'id' => 1,
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ),
                        ),
                        'class' => 'ada-grid-input ada-input-radio',
                    ),
                    array(
                        'type' => 'radio',
                        'name' => 'ADA_GRID_SHOW_ISBN',
                        'label' => $this->l('Show Isbn?'),
                        'lang' => false,
                        'required' => true,
                        'values' => array(
                            array(
                                'id' => 0,
                                'value' => 0,
                                'label' => $this->l('No'),
                            ),
                            array(
                                'id' => 1,
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ),
                        ),
                        'class' => 'ada-grid-input ada-input-radio',
                    ),
                    array(
                        'type' => 'radio',
                        'name' => 'ADA_GRID_SHOW_EAN',
                        'label' => $this->l('Show EAN?'),
                        'lang' => false,
                        'required' => true,
                        'values' => array(
                            array(
                                'id' => 0,
                                'value' => 0,
                                'label' => $this->l('No'),
                            ),
                            array(
                                'id' => 1,
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ),
                        ),
                        'class' => 'ada-grid-input ada-input-radio',
                    ),  
                    array(
                        'type' => 'radio',
                        'name' => 'ADA_GRID_SHOW_IMAGE',
                        'label' => $this->l('Show image?'),
                        'lang' => false,
                        'required' => true,
                        'values' => array(
                            array(
                                'id' => 0,
                                'value' => 0,
                                'label' => $this->l('No'),
                            ),
                            array(
                                'id' => 1,
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ),
                        ),
                        'class' => 'ada-grid-input ada-input-radio',
                    ),
                    array(
                        'type' => 'radio',
                        'name' => 'ADA_GRID_SHOW_MIN_QTY',
                        'label' => $this->l('Show minimal quantity?'),
                        'lang' => false,
                        'required' => true,
                        'values' => array(
                            array(
                                'id' => 0,
                                'value' => 0,
                                'label' => $this->l('No'),
                            ),
                            array(
                                'id' => 1,
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ),
                        ),
                        'class' => 'ada-grid-input ada-input-radio',
                    ),  
                    array(
                        'type' => 'radio',
                        'name' => 'ADA_GRID_SHOW_ONE_IMAGE',
                        'label' => $this->l('Show only one image?'),
                        'lang' => false,
                        'required' => true,
                        'values' => array(
                            array(
                                'id' => 0,
                                'value' => 0,
                                'label' => $this->l('No'),
                            ),
                            array(
                                'id' => 1,
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ),
                        ),
                        'class' => 'ada-grid-input ada-input-radio',
                    ), 
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }


    /* Load values in configuration form */
    protected function getConfigFormValues(){
        return array(   
            'ADA_GRID_ENABLE' => Configuration::get('ADA_GRID_ENABLE', true), 
            'ADA_GRID_SHOW_REFERENCE' => Configuration::get('ADA_GRID_SHOW_REFERENCE', true), 
            'ADA_GRID_SHOW_ISBN' => Configuration::get('ADA_GRID_SHOW_ISBN', true), 
            'ADA_GRID_SHOW_EAN' => Configuration::get('ADA_GRID_SHOW_EAN', true), 
            'ADA_GRID_SHOW_IMAGE' => Configuration::get('ADA_GRID_SHOW_IMAGE', true), 
            'ADA_GRID_SHOW_MIN_QTY' => Configuration::get('ADA_GRID_SHOW_MIN_QTY', true), 
            'ADA_GRID_SHOW_ONE_IMAGE' => Configuration::get('ADA_GRID_SHOW_ONE_IMAGE', true), 
        );
    }


    /* Process configuration form */
    protected function postProcess(){
        $form_values = $this->getConfigFormValues();
        foreach(array_keys($form_values) as $key) Configuration::updateValue($key, Tools::getValue($key));
    }


    /* Add the CSS & JavaScript files you want to be loaded in the BO. */
    public function hookBackOfficeHeader(){
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }        
    }


    /* Add the CSS & JavaScript files you want to be added on the FO. */
    public function hookHeader(){
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    /* Hook to print the grid */
    public function hookDisplayProductAdditionalInfo($params){
        $adagrid_obj = new Adagrid();
        $grid = $adagrid_obj->buildGrid($params["product"]->id);
        $this->context->smarty->assign('grid', $grid);

        return $this->display(__FILE__, 'views/templates/front/grid.tpl');
    } 


}