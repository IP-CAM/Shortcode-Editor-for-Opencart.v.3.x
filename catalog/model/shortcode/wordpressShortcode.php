<?php
/**
 * Search only inside HTML elements for shortcodes and process them.
 *
 * Any [ or ] characters remaining inside elements will be HTML encoded
 * to prevent interference with shortcodes that are outside the elements.
 * Assumes $content processed by KSES already.  Users with unfiltered_html
 * capability may get unexpected output if angle braces are nested in tags.
 *
 * @since 4.2.3
 *
 * @param string $content Content to search for shortcodes
 * @param bool $ignore_html When true, all square braces inside elements will be encoded.
 * @param array $tagnames List of shortcodes to find.
 * @return string Content with shortcodes filtered out.
 */
function do_shortcodes_in_html_tags( $content, $ignore_html, $tagnames,$shortcode_tags ) {
    // Normalize entities in unfiltered HTML before adding placeholders.
    $trans = array( '&#91;' => '&#091;', '&#93;' => '&#093;' );
    $content = strtr( $content, $trans );
    $trans = array( '[' => '&#91;', ']' => '&#93;' );
    $pattern = get_shortcode_regex( $tagnames ,$shortcode_tags);
    preg_split( get_html_split_regex(), $input, -1, PREG_SPLIT_DELIM_CAPTURE );
    $textarr = wp_html_split( $content );
    foreach ( $textarr as &$element ) {
        if ( '' == $element || '<' !== $element[0] ) {
            continue;
        }
        $noopen = false === strpos( $element, '[' );
        $noclose = false === strpos( $element, ']' );
        if ( $noopen || $noclose ) {
            // This element does not contain shortcodes.
            if ( $noopen xor $noclose ) {
                // Need to encode stray [ or ] chars.
                $element = strtr( $element, $trans );
            }
            continue;
        }
        if ( $ignore_html || '<!--' === substr( $element, 0, 4 ) || '<![CDATA[' === substr( $element, 0, 9 ) ) {
            // Encode all [ and ] chars.
            $element = strtr( $element, $trans );
            continue;
        }
        $attributes = wp_kses_attr_parse( $element );
        if ( false === $attributes ) {
            // Some plugins are doing things like [name] <[email]>.
            if ( 1 === preg_match( '%^<\s*\[\[?[^\[\]]+\]%', $element ) ) {
                $element = preg_replace_callback( "/$pattern/", 'do_shortcode_tag', $element );
            }
            // Looks like we found some crazy unfiltered HTML.  Skipping it for sanity.
            $element = strtr( $element, $trans );
            continue;
        }
        // Get element name
        $front = array_shift( $attributes );
        $back = array_pop( $attributes );
        $matches = array();
        preg_match('%[a-zA-Z0-9]+%', $front, $matches);
        $elname = $matches[0];
        // Look for shortcodes in each attribute separately.
        foreach ( $attributes as &$attr ) {
            $open = strpos( $attr, '[' );
            $close = strpos( $attr, ']' );
            if ( false === $open || false === $close ) {
                continue; // Go to next attribute.  Square braces will be escaped at end of loop.
            }
            $double = strpos( $attr, '"' );
            $single = strpos( $attr, "'" );
            if ( ( false === $single || $open < $single ) && ( false === $double || $open < $double ) ) {
                // $attr like '[shortcode]' or 'name = [shortcode]' implies unfiltered_html.
                // In this specific situation we assume KSES did not run because the input
                // was written by an administrator, so we should avoid changing the output
                // and we do not need to run KSES here.
                $attr = preg_replace_callback( "/$pattern/", 'do_shortcode_tag', $attr );
            } else {
                // $attr like 'name = "[shortcode]"' or "name = '[shortcode]'"
                // We do not know if $content was unfiltered. Assume KSES ran before shortcodes.
                $count = 0;
                $new_attr = preg_replace_callback( "/$pattern/", 'do_shortcode_tag', $attr, -1, $count );
                if ( $count > 0 ) {
                    // Sanitize the shortcode output using KSES.
                    $new_attr = wp_kses_one_attr( $new_attr, $elname );
                    if ( '' !== trim( $new_attr ) ) {
                        // The shortcode is safe to use now.
                        $attr = $new_attr;
                    }
                }
            }
        }
        $element = $front . implode( '', $attributes ) . $back;
        // Now encode any remaining [ or ] chars.
        $element = strtr( $element, $trans );
    }
    $content = implode( '', $textarr );
    return $content;
}
/**
 * Retrieve the shortcode regular expression for searching.
 *
 * The regular expression combines the shortcode tags in the regular expression
 * in a regex class.
 *
 * The regular expression contains 6 different sub matches to help with parsing.
 *
 * 1 - An extra [ to allow for escaping shortcodes with double [[]]
 * 2 - The shortcode name
 * 3 - The shortcode argument list
 * 4 - The self closing /
 * 5 - The content of a shortcode when it wraps some content.
 * 6 - An extra ] to allow for escaping shortcodes with double [[]]
 *
 * @since 2.5.0
 * @since 4.4.0 Added the `$tagnames` parameter.
 *
 * @global array $shortcode_tags
 *
 * @param array $tagnames Optional. List of shortcodes to find. Defaults to all registered shortcodes.
 * @return string The shortcode search regular expression
 */
