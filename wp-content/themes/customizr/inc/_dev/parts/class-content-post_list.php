<?php
/**
* Posts content actions
*
*/
if ( ! class_exists( 'CZR_post_list' ) ) :
class CZR_post_list {
  static $instance;
  function __construct () {
    self::$instance =& $this;
    //Set new image size can be set here ( => wp hook would be too late) (since 3.2.0)
    add_action( 'init'                    , array( $this, 'czr_fn_set_thumb_early_options') );
    //Set __loop hooks and customizer options (since 3.2.0)
    add_action( 'wp_head'                 , array( $this, 'czr_fn_set_post_list_hooks'));
    //append inline style to the custom stylesheet
    //! tc_user_options_style filter is shared by several classes => must always check the local context inside the callback before appending new css
    //fired on hook : wp_enqueue_scripts
    //Set thumbnail specific design based on user options
    add_filter( 'tc_user_options_style'   , array( $this , 'czr_fn_write_thumbnail_inline_css') );
  }



  /***************************
  * POST LIST HOOKS SETUP
  ****************************/
  /**
  * hook : init
  * @return void
  *
  * @package Customizr
  * @since Customizr 3.2.6
  */
  function czr_fn_set_thumb_early_options() {
    //Set thumb size depending on the customizer thumbnail position options (since 3.2.0)
    add_filter ( 'tc_thumb_size_name'     , array( $this , 'czr_fn_set_thumb_size') );
  }



  /**
  * Set __loop hooks and various filters based on customizer options
  * hook : wp_head
  *
  * @package Customizr
  * @since Customizr 3.2.0
  */
  function czr_fn_set_post_list_hooks() {
    if ( ! $this -> czr_fn_post_list_controller() )
      return;
    //displays the article with filtered layout : content + thumbnail
    add_action ( '__loop'                    , array( $this , 'czr_fn_prepare_section_view') );

    //ARTICLE CONTAINER CSS CLASSES
    add_filter( 'tc_article_container_class' , array( $this, 'czr_fn_article_container_set_classes' ) );

    //based on customizer user options
    add_filter( 'tc_post_list_layout'        , array( $this , 'czr_fn_set_post_list_layout') );
    add_filter( 'post_class'                 , array( $this , 'czr_fn_set_content_class') );
    add_filter( 'excerpt_length'             , array( $this , 'czr_fn_set_excerpt_length') , 999 );
    add_filter( 'post_class'                 , array( $this , 'czr_fn_add_thumb_shape_name') );

    //add current context to the body class
    add_filter( 'body_class'                 , array( $this , 'czr_fn_add_post_list_context') );
    //Set thumb shape with customizer options (since 3.2.0)
    add_filter( 'tc_post_thumb_wrapper'      , array( $this , 'czr_fn_set_thumb_shape'), 10 , 2 );

    add_filter( 'tc_the_content'             , array( $this , 'czr_fn_add_support_for_shortcode_special_chars') );

    // => filter the thumbnail inline style tc_post_thumb_inline_style and replace width:auto by width:100%
    // 3 args = $style, $_width, $_height
    add_filter( 'tc_post_thumb_inline_style'  , array( $this , 'czr_fn_change_thumbnail_inline_css_width'), 20, 3 );
  }


  /***************************
  * POST LIST MODEL
  ****************************/
  /**
  * Prepare default posts lists view
  * hook : __loop
  * inside loop
  * @package Customizr
  * @since Customizr 3.0.10
  */
  function czr_fn_prepare_section_view() {
    global $post;
    if ( ! isset( $post ) || empty( $post ) || ! apply_filters( 'tc_show_post_in_post_list', $this -> czr_fn_post_list_controller() , $post ) )
      return;

    //get the filtered post list layout
    $_layout        = apply_filters( 'tc_post_list_layout', CZR_init::$instance -> post_list_layout );
    $_content_model = $this -> czr_fn_get_content_model( $_layout );
    $_thumb_model   = $this -> czr_fn_show_thumb() ? CZR_post_thumbnails::$instance -> czr_fn_get_thumbnail_model() : array();

    $this -> czr_fn_render_section_view( $_layout, $_content_model, $_thumb_model );
  }


