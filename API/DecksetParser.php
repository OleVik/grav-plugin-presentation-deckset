<?php
/**
 * Presentation Plugin, Parser API
 *
 * PHP version 7
 *
 * @category   API
 * @package    Grav\Plugin\PresentationPlugin
 * @subpackage Grav\Plugin\PresentationPlugin\API
 * @author     Ole Vik <git@olevik.net>
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 * @link       https://github.com/OleVik/grav-plugin-presentation
 */

namespace Grav\Plugin\PresentationPlugin\API;

use Grav\Common\Utils;

/**
 * Parser API
 *
 * Parser API for parsing content
 *
 * @category Extensions
 * @package  Grav\Plugin\PresentationPlugin\API
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-presentation
 */
class DecksetParser extends Parser implements ParserInterface
{
    /**
     * Instantiate Parser API
     *
     * @param Styles $styles Styles API
     */
    public function __construct($styles)
    {
        $this->styles = $styles;
    }

    /**
     * Regular expressions
     */
    const REGEX_IMG = "/(<img(?:(\s*(class)\s*=\s*\x22([^\x22]+)\x22*)+|[^>]+?)*>)/";
    const REGEX_IMG_P = "/<p>\s*?(<a .*<img.*<\/a>|<img.*)?\s*<\/p>/";
    const REGEX_IMG_TITLE = "/<img[^>]*?title[ ]*=[ ]*[\"](.*?)[\"][^>]*?>/";
    const REGEX_IMG_WRAPPING_LINK = '/\[(?\'image\'\!.*)\]\((?\'url\'https?:\/\/.*)\)/';
    const REGEX_FRAGMENT_SHORTCODE = '~\[fragment=*([a-zA-Z-]*)\](.*)\[\/fragment\]~im';
    const REGEX_SHORTCODES = '/\[\.(?<property>[a-zA-Z0-9_-]+)?:(?<value>.*)\]/mi';
    const REGEX_WORDS = '/[a-zA-Z0-9_\- ]*/m';
    const REGEX_BRACKET_VALUE = '/(?![a-zA-Z0-9_\- ])\((?<property>.*)\)/m';

    /**
     * Parse shortcodes
     *
     * @param string $content Markdown content in Page
     * @param string $id      Slide id-attribute
     *
     * @return array Processed contents and properties
     */
    public function interpretShortcodes(string $content, string $id)
    {
        $return = array();
        preg_match_all(
            self::REGEX_SHORTCODES,
            $content,
            $matches,
            PREG_SET_ORDER,
            0
        );
        if (!empty($matches)) {
            foreach ($matches as $match) {
                $property = $match['property'];
                $value = $match['value'];
                $content = str_replace($match[0], '', $content);
                if ($property == 'text') {
                    $css = self::collapseToCssString(self::genericShortcode($value));
                    $this->styles->setStyle($id, "{\n$css\n}");
                } elseif ($property == 'text-emphasis') {
                    $css = self::collapseToCssString(self::genericShortcode($value));
                    $this->styles->setStyle($id, "{\n$css\n}", 'i');
                    $this->styles->setStyle($id, "{\n$css\n}", 'em');
                } elseif ($property == 'text-strong') {
                    $css = self::collapseToCssString(self::genericShortcode($value));
                    $this->styles->setStyle($id, "{\n$css\n}", 'b');
                    $this->styles->setStyle($id, "{\n$css\n}", 'strong');
                } elseif ($property == 'header') {
                    $css = self::collapseToCssString(self::genericShortcode($value));
                    $this->styles->setStyle($id, "{\n$css\n}", 'h1,h2,h3,h4,h5,h6');
                } elseif ($property == 'header-emphasis') {
                    $css = self::collapseToCssString(self::genericShortcode($value));
                    $this->styles->setStyle($id, "{\n$css\n}", 'h1 i,h2 i,h3 i,h4 i,h5 i,h6 i');
                    $this->styles->setStyle($id, "{\n$css\n}", 'h1 em,h2 em,h3 em,h4 em,h5 em,h6 em');
                } elseif ($property == 'header-strong') {
                    $css = self::collapseToCssString(self::genericShortcode($value));
                    $this->styles->setStyle($id, "{\n$css\n}", 'h1 b,h2 b,h3 b,h4 b,h5 b,h6 b');
                    $this->styles->setStyle($id, "{\n$css\n}", 'h1 strong,h2 strong,h3 strong,h4 strong,h5 strong,h6 strong');
                } elseif ($property == 'footer-style') {
                    $css = self::collapseToCssString(self::genericShortcode($value));
                    $this->styles->setStyle($id, "{\n$css\n}", 'footer');
                } elseif ($property == 'background-color') {
                    $this->styles->setStyle($id, "{\n$property:$value;\n}");
                } elseif ($property == 'list') {
                    $css = self::collapseToCssString(self::listShortcode($value));
                    $this->styles->setStyle($id, "{\n$css\n}", 'ul,ol');
                } elseif ($property == 'code') {
                    $css = self::collapseToCssString(self::genericShortcode($value));
                    $this->styles->setStyle($id, "{\n$css\n}", 'code,pre');
                } elseif ($property == 'quote') {
                    $css = self::collapseToCssString(self::genericShortcode($value));
                    $this->styles->setStyle($id, "{\n$css\n}", 'blockquote');
                }
            }
        }
        return ['content' => $content, 'props' => $return];
    }

