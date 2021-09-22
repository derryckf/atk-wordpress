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
 * This service is responsible to load js and css file within WP.
 */

namespace atkwp\services;

use atkwp\interfaces\ComponentCtrlInterface;

class EnqueueService
{
    /**
     * The component controller responsible of initiating this service.
     *
     * @var ComponentCtrlInterface
     */
    private $ctrl;

    protected $atkUiVersion = '3.0.0';
    protected $fomanticUiVersion = '2.8.7';
    protected $flatpickrVersion = '4.6.6';
    /**
     * The js files to load.
     *
     * @var array
     */
    protected $jsFiles = [];

    /**
     * The css files to load.
     *
     * @var array
     */
    protected $cssFiles = [];

    /**
     * The js files already registered within Wp.
     *
     * @var array
     */
    protected $jsRegistered = [];

    /**
     * The url to the vendor directory.
     * ./pluginName/vendor.
     *
     * @var string
     */
    protected $vendorUrl;

    /**
     * The url to this package assets directory.
     * ./pluginName/vendor/ibelar/atk-wordpress/assets.
     *
     * @var string
     */
    protected $atkWpAssetsUrl;

    /**
     * The url to the plugin assets directory.
     * ./pluginName/assest.
     *
     * @var string
     */
    protected $assetsUrl;

    /**
     * EnqueueService constructor.
     *
     * @param ComponentCtrlInterface $ctrl
     * @param array                  $enqueueFiles
     * @param string                 $assetUrl
     * @param string                 $vendorUrl
     */
    public function __construct(ComponentCtrlInterface $ctrl, array $enqueueFiles, $assetUrl, $vendorUrl)
    {
        $this->ctrl = $ctrl;
        $this->assetsUrl = $assetUrl;
        $this->vendorUrl = $vendorUrl;
        $this->atkWpAssetsUrl = $vendorUrl.'/ibelar/atk-wordpress/assets';

        if (is_admin()) {
            if (isset($enqueueFiles['admin']['js']) && is_array($enqueueFiles['admin']['js'])) {
                $this->jsFiles = array_merge($this->jsFiles, $enqueueFiles['admin']['js']);
            }
            if (isset($enqueueFiles['admin']['css']) && is_array($enqueueFiles['admin']['css'])) {
                $this->cssFiles = array_merge($this->cssFiles, $enqueueFiles['admin']['css']);
            }
            add_action('admin_enqueue_scripts', [$this, 'enqueueAdminFiles']);
        } else {
            if (isset($enqueueFiles['front']['js']) && is_array($enqueueFiles['front']['js'])) {
                $this->jsFiles = array_merge($this->jsFiles, $enqueueFiles['front']['js']);
            }
            if (isset($enqueueFiles['front']['css']) && is_array($enqueueFiles['front']['css'])) {
                $this->cssFiles = array_merge($this->cssFiles, $enqueueFiles['front']['css']);
            }
            add_action('wp_enqueue_scripts', [$this, 'enqueueFrontFiles']);
        }
    }

    /**
     * WP action function when running under admin mode.
     *
     * @param $hook
     */
    public function enqueueAdminFiles($hook)
    {
        $this->registerAtkWpFiles();
        // Check if this is an atk component.
        // We need to load js and css for atk when using panel or metaBox
        if ($component = $this->ctrl->searchComponentByType('panel', $hook, 'hook')) {
        } elseif ($hook === 'post.php') {
            // if we are here, mean that we are editing a post.
            // check it's type and see if a metabox is using this type.
            if ($postType = get_post_type($_GET['post'])) {
                $component = $this->ctrl->searchComponentByType('metaBox', $postType, 'type');
            }
        } elseif ($hook === 'post-new.php') {
            if ($postType = @$_GET['post_type']) {
                // Check if we have a metabox that is using this post type.
                $component = $this->ctrl->searchComponentByType('metaBox', $postType, 'type');
            } else {
                //if not post_type set, this mean that we have a regular post.
                //Check if a metabox using post.
                $component = $this->ctrl->searchComponentByType('metaBox', 'post', 'type');
            }
        } elseif ($hook === 'index.php') {
            // if we are here mean that we are in dashboard page.
            // for now, just load atk js file if we are using dashboard.
            $component = $this->ctrl->getComponentsByType('dashboard');
        }

        if (isset($component)) {
            //check if component require specific js or css file.
            if (isset($component['js']) && !empty($component['js'])) {
                $this->jsFiles = array_merge($this->jsFiles, $component['js']);
            }
            if (isset($component['css']) && !empty($component['css'])) {
                $this->cssFiles = array_merge($this->cssFiles, $component['css']);
            }
            if (isset($component['js-inc']) && !empty($component['js-inc'])) {
                $this->jsRegistered = array_merge($this->jsRegistered, $component['js-inc']);
            }

            //Load our register atk js and css.
            wp_enqueue_script('atkjs-ui');
            wp_enqueue_style('atk-wp');
        }

        if (!empty($this->jsFiles)) {
            $this->enqueueFiles($this->jsFiles, 'js');
        }
        if (!empty($this->cssFiles)) {
            $this->enqueueFiles($this->cssFiles, 'css');
        }
        if (!empty($this->jsRegistered)) {
            $this->enqueueJsInclude($this->jsRegistered);
        }
    }

