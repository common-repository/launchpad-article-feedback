<?php
/***
	Plugin Name: Launchpad Article Feedback
	Plugin URI: https://launchpad.vn
	Description: Was this article helpful?
	Version: 1.1
	Author: Launchpad.vn
	Author URI: https://launchpad.vn
	Author Email: vinhdd.cntt@gmail.com
	Text Domain: launchpad-feedback
	Domain Path: /languages
***/

class LaunchpadFeedback {
	private static $instance;
	const VERSION = '1.1';

	private static function has_instance() {
		return isset( self::$instance ) && null != self::$instance;
	}

	public static function get_instance() {
		if ( ! self::has_instance() ) {
			self::$instance = new LaunchpadFeedback;
		}
		return self::$instance;
	}

	public static function setup() {
		self::get_instance();
	}

	protected function __construct() {
		if ( ! self::has_instance() ) {
			$this->init();
		}
	}

	public function init() {
		add_action('wp_enqueue_scripts', array( $this, 'register_plugin_styles' ) );
		add_action('admin_enqueue_scripts', array( $this, 'register_plugin_admin_styles' ) );
		add_shortcode('launchpad_feedback', array( $this, 'feedback_content' ) );
		
		// register our settings page
		add_action( 'admin_menu', array( $this, 'register_submenu' ) );
		
		// register setting
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		
		// content Filter wp the_content
		add_filter( 'the_content', array( $this, 'append_feedback_html' ) );
		register_activation_hook( __FILE__, array( $this, 'load_defaults' ) );
		
		//Ajax to send feedback
		add_action('wp_ajax_launchpad_feedback', array( $this,'get_launchpad_feedback'));
		add_action('wp_ajax_nopriv_launchpad_feedback', array( $this,'get_launchpad_feedback'));
		
		//Language Support For Article Feedback
		add_action( 'plugins_loaded', array($this,'feedback_load_textdomain') );

		//Custom post Feedback
		add_action( 'init', array( $this, 'feedback_register' ) );
		add_filter( 'manage_edit-feedback_columns', array( $this, 'feedback_edit_columns' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'feedback_column_display' ), 10, 2 );
		add_action( 'add_meta_boxes', array( $this, 'lp_create_metabox' ) );
	}

	public function feedback_register() {  
		global $data;
		$labels = array(
			'name'               => __( 'Feedback', 'launchpad-feedback' ),
			'singular_name'      => __( 'Feedback', 'launchpad-feedback' ),
			'add_new'            => __( 'Add Feedback', 'launchpad-feedback' ),
			'add_new_item'       => __( 'Add Feedback', 'launchpad-feedback' ),
			'edit_item'          => __( 'Edit Feedback', 'launchpad-feedback' ),
			'new_item'           => __( 'Add Feedback', 'launchpad-feedback' ),
			'view_item'          => __( 'View Feedback', 'launchpad-feedback' ),
			'search_items'       => __( 'Search Feedback', 'launchpad-feedback' ),
			'not_found'          => __( 'No feedback items found', 'launchpad-feedback' ),
			'not_found_in_trash' => __( 'No feedback items found in trash', 'launchpad-feedback' )
		);
		
	    $args = array(  
			'labels'          => $labels,
			'public'          => false,  
			'show_ui'         => true,  
			'has_archive'     => false,
			'capability_type' => 'post',  
			'capabilities'    => array(
			    'create_posts'=> false, 
			),
			'map_meta_cap' 	  => true,
			'hierarchical' 	  => false,  
			'menu_position'   => 22,
			'menu_icon'       => 'dashicons-format-status',
			'rewrite'      	  => array('slug' => false), 
			'supports'     	  => array('title', 'editor'),
	       );  
	  
	    register_post_type( 'feedback' , $args );  
	}  

	public function lp_create_metabox(){
	    add_meta_box('feedback-info', 'Feedback Info', array($this, 'lp_show_feedback_info'), 'feedback', 'advanced', 'high');
	}
  