function get_shortcode_regex( $tagnames = null,$shortcode_tags ) {
    if ( empty( $tagnames ) ) {
        $tagnames = array_keys( $shortcode_tags );
    }
    $tagregexp = join( '|', array_map('preg_quote', $tagnames) );
    // WARNING! Do not change this regex without changing do_shortcode_tag() and strip_shortcode_tag()
    // Also, see shortcode_unautop() and shortcode.js.
    return
        '\\['                              // Opening bracket
        . '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
        . "($tagregexp)"                     // 2: Shortcode name
        . '(?![\\w-])'                       // Not followed by word character or hyphen
        . '('                                // 3: Unroll the loop: Inside the opening shortcode tag
        .     '[^\\]\\/]*'                   // Not a closing bracket or forward slash
        .     '(?:'
        .         '\\/(?!\\])'               // A forward slash not followed by a closing bracket
        .         '[^\\]\\/]*'               // Not a closing bracket or forward slash
        .     ')*?'
        . ')'
        . '(?:'
        .     '(\\/)'                        // 4: Self closing tag ...
        .     '\\]'                          // ... and closing bracket
        . '|'
        .     '\\]'                          // Closing bracket
        .     '(?:'
        .         '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
        .             '[^\\[]*+'             // Not an opening bracket
        .             '(?:'
        .                 '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
        .                 '[^\\[]*+'         // Not an opening bracket
        .             ')*+'
        .         ')'
        .         '\\[\\/\\2\\]'             // Closing shortcode tag
        .     ')?'
        . ')'
        . '(\\]?)';                          // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
}
/**
 * Retrieve the regular expression for an HTML element.
 *
 * @since 4.4.0
 *
 * @staticvar string $regex
 *
 * @return string The regular expression
 */
function get_html_split_regex() {
    static $regex;
    if ( ! isset( $regex ) ) {
        $comments =
            '!'           // Start of comment, after the <.
            . '(?:'         // Unroll the loop: Consume everything until --> is found.
            .     '-(?!->)' // Dash not followed by end of comment.
            .     '[^\-]*+' // Consume non-dashes.
            . ')*+'         // Loop possessively.
            . '(?:-->)?';   // End of comment. If not found, match all input.

        $cdata =
            '!\[CDATA\['  // Start of comment, after the <.
            . '[^\]]*+'     // Consume non-].
            . '(?:'         // Unroll the loop: Consume everything until ]]> is found.
            .     '](?!]>)' // One ] not followed by end of comment.
            .     '[^\]]*+' // Consume non-].
            . ')*+'         // Loop possessively.
            . '(?:]]>)?';   // End of comment. If not found, match all input.

        $escaped =
            '(?='           // Is the element escaped?
            .    '!--'
            . '|'
            .    '!\[CDATA\['
            . ')'
            . '(?(?=!-)'      // If yes, which type?
            .     $comments
            . '|'
            .     $cdata
            . ')';

        $regex =
            '/('              // Capture the entire match.
            .     '<'           // Find start of element.
            .     '(?'          // Conditional expression follows.
            .         $escaped  // Find end of escaped element.
            .     '|'           // ... else ...
            .         '[^>]*>?' // Find end of normal element.
            .     ')'
            . ')/';
    }

    return $regex;
}
/**
 * Separate HTML elements and comments from the text.
 *
 * @since 4.2.4
 *
 * @param string $input The text which has to be formatted.
 * @return array The formatted text.
 */
function wp_html_split( $input ) {
    return preg_split( get_html_split_regex(), $input, -1, PREG_SPLIT_DELIM_CAPTURE );
}
/**
 * Retrieve all attributes from the shortcodes tag.
 *
 * The attributes list has the attribute name as the key and the value of the
 * attribute as the value in the key/value pair. This allows for easier
 * retrieval of the attributes, since all attributes have to be known.
 *
 * @since 2.5.0
 *
 * @param string $text
 * @return array|string List of attribute values.
 *                      Returns empty array if trim( $text ) == '""'.
 *                      Returns empty string if trim( $text ) == ''.
 *                      All other matches are checked for not empty().
 */
function shortcode_parse_atts($text) {
    $atts = array();
    $pattern = get_shortcode_atts_regex();
    $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
    if ( preg_match_all($pattern, $text, $match, PREG_SET_ORDER) ) {
        foreach ($match as $m) {
            if (!empty($m[1]))
                $atts[strtolower($m[1])] = stripcslashes($m[2]);
            elseif (!empty($m[3]))
                $atts[strtolower($m[3])] = stripcslashes($m[4]);
            elseif (!empty($m[5]))
                $atts[strtolower($m[5])] = stripcslashes($m[6]);
            elseif (isset($m[7]) && strlen($m[7]))
                $atts[] = stripcslashes($m[7]);
            elseif (isset($m[8]) && strlen($m[8]))
                $atts[] = stripcslashes($m[8]);
            elseif (isset($m[9]))
                $atts[] = stripcslashes($m[9]);
        }

        // Reject any unclosed HTML elements
        foreach( $atts as &$value ) {
            if ( false !== strpos( $value, '<' ) ) {
                if ( 1 !== preg_match( '/^[^<]*+(?:<[^>]*+>[^<]*+)*+$/', $value ) ) {
                    $value = '';
                }
            }
        }
    } else {
        $atts = ltrim($text);
    }
    return $atts;
}
/**
 * Retrieve the shortcode attributes regex.
 *
 * @since 4.4.0
 *
 * @return string The shortcode attribute regular expression
 */
function get_shortcode_atts_regex() {
    return '/([\w-]+)\s*=\s*"([^"]*)"(?:\s|$)|([\w-]+)\s*=\s*\'([^\']*)\'(?:\s|$)|([\w-]+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|\'([^\']*)\'(?:\s|$)|(\S+)(?:\s|$)/';
}
function unescape_invalid_shortcodes( $content ) {
    // Clean up entire string, avoids re-parsing HTML.
    $trans = array( '&#91;' => '[', '&#93;' => ']' );
    $content = strtr( $content, $trans );
    return $content;
}