  /**
  * Return the default post list model for the content
  * inside loop
  * @return array() "_layout" , "_show_thumb" , "_css_class"
  * @package Customizr
  * @since Customizr 3.3.2
  */
  private function czr_fn_get_content_model($_layout) {
    $_content      = '';
    if ( $this -> czr_fn_show_excerpt() )
      $_content = apply_filters( 'the_excerpt', get_the_excerpt() );
    else
      $_content = apply_filters( 'tc_the_content', get_the_content() );

    //what is determining the layout ? if no thumbnail then full width + filter's conditions
    $_layout_class = $this -> czr_fn_show_thumb() ? $_layout['content'] : 'span12';
    $_layout_class = implode( " " , apply_filters( 'tc_post_list_content_class', array($_layout_class) , $this -> czr_fn_show_thumb() , $_layout ) );

    //display an icon for div if there is no title
    $_icon_class    = in_array(get_post_format(), array(  'quote' , 'aside' , 'status' , 'link' )) ? apply_filters( 'tc_post_list_content_icon', 'format-icon' ) :'';

    return compact( "_layout_class" , "_icon_class" , "_content" );
  }




  /**
  * @return boolean whether excerpt instead of full content
  * @package Customizr
  * @since Customizr 3.3.2
  */
  private function czr_fn_show_excerpt() {
    //When do we show the post excerpt?
    //1) when set in options
    //2) + other filters conditions
    return (bool) apply_filters( 'tc_show_excerpt', 'full' != esc_attr( czr_fn_opt( 'tc_post_list_length' ) ) );
  }


  /**
  * @return boolean
  * @package Customizr
  * @since Customizr 3.3.2
  */
  private function czr_fn_show_thumb() {
    //when do we display the thumbnail ?
    //1) there must be a thumbnail
    //2) the excerpt option is not set to full
    //3) user settings in customizer
    //4) filter's conditions
    return apply_filters( 'tc_show_thumb', array_product(
        array(
          $this -> czr_fn_show_excerpt(),
          CZR_post_thumbnails::$instance -> czr_fn_has_thumb(),
          czr_fn_is_checked( 'tc_post_list_show_thumb' )
        )
      )
    );
  }


  /***************************
  * POST LIST VIEW
  ****************************/
  /**
  * Render each post list section view
  *
  * @package Customizr
  * @since Customizr 3.0.10
  */
  private function czr_fn_render_section_view( $_layout, $_content_model, $_thumb_model ) {
    global $wp_query;
    echo '<div class="grid__item">';
    //Renders the filtered layout for content + thumbnail
    if ( isset($_layout['alternate']) && $_layout['alternate'] ) {
      if ( 0 == $wp_query->current_post % 2 ) {
        $this -> czr_fn_render_content_view( $_content_model ) ;
        CZR_post_thumbnails::$instance -> czr_fn_render_thumb_view( $_thumb_model , $_layout['thumb'] );
      }
      else {
        CZR_post_thumbnails::$instance -> czr_fn_render_thumb_view( $_thumb_model , $_layout['thumb'] );
        $this -> czr_fn_render_content_view( $_content_model );
      }
    }
    else if ( isset($_layout['show_thumb_first']) && ! $_layout['show_thumb_first'] ) {
        $this -> czr_fn_render_content_view( $_content_model );
        CZR_post_thumbnails::$instance -> czr_fn_render_thumb_view( $_thumb_model , $_layout['thumb'] );
    }
    else {
      CZR_post_thumbnails::$instance -> czr_fn_render_thumb_view( $_thumb_model , $_layout['thumb'] );
      $this -> czr_fn_render_content_view( $_content_model );
    }

    //renders the hr separator after each article
    echo apply_filters( 'tc_post_list_separator', '<hr class="featurette-divider '.current_filter().'">' );
    echo '</div>';
  }



