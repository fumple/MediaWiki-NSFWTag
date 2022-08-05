<?php
/**
 * NSFWTag extension main file, handles hooks
 * 
 * @author  Fumple <me@fumple.pl>
 * @license ../LICENSE GPL-3.0-only
 * @link    https://github.com/fumple/MediaWiki-NSFWTag
 */

namespace MediaWiki\Extension\NSFWTag;

use MediaWiki\User\UserOptionsManager;
use \EditPage;
use \Parser;
use \PPFrame;
use \User;

class Hooks implements
    \MediaWiki\Preferences\Hook\GetPreferencesHook, 
    \MediaWiki\Hook\BeforePageDisplayHook,
    \MediaWiki\Hook\EditPageGetCheckboxesDefinitionHook,
    \MediaWiki\Hook\OutputPageParserOutputHook,
    \MediaWiki\Hook\ParserFirstCallInitHook, 
    \MediaWiki\Hook\ParserOptionsRegisterHook
{
    /**
     * @var UserOptionsManager
     */
    private $_userOptionsManager;

    /**
     * @param UserOptionsManager $userOptionsManager User Options Manager provided by MediaWiki
     */
    public function __construct( UserOptionsManager $userOptionsManager )
    {
        $this->_userOptionsManager = $userOptionsManager;
    }

    /**
     * GetPreferencesHook
     * https://doc.wikimedia.org/mediawiki-core/master/php/interfaceMediaWiki_1_1Preferences_1_1Hook_1_1GetPreferencesHook.html#ab914379d09f36c53bfc2494394a7a28f
     * 
     * Add two preferences
     * 
     * @param User  $user
     * @param array $preferences
     */
    public function onGetPreferences( $user, &$preferences )
    {
        // Add a checkbox
        $preferences['nsfwtag-pref'] = [
        'type' => 'toggle',
        'label-message' => 'nsfwtag-pref',
        'section' => 'rendering',
        ];
        $preferences['nsfwtag-prefeditor'] = [
        'type' => 'toggle',
        'label-message' => 'nsfwtag-prefeditor',
        'section' => 'editing/editor',
        ];
    }


    /**
     * ParserFirstCallInitHook
     * https://doc.wikimedia.org/mediawiki-core/master/php/interfaceMediaWiki_1_1Hook_1_1ParserFirstCallInitHook.html#a2418eacc144d4cb61996c64e10c7a7a4
     * 
     * Register the tags and function
     * 
     * @param Parser $parser
     */
    public function onParserFirstCallInit( $parser )
    {
        $parser->setFunctionHook('nsfw',  [ $this, 'renderFunction'  ]);
        $parser->setHook('nsfw', [ $this, 'renderTagNSFW' ]);
        $parser->setHook('sfw',  [ $this, 'renderTagSFW'  ]);
    }

    /**
     * Render <nsfw> tag
     * Passes the job onto self::renderTag(true)
     * 
     * @return string
     */
    public function renderTagNSFW( $input, array $args, Parser $parser, PPFrame $frame )
    {
        // Call renderTag()
        return self::renderTag(true, $input, $args, $parser, $frame);
    }

    /**
     * Render <sfw> tag
     * Passes the job onto self::renderTag(false)
     * 
     * @return string
     */
    public function renderTagSFW( $input, array $args, Parser $parser, PPFrame $frame )
    {
        // Call renderTag()
        return self::renderTag(false, $input, $args, $parser, $frame);
    }

    /**
     * Render tag if $isNSFWTag matches the preference
     * 
     * @return string
     */
    public function renderTag( $isNSFWTag, $input, array $args, Parser $parser, PPFrame $frame )
    {
        $preference = Util::getNSFWPreference($this->_userOptionsManager, $parser);

        // Check if NSFW is enabled, and compare it to $isNSFWTag
        if ($preference == $isNSFWTag) {
            // Return the input provided, but wrap it in a <span> with a class to allow the wiki to style the content
            $class = $isNSFWTag ? 'nsfwtag' : 'sfwtag';
            // Parse the input for wikitext before outputing it
            $parsedInput = $parser->recursiveTagParse($input, $frame);
            return "<span class=\"$class\">".$parsedInput."</span>";
        } else {
            // Return a blank string, as the user should not see this part
            return "";
        }
    }

    /**
     * Render function based on preference
     * $param1 => $nsfwText
     * $param2 => $sfwText
     * 
     * @return string
     */
    public function renderFunction( Parser $parser, $nsfwText = '', $sfwText = '' )
    {
        $preference = Util::getNSFWPreference($this->_userOptionsManager, $parser);

        // Return the input provided, but wrap it in a <span> with a class to allow the wiki to style the content
        $class = $preference ? 'nsfwtag' : 'sfwtag';
        // Parse the input for wikitext before outputing it
        $parsedInput = $parser->recursiveTagParse($preference ? $nsfwText : $sfwText);
        return "<span class=\"$class\">".$parsedInput."</span>";
    }

    /**
     * ParserOptionsRegisterHook
     * https://doc.wikimedia.org/mediawiki-core/master/php/interfaceMediaWiki_1_1Hook_1_1ParserOptionsRegisterHook.html#ad2bbcafd3babaffdaa7dad204bb50b0f
     * 
     * Add extnsfwtag for nsfw preference storing in cache
     * The fact that this causes two page versions to be stored in cache isn't good, but not every page is going to use this, so it should be fine
     */
    public function onParserOptionsRegister( &$defaults, &$inCacheKey, &$lazyLoad )
    {
        // register parser option
        $defaults['extnsfwtag'] = '';
        $inCacheKey['extnsfwtag'] = true;
    }

    /**
     * OutputPageParserOutputHook
     * https://doc.wikimedia.org/mediawiki-core/master/php/interfaceMediaWiki_1_1Hook_1_1OutputPageParserOutputHook.html#a381561eea2ec6cd1cc04a8b0f7d073e6
     * 
     * Pass the extnsfwtag option to OutputPage as extnsfwtag-used if it's not null
     * 
     * @param \OutputPage   $out
     * @param \ParserOutput $parserOutput
     */
    public function onOutputPageParserOutput( $out, $parserOutput ): void
    {
        // if extnsfwtag was used, set a property on OutputPage, so onBeforePageDisplay can see it
        if ($parserOutput->getExtensionData('extnsfwtag') != null) {
            $out->setProperty('extnsfwtag-used', true);
        }
    }

    /**
     * BeforePageDisplayHook
     * https://doc.wikimedia.org/mediawiki-core/master/php/interfaceMediaWiki_1_1Hook_1_1BeforePageDisplayHook.html#a71800060caff0d55c9dfed6483a3d58c
     * 
     * Add NSFWTag header and footer if nsfw/sfw tags were used
     * 
     * @param \OutputPage $out
     * @param \Skin       $skin
     */
    public function onBeforePageDisplay( $out, $skin ): void
    {
        // if used an nsfw or sfw tag, add header and footer
        if ($out->getProperty('extnsfwtag-used')) {
            $out->prependHtml(wfMessage('nsfwtag-header')->parseAsBlock());
            $out->addWikiMsg('nsfwtag-footer');
        }
    }

    /**
     * EditPageGetCheckboxesDefinitionHook
     * https://doc.wikimedia.org/mediawiki-core/master/php/interfaceMediaWiki_1_1Hook_1_1EditPageGetCheckboxesDefinitionHook.html#aefd954bb29633bf9e9617422f2a8affe
     * 
     * Add Show NSFW checkbox if user has it enabled
     * 
     * @param EditPage $editPage
     * @param array    $checkboxes
     */
    public function onEditPageGetCheckboxesDefinition( $editPage, &$checkboxes )
    {
        $context = $editPage->getContext();
        if ($this->_userOptionsManager->getBoolOption($context->getUser(), 'nsfwtag-prefeditor')) {
            $checkboxes['shownsfwcheckbox'] = [
            'id' => 'wpNSFWTagShow',
            'default' => $context->getRequest()->getCheck('shownsfwcheckbox'),
            'label-message' => 'nsfwtag-shownsfw-label',
            'title-message' => 'nsfwtag-shownsfw-title'
            ];
        }
    }
}