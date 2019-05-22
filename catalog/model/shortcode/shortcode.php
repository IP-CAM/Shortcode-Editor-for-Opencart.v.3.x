<?php
include __DIR__.'/kses.php';
include __DIR__.'/wordpressShortcode.php';
class ModelShortcodeShortcode extends Model
{
    /*
     * название шорткода => метод в catalog/controller/shortcode/shortcode.php
     */
    protected $shortcode_tags=[
           'productsliders' => 'productSliders'
    ];
    public function get($content,$ignore_html = false)
    {
        if (false === strpos($content, '[')) {
            return $content;
        }
        preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches);
        $tagnames = array_intersect(array_keys($this->shortcode_tags), $matches[1]);
        if (empty($tagnames)) {
            return $content;
        }
        $content =  do_shortcodes_in_html_tags( $content, $ignore_html, $tagnames ,$this->shortcode_tags);

        $pattern = get_shortcode_regex( $tagnames ,$this->shortcode_tags);
        $content = preg_replace_callback( "/$pattern/", array($this, 'do_shortcode_tag'), $content );
        $content = unescape_invalid_shortcodes( $content );
        return $content;
    }
    public function do_shortcode_tag($m){
        if ( $m[1] == '[' && $m[6] == ']' ) {
            return substr($m[0], 1, -1);
        }
        $tag = $m[2];
        $attr = shortcode_parse_atts( $m[3] );
        $this->load->model('catalog/product');
        $data['column_left'] = $this->load->controller('common/column_left');
        $content = isset( $m[5] ) ? $m[5] : null;
        $arg=[
            'attr'=>$attr,
            'content'=>$content,
            'tag'=>$tag
        ];
        return $m[1].$this->load->controller('shortcode/shortcode/'.$this->shortcode_tags[ $tag ],$arg).$m[6];
    }
}


