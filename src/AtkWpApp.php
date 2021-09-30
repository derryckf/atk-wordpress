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
 * The agile toolkit application needed to add and output Wp component views.
 */

namespace atkwp;

use Atk4\Ui\App;
use Atk4\Ui\Exception;
use Atk4\Core\Factory;
use Atk4\Ui\UserAction\ExecutorFactory;
use atkwp\helpers\WpUtil;

class AtkWpApp extends App
{
    use \Atk4\Core\SessionTrait;

    /**
     * The plugin running this app.
     *
     * @var AtkWp
     */
    public $plugin;

    /**
     * The html produce by this app.
     *
     * @var AtkWpView
     */
    public $wpHtml;
    
    public $layout ='';

    /**
     * The maximum number of letter of atk element name.
     *
     * @var int
     */
    public $max_name_length = 60;

    /**
     * The default directory name of atk template.
     *
     * @var string
     */
    public $skin = 'semantic-ui';

    /**
     * atk view initialisation.
     */
    protected function init() :void
    {
        parent::init();
     
        $this->addMethod('getViewJS', static function ($m, $actions) {
                                               
                                                if (!$actions) {
                                                    return '';
                                                }

                                                $actions['indent'] = '';
                                                //$ready = new \Atk4\Ui\JsFunction(['$'], $actions);
                                                $ready = new \Atk4\Ui\JsFunction($actions);
                                                return "<script>jQuery(document).ready({$ready->jsRender()})</script>";
                                            });

    }

    /**
     * AtkWpApp constructor.
     *
     * @param AtkWp|null $plugin
     * @param UI|null    $ui_persistence
     */
    public function __construct(AtkWp $plugin = null, ?\Atk4\Ui\Persistence\Ui $ui_persistence = null)
    {
        $this->setApp($this);
        $this->plugin = $plugin;
        
         if (!isset($ui_persistence)) {    
            $this->ui_persistence = new \Atk4\Ui\Persistence\Ui();
        } else {
            $this->ui_persistence = $ui_persistence;
        }
        // setting up default executor factory.
        $this->executorFactory = Factory::factory([ExecutorFactory::class]);
    }


    /**
     * The layout initialisation for each Wp component.
     *
     * @param AtkWpView $view
     * @param $layout
     * @param $name
     *
     * @throws \atk4\core\Exception
     *
     * @return AtkWpView The Wp component being output.
     */
    public function initWpLayout(AtkWpView $view, $layout, $name)
    {
        if (!$this->wpHtml) {
            $this->wpHtml = new AtkWpView(['defaultTemplate' => $layout, 'name' => $name]);
            if (!$this->html)
            {
                $this->html = $this->wpHtml;
            }
            $this->wpHtml->setApp($this);
            $this->wpHtml->invokeInit();
            $this->wpHtml->add($view);
        }
        
        
        return $view;
        //return $this->wpHtml;
    }

    /**
     * Runs app and echo rendered template.
     *
     * @param bool $isAjax
     *
     * @throws \atk4\core\Exception
     */
    public function execute($isAjax = false)
    {
        echo $this->render($isAjax);
    }

    /**
     * Take care of rendering views.
     *
     * @param $isAjax
     *
     * @throws \atk4\core\Exception
     *
     * @return mixed
     */
    public function render($isAjax)
    {
        $this->hook('beforeRender');
        $this->is_rendering = true;
        $this->wpHtml->renderAll();
        //$this->wpHtml->render();
        $this->wpHtml->template->dangerouslyAppendHtml('HEAD', $this->getJsReady($this->wpHtml));
        //$this->wpHtml->template->dangerouslyAppendHtml('HEAD', $this->getJsReady($this->wpHtml));
        $this->is_rendering = false;
        $this->hook('beforeOutput');
       
        

        if (!$this->exit_called) { // output already send by terminate()
            if ($this->isJsUrlRequest()) {
               return $this->wpHtml->renderToJsonArr();
            } else {
               return $this->wpHtml->template->renderToHtml(); 
               //return $this->wpHtml->getHtml();
            }
        }
        //return $this->wpHtml->getHtml();
        //return $this->wpHtml->template->render();
        //return $this->wpHtml->render();
        return "\n" . '!! FATAL UI ERROR: wp Plugin !!' . "\n";
    }

    /**
     * Return the db connection run by this plugin.
     *
     * @return mixed
     */
    public function getDbConnection()
    {
        return $this->plugin->getDbConnection();
    }