    /**
     * WP action function when running in front end.
     */
    public function enqueueFrontFiles()
    {
        $this->registerAtkWpFiles();

        if (!empty($this->jsFiles)) {
            $this->enqueueFiles($this->jsFiles, 'js');
        }
        if (!empty($this->cssFiles)) {
            $this->enqueueFiles($this->cssFiles, 'css');
        }
        if (!empty($this->jsRegistered)) {
            $this->enqueueJsInclude($this->jsRegistered);
        }
    }

    /**
     * Shortcode need to run in Wp front.
     * This method is used to directly enqueue files when shortcode need them.
     *
     * @param $shortcode
     */
    public function enqueueShortCodeFiles($shortcode)
    {
        if ($shortcode['atk']) {
            $this->enqueueJsInclude(['atkjs-ui']);
            //$this->enqueueCssInclude(['semantic', 'semantic-calendar']);
            $this->enqueueCssInclude(['fomantic', 'atkjs-ui']);
        }

        if (!empty($jsFiles)) {
            $this->enqueueFiles($jsFiles, 'js');
        }
        if (!empty($cssFiles)) {
            $this->enqueueFiles($cssFiles, 'css');
        }
    }

    /**
     * The actual file inclusion in WP.
     *
     * @param array  $files    The list of files to include.
     * @param string $type     The type of file to include, js or css.
     * @param null   $required The required file to include if needed.
     */
    public function enqueueFiles($files, $type, $required = null)
    {
        if ($type === 'js') {
            foreach ($files as $file) {
                if (strpos($file, 'http') === 0) {
                    $source = $file;
                } else {
                    $source = "{$this->assetsUrl}/js/{$file}.js";
                }
                //load in footer
                wp_enqueue_script($file, $source, $required, false, true);
            }
        } else {
            foreach ($files as $file) {
                if (strpos($file, 'http') === 0) {
                    $source = $file;
                } else {
                    $source = "{$this->assetsUrl}/css/{$file}.css";
                }
                wp_enqueue_style($file, $source, $file);
            }
        }
    }

    /**
     * Register js and css files necessary for our components.
     */
    protected function registerAtkWpFiles()
    {
      
        wp_register_script(
            'jQuery',
            "https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js",
            [],
            null,
            true
        );
            
        wp_register_script(
            'fomantic',
            //"{$this->atkWpAssetsUrl}/vendor/fomantic/{$this->fomanticUiVersion}/semantic.min.js",
            //2.8.7/semantic.min.js
            "https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/{$this->fomanticUiVersion}/semantic.min.js",
            [],
            $this->fomanticUiVersion,
            true
        );
            
        wp_register_style(
            'fomantic',
            //"{$this->atkWpAssetsUrl}/vendor/fomantic/{$this->fomanticUiVersion}/semantic.min.js",
            //https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.8.7/semantic.min.css
            "https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/{$this->fomanticUiVersion}/semantic.min.css",
            [],
            $this->fomanticUiVersion,
            false          
        );
            
            
        wp_register_script(
            'flatpickr',
            "https://cdnjs.cloudflare.com/ajax/libs/flatpickr/{$this->flatpickrVersion}/flatpickr.min.js",
            [],
            null,
            true
        );
            
        wp_register_style(
            'flatpickr',
            "https://cdnjs.cloudflare.com/ajax/libs/flatpickr/{$this->flatpickrVersion}/flatpickr.min.css",
            [],
            null,
            false
        );

        /*
         * Register our js files.
         * Because we declare dependencies for atk4JS, then calling wp_enqueue_script('atk4JS') will also load
         * these dependencies.
         */
        wp_register_script(
            'atkjs-ui',
            //"{$this->atkWpAssetsUrl}/vendor/atk4/ui/{$this->atkUiVersion}/atkjs-ui.min.js",
            //https://raw.githack.com/atk4/ui/3.0.0/public/atkjs-ui.min.js        
            "https://raw.githack.com/atk4/ui/{$this->atkUiVersion}/public/atkjs-ui.min.js",
            ['jquery-serialize-object', 'fomantic'],
            null,//$this->atkUiVersion,
            true
            
        );

        wp_register_style(
            'atkjs-ui',
            //"{$this->atkWpAssetsUrl}/vendor/fomantic/{$this->fomanticUiVersion}/semantic.min.css",
            //https://raw.githack.com/atk4/ui/3.0.0/public/agileui.css
            "https://raw.githack.com/atk4/ui/{$this->atkUiVersion}/public/agileui.css",
            ['fomantic'],
            null,//$this->atkUiVersion,
            false
        );

        // Admin section css fix for certain semantic ui element.
        wp_register_style(
            'atk-wp',
            "{$this->atkWpAssetsUrl}/css/atk-wordpress.css",
            ['jQuery' ,'fomantic'],
            null
        );
    }

    /**
     * The js files to includes that are already registered within WP.
     *
     * @param array $files
     */
    protected function enqueueJsInclude(array $files)
    {
        foreach ($files as $file) {
            wp_enqueue_script($file);
        }
    }

    /**
     * The css files to include that are already registered withing WP.
     *
     * @param array $files
     */
    protected function enqueueCssInclude(array $files)
    {
        foreach ($files as $file) {
            wp_enqueue_style($file);
        }
    }
}
