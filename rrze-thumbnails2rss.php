<?php

/**
 * Plugin Name:     RRZE Thumbnails2RSS
 * Plugin URI:      https://github.com/RRZE-Webteam/rrze-thumbnails2rss
 * Description:     Erweiterung des RSS-XML von WordPress-RSS-Feeds um Angaben zu Thumbnails des jeweiligen Beitrags. Siehe auch http://www.sciencemedianetwork.org/wiki/Enclosures_in_yahoo_media,_rss,_and_atom
 * Version:         1.0.1
 * Author:          RRZE-Webteam
 * Author URI:      https://blogs.fau.de/webworking/
 * License:         GNU General Public License v2
 * License URI:     http://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path:     /languages
 * Text Domain:     rrze-thumbnails2rss
 */

add_action('plugins_loaded', array('RRZE_Thumbnails2RSS', 'instance'));

register_activation_hook(__FILE__, array('RRZE_Thumbnails2RSS', 'activation'));

class RRZE_Thumbnails2RSS {
    const option_name = '_rrze_thumbnails2rss';
    const php_version = '5.3'; // Minimal erforderliche PHP-Version
    const wp_version = '3.9'; // Minimal erforderliche WordPress-Version

    protected static $instance = null;

    public static function instance() {

        if (null == self::$instance) {
            self::$instance = new self;
            self::$instance->init();
        }

        return self::$instance;
    }
    
    private $thumbnailrss_option_page = null;

    public static function activation() {
        // Sprachdateien werden eingebunden.
        self::load_textdomain();

        self::system_requirements();
    }
    
    private static function system_requirements() {
        $error = '';

        if (version_compare(PHP_VERSION, self::php_version, '<')) {
            $error = sprintf(__('Ihre PHP-Version %s ist veraltet. Bitte aktualisieren Sie mindestens auf die PHP-Version %s.', 'rrze-thumbnails2rss'), PHP_VERSION, self::php_version);
        }

        if (version_compare($GLOBALS['wp_version'], self::wp_version, '<')) {
            $error = sprintf(__('Ihre Wordpress-Version %s ist veraltet. Bitte aktualisieren Sie mindestens auf die Wordpress-Version %s.', 'rrze-thumbnails2rss'), $GLOBALS['wp_version'], self::wp_version);
        }

        if (!empty($error)) {
            deactivate_plugins(plugin_basename(__FILE__), false, true);
            wp_die($error);
        }
    }

    private function default_options() {

        $options = array(
	    "add_namespace" => 'http://search.yahoo.com/mrss/',
	    'show_full'	    => false,
	    'show_allsizes' => false,
	    'filter_sizes'  => array('thumbnail', 'medium', 'large', 'post-thumbnail'),
	);

        return $options;
    }

    public function get_options() {
        $defaults = $this->default_options();
        $options = (array) get_option(self::option_name);
        $options = wp_parse_args($options, $defaults);
        $options = array_intersect_key($options, $defaults);
        return $options;
    }

    private function init() {
        // Sprachdateien werden eingebunden.
        self::load_textdomain();

        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_menu', array($this, 'add_options_page'));

	add_action( 'rss2_ns', array($this, 'add_media_namespace') );
	add_action( 'rss2_item',  array($this, 'add_media_thumbnail') );
    }

    // Einbindung der Sprachdateien.
    private static function load_textdomain() {
        load_plugin_textdomain('rrze-thumbnails2rss', false, sprintf('%s/languages/', dirname(plugin_basename(__FILE__))));
    }
    
    public function add_options_page() {
        $this->thumbnailrss_option_page = add_options_page(
		__('Thumbnails2RSS', 'rrze-thumbnails2rss'),
		__('Thumbnails2RSS', 'rrze-thumbnails2rss'), 
		'manage_options', 
		'menu_thumbnails2rss', 
		array($this, 'options_thumbnails2rss'));
        add_action('load-' . $this->thumbnailrss_option_page, array($this, 'thumbnailrss_help_menu'));

    }

public function thumbnailrss_help_menu() {

        $content_overview = array(
            '<p>' . __('WordPress bietet automatisch einen RSS-Stream an, mit denen Beiträge exportiert werden können. Das Plugin RRZE Thumbnails2RSS ergänzt den Standard-Satz des RSS XML um Angaben zu vorhandenen Thumbnails.', 'rrze-thumbnails2rss') . '</p>',
            '<p><strong>' . __('Standardwerte ' , 'rrze-thumbnails2rss') . '</strong></p>',
            '<p>' . __('Hier können Sie die Standard-Namespace-Angebe ändern.', 'rrze-thumbnails2rss') . '</p>'
	    );


        $help_tab_overview = array(
            'id' => 'overview',
            'title' => __('Übersicht', 'rrze-thumbnails2rss'),
            'content' => implode(PHP_EOL, $content_overview),
        );

       

        $help_sidebar = __('<p><strong>Für mehr Information:</strong></p><p><a href="http://blogs.fau.de/webworking">RRZE-Webworking</a></p><p><a href="https://github.com/RRZE-Webteam">RRZE-Webteam in Github</a></p>', 'rrze-thumbnails2rss');

        $screen = get_current_screen();

        if ($screen->id != $this->thumbnailrss_option_page) {
            return;
        }

        $screen->add_help_tab($help_tab_overview);
        $screen->set_help_sidebar($help_sidebar);
    }    