  /**
  * Displays the posts list content
  *
  * @package Customizr
  * @since Customizr 3.0
  */
  private function czr_fn_render_content_view( $_content_model ) {
    //extract "_layout_class" , "_icon_class" , "_content"
    extract($_content_model);
    $_sub_class = 'entry-summary';

    if ( in_array( get_post_format(), array( 'image' , 'gallery' ) ) )
    {
      $_sub_class = 'entry-content';
      $_content   = '<p class="format-icon"></p>';
    }
    elseif ( in_array( get_post_format(), array( 'quote', 'status', 'link', 'aside', 'video' ) ) )
    {
      $_sub_class = sprintf( 'entry-content %s' , $_icon_class );
      $_content   = sprintf( '%1$s%2$s',
        apply_filters( 'tc_the_content', get_the_content( __( 'Continue reading <span class="meta-nav">&rarr;</span>' , 'customizr' ) ) ),
        wp_link_pages( array(
          'before'  => '<div class="pagination pagination-centered">' . __( 'Pages:' , 'customizr' ),
          'after'   => '</div>',
          'echo'    => 0
          ) )
      );
    }

    ob_start();
    ?>
    <section class="tc-content <?php echo $_layout_class; ?>">
      <?php
        do_action( '__before_content' );

          printf('<section class="%1$s">%2$s</section>',
            $_sub_class,
            $_content
          );

        do_action( '__after_content' );
    ?>
    </section>
    <?php
    $_html = ob_get_contents();
    if ($_html) ob_end_clean();
    echo apply_filters( 'tc_post_list_content', $_html, $_content_model );
  }



  /******************************
  * SETTERS / HELPERS / CALLBACKS
  *******************************/
  /**
  * hook : tc_post_thumb_wrapper
  * ! 2 cases here : posts lists and single posts
  *
  * @package Customizr
  * @since Customizr 3.2.0
  */
  function czr_fn_set_thumb_shape( $thumb_wrapper, $thumb_img ) {
    $_shape = esc_attr( czr_fn_opt( 'tc_post_list_thumb_shape') );

    //1) check if shape is rounded, squared on rectangular
    if ( ! $_shape || false !== strpos($_shape, 'rounded') || false !== strpos($_shape, 'squared') )
      return $thumb_wrapper;

    $_position = esc_attr( czr_fn_opt( 'tc_post_list_thumb_position' ) );
    return sprintf('<div class="%3$s"><a class="tc-rectangular-thumb" href="%1$s">%2$s</a></div>',
          get_permalink( get_the_ID() ),
          $thumb_img,
          ( 'top' == $_position || 'bottom' == $_position ) ? '' : implode( " ", apply_filters( 'tc_thumb_wrapper_class', array('') ) )
    );
  }


  /**
  * hook : body_class
  * @return  array of classes
  *
  * @package Customizr
  * @since Customizr 3.3.2
  */
  function czr_fn_add_post_list_context( $_classes ) {
    return array_merge( $_classes , array( 'tc-post-list-context' ) );
  }


  /**
  * @return  bool
  * Controller of the posts list view
  *
  * @package Customizr
  * @since Customizr 3.2.0
  */
  public function czr_fn_post_list_controller() {
    global $wp_query;
    //must be archive or search result. Returns false if home is empty in options.
    return apply_filters( 'tc_post_list_controller',
      ! is_singular()
      && ! is_404()
      && 0 != $wp_query -> post_count
      && ! czr_fn__f( '__is_home_empty')
    );
  }


  /*
  * hook : tc_article_container_class
  */
  public function czr_fn_article_container_set_classes( $_classes ) {
    $_classes[] = 'grid-container'; //used by the pro infinite scroll
    return $_classes;
  }


  /**
  * Callback of filter post_class
  * @return  array() of classes
  * @package Customizr
  * @since Customizr 3.2.0
  */
  function czr_fn_add_thumb_shape_name( $_classes ) {
    return array_merge( $_classes , array(esc_attr( czr_fn_opt( 'tc_post_list_thumb_shape') ) ) );
  }


  /**
  * hook : excerpt_length hook
  * @return string
  * @package Customizr
  * @since Customizr 3.2.0
  */
  function czr_fn_set_excerpt_length( $length ) {
    $_custom = esc_attr( czr_fn_opt( 'tc_post_list_excerpt_length' ) );
    return ( false === $_custom || !is_numeric($_custom) ) ? $length : $_custom;
  }


