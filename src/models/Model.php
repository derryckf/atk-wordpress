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
 * Base model.
 */

namespace atkwp\models;

use atkwp\helpers\WpUtil;

class Model extends \Atk4\Data\Model
{
    public $wp_table;

    protected function init() : void
    {
        if (!empty($this->wp_table)) {
            $this->table = WpUtil::getDbPrefix().$this->wp_table;
        }

        parent::init();
    }
}