    public function options_thumbnails2rss() {
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2><?php echo esc_html(__('Einstellungen &rsaquo; RRZE Thumbnails2RSS', 'rrze-thumbnails2rss')); ?></h2>

            <form method="post" action="options.php">
                <?php
                settings_fields('rrze_thumbnails2rss_options');
                do_settings_sections('rrze_thumbnails2rss_options');
                submit_button();
                ?>
            </form>            
        </div>
        <?php
    }

    public function admin_init() {
        register_setting('rrze_thumbnails2rss_options', self::option_name, array($this, 'options_validate'));
	
	add_settings_section('thumbnails2rss_default_section', __('Thumbnail Settings', 'rrze-thumbnails2rss'), array($this, 'thumbnails2rss_sandbox_callback'), 'rrze_thumbnails2rss_options');
        add_settings_field('thumbnails2rss_namespace', __('Media Namespace', 'rrze-thumbnails2rss'), array($this, 'thumbnails2rss_namespace_callback'), 'rrze_thumbnails2rss_options', 'thumbnails2rss_default_section');
        add_settings_field('thumbnails2rss_enclosure', __('Enclosure', 'rrze-thumbnails2rss'), array($this, 'thumbnails2rss_enclosure_callback'), 'rrze_thumbnails2rss_options', 'thumbnails2rss_default_section');
        add_settings_field('thumbnails2rss_show_allsizes', __('Bildauswahl', 'rrze-thumbnails2rss'), array($this, 'thumbnails2rss_show_allsizes_callback'), 'rrze_thumbnails2rss_options', 'thumbnails2rss_default_section');

	 
	register_setting('rrze_thumbnails2rss_options', 'thumbnails2rss_namespace');
	register_setting('rrze_thumbnails2rss_options', 'thumbnails2rss_enclosure');
	register_setting('rrze_thumbnails2rss_options', 'thumbnails2rss_show_allsizes');
 
    }
    
    public function thumbnails2rss_sandbox_callback() {
	echo __('Anpassung der Art und der Auswahl der im RSS-Stream angegebenen Thumbnails.', 'rrze-thumbnails2rss')."\n";
    }