    /**
     * Return url.
     *
     * @param array $page
     * @param bool  $needRequestUri
     * @param array $extraArgs
     *
     * @return array|null|string
     */
    public function url($page = [], $needRequestUri = false, $extraArgs = [])
    {
        if (is_string($page)) {
            return $page;
        }
        
        if ( ! empty ($page)) {
            return parent::url($page);
        }

        $wpPage = admin_url( 'admin-post' ); // using admin-post to catch postback

        if ($wpPageRequest = @$_REQUEST['page']) {
            $extraArgs['page'] = $wpPageRequest;
        }
        
        // setup action settings for post callback
        if(isset($extraArgs['__atk_callback']))
        {
            //action post parameter to enable wordpress to do the action
            $extraArgs['action'] = $this->plugin->getPluginName();
            //atkwp so we can find the component again
            $extraArgs['atkwp'] = $this->plugin->getWpComponentId();
        }

        return $this->buildUrl($wpPage, $page, $extraArgs);
    }

    /**
     * Return url.
     *
     * @param array $page
     * @param bool  $hasRequestUri
     * @param array $extraArgs
     *
     * @return array|null|string
     */
    public function jsUrl($page = [], $needRequestUri = false, $extraRequestUriArgs = [])
    {
        
        if ( isset($extraRequestUriArgs['__atk_reload']))
        //if (1)
        {
            // append to the end but allow override as per App.php
            $extraRequestUriArgs = array_merge($extraRequestUriArgs, ['__atk_json' => 1], $extraRequestUriArgs);
        }
        
        if (is_string($page)) {
            return $page;
        }

        $wpPage = admin_url( 'admin-ajax' );

        //if running front end set url for ajax. // sf not certain this works
        if (!WpUtil::isAdmin()) {
            $this->page = WpUtil::getBaseAdminUrl().'admin-ajax';
        }

        $extraRequestUriArgs['action'] = $this->plugin->getPluginName();
        $extraRequestUriArgs['atkwp'] = $this->plugin->getWpComponentId();

        /*
        if ($this->plugin->getComponentCount() > 0) {
            $extraRequestUriArgs['atkwp-count'] = $this->plugin->getComponentCount();
        }
         * 
         */
        
        if ($this->plugin->componentCount > 0) {
            $extraRequestUriArgs['atkwp-count'] = $this->plugin->componentCount;
        }

        if ($this->plugin->config->getConfig('plugin/use_nounce', false)) {
            $extraRequestUriArgs['_ajax_nonce'] = helpers\WpUtil::createWpNounce($this->plugin->getPluginName());
        }

        /* Page argument may be forced by using $config['plugin']['use_page_argument'] = true in config-default.php. */
        if (isset($extraRequestUriArgs['path']) || $this->plugin->config->getConfig('plugin/use_page_argument', false)) {
            $extraRequestUriArgs['page'] = $this->plugin->wpComponent['slug'];
        }

        return $this->buildUrl($wpPage, $page, $extraRequestUriArgs);
    }

    private function buildUrl($wpPage, $page, $extras)
    {
        $result = $extras;
        $sticky = $this->sticky_get_arguments;
        $this->page = $wpPage;

        if (!isset($page[0])) {
            $page[0] = $this->page;

            if (is_array($sticky) && !empty($sticky)) {
                foreach ($sticky as $key => $val) {
                    if ($val === true) {
                        if (isset($_GET[$key])) {
                            $val = $_GET[$key];
                        } else {
                            continue;
                        }
                    }
                    if (!isset($result[$key])) {
                        $result[$key] = $val;
                    }
                }
            }
        }

        foreach ($page as $arg => $val) {
            if ($arg === 0) {
                continue;
            }

            if ($val === null || $val === false) {
                unset($result[$arg]);
            } else {
                $result[$arg] = $val;
            }
        }

        $page = $page[0];

        $url = $page ? $page.'.php' : '';

        $args = http_build_query($result);

        if ($args) {
            $url = $url.'?'.$args;
        }

        return $url;
    }

    /**
     * Return javascript action.
     *
     * @param $app_view
     *
     * @throws Exception
     *
     * @return string
     */
    public function getJsReady($app_view)
    {
        $actions = [];

        foreach ($app_view->_js_actions as $eventActions) {
            foreach ($eventActions as $action) {
                $actions[] = $action;
            }
        }

        if (!$actions) {
            return '';
        }

        $actions['indent'] = '';
        $ready = new \Atk4\Ui\JsFunction(['$'], $actions);
        return "<script>jQuery(document).ready({$ready->jsRender()})</script>";
    }
    
     /**
     * Return javascript action.*
     *
     * @param $app_view
     *
     * @throws Exception
     *
     * @return string
     */
    public function invokeGetViewJS($actions)
    {
       
        if (!$actions) {
            return '';
        }

        $actions['indent'] = '';
        $ready = new \Atk4\Ui\JsFunction(['$'], $actions);
        return "<script>jQuery(document).ready({$ready->jsRender()})</script>";
    }

    /**
     * Load template file.
     *
     * @param string $name
     *
     * @throws Exception
     *
     * @return Template
     */
    public function loadTemplate($name)
    {
        $template = new \Atk4\Ui\HtmlTemplate();
        $template->setApp($this);
        
        return $template->loadFromFile($this->plugin->getTemplateLocation($name));
    }
}