	function lp_show_feedback_info($post, $box){
	    $link_download = get_post_meta($post->ID, 'post_link_download', true);
	    $link_demo = get_post_meta($post->ID, 'post_link_demo', true);
	     
	    wp_nonce_field(basename(__FILE__), 'post_media_metabox');
	     
	    ?>
	    <p>
	        <input type="text" value="<?php echo $link_download; ?>" name="post_link_download" /> Link Download
	    </p>
	    <p>
	        <input type="text" value="<?php echo $link_demo; ?>" name="post_link_demo" /> Link Demo
	    </p>
	    <?php
	}

	public function feedback_edit_columns( $feedback_columns ) {
		$feedback_columns = array(
			"cb" => "<input type=\"checkbox\" />",
			"name" => _x('Author', 'feedbackposttype'),
			//"thumbnail" => __('Thumbnail', 'feedbackposttype'),
			"post" => __('Post', 'feedbackposttype'),
			"feedback" => __('Feedback', 'feedbackposttype'),
			//"comments" => __('Comments', 'feedbackposttype'),
			"date" => __('Date', 'feedbackposttype'),
		);
		//$feedback_columns['comments'] = '<div class="vers"><img alt="Comments" src="' . esc_url( admin_url( 'images/comment-grey-bubble.png' ) ) . '" /></div>';
		return $feedback_columns;
	}

	public function feedback_column_display( $feedback_columns, $post_id ) {
		switch ( $feedback_columns ) {
			case "name":
				echo '<strong style="font-size:15px;color:#333;">'. get_post_meta( $post_id, 'feedback_name', true ) . '</strong><br>';
				echo  get_post_meta( $post_id, 'feedback_email', true ) . '<br>';
			break;	
			case "post":
				echo '<a href="'.get_permalink(get_post_meta( $post_id, 'feedback_post_id', true )).'">'.get_the_title(get_post_meta( $post_id, 'feedback_post_id', true )).'</a>';
			break;	
			case "feedback":
				echo  get_post_meta( $post_id, 'feedback_message', true ). '<br>';
				echo '<i>'. get_post_meta( $post_id, 'feedback_response', true ) .'</i>';
			break;			
		}
	}
 
