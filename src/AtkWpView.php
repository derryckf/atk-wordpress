<?php
/* =====================================================================
 * atk-wordpress => Wordpress interface for Agile Toolkit Framework.
 *
 * This interface enable the use of the Agile Toolkit framework within a WordPress site.
 *
 * Please note that when atk is mentioned it generally refer to Agile Toolkit.
 * More information on Agile Toolkit: http://www.agiletoolkit.org
 *
 * Author: Alain Belair
 * https://github.com/ibelar
 * Licensed under MIT
 * =====================================================================*/
/**
 * The atk basic view for all Wp components.
 * All Wp component views derive from this class.
 */

namespace atkwp;

class AtkWpView extends \Atk4\Ui\View
{
    /** @var AtkWpApp */
    //public $app; // not required using scope trait instead
    
    
    /**
     * Return the plugin running this view.
     *
     * @return AtkWp
     */
    public function getPluginInstance()
    {
        //return $this->app->plugin;
        return $this->getApp()->plugin;
    }

    /**
     * Return the db connection set for this plugin.
     *
     * @return mixed
     */
    public function getDbConnection()
    {
        //return $this->app->plugin->getDbConnection();
        return $this->getApp()->plugin->getDbConnection();
    }
}
