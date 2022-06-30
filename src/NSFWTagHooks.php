<?php
namespace MediaWiki\Extension\NSFWTag;

use MediaWiki\User\UserOptionsManager;
use Parser;
use PPFrame;
use RequestContext;
use User;

class Extension implements
	\MediaWiki\Preferences\Hook\GetPreferencesHook, 
	\MediaWiki\Hook\BeforePageDisplayHook,
	\MediaWiki\Hook\EditPageGetCheckboxesDefinitionHook,
	\MediaWiki\Hook\OutputPageParserOutputHook,
	\MediaWiki\Hook\ParserFirstCallInitHook, 
	\MediaWiki\Hook\ParserOptionsRegisterHook
{
	private $userOptionsManager;

	public function __construct( UserOptionsManager $userOptionsManager ) {
		$this->userOptionsManager = $userOptionsManager;
	}

    /**
	 * @param User $user
	 * @param array $preferences
	 */
	public function onGetPreferences( $user, &$preferences ) {
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

	private function getNSFWPreference( Parser $parser ) {
		// Check whether the extnsfwtag option was already set
		$parserOption = $parser->getOptions()->getOption( 'extnsfwtag' );

		// If not set
		if($parserOption == '') {
			$NSFWTogglePreference = $this->userOptionsManager->getBoolOption( $parser->getUserIdentity(), 'nsfwtag-prefeditor' );
			// load preference and save as 1/0
			if(isset($_POST['mode']) && $_POST['mode'] == 'preview' && $NSFWTogglePreference) {
				$preference = isset($_POST['shownsfw']);
			} else if(isset( $_GET['shownsfw'] )) {
				$preference = $_GET['shownsfw'] == '1';
			} else {
				$preference = $this->userOptionsManager->getBoolOption( $parser->getUserIdentity(), 'nsfwtag-pref' );
			}
			$parser->getOptions()->setOption( 		'extnsfwtag', $preference ? '1' : '0' );
			$parser->getOutput()->setExtensionData( 'extnsfwtag', $preference ? '1' : '0' );
			return $preference;
		} else {
			// convert 1/0 to true-false
			return $parserOption == '1';
		}
	}

	// Register the tags and function
	public function onParserFirstCallInit( $parser ) {
		$parser->setFunctionHook( 'nsfw',  [ $this, 'renderFunction'  ] );
		$parser->setHook( 'nsfw', [ $this, 'renderTagNSFW' ] );
		$parser->setHook( 'sfw',  [ $this, 'renderTagSFW'  ] );
	}

	// Render <nsfw>
	public function renderTagNSFW( $input, array $args, Parser $parser, PPFrame $frame ) {
		// Call renderTag()
		return self::renderTag( true, $input, $args, $parser, $frame );
	}

	// Render <sfw>
	public function renderTagSFW( $input, array $args, Parser $parser, PPFrame $frame ) {
		// Call renderTag()
		return self::renderTag( false, $input, $args, $parser, $frame );
	}

	// Render tag
	public function renderTag( $isNSFWTag, $input, array $args, Parser $parser, PPFrame $frame ) {
		$preference = $this->getNSFWPreference( $parser );

		// Check if NSFW is enabled, and compare it to $isNSFWTag
		if($preference == $isNSFWTag) {
			// Return the input provided, but wrap it in a <span> with a class to allow the wiki to style the content
			$class = $isNSFWTag ? 'nsfwtag' : 'sfwtag';
			// Parse the input for wikitext before outputing it
			$parsedInput = $parser->recursiveTagParse( $input, $frame );
			return "<span class=\"$class\">".$parsedInput."</span>";
		} else {
			// Return a blank string, as the user should not see this part
			return "";
		}
	}

	// Render function
	// $param1 => $nsfwText
	// $param2 => $sfwText
	public function renderFunction( Parser $parser, $nsfwText = '', $sfwText = '' ) {
		$preference = $this->getNSFWPreference( $parser );

		// Return the input provided, but wrap it in a <span> with a class to allow the wiki to style the content
		$class = $preference ? 'nsfwtag' : 'sfwtag';
		// Parse the input for wikitext before outputing it
		$parsedInput = $parser->recursiveTagParse( $preference ? $nsfwText : $sfwText );
		return "<span class=\"$class\">".$parsedInput."</span>";
	}

	public function onParserOptionsRegister( &$defaults, &$inCacheKey, &$lazyLoad ) {
		// register parser option
		$defaults['extnsfwtag'] = '';
		$inCacheKey['extnsfwtag'] = true;
	}

	public function onOutputPageParserOutput( $out, $parserOutput ): void {
		// if extnsfwtag was used, set a property on OutputPage, so onBeforePageDisplay can see it
		if($parserOutput->getExtensionData( 'extnsfwtag' ) != null)
			$out->setProperty( 'extnsfwtag-used', true );
	}

	public function onBeforePageDisplay( $out, $skin ): void {
		// if used an nsfw or sfw tag, add header and footer
		if($out->getProperty( 'extnsfwtag-used' )) {
			$out->prependHtml( wfMessage('nsfwtag-header')->plain() );
			$out->addWikiMsg( 'nsfwtag-footer' );
		}
	}

	public function onEditPageGetCheckboxesDefinition( $editPage, &$checkboxes ) {
		if($this->userOptionsManager->getBoolOption( RequestContext::getMain()->getUser(), 'nsfwtag-prefeditor')) {
			$checkboxes['shownsfw'] = [
				'id' => 'wpNSFWTagShow',
				'default' => isset( $_POST['shownsfw'] ),
				'label-message' => 'nsfwtag-shownsfw-label',
				'title-message' => 'nsfwtag-shownsfw-title'
			];
		}
	}
}