	/**
	* Feedback Plugin styles.
	*
	* @since 1.0
	*/
	public function register_plugin_styles() {
		global $wp_styles;
		$feedback_options = $this->get_feedback_options('feedback_options');
		$fontsize=$feedback_options['lp-font-size'];
		$Upcolor=($feedback_options['lp-thumbs-up']!="")?$feedback_options['lp-thumbs-up']:'#FF3234';
		$Downcolor=($feedback_options['lp-thumbs-down']!="")?$feedback_options['lp-thumbs-down']:'#5C7ED7';

		wp_enqueue_style( 'launchpad_feedback_css', plugins_url( 'assets/css/launchpad_feedback.css', __FILE__ ), array(), self::VERSION, 'all' );
		wp_enqueue_script( 'launchpad_feedback_js', plugins_url( 'assets/js/launchpad_feedback.js', __FILE__ ), array('jquery'), self::VERSION, 'all' );
		// declare the URL to the file that handles the AJAX request (wp-admin/admin-ajax.php)
		wp_localize_script( 'launchpad_feedback_js', 'LaunchpadFeedback', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		wp_add_inline_style( 'launchpad_feedback_css', '' );
	}

	/**
    * Add custom css for admin section
    * @since 1.2
    */
    function register_plugin_admin_styles(){
       	wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script('launchpad_feedback_admin_js', plugins_url( 'assets/js/launchpad_feedback_admin.js', __FILE__ ), array('jquery','wp-color-picker'), self::VERSION, 'all' );
        wp_register_style('launchpad_feedback_admin_css', plugin_dir_url(__FILE__) . '/assets/css/launchpad_feedback_admin.css', false, self::VERSION );
        wp_enqueue_style( 'launchpad_feedback_admin_css' );
    }

	/**
	* Load plugin textdomain.
	*
	* @since 1.2
	*/
	function feedback_load_textdomain() {
		load_plugin_textdomain( 'launchpad-feedback', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' ); 
	}
	
	/**
	* Feedback Content.
	*
	* @since 1.0
	*/
	public function feedback_content() {
		global $post;
		$feedback_options = $this->get_feedback_options('feedback_options');
		$onclick="javascript:window.open(this.href,'', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=600,width=600');return false;";
	
		return 
			'<div id="af-was">
				<h3>'.$this->get_feedback_default_title($feedback_options['lp-title-phrase-1'],'Was this article helpful?').'</h3>
				<a href="#" id="af-yes">'.$this->get_feedback_default_title($feedback_options['lp-button-yes'],'Yes').'</a>
				<a href="#" id="af-no">'.$this->get_feedback_default_title($feedback_options['lp-button-no'],'No').'</a>
			</div>
			<div id="af-popup-1">
				<div>
					<div class="af-popup-content af-popup-1-content">
						<a href="#" class="af-close-popup">&times;</a>
						<h3>'.$this->get_feedback_default_title($feedback_options['lp-title-phrase-5'],'What went wrong?').'</h3>
						<p>
							<a href="#" class="af-response-select" data-show=".af-incorrect">'.$this->get_feedback_default_title($feedback_options['lp-title-phrase-6'],'This article contains incorrect information').'</a>
						</p>
						<p>
							<a href="#" class="af-response-select" data-show=".af-missing">'.$this->get_feedback_default_title($feedback_options['lp-title-phrase-7'],'This article does not have the information I am looking for').'</a>
						</p>
					</div>
				</div>
			</div>
			<div id="af-popup-2">
				<div>
					<div class="af-popup-content">
						<a href="#" class="af-close-popup">&times;</a>
						<h3>'.$this->get_feedback_default_title($feedback_options['lp-title-phrase-10'],'How can we improve it?').'</h3>
						<form id="af-feedback-form" accept-charset="UTF-8" method="post">
							'.wp_nonce_field(-1,'authenticity_token',true, false).'
							<input type="hidden" name="action" value="af_response"/>
							<input type="hidden" id="af-response" name="af-response" value="Article was helpful"/>
							<input type="hidden" id="af-post-id" name="af-post-id" value="'.urldecode($post->ID).'"/>
							<label for="af-feedback"><span class="af-incorrect">'.$this->get_feedback_default_title($feedback_options['lp-title-phrase-8'],'Please tell us what was incorrect').':</span><span class="af-missing">'.$this->get_feedback_default_title($feedback_options['lp-title-phrase-9'],'Please tell us what was missing').':</span></label>
							<textarea rows="4" minlength="20" id="af-feedback" name="af-feedback" required></textarea>
							<div class="af-row">
								<div class="af-column">
									<label for="af-name">'.$this->get_feedback_default_title($feedback_options['lp-your-name'],'Your Name').':</label>
									<input type="text" id="af-name" name="af-name" required value=""/>
								</div>
								<div class="af-column">
									<label for="af-email">'.$this->get_feedback_default_title($feedback_options['lp-your-email'],'Your Email').':</label>
									<input type="email" id="af-email" name="af-email" required value=""/>
								</div>
							</div>
							<p class="af-error"></p>
							<input type="submit" value="'.$this->get_feedback_default_title($feedback_options['lp-button-submit'],'Submit').'"/>
						</form>
					</div>
				</div>
			</div>
			<div id="af-popup-3">
				<div>
					<div class="af-popup-content af-poopup-content-3">
						<a href="#" class="af-close-popup">&times;</a>
						<h3>'.$this->get_feedback_default_title($feedback_options['lp-title-phrase-2'],'We appreciate your helpul feedback!').'</h3>
						<p>'.$this->get_feedback_default_title($feedback_options['lp-title-phrase-3'],'Your answer will be used to improve our content. The more feedback you give us, the better our pages can be.').'</p>
						<h4>'.$this->get_feedback_default_title($feedback_options['lp-title-phrase-4'],'Follow us on social media').':</h4>
						<a href="'.$this->get_feedback_default_title($feedback_options['lp-facebook-url'],'https://facebook.com/vinhdd.cntt').'" target="_blank" class="fa-share-button fa-share-facebook"><span class="icon-facebook"></span> Facebook</a>
						<a href="'.$this->get_feedback_default_title($feedback_options['lp-pinterest-url'],'https://www.pinterest.co.uk/TexRoxy/').'" target="_blank" class="fa-share-button fa-share-pinterest"><span class="icon-pinterest-p"></span> Pinterest</a>
					</div>
				</div>
			</div>';
	}

		/**
		* Feedback Append HTML with Content with Thumbs Up and Down.
		*
		* @since 1.0
		*/
	
	public function append_feedback_html( $content ) {
		$feedback_options = $this->get_feedback_options('feedback_options');
		// get current post's id
		global $post;
		$post_id = $post->ID;
		
		if( in_array($post_id,explode(',',$feedback_options['lp-exclude-on'])) )
			return $content;
		if( is_home() && !in_array( 'home', (array)$feedback_options['lp-show-on'] ) )
			return $content;
		if( is_single() && !in_array( 'posts', (array)$feedback_options['lp-show-on'] ) )
			return $content;
		if( is_page() && !in_array( 'pages', (array)$feedback_options['lp-show-on'] ) )
			return $content;
		if( is_archive() && !in_array( 'archive', (array)$feedback_options['lp-show-on'] ) )
			return $content;
		
		$feedback_html_markup = $this->feedback_content();
		
		if( is_array($feedback_options['lp-select-position']) && in_array('before-content', $feedback_options['lp-select-position']) )
			$content = $feedback_html_markup.$content;
		if( is_array($feedback_options['lp-select-position']) && in_array('after-content', (array)$feedback_options['lp-select-position']) )
			$content .= $feedback_html_markup;
		return $content;

	}
	public function load_defaults(){

		update_option( 'feedback_options', $this->get_defaults() );

	}
	public function get_defaults($preset=true) {
		return array(
			'ss-select-position' => $preset ? array('before-content') : array(),
			'ss-show-on' => $preset ? array('pages', 'posts') : array(),
			'ss-title-phrase'=>'',
			'ss-exclude-on' => '',
			'ss-feedback-email'=>'',
			'ss-font-size'=>'2.4',
			'ss-thumbs-up'=>'#5C7ED7',
			'ss-thumbs-down'=>'#FF3234'
		);
	}

	public function register_settings(){
		register_setting( 'feedback_options', 'feedback_options' );
	}

	/**
	 * Add sub menu page in Settings for configuring plugin
	 *
	 * @since 1.0
	 */
	public function register_submenu(){

		add_submenu_page( 'options-general.php', 'Launchpad Article Feedback settings', 'Launchpad Article Feedback', 'activate_plugins', 'article-feeback-settings', array( $this, 'submenu_page' ) );

	}

	public function get_feedback_options() {
		return array_merge( $this->get_defaults(false), get_option('feedback_options') );
	}


	public function get_feedback_default_title($title,$default) {
		if( isset($title) && $title!=""):
			return 	$title;
		else:
			return $default;
		endif;
	}
	/*
	 * Callback for add_submenu_page for generating markup of page
	 */
	public function submenu_page() {
		?>
		<div class="wrap">
			<h2 class="boxed-header"><?php  _e('Launchpad Article Feedback Settings','launchpad-feedback');?></h2>
			<div class="activate-boxed-highlight activate-boxed-option">
				<form method="POST" action="options.php">
				<?php settings_fields('feedback_options'); ?>
				<?php
				$feedback_options = get_option('feedback_options');
				?>
				<?php echo $this->admin_form($feedback_options); ?>
			</div>
			<div class="activate-use-option sidebox first-sidebox">
	          	<h3><?php  _e('Instruction to use Plugin','launchpad-feedback');?></h3>
	        	<hr />
	        	<h3><?php _e('Using Shortcode','launchpad-feedback');?></h3>
	        	<p><?php _e('You can place the shortcode ','launchpad-feedback')?> <code>[launchpad_feedback]</code> <?php _e(' wherever you want to display the Launchpad Article Feedback.','launchpad-feedback');?></p>
	        	<hr />
	        </div>
		</div>
		<?php
	}

	/**
	 * Admin form for Feedabck Settings
	 *
	 *@since 1.0
	 */
	public function admin_form( $feedback_options ){
	
		return '<table class="form-table settings-table">
			<tr>
				<th><label for="lp-select-postion">'.__('Select Position','launchpad-feedback').'</label></th>
				<td>
					<input type="checkbox" name="feedback_options[lp-select-position][]" id="before-content" class="css-checkbox" value="before-content" '.__checked_selected_helper( in_array( 'before-content', (array)$feedback_options['lp-select-position'] ),true, false,'checked' ).'>
					<label for="before-content" class="css-label cb0">'.__('Before Content','launchpad-feedback').'</label>					
					<input type="checkbox" name="feedback_options[lp-select-position][]" id="after-content" class="css-checkbox" value="after-content" '.__checked_selected_helper( in_array( 'after-content', (array)$feedback_options['lp-select-position'] ),true, false,'checked' ).'>
					<label for="after-content" class="css-label cb0">'.__('After Content','launchpad-feedback').'</label>					
					
				</td>
			</tr>
			<tr>
				<th><label for="lp-select-postion">'.__('Show on','launchpad-feedback').'</label></th>
				<td>
					<input type="checkbox" name="feedback_options[lp-show-on][]" id="home-pages" class="css-checkbox" value="home" '.__checked_selected_helper( in_array( 'home', (array)$feedback_options['lp-show-on'] ),true, false,'checked' ).'>
					<label for="home-pages" class="css-label cb0">'.__('Home Page','launchpad-feedback').'</label>					
					<input type="checkbox" name="feedback_options[lp-show-on][]" id="pages" class="css-checkbox" value="pages" '.__checked_selected_helper( in_array( 'pages', (array)$feedback_options['lp-show-on'] ),true, false,'checked' ).'>
					<label for="pages" class="css-label cb0">'.__('Pages','launchpad-feedback').'</label>					
					<input type="checkbox" name="feedback_options[lp-show-on][]" id="posts" class="css-checkbox" value="posts" '.__checked_selected_helper( in_array( 'posts', (array)$feedback_options['lp-show-on'] ),true, false,'checked' ).'>
					<label for="posts" class="css-label cb0">'.__('Posts','launchpad-feedback').'</label>					
					<input type="checkbox" name="feedback_options[lp-show-on][]" id="archives" class="css-checkbox" value="archive" '.__checked_selected_helper( in_array( 'archive', (array)$feedback_options['lp-show-on'] ),true, false,'checked' ).'>
					<label for="archives" class="css-label cb0">'.__('Archives','launchpad-feedback').'</label>					
				</td>
			</tr>
			<tr>
				<th><label for="lp-title-phrase-1">'.__('Title Phrase 1','launchpad-feedback').'</label></th>
				<td>
					<input type="text" name="feedback_options[lp-title-phrase-1]" value="'.$feedback_options['lp-title-phrase-1'].'">
					<small><em>'.__('Was this article helpful?','launchpad-feedback').' </em></small>
				</td>
			</tr>
			<tr>
				<th><label for="lp-title-phrase-2">'.__('Title Phrase 2','launchpad-feedback').'</label></th>
				<td>
					<input type="text" name="feedback_options[lp-title-phrase-2]" value="'.$feedback_options['lp-title-phrase-2'].'">
					<small><em>'.__('We appreciate your helpul feedback!','launchpad-feedback').' </em></small>
				</td>
			</tr>
			<tr>
				<th><label for="lp-title-phrase-3">'.__('Title Phrase 3','launchpad-feedback').'</label></th>
				<td>
					<input type="text" name="feedback_options[lp-title-phrase-3]" value="'.$feedback_options['lp-title-phrase-3'].'">
					<small><em>'.__('Your answer will be used to improve our content. The more feedback you give us, the better our pages can be.','launchpad-feedback').' </em></small>
				</td>
			</tr>
			<tr>
				<th><label for="lp-title-phrase-4">'.__('Title Phrase 4','launchpad-feedback').'</label></th>
				<td>
					<input type="text" name="feedback_options[lp-title-phrase-4]" value="'.$feedback_options['lp-title-phrase-4'].'">
					<small><em>'.__('Follow us on social media:','launchpad-feedback').' </em></small>
				</td>
			</tr>
			<tr>
				<th><label for="lp-title-phrase-5">'.__('Title Phrase 5','launchpad-feedback').'</label></th>
				<td>
					<input type="text" name="feedback_options[lp-title-phrase-5]" value="'.$feedback_options['lp-title-phrase-5'].'">
					<small><em>'.__('What went wrong?','launchpad-feedback').' </em></small>
				</td>
			</tr>
			<tr>
				<th><label for="lp-title-phrase-6">'.__('Title Phrase 6','launchpad-feedback').'</label></th>
				<td>
					<input type="text" name="feedback_options[lp-title-phrase-6]" value="'.$feedback_options['lp-title-phrase-6'].'">
					<small><em>'.__('This article contains incorrect information.','launchpad-feedback').' </em></small>
				</td>
			</tr>
			<tr>
				<th><label for="lp-title-phrase-7">'.__('Title Phrase 7','launchpad-feedback').'</label></th>
				<td>
					<input type="text" name="feedback_options[lp-title-phrase-7]" value="'.$feedback_options['lp-title-phrase-7'].'">
					<small><em>'.__('This article does not have the information I am looking for','launchpad-feedback').' </em></small>
				</td>
			</tr>
			<tr>
				<th><label for="lp-title-phrase-8">'.__('Title Phrase 8','launchpad-feedback').'</label></th>
				<td>
					<input type="text" name="feedback_options[lp-title-phrase-8]" value="'.$feedback_options['lp-title-phrase-8'].'">
					<small><em>'.__('Please tell us what was incorrect','launchpad-feedback').' </em></small>
				</td>
			</tr>
			<tr>
				<th><label for="lp-title-phrase-9">'.__('Title Phrase 9','launchpad-feedback').'</label></th>
				<td>
					<input type="text" name="feedback_options[lp-title-phrase-9]" value="'.$feedback_options['lp-title-phrase-9'].'">
					<small><em>'.__('Please tell us what was missing','launchpad-feedback').' </em></small>
				</td>
			</tr>
			<tr>
				<th><label for="lp-title-phrase-9">'.__('Title Phrase 10','launchpad-feedback').'</label></th>
				<td>
					<input type="text" name="feedback_options[lp-title-phrase-10]" value="'.$feedback_options['lp-title-phrase-10'].'">
					<small><em>'.__('How can we improve it?','launchpad-feedback').' </em></small>
				</td>
			</tr>
			<tr>
				<th><label>'.__('Your Name','launchpad-feedback').'</label></th>
				<td>
					<input type="text" name="feedback_options[lp-your-name]" value="'.$feedback_options['lp-your-name'].'">
				</td>
			</tr>
			<tr>
				<th><label>'.__('Your Email','launchpad-feedback').'</label></th>
				<td>
					<input type="text" name="feedback_options[lp-your-email]" value="'.$feedback_options['lp-your-email'].'">
				</td>
			</tr>
			<tr>
				<th><label>'.__('Button Yes','launchpad-feedback').'</label></th>
				<td>
					<input type="text" name="feedback_options[lp-button-yes]" value="'.$feedback_options['lp-button-yes'].'">
				</td>
			</tr>
			<tr>
				<th><label>'.__('Button No','launchpad-feedback').'</label></th>
				<td>
					<input type="text" name="feedback_options[lp-button-no]" value="'.$feedback_options['lp-button-no'].'">
				</td>
			</tr>
			<tr>
				<th><label>'.__('Button Submit','launchpad-feedback').'</label></th>
				<td>
					<input type="text" name="feedback_options[lp-button-submit]" value="'.$feedback_options['lp-button-submit'].'">
				</td>
			</tr>
			<tr>
				<th><label>'.__('Facebook URL','launchpad-feedback').'</label></th>
				<td>
					<input type="text" name="feedback_options[lp-facebook-url]" value="'.$feedback_options['lp-facebook-url'].'">
				</td>
			</tr>
			<tr>
				<th><label>'.__('Pinterest URL','launchpad-feedback').'</label></th>
				<td>
					<input type="text" name="feedback_options[lp-pinterest-url]" value="'.$feedback_options['lp-pinterest-url'].'">
				</td>
			</tr>
			<tr>
				<th><label for="lp-exclude-on">'.__('Exclude on','launchpad-feedback').'</label></th>
				<td>
					<input type="text" name="feedback_options[lp-exclude-on]" value="'.$feedback_options['lp-exclude-on'].'">
					<small><em>'.__('Comma seperated post id\'s Eg:','launchpad-feedback').' </em><code>1207,1222</code></small>
				</td>
			</tr>
			
			<tr>
				<th><label for="lp-thumbs-up">'.__('Button color YES','launchpad-feedback').'</label></th>
				<td>
					<input type="text" name="feedback_options[lp-color-yes]" id="ssthumbsup" data-default-color="#4bbb8b" value="'.$feedback_options['lp-color-yes'].'">
				</td>
			</tr>
			<tr>
				<th><label for="lp-thumbs-down">'.__('Button color NO','launchpad-feedback').'</label></th>
				<td>
					<input type="text" name="feedback_options[lp-color-no]" id="ssthumbsdown" data-default-color="#ed6363" value="'.$feedback_options['lp-color-no'].'">
				</td>
			</tr>
		</table>
		<p class="submit">
			<input type="submit" name="submit" id="submit" class="button button-primary" value="'.__('Save Changes','launchpad-feedback').'">
		</p>
	</form>';

	}
	/**
	* Feedback send article Feedback by mail to article author or custom provided mail id
	*
	* @since 1.0 
	*/
	function get_launchpad_feedback(){
		$feedback_options = get_option('feedback_options');
		$name             = sanitize_text_field($_POST['name']);
		$feedback         = sanitize_text_field($_POST['feedback']);
		$email            = sanitize_email($_POST['email']);
		$post_id          = sanitize_text_field($_POST['post_id']);
		$response         = sanitize_text_field($_POST['response']);

		$my_feedback = array(
			'post_title'   => wp_strip_all_tags($name) . wp_strip_all_tags($email),
			'post_content' => $feedback,
			'post_status'  => 'publish',
			'post_type'    => 'feedback',
		);
		if($feedback_id = wp_insert_post($my_feedback)){
			update_post_meta($feedback_id, 'feedback_name', $name);
			update_post_meta($feedback_id, 'feedback_email', $email);
			update_post_meta($feedback_id, 'feedback_message', $feedback);
			update_post_meta($feedback_id, 'feedback_post_id', $post_id);
			update_post_meta($feedback_id, 'feedback_response', $response);
		}	
	}
}

LaunchpadFeedback::setup();