    public function thumbnails2rss_show_allsizes_callback() {
        $options = $this->get_options();

	$html = '<input type="radio" id="show_allsizes_true" name="_rrze_thumbnails2rss[show_allsizes]" value="1"' . checked( 1, $options['show_allsizes'], false ) . '/>';
	$html .= '<label for="show_allsizes_true">'.__('Alle', 'rrze-thumbnails2rss').'</label> ';
     
	$html .= '<input type="radio" id="show_allsizes_false" name="_rrze_thumbnails2rss[show_allsizes]" value="0"' . checked( 0, $options['show_allsizes'], false ) . '/>';
	$html .= '<label for="show_allsizes_false">'.__('Nur Auswahl', 'rrze-thumbnails2rss').'</label> ';
	$html .= '<p>'.__('Baue alle momehntan verfügbaren Thumbnail-Bildversionen ins RSS ein, oder nur eine Auswahl ', 'rrze-thumbnails2rss')."</p>\n";
	echo $html;
	
	echo "<p>".__('Alle aktuellen Bildauflösungen:','rrze-thumbnails2rss')."</p><code>";
	    $alle  = get_intermediate_image_sizes();
	    $last = end($alle);
	    foreach ($alle as $s) {
		echo $s;
		if ($s != $last) {
		    echo ", ";
		}
		
	    }    
	echo "</code>";
	echo "<p>".__('Hinweis: Diese Auswahl ist abhängig von dem installierten Theme und von Plugins und kann sich daher jeweils ändern.','rrze-thumbnails2rss')."</p>";
	echo "<p>".__('Standardauswahl:','rrze-thumbnails2rss')."</p><code>";
	
	    $alle  = $options['filter_sizes'];
	    $last = end($alle);
	    foreach ($alle as $s) {
		echo $s;
		if ($s != $last) {
		    echo ", ";
		}
		
	    }    
	echo "</code>";    

    }
   public function thumbnails2rss_enclosure_callback() {
        $options = $this->get_options();

	$html = '<input type="radio" id="show_full_true" name="_rrze_thumbnails2rss[show_full]" value="1"' . checked( 1, $options['show_full'], false ) . '/>';
	$html .= '<label for="show_full_true">'.__('angeben', 'rrze-thumbnails2rss').'</label> ';
     
	$html .= '<input type="radio" id="show_full_false" name="_rrze_thumbnails2rss[show_full]" value="0"' . checked( 0, $options['show_full'], false ) . '/>';
	$html .= '<label for="show_full_false">'.__('verbergen', 'rrze-thumbnails2rss').'</label> ';
	$html .= '<p>'.__('Enclosure (Originalbild) des Thumbnails', 'rrze-thumbnails2rss')."</p>\n";
     
	echo $html;

    }
    public function thumbnails2rss_namespace_callback() {
        $options = $this->get_options();
        ?>
	<input type='text' size="40" name="<?php printf('%s[add_namespace]', self::option_name); ?>" value="<?php echo $options['add_namespace']; ?>">
        <?php
    }

    public function options_validate($input) {
        $defaults = $this->default_options();
       
        $input['add_namespace'] = !empty($input['add_namespace']) ? htmlspecialchars($input['add_namespace']) : $defaults['add_namespace'];
	$input['show_full'] =( isset( 	$input['show_full'] ) ? intval($input['show_full'] ) : 0 );	
	$input['show_allsizes'] = ( isset( 	$input['show_allsizes'] ) ? intval($input['show_allsizes'] ) : 0 );	
	
	
	return $input;
    }


	
    public function do_thumbnails2rss() {
	$options = $this->get_options();
	add_action( 'rss2_ns', 'add_media_namespace' );
	add_action( 'rss2_item', 'add_media_thumbnail' );
    }
    

  

     public function add_media_thumbnail() {
      global $post;
      $options = $this->get_options();
      
      if( has_post_thumbnail( $post->ID )) {
	$thumb_ID = get_post_thumbnail_id( $post->ID );
	$data = wp_get_attachment_metadata( $thumb_ID ); 
		 
	if ($options['show_full']) {
	    $details = wp_get_attachment_image_src($thumb_ID, 'full');  
	    $size = @filesize( get_attached_file( $thumb_ID ) );
	    echo '<enclosure url="' . $details[0] . '" length="' . $size . '" type="image/jpg" />' . "\n";
	}	 
	foreach ( $data['sizes'] as $_size => $sizedata) {
		$details = wp_get_attachment_image_src($thumb_ID, $_size);
		$out = '';    
		if( is_array($details) ) {
		    if ( in_array( $_size, array('thumbnail') ) ) {
			$out .= '<media:thumbnail url="'.$details[0]. '" width="' . $details[1] . '" height="' . $details[2] . '" />' . "\n";	     
		    } else {
			
			if ($options['show_allsizes']==false) {
			    if ( in_array( $_size, $options['filter_sizes'] ) ) {
				$out .= '<media:content url="'.$details[0].'" medium="image" width="'.$details[1].'" height="'.$details[2].'" type="'.$sizedata['mime-type'].'" >';
				$out .= '<media:description type="plain"><![CDATA['.$_size.']]></media:description>';
				$out .= ' </media:content>';
				$out .= "\n";
			    }
			} else {
			    $out .= '<media:content url="'.$details[0].'" medium="image" width="'.$details[1].'" height="'.$details[2].'" type="'.$sizedata['mime-type'].'" >';
			    $out .= '<media:description type="plain"><![CDATA['.$_size.']]></media:description>';
			    $out .= ' </media:content>';
			    $out .= "\n";	
			}			
		    }
		   echo $out;		
		}

	}

      }
    }
    
     // add the namespace to the RSS opening element
    public function add_media_namespace() {
	$options = $this->get_options();
	if (!empty($options['add_namespace'])) {
	    echo "xmlns:media=\"".$options['add_namespace']."\"";
	}
    }
}

 

  