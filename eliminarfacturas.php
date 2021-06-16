<?php
/**
* 2007-2021 PrestaShop
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
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Eliminarfacturas extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'eliminarfacturas';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Christian Herrero';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Eliminar facturas');
        $this->description = $this->l('Elimina todas las facturas generadas en Prestashop.');

        $this->confirmUninstall = $this->l('');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('ELIMINARFACTURAS', '');

        return parent::install();
    }

    public function uninstall()
    {
        Configuration::deleteByName('ELIMINARFACTURAS');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitEliminarfacturasModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);



        return $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {   
        // Detectamos si ya se han borrado las facturas para mostrar la alerta
        if (Configuration::get('ELIMINARFACTURAS') === '1' || Configuration::get('ELIMINARFACTURAS_RANGE') !== '') {
            $message = $this->showAlert('Las facturas han sido borradas');
            // Reiniciamos el valor del formulario a false
            Configuration::updateValue('ELIMINARFACTURAS', '');
            Configuration::updateValue('ELIMINARFACTURAS_RANGE', '');

        } else {
            $message = '';
        }


        // Inicializamos el formulario de config
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitEliminarfacturasModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );


        return $message.$helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Configuración'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Eliminar todas las facturas'),
                        'name' => 'ELIMINARFACTURAS',
                        'is_bool' => true,
                        'desc' => $this->l('CUIDADO! Estableciendo en "Si" se borrarán todas las facturas de la tienda'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Eliminar a partir del nº de factura'),
                        'name' => 'ELIMINARFACTURAS_RANGE',
                        'desc' => $this->l('Escribe el número de factura a partir del cual quieres borrar las facturas.'),
                        'value' => '',
                        'hint' => $this->l('Si por ejemplo pones el nº 100, se borrarán todas las facturas posteriores incluyendo la nº 100')
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Borrar facturas'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {   
        $array = array(
            'ELIMINARFACTURAS' => Configuration::get('ELIMINARFACTURAS', true),
            'ELIMINARFACTURAS_RANGE' => Configuration::get('ELIMINARFACTURAS_RANGE', true)
        );
        
        return $array;
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {

            // Recogemos el valor del input
            Configuration::updateValue($key, Tools::getValue($key));
            
            // Si está en true, borramos facturas
            if ($key === 'ELIMINARFACTURAS' && Tools::getValue($key) === '1') {
                $this->deleteAllInvoices();
            }

            // Si está en true, borramos facturas
            if ($key === 'ELIMINARFACTURAS_RANGE' && Tools::getValue($key) !== '') {
                $this->deleteInvoicesFromNumber(Tools::getValue($key));
            }
        }
    }

    /**
     * Delete all the invoices.
     */
    public function deleteAllInvoices() {
        $db = Db::getInstance();
        $query = $db->execute('
            DELETE FROM `' . _DB_PREFIX_ . 'order_invoice`;
            DELETE FROM `' . _DB_PREFIX_ . 'order_invoice_payment`;
            DELETE FROM `' . _DB_PREFIX_ . 'order_invoice_tax`;
            UPDATE `' . _DB_PREFIX_ . 'orders` SET `invoice_number` = 0;
        ');
    }

    /**
     * Display an alert when all invoices has been deleted.
     */
    public function showAlert($message) {
        echo '
            <script>
                alert("'.$message.'");
            </script>
        ';
    }
    
    /**
     * Display an alert when all invoices has been deleted.
     */
    public function deleteInvoicesFromNumber($number) {
        
        $db = Db::getInstance();
        $res = $db->execute('
            DELETE FROM ' . _DB_PREFIX_ . 'order_invoice WHERE id_order_invoice >= '.$number.' ;
            DELETE FROM ' . _DB_PREFIX_ . 'order_invoice_payment WHERE id_order_invoice >= '.$number.';
            DELETE FROM ' . _DB_PREFIX_ . 'order_invoice_tax WHERE id_order_invoice >= '.$number.';
            UPDATE `' . _DB_PREFIX_ . 'orders` SET `invoice_number` = 0 WHERE invoice_number >= '.$number.';
        ');
    }

}
