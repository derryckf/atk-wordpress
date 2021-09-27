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
 * Wordpress Widget.
 * This is not a typical atk view but rather a child of a \WP_Widget class.
 *
 * This component can be use as a regular Widget class in WP.
 *
 * The WidgetInterface is needed to use this Widget class as an atk view.
 * Implementing the interface will run three methods:
 *      - onWidget(AtkWp $view, $instance);
 *          Will pass an AtkWp view as param in order to allow for adding atk4\ui views element.
 *      - onForm(AtkWp $view, $instance);
 *          Will pas an AtkWp view as param in order to allow for adding atk4\ui input.
 *      - onUpdate($newInstance, $oldInstance);
 *          Will allow cleaning of user input value.
 */

namespace atkwp\components;

use Atk4\Ui\Exception;
use atkwp\AtkWp;

class WidgetComponent extends \WP_Widget
{
    /**
     * The plugin running this widget.
     *
     * @var AtkWp
     */
    public $plugin;

    /**
     * The current widget configuration.
     *
     * @var array
     */
    public $widgetConfig;

    public function __construct($idBase = null, $name = 'atkDefaultName', $widgetOtions = [], $controlOptions = [])
    {
        parent::__construct($idBase, $name, $widgetOtions, $controlOptions);
    }

    /**
     * Pre initialisation of our widget.
     * Call from the WidgetService when widget is register in WP.
     * Call directly after widget creation.
     *
     * @param $id
     * @param array $config
     * @param AtkWp $plugin
     */
    public function initializeWidget($id, array $config, AtkWp $plugin)
    {
        $this->plugin = $plugin;
        $this->name = $config['title'];

        //make sure our id_base is unique
        $this->id_base = $plugin->pluginName.'_'.$id;

        //Widget option_name in Option table that will hold the widget instance field value.
        $this->option_name = 'widget-'.$this->id_base;
        $this->widget_options = wp_parse_args($this->widget_options, $config['widget_ops']);

        $control = (isset($config['widget_control_ops'])) ? $config['widget_control_ops'] : [];
        $this->control_options = wp_parse_args($control, ['id_base' => $this->id_base]);

        // Our widget definition
        $this->widgetConfig = $config;

        //Add the id value to our widget definition.
        $this->widgetConfig['id'] = $id;
    }

    /**
     * The \Wp_Widget::widget() method.
     * If child class implement WidgetInterface, this method will call the onWidget method
     * passing a fully working atk view that will be echo when return by onWidget.
     *
     * @param array $args
     * @param array $instance
     *
     * @throws Exception
     */
    public function widget($args, $instance)
    {
        echo $args['before_widget'];

        $title = apply_filters('widget_title', $instance['title']);
        if (!empty($title)) {
            echo $args['before_title'].$title.$args['after_title'];
        }

        try {
            $view = $this->onWidget($this->plugin->newAtkAppView('widget.html', $this->widgetConfig['id']), $instance);
            if (!$view) {
                throw new Exception('Method onWidget() should return the atk view');
            }
            //$view->app->execute();
            $view->getApp()->execute();
        } catch (Exception $e) {
            $this->plugin->caughtException($e);
        }

        echo $args['after_widget'];
    }

    /**
     * The \Wp_Widget::form() method.
     * If child class implement WidgetInterface, this method will call the onForm method
     * passing a fully working atk view that will be echo when return by onForm.
     * Use the $view pass to onForm for adding your input field.
     *
     * @param array $instance
     *
     * @throws Exception
     *
     * @return string|void
     */
    public function form($instance)
    {
        try {
            $view = $this->onForm($this->plugin->newAtkAppView('widget.html', $this->widgetConfig['id']), $instance);
            if (!$view) {
                throw new Exception('Method onForm() should return the atk view');
            }
            //$view->app->execute();
            $view->getApp()->execute();
        } catch (Exception $e) {
            $this->plugin->caughtException($e);
        }
    }

    /**
     * The \Wp_Widget::update() method.
     * If child class implement WidgetInterface, this method will call the onUpdate method
     * Use the onUpdate to sanitize field entry value prior to save them to db.
     *
     * @param array $newInstance
     * @param array $oldInstance
     *
     * @return array
     */
    public function update($newInstance, $oldInstance)
    {
        return $this->onUpdate($newInstance, $oldInstance);
    }
}