  /**
  * hook : tc_post_list_layout
  * @return array() of layout data
  * @package Customizr
  * @since Customizr 3.2.0
  */
  function czr_fn_set_post_list_layout( $_layout ) {
    $_position                  = esc_attr( czr_fn_opt( 'tc_post_list_thumb_position' ) );
    //since 3.4.16 the alternate layout is not available when the position is top or bottom
    $_layout['alternate']        = ( 0 == czr_fn_opt( 'tc_post_list_thumb_alternate' ) || in_array( $_position, array( 'top', 'bottom') ) ) ? false : true;
    $_layout['show_thumb_first'] = ( 'left' == $_position || 'top' == $_position ) ? true : false;
    $_layout['content']          = ( 'left' == $_position || 'right' == $_position ) ? $_layout['content'] : 'span12';
    $_layout['thumb']            = ( 'top' == $_position || 'bottom' == $_position ) ? 'span12' : $_layout['thumb'];
    return $_layout;
  }


  /**
  * hook : WP filter post_class
  * @return array() of classes
  * @package Customizr
  * @since Customizr 3.2.0
  */
  function czr_fn_set_content_class( $_classes ) {
    $_position                  = esc_attr( czr_fn_opt( 'tc_post_list_thumb_position' ) );
    return array_merge( $_classes , array( "thumb-position-{$_position}") );
  }


  /**
  * hook tc_post_thumb_inline_style (declared in CZR_post_thumbnails)
  * Replace default widht:auto by width:100%
  * @param array of args passed by apply_filters_ref_array method
  * @return  string
  *
  * @package Customizr
  * @since Customizr 3.2.6
  */
  function czr_fn_change_thumbnail_inline_css_width( $_style,  $image, $_filtered_thumb_size) {
    //conditions :
    //note : handled with javascript if tc_center_img option enabled
    $_bool = array_product(
      array(
        ! esc_attr( czr_fn_opt( 'tc_center_img') ),
        false != $image,
        ! empty($image),
        isset($_filtered_thumb_size['width']),
        isset($_filtered_thumb_size['height'])
      )
    );
    if ( ! $_bool )
      return $_style;

    $_width     = $_filtered_thumb_size['width'];
    $_height    = $_filtered_thumb_size['height'];
    $_shape     = esc_attr( czr_fn_opt( 'tc_post_list_thumb_shape') );
    $_is_rectangular = ! $_shape || false !== strpos($_shape, 'rounded') || false !== strpos($_shape, 'squared') ? false : true;
    if ( ! is_single() && ! $_is_rectangular )
      return $_style;

    return sprintf('min-width:%1$spx;min-height:%2$spx;max-width: none;width:100%%;max-height: none;', $_width, $_height );
  }


  /**
  * hook : tc_user_options_style
  * @return css string
  *
  * @package Customizr
  * @since Customizr 3.2.6
  */
  function czr_fn_write_thumbnail_inline_css( $_css ) {
    if ( ! $this -> czr_fn_post_list_controller() )
      return $_css;
    $_list_thumb_height     = esc_attr( czr_fn_opt( 'tc_post_list_thumb_height' ) );
    $_list_thumb_height     = (! $_list_thumb_height || ! is_numeric($_list_thumb_height) ) ? 250 : $_list_thumb_height;

    return sprintf("%s\n%s",
      $_css,
      ".tc-rectangular-thumb {
        max-height: {$_list_thumb_height}px;
        height :{$_list_thumb_height}px
      }\n"
    );
  }


  /**
  * hook : tc_thumb_size_name (declared in CZR_post_thumbnails)
  *
  * @package Customizr
  * @since Customizr 3.2.0
  */
  function czr_fn_set_thumb_size( $_default_size ) {
    $_shape = esc_attr( czr_fn_opt( 'tc_post_list_thumb_shape') );
    if ( ! $_shape || false !== strpos($_shape, 'rounded') || false !== strpos($_shape, 'squared') )
      return $_default_size;

    $_position                  = esc_attr( czr_fn_opt( 'tc_post_list_thumb_position' ) );
    return ( 'top' == $_position || 'bottom' == $_position ) ? 'tc_rectangular_size' : $_default_size;
  }


  /**
  * hook : tc_the_content
  * Applies tc_the_content filter to the passed string
  *
  * @param string
  * @return  string
  *
  * @package Customizr
  * @since Customizr 3.3+
  */
  function czr_fn_add_support_for_shortcode_special_chars( $_content ) {
    return str_replace( ']]>', ']]&gt;', apply_filters( 'the_content', $_content ) );
  }


}//end of class
endif;

?>