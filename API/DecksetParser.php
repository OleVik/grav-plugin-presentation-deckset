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

use Grav\Common\Grav;
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
     * @param Transport $transport Transport API
     */
    public function __construct($transport)
    {
        $this->transport = $transport;
    }

    /**
     * Regular expressions
     */
    const REGEX_SHORTCODES = '/\[\.(?<property>[a-zA-Z0-9_-]+)?:(?<value>.*)\]/mi';
    const REGEX_WORDS = '/^[a-zA-Z0-9_\- ]+/im';
    const REGEX_BRACKET_VALUE = '/(?![a-zA-Z0-9_\- ])\((?<property>.*)\)/m';
    const REGEX_IMG = '/<img\s*(?:alt="(?<alt>.*)")\s*(?:src="(?<src>.*)")\s*(?:>|\/>)/iU';
    const REGEX_IMGS = '/(?:<p>\s*?)?((<a .*<img.*<\/a>|<img.*\s*)*)(?:\s*<\/p>)?/mi';
    const REGEX_IMG_PERCENTAGE = '/^(?:\w*\s*)(?<percentage>\d*%$)/mU';
    const REGEX_VIDEO = '/(?:<video).*(?:alt="(?<alt>.*)").*(?:src="(?<src>.*)").*(?:<\/video>)/iUm';
    const REGEX_AUDIO = '/(?:<audio).*(?<controls>controls.*)\s*(?:alt="(?<alt>.*)").*(?:src="(?<src>.*)").*(?:<\/audio>)/i';

    /**
     * Parse shortcodes
     *
     * @param string $content Markdown content in Page
     * @param string $id      Slide id-attribute
     *
     * @return array Processed content and properties
     */
    public function interpretShortcodes(string $content, string $id)
    {
        if (preg_match(self::REGEX_IMG, $content)) {
            $processed = self::processImages($content);
            if (!empty($processed['style'])) {
                $css = self::collapseToCssString($processed['style']);
                $this->transport->setStyle($id, "{\n$css\n}");
            }
            if (!empty($processed['data'])) {
                foreach ($processed['data'] as $attribute => $value) {
                    $this->transport->setDataAttribute($id, $attribute, $value);
                }
            }
            $content = $processed['content'];
        }
        if (preg_match(self::REGEX_VIDEO, $content)) {
            $processed = self::processVideos($content);
            if (!empty($processed['style'])) {
                $css = self::collapseToCssString($processed['style']);
                $this->transport->setStyle($id, "{\n$css\n}");
            }
            if (!empty($processed['data'])) {
                foreach ($processed['data'] as $attribute => $value) {
                    $this->transport->setDataAttribute($id, $attribute, $value);
                }
            }
            $content = $processed['content'];
        }
        if (preg_match(self::REGEX_AUDIO, $content)) {
            $processed = self::processAudio($content);
            $content = $processed['content'];
        }
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
                    $this->transport->setStyle($id, "{\n$css\n}");
                } elseif ($property == 'text-emphasis') {
                    $css = self::collapseToCssString(self::genericShortcode($value));
                    $this->transport->setStyle($id, "{\n$css\n}", 'i');
                    $this->transport->setStyle($id, "{\n$css\n}", 'em');
                } elseif ($property == 'text-strong') {
                    $css = self::collapseToCssString(self::genericShortcode($value));
                    $this->transport->setStyle($id, "{\n$css\n}", 'b');
                    $this->transport->setStyle($id, "{\n$css\n}", 'strong');
                } elseif ($property == 'header') {
                    $css = self::collapseToCssString(self::genericShortcode($value));
                    $this->transport->setStyle($id, "{\n$css\n}", 'h1,h2,h3,h4,h5,h6');
                } elseif ($property == 'header-emphasis') {
                    $css = self::collapseToCssString(self::genericShortcode($value));
                    $this->transport->setStyle($id, "{\n$css\n}", 'h1 i,h2 i,h3 i,h4 i,h5 i,h6 i');
                    $this->transport->setStyle($id, "{\n$css\n}", 'h1 em,h2 em,h3 em,h4 em,h5 em,h6 em');
                } elseif ($property == 'header-strong') {
                    $css = self::collapseToCssString(self::genericShortcode($value));
                    $this->transport->setStyle($id, "{\n$css\n}", 'h1 b,h2 b,h3 b,h4 b,h5 b,h6 b');
                    $this->transport->setStyle($id, "{\n$css\n}", 'h1 strong,h2 strong,h3 strong,h4 strong,h5 strong,h6 strong');
                } elseif ($property == 'footer-style') {
                    $css = self::collapseToCssString(self::genericShortcode($value));
                    $this->transport->setStyle($id, "{\n$css\n}", 'footer');
                } elseif ($property == 'background-color') {
                    if ($this->transport->getDataAttribute($id, 'background-image') || $this->transport->getDataAttribute($id, 'background-video')) {
                        $this->transport->setDataAttribute($id, 'background-color', $value);
                    } else {
                        $this->transport->setStyle($id, "{\n$property:$value;\n}");
                    }
                } elseif ($property == 'list') {
                    $css = self::collapseToCssString(self::listShortcode($value));
                    $this->transport->setStyle($id, "{\n$css\n}", 'ul,ol');
                } elseif ($property == 'code') {
                    $css = self::collapseToCssString(self::genericShortcode($value));
                    $this->transport->setStyle($id, "{\n$css\n}", 'code,pre');
                } elseif ($property == 'quote') {
                    $css = self::collapseToCssString(self::genericShortcode($value));
                    $this->transport->setStyle($id, "{\n$css\n}", 'blockquote');
                } elseif ($property == 'build-lists') {
                    $content = self::buildListShortcode($content);
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
     * @return array Processed content and properties
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
     * @return array Processed content and properties
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
     * Parse Deckset build-list shortcode
     *
     * @param string $content Markdown content in Page
     *
     * @return array Processed content and properties
     */
    public static function buildListShortcode(string $content)
    {
        $content = str_replace('<li>', '<li class="fragment">', $content);
        return $content;
    }

    /**
     * Parse Deckset Media Background and Inline Images
     *
     * @param string $content Markdown content in Page
     *
     * @return array Processed content and properties
     */
    public static function processImages(string $content)
    {
        preg_match_all(self::REGEX_IMG, $content, $images, PREG_SET_ORDER, 0);
        $return = array();
        $return['content'] = $content;
        $count = count($images);
        if ($count == 1) {
            if ($images[0]['alt'] == '') {
                $return['data'] = [
                    'background-image' => $images[0]['src']
                ];
            } elseif ($images[0]['alt'] == 'fit') {
                $return['data'] = [
                    'background-image' => $images[0]['src'],
                    'background-size' => 'contain'
                ];
            } elseif (preg_match(self::REGEX_IMG_PERCENTAGE, $images[0]['alt'])) {
                preg_match_all(self::REGEX_IMG_PERCENTAGE, $images[0]['alt'], $alt, PREG_SET_ORDER, 0);
                $return['style'] = [
                    'background-image' => 'url(' . $images[0]['src'] . ')',
                    'background-size' => $alt[0]['percentage'],
                    'background-repeat' => 'no-repeat',
                    'background-position' => 'center',
                ];
            } elseif ($images[0]['alt'] == 'left') {
                $return['style'] = [
                    'background-image' => 'url(' . $images[0]['src'] . ')',
                    'background-size' => '50%',
                    'background-repeat' => 'no-repeat',
                    'background-position' => 'center left',
                    'padding-left' => '50% !important'
                ];
            } elseif ($images[0]['alt'] == 'right') {
                $return['style'] = [
                    'background-image' => 'url(' . $images[0]['src'] . ')',
                    'background-size' => '50%',
                    'background-repeat' => 'no-repeat',
                    'background-position' => 'center right',
                    'padding-right' => '50% !important'
                ];
            }
        } elseif ($count == 2) {
            $return['style'] = [
                'background-image' => 'url(' . $images[0]['src'] . '), url(' . $images[1]['src'] . ')',
                'background-repeat' => 'no-repeat',
                'background-position' => 'left, right',
                'background-size' => '50% auto, 50% auto'
            ];
        } elseif ($count >= 3) {
            $return['style'] = [
                'background-image' => 'url(' . $images[0]['src'] . '), url(' . $images[1]['src'] . '), url(' . $images[2]['src'] . ')',
                'background-repeat' => 'no-repeat',
                'background-position' => 'left, center, right',
                'background-size' => '33% auto, 33% auto, 33% auto'
            ];
        }
        if ($images[0]['alt'] != 'inline') {
            $return['content'] = preg_replace(self::REGEX_IMGS, '', $return['content']);
        }
        if (preg_match(self::REGEX_WORDS, $return['content']) && !Utils::contains($images[0]['alt'], 'original')) {
            $return['style']['background-color'] = 'rgba(48, 85, 165, 0.5)';
            $return['style']['background-blend-mode'] = 'screen';
        }
        return $return;
    }

    /**
     * Parse Deckset Media Video
     *
     * @param string $content Markdown content in Page
     *
     * @return array Processed content and properties
     */
    public static function processVideos(string $content)
    {
        preg_match_all(self::REGEX_VIDEO, $content, $videos, PREG_SET_ORDER, 0);
        $return = array();
        $return['content'] = $content;
        $count = count($videos);
        if ($count == 1) {
            if ($videos[0]['alt'] == '') {
                $return['data'] = [
                    'background-video' => $videos[0]['src'],
                    'background-size' => 'contain'
                ];
            }
        }
        if ($videos[0]['alt'] != 'inline') {
            $return['content'] = preg_replace(self::REGEX_VIDEO, '', $return['content']);
        }
        return $return;
    }

    /**
     * Parse Deckset Media Audio
     *
     * @param string $content Markdown content in Page
     *
     * @return array Processed content and properties
     */
    public static function processAudio(string $content)
    {
        preg_match_all(self::REGEX_AUDIO, $content, $audios, PREG_SET_ORDER, 0);
        $return = array();
        $return['content'] = $content;
        foreach ($audios as $audio) {
            $tag = $audio[0];
            if (Utils::contains($audio['alt'], 'autoplay')) {
                $tag = str_replace($audio['controls'], $audio['controls'] . ' autoplay ', $tag);
            }
            if (Utils::contains($audio['alt'], 'loop')) {
                $tag = str_replace($audio['controls'], $audio['controls'] . ' loop ', $tag);
            }
            if (Utils::contains($audio['alt'], 'muted')) {
                $tag = str_replace($audio['controls'], $audio['controls'] . ' muted ', $tag);
            }
            $tag = str_replace($audio['controls'], $audio['controls'] . '  controlsList="nodownload"', $tag);
            $return['content'] = str_replace($audio[0], $tag, $return['content']);
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