    /**
     * Parse Deckset generic shortcodes
     *
     * @param string $content Markdown content in Page
     *
     * @return array Processed contents and properties
     */
    public static function genericShortcode(string $content)
    {
        $return = array();
        $pieces = explode(',', $content);
        foreach ($pieces as $piece) {
            $piece = trim($piece);
            if (Utils::startsWith($piece, '#')) {
                $return['color'] = $piece;
            } elseif (Utils::startsWith($piece, 'alignment')) {
                preg_match(self::REGEX_BRACKET_VALUE, $piece, $matches);
                $return['text-align'] = $matches['property'];
            } elseif (Utils::startsWith($piece, 'line-height')) {
                preg_match(self::REGEX_BRACKET_VALUE, $piece, $matches);
                $return['line-height'] = $matches['property'];
            } elseif (Utils::startsWith($piece, 'text-scale')) {
                preg_match(self::REGEX_BRACKET_VALUE, $piece, $matches);
                $scale = (float) $matches['property'];
                $return['font-size'] = (16 * $scale) . 'px';
            } elseif (preg_match(self::REGEX_WORDS, $piece)) {
                $return['font-family'] = $piece;
            }
        }
        return $return;
    }

    /**
     * Parse Deckset list shortcodes
     *
     * @param string $content Markdown content in Page
     *
     * @return array Processed contents and properties
     */
    public static function listShortcode(string $content)
    {
        $return = array();
        $pieces = explode(',', $content);
        foreach ($pieces as $piece) {
            $piece = trim($piece);
            if (Utils::startsWith($piece, '#')) {
                $return['color'] = $piece;
            } elseif (Utils::startsWith($piece, 'alignment')) {
                preg_match(self::REGEX_BRACKET_VALUE, $piece, $matches);
                $return['text-align'] = $matches['property'];
            } elseif (Utils::startsWith($piece, 'bullet-character')) {
                preg_match(self::REGEX_BRACKET_VALUE, $piece, $matches);
                $return['list-style-type'] = $matches['property'];
            }
        }
        return $return;
    }

    /**
     * Convert an array of properties and values to a CSS string
     *
     * @param array $array Array of strings to process
     *
     * @return string Concatenated properties and values
     */
    public static function collapseToCssString(array $array)
    {
        $return = '';
        foreach ($array as $property => $value) {
            $return .= $property . ': ' . $value . ';';
        }
        return $return;
    }
}
