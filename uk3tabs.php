<?php defined('_JEXEC') or die;
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.uk3tabs
 * @copyright   Copyright (C) Aleksey A. Morozov. All rights reserved.
 * @license     GNU General Public License version 3 or later; see http://www.gnu.org/licenses/gpl-3.0.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Filter\OutputFilter;

class PlgContentUk3tabs extends CMSPlugin
{
    public function onContentPrepare($context, &$article, &$params, $page = 0)
    {
        if ($context == 'com_finder.indexer' || !preg_match('/{tab\s(.*)}/s', $article->text)) {
            return false;
        }

        $vars = [
            'tabs_class', 'title_class', 'content_class', 'position', 'align',
            'swiping', 'media',
        ];

        foreach ($vars as $var) {
            $$var = $this->params->get($var);
        }

        $layout = Path::clean(PluginHelper::getLayoutPath('content', 'uk3tabs'));
        $layout = pathinfo($layout, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . pathinfo($layout, PATHINFO_FILENAME);

        $tabs_class = trim($tabs_class) ? ' ' . trim($tabs_class) : '';
        $tabs_class = $position !== 'top' ? $tabs_class . ' uk-tab-' . $position : $tabs_class;
        switch ($align) {
            case 'right':
                $tabs_class .= ' uk-flex-right';
                break;
            case 'center':
                $tabs_class .= ' uk-flex-center';
                break;
            case 'justify':
                $tabs_class .= ' uk-flex-between';
                break;
            case 'width':
                if ($position == 'top' || $position == 'bottom') $tabs_class .= ' uk-child-width-expand';
                break;
            case 'left':
            default:;
        }

        $title_class = trim($title_class) ? ' ' . trim($title_class) : '';
        $content_class_common = trim($content_class) ? ' ' . trim($content_class) : '';

        $tabs = [];
        $matches = [];

        if (preg_match_all('/{tab\s(.*)}{tab\s(.*)}|{tab\s(.*)}|{\/tab}/', $article->text, $matches, PREG_PATTERN_ORDER) > 0) {
            $article->text = preg_replace('|<[^>]+>{tab\s(.*)}</[^>]+>|U', '{tab \\1}', $article->text);
            $article->text = preg_replace('|<(.*)>{tab\s(.*)}|U', '{tab \\2}<\\1>', $article->text);
            $article->text = preg_replace('|{tab\s(.*)}</(.*)>|U', '</\\2>{tab \\1}', $article->text);
            $article->text = preg_replace('|<[^>]+>{/tab}</[^>]+>|U', '{/tab}', $article->text);
            $article->text = preg_replace('|<(.*)>{/tab}|U', '{/tab}<\\1>', $article->text);
            $article->text = preg_replace('|{/tab}</(.*)>|U', '</\\1>{/tab}', $article->text);
            $step = 1;
            foreach ($matches[0] as $match) {
                if ($step == 1 && $match != '{/tab}') {
                    $tabs[] = 1;
                    $step = 2;
                } elseif ($match == '{/tab}') {
                    $tabs[] = 3;
                    $step = 1;
                } elseif (preg_match('/{tab\s(.*)}{tab\s(.*)}/', $match)) {
                    $tabs[] = 2;
                    $tabs[] = 1;
                    $step = 2;
                } else {
                    $tabs[] = 2;
                }
            }
        }

        if ($matches) {
            Factory::getDocument()->addScript('/plugins/content/uk3tabs/assets/plguktabs.js');
            $tabsCount = 0;
            $tabsFirst = true;
            foreach ($matches[0] as $match) {
                if ($tabsFirst) {
                    $id = 'plg_uk3tabs_' . uniqid();
                    $tabs_titles = '';
                    $tabs_params = [];
                    $tabs_params[] = 'connect:#' . $id;
                    if (!(bool)$swiping) {
                        $tabs_params[] = 'swiping:false';
                    }
                    if (trim($media)) {
                        $tabs_params[] = 'media:' . trim($media);
                    }
                    $tabs_params = $tabs_params ? '="' . implode(';', $tabs_params) . '"' : '';
                }

                if ($tabs[$tabsCount] == 1) {

                    $title = preg_replace('|{tab\s(.*)}|U', '\\1', $match);
                    $title = strip_tags($title);
                    $tab_id = 'tab-' . OutputFilter::stringURLSafe($title);
                    $tab_active = ' class="uk-active"';
                    ob_start();
                    include $layout . '_title_start.php';
                    include $layout . '_title_li.php';
                    $tabs_titles = ob_get_clean();

                    $content_class = $content_class_common . ' uk-active';
                    ob_start();
                    include $layout . '_content_start.php';
                    include $layout . '_content_li_start.php';
                    $tabs_content = ($position === 'top' || $position === 'left' ? "titles_$id" : '') . ob_get_clean();

                    $article->text = preg_replace('/' . preg_quote($match) . '/U', $tabs_content, $article->text, 1);

                    $tabsFirst = false;

                } elseif ($tabs[$tabsCount] == 2) {

                    $title = preg_replace('|{tab\s(.*)}|U', '\\1', $match);
                    $title = strip_tags($title);
                    $tab_id = 'tab-' . OutputFilter::stringURLSafe($title);
                    $tab_active = '';
                    ob_start();
                    include $layout . '_title_li.php';
                    $tabs_titles .= ob_get_clean();

                    $content_class = $content_class_common;
                    ob_start();
                    include $layout . '_content_li_end.php';
                    include $layout . '_content_li_start.php';
                    $tabs_content = ob_get_clean();

                    $article->text = preg_replace('/' . preg_quote($match) . '/U', $tabs_content, $article->text, 1);

                    $tabsFirst = false;

                } elseif ($tabs[$tabsCount] == 3) {

                    ob_start();
                    include $layout . '_title_end.php';
                    $tabs_titles .= ob_get_clean();

                    ob_start();
                    include $layout . '_content_li_end.php';
                    include $layout . '_content_end.php';
                    $tabs_content = ob_get_clean() . ($position === 'bottom' || $position === 'right' ? "titles_$id" : '');

                    $article->text = preg_replace('|{/tab}|U', $tabs_content, $article->text, 1);
                    $article->text = str_replace("titles_$id", $tabs_titles, $article->text);

                    $tabsFirst = true;

                }
                $tabsCount++;
            }
        }
    }
}
